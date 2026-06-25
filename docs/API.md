# EstrateGIA API REST v2.1

Documentación completa de la API REST del Sistema Integrado de Gestión EstrateGIA.

## 1. Autenticación

Todas las rutas (excepto `/api/health`, `/api/auth/login` y `/api/demo/crear`) requieren autenticación JWT Bearer.

### Obtener Token

```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@estrategia.com",
  "password": "admin123"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "usuario_id": 1,
    "usuario_nombre": "Administrador",
    "token": "eyJhbGciOiJIUzI1NiIs..."
  },
  "message": "Inicio de sesión exitoso",
  "timestamp": "2026-06-25T10:00:00-05:00"
}
```

**Error de credenciales (401):**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_FAILED",
    "message": "Credenciales inválidas"
  }
}
```

### Usar el Token

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...
```

El token expira a las 8 horas (configurable en `jwt_expire`). Tras 5 intentos fallidos de login, el usuario queda bloqueado por 15 minutos.

### CSRF en POST

Los endpoints POST que no son API pura requieren token CSRF:

```
X-CSRF-Token: <session_csrf_token>
```

## 2. Formato de Respuesta

### Éxito

```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa",
  "timestamp": "2026-06-25T10:30:00-05:00"
}
```

### Error

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

## 3. Códigos de Error

| HTTP | Código | Significado |
|------|--------|-------------|
| 400 | `VALIDATION_ERROR` | Datos de entrada inválidos |
| 400 | `MISSING_FIELD` | Campos requeridos faltantes |
| 401 | `AUTH_FAILED` | Credenciales incorrectas |
| 401 | `TOKEN_EXPIRED` | Token JWT vencido |
| 401 | `TOKEN_INVALID` | Token JWT inválido |
| 401 | `LOGIN_BLOCKED` | Usuario bloqueado por intentos fallidos |
| 403 | `FORBIDDEN` | Sin permisos para la acción |
| 403 | `CSRF_INVALID` | Token CSRF inválido |
| 404 | `NOT_FOUND` | Recurso no encontrado |
| 429 | `RATE_LIMITED` | Demasiadas peticiones |
| 500 | `SERVER_ERROR` | Error interno del servidor |

## 4. Rate Limiting

- **100 peticiones** por minuto por IP
- La ventana se reinicia cada 60 segundos
- Al exceder el límite se retorna HTTP 429

```
HTTP/1.1 429 Too Many Requests
X-RateLimit-Remaining: 0
Content-Type: application/json

{
  "success": false,
  "error": "Demasiadas peticiones. Espera un minuto."
}
```

## 5. Endpoints

### 5.1 Sistema

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/health` | Health check (sin auth) |
| `POST` | `/api/error/report` | Reportar error JS del frontend |
| `POST` | `/api/demo/crear` | Crear empresa demo (trial 15 días) |
| `GET` | `/api/backup/log?limit=10` | Historial de backups |
| `POST` | `/api/backup/log` | Registrar ejecución de backup |
| `GET` | `/api/backup/ultimo` | Último backup registrado |
| `POST` | `/api/backup/ejecutar` | Ejecutar backup manual (SUPER_ADMIN) |

**Health Check:**
```bash
GET /api/health

# Respuesta (200):
{
  "status": "ok",
  "app": "EstrateGIA",
  "version": "2.1.0",
  "db_tables": 52,
  "timestamp": "2026-06-25T10:00:00-05:00"
}
```

### 5.2 Autenticación

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/auth/login` | Login (JWT) |
| `POST` | `/api/auth/logout` | Logout |
| `GET` | `/api/auth/me` | Perfil del usuario autenticado |

### 5.3 Usuarios

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/usuarios` | Listar usuarios |
| `POST` | `/api/usuarios` | Crear usuario |
| `GET` | `/api/roles` | Listar roles |
| `GET` | `/api/permisos` | Permisos del usuario actual |

### 5.4 Planeación

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/metodologias` | Metodologías disponibles |
| `GET` | `/api/empresas` | Listar empresas |
| `POST` | `/api/empresas` | Crear empresa |
| `GET` | `/api/planes?empresa_id={id}` | Listar planes |
| `POST` | `/api/planes` | Crear plan (genera 7 fases) |
| `GET` | `/api/planes/{id}` | Detalle de plan |
| `GET` | `/api/planes/{id}/arbol` | Árbol fases→objetivos→estrategias→actividades |
| `GET` | `/api/planes/{id}/progreso` | Resumen ejecutivo |
| `GET` | `/api/fases/{id}/paso-a-paso` | Guía de fase |
| `POST` | `/api/analisis` | Crear análisis (FODA/PESTEL) |
| `GET` | `/api/objetivos?plan_id={id}` | Objetivos del plan |
| `POST` | `/api/objetivos` | Crear objetivo |
| `POST` | `/api/estrategias` | Crear estrategia |
| `POST` | `/api/actividades` | Crear actividad |
| `POST` | `/api/mapa-actividades` | Asignar usuario a actividad |
| `PUT` | `/api/mapa-actividades/{id}/estado` | Actualizar estado |

**Crear Plan:**
```bash
POST /api/planes
Authorization: Bearer <token>

{
  "plan_empresa_id": 2,
  "plan_metodologia_id": 1,
  "plan_nombre": "Plan Estratégico 2026",
  "plan_periodo": "2026",
  "plan_descripcion": "Plan de transformación digital"
}
```

