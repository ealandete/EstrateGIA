# EstrateGIA - Manual de Backup y Restauración

## 1. Scripts Disponibles

| Script | Propósito | Ejecución |
|--------|-----------|-----------|
| `scripts/backup.sh` | Backup diario de base de datos | Manual o Cron |
| `scripts/backup_verify.sh` | Verificación de integridad de backups | Cron diario |
| `scripts/restore.sh` | Restauración interactiva de backup | Manual (con confirmación) |

## 2. Backup (`scripts/backup.sh`)

### Configuración

```bash
BACKUP_DIR="/home/emilio/estrategia/backups"
DB_NAME="estrategia_v1"
DB_USER="emilio"
DB_PASS="s1gma"
RETENTION_DAYS=30
```

### Ejecución Manual

```bash
bash /home/emilio/estrategia/workspace/scripts/backup.sh
```

**Salida esperada**:
```
Backup: /home/emilio/estrategia/backups/estrategia_v1_20260625_020000.sql.gz (2.3M)
```

### Programar con Cron

```bash
# Backup diario a las 2 AM
0 2 * * * bash /home/emilio/estrategia/workspace/scripts/backup.sh
```

### Qué Hace

1. Crea dump de MySQL con `mysqldump --single-transaction --no-tablespaces`
2. Comprime con `gzip`
3. Guarda en `BACKUP_DIR` con timestamp (`estrategia_v1_YYYYMMDD_HHMMSS.sql.gz`)
4. Elimina backups de más de 30 días (`RETENTION_DAYS`)

### Características del Dump

- `--single-transaction`: Consistencia sin bloquear tablas (InnoDB)
- `--no-tablespaces`: Compatibilidad con versiones antiguas de MySQL
- Salida por stdout → gzip → archivo (no usa disco para descomprimido)

## 3. Verificación (`scripts/backup_verify.sh`)

### Configuración

```bash
APP_NAME="estrategia"
BACKUP_DIR="/home/emilio/backups/estrategia"
RETENTION_DAYS=30
```

### Ejecución

```bash
bash /home/emilio/estrategia/workspace/scripts/backup_verify.sh
```

### Qué Verifica

| Verificación | Comprobación | Estado OK |
|-------------|-------------|-----------|
| Backup reciente | ¿Existe backup en últimas 24h? | Archivo encontrado |
| SHA256 | `sha256sum -c` del archivo `.sha256` | Coincide |
| Tamaño | `stat -c%s` | > 1 KB (1024 bytes) |
| Integridad gzip | `gunzip -t` (test, sin descomprimir) | Sin errores |

### Resultado

```
=== Verificacion de Backup: 2026-06-25 06:30:00 ===
Backup encontrado: estrategia_v1_20260625_020000.sql.gz
  SHA256: OK
  TamaNo: 2.3M (OK)
  Integridad gzip: OK
Resultado: OK — Backup verificado correctamente
```

### Posibles Estados

| Estado | Significado | Acción |
|--------|-------------|--------|
| `OK` | Todo correcto | Ninguna |
| `WARN` | Issues detectados (tamaño sospechoso, SHA no coincide) | Revisar logs |
| `ERROR` | No hay backup en 24h | Ejecutar backup manual |

### Programar con Cron

```bash
# Verificación diaria a las 6:30 AM
30 6 * * * bash /home/emilio/estrategia/workspace/scripts/backup_verify.sh
```

## 4. Restauración (`scripts/restore.sh`)

### Ejecución

```bash
# Modo interactivo (lista backups disponibles)
bash /home/emilio/estrategia/workspace/scripts/restore.sh

# Restaurar archivo específico
bash /home/emilio/estrategia/workspace/scripts/restore.sh estrategia_v1_20260625_020000.sql.gz
```

### Flujo de Restauración

1. **Listar backups** (si no se especifica archivo): Muestra los 20 más recientes con tamaño, fecha y SHA256
2. **Seleccionar**: Ingresar número del backup a restaurar
3. **Verificar SHA256**: Si existe `.sha256`, se verifica integridad
4. **Confirmar**: Escribir `SI` (mayúsculas) para confirmar la restauración
5. **Restaurar**: `gunzip -c backup.sql.gz | mysql`
6. **Registrar**: Log en `backup_log` vía API o directo a BD

