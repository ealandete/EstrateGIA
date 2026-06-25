# PENDIENTES DE DESARROLLO — EstrateGIA v2.0

> **Versión:** 1.0 | **Fecha:** 2026-06-11 | **Auditoría:** #001
>
> Archivo maestro de funcionalidades pendientes, mejoras y bugs.
> Cada entrada tiene: ID, estado, prioridad, fecha, descripción y ruta de verificación.

---

## PENDIENTES ACTIVOS

### P-001 — CSRF Protection en formularios
- **Estado:** Pendiente
- **Prioridad:** Crítica
- **Fecha creación:** 2026-06-11
- **Estándar:** 04_SEGURIDAD.md R5
- **Descripción:** Todos los formularios POST carecen de token CSRF. Agregar generación y validación de tokens en el layout base y en cada formulario.
- **Ruta verificación:** Cualquier formulario POST (crear objetivo, guardar indicador, etc.)
- **Archivos:** `templates/layout.php`, `src/Controllers/*.php`

### P-002 — Try/catch en controladores
- **Estado:** Pendiente
- **Prioridad:** Crítica
- **Fecha creación:** 2026-06-11
- **Estándar:** 02_BACKEND.md R12
- **Descripción:** Solo 5 de 25 controladores usan try/catch. Agregar manejo de excepciones con respuesta JSON en los 20 restantes.
- **Ruta verificación:** Provocar error en cualquier endpoint (ej: ID inválido)
- **Archivos:** `src/Controllers/*.php` (20 archivos)

### P-003 — Bootstrap y FontAwesome locales
- **Estado:** Pendiente
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Estándar:** 03_FRONTEND.md R7
- **Descripción:** Bootstrap CSS/JS y FontAwesome se cargan desde CDN. Descargar y servir localmente desde `/assets/`.
- **Ruta verificación:** Cargar la app sin internet, verificar que los estilos e iconos funcionan
- **Archivos:** `templates/layout.php` line 52-53, `assets/`

### P-004 — Exportación de datos multi-formato
- **Estado:** Pendiente
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Estándar:** 18_ESTANDAR_PROGRAMACION_UNIFICADO.md R4
- **Descripción:** Ninguna tabla permite exportar a CSV, XLSX, JSON o PDF. Implementar botón de exportación en todas las tablas de datos.
- **Ruta verificación:** Tabla de objetivos, indicadores, estrategias en detail.php
- **Archivos:** `templates/planeacion/detail.php`, nuevo `lib/ExportManager.php`

### P-005 — Rate limiting en /generar
- **Estado:** Pendiente
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Estándar:** 04_SEGURIDAD.md R8
- **Descripción:** El endpoint /generar no tiene límite de peticiones. Implementar rate limiting por usuario/IP.
- **Ruta verificación:** Llamar /generar repetidamente, verificar bloqueo tras N intentos
- **Archivos:** `src/Controllers/GeneradorController.php`, `src/Router.php`

### P-006 — Niveles de detalle N1/N2/N3
- **Estado:** Pendiente
- **Prioridad:** Media
- **Fecha creación:** 2026-06-11
- **Estándar:** 18_ESTANDAR_PROGRAMACION_UNIFICADO.md R2
- **Descripción:** Las tablas no tienen los 3 niveles de visualización (resumido, detallado, auditoría). Implementar botones .nivel-detalle.
- **Ruta verificación:** Cualquier tabla de datos (objetivos, indicadores, estrategias)
- **Archivos:** Templates que muestran tablas, `assets/css/app.css`

### P-007 — Control de acceso por roles
- **Estado:** Pendiente
- **Prioridad:** Media
- **Fecha creación:** 2026-06-11
- **Estándar:** 08_PERMISOS.md R3
- **Descripción:** No hay diferenciación de vistas por rol. Admin, planeador y operador ven lo mismo.
- **Ruta verificación:** Login con diferentes roles, verificar permisos de edición/eliminación
- **Archivos:** `src/Controllers/*.php`, `templates/layout.php`

### P-008 — Builder Design Thinking (5 fases)
- **Estado:** Completado
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Fecha completado:** 2026-06-11
- **Descripción:** Las 5 fases usaban generic builder. Creado `design_builder.php` con canvas adaptativo por fase.
- **Ruta verificación:** `/workbench/{plan_id}/{fase_id}` para fases de Design Thinking
- **Archivos:** `templates/tools/design_builder.php`

### P-009 — Builder Hoshin Kanri (3 fases)
- **Estado:** Completado
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Fecha completado:** 2026-06-11
- **Descripción:** Catchball, Control Diario y Revisión usaban generic. Creado `hoshin_builder.php`.
- **Ruta verificación:** `/workbench/{plan_id}/{fase_id}` para fases de Hoshin Kanri
- **Archivos:** `templates/tools/hoshin_builder.php`

### P-010 — OKR Check-ins y Scoring
- **Estado:** Completado
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Fecha completado:** 2026-06-11
- **Descripción:** Agregados check-in tracker semanal y scoring final con retrospectiva al OKR builder.
- **Ruta verificación:** `/workbench/{plan_id}/{fase_id}` para fases de OKR ejecución y cierre
- **Archivos:** `templates/tools/okr_builder.php`

### P-011 — Reporte Ejecutivo completo
- **Estado:** Completado
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Fecha completado:** 2026-06-11
- **Descripción:** Reescrito reporte.php con PESTEL, Misión/Visión/Valores, despliegue BSC, indicadores por objetivo, iniciativas con seguimiento.
- **Ruta verificación:** `/planeacion/{plan_id}/reporte`
- **Archivos:** `templates/planeacion/reporte.php`

### P-012 — Modal de Evaluación con sugerencias IA
- **Estado:** Completado
- **Prioridad:** Alta
- **Fecha creación:** 2026-06-11
- **Fecha completado:** 2026-06-11
- **Descripción:** Modal con checkbox, filtros por perspectiva, deduplicación por nombre+objetivo_id, pool de 12 KPIs por perspectiva.
- **Ruta verificación:** Evaluación y Ajuste → Sugerir mejoras
- **Archivos:** `templates/tools/evaluacion_builder.php`

---

## COMPLETADOS (Historial)

Ver subcarpeta `historial/` para versiones anteriores.

---

**Resumen:** 12 pendientes registrados | 5 completados | 7 activos
