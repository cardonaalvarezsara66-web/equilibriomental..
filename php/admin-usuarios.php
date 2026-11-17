<?php
require_once "admin-comun.php";

$res = $conexion->query("
    SELECT u.id_usuario, u.nombre, u.correo, u.rol, u.creado_en,
           p.id_psicologa, p.activa
    FROM usuarios u
    LEFT JOIN psicologas p ON p.id_usuario = u.id_usuario
    ORDER BY FIELD(u.rol,'superadmin','administradora','usuario'), u.nombre
");

echo json_encode(["ok" => true, "usuarios" => $res->fetch_all(MYSQLI_ASSOC)]);
$conexion->close();
