<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

function adminResponder($ok, $mensaje, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode([
        "ok" => $ok,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)($_SESSION["id_usuario"] ?? 0);

if ($idUsuario <= 0) {
    adminResponder(false, "Debes iniciar sesión.", 401);
}

try {
    $consulta = $conexion->prepare(
        "SELECT rol FROM usuarios WHERE id_usuario = ? LIMIT 1"
    );
    $consulta->bind_param("i", $idUsuario);
    $consulta->execute();

    $fila = $consulta->get_result()->fetch_assoc();
    $consulta->close();

    $rol = strtolower(trim($fila["rol"] ?? ""));

    if (!in_array($rol, ["superadministradora", "superadmin"], true)) {
        adminResponder(false, "Acceso solo para la superadministradora.", 403);
    }
} catch (Throwable $e) {
    error_log("Error en admin-comun.php: " . $e->getMessage());
    adminResponder(false, "No se pudo verificar el acceso.", 500);
}