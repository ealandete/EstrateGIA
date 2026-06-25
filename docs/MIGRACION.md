# EstrateGIA - Manual de Migración

## 1. Tipos de Migración

| Tipo | Archivo | Propósito |
|------|---------|-----------|
| Instalación limpia | `database/estrategia_v1_completo.sql` | Schema completo desde cero |
| Usuarios y roles | `database/migration_unificacion_usuarios.sql` | Unificar tablas de usuarios, roles, permisos, logging, soporte |
| Comercial | `database/migration_comercial.sql` | Licencias y facturación |
| Ambiental v2 | `scripts/migracion_ambiental_v2.sql` | Tablas y datos del módulo ambiental |

## 2. Migración desde Sistema Legacy

### 2.1 Mapeo de Tablas

| Legado | EstrateGIA | Acción |
|--------|-----------|--------|
| `empresas` | `plan_empresas` | Migrar con `empresa_id` = nuevo autoincrement |
| `usuarios` | `sys_usuarios` | Mapear password a bcrypt, asignar `usuario_rol_id` |
| `indicadores` | `ind_indicadores` | Relacionar con `indicador_categoria_id` y `indicador_plan_id` |
| `mediciones` | `ind_mediciones` | Mantener `medicion_fecha`, convertir valores numéricos |

### 2.2 Procedimiento Paso a Paso

```bash
# 1. Backup previo obligatorio
bash scripts/backup.sh

# 2. Aplicar schema base
mysql -u usuario -p estrategia_v1 < database/estrategia_v1_completo.sql

# 3. Migrar datos legacy con script personalizado
php scripts/migrar_legacy.php --source=legacy_db --target=estrategia_v1

# 4. Aplicar migraciones incrementales
mysql -u usuario -p estrategia_v1 < database/migration_unificacion_usuarios.sql
mysql -u usuario -p estrategia_v1 < database/migration_comercial.sql
mysql -u usuario -p estrategia_v1 < scripts/migracion_ambiental_v2.sql

# 5. Verificar integridad
mysql -u usuario -p estrategia_v1 -e "SELECT COUNT(*) FROM sys_usuarios; SELECT COUNT(*) FROM plan_empresas;"
```

### 2.3 Migración de Contraseñas

El sistema legacy puede usar hashes diferentes (MD5, SHA1). EstrateGIA usa bcrypt:

```php
// En script de migración
foreach ($usuarios_legacy as $u) {
    if (strlen($u['password']) === 32) {
        // MD5 legacy: forzar cambio de contraseña en primer login
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $forceReset = true;
    } else {
        $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    }
    $core->insert('sys_usuarios', [
        'usuario_email' => $u['email'],
        'usuario_password_hash' => $hash,
        'usuario_rol_id' => mapearRol($u['rol_legacy']),
        // ...
    ]);
}
```

## 3. Importación CSV

### 3.1 Formato de Mediciones

```csv
indicador_id,indicador_nombre,categoria,fecha,periodo,valor,observaciones
1,KPI-C01,cumplimiento,2026-05-15,2026-05,85.5,Medición manual mayo
2,KPI-C02,cumplimiento,2026-05-15,2026-05,92.0,Importado desde ERP
```

### 3.2 Validaciones del CSV

- `indicador_id` debe existir en `ind_indicadores` y ser > 0
- `valor` debe ser numérico y distinto de 0
- `fecha` debe tener formato `YYYY-MM-DD`

### 3.3 Importación Programática

```php
require_once BASE_PATH . '/lib/IndicatorManager.php';
$im = new IndicatorManager();
$handle = fopen('mediciones.csv', 'r');
fgetcsv($handle); // Saltar cabecera
while (($fila = fgetcsv($handle)) !== false) {
    if (empty($fila[0]) || trim($fila[0]) === 'EJEMPLO ->') continue;
    $im->registrarMedicion([
        'medicion_indicador_id' => (int)$fila[0],
        'medicion_valor' => (float)$fila[5],
        'medicion_fecha' => $fila[3],
        'medicion_periodo' => $fila[4],
        'medicion_origen' => 'csv',
        'medicion_observaciones' => $fila[6] ?? '',
    ]);
}
fclose($handle);
```

### 3.4 Formato de Empresas (Bulk)

```csv
nombre,razon_social,nit,sector_id,direccion,telefono,email
Hospital Central,Hospital Central SAS,900123456-7,1,Calle 100 #15-20,6015550100,contacto@hospitalcentral.com
```

## 4. Migración de Base de Datos

### 4.1 Verificar Versión del Schema

```sql
-- Verificar que todas las tablas existen
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'estrategia_v1' ORDER BY TABLE_NAME;

-- Verificar columnas clave
SELECT COLUMN_NAME FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'estrategia_v1' AND TABLE_NAME = 'sys_usuarios';
```

### 4.2 Rollback

Si la migración falla, restaurar desde backup:

```bash
bash scripts/restore.sh estrategia_v1_20260625_020000.sql.gz
```

### 4.3 Actualización de Versión

```bash
cd /var/www/estrategia/workspace
git pull origin main
mysql -u usuario -p estrategia_v1 < database/migration_unificacion_usuarios.sql
rm -rf cache/*
```

## 5. Errores Comunes y Soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| `Duplicate entry for key 'PRIMARY'` | IDs legados colisionan con IDs existentes | Usar `INSERT IGNORE` o mapear IDs a nuevos valores |
| `Cannot add foreign key constraint` | Datos huérfanos en tablas relacionadas | Limpiar registros sin padre antes de crear FK |
| `Data too long for column` | Campos VARCHAR más cortos en el nuevo schema | Truncar o expandir columnas antes de migrar |
| `Incorrect date value` | Formato de fecha incompatible | Convertir con `STR_TO_DATE()` o `DATE()` |
| `Unknown column '2fa_secret'` | Columna no existe en schema legado | Verificar que la migración unificada se aplicó primero |
| `Access denied for user` | Credenciales de BD incorrectas | Revisar `lib/EstrateGiaCore.php` líneas 17-20 |
| CSV no importa registros | `valor == 0` se rechaza automáticamente | Incluir valores numéricos > 0 en el CSV |
| `login_attempts table doesn't exist` | Migración unificada no aplicada | Ejecutar `migration_unificacion_usuarios.sql` |

## 6. Post-Migración

```bash
# Verificar health check
curl http://localhost:81/api/health

# Verificar login
curl -X POST http://localhost:81/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@estrategia.com","password":"admin123"}'

# Limpiar caché
rm -rf cache/*
```
