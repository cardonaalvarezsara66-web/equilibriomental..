<?php
session_start();

header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

function responder($ok, $mensaje, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode(
        ["ok" => $ok, "mensaje" => $mensaje],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        responder(false, "Método no permitido.", 405);
    }

    $idUsuario = (int)($_SESSION["id_usuario"] ?? 0);

    if ($idUsuario <= 0) {
        responder(false, "Debes iniciar sesión.", 401);
    }

    // Comprueba el rol actual de la cuenta.
    $consultaRol = $conexion->prepare(
        "SELECT rol FROM usuarios WHERE id_usuario = ? LIMIT 1"
    );
    $consultaRol->bind_param("i", $idUsuario);
    $consultaRol->execute();
    $usuario = $consultaRol->get_result()->fetch_assoc();
    $consultaRol->close();

    $rol = strtolower(trim($usuario["rol"] ?? ""));

    if (!in_array($rol, ["administradora", "superadministradora"], true)) {
        responder(false, "Acceso solo para administradoras.", 403);
    }

    // Obtiene el perfil profesional asociado a la cuenta.
    $consultaPerfil = $conexion->prepare(
        "SELECT id_psicologa
         FROM psicologas
         WHERE id_usuario = ?
         LIMIT 1"
    );
    $consultaPerfil->bind_param("i", $idUsuario);
    $consultaPerfil->execute();
    $perfil = $consultaPerfil->get_result()->fetch_assoc();
    $consultaPerfil->close();

    if (!$perfil) {
        responder(false, "Esta cuenta no tiene un perfil de psicóloga.", 403);
    }

    $idPsicologa = (int)$perfil["id_psicologa"];
    $idCita = (int)($_POST["id_cita"] ?? 0);
    $accion = trim($_POST["accion"] ?? "");

    $estadosValidos = [
        "confirmar" => "confirmada",
        "cancelar" => "cancelada",
        "realizar" => "realizada"
    ];

    if ($idCita <= 0 || !isset($estadosValidos[$accion])) {
        responder(false, "Datos inválidos.", 400);
    }

    $nuevoEstado = $estadosValidos[$accion];

    // Solo permite actualizar una cita asignada a este perfil.
    $actualizar = $conexion->prepare(
        "UPDATE citas
         SET estado = ?
         WHERE id_cita = ? AND id_psicologa = ?"
    );
    $actualizar->bind_param(
        "sii",
        $nuevoEstado,
        $idCita,
        $idPsicologa
    );
    $actualizar->execute();

    if ($actualizar->affected_rows > 0) {
        $actualizar->close();
        $conexion->close();
        responder(true, "Cita actualizada.");
    }

    $actualizar->close();
    $conexion->close();
    responder(false, "No se encontró la cita o no pertenece a tu perfil.", 404);

} catch (Throwable $e) {
    error_log("Error en actualizar-cita.php: " . $e->getMessage());

    responder(
        false,
        "Ocurrió un error al actualizar la cita. Revisa el registro de errores de PHP.",
        500
    );
}
?>
