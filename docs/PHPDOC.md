# EstrateGIA - Referencia PHPDoc

## 1. Arquitectura General

```
public/index.php       ← Front Controller (router único)
    │
    ├── src/Router.php  ← Enrutador con regex, rate limiting, CSRF
    ├── src/Auth.php    ← Autenticación por sesión + guard
    └── src/Controllers/ ← 29 controladores (uno por módulo)
            │
            └── lib/    ← Capa de negocio (17+ managers)
                    │
                    └── lib/EstrateGiaCore.php  ← Singleton PDO + utilidades
```

## 2. EstrateGiaCore (Singleton)

**Archivo**: `lib/EstrateGiaCore.php`
**Patrón**: Singleton (no se puede instanciar directamente)

```php
$core = EstrateGiaCore::getInstance();
```

### Métodos CRUD

| Método | Retorno | Uso |
|--------|---------|-----|
| `insert(string $table, array $data): int` | `int` (ID insertado) | INSERT genérico con prepared statements |
| `update(string $table, array $data, string $where, array $params): int` | `int` (filas afectadas) | UPDATE con prefijo `set_` en bindings |
| `delete(string $table, string $where, array $params): int` | `int` (filas afectadas) | DELETE con placeholders `:key` |
| `fetchOne(string $sql, array $params): ?array` | `?array` | SELECT de una sola fila |
| `fetchAll(string $sql, array $params): array` | `array` | SELECT de múltiples filas |
| `fetchColumn(string $sql, array $params): mixed` | `mixed` | SELECT de un solo valor escalar |
| `execute(string $sql, array $params): int` | `int` (rowCount) | Ejecución general |

### Utilidades

| Método | Descripción |
|--------|-------------|
| `paginate($sql, $params, $page, $perPage)` | Paginación con `data`, `pagination` |
| `apiResponse($success, $data, $message, $httpCode)` | Respuesta API estandarizada |
| `apiError($code, $message, $details, $httpCode)` | Respuesta de error API |
| `sanitizeInput($data)` | Sanitización recursiva con `htmlspecialchars` |
| `validateRequired($data, $fields)` | Validación de campos requeridos |
| `encryptData($data)` | AES-256-CBC con IV aleatorio |
| `decryptData($data)` | Desencriptado AES-256-CBC |
| `audit($accion, $tabla, ...)` | Registro de auditoría con snapshots JSON |
| `logAction($userId, ...)` | Logging en `sys_logs_sistema` |
| `sendNotification($userId, ...)` | Notificaciones en `sys_notificaciones` |

### Autenticación (delegada a AuthService)

```php
$core->authenticateUser('email', 'password');  // → ?array con token JWT
$core->validateJWT($token);                    // → ?array con claims
$core->userHasPermission($userId, 'modulo', 'accion');  // → bool
$core->canAccess('planeacion');                // → bool (rol actual)
$core->requires2FA($user);                     // → bool
$core->verify2FACode($user, $code);            // → bool
```

### Configuración

```php
$core->getConfig();                          // array completo
$core->getConfigValue('jwt_secret');         // valor específico
$core->getConfigValue('app_version');         // '2.1.0'
$core->getPDO();                             // instancia PDO directa
```

## 3. SafeQuery Trait

**Archivo**: `lib/SafeQuery.php`
**Uso**: `use \SafeQuery;` en cualquier clase

### Métodos Principales

```php
class MiController {
    use \SafeQuery;
    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function ejemplo(): void {
        // Escalar
        $total = (int)$this->safe("SELECT COUNT(*) FROM ind_indicadores WHERE indicador_plan_id=?", [2]);

        // Una fila
        $plan = $this->safeOne("SELECT * FROM plan_planes_estrategicos WHERE plan_id=?", [2]);

        // Múltiples filas
        $planes = $this->safeAll("SELECT * FROM plan_planes_estrategicos WHERE plan_activo=1");

        // INSERT/UPDATE/DELETE
        $afectadas = $this->safeExec("UPDATE plan_fases SET fase_estado=? WHERE fase_id=?", ['completada', 5]);

        // Insert con retorno ID
        $id = $this->safeInsert('plan_fases', ['fase_nombre' => 'Análisis', 'fase_plan_id' => 2]);

        // Update
        $this->safeUpdate('plan_fases', ['fase_estado' => 'completada'], 'fase_id=?', [5]);

        // Delete
        $this->safeDelete('plan_fases', 'fase_id=?', [5]);

        // Count
        $count = $this->safeCount('ind_indicadores', 'indicador_plan_id=?', [2]);

        // Exists
        if ($this->safeExists('sys_usuarios', 'usuario_email=?', ['admin@test.com'])) { ... }

        // Paginación
        $resultado = $this->safePaginate(
            "SELECT * FROM ind_mediciones WHERE medicion_indicador_id=? ORDER BY medicion_fecha DESC",
            [1], 1, 20
        );
        // → ['data' => rows, 'total' => int, 'page' => 1, 'pages' => 5, 'perPage' => 20]
    }
}
```

### Validación de Tablas

SafeQuery valida nombres de tabla contra inyección con regex `/^[a-zA-Z_][a-zA-Z0-9_]*$/`.

### Placeholders

SafeQuery usa `?` (posicionales), no `:named`. Esto lo diferencia de `EstrateGiaCore` que usa `:named`.

## 4. Jerarquía de Clases

### Capa de Negocio (lib/)

