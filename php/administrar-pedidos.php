<?php
require_once "admin-comun.php";

try {
    // p.* obtiene únicamente las columnas que existen en pedidos.
    // El correo se obtiene de usuarios, no de pedidos.
    $consultaPedidos = $conexion->query("
        SELECT
            p.*,
            u.nombre AS usuario,
            u.correo AS correo,
            u.correo AS correo_usuario
        FROM pedidos p
        JOIN usuarios u ON u.id_usuario = p.id_usuario
        ORDER BY p.creado_en DESC
    ");

    $pedidos = $consultaPedidos->fetch_all(MYSQLI_ASSOC);

    $consultaItems = $conexion->query("
        SELECT id_pedido, nombre_producto, precio, cantidad
        FROM pedido_items
    ");

    $itemsPorPedido = [];

    while ($fila = $consultaItems->fetch_assoc()) {
        $idPedido = (int)$fila["id_pedido"];
        $itemsPorPedido[$idPedido][] = $fila;
    }

    foreach ($pedidos as &$pedido) {
        $idPedido = (int)$pedido["id_pedido"];
        $pedido["items"] = $itemsPorPedido[$idPedido] ?? [];
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
        "mensaje" => "No se pudieron cargar los pedidos.",
        "detalle" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}