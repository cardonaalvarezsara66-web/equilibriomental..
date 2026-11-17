<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";


if (!isset($_SESSION["usuario_id"])) {

    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesión."
    ]);

    exit;
}


$id_usuario = $_SESSION["usuario_id"];


$consulta = $conexion->prepare("
    SELECT id_dispositivo, nombre_dispositivo, tipo, bluetooth_id, vinculado_en, ultima_conexion, activo
    FROM dispositivos_vinculados
    WHERE id_usuario = ?
    ORDER BY vinculado_en DESC
");

$consulta->bind_param("i", $id_usuario);
$consulta->execute();

$resultado = $consulta->get_result();

$dispositivos = [];

while ($fila = $resultado->fetch_assoc()) {
    $dispositivos[] = $fila;
}

$consulta->close();
$conexion->close();


echo json_encode([
    "ok" => true,
    "dispositivos" => $dispositivos
]);

?>
