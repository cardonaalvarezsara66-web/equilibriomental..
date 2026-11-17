<?php
// Los bloques de horario de la psicóloga que inició sesión.
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

if (
    !isset($_SESSION["usuario_id"]) ||
    ($_SESSION["rol"] ?? "") !== "administradora" ||
    empty($_SESSION["id_psicologa"])
) {
    http_response_code(403);
    echo json_encode(["ok" => false, "mensaje" => "Acceso solo para administradoras."]);
    exit;
}

$id_psicologa = $_SESSION["id_psicologa"];

$consulta = $conexion->prepare("
    SELECT id_horario, dia_semana, hora_inicio, hora_fin
    FROM horarios_disponibles
    WHERE id_psicologa = ?
    ORDER BY dia_semana, hora_inicio
");
$consulta->bind_param("i", $id_psicologa);
$consulta->execute();

echo json_encode(["ok" => true, "horarios" => $consulta->get_result()->fetch_all(MYSQLI_ASSOC)]);
$consulta->close();
$conexion->close();
