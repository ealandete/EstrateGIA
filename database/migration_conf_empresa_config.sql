-- ============================================================================
-- EstrateGIA - Migración: Tabla de configuración por empresa
-- Crea conf_empresa_config para parametrización global multi-empresa
-- ============================================================================

CREATE TABLE IF NOT EXISTS conf_empresa_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    config_clave VARCHAR(100) NOT NULL,
    config_valor TEXT,
    config_tipo ENUM('texto','numero','boolean','json','color','imagen') DEFAULT 'texto',
    config_modulo VARCHAR(50) DEFAULT 'general',
    config_descripcion VARCHAR(250),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_empresa_clave (empresa_id, config_clave),
    KEY idx_empresa_config (empresa_id, config_modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones por defecto para cada empresa existente
INSERT IGNORE INTO conf_empresa_config (empresa_id, config_clave, config_valor, config_tipo, config_modulo, config_descripcion)
SELECT e.empresa_id, v.config_clave, v.config_valor, v.config_tipo, v.config_modulo, v.config_descripcion
FROM plan_empresas e
CROSS JOIN (
    SELECT 'empresa_nombre_corto' AS config_clave, '' AS config_valor, 'texto' AS config_tipo, 'general' AS config_modulo, 'Nombre corto de la empresa' AS config_descripcion
    UNION ALL SELECT 'empresa_logo_url', '', 'imagen', 'general', 'URL o ruta del logo de la empresa'
    UNION ALL SELECT 'empresa_color_primario', '#1a73e8', 'color', 'general', 'Color primario de la marca (hex)'
    UNION ALL SELECT 'empresa_color_secundario', '#1557b0', 'color', 'general', 'Color secundario de la marca (hex)'
    UNION ALL SELECT 'empresa_modo_oscuro_default', '0', 'boolean', 'general', 'Activar modo oscuro por defecto (0/1)'
    UNION ALL SELECT 'empresa_idioma_default', 'es', 'texto', 'general', 'Idioma por defecto (es, en, pt)'
    UNION ALL SELECT 'empresa_timezone', 'America/Bogota', 'texto', 'general', 'Zona horaria de la empresa'
    UNION ALL SELECT 'empresa_formato_fecha', 'd/m/Y', 'texto', 'general', 'Formato de visualización de fechas (PHP date format)'
    UNION ALL SELECT 'empresa_moneda', 'COP', 'texto', 'general', 'Código ISO de la moneda principal'
    UNION ALL SELECT 'empresa_moneda_simbolo', '$', 'texto', 'general', 'Símbolo de la moneda'
    UNION ALL SELECT 'empresa_documento_codigo_prefijo', '', 'texto', 'documentos', 'Prefijo por defecto para códigos de documentos'
    UNION ALL SELECT 'empresa_documento_codigo_formato', '{PREFIJO}-{TIPO}-{CONSECUTIVO}', 'texto', 'documentos', 'Formato de código para documentos'
    UNION ALL SELECT 'empresa_proceso_codigo_formato', '{PREFIJO}-{TIPO}-{CONSECUTIVO}', 'texto', 'procesos', 'Formato de código para procesos'
    UNION ALL SELECT 'empresa_indicador_codigo_formato', 'IND-{CONSECUTIVO}', 'texto', 'indicadores', 'Formato de código para indicadores'
) AS v
WHERE e.empresa_activo = 1;
