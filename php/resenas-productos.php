<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

function responderResena($ok, $mensaje, $codigo = 200, $datos = []) {
    http_response_code($codigo);
    echo json_encode(array_merge(["ok" => $ok, "mensaje" => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion->query(
        "CREATE TABLE IF NOT EXISTS resenas_productos (
            id_resena INT AUTO_INCREMENT PRIMARY KEY,
            id_producto INT NOT NULL,
            id_usuario INT NOT NULL,
            calificacion TINYINT UNSIGNED NOT NULL,
            comentario VARCHAR(1000) NOT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_resenas_producto_fecha (id_producto, creado_en),
            CONSTRAINT fk_resenas_usuario FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $idProducto = filter_input(INPUT_GET, "id_producto", FILTER_VALIDATE_INT);
        if (!$idProducto || $idProducto < 1 || $idProducto > 5) {
            responderResena(false, "Producto inválido.", 400);
        }
        $consulta = $conexion->prepare(
            "SELECT r.calificacion, r.comentario,
                    DATE_FORMAT(r.creado_en, '%Y-%m-%d %H:%i') AS creado_en,
                    u.nombre AS autor
             FROM resenas_productos r
             JOIN usuarios u ON u.id_usuario = r.id_usuario
             WHERE r.id_producto = ?
             ORDER BY r.creado_en DESC, r.id_resena DESC"
        );
        $consulta->bind_param("i", $idProducto);
        $consulta->execute();
        $resenas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();
        responderResena(true, "Reseñas cargadas.", 200, ["resenas" => $resenas]);
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        responderResena(false, "Método no permitido.", 405);
    }

    $idUsuario = (int)($_SESSION["usuario_id"] ?? $_SESSION["id_usuario"] ?? 0);
    if ($idUsuario <= 0) {
        responderResena(false, "Inicia sesión para dejar un comentario.", 401);
    }

    $datos = json_decode(file_get_contents("php://input"), true) ?: $_POST;
    $idProducto = filter_var($datos["id_producto"] ?? null, FILTER_VALIDATE_INT);
    $calificacion = filter_var($datos["calificacion"] ?? null, FILTER_VALIDATE_INT);
    $comentario = trim((string)($datos["comentario"] ?? ""));
    $largo = function_exists("mb_strlen")
        ? mb_strlen($comentario, "UTF-8")
        : strlen($comentario);

    if (
        !$idProducto || $idProducto < 1 || $idProducto > 5 ||
        !$calificacion || $calificacion < 1 || $calificacion > 5 ||
        $comentario === "" || $largo > 1000
    ) {
        responderResena(false, "Escribe un comentario de hasta 1000 caracteres y elige de 1 a 5 estrellas.", 400);
    }

    $insertar = $conexion->prepare(
        "INSERT INTO resenas_productos (id_producto, id_usuario, calificacion, comentario)
         VALUES (?, ?, ?, ?)"
    );
    $insertar->bind_param("iiis", $idProducto, $idUsuario, $calificacion, $comentario);
    $insertar->execute();
    $insertar->close();
    responderResena(true, "Comentario publicado.");
} catch (Throwable $e) {
    error_log("Error en resenas-productos.php: " . $e->getMessage());
    responderResena(false, "No se pudo procesar la reseña. Revisa la conexión con la base de datos.", 500);
}
?>
