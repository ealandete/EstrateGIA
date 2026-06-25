#!/bin/bash
# =====================================================================
# EstrateGIA — Provisioning de Nueva Empresa/Cliente
# Uso: ./provision.sh <NOMBRE_CLIENTE> <RAZON_SOCIAL> <NIT> <EMAIL_ADMIN>
# =====================================================================
set -euo pipefail

CLIENTE="${1:-demo}"
RAZON_SOCIAL="${2:-Empresa Demo SAS}"
NIT="${3:-900123456}"
EMAIL_ADMIN="${4:-admin@demo.com}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="${SCRIPT_DIR}/.."
DB_NAME="estrategia_${CLIENTE}"

DB_USER="emilio"
DB_PASS="s1gma"
DB_HOST="localhost"
MYSQL_CMD="mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS}"

echo "=== EstrateGIA — Provisioning: ${CLIENTE} ==="
echo "  Empresa: ${RAZON_SOCIAL}"
echo "  NIT: ${NIT}"
echo "  Admin: ${EMAIL_ADMIN}"
echo "  DB: ${DB_NAME}"
echo ""

echo "[1/4] Creando base de datos..."
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "[2/4] Aplicando schema base y migraciones..."
if [ -f "${APP_DIR}/database/estrategia_v1_completo.sql" ]; then
    $MYSQL_CMD "${DB_NAME}" < "${APP_DIR}/database/estrategia_v1_completo.sql"
fi
if [ -f "${APP_DIR}/database/migration_unificacion_usuarios.sql" ]; then
    $MYSQL_CMD "${DB_NAME}" < "${APP_DIR}/database/migration_unificacion_usuarios.sql"
fi
if [ -f "${APP_DIR}/database/migration_comercial.sql" ]; then
    $MYSQL_CMD "${DB_NAME}" < "${APP_DIR}/database/migration_comercial.sql"
fi

echo "[3/4] Creando empresa y admin..."
ADMIN_PASSWORD="Admin123!"
LICENSE_TOKEN=$(python3 -c "import secrets; print(secrets.token_hex(32))" 2>/dev/null || openssl rand -hex 32)

$MYSQL_CMD "${DB_NAME}" <<SQL
INSERT INTO sys_empresas (empresa_nit, empresa_dv, empresa_razon_social, empresa_estado, empresa_email)
VALUES ('${NIT}', '0', '${RAZON_SOCIAL}', 'ACTIVO', '${EMAIL_ADMIN}');

SET @new_eid = LAST_INSERT_ID();

INSERT INTO sys_usuarios (empresa_id, usuario_nombre, usuario_apellido, usuario_email, usuario_password, usuario_rol_id, usuario_rol_nombre, usuario_activo)
VALUES (@new_eid, 'Admin', '${CLIENTE}', '${EMAIL_ADMIN}', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'SUPER_ADMIN', 1);

INSERT INTO licencias (id_empresa, app, plan, usuarios_max, modulos_activos, fecha_inicio, fecha_fin, activa, token_licencia)
VALUES (@new_eid, 'EstrateGIA', 'AVANZADO', 999, '["planeacion","workbench","indicadores","evaluacion","procesos","calidad","sst","ambiental","nc","documentos","proveedores","crm","ia","soporte","financiero","admin","config"]', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 1, '${LICENSE_TOKEN}');
SQL

echo "[4/4] Creando .env del cliente..."
cat > "/home/emilio/estrategia/env_${CLIENTE}.env" <<ENVFILE
DB_HOST=${DB_HOST}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_NAME=${DB_NAME}
APP_NAME=EstrateGIA
APP_DEBUG=false
CLIENTE=${CLIENTE}
ENVFILE

echo ""
echo "=============================================="
echo " Provisioning completado: ${CLIENTE}"
echo "=============================================="
echo "  DB: ${DB_NAME}"
echo "  URL: http://localhost:90"
echo "  Admin: ${EMAIL_ADMIN}"
echo "  Pass: ${ADMIN_PASSWORD}"
echo "  Trial: 15 dias"
echo "=============================================="
