-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS agent;

-- Usar la base de datos
USE agent;

-- Crear la tabla de usuarios
CREATE TABLE IF NOT EXISTS agusers (
  idusers INT AUTO_INCREMENT PRIMARY KEY,
  nomUsuario VARCHAR(50) NOT NULL,
  emailUser VARCHAR(50) NOT NULL UNIQUE,
  passwordUser VARCHAR(255) NOT NULL,
  creado DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar un usuario de prueba (contraseña: admin123)
-- INSERT INTO agusers (nomUsuario, emailUser, passwordUser, creado) 
-- VALUES ('Administrador', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());