-- ============================================================
-- EstrateGIA v2.1 - Migracion Modulo Ambiental Completo
-- Tablas: AIA, Huella de Carbono ISO 14064, Controles, Planes
-- ============================================================

-- 1. EMISIONES GEI - Huella de Carbono ISO 14064
CREATE TABLE IF NOT EXISTS amb_emisiones_gei (
    gei_id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    gei_alcance ENUM('alcance_1','alcance_2','alcance_3') NOT NULL DEFAULT 'alcance_1',
    gei_tipo_fuente VARCHAR(50) NOT NULL COMMENT 'combustible, electricidad, refrigerante, viajes, residuos, compras',
    gei_fuente VARCHAR(200) NOT NULL COMMENT 'ej: Flota ambulancias, Planta electrica, A/A',
    gei_descripcion TEXT NULL,
    gei_cantidad DECIMAL(15,5) NOT NULL DEFAULT 0,
    gei_unidad VARCHAR(20) NOT NULL DEFAULT 'tCO2e',
    gei_factor_emision DECIMAL(10,5) NOT NULL DEFAULT 1.0,
    gei_periodo YEAR NOT NULL,
    gei_coordenadas_generacion VARCHAR(100) NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa_alcance (empresa_id, gei_alcance),
    INDEX idx_empresa_periodo (empresa_id, gei_periodo),
    CONSTRAINT fk_emisiones_empresa FOREIGN KEY (empresa_id) REFERENCES plan_empresas(empresa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CONTROLES AMBIENTALES
CREATE TABLE IF NOT EXISTS amb_controles (
    control_id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    asp_id INT NULL COMMENT 'FK a amb_aspectos - aspecto ambiental vinculado',
    control_criticidad ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
    control_descripcion TEXT NOT NULL,
    control_efectividad ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
    control_efectivo TINYINT(1) NOT NULL DEFAULT 0,
    control_estado ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
    control_fecha_implantacion DATE NULL,
    control_responsable_id INT NULL,
    INDEX idx_empresa (empresa_id),
    INDEX idx_aspecto (asp_id),
    CONSTRAINT fk_controles_empresa FOREIGN KEY (empresa_id) REFERENCES plan_empresas(empresa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PLANES DE TRABAJO AMBIENTAL
CREATE TABLE IF NOT EXISTS amb_planes_trabajo (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    plan_nombre VARCHAR(200) NOT NULL,
    plan_anio YEAR NOT NULL,
    plan_objetivo TEXT NULL,
    plan_fecha_inicio DATE NOT NULL,
    plan_fecha_fin DATE NULL,
    plan_responsable_id INT NULL,
    plan_presupuesto DECIMAL(15,2) NOT NULL DEFAULT 0,
    plan_porcentaje_avance DECIMAL(5,1) NOT NULL DEFAULT 0,
    plan_estado ENUM('planificado','en_progreso','completado','cancelado') NOT NULL DEFAULT 'planificado',
    INDEX idx_empresa_anio (empresa_id, plan_anio),
    CONSTRAINT fk_planes_empresa FOREIGN KEY (empresa_id) REFERENCES plan_empresas(empresa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ACTIVIDADES DE PLANES DE TRABAJO
CREATE TABLE IF NOT EXISTS amb_plan_actividades (
    actividad_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    actividad_nombre VARCHAR(200) NOT NULL,
    actividad_descripcion TEXT NULL,
    actividad_fecha_inicio DATE NOT NULL,
    actividad_fecha_fin DATE NULL,
    actividad_responsable_id INT NULL,
    actividad_porcentaje DECIMAL(5,1) NOT NULL DEFAULT 0,
    actividad_estado ENUM('pendiente','en_progreso','completada','cancelada') NOT NULL DEFAULT 'pendiente',
    INDEX idx_plan (plan_id),
    CONSTRAINT fk_actividades_plan FOREIGN KEY (plan_id) REFERENCES amb_planes_trabajo(plan_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. METAS AMBIENTALES
CREATE TABLE IF NOT EXISTS amb_metas_ambientales (
    meta_id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    meta_nombre VARCHAR(200) NOT NULL,
    meta_anio YEAR NOT NULL,
    meta_tipo ENUM('reduccion_gei','eficiencia_agua','eficiencia_energia','residuos','reciclaje','otro') NOT NULL DEFAULT 'reduccion_gei',
    meta_valor_objetivo DECIMAL(15,5) NOT NULL DEFAULT 0,
    meta_valor_actual DECIMAL(15,5) NOT NULL DEFAULT 0,
    meta_unidad VARCHAR(20) NOT NULL DEFAULT 'tCO2e',
    meta_responsable_id INT NULL,
    meta_estado ENUM('activa','cumplida','cancelada') NOT NULL DEFAULT 'activa',
    INDEX idx_empresa_anio (empresa_id, meta_anio),
    CONSTRAINT fk_metas_empresa FOREIGN KEY (empresa_id) REFERENCES plan_empresas(empresa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. CARBONO EVITADO (compensaciones)
CREATE TABLE IF NOT EXISTS amb_carbono_evitado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    periodo YEAR NOT NULL,
    fuente VARCHAR(200) NOT NULL,
    cantidad DECIMAL(15,5) NOT NULL DEFAULT 0,
    factor DECIMAL(10,5) NOT NULL DEFAULT 1.0,
    INDEX idx_empresa_periodo (empresa_id, periodo),
    CONSTRAINT fk_ce_empresa FOREIGN KEY (empresa_id) REFERENCES plan_empresas(empresa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ENERGIA RENOVABLE
CREATE TABLE IF NOT EXISTS amb_energia_renovable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    periodo YEAR NOT NULL,
    fuente VARCHAR(100) NOT NULL,
    generacion_mwh DECIMAL(15,5) NOT NULL DEFAULT 0,
    INDEX idx_empresa_periodo (empresa_id, periodo),
    CONSTRAINT fk_er_empresa FOREIGN KEY (empresa_id) REFERENCES plan_empresas(empresa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ALTER: Agregar columnas nuevas a amb_aspectos si no existen
ALTER TABLE amb_aspectos
    ADD COLUMN IF NOT EXISTS asp_recurso VARCHAR(30) NULL COMMENT 'agua, aire, suelo, flora, fauna, energia, residuos' AFTER asp_proceso_id,
    ADD COLUMN IF NOT EXISTS asp_area_id INT NULL AFTER asp_recurso,
    ADD COLUMN IF NOT EXISTS asp_plan_id INT NULL AFTER asp_area_id,
    ADD COLUMN IF NOT EXISTS asp_operacion_descripcion TEXT NULL AFTER asp_controles,
    ADD COLUMN IF NOT EXISTS asp_calculo_posible VARCHAR(200) NULL AFTER asp_operacion_descripcion,
    ADD COLUMN IF NOT EXISTS asp_proporcion_cientificamente_estimada VARCHAR(200) NULL AFTER asp_calculo_posible,
    ADD COLUMN IF NOT EXISTS asp_plan_accion_actual TEXT NULL AFTER asp_proporcion_cientificamente_estimada,
    ADD COLUMN IF NOT EXISTS asp_impacto_residual DECIMAL(5,1) NULL AFTER asp_plan_accion_actual,
    ADD COLUMN IF NOT EXISTS asp_controles_efectivos INT NOT NULL DEFAULT 0 AFTER asp_impacto_residual,
    ADD COLUMN IF NOT EXISTS asp_reduccion_por_controles DECIMAL(5,1) NOT NULL DEFAULT 0 AFTER asp_controles_efectivos;
