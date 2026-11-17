<?php
// Todas las citas: quién la agendó, con qué psicóloga y en qué estado.
require_once "admin-comun.php";

$res = $conexion->query("
    SELECT c.id_cita, c.fecha, c.hora, c.estado, c.notas,
           u.nombre AS usuario, u.correo AS correo_usuario,
           p.nombre_completo AS psicologa
    FROM citas c
    JOIN usuarios u   ON u.id_usuario   = c.id_usuario
    JOIN psicologas p ON p.id_psicologa = c.id_psicologa
    ORDER BY c.fecha DESC, c.hora DESC
");

echo json_encode(["ok" => true, "citas" => $res->fetch_all(MYSQLI_ASSOC)]);
$conexion->close();
