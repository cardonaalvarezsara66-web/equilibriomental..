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

$idUsuario = (int)$_SESSION["id_usuario"];

// Busca el perfil de psicóloga vinculado a la cuenta de Ximena.
$buscarPerfil = $conexion->prepare("
    SELECT id_psicologa
    FROM psicologas
    WHERE id_usuario = ?
    LIMIT 1
");
$buscarPerfil->bind_param("i", $idUsuario);
$buscarPerfil->execute();

$perfil = $buscarPerfil->get_result()->fetch_assoc();
$buscarPerfil->close();

if (!$perfil) {
    http_response_code(403);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Esta cuenta no tiene un perfil de psicóloga."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idPsicologa = (int)$perfil["id_psicologa"];

$consulta = $conexion->prepare("
    SELECT
        c.id_cita,
        c.fecha,
        c.hora,
        c.estado,
        c.notas,
        u.nombre AS usuario,
        u.correo AS correo_usuario
    FROM citas c
    JOIN usuarios u ON u.id_usuario = c.id_usuario
    WHERE c.id_psicologa = ?
    ORDER BY c.fecha ASC, c.hora ASC
");
$consulta->bind_param("i", $idPsicologa);
$consulta->execute();

$citas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);

$consulta->close();
$conexion->close();

echo json_encode([
    "ok" => true,
    "citas" => $citas
], JSON_UNESCAPED_UNICODE);
?>