<?php
session_start();

header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

function responder($ok, $mensaje, $codigo = 200, $datos = []) {
    http_response_code($codigo);

    echo json_encode(
        array_merge([
            "ok" => $ok,
            "mensaje" => $mensaje
        ], $datos),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {
    if (!usuarioTieneRolActual($conexion, ["superadministradora"])) {
        responder(
            false,
            "Acceso solo para la superadministradora.",
            403
        );
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $id = filter_input(INPUT_POST, "id_usuario", FILTER_VALIDATE_INT);
        $rol = $_POST["rol"] ?? "";

        $rolesPermitidos = [
            "usuario",
            "administradora",
            "superadministradora"
        ];

        if (!$id || !in_array($rol, $rolesPermitidos, true)) {
            responder(false, "Datos inválidos.", 400);
        }

        // El inicio de sesión guarda la clave como id_usuario.
        $idSesion = (int)($_SESSION["id_usuario"] ?? 0);

        if ($id === $idSesion && $rol !== "superadministradora") {
            responder(
                false,
                "No puedes quitarte el rol de superadministradora.",
                400
            );
        }

        $actual = $conexion->prepare(
            "SELECT rol, nombre FROM usuarios WHERE id_usuario = ?"
        );
        $actual->bind_param("i", $id);
        $actual->execute();

        $filaUsuario = $actual->get_result()->fetch_assoc();
        $actual->close();

        if (!$filaUsuario) {
            responder(false, "No se encontró esa cuenta.", 404);
        }

        if (
            $filaUsuario["rol"] === "superadministradora"
            && $rol !== "superadministradora"
        ) {
            $resultado = $conexion->query(
                "SELECT COUNT(*) AS total
                 FROM usuarios
                 WHERE rol = 'superadministradora'"
            );

            $cantidad = (int)$resultado->fetch_assoc()["total"];

            if ($cantidad <= 1) {
                responder(
                    false,
                    "Debe quedar al menos una superadministradora.",
                    400
                );
            }
        }

        $conexion->begin_transaction();

        $actualizar = $conexion->prepare(
            "UPDATE usuarios SET rol = ? WHERE id_usuario = ?"
        );
        $actualizar->bind_param("si", $rol, $id);
        $actualizar->execute();
        $actualizar->close();

        // Si ahora es administradora, crear su perfil de psicóloga
        // solamente cuando todavía no tenga uno.
        if ($rol === "administradora") {
            $buscarPerfil = $conexion->prepare(
                "SELECT id_psicologa
                 FROM psicologas
                 WHERE id_usuario = ?"
            );
            $buscarPerfil->bind_param("i", $id);
            $buscarPerfil->execute();

            $tienePerfil = (bool)$buscarPerfil->get_result()->fetch_assoc();
            $buscarPerfil->close();

            if (!$tienePerfil) {
                $nombreCompleto = $filaUsuario["nombre"];
                $especialidad = "Psicología general";

                $crearPerfil = $conexion->prepare(
                    "INSERT INTO psicologas
                     (id_usuario, nombre_completo, especialidad)
                     VALUES (?, ?, ?)"
                );
                $crearPerfil->bind_param(
                    "iss",
                    $id,
                    $nombreCompleto,
                    $especialidad
                );
                $crearPerfil->execute();
                $crearPerfil->close();
            }
        }

        $conexion->commit();

        responder(true, "Rol actualizado.");
    }

    $resultado = $conexion->query(
        "SELECT id_usuario, nombre, correo, rol, creado_en
         FROM usuarios
         ORDER BY creado_en DESC"
    );

    echo json_encode([
        "ok" => true,
        "usuarios" => $resultado->fetch_all(MYSQLI_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($conexion) && $conexion instanceof mysqli) {
        try {
            $conexion->rollback();
        } catch (Throwable $ignorado) {
            // No hay una transacción activa que revertir.
        }
    }

    error_log("Error en administrar-usuarios.php: " . $e->getMessage());

    responder(
        false,
        "Error interno al procesar la solicitud. Revisa el registro de errores de PHP.",
        500
    );
}