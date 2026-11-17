-- Ejecuta esto una sola vez en phpMyAdmin para una base ya creada.
-- Cambia el correo por el de tu cuenta existente antes de ejecutar.
ALTER TABLE usuarios
  MODIFY rol ENUM('usuario','administradora','superadministradora')
  NOT NULL DEFAULT 'usuario';

UPDATE usuarios
SET rol = 'superadministradora'
WHERE correo = 'cardonaalvarezsara@gmail.com';

-- Confirma que solo la cuenta deseada recibió el permiso.
SELECT id_usuario, nombre, correo, rol
FROM usuarios
WHERE correo = 'cardonaalvarezsara@gmail.com';
