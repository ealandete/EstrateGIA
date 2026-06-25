# Manual del Programador — EstrateGIA v2.0

> **Versión:** 1.0 | **Fecha:** 2026-06-11
>
> Documentación técnica para desarrolladores

---

## 1. Arquitectura

- **Stack**: PHP 8.5 vanilla + MySQL/MariaDB + Bootstrap 5 + Nginx
- **Patrón**: MVC simple con Router frontal, Controladores, Managers (modelos), Templates (vistas)
- **Entrada**: `public/index.php` → Router → Controller → Manager/Template
- **Sin frameworks**: `require_once` explícito, sin Composer

### 1.1 Estructura de archivos

```
workspace/
  public/
    index.php          # Front controller (604 líneas, 163 rutas)
    assets/            # CSS, JS, webfonts (Bootstrap 5, FontAwesome 6, Chart.js)
  src/
    Controllers/       # 25 controladores (1 por módulo)
    Router.php         # Router con 163 rutas, CSRF, rate limiting
    N1SupportEngine.php
  lib/
    PlanManager.php    # Objetivos, estrategias, fases, árbol del plan
    IndicatorManager.php # Indicadores KPIs, mediciones, 4 variantes
    AIManager.php      # Generación IA (local + OpenAI/Claude/Gemini)
    EstrateGiaCore.php # Singleton DB, CRUD, validación
    DocManager.php     # Documentos ISO, normas, plantillas
    ExportManager.php  # Exportación CSV/JSON
    SimpleXLSX.php     # Generador XLSX nativo
    BaseHSEManager.php # Manager base para HSE
  templates/
    planeacion/        # Vistas de plan (detail, reporte)
    tools/             # Builders (bsc, okr, scenarios, foda, pestel, vision, generic, design, hoshin, evaluacion, indicadores, iniciativas)
    indicadores/       # Módulo de indicadores independiente
    hse/               # Vistas Ambiental y SST
    calidad/           # Vistas de calidad y acreditación
    layout.php         # Layout principal (253 líneas)
```

---

## 2. Base de Datos

### 2.1 Tablas principales (91 tablas, 123 FKs)

| Prefijo | Módulo | Tablas |
|---------|--------|--------|
| `plan_` | Planeación | 10 (planes, objetivos, estrategias, actividades, fases, análisis) |
| `ind_` | Indicadores | 5 (indicadores, mediciones, metas, categorías) |
| `cal_` | Calidad | 6 (estándares, evidencias, PAMEC, riesgos, NC) |
| `amb_` | Ambiental | 8 (aspectos, auditorías, programas, registros) |
| `sst_` | SST | 14 (incidentes, peligros, ausentismo, capacitaciones) |
| `sys_` | Sistema | 10 (usuarios, roles, permisos, auditoría) |
| `doc_` | Documentos | 5 (documentos, normas ISO, plantillas, sectores) |
| `proc_` | Procesos | 6 (macroprocesos, procesos, procedimientos) |
| `ia_` | IA | 4 (modelos, recomendaciones, prompts, uso) |
| `prov_` | Proveedores | 3 |
| `crm_` | CRM | 3 |

---

## 3. Router y Seguridad

### 3.1 Registro de rutas
```php
$router->get('/planeacion', function () { ... });
$router->post('/tools/save-objetivo', function () { ... });
```

### 3.2 Middleware automático
- **CSRF**: token en sesión, inyectado en todos los fetch POST
- **Rate limiting**: 10 req/min por IP en /tools/* y /generar
- **Error handler**: try/catch global con JSON en Router::dispatch
- **Auth**: `Auth::guard()` en cada constructor de controller

---

## 4. Patrones de Desarrollo

### 4.1 Controller típico
```php
class MiController {
    public function __construct() { Auth::guard(); }
    public function index(): void {
        $data = $this->manager->getData($planId);
        ob_start(); require 'template.php';
        $content = ob_get_clean(); require 'layout.php';
    }
}
```

### 4.2 Manager (Modelo)
```php
class PlanManager {
    public function getObjetivos(int $planId): array {
        return $this->core->fetchAll('SELECT ... WHERE objetivo_plan_id = :pid', ['pid'=>$planId]);
    }
}
```

### 4.3 Template
- Variables disponibles: las definidas en el controller + `$this->pm`
- Siempre usar `htmlspecialchars()` para output
- Bootstrap 5: `card-box`, `stat-card`, `modal`, `table`

---

## 5. Generadores IA

### 5.1 AIManager
- `generarContenido($tipo, $contexto)` → consulta al proveedor IA
- Si el modelo es `simulado:true`, usa generadores locales
- 13 tipos de contenido: foda, pestel, bsc, okr, indicadores, iniciativas, evaluacion, proceso, etc.
- Proveedores: OpenAI, Claude, Gemini (configurables)

### 5.2 Flujo de generación
1. Builder JS llama `/generar` con tipo y contexto
2. GeneradorController instancia AIManager
3. Si simulado → generador local con templates
4. Si real → API call al proveedor configurado
5. Resultado se guarda automáticamente en BD

---

## 6. Despliegue

### 6.1 Local
```bash
sudo systemctl restart php8.5-fpm  # Limpiar opcache
```

### 6.2 Staging
```bash
bash deploy-staging.sh  # Rsync + fix DB creds + restart PHP
```

### 6.3 Requisitos
- PHP 8.5 con extensiones: PDO MySQL, ZipArchive, SimpleXML, mbstring
- Nginx con PHP-FPM
- MariaDB/MySQL 8+

---

## 7. Políticas de ContextoGeneral

Todo desarrollo sigue los 19 documentos de política en `/home/emilio/ContextoGeneral/`:
- 00_MAESTRO.md — Estructura general
- 01_BD.md — Base de datos
- 02_BACKEND.md — Backend PHP
- 03_FRONTEND.md — Frontend/UI
- 04_SEGURIDAD.md — Seguridad
- 18_ESTANDAR_PROGRAMACION_UNIFICADO.md — Reglamento unificado

---

**EstrateGIA v2.0** — Documento generado el 11 de junio de 2026
