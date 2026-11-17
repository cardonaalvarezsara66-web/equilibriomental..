<?php
require_once "admin-comun.php";

try {
    $res = $conexion->query("
        SELECT
    p.*,
    u.nombre AS usuario,
    u.correo AS correo,
    u.correo AS correo_usuario
        FROM pedidos p
        JOIN usuarios u ON u.id_usuario = p.id_usuario
        ORDER BY p.creado_en DESC
    ");

    $pedidos = $res->fetch_all(MYSQLI_ASSOC);

    $items = [];

    $r2 = $conexion->query("
        SELECT id_pedido, nombre_producto, precio, cantidad
        FROM pedido_items
    ");

    while ($fila = $r2->fetch_assoc()) {
        $items[$fila["id_pedido"]][] = $fila;
    }

    foreach ($pedidos as &$pedido) {
        $idPedido = $pedido["id_pedido"];
        $pedido["items"] = $items[$idPedido] ?? [];
    }
    unset($pedido);

    echo json_encode([
        "ok" => true,
        "pedidos" => $pedidos
    ], JSON_UNESCAPED_UNICODE);

    $conexion->close();

} catch (Throwable $e) {
    error_log("Error en administrar-pedidos.php: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudieron cargar los pedidos. Revisa las columnas de las tablas pedidos y pedido_items."
    ], JSON_UNESCAPED_UNICODE);

    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
?>