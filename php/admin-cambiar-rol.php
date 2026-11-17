<?php
// El administrador decide quién es usuario, administradora (psicóloga) o superadmin.
require_once "admin-comun.php";

$id  = intval($_POST["id_usuario"] ?? 0);
$rol = trim($_POST["rol"] ?? "");
$especialidad = trim($_POST["especialidad"] ?? "");

if ($id <= 0 || !in_array($rol, ["usuario", "administradora", "superadmin"], true)) {
    echo json_encode(["ok" => false, "mensaje" => "Datos inválidos."]);
    exit;
}

if ($id === intval($_SESSION["usuario_id"])) {
    echo json_encode(["ok" => false, "mensaje" => "No puedes cambiar tu propio rol."]);
    exit;
}

$q = $conexion->prepare("SELECT nombre, rol FROM usuarios WHERE id_usuario = ?");
$q->bind_param("i", $id);
$q->execute();
$u = $q->get_result()->fetch_assoc();
$q->close();

if (!$u) {
    echo json_encode(["ok" => false, "mensaje" => "Usuario no encontrado."]);
    exit;
}

// Si era administradora y pasa a otro rol: no puede tener citas activas
if ($u["rol"] === "administradora" && $rol !== "administradora") {
    $c = $conexion->prepare("
        SELECT COUNT(*) AS n FROM citas c
        JOIN psicologas p ON p.id_psicologa = c.id_psicologa
        WHERE p.id_usuario = ? AND c.estado IN ('pendiente','confirmada') AND c.fecha >= CURDATE()
    ");
    $c->bind_param("i", $id);
    $c->execute();
    $n = $c->get_result()->fetch_assoc()["n"];
    $c->close();

    if ($n > 0) {
        echo json_encode(["ok" => false, "mensaje" => "Tiene $n cita(s) pendientes o confirmadas. Cancélalas o reasígnalas antes de quitarle el rol."]);
        exit;
    }
}

$conexion->begin_transaction();

try {
    $up = $conexion->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
    $up->bind_param("si", $rol, $id);
    $up->execute();
    $up->close();

    if ($rol === "administradora") {
        // Crear (o reactivar) su ficha de psicóloga
        $esp = ($especialidad !== "") ? $especialidad : null;
        $ps = $conexion->prepare("
            INSERT INTO psicologas (id_usuario, nombre_completo, especialidad, activa)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE activa = 1,
              especialidad = COALESCE(VALUES(especialidad), especialidad)
        ");
        $ps->bind_param("iss", $id, $u["nombre"], $esp);
        $ps->execute();
        $ps->close();
    } else {
        // Ya no es psicóloga: se desactiva (se conserva su historial de citas)
        $ps = $conexion->prepare("UPDATE psicologas SET activa = 0 WHERE id_usuario = ?");
        $ps->bind_param("i", $id);
        $ps->execute();
        $ps->close();
    }

    $conexion->commit();
    echo json_encode(["ok" => true, "mensaje" => "Rol actualizado. Se aplica cuando esa persona vuelva a iniciar sesión."]);
} catch (Throwable $e) {
    $conexion->rollback();
    echo json_encode(["ok" => false, "mensaje" => "No se pudo cambiar el rol."]);
}

$conexion->close();
