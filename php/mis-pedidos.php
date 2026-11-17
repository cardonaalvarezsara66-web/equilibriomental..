<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

$idUsuario = (int)($_SESSION["usuario_id"] ?? $_SESSION["id_usuario"] ?? 0);
if ($idUsuario <= 0) {
    http_response_code(401);
    echo json_encode(["ok" => false, "mensaje" => "Debes iniciar sesión para rastrear tus pedidos."], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $consulta = $conexion->prepare(
        "SELECT id_pedido, estado, creado_en, metodo_envio, total
         FROM pedidos
         WHERE id_usuario = ?
         ORDER BY creado_en DESC, id_pedido DESC"
    );
    $consulta->bind_param("i", $idUsuario);
    $consulta->execute();
    $pedidos = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
    $consulta->close();
    echo json_encode(["ok" => true, "pedidos" => $pedidos], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en mis-pedidos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ok" => false, "mensaje" => "No se pudieron consultar los pedidos."], JSON_UNESCAPED_UNICODE);
}
$conexion->close();
?>
