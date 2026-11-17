<?php
// Guarda el pedido de la tienda en la base de datos.
// OJO: nunca se guardan datos de tarjeta (número, CVV, etc.), solo el método de pago.
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

$idUsuario = (int) ($_SESSION["id_usuario"] ?? $_SESSION["usuario_id"] ?? 0);

if ($idUsuario <= 0) {
    http_response_code(401);
    echo json_encode(["ok" => false, "mensaje" => "Debes iniciar sesión para comprar."]);
    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);
$items = $datos["items"] ?? [];

if (!is_array($items) || count($items) === 0) {
    echo json_encode(["ok" => false, "mensaje" => "El carrito está vacío."]);
    exit;
}

$envios = ["estandar" => 8000.00, "express" => 15000.00, "gratis" => 0.00];
$envio  = $datos["envio"] ?? "";
$pago   = $datos["metodo_pago"] ?? "";

if (!isset($envios[$envio]) || !in_array($pago, ["tarjeta", "nequi", "qr", "pse"], true)) {
    echo json_encode(["ok" => false, "mensaje" => "Método de envío o pago inválido."]);
    exit;
}

$campos = ["nombre", "apellido", "email", "telefono", "direccion", "ciudad", "estado", "codigoPostal", "pais"];
$f = [];
foreach ($campos as $c) {
    $f[$c] = trim($datos["cliente"][$c] ?? "");
    if ($f[$c] === "") {
        echo json_encode(["ok" => false, "mensaje" => "Completa todos los datos del pedido."]);
        exit;
    }
}

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += floatval($it["price"] ?? 0) * intval($it["qty"] ?? 0);
}
$costoEnvio = $envios[$envio];
$total = $subtotal + $costoEnvio;

$conexion->begin_transaction();

try {
    $p = $conexion->prepare("
        INSERT INTO pedidos
        (id_usuario, nombre, apellido, email, telefono, direccion, ciudad, departamento,
         codigo_postal, pais, metodo_envio, metodo_pago, subtotal, costo_envio, total)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $p->bind_param(
        "isssssssssssddd",
        $idUsuario,
        $f["nombre"], $f["apellido"], $f["email"], $f["telefono"],
        $f["direccion"], $f["ciudad"], $f["estado"], $f["codigoPostal"], $f["pais"],
        $envio, $pago, $subtotal, $costoEnvio, $total
    );
    $p->execute();
    $idPedido = $p->insert_id;
    $p->close();

    $i = $conexion->prepare("
        INSERT INTO pedido_items (id_pedido, id_producto, nombre_producto, precio, cantidad)
        VALUES (?,?,?,?,?)
    ");
    foreach ($items as $it) {
        $idProd = intval($it["id"] ?? 0);
        $nombre = substr(trim($it["name"] ?? ""), 0, 150);
        $precio = floatval($it["price"] ?? 0);
        $cant   = intval($it["qty"] ?? 0);
        if ($cant <= 0 || $nombre === "") continue;
        $i->bind_param("iisdi", $idPedido, $idProd, $nombre, $precio, $cant);
        $i->execute();
    }
    $i->close();

    $conexion->commit();
    echo json_encode(["ok" => true, "mensaje" => "Pedido realizado.", "id_pedido" => $idPedido, "total" => $total]);
} catch (Throwable $e) {
    $conexion->rollback();
    echo json_encode(["ok" => false, "mensaje" => "No se pudo guardar el pedido."]);
}

$conexion->close();
