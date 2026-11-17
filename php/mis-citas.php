<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesión."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$id_usuario = (int) $_SESSION["id_usuario"];

$consulta = $conexion->prepare("
    SELECT
        c.id_cita,
        c.fecha,
        c.hora,
        c.estado,
        c.notas,
        p.nombre_completo AS psicologa
    FROM citas c
    JOIN psicologas p
        ON p.id_psicologa = c.id_psicologa
    WHERE c.id_usuario = ?
    ORDER BY c.fecha DESC, c.hora DESC
");

$consulta->bind_param("i", $id_usuario);
$consulta->execute();

$citas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);

$consulta->close();
$conexion->close();

echo json_encode([
    "ok" => true,
    "citas" => $citas
], JSON_UNESCAPED_UNICODE);