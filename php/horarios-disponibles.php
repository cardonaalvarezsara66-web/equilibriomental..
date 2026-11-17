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

$id_psicologa = intval($_GET["id_psicologa"] ?? 0);
$fecha = trim($_GET["fecha"] ?? "");

if (
    $id_psicologa <= 0 ||
    $fecha === "" ||
    !strtotime($fecha)
) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Datos inválidos."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// No permitir fechas pasadas.
if (strtotime($fecha) < strtotime(date("Y-m-d"))) {
    echo json_encode([
        "ok" => true,
        "horarios" => []
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 1 = lunes ... 7 = domingo.
$diaSemana = intval(date("N", strtotime($fecha)));

// Obtener los bloques de horario de la psicóloga para ese día.
$consulta = $conexion->prepare("
    SELECT hora_inicio, hora_fin
    FROM horarios_disponibles
    WHERE id_psicologa = ?
      AND dia_semana = ?
");

$consulta->bind_param("ii", $id_psicologa, $diaSemana);
$consulta->execute();

$bloques = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();

// Obtener las citas ya ocupadas ese día.
$ocupadas = [];

$consultaCitas = $conexion->prepare("
    SELECT hora
    FROM citas
    WHERE id_psicologa = ?
      AND fecha = ?
      AND estado IN ('pendiente', 'confirmada')
");

$consultaCitas->bind_param("is", $id_psicologa, $fecha);
$consultaCitas->execute();

$resultadoCitas = $consultaCitas->get_result();

while ($fila = $resultadoCitas->fetch_assoc()) {
    $ocupadas[] = substr($fila["hora"], 0, 5);
}

$consultaCitas->close();
$conexion->close();

// Generar franjas de una hora dentro de cada bloque.
$disponibles = [];
$hoy = date("Y-m-d") === $fecha;
$horaActual = intval(date("H"));

foreach ($bloques as $bloque) {
    $inicio = intval(substr($bloque["hora_inicio"], 0, 2));
    $fin = intval(substr($bloque["hora_fin"], 0, 2));

    for ($h = $inicio; $h < $fin; $h++) {
        if ($hoy && $h <= $horaActual) {
            continue;
        }

        $horaTexto = sprintf("%02d:00", $h);

        if (!in_array($horaTexto, $ocupadas, true)) {
            $disponibles[] = $horaTexto;
        }
    }
}

echo json_encode([
    "ok" => true,
    "horarios" => $disponibles
], JSON_UNESCAPED_UNICODE);