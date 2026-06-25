-- =====================================================================
-- EstrateGIA — Migracion Comercial (Licencias + Facturacion)
-- Fecha: 2026-06-15
-- Cumple: Politica 23_ESTRATEGIA_COMERCIAL.md §5.3
-- =====================================================================
USE estrategia_v1;

CREATE TABLE IF NOT EXISTS licencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    app VARCHAR(50) NOT NULL DEFAULT 'EstrateGIA',
    plan ENUM('BASICO','ESTANDAR','AVANZADO','EMPRESARIAL') NOT NULL DEFAULT 'BASICO',
    usuarios_max INT NOT NULL DEFAULT 5,
    modulos_activos JSON NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activa TINYINT(1) DEFAULT 1,
    token_licencia VARCHAR(64) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (id_empresa),
    INDEX idx_activa (activa),
    INDEX idx_fecha_fin (fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS facturacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_licencia INT NOT NULL,
    id_empresa INT NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'USD',
    estado ENUM('PENDIENTE','PAGADA','VENCIDA','CANCELADA') DEFAULT 'PENDIENTE',
    metodo_pago VARCHAR(50),
    fecha_pago DATETIME NULL,
    comprobante_pago VARCHAR(255),
    referencia_externa VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (id_empresa),
    INDEX idx_licencia (id_licencia),
    INDEX idx_estado (estado),
    INDEX idx_periodo (periodo_inicio, periodo_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO licencias (id_empresa, app, plan, usuarios_max, modulos_activos, fecha_inicio, fecha_fin, activa, token_licencia)
VALUES (2, 'EstrateGIA', 'EMPRESARIAL', 999, '["planeacion","workbench","indicadores","evaluacion","procesos","calidad","sst","ambiental","nc","documentos","proveedores","crm","ia","soporte","financiero","admin","config"]', '2024-01-01', '2030-12-31', 1, 'DEMO-EGIA-E2');

SELECT 'Migracion Comercial — EstrateGIA aplicada correctamente' AS resultado;
