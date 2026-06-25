-- ============================================================================
-- EstrateGIA — Migracion Unificacion Transversal (22_UNIFICACION_TRANSVERSAL.md)
-- Fecha: 2026-06-15
-- Version: 1.0
-- Proposito: Unificar tablas de usuarios/roles/permisos, logging, soporte y errores
-- ============================================================================

USE estrategia_v1;

-- ============================================================================
-- PARTE 1: USUARIOS, ROLES Y PERMISOS
-- ============================================================================

-- 1.1 login_attempts (bloqueo por intentos fallidos)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    intento_fallido TINYINT(1) DEFAULT 1,
    bloqueado_hasta DATETIME NULL,
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.2 roles (estandar unificado — SNAKE_CASE_UPPER)
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(200),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.3 roles_permisos (acceso basico a modulo)
CREATE TABLE IF NOT EXISTS roles_permisos (
    rol_nombre VARCHAR(50) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    PRIMARY KEY (rol_nombre, modulo),
    FOREIGN KEY (rol_nombre) REFERENCES roles(nombre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.4 roles_permisos_detalle (permisos granulares rol → modulo → accion)
CREATE TABLE IF NOT EXISTS roles_permisos_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_nombre VARCHAR(50) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    accion VARCHAR(30) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (rol_nombre) REFERENCES roles(nombre) ON DELETE CASCADE,
    UNIQUE KEY uk_rol_modulo_accion (rol_nombre, modulo, accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.5 usuarios (estandar unificado)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL DEFAULT 1,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_nombre VARCHAR(50) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    2fa_secret VARCHAR(64) NULL,
    usuario_2fa_activo TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_nombre) REFERENCES roles(nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.6 Agregar columnas faltantes a sys_usuarios (tabla legacy existente)
ALTER TABLE sys_usuarios
    ADD COLUMN IF NOT EXISTS intentos_fallidos INT DEFAULT 0 AFTER usuario_activo,
    ADD COLUMN IF NOT EXISTS bloqueado_hasta DATETIME NULL AFTER intentos_fallidos;
-- Nota: 2fa_secret y usuario_2fa_activo usan nombres legacy: usuario_2fa_secret, usuario_2fa_activo
-- que ya existen en la tabla (no hace falta ALTER adicional)

-- ============================================================================
-- PARTE 2: FULL LOGGING
-- ============================================================================

-- 2.1 error_log
CREATE TABLE IF NOT EXISTS error_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    mensaje TEXT NOT NULL,
    archivo VARCHAR(255),
    linea INT,
    url VARCHAR(500),
    user_agent VARCHAR(500),
    trace TEXT,
    resuelto TINYINT(1) DEFAULT 0,
    resuelto_por VARCHAR(100) NULL,
    resuelto_fecha DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_resuelto (resuelto),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.2 auditoria (con datos_anteriores/datos_nuevos JSON)
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL DEFAULT 1,
    id_usuario INT,
    usuario_nombre VARCHAR(100),
    accion VARCHAR(50) NOT NULL,
    tabla_afectada VARCHAR(100),
    registro_id INT,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    ip_origen VARCHAR(45),
    user_agent VARCHAR(500),
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_accion (accion),
    INDEX idx_tabla (tabla_afectada),
    INDEX idx_usuario (id_usuario),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.3 auditoria_uso (accesos y uso del sistema)
CREATE TABLE IF NOT EXISTS auditoria_uso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    usuario_nombre VARCHAR(100),
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50),
    entidad VARCHAR(100),
    ip_origen VARCHAR(45),
    user_agent VARCHAR(500),
    duracion_ms INT,
    detalles JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_created (id_usuario, created_at),
    INDEX idx_modulo (modulo),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.4 Agregar columnas estandar a sys_logs_sistema (si hacen falta)
ALTER TABLE sys_logs_sistema
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(30) NULL AFTER log_id,
    ADD COLUMN IF NOT EXISTS descripcion TEXT NULL AFTER tipo,
    ADD COLUMN IF NOT EXISTS estado VARCHAR(20) DEFAULT 'OK' AFTER descripcion,
    ADD COLUMN IF NOT EXISTS metadata JSON NULL AFTER estado;

-- ============================================================================
-- PARTE 3: SOPORTE
-- ============================================================================

-- 3.1 soporte_tickets
CREATE TABLE IF NOT EXISTS soporte_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL DEFAULT 1,
    modulo_afectado VARCHAR(50),
    asunto VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    prioridad ENUM('CRITICA','ALTA','MEDIA','BAJA') DEFAULT 'MEDIA',
    estado ENUM('ABIERTO','EN_PROGRESO','RESUELTO','CERRADO','ESCALADO_N2','ESCALADO_N3') DEFAULT 'ABIERTO',
    nivel_actual ENUM('N1','N2','N3') DEFAULT 'N1',
    origen ENUM('USUARIO','AUTO_DETECT','IA_DIAGNOSTICO','MONITOREO') DEFAULT 'USUARIO',
    asignado_a VARCHAR(100) NULL,
    sla_vencimiento DATETIME NULL,
    creado_por VARCHAR(100),
    resuelto_por VARCHAR(100) NULL,
    resolucion TEXT NULL,
    tiempo_resolucion_min INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_prioridad (prioridad),
    INDEX idx_modulo (modulo_afectado),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3.2 soporte_respuestas
CREATE TABLE IF NOT EXISTS soporte_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    tipo ENUM('RESPUESTA','DIAGNOSTICO_IA','ESCALACION','CIERRE','NOTA_INTERNA') DEFAULT 'RESPUESTA',
    contenido TEXT NOT NULL,
    autor VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES soporte_tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PARTE 4: BACKUP LOG
-- ============================================================================

CREATE TABLE IF NOT EXISTS backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('COMPLETO','DB_ONLY','FILES_ONLY','RESTORE') NOT NULL,
    archivo VARCHAR(255),
    tamano_bytes BIGINT,
    sha256 VARCHAR(64),
    estado ENUM('OK','WARN','ERROR') DEFAULT 'OK',
    mensaje TEXT NULL,
    ejecutado_por VARCHAR(100) DEFAULT 'CRON',
    duracion_seg INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SEEDS: Roles y Usuario Admin
-- ============================================================================

-- Insertar roles estandar (IGNORE para no duplicar)
INSERT IGNORE INTO roles (nombre, descripcion) VALUES
    ('SUPER_ADMIN', 'Acceso total al sistema, gestion de roles y permisos'),
    ('ADMIN', 'Administracion general sin acceso a configuracion de roles'),
    ('AUDITOR', 'Solo lectura de auditoria, reportes y logs'),
    ('CONSULTOR', 'Acceso de consulta a modulos operativos');

-- Insertar roles en tabla legacy sys_roles (IGNORE si ya existen)
INSERT IGNORE INTO sys_roles (rol_nombre, rol_descripcion) VALUES
    ('SUPER_ADMIN', 'Acceso total al sistema'),
    ('ADMIN', 'Administracion general'),
    ('AUDITOR', 'Solo lectura de auditoria y reportes'),
    ('CONSULTOR', 'Acceso de consulta a modulos operativos');

-- Insertar permisos base en roles_permisos (SUPER_ADMIN = acceso total)
INSERT IGNORE INTO roles_permisos (rol_nombre, modulo) VALUES
    ('SUPER_ADMIN', '*'),
    ('ADMIN', 'planeacion'),
    ('ADMIN', 'indicadores'),
    ('ADMIN', 'procesos'),
    ('ADMIN', 'documentos'),
    ('ADMIN', 'calidad'),
    ('ADMIN', 'sst'),
    ('ADMIN', 'ambiental'),
    ('ADMIN', 'usuarios'),
    ('ADMIN', 'auditoria'),
    ('AUDITOR', 'auditoria'),
    ('AUDITOR', 'reportes'),
    ('CONSULTOR', 'indicadores'),
    ('CONSULTOR', 'procesos'),
    ('CONSULTOR', 'documentos');

-- Insertar permisos detallados para ADMIN
INSERT IGNORE INTO roles_permisos_detalle (rol_nombre, modulo, accion) VALUES
    ('ADMIN', 'planeacion', 'ver'),
    ('ADMIN', 'planeacion', 'crear'),
    ('ADMIN', 'planeacion', 'editar'),
    ('ADMIN', 'planeacion', 'eliminar'),
    ('ADMIN', 'indicadores', 'ver'),
    ('ADMIN', 'indicadores', 'crear'),
    ('ADMIN', 'indicadores', 'editar'),
    ('ADMIN', 'procesos', 'ver'),
    ('ADMIN', 'procesos', 'crear'),
    ('ADMIN', 'procesos', 'editar'),
    ('ADMIN', 'documentos', 'ver'),
    ('ADMIN', 'documentos', 'crear'),
    ('ADMIN', 'documentos', 'editar'),
    ('ADMIN', 'calidad', 'ver'),
    ('ADMIN', 'sst', 'ver'),
    ('ADMIN', 'ambiental', 'ver'),
    ('AUDITOR', 'auditoria', 'ver'),
    ('AUDITOR', 'auditoria', 'exportar'),
    ('CONSULTOR', 'indicadores', 'ver'),
    ('CONSULTOR', 'procesos', 'ver'),
    ('CONSULTOR', 'documentos', 'ver');

-- Insertar usuario admin (password: admin123, bcrypt hash)
-- Si ya existe sys_usuarios con ese email, actualizar rol a SUPER_ADMIN
INSERT IGNORE INTO sys_usuarios
    (usuario_email, usuario_nombre, usuario_apellido, usuario_password_hash,
     usuario_rol_id, usuario_cargo, usuario_activo, usuario_2fa_activo)
SELECT
    'admin@estrategia.com', 'Administrador', 'EstrateGIA',
    '$2y$12$LJ3m4ys3GZfnYMz8kVsKaOTSxGlzDzNMQCwPDxqe31pgANaFM4UUK',
    (SELECT rol_id FROM sys_roles WHERE rol_nombre = 'SUPER_ADMIN' LIMIT 1),
    'Super Administrador', 1, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_usuarios WHERE usuario_email = 'admin@estrategia.com');

-- Si ya existe, asegurar que tenga el rol SUPER_ADMIN
UPDATE sys_usuarios u
JOIN sys_roles r ON r.rol_nombre = 'SUPER_ADMIN'
SET u.usuario_rol_id = r.rol_id
WHERE u.usuario_email = 'admin@estrategia.com';

-- Tambien insertar en la tabla usuarios estandar
INSERT IGNORE INTO usuarios
    (nombre, email, password_hash, rol_nombre, activo, usuario_2fa_activo)
VALUES
    ('Administrador EstrateGIA', 'admin@estrategia.com',
     '$2y$12$LJ3m4ys3GZfnYMz8kVsKaOTSxGlzDzNMQCwPDxqe31pgANaFM4UUK',
     'SUPER_ADMIN', 1, 0);
