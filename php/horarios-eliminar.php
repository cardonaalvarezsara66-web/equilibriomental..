<?php
// Elimina un bloque de horario, solo si pertenece a la psicóloga en sesión.
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

$id_horario = intval($_POST["id_horario"] ?? 0);

$consulta = $conexion->prepare("DELETE FROM horarios_disponibles WHERE id_horario = ? AND id_psicologa = ?");
$consulta->bind_param("ii", $id_horario, $_SESSION["id_psicologa"]);
$consulta->execute();

echo json_encode(["ok" => $consulta->affected_rows > 0, "mensaje" => $consulta->affected_rows > 0 ? "Horario eliminado." : "No encontrado."]);
$consulta->close();
$conexion->close();
