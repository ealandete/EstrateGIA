# EstrateGIA - Arquitectura de Datos y Flujo de Información

## 1. Visión General

EstrateGIA implementa una arquitectura de tres capas que separa datos de configuración, datos operacionales y datos analíticos, siguiendo el mismo patrón del sistema agropecuario.

```
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE RESULTADOS                        │
│  Dashboards  │  Reportes  │  Evaluaciones  │  Predicciones  │
├─────────────────────────────────────────────────────────────┤
│                    CAPA DE OPERACIÓN                         │
│  Planes │ Actividades │ Mediciones │ Tiempos │ Documentos   │
├─────────────────────────────────────────────────────────────┤
│                    CAPA DE PARÁMETROS                        │
│  Metodologías │ Normas ISO │ Sectores │ Catálogos │ Roles   │
└─────────────────────────────────────────────────────────────┘
```

## 2. Flujo de Datos Principal

### 2.1 Ciclo de Planeación Estratégica

```
1. DEFINIR EMPRESA → 2. SELECCIONAR METODOLOGÍA → 3. CREAR PLAN
   ↓                        ↓                              ↓
4. EJECUTAR FASES → 5. DEFINIR OBJETIVOS → 6. CREAR ESTRATEGIAS
   ↓                        ↓
7. ASIGNAR ACTIVIDADES → 8. MAPEAR COLABORADORES
   ↓
9. EJECUTAR → 10. MEDIR → 11. EVALUAR → 12. AJUSTAR (ciclo)
```

### 2.2 Integración Automática de Datos

```
CRMs/ERPs ──→ Web Services ──→ Mapeos de Datos ──→ Mediciones Automáticas
    │                                                      │
    └────→ Minería de Datos ──→ Detección KPIs ───────────┘
                  │
Documentos ──→ NLP/Regex ──→ Extracción Indicadores ──────┘
Logs/BD ──→ Queries ──→ Transformación ─────────────────┘

Resultado: % mínimo de registro manual, máximo de automatización
```

## 3. API REST

### 3.1 Estructura de Endpoints

```
{BASE_URL}/api/{modulo}/{recurso}
```

### 3.2 Formato de Respuesta Estándar

```json
{
  "success": true,
  "data": {},
  "message": "Operación exitosa",
  "timestamp": "2026-05-11T10:30:00-05:00"
}
```

