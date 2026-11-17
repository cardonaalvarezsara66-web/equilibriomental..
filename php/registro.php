<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";


// ═══════════════════════════════════════════════
// RECIBIR DATOS
// ═══════════════════════════════════════════════

$usuario = trim($_POST["usuario"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$clave = $_POST["clave"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";


// ═══════════════════════════════════════════════
// VALIDAR CAMPOS
// ═══════════════════════════════════════════════

if (
    $usuario === "" ||
    $correo === "" ||
    $clave === "" ||
    $confirmar === ""
) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Completa todos los campos."
    ]);

    exit;
}


// ═══════════════════════════════════════════════
// VALIDAR CORREO
// ═══════════════════════════════════════════════

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Introduce un correo electrónico válido."
    ]);

    exit;
}


// ═══════════════════════════════════════════════
// VALIDAR CONTRASEÑAS
// ═══════════════════════════════════════════════

if ($clave !== $confirmar) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Las contraseñas no coinciden."
    ]);

    exit;
}


if (strlen($clave) < 6) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La contraseña debe tener al menos 6 caracteres."
    ]);

    exit;
}


// ═══════════════════════════════════════════════
// COMPROBAR SI YA EXISTE
// ═══════════════════════════════════════════════

$verificar = $conexion->prepare("
    SELECT id_usuario
    FROM usuarios
    WHERE nombre = ? OR correo = ?
    LIMIT 1
");


if (!$verificar) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al preparar la consulta."
    ]);

    $conexion->close();

    exit;
}


$verificar->bind_param(
    "ss",
    $usuario,
    $correo
);


$verificar->execute();

$verificar->store_result();


if ($verificar->num_rows > 0) {

    $verificar->close();
    $conexion->close();

    echo json_encode([
        "ok" => false,
        "mensaje" => "Ese usuario o correo ya está registrado."
    ]);

    exit;
}


$verificar->close();


// ═══════════════════════════════════════════════
// CIFRAR CONTRASEÑA
// ═══════════════════════════════════════════════

$claveCifrada =
    password_hash(
        $clave,
        PASSWORD_DEFAULT
    );


// ═══════════════════════════════════════════════
// INSERTAR USUARIO
// ═══════════════════════════════════════════════

$insertar = $conexion->prepare("
    INSERT INTO usuarios
    (nombre, correo, contrasena)
    VALUES (?, ?, ?)
");


if (!$insertar) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al preparar el registro."
    ]);

    $conexion->close();

    exit;
}


$insertar->bind_param(
    "sss",
    $usuario,
    $correo,
    $claveCifrada
);


// ═══════════════════════════════════════════════
// EJECUTAR
// ═══════════════════════════════════════════════

if ($insertar->execute()) {

    $id =
        $insertar->insert_id;


    // Crear sesión
    session_regenerate_id(true);


    $_SESSION["usuario_id"] =
        $id;

    $_SESSION["usuario"] =
        $usuario;

    $_SESSION["correo"] =
        $correo;


    $insertar->close();
    $conexion->close();


    echo json_encode([

        "ok" => true,

        "mensaje" =>
            "Cuenta creada correctamente.",

        "usuario" => [
            "id" => $id,
            "nombre" => $usuario,
            "correo" => $correo
        ]

    ]);

    exit;
}


// ═══════════════════════════════════════════════
// ERROR
// ═══════════════════════════════════════════════

$insertar->close();
$conexion->close();


echo json_encode([
    "ok" => false,
    "mensaje" => "No se pudo crear la cuenta."
]);

exit;

?>
