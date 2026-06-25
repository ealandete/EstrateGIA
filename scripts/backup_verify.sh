#!/bin/bash
# EstrateGIA — Verificacion diaria de integridad de backups (22_UNIFICACION_TRANSVERSAL.md §4.4)
# Ejecutar via cron: 30 6 * * * /home/emilio/estrategia/workspace/scripts/backup_verify.sh
set -euo pipefail

APP_NAME="estrategia"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
WORKSPACE_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="/home/emilio/backups/${APP_NAME}"
RETENTION_DAYS=30

if [ -f "${WORKSPACE_DIR}/.env" ]; then
    export $(grep -v '^#' "${WORKSPACE_DIR}/.env" | xargs)
fi
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-estrategia_v1}"
DB_USER="${DB_USER:-emilio}"
DB_PASS="${DB_PASS:-s1gma}"

echo "=== Verificacion de Backup: $(date '+%Y-%m-%d %H:%M:%S') ==="

# ===== 1. Buscar ultimo backup (ultimas 24h) =====
LATEST_DB=$(find "${BACKUP_DIR}/db" -name "${APP_NAME}_*.sql.gz" -mtime -1 -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)

if [ -z "$LATEST_DB" ]; then
    echo "ERROR: No hay backup en las ultimas 24h"
    ESTADO="ERROR"
    MENSAJE="No se encontro backup en las ultimas 24 horas"
else
    BASENAME=$(basename "$LATEST_DB")
    echo "Backup encontrado: ${BASENAME}"

    ISSUES=""

    # ===== 2. Verificar SHA256 =====
    SHA_FILE="${LATEST_DB%.sql.gz}.sha256"
    if [ -f "$SHA_FILE" ]; then
        if sha256sum -c "$SHA_FILE" --status 2>/dev/null; then
            echo "  SHA256: OK"
        else
            echo "  SHA256: FAIL"
            ISSUES="${ISSUES} SHA256 no coincide;"
        fi
    else
        echo "  SHA256: NO FILE"
        ISSUES="${ISSUES} Archivo .sha256 no encontrado;"
    fi

    # ===== 3. Verificar tamaNo > 1KB =====
    SIZE=$(stat -c%s "$LATEST_DB" 2>/dev/null || echo 0)
    if [ "$SIZE" -gt 1024 ]; then
        echo "  TamaNo: $(du -h "$LATEST_DB" | cut -f1) (OK)"
    else
        echo "  TamaNo: ${SIZE} bytes (WARN - posible backup vacio)"
        ISSUES="${ISSUES} TamaNo sospechoso (${SIZE} bytes);"
    fi

    # ===== 4. Gunzip -t (test integridad compresion) =====
    if gunzip -t "$LATEST_DB" 2>/dev/null; then
        echo "  Integridad gzip: OK"
    else
        echo "  Integridad gzip: FAIL"
        ISSUES="${ISSUES} gunzip -t fallo (archivo corrupto);"
    fi

    if [ -n "$ISSUES" ]; then
        ESTADO="WARN"
        MENSAJE="Issues detectados:${ISSUES}"
    else
        ESTADO="OK"
        MENSAJE="Backup ${BASENAME} verificado correctamente: SHA256 OK, $(du -h "$LATEST_DB" | cut -f1)"
    fi
fi

# ===== 5. Reportar resultado =====
echo "Resultado: ${ESTADO} — ${MENSAJE}"

# Intentar registrar via API
VERIFY_JSON=$(cat <<EOF
{"tipo":"HEALTH_CHECK","archivo":"${BASENAME:-N/A}","estado":"${ESTADO}","mensaje":"[backup_verify] ${MENSAJE}","ejecutado_por":"CRON"}
EOF
)

if curl -sSf -X POST "http://localhost:81/api/backup/log" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer BACKUP_SERVICE" \
    -d "$VERIFY_JSON" > /dev/null 2>&1; then
    echo "Log registrado via API"
else
    echo "API no disponible, registrando directo en sys_logs_sistema..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
        "INSERT INTO sys_logs_sistema (log_accion, tipo, descripcion, estado, metadata, created_at)
         VALUES ('CRON', 'HEALTH_CHECK', '[backup_verify] ${MENSAJE}', '${ESTADO}',
                 JSON_OBJECT('archivo', '${BASENAME:-N/A}'), NOW())" 2>/dev/null || true
fi

# ===== 6. Log local =====
echo "[$(date '+%Y-%m-%d %H:%M:%S')] VERIFY ${ESTADO}: ${MENSAJE}" >> "${BACKUP_DIR}/backup.log"
