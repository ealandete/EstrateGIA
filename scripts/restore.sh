#!/bin/bash
# EstrateGIA — Restauracion de backup (22_UNIFICACION_TRANSVERSAL.md §4.4)
# Uso: ./restore.sh [archivo.sql.gz]
set -euo pipefail

APP_NAME="estrategia"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
WORKSPACE_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="/home/emilio/backups/${APP_NAME}"

if [ -f "${WORKSPACE_DIR}/.env" ]; then
    export $(grep -v '^#' "${WORKSPACE_DIR}/.env" | xargs)
fi
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-estrategia_v1}"
DB_USER="${DB_USER:-emilio}"
DB_PASS="${DB_PASS:-s1gma}"

echo "=========================================="
echo "  EstrateGIA — Restauracion de Backup"
echo "=========================================="
echo ""

# ===== 1. Listar backups disponibles =====
if [ $# -ge 1 ]; then
    BACKUP_FILE="$1"
    if [ ! -f "$BACKUP_FILE" ]; then
        BACKUP_FILE="${BACKUP_DIR}/db/${BACKUP_FILE}"
    fi
else
    echo "Backups disponibles (mas recientes primero):"
    echo "--------------------------------------------"
    I=1
    declare -a BACKUP_LIST
    while IFS= read -r f; do
        SHA="${f%.sql.gz}.sha256"
        SHA_VAL=""
        [ -f "$SHA" ] && SHA_VAL="  SHA256: $(head -1 "$SHA" | awk '{print $1}')"
        echo "  ${I}) $(basename "$f")  ($(du -h "$f" | cut -f1))$(stat -c '  %y' "$f" 2>/dev/null | cut -d'.' -f1)${SHA_VAL}"
        BACKUP_LIST[$I]="$f"
        I=$((I + 1))
    done < <(find "${BACKUP_DIR}/db" -name "${APP_NAME}_*.sql.gz" -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -20 | cut -d' ' -f2-)

    if [ $I -eq 1 ]; then
        echo "  NO hay backups disponibles en ${BACKUP_DIR}/db/"
        exit 1
    fi

    echo ""
    echo "Ingresa el numero del backup a restaurar (o 'q' para salir):"
    read -r SEL
    [ "$SEL" = "q" ] && exit 0

    if ! [[ "$SEL" =~ ^[0-9]+$ ]] || [ "$SEL" -lt 1 ] || [ "$SEL" -ge $I ]; then
        echo "Seleccion invalida"
        exit 1
    fi
    BACKUP_FILE="${BACKUP_LIST[$SEL]}"
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: Archivo no encontrado: ${BACKUP_FILE}"
    exit 1
fi

BASENAME=$(basename "$BACKUP_FILE")
echo ""
echo "Backup seleccionado: ${BASENAME}"
echo "TamaNo: $(du -h "$BACKUP_FILE" | cut -f1)"

# ===== 2. Verificar SHA256 =====
SHA_FILE="${BACKUP_FILE%.sql.gz}.sha256"
if [ -f "$SHA_FILE" ]; then
    echo ""
    echo "Verificando SHA256..."
    if sha256sum -c "$SHA_FILE" --status 2>/dev/null; then
        echo "  SHA256: OK"
    else
        echo "  SHA256: FAIL — el archivo no coincide con el checksum grabado"
        EXPECTED=$(awk '{print $1}' "$SHA_FILE")
        ACTUAL=$(sha256sum "$BACKUP_FILE" | awk '{print $1}')
        echo "  Esperado:  ${EXPECTED}"
        echo "  Obtenido:  ${ACTUAL}"
        echo ""
        echo "Restaurar de todas formas? (SI/no):"
        read -r FORCE
        if [ "$FORCE" != "SI" ]; then
            echo "Restauracion cancelada"
            exit 1
        fi
    fi
else
    echo "  AVISO: No se encontro archivo .sha256, no se puede verificar integridad"
fi

# ===== 3. Confirmar =====
echo ""
echo "=========================================="
echo "  ADVERTENCIA"
echo "=========================================="
echo "  Esto SOBREESCRIBIRA la base de datos '${DB_NAME}'"
echo "  Host: ${DB_HOST}"
echo "  Todos los datos actuales se PERDERAN"
echo ""
echo "  Escribe 'SI' (mayusculas) para confirmar:"
read -r CONFIRM

if [ "$CONFIRM" != "SI" ]; then
    echo "Restauracion cancelada"
    exit 0
fi

# ===== 4. Restaurar =====
echo ""
echo "Iniciando restauracion..."
START_TIME=$(date +%s)
echo "[$(date '+%H:%M:%S')] Descomprimiendo y restaurando ${BASENAME}..."

if gunzip -c "$BACKUP_FILE" | mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/tmp/restore_mysql_err.log; then
    END_TIME=$(date +%s)
    DURACION=$((END_TIME - START_TIME))
    echo "[$(date '+%H:%M:%S')] Restauracion completada exitosamente (${DURACION}s)"
else
    echo "ERROR en restauracion:"
    cat /tmp/restore_mysql_err.log
    echo ""
    echo "[$(date '+%H:%M:%S')] RESTORE ERROR: ${BASENAME}" >> "${BACKUP_DIR}/backup.log"
    exit 1
fi

# ===== 5. Registrar =====
SHA256=$(sha256sum "$BACKUP_FILE" | awk '{print $1}')
SIZE=$(stat -c%s "$BACKUP_FILE")
RESTORE_JSON=$(cat <<EOF
{"tipo":"RESTORE","archivo":"${BASENAME}","tamano_bytes":${SIZE},"sha256":"${SHA256}","estado":"OK","mensaje":"Restauracion manual completada (${DURACION}s)","ejecutado_por":"MANUAL","duracion_seg":${DURACION}}
EOF
)

if curl -sSf -X POST "http://localhost:81/api/backup/log" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer BACKUP_SERVICE" \
    -d "$RESTORE_JSON" > /dev/null 2>&1; then
    echo "Log registrado via API"
else
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
        "INSERT INTO backup_log (tipo, archivo, tamano_bytes, sha256, estado, mensaje, ejecutado_por, duracion_seg, created_at)
         VALUES ('RESTORE', '${BASENAME}', ${SIZE}, '${SHA256}', 'OK',
                 'Restauracion manual completada (${DURACION}s)', 'MANUAL', ${DURACION}, NOW())" 2>/dev/null || true
fi

echo ""
echo "[$(date '+%Y-%m-%d %H:%M:%S')] RESTORE OK: ${BASENAME} (${DURACION}s)" >> "${BACKUP_DIR}/backup.log"
echo "Restauracion finalizada."