### 5.5 Procesos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/macroprocesos?empresa_id={id}` | Macroprocesos |
| `GET` | `/api/procesos?empresa_id={id}` | Procesos |
| `POST` | `/api/procesos` | Crear proceso |
| `GET` | `/api/procesos/{id}` | Estado completo |
| `POST` | `/api/procedimientos` | Crear procedimiento |
| `POST` | `/api/tareas` | Crear tarea |
| `POST` | `/api/tiempos/iniciar` | Iniciar registro de tiempo |
| `PUT` | `/api/tiempos/{id}/finalizar` | Finalizar registro |

### 5.6 Indicadores

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/categorias` | Categorías (4 variantes) |
| `POST` | `/api/indicadores` | Crear indicador |
| `GET` | `/api/indicadores?plan_id={id}` | Indicadores del plan |
| `GET` | `/api/indicadores/sst?empresa_id={id}` | Indicadores SST |
| `GET` | `/api/indicadores/ambiental?empresa_id={id}` | Indicadores ambientales |
| `POST` | `/api/metas` | Crear meta |
| `POST` | `/api/mediciones` | Registrar medición |
| `GET` | `/api/mediciones?indicador_id={id}` | Historial de mediciones |
| `GET` | `/api/variantes/resumen?plan_id={id}` | Resumen 4 variantes |
| `GET` | `/api/variantes/semaforo?plan_id={id}` | Semáforo |
| `GET` | `/api/variantes/tendencia?plan_id={id}` | Tendencia 6 períodos |
| `POST` | `/api/evaluaciones/calcular/{usuario_id}` | Calcular evaluación |
| `GET` | `/api/evaluaciones/{usuario_id}` | Historial evaluaciones |
| `GET` | `/api/ranking?periodo={periodo}` | Ranking colaboradores |

**Registrar Medición:**
```bash
POST /api/mediciones
Authorization: Bearer <token>

{
  "medicion_indicador_id": 1,
  "medicion_meta_id": 1,
  "medicion_valor": 85.5,
  "medicion_fecha": "2026-05-15"
}
```

### 5.7 Calidad

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/nc?empresa_id={id}` | Listar No Conformidades |
| `POST` | `/api/nc` | Registrar NC |

### 5.8 SST (ISO 45001)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/peligros?empresa_id={id}` | Peligros identificados |
| `GET` | `/api/peligros/{id}` | Detalle de peligro |
| `GET` | `/api/incidentes?empresa_id={id}&anio=2026` | Incidentes |

### 5.9 Ambiental (ISO 14001)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/registros/ambiental?empresa_id={id}` | Registros ambientales |
| `GET` | `/ambiental/api/huella?empresa_id={id}&anio=2026` | Huella de carbono |
| `GET` | `/ambiental/api/indicadores-carbono?empresa_id={id}` | Indicadores de carbono |
| `GET` | `/ambiental/api/dashboard?empresa_id={id}` | Dashboard ambiental |

### 5.10 IA

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/ia/asistencia` | Consulta al asistente IA |
| `GET` | `/api/ia/asistencias/historial` | Historial de consultas |
| `POST` | `/api/ia/recomendaciones` | Generar recomendación |
| `GET` | `/api/ia/recomendaciones?contexto=plan&id={id}` | Ver recomendaciones |
| `POST` | `/api/ia/predicciones/{indicador_id}` | Predecir indicador |
| `POST` | `/api/ia/generar/{tipo}` | Generar contenido (mision, vision, foda, bsc) |
| `POST` | `/api/ia/fases/{id}/guia` | Guía paso a paso |

### 5.11 Dashboard

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/dashboard/ejecutivo?empresa_id={id}` | Dashboard ejecutivo |
| `GET` | `/api/dashboard/procesos?empresa_id={id}` | Dashboard procesos |
| `GET` | `/api/dashboard/colaborador` | Dashboard individual |
| `GET` | `/api/reportes/gestion?empresa_id={id}&periodo={periodo}` | Reporte consolidado |
| `GET` | `/api/powerbi?plan_id={id}&format=json` | Export para Power BI |
| `GET` | `/sse/dashboard` | Dashboard en tiempo real (SSE) |

### 5.12 Integraciones

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/conexiones` | Crear conexión externa |
| `POST` | `/api/conexiones/{id}/test` | Probar conexión |
| `POST` | `/api/mapeos` | Crear mapeo |
| `POST` | `/api/mapeos/{id}/sincronizar` | Sincronizar |
| `POST` | `/api/crm/sincronizar` | Sincronización CRM general |

### 5.13 Documentos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/sectores` | Sectores disponibles |
| `GET` | `/api/normas-iso?sector_id={id}` | Normas por sector |
| `GET` | `/api/plantillas?norma_id={id}` | Plantillas |
| `POST` | `/api/documentos` | Crear documento |
| `GET` | `/api/documentos?empresa_id={id}` | Listar documentos |
| `PUT` | `/api/documentos/{id}/aprobar` | Aprobar |
| `PUT` | `/api/documentos/{id}/publicar` | Publicar |
| `POST` | `/api/documentos/{id}/version` | Nueva versión |
| `POST` | `/api/auditorias` | Crear auditoría |

### 5.14 Alertas

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/alertas/vencimientos?empresa_id={id}` | Alertas proactivas de vencimientos |

### 5.15 Soporte

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/tickets?estado=ABIERTO&page=1&limit=20` | Tickets paginados |
| `GET` | `/api/tickets/resumen` | Resumen de tickets |

### 5.16 Proveedores

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/proveedores?empresa_id={id}` | Lista de proveedores |

## 6. Paginación

Varios endpoints soportan paginación:

```bash
GET /api/tickets?page=1&limit=20
Authorization: Bearer <token>

# Respuesta:
{
  "data": [ ... ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 156,
    "total_pages": 8,
    "has_more": true
  }
}
```
