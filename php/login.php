<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function responder($ok, $mensaje, $usuario = null, $codigo = 200) {
    http_response_code($codigo);

    echo json_encode([
        'ok' => $ok,
        'mensaje' => $mensaje,
        'usuario' => $usuario
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método no permitido.', null, 405);
}

$identificador = trim($_POST['usuario'] ?? '');
$clave = $_POST['clave'] ?? '';

if ($identificador === '' || $clave === '') {
    responder(false, 'Escribe tu usuario o correo y tu contraseña.', null, 400);
}

try {
    // Usa el host, usuario, contraseña y base configurados en conexion.php
    require_once __DIR__ . '/conexion.php';

    $sql = "SELECT id_usuario, nombre, correo, contrasena, rol
            FROM usuarios
            WHERE nombre = ? OR correo = ?
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ss', $identificador, $identificador);
    $stmt->execute();

    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fila || !password_verify($clave, $fila['contrasena'])) {
        responder(false, 'Usuario o contraseña incorrectos.', null, 401);
    }

    $rol = strtolower(trim($fila['rol'] ?? ''));

    $rolesPermitidos = [
        'superadministradora',
        'superadmin',
        'administradora',
        'usuario'
    ];

    if (!in_array($rol, $rolesPermitidos, true)) {
        responder(false, 'El rol de esta cuenta no es válido.', null, 403);
    }

    session_regenerate_id(true);

    $_SESSION['id_usuario'] = (int) $fila['id_usuario'];
    $_SESSION['nombre'] = $fila['nombre'];
    $_SESSION['correo'] = $fila['correo'];
    $_SESSION['rol'] = $rol;

    $usuarioRespuesta = [
        'id' => (int) $fila['id_usuario'],
        'nombre' => $fila['nombre'],
        'correo' => $fila['correo'],
        'rol' => $rol
    ];

    responder(true, 'Inicio de sesión correcto.', $usuarioRespuesta);

} catch (Throwable $e) {
    error_log('Error en login.php: ' . $e->getMessage());

    // Mensaje temporal para encontrar el error; quítalo cuando el login funcione.
    responder(false, 'DEBUG: ' . $e->getMessage(), null, 500);
}