
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

if (
    isset($_SESSION["id_usuario"]) &&
    isset($_SESSION["nombre"])
) {

    echo json_encode([
        "ok" => true,
        "logueado" => true,

        "usuario" => [
            "id" => $_SESSION["id_usuario"],
            "nombre" => $_SESSION["nombre"],
            "correo" => $_SESSION["correo"] ?? "",
            "rol" => $_SESSION["rol"] ?? "usuario",
            "id_psicologa" => $_SESSION["id_psicologa"] ?? null
        ]

    ], JSON_UNESCAPED_UNICODE);

    exit;
}

echo json_encode([
    "ok" => false,
    "logueado" => false,
    "usuario" => null
], JSON_UNESCAPED_UNICODE);

exit;
?>
