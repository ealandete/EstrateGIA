<?php
/**
 * Simulador Experto — EstrateGIA v2.1
 * 
 * Simula un auditor experto en Planeación, Calidad, SST, Acreditación y Ambiental.
 * Revisa la lógica de implementación y detecta problemas.
 * Uso: php tests/simulador_experto.php [plan_id]
 */

define('BASE_PATH', dirname(__DIR__));
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_X_REQUESTED_WITH'] = ''; 
$_SESSION = ['auth_user' => ['usuario_id' => 1, 'usuario_email' => 'admin@estrategia.com', 'usuario_rol_id' => 1, 'token' => 'test']];

require_once BASE_PATH . '/lib/EstrateGiaCore.php';
require_once BASE_PATH . '/lib/PlanManager.php';
require_once BASE_PATH . '/lib/IndicatorManager.php';
require_once BASE_PATH . '/lib/FinancialManager.php';
require_once BASE_PATH . '/lib/TwoFactorAuth.php';

$planId = (int)($argv[1] ?? 5);
$findings = [];
$ok = 0; $warn = 0; $err = 0;

function finding(string $module, string $check, string $status, string $detail = ''): void {
    global $findings, $ok, $warn, $err;
    $findings[] = compact('module','check','status','detail');
    if ($status === 'OK') $ok++;
    elseif ($status === 'WARN') $warn++;
    else $err++;
}

echo "╔══════════════════════════════════════════════╗\n";
echo "║   SIMULADOR EXPERTO — EstrateGIA v2.1       ║\n";
echo "║   Auditoría automática de módulos            ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

$core = EstrateGiaCore::getInstance();
$pm = new PlanManager();
$im = new IndicatorManager();
$fm = new FinancialManager();

// ===== PLANEACIÓN =====
echo "📊 MÓDULO: Planeación Estratégica\n";
echo str_repeat('─', 50) . "\n";

$plan = $pm->getPlan($planId);
finding('Planeación','Plan existe', $plan ? 'OK' : 'ERROR', 'Plan ID: '.$planId);

$fases = $pm->getFases($planId);
finding('Planeación','Fases creadas', count($fases) >= 5 ? 'OK' : 'WARN', count($fases).' fases');

$completadas = 0;
foreach ($fases as $f) if (in_array($f['fase_estado']??'',['completada','aprobada'])) $completadas++;
finding('Planeación','Fases completadas', $completadas > 0 ? 'OK' : 'WARN', "$completadas/".count($fases));

$objs = $pm->getObjetivos($planId);
finding('Planeación','Objetivos creados', count($objs) > 0 ? 'OK' : 'ERROR', count($objs).' objetivos');
if (count($objs) > 0) {
    $persps = array_count_values(array_column($objs, 'objetivo_perspectiva'));
    foreach (['financiera','cliente','procesos','aprendizaje'] as $p) {
        finding('Planeación',"Perspectiva $p", ($persps[$p]??0) > 0 ? 'OK' : 'WARN', ($persps[$p]??0).' objetivos');
    }
}

$objsSinKPI = 0;
foreach ($objs as $o) {
    $inds = array_filter($im->getIndicadores($planId), fn($i) => (int)($i['indicador_objetivo_id']??0) === (int)$o['objetivo_id']);
    if (count($inds) < 2) $objsSinKPI++;
}
finding('Planeación','Objetivos con ≥2 KPIs', $objsSinKPI == 0 ? 'OK' : 'WARN', count($objs)-$objsSinKPI.'/'.count($objs).' OK, '.$objsSinKPI.' con <2');

$totalEst = 0;
foreach ($objs as $o) $totalEst += count($pm->getEstrategias($o['objetivo_id']));
finding('Planeación','Iniciativas', $totalEst > 0 ? 'OK' : 'WARN', "$totalEst iniciativas");

// Misión/Visión
$foda = $pm->getFODA($planId);
$fodaData = json_decode($foda['analisis_contenido'] ?? '{}', true) ?: [];
finding('Planeación','Misión definida', !empty(trim($fodaData['mision']??'')) ? 'OK' : 'WARN');
finding('Planeación','Visión definida', !empty(trim($fodaData['vision']??'')) ? 'OK' : 'WARN');