### Ejemplo de Sesión

```
==========================================
  EstrateGIA — Restauracion de Backup
==========================================

Backups disponibles (mas recientes primero):
--------------------------------------------
  1) estrategia_v1_20260625_020000.sql.gz  (2.3M)  2026-06-25 02:00:01  SHA256: a1b2c3...
  2) estrategia_v1_20260624_020000.sql.gz  (2.1M)  2026-06-24 02:00:01  SHA256: d4e5f6...

Ingresa el numero del backup a restaurar (o 'q' para salir):
1

Backup seleccionado: estrategia_v1_20260625_020000.sql.gz
TamaNo: 2.3M

Verificando SHA256...
  SHA256: OK

==========================================
  ADVERTENCIA
==========================================
  Esto SOBREESCRIBIRA la base de datos 'estrategia_v1'
  Host: localhost
  Todos los datos actuales se PERDERAN

  Escribe 'SI' (mayusculas) para confirmar:
SI

Iniciando restauracion...
[06:45:30] Descomprimiendo y restaurando estrategia_v1_20260625_020000.sql.gz...
[06:45:32] Restauracion completada exitosamente (2s)
Log registrado via API
Restauracion finalizada.
```

### SHA256 No Coincide

Si el checksum falla, se muestra el esperado vs. el real y se pregunta si restaurar de todas formas:

```
  SHA256: FAIL — el archivo no coincide con el checksum grabado
  Esperado:  a1b2c3d4...
  Obtenido:  x9y8z7w6...
  Restaurar de todas formas? (SI/no):
```

## 5. API de Backup

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/backup/log?limit=10` | Historial de ejecuciones |
| `POST` | `/api/backup/log` | Registrar ejecución |
| `GET` | `/api/backup/ultimo` | Último backup registrado |
| `POST` | `/api/backup/ejecutar` | Ejecutar backup manual (SUPER_ADMIN) |

### Registrar vía API

```bash
curl -X POST http://localhost:81/api/backup/log \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer BACKUP_SERVICE" \
  -d '{
    "tipo": "COMPLETO",
    "archivo": "estrategia_v1_20260625.sql.gz",
    "tamano_bytes": 2415000,
    "sha256": "a1b2c3d4...",
    "estado": "OK",
    "ejecutado_por": "CRON"
  }'
```

## 6. Tabla backup_log

```sql
CREATE TABLE backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('COMPLETO','DB_ONLY','FILES_ONLY','RESTORE') NOT NULL,
    archivo VARCHAR(255),
    tamano_bytes BIGINT,
    sha256 VARCHAR(64),
    estado ENUM('OK','WARN','ERROR') DEFAULT 'OK',
    mensaje TEXT,
    ejecutado_por VARCHAR(100) DEFAULT 'CRON',
    duracion_seg INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 7. Procedimientos Recomendados

### Frecuencia

| Tipo | Frecuencia | Script |
|------|-----------|--------|
| Backup completo | Diario (2 AM) | `backup.sh` |
| Verificación | Diario (6:30 AM) | `backup_verify.sh` |
| Backup pre-migración | Antes de cada migración | `backup.sh` (manual) |
| Backup pre-actualización | Antes de `git pull` | `backup.sh` (manual) |

### Checklist de Recuperación

1. Identificar el backup a restaurar (fecha, tamaño, SHA)
2. Verificar espacio en disco para descompresión
3. Notificar a usuarios de ventana de mantenimiento
4. Ejecutar `restore.sh` con backup verificado
5. Verificar health check: `curl http://localhost:81/api/health`
6. Probar login: `curl -X POST http://localhost:81/api/auth/login`
7. Registrar restauración en `backup_log`

### Almacenamiento Off-Site

```bash
# Copiar backups a servidor remoto (ejemplo)
rsync -avz /home/emilio/estrategia/backups/ user@backup-server:/backups/estrategia/

# O subir a S3
aws s3 sync /home/emilio/estrategia/backups/ s3://mi-bucket/estrategia-backups/
```

### Retención

- **30 días** en disco local (configurable en `RETENTION_DAYS`)
- **90 días** en almacenamiento externo (recomendado)
- **12 meses** para backups de fin de mes (recomendado)
