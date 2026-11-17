<?php
require_once "admin-comun.php";

$id = intval($_POST["id_pedido"] ?? 0);
$estado = trim($_POST["estado"] ?? "");

if ($id <= 0 || !in_array($estado, ["pendiente", "enviado", "entregado", "cancelado"], true)) {
    echo json_encode(["ok" => false, "mensaje" => "Datos inválidos."]);
    exit;
}

$q = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
$q->bind_param("si", $estado, $id);
$q->execute();

echo json_encode(["ok" => true, "mensaje" => "Pedido actualizado."]);
$q->close();
$conexion->close();
