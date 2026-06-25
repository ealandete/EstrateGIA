#!/bin/bash
# EstrateGIA - Smoke Test Automatizado
# Verifica que todas las rutas principales respondan HTTP 200/302
# Uso: bash tests/smoke_test.sh

BASE="http://127.0.0.1:81"
COOKIES="/tmp/smoke_cookies.txt"
PASS=0; FAIL=0

echo "=== EstrateGIA Smoke Test - $(date) ==="

# Login
curl -s -c "$COOKIES" -L -o /dev/null "$BASE/login.php" -d "email=admin@estrategia.com&password=admin123" 2>/dev/null

check() { local code=$(curl -s -b "$COOKIES" -o /dev/null -w "%{http_code}" --connect-timeout 5 "$BASE$1" 2>/dev/null); if [ "$code" = "200" ]; then PASS=$((PASS+1)); else FAIL=$((FAIL+1)); echo "  ❌ $code $1"; fi; }

echo "--- GET Routes ---"
for r in "/" "/planeacion" "/procesos" "/indicadores" "/mediciones" "/documentos" "/calendario" "/evaluacion" "/evaluacion/ranking" "/ia" "/nc" "/calidad" "/calidad/autoevaluacion" "/calidad/estandares" "/calidad/riesgos" "/calidad/pamec" "/proveedores" "/formacion" "/satisfaccion" "/admin/usuarios" "/admin/roles" "/admin/auditoria" "/admin/config" "/crm" "/sst?seccion=dashboard" "/sst?seccion=peligros" "/sst?seccion=incidentes" "/sst?seccion=plan" "/sst?seccion=normatividad" "/sst?seccion=ausentismo" "/sst?seccion=capacitaciones" "/sst?seccion=examenes" "/sst?seccion=inspecciones" "/sst?seccion=emergencias" "/sst?seccion=reportes" "/ambiental?seccion=dashboard" "/ambiental?seccion=aspectos" "/ambiental?seccion=registros" "/ambiental?seccion=plan" "/ambiental?seccion=normatividad" "/ambiental?seccion=auditorias" "/ambiental?seccion=reportes"; do check "$r"; done

echo "--- DB Health ---"
mysql -u emilio -ps1gma estrategia_v1 -e "SELECT COUNT(*) as tables FROM information_schema.TABLES WHERE TABLE_SCHEMA='estrategia_v1'" 2>/dev/null | tail -1
mysql -u emilio -ps1gma estrategia_v1 -e "SELECT COUNT(*) as fks FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='estrategia_v1' AND REFERENCED_TABLE_NAME IS NOT NULL" 2>/dev/null | tail -1

echo "--- Results: $PASS passed, $FAIL failed ---"
[ $FAIL -eq 0 ] && echo "SMOKE TEST: ALL CLEAN" || echo "SMOKE TEST: ISSUES FOUND"
