<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";


// ═══════════════════════════════════════════════
// REQUIERE SESIÓN
// ═══════════════════════════════════════════════

if (!isset($_SESSION["usuario_id"])) {

    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesión."
    ]);

    exit;
}


$id_usuario = $_SESSION["usuario_id"];

$nombre_dispositivo = trim($_POST["nombre_dispositivo"] ?? "");
$tipo = trim($_POST["tipo"] ?? "otro");
$bluetooth_id = trim($_POST["bluetooth_id"] ?? "");

$tiposValidos = ["anillo", "reloj", "arete", "otro"];
if (!in_array($tipo, $tiposValidos)) {
    $tipo = "otro";
}


if ($nombre_dispositivo === "") {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Falta el nombre del dispositivo."
    ]);

    exit;
}


$insertar = $conexion->prepare("
    INSERT INTO dispositivos_vinculados
        (id_usuario, nombre_dispositivo, tipo, bluetooth_id, ultima_conexion)
    VALUES (?, ?, ?, ?, NOW())
");

$insertar->bind_param(
    "isss",
    $id_usuario,
    $nombre_dispositivo,
    $tipo,
    $bluetooth_id
);


if ($insertar->execute()) {

    echo json_encode([
        "ok" => true,
        "mensaje" => "Dispositivo vinculado.",
        "id_dispositivo" => $insertar->insert_id
    ]);

    $insertar->close();
    $conexion->close();
    exit;
}


echo json_encode([
    "ok" => false,
    "mensaje" => "No se pudo guardar el dispositivo."
]);

$insertar->close();
$conexion->close();

?>
