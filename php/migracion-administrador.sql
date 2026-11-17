-- ═══════════════════════════════════════════════════════════
-- MIGRACIÓN: perfil de ADMINISTRADOR (superadmin) + compras
-- En phpMyAdmin: selecciona primero tu base de datos
-- (la misma de conexion.php) y luego importa este archivo.
-- ═══════════════════════════════════════════════════════════

-- 1) Nuevo rol: superadmin (decide quién es administradora o usuario)
ALTER TABLE usuarios
  MODIFY rol ENUM('usuario','administradora','superadmin')
  NOT NULL DEFAULT 'usuario';

-- 2) Psicólogas activas / inactivas (al quitarle el rol no se borra su historial)
ALTER TABLE psicologas
  ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 1;

-- 3) Pedidos de la tienda
CREATE TABLE IF NOT EXISTS pedidos (
  id_pedido        INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario       INT NOT NULL,
  nombre           VARCHAR(100) NOT NULL,
  apellido         VARCHAR(100) NOT NULL,
  email            VARCHAR(150) NOT NULL,
  telefono         VARCHAR(30)  NOT NULL,
  direccion        VARCHAR(200) NOT NULL,
  ciudad           VARCHAR(100) NOT NULL,
  departamento     VARCHAR(100) NOT NULL,
  codigo_postal    VARCHAR(20)  NOT NULL,
  pais             VARCHAR(100) NOT NULL,
  metodo_envio     ENUM('estandar','express','gratis') NOT NULL,
  metodo_pago      ENUM('tarjeta','nequi','qr','pse') NOT NULL,
  subtotal         DECIMAL(10,2) NOT NULL,
  costo_envio      DECIMAL(10,2) NOT NULL,
  total            DECIMAL(10,2) NOT NULL,
  estado           ENUM('pendiente','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  creado_en        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedido_items (
  id_item          INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido        INT NOT NULL,
  id_producto      INT NOT NULL,
  nombre_producto  VARCHAR(150) NOT NULL,
  precio           DECIMAL(10,2) NOT NULL,
  cantidad         INT NOT NULL,
  FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) TU cuenta como administrador (cambia el correo por el tuyo
--    y quita los dos guiones "--" de la línea de abajo)
-- UPDATE usuarios SET rol = 'superadmin' WHERE correo = 'TU_CORREO@ejemplo.com';

-- 5) Foto y calificación de cada psicóloga (para la tarjeta al elegirla)
ALTER TABLE psicologas
  ADD COLUMN foto VARCHAR(255) NULL,
  ADD COLUMN calificacion DECIMAL(2,1) NOT NULL DEFAULT 5.0;
