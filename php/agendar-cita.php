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

$id_psicologa = intval($_POST["id_psicologa"] ?? 0);
$fecha = trim($_POST["fecha"] ?? "");
$hora = trim($_POST["hora"] ?? "");
$notas = trim($_POST["notas"] ?? "");

if ($id_psicologa <= 0 || $fecha === "" || $hora === "") {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Completa la psicóloga, la fecha y la hora."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (strtotime($fecha) < strtotime(date("Y-m-d"))) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No puedes agendar una fecha pasada."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// La hora llega como HH:MM y se guarda como HH:MM:00.
$horaSql = $hora . ":00";

$insertar = $conexion->prepare("
    INSERT INTO citas (
        id_usuario,
        id_psicologa,
        fecha,
        hora,
        estado,
        notas
    )
    VALUES (?, ?, ?, ?, 'pendiente', ?)
");

$insertar->bind_param(
    "iisss",
    $id_usuario,
    $id_psicologa,
    $fecha,
    $horaSql,
    $notas
);

if ($insertar->execute()) {
    echo json_encode([
        "ok" => true,
        "mensaje" => "Cita solicitada. Quedará pendiente hasta que la psicóloga la confirme.",
        "id_cita" => $insertar->insert_id
    ], JSON_UNESCAPED_UNICODE);

    $insertar->close();
    $conexion->close();
    exit;
}

// Error 1062 significa que el horario ya está ocupado.
$mensaje = ($insertar->errno === 1062)
    ? "Ese horario ya fue tomado por otra persona. Elige otro."
    : "No se pudo agendar la cita.";

echo json_encode([
    "ok" => false,
    "mensaje" => $mensaje
], JSON_UNESCAPED_UNICODE);

$insertar->close();
$conexion->close();