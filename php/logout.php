<?php

session_start();

header("Content-Type: application/json; charset=utf-8");


// Vaciar sesión
$_SESSION = [];


// Eliminar cookie
if (ini_get("session.use_cookies")) {

    $params =
        session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


// Destruir sesión
session_destroy();


echo json_encode([
    "ok" => true,
    "mensaje" => "Sesión cerrada correctamente."
]);

exit;

?>
