<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";

// login.php guarda la sesión con la clave "id_usuario".
if (!isset($_SESSION["id_usuario"])) {
    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesión."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$resultado = $conexion->query("
    SELECT id_psicologa, nombre_completo, especialidad, foto, calificacion
    FROM psicologas
    WHERE activa = 1
    ORDER BY nombre_completo
");

$psicologas = [];

while ($fila = $resultado->fetch_assoc()) {
    $psicologas[] = $fila;
}

$conexion->close();

echo json_encode([
    "ok" => true,
    "psicologas" => $psicologas
], JSON_UNESCAPED_UNICODE);