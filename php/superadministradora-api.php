<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

if (!usuarioTieneRolActual($conexion, ["superadministradora"])) {
    http_response_code(403);
    echo json_encode(["ok" => false, "mensaje" => "Esta sección es exclusiva de la superadministradora."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $usuarios = $conexion->query("SELECT id_usuario,nombre,correo,rol,creado_en FROM usuarios ORDER BY creado_en DESC")->fetch_all(MYSQLI_ASSOC);
    $psicologas = $conexion->query("
    SELECT id_psicologa, nombre_completo, especialidad
    FROM psicologas
    WHERE activa = 1
    ORDER BY nombre_completo
")->fetch_all(MYSQLI_ASSOC);

$horarios = $conexion->query("
    SELECT
        h.id_horario,
        h.id_psicologa,
        h.dia_semana,
        h.hora_inicio,
        h.hora_fin,
        p.nombre_completo AS psicologa
    FROM horarios_disponibles h
    JOIN psicologas p
        ON p.id_psicologa = h.id_psicologa
    WHERE p.activa = 1
    ORDER BY p.nombre_completo, h.dia_semana, h.hora_inicio
")->fetch_all(MYSQLI_ASSOC);
    $citas = $conexion->query("SELECT c.id_cita,c.fecha,c.hora,c.estado,c.notas,u.nombre AS usuario,u.correo,p.nombre_completo AS psicologa FROM citas c JOIN usuarios u ON u.id_usuario=c.id_usuario JOIN psicologas p ON p.id_psicologa=c.id_psicologa ORDER BY c.fecha DESC,c.hora DESC")->fetch_all(MYSQLI_ASSOC);
    $dispositivos = $conexion->query("SELECT d.id_dispositivo,d.nombre_dispositivo,d.tipo,d.activo,d.vinculado_en,u.nombre AS usuario,u.correo FROM dispositivos_vinculados d JOIN usuarios u ON u.id_usuario=d.id_usuario ORDER BY d.vinculado_en DESC")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(["ok"=>true,"usuarios"=>$usuarios,"psicologas"=>$psicologas,"horarios"=>$horarios,"citas"=>$citas,"dispositivos"=>$dispositivos]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["ok"=>false,"mensaje"=>"Método no permitido."]);
    exit;
}

$accion = $_POST["accion"] ?? "";

if ($accion === "guardar_horario") {
    $idPsicologa = filter_input(INPUT_POST, "id_psicologa", FILTER_VALIDATE_INT);
    $dia = filter_input(INPUT_POST, "dia_semana", FILTER_VALIDATE_INT);
    $inicio = trim($_POST["hora_inicio"] ?? "");
    $fin = trim($_POST["hora_fin"] ?? "");
    if (!$idPsicologa || !$dia || $dia < 1 || $dia > 7 || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $inicio) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $fin) || $inicio >= $fin) {
        http_response_code(400);
        echo json_encode(["ok"=>false,"mensaje"=>"Revisa la psicóloga, el día y el rango de horas."]);
        exit;
    }
    $existePsicologa = $conexion->prepare("SELECT id_psicologa FROM psicologas WHERE id_psicologa=?");
    $existePsicologa->bind_param("i", $idPsicologa);
    $existePsicologa->execute();
    if (!$existePsicologa->get_result()->fetch_assoc()) {
        http_response_code(400);
        echo json_encode(["ok"=>false,"mensaje"=>"No se encontró esa psicóloga."]);
        exit;
    }
    $existePsicologa->close();
    $conflicto = $conexion->prepare("SELECT id_horario FROM horarios_disponibles WHERE id_psicologa=? AND dia_semana=? AND hora_inicio < ? AND hora_fin > ? LIMIT 1");
    $conflicto->bind_param("iiss", $idPsicologa, $dia, $fin, $inicio);
    $conflicto->execute();
    if ($conflicto->get_result()->fetch_assoc()) {
        http_response_code(409);
        echo json_encode(["ok"=>false,"mensaje"=>"Ese rango se cruza con otro horario de ese día."]);
        exit;
    }
    $conflicto->close();
    $insertar = $conexion->prepare("INSERT INTO horarios_disponibles (id_psicologa,dia_semana,hora_inicio,hora_fin) VALUES (?,?,?,?)");
    $insertar->bind_param("iiss", $idPsicologa, $dia, $inicio, $fin);
    $ok = $insertar->execute();
    echo json_encode(["ok"=>$ok,"mensaje"=>$ok ? "Horario semanal guardado." : "No se pudo guardar el horario."]);
    exit;
}

if ($accion === "eliminar_horario") {
    $idHorario = filter_input(INPUT_POST, "id_horario", FILTER_VALIDATE_INT);
    if (!$idHorario) {
        http_response_code(400);
        echo json_encode(["ok"=>false,"mensaje"=>"Horario inválido."]);
        exit;
    }
    $stmt = $conexion->prepare("DELETE FROM horarios_disponibles WHERE id_horario=?");
    $stmt->bind_param("i", $idHorario);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    echo json_encode(["ok"=>$ok,"mensaje"=>$ok ? "Horario eliminado." : "No se encontró el horario."]);
    exit;
}

if ($accion === "actualizar_cita") {
    $idCita = filter_input(INPUT_POST, "id_cita", FILTER_VALIDATE_INT);
    $estado = $_POST["estado"] ?? "";
    if (!$idCita || !in_array($estado, ["pendiente","confirmada","cancelada","realizada"], true)) {
        http_response_code(400);
        echo json_encode(["ok"=>false,"mensaje"=>"Cita o estado inválido."]);
        exit;
    }
    $stmt = $conexion->prepare("UPDATE citas SET estado=? WHERE id_cita=?");
    $stmt->bind_param("si", $estado, $idCita);
    $ok = $stmt->execute() && $stmt->affected_rows >= 0;
    echo json_encode(["ok"=>$ok,"mensaje"=>$ok ? "Estado de la cita actualizado." : "No se pudo actualizar la cita."]);
    exit;
}

http_response_code(400);
echo json_encode(["ok"=>false,"mensaje"=>"Acción desconocida."]);
