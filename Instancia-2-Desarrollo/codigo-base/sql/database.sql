-- Base de datos para el sistema de solicitudes internas
CREATE DATABASE IF NOT EXISTS sistema_solicitudes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_solicitudes;

CREATE TABLE solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    area_solicitante VARCHAR(100) NOT NULL,
    area_destino VARCHAR(100) NOT NULL,
    prioridad ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
    estado ENUM('pendiente', 'en_proceso', 'resuelta', 'rechazada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO solicitudes (titulo, descripcion, area_solicitante, area_destino, prioridad, estado) VALUES
('Impresora sin toner', 'La impresora del piso 2 no imprime. Falta recarga de toner negro.', 'Administración', 'Sistemas', 'media', 'pendiente'),
('Acceso a carpeta compartida', 'Necesito acceso de lectura a //files/proyectos2024 para el informe trimestral.', 'Contabilidad', 'Sistemas', 'alta', 'en_proceso'),
('Silla ergonómica rota', 'Solicito reemplazo de silla en puesto 14.', 'Recursos Humanos', 'Mantenimiento', 'baja', 'resuelta'),
('Licencia software CAD', 'Renovación de licencia vencida ayer.', 'Obras', 'Compras', 'alta', 'rechazada');
