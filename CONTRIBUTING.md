# Guía de Contribución - EstrateGIA

## 1. Estándares de Código

### PHP

- **Versión**: PHP 8.0+ (usar `declare(strict_types=1);` en controladores)
- **Indentación**: 4 espacios, sin tabs
- **Codificación**: UTF-8
- **Llaves**: K&R (llave de apertura en misma línea)
- **Nombres de clase**: PascalCase (`PlanManager`, `SSTController`)
- **Nombres de método**: camelCase (`getPlanes()`, `registrarMedicion()`)
- **Nombres de tabla/columna**: snake_case con prefijo (`plan_planes_estrategicos`, `medicion_indicador_id`)
- **Nombres de columna BD**: snake_case, con prefijo de entidad (`usuario_nombre`, `fase_estado`)
- **Visibilidad**: Siempre explícita (`public`, `protected`, `private`)
- **Tipado**: Usar `strict_types=1` y declarar tipos de retorno

### Ejemplo

```php
<?php
declare(strict_types=1);

class MiManager {
    private EstrateGiaCore $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function getPlanes(int $empresaId): array {
        return $this->core->fetchAll(
            'SELECT * FROM plan_planes_estrategicos WHERE plan_empresa_id = :eid',
            ['eid' => $empresaId]
        );
    }
}
```

### SQL

- Usar **prepared statements** siempre — nunca concatenar valores en queries
- Preferir `SafeQuery` trait con placeholders `?` en controladores
- Preferir `EstrateGiaCore` con placeholders `:named` en managers
- Nombrar índices con `idx_` + propósito (`idx_empresa`, `idx_created`)
- Foreign keys con nombres explícitos

## 2. Estructura de Archivos

```
workspace/
├── lib/               ← Managers y lógica de negocio
│   └── MiManager.php
├── src/
│   ├── Controllers/   ← Controladores (1 por módulo)
│   │   └── MiController.php
│   ├── Auth.php       ← Autenticación
│   └── Router.php     ← Enrutador
├── templates/         ← Vistas HTML/PHP
│   └── modulo/
│       └── index.php
├── tests/             ← Pruebas PHP
├── database/          ← Migraciones SQL
├── docs/              ← Documentación
└── public/            ← Raíz web
    └── index.php      ← Front Controller
```

### Dónde va cada cosa

| Tipo | Ubicación | Convención |
|------|-----------|------------|
| Nueva funcionalidad | `lib/` + `src/Controllers/` | Manager + Controller |
| Nueva página | `src/Controllers/` + `templates/` | Método en controller + vista |
| Nueva tabla | `database/` | Archivo SQL de migración |
| API endpoint | `public/index.php` + `src/Controllers/` | Ruta en router |
| Test | `tests/` | `*_test.php` o dentro de existentes |

## 3. Patrones

### Controlador

```php
<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class MiController {
    use \SafeQuery;
    private $core;

    public function __construct() {
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
    }

    public function index(): void {
        $pageTitle = 'Título';
        ob_start();
        require BASE_PATH . '/templates/mi_modulo/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }
}
```

### Manager

```php
<?php
require_once __DIR__ . '/EstrateGiaCore.php';

class MiManager {
    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function crear(array $data): int {
        $required = ['nombre', 'empresa_id'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new \InvalidArgumentException(json_encode($errors));

        $id = $this->core->insert('mi_tabla', $data);
        $this->core->logAction($data['usuario_id'] ?? null, 'crear', 'modulo', 'entidad', $id);
        return $id;
    }
}
```

### Ruta en Router

```php
// GET
$router->get('/mi-modulo', function () {
    (new MiController())->index();
});

// POST
$router->post('/mi-modulo/crear', function () {
    (new MiController())->crear();
});

// Con parámetros
$router->get('/mi-modulo/{id}', function ($id) {
    (new MiController())->ver((int)$id);
});
```

## 4. Git Workflow

### Ramas

```
main        ← Producción. Solo mergear desde develop vía PR.
develop     ← Desarrollo activo. Rama por defecto.
feature/*   ← Nuevas funcionalidades (feature/export-pdf)
fix/*       ← Correcciones (fix/login-timeout)
```