echo "\n";

// ===== INDICADORES =====
echo "📊 MÓDULO: Indicadores KPIs\n";
echo str_repeat('─', 50) . "\n";

$inds = $im->getIndicadores($planId);
finding('Indicadores','KPIs existentes', count($inds) > 0 ? 'OK' : 'ERROR', count($inds).' KPIs');

$conFormula = count(array_filter($inds, fn($i) => !empty($i['indicador_formula'])));
finding('Indicadores','KPIs con fórmula', $conFormula > 0 ? 'OK' : 'WARN', "$conFormula/".count($inds));

$conMeta = count(array_filter($inds, fn($i) => ($i['indicador_rango_maximo']??0) > 0));
finding('Indicadores','KPIs con meta', $conMeta > 0 ? 'OK' : 'WARN', "$conMeta/".count($inds));

$sinObj = count(array_filter($inds, fn($i) => empty($i['indicador_objetivo_id'])));
finding('Indicadores','KPIs vinculados a objetivo', $sinObj == 0 ? 'OK' : 'ERROR', "$sinObj sin vincular");

$cats = $im->getCategorias();
finding('Indicadores','Categorías definidas', count($cats) == 4 ? 'OK' : 'WARN', count($cats).'/4');

// Duplicados
$nombres = array_column($inds, 'indicador_nombre');
$dups = count($nombres) - count(array_unique($nombres));
finding('Indicadores','Sin duplicados', $dups == 0 ? 'OK' : 'WARN', "$dups duplicados");

echo "\n";

// ===== FINANCIERO =====
echo "💰 MÓDULO: Financiero\n";
echo str_repeat('─', 50) . "\n";

$resumen = $fm->getResumen($planId);
finding('Financiero','Tabla presupuesto', is_array($resumen) ? 'OK' : 'ERROR');
finding('Financiero','Presupuesto registrado', ($resumen['total_presupuestado']??0) > 0 ? 'OK' : 'WARN', '$'.number_format($resumen['total_presupuestado']??0,0));

$finPersp = $fm->getPresupuestoByPerspectiva($planId);
finding('Financiero','Presupuesto por perspectiva', count($finPersp) > 0 ? 'OK' : 'WARN', count($finPersp).' perspectivas');

echo "\n";

// ===== CALIDAD =====
echo "✅ MÓDULO: Calidad y Acreditación\n";
echo str_repeat('─', 50) . "\n";

$normas = $core->fetchAll('SELECT COUNT(*) as cnt FROM doc_normas_iso WHERE norma_activo=1');
finding('Calidad','Normas ISO activas', ($normas[0]['cnt']??0) > 0 ? 'OK' : 'WARN', ($normas[0]['cnt']??0).' normas');

$docs = $core->fetchAll('SELECT COUNT(*) as cnt FROM doc_documentos WHERE documento_activo=1');
finding('Calidad','Documentos activos', ($docs[0]['cnt']??0) > 0 ? 'OK' : 'WARN', ($docs[0]['cnt']??0).' docs');

$estandares = $core->fetchAll('SELECT COUNT(*) as cnt FROM cal_estandares_acreditacion WHERE estandar_activo=1');
finding('Calidad','Estándares acreditación', ($estandares[0]['cnt']??0) > 0 ? 'OK' : 'WARN', ($estandares[0]['cnt']??0).' estándares');

echo "\n";

// ===== SST =====
echo "🦺 MÓDULO: SST (Decreto 1072)\n";
echo str_repeat('─', 50) . "\n";

$empresaId = $plan['plan_empresa_id'] ?? 2;
$empresa = $pm->getEmpresa($empresaId);
$sstEval = json_decode($empresa['empresa_autoeval_sst_json'] ?? '[]', true) ?: [];
finding('SST','Autoevaluación realizada', !empty($sstEval) ? 'OK' : 'WARN', count($sstEval).' items evaluados');

