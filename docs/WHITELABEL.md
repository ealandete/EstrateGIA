# EstrateGIA - Manual de Marca Blanca

Personalice la plataforma con la identidad de su organización: logo, colores, nombre y más.

## 1. Configuración Rápida

Acceda a **Configuración > Empresas** y edite los campos de personalización:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| Nombre Corto | Nombre abreviado visible en header y menú | `MiEmpresa` |
| Color Primario | Color principal de la interfaz | `#1a73e8` |
| Logo URL | Ruta de la imagen del logo | `/uploads/logo_miempresa.png` |
| Formato Fecha | Formato regional para fechas | `d/m/Y` |
| Moneda | Moneda por defecto | `COP` |
| Tipo de Empresa | Categoría (`general`, `salud`, `inmobiliario`, `logistica`) | `salud` |

## 2. Tabla de Configuraciones

El sistema usa la tabla `sys_configuraciones` para almacenar valores personalizados por empresa:

```sql
-- Estructura
CREATE TABLE sys_configuraciones (
    config_clave VARCHAR(64) PRIMARY KEY,
    config_valor TEXT,
    config_descripcion TEXT
);
```

### Claves Disponibles

| Clave | Tipo | Por Defecto | Descripción |
|-------|------|-------------|-------------|
| `empresa_color_primario` | Color HEX | `#1a73e8` | Color primario de botones, enlaces, header |
| `empresa_color_secundario` | Color HEX | `#34a853` | Color de acentos y elementos secundarios |
| `empresa_nombre_corto` | String | (vacío) | Nombre abreviado en header |
| `empresa_formato_fecha` | String | `d/m/Y` | Formato PHP para fechas |
| `empresa_moneda` | String | `COP` | Código ISO de moneda |
| `empresa_logo_url` | String | (vacío) | Ruta relativa de imagen de logo |
| `empresa_favicon_url` | String | (vacío) | Ruta del favicon |
| `empresa_pie_pagina` | String | `EstrateGIA` | Texto del pie de página |
| `empresa_color_bg` | Color HEX | `#ffffff` | Color de fondo general |
| `empresa_fuente_primaria` | String | `system-ui` | Familia tipográfica CSS |

### Consultar y Modificar

```php
$core = EstrateGiaCore::getInstance();

// Leer
$color = $core->fetchColumn(
    "SELECT config_valor FROM sys_configuraciones WHERE config_clave = ?",
    ['empresa_color_primario']
);

// Escribir
$core->execute(
    "INSERT INTO sys_configuraciones (config_clave, config_valor, config_descripcion)
     VALUES (?, ?, '') ON DUPLICATE KEY UPDATE config_valor = ?",
    ['empresa_color_primario', '#ff6600', '#ff6600']
);
```

## 3. Personalización de Logo

### 3.1 Requisitos de Imagen

- **Formato**: PNG (recomendado con transparencia), SVG, o JPG
- **Dimensiones**: Ancho máximo 250px, altura máxima 60px
- **Tamaño**: < 500 KB
- **Ubicación**: `uploads/` o URL externa HTTPS

### 3.2 Subida de Logo

```bash
# Vía formulario en Configuración > Empresas, o directamente:
cp mi_logo.png /var/www/estrategia/workspace/uploads/
chmod 644 /var/www/estrategia/workspace/uploads/mi_logo.png
```

Luego configure `empresa_logo_url` como `/uploads/mi_logo.png`.

## 4. CSS Variables Reference

Todas las variables CSS de marca blanca:

```css
:root {
    /* Colores primarios */
    --color-primary: #1a73e8;        /* Botones primarios, enlaces, header */
    --color-primary-hover: #1557b0;  /* Hover de elementos primarios */
    --color-primary-light: #e8f0fe;  /* Fondos suaves primarios */

    /* Colores secundarios */
    --color-secondary: #34a853;      /* Acentos, badges, éxito */
    --color-secondary-hover: #2d9249;

    /* Fondos y texto */
    --color-bg: #ffffff;             /* Fondo general */
    --color-bg-alt: #f8f9fa;         /* Fondo alternativo (tablas, cards) */
    --color-text: #202124;           /* Texto principal */
    --color-text-secondary: #5f6368; /* Texto secundario */

    /* Estados */
    --color-success: #34a853;
    --color-warning: #fbbc04;
    --color-danger: #ea4335;
    --color-info: #4285f4;

    /* Estructura */
    --sidebar-bg: #1a1a2e;           /* Fondo del sidebar */
    --sidebar-text: #e0e0e0;         /* Texto del sidebar */
    --sidebar-active: #1a73e8;       /* Item activo del sidebar */
    --header-height: 60px;
    --sidebar-width: 240px;

    /* Bordes */
    --border-radius: 8px;
    --border-color: #dadce0;

    /* Tipografía */
    --font-family: system-ui, -apple-system, sans-serif;
    --font-size-xs: 0.75rem;
    --font-size-sm: 0.875rem;
    --font-size-base: 1rem;
    --font-size-lg: 1.25rem;
    --font-size-xl: 1.5rem;

    /* Espaciado */
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;

    /* Sombras */
    --shadow-sm: 0 1px 2px rgba(0,0,0,0.1);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);

    /* Transiciones */
    --transition-fast: 0.15s ease;
    --transition-normal: 0.3s ease;
}
```

### 4.1 Sobrescribir desde Configuración

```php
// En el layout, inyectar valores desde sys_configuraciones
$color = $core->fetchColumn(
    "SELECT config_valor FROM sys_configuraciones WHERE config_clave=?",
    ['empresa_color_primario']
) ?: '#1a73e8';

echo "<style>
:root {
    --color-primary: {$color};
    --color-primary-hover: " . adjustBrightness($color, -15) . ";
}
</style>";
```

## 5. Personalización por Empresa (Multi-Tenant)

Cada empresa puede tener su propia personalización:

```sql
-- Asociar configuraciones a empresa
ALTER TABLE sys_configuraciones ADD COLUMN empresa_id INT DEFAULT NULL;

-- Insertar configuraciones por empresa
INSERT INTO sys_configuraciones (empresa_id, config_clave, config_valor)
VALUES (2, 'empresa_color_primario', '#ff5722');
```

```php
// Obtener configuración con fallback global
function getEmpresaConfig(int $empresaId, string $key, $default = null) {
    $val = $core->fetchColumn(
        "SELECT config_valor FROM sys_configuraciones
         WHERE config_clave = ? AND empresa_id = ?",
        [$key, $empresaId]
    );
    if ($val !== false && $val !== null) return $val;
    return $core->fetchColumn(
        "SELECT config_valor FROM sys_configuraciones
         WHERE config_clave = ? AND empresa_id IS NULL",
        [$key]
    ) ?: $default;
}
```

## 6. Ejemplos de Temas

### Tema Corporativo Azul

```
color_primario: #1a73e8
color_secundario: #34a853
sidebar_bg: #1a1a2e
```

### Tema Salud Verde

```
color_primario: #009688
color_secundario: #4caf50
sidebar_bg: #004d40
```

### Tema Industrial Naranja

```
color_primario: #ff9800
color_secundario: #607d8b
sidebar_bg: #263238
```
