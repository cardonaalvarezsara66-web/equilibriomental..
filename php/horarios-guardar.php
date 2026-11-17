<?php
// Agrega un bloque de horario disponible (día + hora inicio/fin) para la psicóloga en sesión.
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
$dia    = intval($_POST["dia_semana"] ?? 0);
$inicio = trim($_POST["hora_inicio"] ?? "");
$fin    = trim($_POST["hora_fin"] ?? "");

if ($dia < 1 || $dia > 7 || !preg_match('/^\d{2}:\d{2}$/', $inicio) || !preg_match('/^\d{2}:\d{2}$/', $fin) || $inicio >= $fin) {
    echo json_encode(["ok" => false, "mensaje" => "Datos de horario inválidos."]);
    exit;
}

$consulta = $conexion->prepare("
    INSERT INTO horarios_disponibles (id_psicologa, dia_semana, hora_inicio, hora_fin)
    VALUES (?, ?, ?, ?)
");
$consulta->bind_param("iiss", $id_psicologa, $dia, $inicio, $fin);

if ($consulta->execute()) {
    echo json_encode(["ok" => true, "mensaje" => "Horario agregado.", "id_horario" => $consulta->insert_id]);
} else {
    echo json_encode(["ok" => false, "mensaje" => "No se pudo guardar el horario."]);
}

$consulta->close();
$conexion->close();