| Clase | Tablas Principales | Responsabilidad |
|-------|-------------------|-----------------|
| `EstrateGiaCore` | — | Singleton, PDO, CRUD genérico, utilidades |
| `AuthService` | `sys_usuarios`, `sys_roles`, `login_attempts` | Autenticación, JWT, bloqueo, 2FA |
| `PlanManager` | `plan_*` | Planes, fases, objetivos, estrategias, actividades, análisis |
| `ProcessManager` | `proc_*` | Macroprocesos, procesos, procedimientos, tareas |
| `BaseHSEManager` | — | Base abstracta para HSE |
| `SSTManager` | `sst_*` | Peligros, incidentes, indicadores SST |
| `AmbientalManager` | `amb_*` | Aspectos, huella de carbono, indicadores ambientales |
| `IndicatorManager` | `ind_*` | Indicadores, mediciones, metas, evaluaciones, ranking |
| `DocManager` | `doc_*` | Documentos ISO, plantillas, sectores, auditorías |
| `CRMManager` | `crm_*` | Conexiones externas, mapeos, sincronización |
| `AIManager` | `ia_*` | Asistente IA, recomendaciones, predicciones, generación |
| `FinancialManager` | `plan_presupuestos` | Presupuestos y finanzas |
| `ExportManager` | — | Exportaciones CSV/PDF |
| `CacheService` | `sys_cache_consultas` | Caché de consultas |
| `RateLimiter` | (sesión) | Rate limiting por IP |
| `TwoFactorAuth` | — | TOTP para 2FA |
| `SimpleXLSX` | — | Lectura de archivos Excel |
| `WebhookService` | — | Webhooks salientes |
| `SystemIntegrator` | — | Orquestador de integraciones |
| `ProveedoresManager` | `prov_*` | Gestión de proveedores |
| `Logger` | `error_log` | Logging de errores |
| `N1SupportEngine` | `soporte_*` | Motor de soporte N1 |

### Capa de Controladores (src/Controllers/)

29 controladores. Todos protegen con `Auth::guard()` excepto `SetupController`.

| Controlador | Módulo |
|-------------|--------|
| `PlaneacionController` | Planeación estratégica |
| `ProcesosController` | Gestión de procesos |
| `IndicadoresController` | Indicadores KPIs |
| `MedicionController` | Registro de mediciones (CSV) |
| `EvaluacionController` | Evaluación de desempeño |
| `FaseController` | Fases de planeación |
| `SIGController` | Dashboard integrado |
| `CalendarioController` | Calendario de tareas |
| `SSTController` | Seguridad y salud |
| `AmbientalController` | Gestión ambiental |
| `CalidadController` | Calidad (NC, acreditación) |
| `NCController` | No conformidades |
| `DocumentosController` | Documentación ISO |
| `DocsController` | Documentación pública (GET-only) |
| `ProveedoresController` | Proveedores |
| `CRMController` | Integración CRM |
| `IntegracionesController` | Sincronizaciones |
| `GeneradorController` | Generación de contenido |
| `IAController` | Asistente IA |
| `DashboardController` | Dashboards |
| `AdminController` | Administración usuarios/roles |
| `ConfigController` | Configuración empresas |
| `SetupController` | Wizard de instalación |
| `LicenciasController` | Licencias y facturación |
| `SoporteController` | Tickets de soporte |
| `WorkbenchController` | Workbench de trabajo |
| `ExtrasController` | Funciones auxiliares |
| `PhvaController` | Ciclo PHVA |
| `AcreditacionController` | Acreditación en salud |

## 5. Patrones de Uso Comunes

### CRUD Estándar en un Manager

```php
class MiManager {
    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function crear(array $data): int {
        $required = ['nombre', 'empresa_id'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new \InvalidArgumentException(json_encode($errors));

        $id = $this->core->insert('tabla', $data);
        $this->core->logAction($data['usuario_id'] ?? null, 'crear', 'modulo', 'entidad', $id);
        return $id;
    }

    public function obtener(int $id): ?array {
        return $this->core->fetchOne('SELECT * FROM tabla WHERE id=:id AND activo=1', ['id' => $id]);
    }

    public function listar(?int $filtro = null): array {
        $sql = 'SELECT * FROM tabla WHERE activo=1';
        $params = [];
        if ($filtro) { $sql .= ' AND campo=:f'; $params['f'] = $filtro; }
        return $this->core->fetchAll($sql, $params);
    }

    public function actualizar(int $id, array $data): bool {
        $ok = $this->core->update('tabla', $data, 'id=:id', ['id' => $id]) > 0;
        $this->core->audit('actualizar', 'tabla', $id, null, $data);
        return $ok;
    }

    public function eliminar(int $id): bool {
        return $this->core->update('tabla', ['activo' => 0], 'id=:id', ['id' => $id]) > 0;
    }
}
```

### Validación en Controlador

```php
public function crear(): void {
    Auth::guard();
    $data = $this->core->sanitizeInput($_POST);
    $errors = $this->core->validateRequired($data, ['nombre', 'email']);
    if (!empty($errors)) {
        header('Location: /form?error=' . urlencode(implode(', ', $errors)));
        exit;
    }
    // ... procesar
}
```

### Respuesta API JSON

```php
// Éxito
echo json_encode($core->apiResponse(true, $datos, 'Creado correctamente', 201));

// Error
echo json_encode($core->apiError('VALIDATION_ERROR', 'Campo requerido', ['nombre' => 'obligatorio'], 400));
```

## 6. Ciclo de Vida de una Petición

```
1. public/index.php → require Bootstrap (EstrateGiaCore, sesión)
2. Router::dispatch($method, $uri)
3. RateLimiter::check() → 429 si excede límite
4. CSRF validation en POST API
5. Handler del controlador → Auth::guard()
6. Controlador → Manager → EstrateGiaCore::fetch*/insert/update/delete
7. Manager → EstrateGiaCore::audit() / logAction()
8. Respuesta (HTML via template o JSON)
```
