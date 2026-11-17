<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

if (empty($_SESSION["usuario_id"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "mensaje" => "Inicia sesión para guardar tu pedido."]);
    exit;
}

$required = ["nombre_destinatario", "correo", "telefono", "direccion", "ciudad", "departamento", "codigo_postal", "pais", "metodo_envio"];
$data = [];
foreach ($required as $key) {
    $data[$key] = trim($_POST[$key] ?? "");
    if ($data[$key] === "") {
        http_response_code(400);
        echo json_encode(["ok" => false, "mensaje" => "Falta completar la dirección y los datos de entrega."]);
        exit;
    }
}
$items = json_decode($_POST["items"] ?? "", true);
if (!is_array($items) || count($items) < 1 || count($items) > 50) {
    http_response_code(400);
    echo json_encode(["ok" => false, "mensaje" => "El carrito no es válido."]);
    exit;
}

// El pago no está integrado; registrar el pedido no procesa ni cobra una tarjeta.
$total = 0.0;
$cleanItems = [];
foreach ($items as $item) {
    $name = trim((string)($item["name"] ?? ""));
    $qty = filter_var($item["qty"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 99]]);
    $price = filter_var($item["price"] ?? null, FILTER_VALIDATE_FLOAT);
    if ($name === "" || !$qty || $price === false || $price < 0 || $price > 100000000) {
        http_response_code(400);
        echo json_encode(["ok" => false, "mensaje" => "Hay un producto inválido en el carrito."]);
        exit;
    }
    $cleanItems[] = [$name, $qty, (float)$price];
    $total += $qty * (float)$price;
}
$envio = $_POST["metodo_envio"] === "express" ? 15 : ($_POST["metodo_envio"] === "estandar" ? 8 : 0);
$total += $envio;

$conexion->begin_transaction();
try {
    $stmt = $conexion->prepare("INSERT INTO pedidos (id_usuario,total,nombre_destinatario,correo,telefono,direccion,ciudad,departamento,codigo_postal,pais,metodo_envio) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $uid = (int)$_SESSION["usuario_id"];
    $stmt->bind_param("idsssssssss", $uid, $total, $data["nombre_destinatario"], $data["correo"], $data["telefono"], $data["direccion"], $data["ciudad"], $data["departamento"], $data["codigo_postal"], $data["pais"], $data["metodo_envio"]);
    $stmt->execute();
    $idPedido = $stmt->insert_id;
    $stmt->close();
    $line = $conexion->prepare("INSERT INTO pedido_items (id_pedido,nombre_producto,cantidad,precio_unitario) VALUES (?,?,?,?)");
    foreach ($cleanItems as [$name, $qty, $price]) {
        $line->bind_param("isid", $idPedido, $name, $qty, $price);
        $line->execute();
    }
    $line->close();
    $conexion->commit();
    echo json_encode(["ok" => true, "mensaje" => "Pedido registrado.", "id_pedido" => $idPedido]);
} catch (Throwable $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(["ok" => false, "mensaje" => "No se pudo guardar el pedido."]);
}