### 3.3 Formato de Error

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Datos inválidos",
    "details": { "campo": ["Mensaje de error"] }
  }
}
```

### 3.4 Endpoints por Módulo

#### Autenticación
- `POST /api/auth/login` - Iniciar sesión (JWT)
- `POST /api/auth/logout` - Cerrar sesión
- `GET  /api/auth/me` - Perfil del usuario autenticado

#### Sistema Base
- `GET    /api/usuarios` - Listar usuarios
- `POST   /api/usuarios` - Crear usuario
- `GET    /api/roles` - Listar roles
- `GET    /api/permisos` - Permisos del usuario

#### Planeación Estratégica
- `GET    /api/metodologias` - Metodologías disponibles
- `GET    /api/empresas` - Empresas
- `POST   /api/empresas` - Crear empresa
- `GET    /api/planes` - Planes estratégicos
- `POST   /api/planes` - Crear plan (genera fases automáticamente)
- `GET    /api/planes/{id}` - Detalle de plan
- `GET    /api/planes/{id}/arbol` - Árbol completo (fases→objetivos→estrategias→actividades)
- `GET    /api/planes/{id}/progreso` - Resumen ejecutivo (SP)
- `GET    /api/fases/{id}/paso-a-paso` - Guía de fase
- `POST   /api/analisis` - Crear análisis (FODA/PESTEL/etc.)
- `GET    /api/objetivos?plan_id={id}` - Objetivos del plan
- `POST   /api/objetivos` - Crear objetivo
- `POST   /api/estrategias` - Crear estrategia
- `POST   /api/actividades` - Crear actividad
- `GET    /api/actividades?responsable_id={id}` - Actividades por responsable
- `POST   /api/mapa-actividades` - Asignar usuario a actividad
- `PUT    /api/mapa-actividades/{id}/estado` - Actualizar estado

#### Procesos
- `GET    /api/macroprocesos?empresa_id={id}` - Macroprocesos
- `GET    /api/procesos?macro_id={id}` - Procesos
- `POST   /api/procesos` - Crear proceso
- `GET    /api/procesos/{id}` - Estado completo del proceso
- `POST   /api/procedimientos` - Crear procedimiento
- `POST   /api/tareas` - Crear tarea
- `POST   /api/tiempos/iniciar` - Iniciar registro de tiempo
- `PUT    /api/tiempos/{id}/finalizar` - Finalizar registro
- `GET    /api/tiempos/estadisticas?usuario_id={id}` - Estadísticas de usuario

#### Indicadores
- `GET    /api/categorias` - Categorías de indicadores (4 variantes)
- `POST   /api/indicadores` - Crear indicador
- `GET    /api/indicadores?plan_id={id}` - Indicadores del plan
- `POST   /api/metas` - Crear meta
- `POST   /api/mediciones` - Registrar medición
- `GET    /api/mediciones?indicador_id={id}` - Histórico
- `GET    /api/variantes/resumen?plan_id={id}` - Resumen 4 variantes
- `GET    /api/variantes/semaforo?plan_id={id}` - Semáforo
- `GET    /api/variantes/tendencia?plan_id={id}` - Tendencia
- `POST   /api/evaluaciones/calcular/{usuario_id}` - Calcular evaluación
- `GET    /api/evaluaciones/{usuario_id}` - Historial evaluaciones
- `GET    /api/ranking?periodo={periodo}` - Ranking colaboradores

#### Documentación
- `GET    /api/sectores` - Sectores disponibles
- `GET    /api/normas-iso?sector_id={id}` - Normas por sector
- `GET    /api/plantillas?norma_id={id}` - Plantillas documento
- `POST   /api/documentos` - Crear documento
- `GET    /api/documentos?empresa_id={id}` - Documentos
- `PUT    /api/documentos/{id}/aprobar` - Aprobar
- `PUT    /api/documentos/{id}/publicar` - Publicar
- `POST   /api/documentos/{id}/version` - Nueva versión
- `POST   /api/auditorias` - Crear auditoría
- `GET    /api/sector/salud/{empresa_id}` - Info sector salud
- `GET    /api/sector/inmobiliario/{empresa_id}` - Info sector inmobiliario

#### Integración CRM
- `POST   /api/conexiones` - Crear conexión externa
- `POST   /api/conexiones/{id}/test` - Probar conexión
- `POST   /api/mapeos` - Crear mapeo datos
- `POST   /api/mapeos/{id}/sincronizar` - Ejecutar sincronización
- `POST   /api/mineria` - Crear config minería
- `POST   /api/mineria/{id}/ejecutar` - Ejecutar minería
- `POST   /api/sincronizacion/completa?empresa_id={id}` - Ciclo completo

#### Inteligencia Artificial
- `POST   /api/ia/asistencia` - Consulta al asistente IA
- `GET    /api/ia/asistencias/historial` - Historial
- `POST   /api/ia/recomendaciones` - Generar recomendación
- `GET    /api/ia/recomendaciones?contexto=plan&id={id}` - Ver recomendaciones
- `POST   /api/ia/predicciones/{indicador_id}` - Predecir indicador
- `POST   /api/ia/generar/{tipo}` - Generar contenido (misión, visión, FODA, etc.)
- `POST   /api/ia/fases/{id}/guia` - Generar guía paso a paso

#### Dashboards
- `GET    /api/dashboard/ejecutivo?empresa_id={id}` - Dashboard ejecutivo
- `GET    /api/dashboard/procesos?empresa_id={id}` - Dashboard procesos
- `GET    /api/dashboard/colaborador` - Dashboard individual
- `GET    /api/reportes/gestion?empresa_id={id}&periodo={p}` - Reporte consolidado

## 4. Estrategia de Sincronización y Caché

### 4.1 Flujo Offline → Online (App Móvil)
1. Usuario trabaja offline en actividades asignadas
2. App almacena en SQLite local
3. Al reconectar, sincroniza vía API (cola de sync)
4. Manejo de conflictos: último en escribir gana + notificación

### 4.2 Caché de Consultas
- Tabla `sys_cache_consultas` almacena resultados frecuentes
- TTL configurable (default: 3600s)
- Invalidación automática al modificar datos relacionados
- Claves por módulo: `dashboard_{empresa_id}`, `indicadores_{plan_id}`, etc.

## 5. Seguridad

### 5.1 Autenticación
- JWT con HS256, expiración configurable (default: 8 horas)
- Blacklist de tokens en logout
- Rate limiting en endpoints de auth

### 5.2 Autorización (RBAC)
- 8 roles predefinidos: Super Admin a Cliente/Invitado
- Permisos granulares: ver, crear, editar, eliminar, exportar, importar, aprobar
- Filtrado automático de datos según rol

### 5.3 Encriptación
- AES-256-CBC para credenciales CRM
- bcrypt para contraseñas de usuario
- HTTPS requerido en producción

## 6. Automatización

### 6.1 Triggers MySQL
- `tr_actualizar_avance_actividad`: Actualiza % al completar mapa
- `tr_actualizar_avance_estrategia`: Promedio de actividades → estrategia
- `tr_actualizar_avance_objetivo`: Promedio de estrategias → objetivo
- `tr_calcular_semaforo_medicion`: Semáforo automático al registrar medición
- `tr_actualizar_tiempo_promedio_tarea`: Benchmark tiempos
- `tr_log_cambios_plan`: Auditoría de cambios de estado

### 6.2 Stored Procedures
- `sp_calcular_desempeno_usuario(user_id, periodo)`: Calcula 4 variantes individuales
- `sp_resumen_ejecutivo_plan(plan_id)`: Resumen gerencial completo

### 6.3 Ciclo Automático (Cron Job Recomendado)
```
0 2 * * * php /path/to/cron_sync.php  → Sincronización CRM + Minería
0 3 * * 1 php /path/to/cron_eval.php  → Evaluaciones semanales
0 4 1 * * php /path/to/cron_pred.php  → Predicciones mensuales
```

## 7. Modelo de Datos (Diagrama Simplificado)

```
sys_usuarios ──┐
sys_roles ─────┤
                ├── plan_planes_estrategicos ── plan_fases ── plan_objetivos
                │       │                                          │
plan_metodologias┘       │                              plan_estrategias
                         │                                    │
                plan_presupuestos                    plan_actividades
                                                      │
                                            plan_mapa_actividades ── sys_usuarios
                                                      │
                                            proc_mapeo_tiempos ── proc_tareas ── proc_procedimientos ── proc_procesos
                                                      │
                                            ind_mediciones ── ind_indicadores ── ind_categorias (4 variantes)
                                                      │
                                            ind_evaluaciones_desempeno ── sys_usuarios

crm_conexiones ── crm_mapeos_datos ──→ ind_mediciones (automático)
crm_mineria_datos ──→ ind_mediciones (automático)

doc_documentos ── doc_plantillas ── doc_normas_iso ── doc_sectores
doc_auditorias ── doc_normas_iso

ia_modelos ── ia_recomendaciones
           ── ia_predicciones
           ── ia_asistencias
```

## 8. Escalabilidad

- Particionamiento por empresa (schema multi-tenant opcional)
- Índices optimizados para consultas frecuentes
- Caché en capa de aplicación y base de datos
- Preparado para escalar a PostgreSQL (consultas estándar SQL)
