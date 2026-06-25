-- ============================================================================
-- EstrateGIA v1.0 - Sistema de Gestión de Planeación Estratégica con IA
-- Schema Completo de Base de Datos
-- Motor: MySQL 5.7+ / MariaDB 10.2+
-- Charset: utf8mb4
-- ============================================================================

CREATE DATABASE IF NOT EXISTS estrategia_v1
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE estrategia_v1;

-- ============================================================================
-- MÓDULO 1: SISTEMA BASE (sys_)
-- ============================================================================

CREATE TABLE sys_configuraciones (
  config_id INT AUTO_INCREMENT PRIMARY KEY,
  config_clave VARCHAR(100) NOT NULL UNIQUE,
  config_valor TEXT,
  config_tipo ENUM('texto','numero','booleano','json','archivo') DEFAULT 'texto',
  config_modulo VARCHAR(50),
  config_descripcion VARCHAR(255),
  config_editable TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sys_roles (
  rol_id INT AUTO_INCREMENT PRIMARY KEY,
  rol_nombre VARCHAR(100) NOT NULL UNIQUE,
  rol_descripcion TEXT,
  rol_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sys_usuarios (
  usuario_id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_email VARCHAR(150) NOT NULL UNIQUE,
  usuario_nombre VARCHAR(100) NOT NULL,
  usuario_apellido VARCHAR(100),
  usuario_password_hash VARCHAR(255) NOT NULL,
  usuario_rol_id INT NOT NULL,
  usuario_cargo VARCHAR(150),
  usuario_departamento VARCHAR(100),
  usuario_activo TINYINT(1) DEFAULT 1,
  usuario_ultimo_acceso TIMESTAMP NULL,
  usuario_foto_url VARCHAR(500),
  usuario_token_reset VARCHAR(255),
  usuario_token_expira TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_rol_id) REFERENCES sys_roles(rol_id)
) ENGINE=InnoDB;

CREATE TABLE sys_modulos (
  modulo_id INT AUTO_INCREMENT PRIMARY KEY,
  modulo_nombre VARCHAR(100) NOT NULL UNIQUE,
  modulo_descripcion TEXT,
  modulo_icono VARCHAR(50),
  modulo_ruta VARCHAR(100),
  modulo_orden INT DEFAULT 0,
  modulo_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sys_permisos (
  permiso_id INT AUTO_INCREMENT PRIMARY KEY,
  permiso_modulo_id INT NOT NULL,
  permiso_accion VARCHAR(50) NOT NULL COMMENT 'ver,crear,editar,eliminar,exportar,importar,aprobar',
  permiso_descripcion VARCHAR(255),
  FOREIGN KEY (permiso_modulo_id) REFERENCES sys_modulos(modulo_id),
  UNIQUE KEY uk_modulo_accion (permiso_modulo_id, permiso_accion)
) ENGINE=InnoDB;

CREATE TABLE sys_rol_permisos (
  rp_rol_id INT NOT NULL,
  rp_permiso_id INT NOT NULL,
  PRIMARY KEY (rp_rol_id, rp_permiso_id),
  FOREIGN KEY (rp_rol_id) REFERENCES sys_roles(rol_id),
  FOREIGN KEY (rp_permiso_id) REFERENCES sys_permisos(permiso_id)
) ENGINE=InnoDB;

CREATE TABLE sys_logs_sistema (
  log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  log_usuario_id INT,
  log_accion VARCHAR(100) NOT NULL,
  log_modulo VARCHAR(50),
  log_entidad VARCHAR(50),
  log_entidad_id INT,
  log_detalle JSON,
  log_ip VARCHAR(45),
  log_user_agent VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_usuario (log_usuario_id),
  INDEX idx_logs_fecha (created_at),
  INDEX idx_logs_modulo (log_modulo)
) ENGINE=InnoDB;

CREATE TABLE sys_notificaciones (
  notif_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  notif_usuario_id INT NOT NULL,
  notif_titulo VARCHAR(200) NOT NULL,
  notif_mensaje TEXT,
  notif_tipo ENUM('info','exito','advertencia','error','alerta') DEFAULT 'info',
  notif_leida TINYINT(1) DEFAULT 0,
  notif_url VARCHAR(500),
  notif_entidad VARCHAR(50),
  notif_entidad_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (notif_usuario_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_notif_usuario (notif_usuario_id, notif_leida),
  INDEX idx_notif_fecha (created_at)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 2: PLANEACIÓN ESTRATÉGICA (plan_)
-- ============================================================================

CREATE TABLE plan_metodologias (
  metodologia_id INT AUTO_INCREMENT PRIMARY KEY,
  metodologia_nombre VARCHAR(150) NOT NULL UNIQUE,
  metodologia_descripcion TEXT,
  metodologia_fases_json JSON COMMENT 'Estructura de fases estándar de la metodología',
  metodologia_icono VARCHAR(50),
  metodologia_activo TINYINT(1) DEFAULT 1,
  metodologia_referencia_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE plan_empresas (
  empresa_id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_nombre VARCHAR(200) NOT NULL,
  empresa_razon_social VARCHAR(200),
  empresa_nit VARCHAR(50),
  empresa_sector_id INT,
  empresa_direccion VARCHAR(300),
  empresa_telefono VARCHAR(50),
  empresa_email VARCHAR(150),
  empresa_logo_url VARCHAR(500),
  empresa_mision TEXT,
  empresa_vision TEXT,
  empresa_valores JSON,
  empresa_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE plan_planes_estrategicos (
  plan_id INT AUTO_INCREMENT PRIMARY KEY,
  plan_empresa_id INT NOT NULL,
  plan_metodologia_id INT NOT NULL,
  plan_nombre VARCHAR(250) NOT NULL,
  plan_descripcion TEXT,
  plan_fecha_inicio DATE,
  plan_fecha_fin DATE,
  plan_periodo VARCHAR(50) COMMENT '2024-2027, 2025, Q1-2025, etc.',
  plan_estado ENUM('borrador','en_proceso','revision','aprobado','ejecucion','completado','cancelado') DEFAULT 'borrador',
  plan_avance_porcentaje DECIMAL(5,2) DEFAULT 0.00,
  plan_presupuesto_total DECIMAL(18,2),
  plan_responsable_id INT,
  plan_version INT DEFAULT 1,
  plan_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (plan_empresa_id) REFERENCES plan_empresas(empresa_id),
  FOREIGN KEY (plan_metodologia_id) REFERENCES plan_metodologias(metodologia_id),
  FOREIGN KEY (plan_responsable_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_plan_empresa (plan_empresa_id),
  INDEX idx_plan_estado (plan_estado),
  INDEX idx_plan_fechas (plan_fecha_inicio, plan_fecha_fin)
) ENGINE=InnoDB;

CREATE TABLE plan_fases (
  fase_id INT AUTO_INCREMENT PRIMARY KEY,
  fase_plan_id INT NOT NULL,
  fase_nombre VARCHAR(200) NOT NULL,
  fase_descripcion TEXT,
  fase_orden INT NOT NULL,
  fase_duracion_dias INT,
  fase_fecha_inicio DATE,
  fase_fecha_fin DATE,
  fase_estado ENUM('pendiente','en_proceso','completada','bloqueada') DEFAULT 'pendiente',
  fase_avance_porcentaje DECIMAL(5,2) DEFAULT 0.00,
  fase_responsable_id INT,
  fase_entregables JSON,
  fase_guia_paso_a_paso JSON COMMENT 'Pasos detallados guiados por IA para completar la fase',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (fase_plan_id) REFERENCES plan_planes_estrategicos(plan_id),
  FOREIGN KEY (fase_responsable_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_fase_plan (fase_plan_id),
  INDEX idx_fase_orden (fase_plan_id, fase_orden)
) ENGINE=InnoDB;

CREATE TABLE plan_analisis_contexto (
  analisis_id INT AUTO_INCREMENT PRIMARY KEY,
  analisis_plan_id INT NOT NULL,
  analisis_tipo ENUM('FODA','PESTEL','5_FUERZAS_PORTER','BENCHMARKING','STAKEHOLDERS','VUCA','OTRO') NOT NULL,
  analisis_titulo VARCHAR(250),
  analisis_contenido JSON COMMENT 'Matriz del análisis en formato estructurado',
  analisis_conclusiones TEXT,
  analisis_fecha DATE,
  analisis_responsable_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (analisis_plan_id) REFERENCES plan_planes_estrategicos(plan_id),
  FOREIGN KEY (analisis_responsable_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_analisis_plan (analisis_plan_id)
) ENGINE=InnoDB;

CREATE TABLE plan_objetivos (
  objetivo_id INT AUTO_INCREMENT PRIMARY KEY,
  objetivo_plan_id INT NOT NULL,
  objetivo_fase_id INT,
  objetivo_codigo VARCHAR(20),
  objetivo_nombre VARCHAR(300) NOT NULL,
  objetivo_descripcion TEXT,
  objetivo_tipo ENUM('estrategico','tactico','operativo') DEFAULT 'estrategico',
  objetivo_perspectiva ENUM('financiera','cliente','procesos','aprendizaje','sostenibilidad','otra') DEFAULT 'financiera',
  objetivo_peso_relativo DECIMAL(5,2) COMMENT 'Peso porcentual en el plan',
  objetivo_prioridad ENUM('critico','alto','medio','bajo') DEFAULT 'medio',
  objetivo_estado ENUM('pendiente','en_proceso','cumplido','desviado','cancelado') DEFAULT 'pendiente',
  objetivo_avance_porcentaje DECIMAL(5,2) DEFAULT 0.00,
  objetivo_responsable_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (objetivo_plan_id) REFERENCES plan_planes_estrategicos(plan_id),
  FOREIGN KEY (objetivo_fase_id) REFERENCES plan_fases(fase_id),
  FOREIGN KEY (objetivo_responsable_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_objetivo_plan (objetivo_plan_id)
) ENGINE=InnoDB;

CREATE TABLE plan_estrategias (
  estrategia_id INT AUTO_INCREMENT PRIMARY KEY,
  estrategia_objetivo_id INT NOT NULL,
  estrategia_codigo VARCHAR(20),
  estrategia_nombre VARCHAR(300) NOT NULL,
  estrategia_descripcion TEXT,
  estrategia_tipo ENUM('ofensiva','defensiva','adaptativa','supervivencia','innovacion','crecimiento') DEFAULT 'crecimiento',
  estrategia_prioridad ENUM('critico','alto','medio','bajo') DEFAULT 'medio',
  estrategia_estado ENUM('pendiente','en_proceso','implementada','evaluada','cancelada') DEFAULT 'pendiente',
  estrategia_avance_porcentaje DECIMAL(5,2) DEFAULT 0.00,
  estrategia_presupuesto DECIMAL(18,2),
  estrategia_responsable_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (estrategia_objetivo_id) REFERENCES plan_objetivos(objetivo_id),
  FOREIGN KEY (estrategia_responsable_id) REFERENCES sys_usuarios(usuario_id)
) ENGINE=InnoDB;

CREATE TABLE plan_actividades (
  actividad_id INT AUTO_INCREMENT PRIMARY KEY,
  actividad_estrategia_id INT,
  actividad_objetivo_id INT,
  actividad_proceso_id INT,
  actividad_codigo VARCHAR(20),
  actividad_nombre VARCHAR(300) NOT NULL,
  actividad_descripcion TEXT,
  actividad_tipo ENUM('accion','proyecto','tarea','hito','revision') DEFAULT 'tarea',
  actividad_fecha_inicio DATE,
  actividad_fecha_fin_planeada DATE,
  actividad_fecha_fin_real DATE,
  actividad_duracion_estimada_horas DECIMAL(8,2),
  actividad_duracion_real_horas DECIMAL(8,2),
  actividad_estado ENUM('pendiente','en_proceso','completada','retrasada','bloqueada','cancelada') DEFAULT 'pendiente',
  actividad_avance_porcentaje DECIMAL(5,2) DEFAULT 0.00,
  actividad_prioridad ENUM('critico','alto','medio','bajo') DEFAULT 'medio',
  actividad_dependencia_id INT COMMENT 'Actividad predecesora',
  actividad_responsable_id INT,
  actividad_equipo_ids JSON COMMENT 'IDs de usuarios del equipo',
  actividad_recursos JSON,
  actividad_entregables JSON,
  actividad_notas TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (actividad_estrategia_id) REFERENCES plan_estrategias(estrategia_id),
  FOREIGN KEY (actividad_objetivo_id) REFERENCES plan_objetivos(objetivo_id),
  FOREIGN KEY (actividad_responsable_id) REFERENCES sys_usuarios(usuario_id),
  FOREIGN KEY (actividad_dependencia_id) REFERENCES plan_actividades(actividad_id),
  INDEX idx_act_estrategia (actividad_estrategia_id),
  INDEX idx_act_objetivo (actividad_objetivo_id),
  INDEX idx_act_responsable (actividad_responsable_id),
  INDEX idx_act_estado (actividad_estado),
  INDEX idx_act_fechas (actividad_fecha_inicio, actividad_fecha_fin_planeada)
) ENGINE=InnoDB;

CREATE TABLE plan_mapa_actividades (
  mapa_id INT AUTO_INCREMENT PRIMARY KEY,
  mapa_actividad_id INT NOT NULL,
  mapa_usuario_id INT NOT NULL,
  mapa_rol ENUM('responsable','ejecutor','revisor','aprobador','informado') DEFAULT 'ejecutor',
  mapa_tiempo_estimado_minutos INT,
  mapa_tiempo_real_minutos INT,
  mapa_tiempo_promedio_historico_minutos DECIMAL(10,2),
  mapa_capacidad_porcentaje DECIMAL(5,2) COMMENT '% de capacidad del colaborador asignado',
  mapa_estado ENUM('asignado','aceptado','en_progreso','completado','rechazado') DEFAULT 'asignado',
  mapa_fecha_asignacion DATE,
  mapa_fecha_completado DATE,
  mapa_observaciones TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (mapa_actividad_id) REFERENCES plan_actividades(actividad_id),
  FOREIGN KEY (mapa_usuario_id) REFERENCES sys_usuarios(usuario_id),
  UNIQUE KEY uk_mapa_actividad_usuario (mapa_actividad_id, mapa_usuario_id, mapa_rol),
  INDEX idx_mapa_usuario (mapa_usuario_id),
  INDEX idx_mapa_estado (mapa_estado)
) ENGINE=InnoDB;

CREATE TABLE plan_presupuestos (
  presupuesto_id INT AUTO_INCREMENT PRIMARY KEY,
  presupuesto_plan_id INT NOT NULL,
  presupuesto_categoria VARCHAR(150),
  presupuesto_monto_planeado DECIMAL(18,2),
  presupuesto_monto_ejecutado DECIMAL(18,2) DEFAULT 0.00,
  presupuesto_monto_comprometido DECIMAL(18,2) DEFAULT 0.00,
  presupuesto_porcentaje_ejecucion DECIMAL(5,2) DEFAULT 0.00,
  presupuesto_periodo VARCHAR(50),
  presupuesto_responsable_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (presupuesto_plan_id) REFERENCES plan_planes_estrategicos(plan_id),
  FOREIGN KEY (presupuesto_responsable_id) REFERENCES sys_usuarios(usuario_id)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 3: GESTIÓN DE PROCESOS (proc_)
-- ============================================================================

CREATE TABLE proc_macroprocesos (
  macro_id INT AUTO_INCREMENT PRIMARY KEY,
  macro_empresa_id INT NOT NULL,
  macro_codigo VARCHAR(20),
  macro_nombre VARCHAR(250) NOT NULL,
  macro_descripcion TEXT,
  macro_tipo ENUM('estrategico','misional','apoyo','evaluacion') DEFAULT 'misional',
  macro_orden INT DEFAULT 0,
  macro_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (macro_empresa_id) REFERENCES plan_empresas(empresa_id),
  INDEX idx_macro_tipo (macro_tipo)
) ENGINE=InnoDB;

CREATE TABLE proc_procesos (
  proceso_id INT AUTO_INCREMENT PRIMARY KEY,
  proceso_macro_id INT NOT NULL,
  proceso_plan_id INT,
  proceso_codigo VARCHAR(20),
  proceso_nombre VARCHAR(250) NOT NULL,
  proceso_descripcion TEXT,
  proceso_objetivo TEXT,
  proceso_alcance TEXT,
  proceso_tipo ENUM('estrategico','misional','apoyo','evaluacion') DEFAULT 'misional',
  proceso_estado ENUM('documentado','implementado','auditado','optimizado','obsoleto') DEFAULT 'documentado',
  proceso_responsable_id INT,
  proceso_version INT DEFAULT 1,
  proceso_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (proceso_macro_id) REFERENCES proc_macroprocesos(macro_id),
  FOREIGN KEY (proceso_plan_id) REFERENCES plan_planes_estrategicos(plan_id),
  FOREIGN KEY (proceso_responsable_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_proc_macro (proceso_macro_id),
  INDEX idx_proc_tipo (proceso_tipo)
) ENGINE=InnoDB;

CREATE TABLE proc_procedimientos (
  procedimiento_id INT AUTO_INCREMENT PRIMARY KEY,
  procedimiento_proceso_id INT NOT NULL,
  procedimiento_codigo VARCHAR(20),
  procedimiento_nombre VARCHAR(250) NOT NULL,
  procedimiento_descripcion TEXT,
  procedimiento_objetivo TEXT,
  procedimiento_orden INT DEFAULT 0,
  procedimiento_documento_id INT,
  procedimiento_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (procedimiento_proceso_id) REFERENCES proc_procesos(proceso_id),
  INDEX idx_proc_proc (procedimiento_proceso_id)
) ENGINE=InnoDB;

CREATE TABLE proc_tareas (
  tarea_id INT AUTO_INCREMENT PRIMARY KEY,
  tarea_procedimiento_id INT,
  tarea_proceso_id INT,
  tarea_codigo VARCHAR(20),
  tarea_nombre VARCHAR(300) NOT NULL,
  tarea_descripcion TEXT,
  tarea_orden INT DEFAULT 0,
  tarea_tipo ENUM('manual','automatica','semi_automatica','decision','espera','notificacion') DEFAULT 'manual',
  tarea_tiempo_estimado_minutos INT,
  tarea_tiempo_real_promedio_minutos DECIMAL(10,2),
  tarea_tiempo_maximo_permitido_minutos INT,
  tarea_frecuencia ENUM('unica','diaria','semanal','quincenal','mensual','trimestral','semestral','anual','continua') DEFAULT 'unica',
  tarea_responsable_id INT,
  tarea_requiere_evidencia TINYINT(1) DEFAULT 0,
  tarea_critica TINYINT(1) DEFAULT 0,
  tarea_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tarea_procedimiento_id) REFERENCES proc_procedimientos(procedimiento_id),
  FOREIGN KEY (tarea_proceso_id) REFERENCES proc_procesos(proceso_id),
  FOREIGN KEY (tarea_responsable_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_tarea_proc (tarea_procedimiento_id),
  INDEX idx_tarea_tipo (tarea_tipo)
) ENGINE=InnoDB;

CREATE TABLE proc_mapeo_tiempos (
  mapeo_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  mapeo_tarea_id INT NOT NULL,
  mapeo_usuario_id INT NOT NULL,
  mapeo_fecha_inicio DATETIME NOT NULL,
  mapeo_fecha_fin DATETIME,
  mapeo_tiempo_total_minutos INT,
  mapeo_estado ENUM('iniciado','pausado','completado','cancelado') DEFAULT 'iniciado',
  mapeo_tipo_registro ENUM('manual','automatico','crm','web_service','mineria_datos') DEFAULT 'manual',
  mapeo_origen_dato VARCHAR(250) COMMENT 'Fuente del dato automático (CRM, WS, etc.)',
  mapeo_observaciones TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mapeo_tarea_id) REFERENCES proc_tareas(tarea_id),
  FOREIGN KEY (mapeo_usuario_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_mapeo_tarea (mapeo_tarea_id),
  INDEX idx_mapeo_usuario (mapeo_usuario_id),
  INDEX idx_mapeo_fechas (mapeo_fecha_inicio, mapeo_fecha_fin),
  INDEX idx_mapeo_origen (mapeo_tipo_registro)
) ENGINE=InnoDB;

CREATE TABLE proc_workflows (
  workflow_id INT AUTO_INCREMENT PRIMARY KEY,
  workflow_proceso_id INT NOT NULL,
  workflow_nombre VARCHAR(250) NOT NULL,
  workflow_descripcion TEXT,
  workflow_diagrama_json JSON COMMENT 'Diagrama de flujo en formato JSON estructurado',
  workflow_estado ENUM('diseno','pruebas','activo','obsoleto') DEFAULT 'diseno',
  workflow_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (workflow_proceso_id) REFERENCES proc_procesos(proceso_id)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 4: INDICADORES Y KPIs (ind_)
-- ============================================================================

CREATE TABLE ind_categorias (
  categoria_id INT AUTO_INCREMENT PRIMARY KEY,
  categoria_nombre VARCHAR(100) NOT NULL UNIQUE,
  categoria_tipo ENUM('cumplimiento','oportunidad','calidad','productividad') NOT NULL,
  categoria_descripcion TEXT,
  categoria_color VARCHAR(7) DEFAULT '#007bff',
  categoria_icono VARCHAR(50),
  categoria_formula_base VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ind_indicadores (
  indicador_id INT AUTO_INCREMENT PRIMARY KEY,
  indicador_categoria_id INT NOT NULL,
  indicador_plan_id INT,
  indicador_proceso_id INT,
  indicador_objetivo_id INT,
  indicador_codigo VARCHAR(20),
  indicador_nombre VARCHAR(300) NOT NULL,
  indicador_descripcion TEXT,
  indicador_formula TEXT COMMENT 'Fórmula de cálculo del indicador',
  indicador_unidad_medida VARCHAR(50),
  indicador_frecuencia_medicion ENUM('diaria','semanal','quincenal','mensual','bimestral','trimestral','semestral','anual') DEFAULT 'mensual',
  indicador_fuente_datos VARCHAR(250),
  indicador_responsable_id INT,
  indicador_tendencia_esperada ENUM('ascendente','descendente','estable','rango') DEFAULT 'estable',
  indicador_rango_minimo DECIMAL(12,4),
  indicador_rango_maximo DECIMAL(12,4),
  indicador_semaforo_json JSON COMMENT 'Configuración de semáforo: verde, amarillo, rojo',
  indicador_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (indicador_categoria_id) REFERENCES ind_categorias(categoria_id),
  FOREIGN KEY (indicador_plan_id) REFERENCES plan_planes_estrategicos(plan_id),
  FOREIGN KEY (indicador_proceso_id) REFERENCES proc_procesos(proceso_id),
  FOREIGN KEY (indicador_objetivo_id) REFERENCES plan_objetivos(objetivo_id),
  FOREIGN KEY (indicador_responsable_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_ind_categoria (indicador_categoria_id),
  INDEX idx_ind_plan (indicador_plan_id)
) ENGINE=InnoDB;

CREATE TABLE ind_metas (
  meta_id INT AUTO_INCREMENT PRIMARY KEY,
  meta_indicador_id INT NOT NULL,
  meta_periodo VARCHAR(50) NOT NULL COMMENT '2025, Q1-2025, Ene-2025, etc.',
  meta_valor DECIMAL(18,4) NOT NULL,
  meta_valor_minimo DECIMAL(18,4),
  meta_valor_maximo DECIMAL(18,4),
  meta_fecha_inicio DATE,
  meta_fecha_fin DATE,
  meta_estado ENUM('pendiente','en_progreso','cumplida','no_cumplida','superada') DEFAULT 'pendiente',
  meta_peso_porcentaje DECIMAL(5,2) COMMENT 'Peso relativo para metas compuestas',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (meta_indicador_id) REFERENCES ind_indicadores(indicador_id),
  INDEX idx_meta_indicador (meta_indicador_id),
  INDEX idx_meta_periodo (meta_periodo)
) ENGINE=InnoDB;

CREATE TABLE ind_mediciones (
  medicion_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  medicion_indicador_id INT NOT NULL,
  medicion_meta_id INT,
  medicion_valor DECIMAL(18,4) NOT NULL,
  medicion_valor_numerador DECIMAL(18,4),
  medicion_valor_denominador DECIMAL(18,4),
  medicion_fecha DATE NOT NULL,
  medicion_periodo VARCHAR(50),
  medicion_origen ENUM('manual','crm','web_service','mineria_datos','sistema','calculado') DEFAULT 'manual',
  medicion_origen_detalle VARCHAR(500),
  medicion_cumplimiento_porcentaje DECIMAL(5,2) COMMENT '% vs meta',
  medicion_desviacion DECIMAL(12,4) COMMENT 'Desviación vs meta',
  medicion_semaforo ENUM('verde','amarillo','rojo') COMMENT 'Estado semáforo calculado',
  medicion_registrado_por INT,
  medicion_observaciones TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (medicion_indicador_id) REFERENCES ind_indicadores(indicador_id),
  FOREIGN KEY (medicion_meta_id) REFERENCES ind_metas(meta_id),
  FOREIGN KEY (medicion_registrado_por) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_med_indicador (medicion_indicador_id),
  INDEX idx_med_fecha (medicion_fecha),
  INDEX idx_med_origen (medicion_origen),
  INDEX idx_med_semaforo (medicion_semaforo)
) ENGINE=InnoDB;

CREATE TABLE ind_evaluaciones_desempeno (
  evaluacion_id INT AUTO_INCREMENT PRIMARY KEY,
  evaluacion_usuario_id INT NOT NULL,
  evaluacion_periodo VARCHAR(50) NOT NULL,
  evaluacion_fecha DATE,
  evaluacion_puntaje_cumplimiento DECIMAL(5,2),
  evaluacion_puntaje_oportunidad DECIMAL(5,2),
  evaluacion_puntaje_calidad DECIMAL(5,2),
  evaluacion_puntaje_productividad DECIMAL(5,2),
  evaluacion_puntaje_total DECIMAL(5,2),
  evaluacion_observaciones TEXT,
  evaluacion_calibracion_json JSON COMMENT 'Ajustes y calibraciones del evaluador',
  evaluacion_evaluador_id INT,
  evaluacion_estado ENUM('autoevaluacion','evaluacion','calibracion','aprobada','reclamada') DEFAULT 'autoevaluacion',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (evaluacion_usuario_id) REFERENCES sys_usuarios(usuario_id),
  FOREIGN KEY (evaluacion_evaluador_id) REFERENCES sys_usuarios(usuario_id),
  UNIQUE KEY uk_eval_usuario_periodo (evaluacion_usuario_id, evaluacion_periodo)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 5: INTEGRACIÓN CRM Y WEB SERVICES (crm_)
-- ============================================================================

CREATE TABLE crm_conexiones (
  conexion_id INT AUTO_INCREMENT PRIMARY KEY,
  conexion_empresa_id INT NOT NULL,
  conexion_nombre VARCHAR(200) NOT NULL,
  conexion_tipo ENUM('crm','erp','web_service','api_rest','base_datos','archivo','iot') NOT NULL,
  conexion_proveedor VARCHAR(100) COMMENT 'Salesforce, HubSpot, SAP, Oracle, etc.',
  conexion_url VARCHAR(500),
  conexion_metodo_autenticacion ENUM('api_key','oauth2','basic','token','certificado','ninguno') DEFAULT 'api_key',
  conexion_credenciales_encriptadas TEXT COMMENT 'Credenciales encriptadas (AES-256)',
  conexion_activo TINYINT(1) DEFAULT 1,
  conexion_ultima_sincronizacion TIMESTAMP NULL,
  conexion_estado_salud ENUM('ok','error','desconectado','pendiente') DEFAULT 'pendiente',
  conexion_configuracion_json JSON COMMENT 'Configuración específica de la conexión',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (conexion_empresa_id) REFERENCES plan_empresas(empresa_id),
  INDEX idx_conexion_tipo (conexion_tipo),
  INDEX idx_conexion_estado (conexion_estado_salud)
) ENGINE=InnoDB;

CREATE TABLE crm_mapeos_datos (
  mapeo_id INT AUTO_INCREMENT PRIMARY KEY,
  mapeo_conexion_id INT NOT NULL,
  mapeo_nombre VARCHAR(200) NOT NULL,
  mapeo_tipo_indicador ENUM('cumplimiento','oportunidad','calidad','productividad') NOT NULL,
  mapeo_indicador_id INT,
  mapeo_endpoint_origen VARCHAR(500) COMMENT 'Endpoint o query de origen',
  mapeo_campo_origen VARCHAR(200) COMMENT 'Campo/columna en el origen',
  mapeo_transformacion_json JSON COMMENT 'Reglas de transformación del dato',
  mapeo_frecuencia_sincro ENUM('tiempo_real','cada_hora','diaria','semanal','mensual','manual') DEFAULT 'diaria',
  mapeo_ultima_ejecucion TIMESTAMP NULL,
  mapeo_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (mapeo_conexion_id) REFERENCES crm_conexiones(conexion_id),
  FOREIGN KEY (mapeo_indicador_id) REFERENCES ind_indicadores(indicador_id),
  INDEX idx_mapeo_conexion (mapeo_conexion_id),
  INDEX idx_mapeo_tipo (mapeo_tipo_indicador)
) ENGINE=InnoDB;

CREATE TABLE crm_mineria_datos (
  mineria_id INT AUTO_INCREMENT PRIMARY KEY,
  mineria_nombre VARCHAR(200) NOT NULL,
  mineria_descripcion TEXT,
  mineria_tipo_fuente ENUM('crm','correo','documentos','logs','base_datos','web_scraping','api') NOT NULL,
  mineria_conexion_id INT,
  mineria_query_config JSON COMMENT 'Configuración de búsqueda/extracción',
  mineria_patrones_json JSON COMMENT 'Patrones NLP/regex para identificar indicadores',
  mineria_indicadores_detectados JSON COMMENT 'Indicadores que esta minería puede detectar',
  mineria_frecuencia ENUM('continua','diaria','semanal','mensual','manual') DEFAULT 'diaria',
  mineria_ultima_ejecucion TIMESTAMP NULL,
  mineria_resultados_ultima JSON COMMENT 'Últimos resultados de la minería',
  mineria_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (mineria_conexion_id) REFERENCES crm_conexiones(conexion_id),
  INDEX idx_mineria_fuente (mineria_tipo_fuente)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 6: GESTIÓN DOCUMENTAL ISO (doc_)
-- ============================================================================

CREATE TABLE doc_sectores (
  sector_id INT AUTO_INCREMENT PRIMARY KEY,
  sector_nombre VARCHAR(150) NOT NULL UNIQUE,
  sector_descripcion TEXT,
  sector_icono VARCHAR(50),
  sector_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE doc_normas_iso (
  norma_id INT AUTO_INCREMENT PRIMARY KEY,
  norma_codigo VARCHAR(50) NOT NULL UNIQUE COMMENT 'ISO 9001, ISO 14001, ISO 45001, etc.',
  norma_nombre VARCHAR(300) NOT NULL,
  norma_descripcion TEXT,
  norma_version VARCHAR(20),
  norma_anio INT,
  norma_sector_id INT,
  norma_requisitos_json JSON COMMENT 'Estructura de requisitos de la norma',
  norma_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (norma_sector_id) REFERENCES doc_sectores(sector_id)
) ENGINE=InnoDB;

CREATE TABLE doc_plantillas (
  plantilla_id INT AUTO_INCREMENT PRIMARY KEY,
  plantilla_norma_id INT,
  plantilla_sector_id INT,
  plantilla_tipo_documento ENUM('manual_calidad','procedimiento','instructivo','registro','formato','politica','plan','informe','auditoria','otro') NOT NULL,
  plantilla_nombre VARCHAR(300) NOT NULL,
  plantilla_descripcion TEXT,
  plantilla_estructura_json JSON COMMENT 'Estructura/índice de la plantilla',
  plantilla_contenido_html TEXT COMMENT 'Contenido base en HTML/Markdown',
  plantilla_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (plantilla_norma_id) REFERENCES doc_normas_iso(norma_id),
  FOREIGN KEY (plantilla_sector_id) REFERENCES doc_sectores(sector_id),
  INDEX idx_plantilla_norma (plantilla_norma_id),
  INDEX idx_plantilla_sector (plantilla_sector_id),
  INDEX idx_plantilla_tipo (plantilla_tipo_documento)
) ENGINE=InnoDB;

CREATE TABLE doc_documentos (
  documento_id INT AUTO_INCREMENT PRIMARY KEY,
  documento_empresa_id INT NOT NULL,
  documento_proceso_id INT,
  documento_procedimiento_id INT,
  documento_plantilla_id INT,
  documento_norma_id INT,
  documento_codigo VARCHAR(50),
  documento_titulo VARCHAR(300) NOT NULL,
  documento_tipo ENUM('manual_calidad','procedimiento','instructivo','registro','formato','politica','plan','informe','auditoria','otro') NOT NULL,
  documento_version VARCHAR(20) DEFAULT '1.0',
  documento_estado ENUM('borrador','revision','aprobado','publicado','obsoleto') DEFAULT 'borrador',
  documento_contenido_html LONGTEXT,
  documento_archivo_url VARCHAR(500),
  documento_fecha_aprobacion DATE,
  documento_aprobado_por INT,
  documento_revisado_por INT,
  documento_elaborado_por INT,
  documento_fecha_vigencia DATE,
  documento_fecha_proxima_revision DATE,
  documento_control_cambios JSON,
  documento_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (documento_empresa_id) REFERENCES plan_empresas(empresa_id),
  FOREIGN KEY (documento_proceso_id) REFERENCES proc_procesos(proceso_id),
  FOREIGN KEY (documento_procedimiento_id) REFERENCES proc_procedimientos(procedimiento_id),
  FOREIGN KEY (documento_plantilla_id) REFERENCES doc_plantillas(plantilla_id),
  FOREIGN KEY (documento_norma_id) REFERENCES doc_normas_iso(norma_id),
  FOREIGN KEY (documento_aprobado_por) REFERENCES sys_usuarios(usuario_id),
  FOREIGN KEY (documento_revisado_por) REFERENCES sys_usuarios(usuario_id),
  FOREIGN KEY (documento_elaborado_por) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_doc_empresa (documento_empresa_id),
  INDEX idx_doc_proceso (documento_proceso_id),
  INDEX idx_doc_tipo (documento_tipo),
  INDEX idx_doc_estado (documento_estado)
) ENGINE=InnoDB;

CREATE TABLE doc_auditorias (
  auditoria_id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_empresa_id INT NOT NULL,
  auditoria_norma_id INT,
  auditoria_tipo ENUM('interna','externa','certificacion','seguimiento','renovacion') DEFAULT 'interna',
  auditoria_fecha_inicio DATE,
  auditoria_fecha_fin DATE,
  auditoria_estado ENUM('planificada','en_proceso','completada','cerrada') DEFAULT 'planificada',
  auditoria_alcance TEXT,
  auditoria_equipo_ids JSON,
  auditoria_hallazgos_json JSON COMMENT 'Hallazgos, no conformidades, observaciones',
  auditoria_informe_url VARCHAR(500),
  auditoria_puntaje DECIMAL(5,2),
  auditoria_resultado ENUM('conforme','no_conforme_mayor','no_conforme_menor','observaciones') DEFAULT 'conforme',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (auditoria_empresa_id) REFERENCES plan_empresas(empresa_id),
  FOREIGN KEY (auditoria_norma_id) REFERENCES doc_normas_iso(norma_id),
  INDEX idx_audit_empresa (auditoria_empresa_id),
  INDEX idx_audit_estado (auditoria_estado)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 7: INTELIGENCIA ARTIFICIAL (ia_)
-- ============================================================================

CREATE TABLE ia_modelos (
  modelo_id INT AUTO_INCREMENT PRIMARY KEY,
  modelo_nombre VARCHAR(200) NOT NULL,
  modelo_tipo ENUM('recomendacion','prediccion','analisis','clasificacion','generacion','asistente') NOT NULL,
  modelo_proveedor VARCHAR(100) COMMENT 'OpenAI, Claude, Gemini, local, etc.',
  modelo_endpoint VARCHAR(500),
  modelo_configuracion_json JSON,
  modelo_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ia_recomendaciones (
  recomendacion_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  recomendacion_modelo_id INT,
  recomendacion_contexto ENUM('plan','objetivo','estrategia','actividad','proceso','indicador','documento','evaluacion') NOT NULL,
  recomendacion_contexto_id INT NOT NULL COMMENT 'ID del elemento en su tabla correspondiente',
  recomendacion_contenido TEXT NOT NULL,
  recomendacion_tipo ENUM('sugerencia','alerta','correccion','mejora','innovacion') DEFAULT 'sugerencia',
  recomendacion_prioridad ENUM('alta','media','baja') DEFAULT 'media',
  recomendacion_aplicada TINYINT(1) DEFAULT 0,
  recomendacion_feedback ENUM('pendiente','util','no_util') DEFAULT 'pendiente',
  recomendacion_metadata_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recomendacion_modelo_id) REFERENCES ia_modelos(modelo_id),
  INDEX idx_recom_contexto (recomendacion_contexto, recomendacion_contexto_id),
  INDEX idx_recom_aplicada (recomendacion_aplicada)
) ENGINE=InnoDB;

CREATE TABLE ia_predicciones (
  prediccion_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  prediccion_modelo_id INT,
  prediccion_indicador_id INT,
  prediccion_contexto ENUM('cumplimiento','oportunidad','calidad','productividad','tendencia','riesgo') NOT NULL,
  prediccion_valor_previsto DECIMAL(18,4),
  prediccion_intervalo_confianza_json JSON,
  prediccion_horizonte ENUM('corto_plazo','mediano_plazo','largo_plazo') DEFAULT 'corto_plazo',
  prediccion_fecha_prediccion DATE NOT NULL,
  prediccion_fecha_objetivo DATE,
  prediccion_precision DECIMAL(5,2),
  prediccion_factores_json JSON COMMENT 'Factores que influyen en la predicción',
  prediccion_estado ENUM('activa','validada','descartada','superada') DEFAULT 'activa',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (prediccion_modelo_id) REFERENCES ia_modelos(modelo_id),
  FOREIGN KEY (prediccion_indicador_id) REFERENCES ind_indicadores(indicador_id),
  INDEX idx_pred_indicador (prediccion_indicador_id),
  INDEX idx_pred_fecha (prediccion_fecha_prediccion)
) ENGINE=InnoDB;

CREATE TABLE ia_asistencias (
  asistencia_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asistencia_modelo_id INT,
  asistencia_usuario_id INT NOT NULL,
  asistencia_contexto ENUM('planeacion','procesos','indicadores','documentacion','evaluacion','general') NOT NULL,
  asistencia_prompt TEXT NOT NULL,
  asistencia_respuesta TEXT NOT NULL,
  asistencia_tokens_entrada INT,
  asistencia_tokens_salida INT,
  asistencia_tiempo_respuesta_ms INT,
  asistencia_feedback ENUM('pendiente','positivo','negativo') DEFAULT 'pendiente',
  asistencia_metadata_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (asistencia_modelo_id) REFERENCES ia_modelos(modelo_id),
  FOREIGN KEY (asistencia_usuario_id) REFERENCES sys_usuarios(usuario_id),
  INDEX idx_asist_usuario (asistencia_usuario_id),
  INDEX idx_asist_contexto (asistencia_contexto),
  INDEX idx_asist_fecha (created_at)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 8: DASHBOARDS Y VISUALIZACIÓN (dash_)
-- ============================================================================

CREATE TABLE dash_tableros (
  tablero_id INT AUTO_INCREMENT PRIMARY KEY,
  tablero_nombre VARCHAR(200) NOT NULL,
  tablero_descripcion TEXT,
  tablero_tipo ENUM('planeacion','procesos','indicadores','individual','ejecutivo','operativo','personalizado') NOT NULL,
  tablero_usuario_id INT COMMENT 'Dueño del tablero (si es personalizado)',
  tablero_empresa_id INT,
  tablero_configuracion_json JSON COMMENT 'Layout, widgets, filtros',
  tablero_es_plantilla TINYINT(1) DEFAULT 0,
  tablero_compartido_con JSON COMMENT 'IDs de usuarios/roles con acceso',
  tablero_activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tablero_usuario_id) REFERENCES sys_usuarios(usuario_id),
  FOREIGN KEY (tablero_empresa_id) REFERENCES plan_empresas(empresa_id),
  INDEX idx_dash_tipo (tablero_tipo),
  INDEX idx_dash_usuario (tablero_usuario_id)
) ENGINE=InnoDB;

CREATE TABLE dash_widgets (
  widget_id INT AUTO_INCREMENT PRIMARY KEY,
  widget_tablero_id INT NOT NULL,
  widget_tipo ENUM('kpi','grafico_lineas','grafico_barras','grafico_pastel','grafico_radar',
                   'grafico_medidor','semaforo','tabla','mapa_calor','cascada','arbol',
                   'linea_tiempo','alimentacion_ia','comparativa','ranking','tendencia') NOT NULL,
  widget_titulo VARCHAR(200),
  widget_configuracion_json JSON COMMENT 'Fuente de datos, filtros, opciones visuales',
  widget_posicion_x INT DEFAULT 0,
  widget_posicion_y INT DEFAULT 0,
  widget_ancho INT DEFAULT 3,
  widget_alto INT DEFAULT 2,
  widget_orden INT DEFAULT 0,
  widget_activo TINYINT(1) DEFAULT 1,
  widget_actualizacion_automatica_segundos INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (widget_tablero_id) REFERENCES dash_tableros(tablero_id),
  INDEX idx_widget_tablero (widget_tablero_id)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 9: SECTORES ESPECÍFICOS
-- ============================================================================

CREATE TABLE sector_salud (
  salud_id INT AUTO_INCREMENT PRIMARY KEY,
  salud_empresa_id INT NOT NULL,
  salud_nivel_atencion ENUM('primario','secundario','terciario','cuaternario') DEFAULT 'primario',
  salud_habilitacion_no VARCHAR(50) COMMENT 'N° habilitación secretaría de salud',
  salud_tipo_institucion ENUM('ips','eps','hospital','clinica','consultorio','laboratorio','otro') DEFAULT 'ips',
  salud_camas INT DEFAULT 0,
  salud_servicios_habilitados JSON,
  salud_normas_aplicables JSON COMMENT 'ISO 7101, Res 3100, etc.',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (salud_empresa_id) REFERENCES plan_empresas(empresa_id),
  UNIQUE KEY uk_salud_empresa (salud_empresa_id)
) ENGINE=InnoDB;

CREATE TABLE sector_inmobiliario (
  inmob_empresa_id INT NOT NULL PRIMARY KEY,
  inmob_tipo_operacion ENUM('ventas','arriendos','administracion','proyectos','avaluos','mixto') DEFAULT 'mixto',
  inmob_numero_propiedades INT DEFAULT 0,
  inmob_numero_proyectos INT DEFAULT 0,
  inmob_zonas_operacion JSON,
  inmob_tipo_inmuebles JSON,
  inmob_camara_comercio VARCHAR(50),
  inmob_lonja_afiliacion VARCHAR(200),
  inmob_licencia_construccion VARCHAR(100),
  inmob_normas_aplicables JSON COMMENT 'ISO 41001, NTC 6047, Ley 820, etc.',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (inmob_empresa_id) REFERENCES plan_empresas(empresa_id)
) ENGINE=InnoDB;

CREATE TABLE sector_logistica_farma (
  logifarma_empresa_id INT NOT NULL PRIMARY KEY,
  logifarma_tipo_operacion ENUM('almacenamiento','distribucion','transporte','cadena_fria','dispensacion','integral') DEFAULT 'integral',
  logifarma_certificacion_bpa VARCHAR(100) COMMENT 'Buenas Prácticas de Almacenamiento',
  logifarma_certificacion_bpt VARCHAR(100) COMMENT 'Buenas Prácticas de Transporte',
  logifarma_areas_almacenamiento JSON,
  logifarma_capacidad_m3 DECIMAL(12,2),
  logifarma_flota_vehiculos INT DEFAULT 0,
  logifarma_vehiculos_refrigerados INT DEFAULT 0,
  logifarma_monitoreo_temperatura TINYINT(1) DEFAULT 0,
  logifarma_invima_registro VARCHAR(100),
  logifarma_normas_aplicables JSON COMMENT 'Res 1160, Dec 780, ISO 13485, BPE, etc.',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (logifarma_empresa_id) REFERENCES plan_empresas(empresa_id)
) ENGINE=InnoDB;

-- ============================================================================
-- DATOS SEED: Metodologías de Planeación
-- ============================================================================

INSERT INTO plan_metodologias (metodologia_nombre, metodologia_descripcion, metodologia_fases_json, metodologia_icono, metodologia_activo) VALUES
('Balanced Scorecard (BSC)', 'Metodología de Kaplan y Norton que traduce la estrategia en objetivos operativos medibles desde 4 perspectivas: Financiera, Cliente, Procesos Internos y Aprendizaje.',
 '{"fases":[
   {"orden":1,"nombre":"Definición de Visión y Estrategia","duracion_dias":15,"pasos":["Definir misión/visión","Identificar valores organizacionales","Establecer propuesta de valor"]},
   {"orden":2,"nombre":"Mapa Estratégico","duracion_dias":20,"pasos":["Construir mapa de relaciones causa-efecto","Identificar temas estratégicos","Validar hipótesis estratégicas"]},
   {"orden":3,"nombre":"Definición de Objetivos","duracion_dias":15,"pasos":["Definir objetivos por perspectiva","Establecer indicadores (KPIs)","Fijar metas por objetivo"]},
   {"orden":4,"nombre":"Iniciativas Estratégicas","duracion_dias":20,"pasos":["Identificar iniciativas clave","Priorizar portafolio de iniciativas","Asignar recursos y responsables"]},
   {"orden":5,"nombre":"Cuadro de Mando Integral","duracion_dias":15,"pasos":["Diseñar dashboard de seguimiento","Establecer frecuencia de revisión","Definir proceso de reporting"]},
   {"orden":6,"nombre":"Despliegue en Cascada","duracion_dias":30,"pasos":["Desplegar a unidades de negocio","Alinear objetivos individuales","Establecer sistema de incentivos"]},
   {"orden":7,"nombre":"Evaluación y Ajuste","duracion_dias":0,"pasos":["Reuniones de revisión estratégica","Análisis de desviaciones","Ajuste de metas e iniciativas"]}
 ]}', 'fa-balance-scale', 1),

('OKR (Objectives & Key Results)', 'Metodología de Google/Intel que conecta objetivos ambiciosos con resultados clave medibles en ciclos cortos.',
 '{"fases":[
   {"orden":1,"nombre":"Definición de Objetivos Maestros","duracion_dias":10,"pasos":["Definir 3-5 objetivos cualitativos ambiciosos","Validar alineación con misión","Comunicar a la organización"]},
   {"orden":2,"nombre":"Key Results","duracion_dias":10,"pasos":["Definir 3-5 KRs por objetivo","Establecer métricas medibles","Definir sistema de puntuación (0.0-1.0)"]},
   {"orden":3,"nombre":"Despliegue OKRs","duracion_dias":10,"pasos":["Cascada a equipos/departamentos","Sesiones de alineación cross-funcional","Validación de interdependencias"]},
   {"orden":4,"nombre":"Ejecución y Check-ins","duracion_dias":0,"pasos":["Check-ins semanales de progreso","Actualización de confianza (on track/at risk/off track)","Identificación de bloqueos"]},
   {"orden":5,"nombre":"Cierre y Retrospectiva","duracion_dias":5,"pasos":["Evaluación final de OKRs (scoring)","Retrospectiva del ciclo","Lecciones aprendidas para siguiente ciclo"]}
 ]}', 'fa-bullseye', 1),

('Hoshin Kanri (Policy Deployment)', 'Metodología japonesa de despliegue de políticas que alinea toda la organización hacia objetivos de ruptura.',
 '{"fases":[
   {"orden":1,"nombre":"Definición de la Visión","duracion_dias":15,"pasos":["Establecer visión a largo plazo (3-5 años)","Identificar brechas estratégicas","Definir objetivos de ruptura"]},
   {"orden":2,"nombre":"Plan Hoshin Anual","duracion_dias":20,"pasos":["Definir 3-5 prioridades estratégicas anuales","Establecer metas de mejora (hoshins)","Identificar recursos necesarios"]},
   {"orden":3,"nombre":"Despliegue en Catchball","duracion_dias":30,"pasos":["Proceso iterativo top-down/bottom-up","Negociación de metas entre niveles","Alineación horizontal y vertical"]},
   {"orden":4,"nombre":"Control Diario","duracion_dias":0,"pasos":["Tableros de control visual","Reuniones diarias de seguimiento","Gestión de anomalías"]},
   {"orden":5,"nombre":"Revisión del Presidente","duracion_dias":0,"pasos":["Revisiones mensuales ejecutivas","Análisis causa-raíz de desviaciones","Contramedidas y ajustes"]}
 ]}', 'fa-project-diagram', 1),

('Planeación por Escenarios', 'Metodología que construye múltiples futuros posibles para desarrollar estrategias robustas ante la incertidumbre.',
 '{"fases":[
   {"orden":1,"nombre":"Análisis del Entorno","duracion_dias":15,"pasos":["Análisis PESTEL completo","Identificación de tendencias","Mapeo de actores clave"]},
   {"orden":2,"nombre":"Identificación de Incertidumbres","duracion_dias":10,"pasos":["Identificar fuerzas impulsoras","Clasificar por impacto e incertidumbre","Seleccionar incertidumbres críticas"]},
   {"orden":3,"nombre":"Construcción de Escenarios","duracion_dias":20,"pasos":["Desarrollar lógica de escenarios (2x2)","Escribir narrativas para cada escenario","Nombrar y caracterizar escenarios"]},
   {"orden":4,"nombre":"Estrategias Robusta","duracion_dias":15,"pasos":["Identificar implicaciones estratégicas","Desarrollar opciones estratégicas por escenario","Identificar señales tempranas (early warnings)"]},
   {"orden":5,"nombre":"Monitoreo de Señales","duracion_dias":0,"pasos":["Dashboard de monitoreo de tendencias","Sistema de alertas tempranas","Revisiones periódicas de escenarios"]}
 ]}', 'fa-chess-board', 1),

('Design Thinking Estratégico', 'Metodología que aplica pensamiento de diseño para resolver problemas estratégicos complejos centrados en el humano.',
 '{"fases":[
   {"orden":1,"nombre":"Empatizar","duracion_dias":15,"pasos":["Investigación con stakeholders","Mapa de empatía","Entrevistas a profundidad","Shadowing"]},
   {"orden":2,"nombre":"Definir","duracion_dias":10,"pasos":["Síntesis de hallazgos","Definición del problema (Point of View)","Cómo podríamos nosotros (HMW)","Criterios de éxito"]},
   {"orden":3,"nombre":"Idear","duracion_dias":10,"pasos":["Brainstorming estructurado","Técnicas de ideación","Agrupación y priorización","Selección de ideas promisorias"]},
   {"orden":4,"nombre":"Prototipar","duracion_dias":15,"pasos":["Prototipos de bajo costo","Mapas de servicio (Service Blueprint)","Business Model Canvas","Validación rápida"]},
   {"orden":5,"nombre":"Testear","duracion_dias":15,"pasos":["Pruebas con usuarios reales","Recolección de feedback","Iteración y refinamiento","Plan de implementación"]}
 ]}', 'fa-lightbulb', 1);

-- ============================================================================
-- DATOS SEED: Categorías de Indicadores (4 variantes)
-- ============================================================================

INSERT INTO ind_categorias (categoria_nombre, categoria_tipo, categoria_descripcion, categoria_color, categoria_icono, categoria_formula_base) VALUES
('Cumplimiento de Metas', 'cumplimiento', 'Mide el grado de consecución de los objetivos y metas establecidos en el plan estratégico.',
 '#28a745', 'fa-check-circle', '(Resultado Alcanzado / Meta Programada) * 100'),
('Oportunidad en Ejecución', 'oportunidad', 'Evalúa la puntualidad y temporalidad en la ejecución de actividades y entrega de resultados.',
 '#ffc107', 'fa-clock', '(Actividades a Tiempo / Total Actividades) * 100'),
('Calidad de Resultados', 'calidad', 'Mide el nivel de calidad, precisión y satisfacción de los entregables y resultados obtenidos.',
 '#007bff', 'fa-star', '(Resultados Conformes / Total Resultados Evaluados) * 100'),
('Productividad Organizacional', 'productividad', 'Evalúa la eficiencia en el uso de recursos para la generación de resultados y valor agregado.',
 '#6f42c1', 'fa-chart-line', '(Output Generado / Recursos Utilizados) * 100');

-- ============================================================================
-- DATOS SEED: Sectores
-- ============================================================================

INSERT INTO doc_sectores (sector_nombre, sector_descripcion, sector_icono, sector_activo) VALUES
('Salud', 'Instituciones prestadoras de servicios de salud, hospitales, clínicas, EPS, IPS, laboratorios clínicos.', 'fa-hospital', 1),
('Inmobiliario', 'Empresas de bienes raíces, constructoras, administradoras de propiedad horizontal, avalúos.', 'fa-building', 1),
('Logística Farmacéutica', 'Operadores logísticos de medicamentos, distribuidores farmacéuticos, cadena de frío, dispensación.', 'fa-truck-medical', 1),
('Tecnología', 'Empresas de desarrollo de software, servicios TI, consultoría tecnológica, SaaS.', 'fa-laptop-code', 1),
('Manufactura', 'Industria manufacturera, producción, ensamblaje, transformación de materias primas.', 'fa-industry', 1),
('General', 'Aplicable a cualquier sector no especializado. Metodologías y normas genéricas.', 'fa-globe', 1);

-- ============================================================================
-- DATOS SEED: Normas ISO por Sector
-- ============================================================================

INSERT INTO doc_normas_iso (norma_codigo, norma_nombre, norma_descripcion, norma_version, norma_anio, norma_sector_id, norma_requisitos_json, norma_activo) VALUES
-- Sector Salud
('ISO 7101:2023', 'Sistemas de Gestión de Calidad en Organizaciones de Salud',
 'Establece requisitos para sistemas de gestión de calidad en organizaciones sanitarias, enfocándose en atención centrada en el paciente, seguridad clínica y mejora continua.',
 '2023', 2023, 1,
 '{"clausulas":[{"num":"4","nombre":"Contexto de la Organización","requisitos":["Comprensión de la organización y su contexto","Comprensión de necesidades de partes interesadas","Determinación del alcance","SGC y sus procesos"]},{"num":"5","nombre":"Liderazgo","requisitos":["Liderazgo y compromiso","Política de calidad","Roles y responsabilidades"]},{"num":"6","nombre":"Planificación","requisitos":["Acciones para abordar riesgos y oportunidades","Objetivos de calidad","Planificación de cambios"]},{"num":"7","nombre":"Apoyo","requisitos":["Recursos","Competencia","Toma de conciencia","Comunicación","Información documentada"]},{"num":"8","nombre":"Operación","requisitos":["Planificación y control operacional","Requisitos para servicios de salud","Diseño de servicios","Control de procesos externos","Prestación del servicio","Liberación de servicios","Control de no conformidades"]},{"num":"9","nombre":"Evaluación del Desempeño","requisitos":["Seguimiento y medición","Auditoría interna","Revisión por la dirección"]},{"num":"10","nombre":"Mejora","requisitos":["No conformidad y acción correctiva","Mejora continua"]}]}', 1),
('ISO 9001:2015', 'Sistemas de Gestión de Calidad - Requisitos',
 'Norma internacional que especifica los requisitos para un sistema de gestión de calidad, aplicable a cualquier organización.',
 '2015', 2015, 1,
 '{"clausulas":[{"num":"4","nombre":"Contexto de la Organización"},{"num":"5","nombre":"Liderazgo"},{"num":"6","nombre":"Planificación"},{"num":"7","nombre":"Apoyo"},{"num":"8","nombre":"Operación"},{"num":"9","nombre":"Evaluación del Desempeño"},{"num":"10","nombre":"Mejora"}]}', 1),
('ISO 45001:2018', 'Sistemas de Gestión de Seguridad y Salud en el Trabajo',
 'Requisitos para un sistema de gestión de SST, con orientación para su uso, que permite a las organizaciones proporcionar lugares de trabajo seguros y saludables.',
 '2018', 2018, 1,
 '{"clausulas":[{"num":"4","nombre":"Contexto"},{"num":"5","nombre":"Liderazgo y participación"},{"num":"6","nombre":"Planificación"},{"num":"7","nombre":"Apoyo"},{"num":"8","nombre":"Operación"},{"num":"9","nombre":"Evaluación"},{"num":"10","nombre":"Mejora"}]}', 1),
('ISO 15189:2022', 'Laboratorios Clínicos - Requisitos para la Calidad y Competencia',
 'Estándar para laboratorios clínicos que cubre aspectos de gestión y técnicos para asegurar resultados confiables.',
 '2022', 2022, 1, '{}', 1),

-- Sector Inmobiliario
('ISO 41001:2018', 'Facility Management - Sistemas de Gestión',
 'Requisitos para un sistema de gestión de facility management, cubriendo la gestión integral de inmuebles y servicios de soporte.',
 '2018', 2018, 2,
 '{"clausulas":[{"num":"4","nombre":"Contexto"},{"num":"5","nombre":"Liderazgo"},{"num":"6","nombre":"Planificación"},{"num":"7","nombre":"Apoyo"},{"num":"8","nombre":"Operación"},{"num":"9","nombre":"Evaluación"},{"num":"10","nombre":"Mejora"}]}', 1),
('ISO 19650', 'Gestión de Información en BIM',
 'Serie de normas para la gestión de información utilizando Building Information Modeling durante el ciclo de vida de un activo construido.',
 '2018', 2018, 2, '{}', 1),

-- Sector Logística Farmacéutica
('ISO 13485:2016', 'Dispositivos Médicos - Sistemas de Gestión de Calidad',
 'Requisitos para un sistema de gestión de calidad para organizaciones involucradas en el ciclo de vida de dispositivos médicos.',
 '2016', 2016, 3,
 '{"clausulas":[{"num":"4","nombre":"Sistema de Gestión de Calidad"},{"num":"5","nombre":"Responsabilidad de la Dirección"},{"num":"6","nombre":"Gestión de Recursos"},{"num":"7","nombre":"Realización del Producto"},{"num":"8","nombre":"Medición, Análisis y Mejora"}]}', 1),
('ISO 28000:2022', 'Sistemas de Gestión de Seguridad para la Cadena de Suministro',
 'Especifica requisitos para un sistema de gestión de seguridad de la cadena de suministro.',
 '2022', 2022, 3, '{}', 1),
('GDP (BPD)', 'Good Distribution Practices - Buenas Prácticas de Distribución',
 'Directrices de la UE para la distribución adecuada de medicamentos para uso humano, cubriendo cadena de frío, trazabilidad y seguridad.',
 '2013/C 343/01', 2013, 3,
 '{"capitulos":[{"num":"1","nombre":"Sistema de Calidad"},{"num":"2","nombre":"Personal"},{"num":"3","nombre":"Instalaciones y Equipos"},{"num":"4","nombre":"Documentación"},{"num":"5","nombre":"Operaciones"},{"num":"6","nombre":"Quejas, Devoluciones y Retiros"},{"num":"7","nombre":"Actividades Externalizadas"},{"num":"8","nombre":"Autoinspecciones"}]}', 1),

-- Sector General (todas las empresas)
('ISO 14001:2015', 'Sistemas de Gestión Ambiental',
 'Requisitos para un sistema de gestión ambiental eficaz que contribuya al pilar ambiental de la sostenibilidad.',
 '2015', 2015, 6, '{}', 1),
('ISO 31000:2018', 'Gestión del Riesgo - Directrices',
 'Proporciona directrices para gestionar el riesgo que enfrentan las organizaciones.',
 '2018', 2018, 6, '{}', 1),
('ISO 27001:2022', 'Sistemas de Gestión de Seguridad de la Información',
 'Requisitos para establecer, implementar, mantener y mejorar un SGSI.',
 '2022', 2022, 6, '{}', 1);

-- ============================================================================
-- DATOS SEED: Roles
-- ============================================================================

INSERT INTO sys_roles (rol_nombre, rol_descripcion) VALUES
('Super Admin', 'Control total del sistema. Acceso a todas las funcionalidades y configuraciones.'),
('Director General', 'Visión ejecutiva de toda la organización. Define planes estratégicos y revisa resultados.'),
('Gerente de Área', 'Gestiona la planeación táctica y seguimiento de su área. Define objetivos e indicadores.'),
('Coordinador', 'Coordina equipos, asigna actividades y hace seguimiento al avance de su unidad.'),
('Analista', 'Registra mediciones, ejecuta análisis, genera reportes. Acceso de lectura y escritura limitado.'),
('Colaborador', 'Visualiza sus actividades asignadas, registra tiempos y avances. Evaluaciones individuales.'),
('Auditor Externo', 'Acceso de solo lectura para auditorías de procesos y documentación ISO.'),
('Cliente/Invitado', 'Visualización limitada de dashboards compartidos. Solo tableros designados.');

-- ============================================================================
-- DATOS SEED: Módulos del Sistema
-- ============================================================================

INSERT INTO sys_modulos (modulo_nombre, modulo_descripcion, modulo_icono, modulo_ruta, modulo_orden) VALUES
('dashboard', 'Tableros de Control y Visualización', 'fa-chart-pie', '/dashboards', 1),
('planeacion', 'Planeación Estratégica', 'fa-bullseye', '/planeacion', 2),
('procesos', 'Gestión de Procesos', 'fa-diagram-project', '/procesos', 3),
('indicadores', 'Indicadores KPIs', 'fa-gauge-high', '/indicadores', 4),
('documentacion', 'Gestión Documental ISO', 'fa-file-lines', '/docs', 5),
('crm_integracion', 'Integración CRM/WS', 'fa-plug', '/integracion', 6),
('ia', 'Inteligencia Artificial', 'fa-brain', '/ia', 7),
('evaluacion', 'Evaluación de Desempeño', 'fa-user-check', '/evaluacion', 8),
('configuracion', 'Configuración del Sistema', 'fa-gear', '/config', 9),
('usuarios', 'Gestión de Usuarios', 'fa-users', '/usuarios', 10);

-- ============================================================================
-- TRIGGERS
-- ============================================================================

DELIMITER //

-- Trigger: Actualizar avance de actividad cuando se completa un mapa
CREATE TRIGGER tr_actualizar_avance_actividad
AFTER UPDATE ON plan_mapa_actividades
FOR EACH ROW
BEGIN
  DECLARE total_mapas INT;
  DECLARE mapas_completados INT;
  DECLARE nuevo_avance DECIMAL(5,2);

  IF NEW.mapa_estado = 'completado' AND OLD.mapa_estado != 'completado' THEN
    SELECT COUNT(*), SUM(CASE WHEN mapa_estado = 'completado' THEN 1 ELSE 0 END)
    INTO total_mapas, mapas_completados
    FROM plan_mapa_actividades
    WHERE mapa_actividad_id = NEW.mapa_actividad_id;

    IF total_mapas > 0 THEN
      SET nuevo_avance = (mapas_completados / total_mapas) * 100;
      UPDATE plan_actividades
      SET actividad_avance_porcentaje = nuevo_avance,
          actividad_estado = CASE
            WHEN nuevo_avance >= 100 THEN 'completada'
            WHEN nuevo_avance > 0 THEN 'en_proceso'
            ELSE actividad_estado
          END
      WHERE actividad_id = NEW.mapa_actividad_id;
    END IF;
  END IF;
END//

-- Trigger: Actualizar avance de estrategia basado en sus actividades
CREATE TRIGGER tr_actualizar_avance_estrategia
AFTER UPDATE ON plan_actividades
FOR EACH ROW
BEGIN
  DECLARE avg_avance DECIMAL(5,2);

  IF NEW.actividad_estrategia_id IS NOT NULL THEN
    SELECT AVG(actividad_avance_porcentaje)
    INTO avg_avance
    FROM plan_actividades
    WHERE actividad_estrategia_id = NEW.actividad_estrategia_id;

    UPDATE plan_estrategias
    SET estrategia_avance_porcentaje = COALESCE(avg_avance, 0),
        estrategia_estado = CASE
          WHEN COALESCE(avg_avance, 0) >= 100 THEN 'implementada'
          WHEN COALESCE(avg_avance, 0) > 0 THEN 'en_proceso'
          ELSE 'pendiente'
        END
    WHERE estrategia_id = NEW.actividad_estrategia_id;
  END IF;
END//

-- Trigger: Actualizar avance de objetivo basado en sus estrategias
CREATE TRIGGER tr_actualizar_avance_objetivo
AFTER UPDATE ON plan_estrategias
FOR EACH ROW
BEGIN
  DECLARE avg_avance DECIMAL(5,2);
  DECLARE peso_total DECIMAL(5,2);

  SELECT AVG(estrategia_avance_porcentaje)
  INTO avg_avance
  FROM plan_estrategias
  WHERE estrategia_objetivo_id = NEW.estrategia_objetivo_id;

  UPDATE plan_objetivos
  SET objetivo_avance_porcentaje = COALESCE(avg_avance, 0),
      objetivo_estado = CASE
        WHEN COALESCE(avg_avance, 0) >= 100 THEN 'cumplido'
        WHEN COALESCE(avg_avance, 0) > 0 THEN 'en_proceso'
        ELSE 'pendiente'
      END
  WHERE objetivo_id = NEW.estrategia_objetivo_id;
END//

-- Trigger: Calcular semáforo automáticamente al insertar medición
CREATE TRIGGER tr_calcular_semaforo_medicion
BEFORE INSERT ON ind_mediciones
FOR EACH ROW
BEGIN
  DECLARE meta_valor DECIMAL(18,4);
  DECLARE semaforo_config JSON;

  SELECT m.meta_valor, i.indicador_semaforo_json
  INTO meta_valor, semaforo_config
  FROM ind_metas m
  JOIN ind_indicadores i ON m.meta_indicador_id = i.indicador_id
  WHERE m.meta_id = NEW.medicion_meta_id;

  IF meta_valor IS NOT NULL AND meta_valor != 0 THEN
    SET NEW.medicion_cumplimiento_porcentaje = (NEW.medicion_valor / meta_valor) * 100;
    SET NEW.medicion_desviacion = NEW.medicion_valor - meta_valor;

    IF NEW.medicion_cumplimiento_porcentaje >= 90 THEN
      SET NEW.medicion_semaforo = 'verde';
    ELSEIF NEW.medicion_cumplimiento_porcentaje >= 70 THEN
      SET NEW.medicion_semaforo = 'amarillo';
    ELSE
      SET NEW.medicion_semaforo = 'rojo';
    END IF;
  END IF;
END//

-- Trigger: Calcular tiempo real promedio en tareas cuando se completa un mapeo
CREATE TRIGGER tr_actualizar_tiempo_promedio_tarea
AFTER INSERT ON proc_mapeo_tiempos
FOR EACH ROW
BEGIN
  DECLARE tiempo_promedio DECIMAL(10,2);

  SELECT AVG(mapeo_tiempo_total_minutos)
  INTO tiempo_promedio
  FROM proc_mapeo_tiempos
  WHERE mapeo_tarea_id = NEW.mapeo_tarea_id
    AND mapeo_estado = 'completado'
    AND mapeo_tiempo_total_minutos IS NOT NULL;

  UPDATE proc_tareas
  SET tarea_tiempo_real_promedio_minutos = COALESCE(tiempo_promedio, tarea_tiempo_estimado_minutos)
  WHERE tarea_id = NEW.mapeo_tarea_id;
END//

-- Trigger: Registrar en log del sistema cambios en planes estratégicos
CREATE TRIGGER tr_log_cambios_plan
AFTER UPDATE ON plan_planes_estrategicos
FOR EACH ROW
BEGIN
  IF OLD.plan_estado != NEW.plan_estado THEN
    INSERT INTO sys_logs_sistema (log_accion, log_modulo, log_entidad, log_entidad_id, log_detalle)
    VALUES ('cambio_estado', 'planeacion', 'plan', NEW.plan_id,
            JSON_OBJECT('anterior', OLD.plan_estado, 'nuevo', NEW.plan_estado));
  END IF;
END//

DELIMITER ;

-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================

DELIMITER //

-- SP: Calcular indicadores de desempeño de un usuario
CREATE PROCEDURE sp_calcular_desempeno_usuario(
  IN p_usuario_id INT,
  IN p_periodo VARCHAR(50)
)
BEGIN
  DECLARE cumplimiento DECIMAL(5,2) DEFAULT 0;
  DECLARE oportunidad DECIMAL(5,2) DEFAULT 0;
  DECLARE calidad DECIMAL(5,2) DEFAULT 0;
  DECLARE productividad DECIMAL(5,2) DEFAULT 0;
  DECLARE total DECIMAL(5,2) DEFAULT 0;

  -- Cumplimiento: % actividades completadas
  SELECT COALESCE(
    (SUM(CASE WHEN mapa_estado = 'completado' THEN 1 ELSE 0 END) /
     NULLIF(COUNT(*), 0)) * 100, 0)
  INTO cumplimiento
  FROM plan_mapa_actividades
  WHERE mapa_usuario_id = p_usuario_id
    AND mapa_fecha_asignacion BETWEEN
      STR_TO_DATE(CONCAT(p_periodo, '-01'), '%Y-%m-%d')
      AND LAST_DAY(STR_TO_DATE(CONCAT(p_periodo, '-01'), '%Y-%m-%d'));

  -- Oportunidad: % completadas a tiempo
  SELECT COALESCE(
    (SUM(CASE WHEN mapa_estado = 'completado' AND mapa_fecha_completado <=
      (SELECT actividad_fecha_fin_planeada FROM plan_actividades WHERE actividad_id = mapa_actividad_id)
      THEN 1 ELSE 0 END) / NULLIF(SUM(CASE WHEN mapa_estado = 'completado' THEN 1 ELSE 0 END), 0)) * 100, 0)
  INTO oportunidad
  FROM plan_mapa_actividades
  WHERE mapa_usuario_id = p_usuario_id;

  -- Calidad: promedio cumplimiento de indicadores de calidad asociados
  SELECT COALESCE(AVG(m.medicion_cumplimiento_porcentaje), 0)
  INTO calidad
  FROM ind_mediciones m
  JOIN ind_indicadores i ON m.medicion_indicador_id = i.indicador_id
  JOIN plan_mapa_actividades pma ON i.indicador_plan_id = (SELECT fase_plan_id FROM plan_fases WHERE fase_id =
    (SELECT actividad_objetivo_id FROM plan_actividades WHERE actividad_id = pma.mapa_actividad_id))
  WHERE pma.mapa_usuario_id = p_usuario_id
    AND i.indicador_categoria_id IN (SELECT categoria_id FROM ind_categorias WHERE categoria_tipo = 'calidad');

  -- Productividad: output / tiempo
  SELECT COALESCE(
    (COUNT(DISTINCT CASE WHEN estado = 'completado' THEN mapa_actividad_id END) /
     NULLIF(SUM(COALESCE(mapa_tiempo_real_minutos, mapa_tiempo_estimado_minutos, 480)), 0)) * 48000, 0)
  INTO productividad
  FROM (
    SELECT mapa_actividad_id, mapa_estado, mapa_tiempo_real_minutos, mapa_tiempo_estimado_minutos
    FROM plan_mapa_actividades
    WHERE mapa_usuario_id = p_usuario_id
  ) AS subq;

  SET total = (cumplimiento * 0.30) + (oportunidad * 0.25) + (calidad * 0.20) + (productividad * 0.25);

  INSERT INTO ind_evaluaciones_desempeno (
    evaluacion_usuario_id, evaluacion_periodo, evaluacion_fecha,
    evaluacion_puntaje_cumplimiento, evaluacion_puntaje_oportunidad,
    evaluacion_puntaje_calidad, evaluacion_puntaje_productividad,
    evaluacion_puntaje_total, evaluacion_estado
  ) VALUES (
    p_usuario_id, p_periodo, CURDATE(),
    cumplimiento, oportunidad, calidad, productividad,
    total, 'autoevaluacion'
  )
  ON DUPLICATE KEY UPDATE
    evaluacion_puntaje_cumplimiento = cumplimiento,
    evaluacion_puntaje_oportunidad = oportunidad,
    evaluacion_puntaje_calidad = calidad,
    evaluacion_puntaje_productividad = productividad,
    evaluacion_puntaje_total = total,
    updated_at = CURRENT_TIMESTAMP;

  SELECT cumplimiento AS puntaje_cumplimiento,
         oportunidad AS puntaje_oportunidad,
         calidad AS puntaje_calidad,
         productividad AS puntaje_productividad,
         total AS puntaje_total;
END//

-- SP: Generar resumen ejecutivo del plan estratégico
CREATE PROCEDURE sp_resumen_ejecutivo_plan(
  IN p_plan_id INT
)
BEGIN
  SELECT
    p.plan_nombre AS plan_estrategico,
    p.plan_estado,
    p.plan_avance_porcentaje AS avance_general,
    met.metodologia_nombre AS metodologia,
    (SELECT COUNT(*) FROM plan_objetivos WHERE objetivo_plan_id = p.plan_id) AS total_objetivos,
    (SELECT COUNT(*) FROM plan_objetivos WHERE objetivo_plan_id = p.plan_id AND objetivo_estado = 'cumplido') AS objetivos_cumplidos,
    (SELECT COUNT(*) FROM plan_estrategias e JOIN plan_objetivos o ON e.estrategia_objetivo_id = o.objetivo_id WHERE o.objetivo_plan_id = p.plan_id) AS total_estrategias,
    (SELECT COUNT(*) FROM plan_actividades a JOIN plan_objetivos o ON a.actividad_objetivo_id = o.objetivo_id WHERE o.objetivo_plan_id = p.plan_id) AS total_actividades,
    (SELECT COUNT(*) FROM plan_actividades a JOIN plan_objetivos o ON a.actividad_objetivo_id = o.objetivo_id WHERE o.objetivo_plan_id = p.plan_id AND a.actividad_estado = 'completada') AS actividades_completadas,
    (SELECT COUNT(*) FROM ind_mediciones m JOIN ind_indicadores i ON m.medicion_indicador_id = i.indicador_id WHERE i.indicador_plan_id = p.plan_id AND m.medicion_semaforo = 'verde') AS indicadores_verdes,
    (SELECT COUNT(*) FROM ind_mediciones m JOIN ind_indicadores i ON m.medicion_indicador_id = i.indicador_id WHERE i.indicador_plan_id = p.plan_id AND m.medicion_semaforo = 'amarillo') AS indicadores_amarillos,
    (SELECT COUNT(*) FROM ind_mediciones m JOIN ind_indicadores i ON m.medicion_indicador_id = i.indicador_id WHERE i.indicador_plan_id = p.plan_id AND m.medicion_semaforo = 'rojo') AS indicadores_rojos,
    p.plan_presupuesto_total,
    (SELECT SUM(presupuesto_monto_ejecutado) FROM plan_presupuestos WHERE presupuesto_plan_id = p.plan_id) AS presupuesto_ejecutado
  FROM plan_planes_estrategicos p
  JOIN plan_metodologias met ON p.plan_metodologia_id = met.metodologia_id
  WHERE p.plan_id = p_plan_id;
END//

DELIMITER ;

-- ============================================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================================================

-- Optimización para consultas frecuentes en dashboards
CREATE INDEX idx_act_avance ON plan_actividades(actividad_avance_porcentaje);
CREATE INDEX idx_med_valor ON ind_mediciones(medicion_valor);
CREATE INDEX idx_eval_total ON ind_evaluaciones_desempeno(evaluacion_puntaje_total);
CREATE INDEX idx_doc_fechas ON doc_documentos(documento_fecha_vigencia, documento_fecha_proxima_revision);

-- Optimización para minería de datos y CRM
CREATE INDEX idx_med_origen_fecha ON ind_mediciones(medicion_origen, medicion_fecha);
CREATE INDEX idx_mapeo_origen_fecha ON proc_mapeo_tiempos(mapeo_tipo_registro, mapeo_fecha_inicio);

-- Optimización para búsqueda de usuarios
CREATE INDEX idx_usuarios_departamento ON sys_usuarios(usuario_departamento);