### Commits

Formato: `tipo(ámbito): mensaje breve`

Tipos: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`, `style`

```
feat(indicadores): añadir exportación CSV de mediciones
fix(sst): corregir cálculo de tasa de accidentes
docs(api): documentar endpoint de login
test(integracion): añadir prueba de flujo NC→CAPA
```

### Flujo de Trabajo

```bash
# 1. Actualizar rama
git checkout develop
git pull origin develop

# 2. Crear rama de feature
git checkout -b feature/mi-cambio

# 3. Hacer cambios y commit
git add .
git commit -m "feat(modulo): descripción del cambio"

# 4. Push y abrir PR
git push origin feature/mi-cambio
# Crear PR en GitHub hacia develop
```

## 5. Pruebas

### Ejecutar Tests

```bash
# Smoke test (rápido, sin BD)
bash tests/smoke_test.sh

# Unit tests
php tests/unit_test.php

# Integration tests (requiere BD)
php tests/integration_test.php

# Security tests
php tests/security_test.php
```

### Escribir Tests

Usar las funciones helpers definidas en los archivos de test:

```php
function ok($cond, string $msg = ''): void
function eq($a, $b, string $msg = ''): void
function gt($a, $b, string $msg = ''): void
function has(string $h, string $n, string $msg = ''): void
```

### Convención de Tests

```php
// Formato: T-XXX: Descripción
$GLOBALS['tests']['Integration']['T-XXX: Nombre descriptivo'] = function () use (...) {
    // Arrange
    // Act
    // Assert
    return [true, 'Mensaje opcional'];  // o [false, 'Razón']
};
```

### Requisitos para Aceptar PR

- [ ] Todos los tests existentes pasan
- [ ] Nuevos tests para la funcionalidad añadida
- [ ] Código sigue estándares (sin `var_dump`, `die`, `echo` en lib)
- [ ] Queries usan prepared statements (SafeQuery o EstrateGiaCore)
- [ ] Sin credenciales hardcodeadas
- [ ] Sin archivos de configuración personal modificados

## 6. Proceso de PR

1. **Crear rama** desde `develop`
2. **Desarrollar** con tests
3. **Ejecutar tests** localmente: `php tests/integration_test.php`
4. **Push** y crear PR en GitHub
5. **Describir** el cambio: qué, por qué, cómo probarlo
6. **Revisión**: al menos 1 aprobación requerida
7. **Merge** a `develop` (squash merge preferido)

### Template de PR

```markdown
## Descripción
[Qué cambia y por qué]

## Tipo
- [ ] feat (nueva funcionalidad)
- [ ] fix (corrección)
- [ ] docs (documentación)
- [ ] test (pruebas)
- [ ] refactor

## Cómo probar
1. [Paso 1]
2. [Paso 2]

## Checklist
- [ ] Tests ejecutados y pasando
- [ ] Código sin credenciales hardcodeadas
- [ ] Documentación actualizada si aplica
```

## 7. No Hacer

- No usar `SELECT *` en queries nuevas — listar columnas
- No usar `md5()` o `sha1()` para contraseñas — solo `password_hash()` con bcrypt
- No concatenar strings en queries SQL — siempre prepared statements
- No hacer `var_dump()` o `print_r()` en producción
- No commitear `composer.lock`, `.env`, ni archivos de configuración personal
- No modificar `lib/EstrateGiaCore.php` sin revisión
- No incluir credenciales reales en tests o ejemplos de código

## 8. Seguridad

- Toda entrada de usuario debe pasar por `sanitizeInput()` o prepared statements
- Los tokens CSRF son obligatorios en formularios POST
- Las contraseñas usan bcrypt (`PASSWORD_BCRYPT`)
- JWT con HS256, expiración configurable
- Rate limiting global: 100 req/min por IP
- Auditoría obligatoria en operaciones CRUD: `$core->audit()` o `$core->logAction()`
- RBAC con 8 roles predefinidos y permisos granulares
