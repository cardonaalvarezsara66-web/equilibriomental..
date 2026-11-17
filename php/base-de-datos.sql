-- ═══════════════════════════════════════════════════════════
-- BASE DE DATOS: equilibrio_mental (XAMPP / phpMyAdmin)
-- Corrige el nombre de BD (antes creaba "infiel") y las columnas
-- (antes usuario/clave, pero login.php y registro.php ya usan
-- id_usuario/nombre/correo/contrasena). Además agrega:
--   - rol de usuario (usuario / administradora)
--   - dispositivos_vinculados (para vinculacion.html)
--   - psicologas, horarios_disponibles, citas (agenda)
-- ═══════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS equilibrio_mental
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE equilibrio_mental;

-- ───────────────────────────────
-- USUARIOS (login / registro)
-- ───────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario  INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(50)  NOT NULL UNIQUE,
  correo      VARCHAR(150) NOT NULL UNIQUE,
  contrasena  VARCHAR(255) NOT NULL,
  rol         ENUM('usuario','administradora','superadmin') NOT NULL DEFAULT 'usuario',
  creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────
-- DISPOSITIVOS VINCULADOS (Bluetooth: anillo, reloj, arete)
-- Queda guardado por usuario, no solo en la sesión del navegador
-- ───────────────────────────────
CREATE TABLE IF NOT EXISTS dispositivos_vinculados (
  id_dispositivo      INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario          INT NOT NULL,
  nombre_dispositivo  VARCHAR(100) NOT NULL,
  tipo                ENUM('anillo','reloj','arete','otro') DEFAULT 'otro',
  bluetooth_id        VARCHAR(100),
  vinculado_en        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultima_conexion     TIMESTAMP NULL,
  activo              TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────
-- PSICÓLOGAS (las 4 administradoras)
-- Cada psicóloga es también un registro en "usuarios" con rol='administradora'
-- ───────────────────────────────
CREATE TABLE IF NOT EXISTS psicologas (
  id_psicologa     INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario       INT NOT NULL UNIQUE,
  nombre_completo  VARCHAR(100) NOT NULL,
  especialidad     VARCHAR(100),
  activa           TINYINT(1) NOT NULL DEFAULT 1,
  foto             VARCHAR(255),
  calificacion     DECIMAL(2,1) NOT NULL DEFAULT 5.0,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────
-- HORARIOS DISPONIBLES por psicóloga (para armar el calendario)
-- dia_semana: 1=lunes ... 7=domingo
-- ───────────────────────────────
CREATE TABLE IF NOT EXISTS horarios_disponibles (
  id_horario    INT AUTO_INCREMENT PRIMARY KEY,
  id_psicologa  INT NOT NULL,
  dia_semana    TINYINT NOT NULL,
  hora_inicio   TIME NOT NULL,
  hora_fin      TIME NOT NULL,
  FOREIGN KEY (id_psicologa) REFERENCES psicologas(id_psicologa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────
-- CITAS agendadas
-- ───────────────────────────────
CREATE TABLE IF NOT EXISTS citas (
  id_cita       INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario    INT NOT NULL,
  id_psicologa  INT NOT NULL,
  fecha         DATE NOT NULL,
  hora          TIME NOT NULL,
  estado        ENUM('pendiente','confirmada','cancelada','realizada') NOT NULL DEFAULT 'pendiente',
  notas         TEXT,
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_psicologa) REFERENCES psicologas(id_psicologa) ON DELETE CASCADE,
  UNIQUE KEY uniq_horario_ocupado (id_psicologa, fecha, hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────
-- PEDIDOS de la tienda
-- ───────────────────────────────
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

-- ═══════════════════════════════════════════════════════════
-- EJEMPLO: cómo crear las 4 psicólogas como administradoras
-- (reemplaza los datos reales; la contraseña debe ir cifrada
-- con password_hash() desde PHP, no en texto plano)
-- ═══════════════════════════════════════════════════════════
-- INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES
--   ('psic_ana',   'ana@equilibriomental.com',   '<hash>', 'administradora'),
--   ('psic_laura', 'laura@equilibriomental.com', '<hash>', 'administradora'),
--   ('psic_maria', 'maria@equilibriomental.com', '<hash>', 'administradora'),
--   ('psic_paula', 'paula@equilibriomental.com', '<hash>', 'administradora');
--
-- INSERT INTO psicologas (id_usuario, nombre_completo, especialidad) VALUES
--   (LAST_INSERT_ID(), 'Ana Pérez', 'Terapia de pareja');
