<?php

    // Conexión a la base de datos de InfinityFree
    $db_host = "sql105.infinityfree.com";
    $db_user = "if0_43359255";
    $db_pass = "xelxkxhZpjav";
    $db_name = "if0_43359255_equilibrio";

    try {
        $conexion = new mysqli(
            $db_host,
            $db_user,
            $db_pass,
            $db_name
        );

        $conexion->set_charset("utf8mb4");

    } catch (Throwable $e) {
        error_log("Error de conexión a MySQL: " . $e->getMessage());

        http_response_code(500);
        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "ok" => false,
            "mensaje" => "No se pudo conectar con la base de datos."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * Comprueba el rol actual del usuario consultando la base de datos.
     */
    function usuarioTieneRolActual($conexion, array $rolesPermitidos)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $idUsuario = filter_var(
            $_SESSION["id_usuario"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$idUsuario) {
            return false;
        }

        $consulta = $conexion->prepare(
            "SELECT rol FROM usuarios WHERE id_usuario = ? LIMIT 1"
        );

        $consulta->bind_param("i", $idUsuario);
        $consulta->execute();

        $resultado = $consulta->get_result();
        $usuario = $resultado->fetch_assoc();

        $consulta->close();

        if (!$usuario) {
            return false;
        }

        $rolActual = strtolower(trim($usuario["rol"] ?? ""));

        $rolesPermitidos = array_map(function ($rol) {
            return strtolower(trim((string) $rol));
        }, $rolesPermitidos);

        return in_array($rolActual, $rolesPermitidos, true);
    }