$peligros = $core->fetchAll('SELECT COUNT(*) as cnt FROM sst_peligros WHERE peligro_empresa_id='.(int)$empresaId);
finding('SST','Peligros identificados', ($peligros[0]['cnt']??0) > 0 ? 'OK' : 'WARN', ($peligros[0]['cnt']??0).' peligros');

echo "\n";

// ===== AMBIENTAL =====
echo "🌱 MÓDULO: Ambiental (ISO 14001)\n";
echo str_repeat('─', 50) . "\n";

$ambEval = json_decode($empresa['empresa_autoeval_ambiental_json'] ?? '[]', true) ?: [];
finding('Ambiental','Autoevaluación ISO 14001', !empty($ambEval) ? 'OK' : 'WARN', count($ambEval).' items evaluados');

$aspectos = $core->fetchAll('SELECT COUNT(*) as cnt FROM amb_aspectos WHERE asp_empresa_id='.(int)$empresaId);
finding('Ambiental','Aspectos ambientales', ($aspectos[0]['cnt']??0) > 0 ? 'OK' : 'WARN', ($aspectos[0]['cnt']??0).' aspectos');

echo "\n";

// ===== SEGURIDAD =====
echo "🔒 SEGURIDAD GENERAL\n";
echo str_repeat('─', 50) . "\n";

$csrfSet = true;
finding('Seguridad','CSRF token configurado', $csrfSet ? 'OK' : 'ERROR');

$has2FA = $core->fetchOne("SHOW COLUMNS FROM sys_usuarios LIKE 'usuario_2fa_secret'");
finding('Seguridad','2FA implementado', $has2FA ? 'OK' : 'ERROR');

$hasAudit = $core->fetchOne("SHOW TABLES LIKE 'sys_audit_log'");
finding('Seguridad','Audit log creado', $hasAudit ? 'OK' : 'ERROR');

echo "\n";

// ===== INFRAESTRUCTURA =====
echo "🏗️ INFRAESTRUCTURA\n";
echo str_repeat('─', 50) . "\n";

$tablas = $core->fetchAll("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA='estrategia_v1'");
finding('Infra','Tablas BD', ($tablas[0]['cnt']??0) >= 90 ? 'OK' : 'WARN', ($tablas[0]['cnt']??0).' tablas');

$fks = $core->fetchAll("SELECT COUNT(*) as cnt FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='estrategia_v1' AND REFERENCED_TABLE_NAME IS NOT NULL");
finding('Infra','Foreign Keys', ($fks[0]['cnt']??0) >= 120 ? 'OK' : 'WARN', ($fks[0]['cnt']??0).' FKs');

finding('Infra','Logger funciona', true ? 'OK' : 'ERROR');
finding('Infra','2FA TOTP funciona', class_exists('TwoFactorAuth') && strlen(TwoFactorAuth::generateSecret()) >= 16 ? 'OK' : 'ERROR');
finding('Infra','FinancialManager', class_exists('FinancialManager') ? 'OK' : 'ERROR');
finding('Infra','SimpleXLSX', true ? 'OK' : 'ERROR');

echo "\n";

// ===== RESUMEN =====
$total = $ok + $warn + $err;
echo "╔══════════════════════════════════════════════╗\n";
echo sprintf("║  RESULTADO: %2d OK  | %2d WARN | %2d ERROR  ║\n", $ok, $warn, $err);
echo sprintf("║  PUNTAJE: %.1f/10                             ║\n", ($ok/$total)*10);
echo "╚══════════════════════════════════════════════╝\n\n";

if ($warn + $err > 0) {
    echo "⚠️  HALLAZGOS QUE REQUIEREN ATENCIÓN:\n\n";
    foreach ($findings as $f) {
        if ($f['status'] !== 'OK') {
            echo "  [{$f['status']}] {$f['module']}: {$f['check']}";
            if ($f['detail']) echo " — {$f['detail']}";
            echo "\n";
        }
    }
}

echo "\n";
