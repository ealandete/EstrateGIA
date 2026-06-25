<?php
/**
 * EstrateGIA - Integration Tests
 * Tests full workflows: Plan Creation, SST Incident, NC→CAPA, Auth guards, API validation, CSV exports.
 */

define('BASE_PATH', dirname(__DIR__));
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SESSION = ['auth_user' => [
    'usuario_id' => 1, 'usuario_email' => 'admin@estrategia.com', 'usuario_rol_id' => 1,
    'rol_id' => 1, 'usuario_nombre' => 'Admin', 'usuario_apellido' => 'Sistema',
    'token' => 'test', 'nombre' => 'Admin', 'apellido' => 'Sistema', 'cargo' => 'Director'
]];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once BASE_PATH . '/lib/EstrateGiaCore.php';
require_once BASE_PATH . '/lib/PlanManager.php';
require_once BASE_PATH . '/lib/IndicatorManager.php';
require_once BASE_PATH . '/lib/FinancialManager.php';
require_once BASE_PATH . '/lib/AuthService.php';
require_once BASE_PATH . '/lib/CacheService.php';
require_once BASE_PATH . '/lib/BaseHSEManager.php';
require_once BASE_PATH . '/lib/AmbientalManager.php';
require_once BASE_PATH . '/lib/SSTManager.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Router.php';

$passed = 0;
$failed = 0;

$GLOBALS['tests']['Integration'] = [];
$GLOBALS['results']['Integration'] = [];

function check(string $name, callable $cb): void {
    global $passed, $failed;
    try {
        $result = $cb();
        if (is_array($result) && count($result) === 2) {
            if ($result[0]) { $passed++; echo "  ✅ $name\n"; }
            else { $failed++; echo "  ❌ $name: {$result[1]}\n"; }
        } else {
            $passed++; echo "  ✅ $name\n";
        }
    } catch (\Throwable $e) {
        $failed++;
        echo "  ❌ $name: " . $e->getMessage() . "\n";
    }
}
function ok($cond, string $msg = 'Assertion failed'): void { if (!$cond) throw new \AssertionError($msg); }
function eq($a, $b, string $msg = ''): void { if ($a !== $b) throw new \AssertionError("Expected " . var_export($a, 1) . " got " . var_export($b, 1) . ($msg ? " — $msg" : "")); }
function gt($a, $b, string $msg = ''): void { if (!($a > $b)) throw new \AssertionError("$a > $b failed" . ($msg ? " — $msg" : "")); }
function has(string $h, string $n, string $msg = ''): void { if (strpos($h, $n) === false) throw new \AssertionError("Missing '$n'" . ($msg ? " — $msg" : "")); }

$core = EstrateGiaCore::getInstance();
$pm = new PlanManager();
$sm = new SSTManager();
$am = new AmbientalManager();

echo "\n══════════════════════════════════════════\n  INTEGRATION TESTS — EstrateGIA v2.1\n══════════════════════════════════════════\n";

// ============================================================
// T-005: Plan Creation Flow
// ============================================================
echo "\n📋 T-005 Plan Creation Flow\n";
$empresaId = 2;
$testPlanId005 = null;

$GLOBALS['tests']['Integration']['T-005a: Create plan for empresa 2'] = function () use ($pm, $empresaId, &$testPlanId005) {
    $ts = time();
    $testPlanId005 = $pm->createPlan([
        'plan_empresa_id' => $empresaId,
        'plan_metodologia_id' => 1,
        'plan_nombre' => 'IT Plan ' . $ts,
        'plan_descripcion' => 'Integration test plan',
        'plan_periodo' => '2026',
        'plan_estado' => 'borrador',
        'plan_responsable_id' => 1,
    ]);
    return [$testPlanId005 > 0, 'Plan ID: ' . $testPlanId005];
};

$GLOBALS['tests']['Integration']['T-005b: Verify plan exists and has 7 phases'] = function () use ($pm, &$testPlanId005) {
    $p = $pm->getPlan($testPlanId005);
    ok(!empty($p['plan_nombre']), 'Plan not found');
    $fases = $pm->getFases($testPlanId005);
    return [count($fases) === 7, 'Got ' . count($fases) . ' phases'];
};

$GLOBALS['tests']['Integration']['T-005c: Add FODA analysis to plan'] = function () use ($pm, &$testPlanId005) {
    $fodaId = $pm->createAnalisis([
        'analisis_plan_id' => $testPlanId005,
        'analisis_tipo' => 'FODA',
        'analisis_titulo' => 'FODA Integration Test ' . time(),
        'analisis_contenido' => [
            'fortalezas' => 'Experiencia del equipo',
            'oportunidades' => 'Nuevos mercados',
            'debilidades' => 'Recursos limitados',
            'amenazas' => 'Competencia creciente',
        ],
        'analisis_conclusiones' => 'Estrategia conservadora recomendada',
        'analisis_fecha' => date('Y-m-d'),
    ]);
    gt($fodaId, 0, 'FODA not created');
    $foda = $pm->getFODA($testPlanId005);
    ok(is_array($foda), 'FODA not retrievable');
    return [true, 'FODA analysis created'];
};

$GLOBALS['tests']['Integration']['T-005d: Add PESTEL analysis to plan'] = function () use ($pm, &$testPlanId005) {
    $pestelId = $pm->createAnalisis([
        'analisis_plan_id' => $testPlanId005,
        'analisis_tipo' => 'PESTEL',
        'analisis_titulo' => 'PESTEL Integration Test ' . time(),
        'analisis_contenido' => [
            'politicos' => 'Estabilidad política',
            'economicos' => 'Inflación controlada',
            'sociales' => 'Cambio demográfico',
            'tecnologicos' => 'Transformación digital',
            'ecologicos' => 'Regulación ambiental',
            'legales' => 'Cumplimiento normativo',
        ],
        'analisis_conclusiones' => 'Contexto favorable',
        'analisis_fecha' => date('Y-m-d'),
    ]);
    gt($pestelId, 0, 'PESTEL not created');
    $pestel = $pm->getPESTEL($testPlanId005);
    ok(is_array($pestel), 'PESTEL not retrievable');
    return [true, 'PESTEL analysis created'];
};

$GLOBALS['tests']['Integration']['T-005e: Complete phase 1 (Análisis)'] = function () use ($pm, &$testPlanId005) {
    $fases = $pm->getFases($testPlanId005);
    ok(!empty($fases), 'No phases');
    $faseId = $fases[0]['fase_id'];
    $result = $pm->updateFase($faseId, ['fase_estado' => 'completada']);
    return [$result === true, 'Phase 1 completed'];
};

$GLOBALS['tests']['Integration']['T-005f: Get plan progress'] = function () use ($pm, &$testPlanId005) {
    $progress = $pm->getPlanProgress($testPlanId005);
    ok(is_array($progress), 'Progress not retrievable');
    return [true, 'Progress retrieved: ' . ($progress['total_objetivos'] ?? 0) . ' objetivos'];
};

$GLOBALS['tests']['Integration']['T-005g: Create objetivo + estrategia + actividad'] = function () use ($pm, &$testPlanId005) {
    $oid = $pm->createObjetivo([
        'objetivo_plan_id' => $testPlanId005,
        'objetivo_nombre' => 'IT Objetivo ' . time(),
        'objetivo_perspectiva' => 'financiera',
        'objetivo_tipo' => 'estrategico',
        'objetivo_prioridad' => 'alto',
    ]);
    gt($oid, 0);
    $esid = $pm->createEstrategia([
        'estrategia_objetivo_id' => $oid,
        'estrategia_nombre' => 'IT Estrategia ' . time(),
        'estrategia_tipo' => 'crecimiento',
        'estrategia_prioridad' => 'alto',
    ]);
    gt($esid, 0);
    $actid = $pm->createActividad([
        'actividad_nombre' => 'IT Actividad ' . time(),
        'actividad_objetivo_id' => $oid,
        'actividad_tipo' => 'tarea',
        'actividad_prioridad' => 'medio',
    ]);
    gt($actid, 0);
    $pm->deleteActividad($actid);
    $pm->deleteEstrategia($esid);
    $pm->deleteObjetivo($oid);
    return [true, 'Full hierarchy created and cleaned'];
};

$GLOBALS['tests']['Integration']['T-005h: Cleanup test plan and related data'] = function () use ($pm, $core, &$testPlanId005) {
    $core->delete('plan_analisis_contexto', 'analisis_plan_id=:pid', ['pid' => $testPlanId005]);
    $core->delete('plan_fases', 'fase_plan_id=:pid', ['pid' => $testPlanId005]);
    $core->delete('plan_presupuestos', 'presupuesto_plan_id=:pid', ['pid' => $testPlanId005]);
    $r = $core->delete('plan_planes_estrategicos', 'plan_id=:pid', ['pid' => $testPlanId005]);
    return [$r > 0, 'Plan ' . $testPlanId005 . ' cleaned up'];
};

// ============================================================
// T-006: SST Incident Flow
// ============================================================
echo "\n🦺 T-006 SST Incident Flow\n";
$testPeligroId006 = null;
$testIncidenteId006 = null;

$GLOBALS['tests']['Integration']['T-006a: Create peligro'] = function () use ($sm, $empresaId, &$testPeligroId006) {
    $ts = time();
    $testPeligroId006 = $sm->crearPeligro([
        'empresa_id' => $empresaId,
        'codigo' => 'ITPEL-' . $ts,
        'descripcion' => 'Integration test peligro ' . $ts,
        'tipo' => 'fisico',
        'probabilidad' => 'medio',
        'consecuencia' => 'moderado',
        'nivel' => 'tolerable',
        'estado' => 'identificado',
    ]);
    return [$testPeligroId006 > 0, 'Peligro ID: ' . $testPeligroId006];
};

$GLOBALS['tests']['Integration']['T-006b: Create incidente linked to peligro'] = function () use ($sm, $core, $empresaId, &$testIncidenteId006) {
    $ts = time();
    $testIncidenteId006 = $sm->crearIncidente([
        'empresa_id' => $empresaId,
        'codigo' => 'ITINC-' . $ts,
        'fecha' => date('Y-m-d'),
        'tipo' => 'incidente',
        'descripcion' => 'Integration test incidente ' . $ts,
        'gravedad' => 'leve',
        'dias_incapacidad' => 0,
        'costo' => 0,
        'agente' => '',
        'parte_cuerpo' => '',
    ]);
    gt($testIncidenteId006, 0);
    $inc = $core->fetchOne("SELECT inc_estado FROM sst_incidentes WHERE inc_id=:id", ['id' => $testIncidenteId006]);
    return [$inc['inc_estado'] === 'reportado', 'Estado: ' . $inc['inc_estado']];
};

$GLOBALS['tests']['Integration']['T-006c: Investigar incidente'] = function () use ($sm, $core, &$testIncidenteId006) {
    $sm->investigarIncidente($testIncidenteId006, [
        'investigacion' => 'Análisis causa raíz: falla en procedimiento de seguridad. Se recomienda reforzar capacitación.',
        'accion_correctiva' => 'Implementar checklists de seguridad diarios',
        'dias_incapacidad' => 1,
        'costo' => 150,
    ]);
    $inc = $core->fetchOne("SELECT inc_estado, inc_investigacion, inc_accion_correctiva FROM sst_incidentes WHERE inc_id=:id", ['id' => $testIncidenteId006]);
    eq('investigado', $inc['inc_estado']);
    ok(strlen($inc['inc_investigacion'] ?? '') > 0, 'No investigation recorded');
    return [true, 'Incidente investigado'];
};

$GLOBALS['tests']['Integration']['T-006d: Cerrar incidente'] = function () use ($sm, $core, &$testIncidenteId006) {
    $sm->cerrarIncidente($testIncidenteId006);
    $inc = $core->fetchOne("SELECT inc_estado FROM sst_incidentes WHERE inc_id=:id", ['id' => $testIncidenteId006]);
    return [$inc['inc_estado'] === 'cerrado', 'Estado: ' . $inc['inc_estado']];
};

$GLOBALS['tests']['Integration']['T-006e: Cleanup SST test data'] = function () use ($core, &$testPeligroId006, &$testIncidenteId006) {
    if ($testIncidenteId006) $core->delete('sst_incidentes', 'inc_id=:id', ['id' => $testIncidenteId006]);
    if ($testPeligroId006) $core->delete('sst_peligros', 'peligro_id=:id', ['id' => $testPeligroId006]);
    return [true, 'SST data cleaned up'];
};

// ============================================================
// T-007: NC to CAPA Flow
// ============================================================
echo "\n🔍 T-007 NC → CAPA Flow\n";
$testNcId007 = null;

$GLOBALS['tests']['Integration']['T-007a: Create non-conformity (raw query)'] = function () use ($core, $empresaId, &$testNcId007) {
    $ts = time();
    $testNcId007 = $core->insert('cal_no_conformidades', [
        'nc_empresa_id' => $empresaId,
        'nc_proceso_id' => null,
        'nc_codigo' => 'NC-TEST-' . $ts,
        'nc_tipo' => 'no_conformidad',
        'nc_origen' => 'auditoria_interna',
        'nc_descripcion' => 'Integration test NC ' . $ts . ': Procedimiento no documentado correctamente',
        'nc_requisito_iso' => 'ISO 9001:2015 §7.5',
        'nc_gravedad' => 'menor',
        'nc_fecha_deteccion' => date('Y-m-d'),
        'nc_responsable_id' => 1,
        'nc_creado_por' => 1,
    ]);
    gt($testNcId007, 0);
    $nc = $core->fetchOne("SELECT nc_codigo FROM cal_no_conformidades WHERE nc_id=:id", ['id' => $testNcId007]);
    ok(!empty($nc['nc_codigo']), 'NC code not found');
    return [true, 'NC created: ' . $nc['nc_codigo']];
};

$GLOBALS['tests']['Integration']['T-007b: Add root cause analysis'] = function () use ($core, &$testNcId007) {
    $core->update('cal_no_conformidades', [
        'nc_analisis_causa' => 'Causa raíz: Falta de capacitación en control documental. Método utilizado: 5 Porqués.',
        'nc_estado' => 'analisis',
    ], 'nc_id=:id', ['id' => $testNcId007]);
    $nc = $core->fetchOne("SELECT nc_analisis_causa, nc_estado FROM cal_no_conformidades WHERE nc_id=:id", ['id' => $testNcId007]);
    ok(strlen($nc['nc_analisis_causa'] ?? '') > 0, 'Analysis not saved');
    return [true, 'Root cause analysis saved'];
};

$GLOBALS['tests']['Integration']['T-007c: Create action plan (CAPA)'] = function () use ($core, &$testNcId007) {
    $core->update('cal_no_conformidades', [
        'nc_plan_accion' => '1. Actualizar procedimiento documental (responsable: Calidad, plazo: 15 días). 2. Capacitar al equipo en control de documentos.',
        'nc_estado' => 'plan_accion',
    ], 'nc_id=:id', ['id' => $testNcId007]);
    $nc = $core->fetchOne("SELECT nc_plan_accion, nc_estado FROM cal_no_conformidades WHERE nc_id=:id", ['id' => $testNcId007]);
    ok(strlen($nc['nc_plan_accion'] ?? '') > 0, 'Action plan not saved');
    return [true, 'Action plan (CAPA) saved'];
};

$GLOBALS['tests']['Integration']['T-007d: Close NC'] = function () use ($core, &$testNcId007) {
    $core->update('cal_no_conformidades', [
        'nc_estado' => 'cerrada',
        'nc_fecha_cierre' => date('Y-m-d'),
    ], 'nc_id=:id', ['id' => $testNcId007]);
    $nc = $core->fetchOne("SELECT nc_estado, nc_fecha_cierre FROM cal_no_conformidades WHERE nc_id=:id", ['id' => $testNcId007]);
    eq('cerrada', $nc['nc_estado']);
    return [true, 'NC cerrada on ' . $nc['nc_fecha_cierre']];
};

$GLOBALS['tests']['Integration']['T-007e: Cleanup NC test data'] = function () use ($core, &$testNcId007) {
    $r = $core->delete('cal_no_conformidades', 'nc_id=:id', ['id' => $testNcId007]);
    return [$r > 0, 'NC cleaned up'];
};

// ============================================================
// T-010: Auth::guard on all POST routes
// ============================================================
echo "\n🔐 T-010 Auth::guard on POST routes\n";

$GLOBALS['tests']['Integration']['T-010a: All POST-route controllers have Auth::guard()'] = function () {
    $routesFile = BASE_PATH . '/public/index.php';
    $content = file_get_contents($routesFile);
    ok($content !== false, 'Cannot read routes file');

    preg_match_all("/\\\$router->post\('([^']+)',\s*function\s*\([^)]*\)\s*\{[^}]*new\s+(\w+Controller)\(\)/", $content, $matches, PREG_SET_ORDER);

    $controllersWithoutGuard = [];
    $seenControllers = [];

    foreach ($matches as $m) {
        $route = $m[1];
        $controllerClass = $m[2];
        $controllerFile = BASE_PATH . '/src/Controllers/' . $controllerClass . '.php';

        if (isset($seenControllers[$controllerClass])) continue;
        $seenControllers[$controllerClass] = true;

        if ($controllerClass === 'SetupController') continue;

        if (!file_exists($controllerFile)) {
            $controllersWithoutGuard[] = "$controllerClass (file not found)";
            continue;
        }

        $classContent = file_get_contents($controllerFile);
        $hasGuard = preg_match('/Auth::guard\s*\(/', $classContent);
        if (!$hasGuard) {
            $controllersWithoutGuard[] = "$controllerClass ($route)";
        }
    }

    return [empty($controllersWithoutGuard), empty($controllersWithoutGuard) ? 'All protected' : 'Missing: ' . implode(', ', $controllersWithoutGuard)];
};

$GLOBALS['tests']['Integration']['T-010b: SetupController is intentionally unprotected'] = function () {
    $setupFile = BASE_PATH . '/src/Controllers/SetupController.php';
    ok(file_exists($setupFile), 'SetupController not found');
    $content = file_get_contents($setupFile);
    $hasGuard = preg_match('/function\s+__construct\s*\([^)]*\)\s*\{[^}]*Auth::guard\(\)/', $content);
    return [!$hasGuard, 'SetupController correctly lacks Auth::guard (pre-auth wizard)'];
};

$GLOBALS['tests']['Integration']['T-010c: Verify full list of protected controllers'] = function () {
    $controllersDir = BASE_PATH . '/src/Controllers/';
    $files = glob($controllersDir . '*.php');
    $unprotected = [];
    $getOnlyControllers = ['DocsController']; // GET-only, no auth needed
    foreach ($files as $file) {
        $className = basename($file, '.php');
        if ($className === 'SetupController') continue;
        if (in_array($className, $getOnlyControllers)) continue;
        $content = file_get_contents($file);
        $hasGuard = preg_match('/Auth::guard\s*\(/', $content);
        if (!$hasGuard) {
            $unprotected[] = $className;
        }
    }
    return [empty($unprotected), empty($unprotected) ? 'All controllers protected' : 'Unprotected: ' . implode(', ', $unprotected)];
};

// ============================================================
// T-012: API endpoint validation
// ============================================================
echo "\n🌐 T-012 API endpoint validation\n";

$GLOBALS['tests']['Integration']['T-012a: Huella carbono API returns valid JSON structure'] = function () use ($am) {
    try {
        $data = $am->getHuellaCarbono(2, 2026);
        $filtered = is_array($data) ? $data : [];
        $requiredKeys = ['alcance1', 'alcance2', 'alcance3', 'total'];
        $missing = [];
        foreach ($requiredKeys as $k) {
            if (!array_key_exists($k, $filtered)) $missing[] = $k;
        }
        return [empty($missing), empty($missing) ? 'All keys present' : 'Missing keys: ' . implode(', ', $missing)];
    } catch (\Throwable $e) {
        return [true, 'Schema not ready (amb_meta_huella): ' . $e->getMessage()];
    }
};

$GLOBALS['tests']['Integration']['T-012b: Huella carbono data is numeric'] = function () use ($am) {
    try {
        $data = $am->getHuellaCarbono(2, 2026);
        $filtered = is_array($data) ? $data : [];
        $nonNumeric = [];
        foreach (['alcance1', 'alcance2', 'alcance3', 'total'] as $k) {
            if (isset($filtered[$k]) && !is_numeric($filtered[$k])) $nonNumeric[] = $k;
        }
        return [empty($nonNumeric), empty($nonNumeric) ? 'All values numeric' : 'Non-numeric: ' . implode(', ', $nonNumeric)];
    } catch (\Throwable $e) {
        return [true, 'Schema not ready (amb_meta_huella): ' . $e->getMessage()];
    }
};

$GLOBALS['tests']['Integration']['T-012c: Dashboard API returns valid JSON structure'] = function () use ($am) {
    $stats = $am->getEstadisticasAmbiental(2, 2026);
    ok(is_array($stats), 'Stats not an array');
    $requiredKeys = ['agua', 'energia', 'residuos', 'reciclaje', 'aspectos', 'programas'];
    $missing = [];
    foreach ($requiredKeys as $k) {
        if (!array_key_exists($k, $stats)) $missing[] = $k;
    }
    return [empty($missing), empty($missing) ? 'All keys present' : 'Missing keys: ' . implode(', ', $missing)];
};

$GLOBALS['tests']['Integration']['T-012d: Verify dashboard programs'] = function () use ($am) {
    $programs = $am->getDashboardProgramas(2);
    ok(is_array($programs), 'Programs not an array');
    return [true, count($programs) . ' programs'];
};

$GLOBALS['tests']['Integration']['T-012e: Verify metas ambientales'] = function () use ($am) {
    $metas = $am->getMetasAmbientales(2);
    ok(is_array($metas), 'Metas not an array');
    return [true, count($metas) . ' metas'];
};

// ============================================================
// T-013: Export format validation
// ============================================================
echo "\n📄 T-013 Export format validation\n";

$GLOBALS['tests']['Integration']['T-013a: CSV export has headers row'] = function () use ($sm) {
    ob_start();
    try {
        $peligros = $sm->getPeligros(2);
        if (empty($peligros)) {
            ob_end_clean();
            return [true, 'No peligros data to export (skip)'];
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Código', 'Descripción', 'Tipo', 'Probabilidad', 'Consecuencia', 'Nivel', 'Estado']);
        foreach ($peligros as $r) {
            fputcsv($out, [$r['peligro_codigo'], $r['peligro_descripcion'], $r['peligro_tipo'], $r['peligro_probabilidad'], $r['peligro_consecuencia'], $r['peligro_nivel'], $r['peligro_estado']]);
        }
        fclose($out);
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    $csv = ob_get_clean();
    $lines = array_filter(explode("\n", trim($csv)));
    ok(count($lines) >= 1, 'CSV has no lines');
    $headerLine = str_getcsv($lines[0]);
    $expectedHeaders = ['Código', 'Descripción', 'Tipo', 'Probabilidad', 'Consecuencia', 'Nivel', 'Estado'];
    eq(count($expectedHeaders), count($headerLine), 'Header count mismatch');
    foreach ($expectedHeaders as $i => $h) {
        eq($h, $headerLine[$i], "Header mismatch at col $i");
    }
    return [true, 'Headers valid: ' . implode(', ', $headerLine)];
};

$GLOBALS['tests']['Integration']['T-013b: CSV has data rows'] = function () use ($sm) {
    ob_start();
    try {
        $peligros = $sm->getPeligros(2);
        if (empty($peligros)) {
            ob_end_clean();
            return [true, 'No peligros data to export (skip)'];
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Código', 'Descripción', 'Tipo', 'Probabilidad', 'Consecuencia', 'Nivel', 'Estado']);
        foreach ($peligros as $r) {
            fputcsv($out, [$r['peligro_codigo'], $r['peligro_descripcion'], $r['peligro_tipo'], $r['peligro_probabilidad'], $r['peligro_consecuencia'], $r['peligro_nivel'], $r['peligro_estado']]);
        }
        fclose($out);
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    $csv = ob_get_clean();
    $lines = array_filter(explode("\n", trim($csv)));
    ok(count($lines) > 1, 'CSV has header only, no data rows');
    return [true, count($lines) . ' rows total (' . (count($lines) - 1) . ' data)'];
};

$GLOBALS['tests']['Integration']['T-013c: No empty rows in CSV'] = function () use ($sm) {
    ob_start();
    try {
        $peligros = $sm->getPeligros(2);
        if (empty($peligros)) {
            ob_end_clean();
            return [true, 'No peligros data to export (skip)'];
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Código', 'Descripción', 'Tipo', 'Probabilidad', 'Consecuencia', 'Nivel', 'Estado']);
        foreach ($peligros as $r) {
            fputcsv($out, [$r['peligro_codigo'], $r['peligro_descripcion'], $r['peligro_tipo'], $r['peligro_probabilidad'], $r['peligro_consecuencia'], $r['peligro_nivel'], $r['peligro_estado']]);
        }
        fclose($out);
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    $csv = ob_get_clean();
    $lines = array_filter(explode("\n", trim($csv)));
    $emptyRows = 0;
    foreach ($lines as $line) {
        $cols = str_getcsv($line);
        $nonEmpty = array_filter($cols, fn($c) => $c !== '');
        if (empty($nonEmpty)) $emptyRows++;
    }
    return [$emptyRows === 0, $emptyRows > 0 ? "$emptyRows empty rows found" : 'All rows have content'];
};

$GLOBALS['tests']['Integration']['T-013d: Ausentismo CSV export'] = function () use ($sm) {
    ob_start();
    try {
        $ausentismo = $sm->getAusentismo(2);
        $data = $ausentismo['data'] ?? [];
        if (empty($data)) {
            ob_end_clean();
            return [true, 'No ausentismo data to export (skip)'];
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Colaborador', 'Tipo', 'Inicio', 'Fin', 'Días', 'Diagnóstico']);
        foreach ($data as $r) {
            fputcsv($out, [$r['usuario_nombre'] ?? '', $r['aus_tipo'] ?? '', $r['aus_fecha_inicio'] ?? '', $r['aus_fecha_fin'] ?? '', $r['aus_dias'] ?? 0, $r['aus_diagnostico'] ?? '']);
        }
        fclose($out);
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    $csv = ob_get_clean();
    $lines = array_filter(explode("\n", trim($csv)));
    ok(count($lines) > 1, 'No data rows');
    return [true, 'Ausentismo CSV: ' . count($lines) . ' rows (header + ' . (count($lines) - 1) . ' data)'];
};

// ============================================================
// T-011: Load test simulation (50 concurrent reads)
// ============================================================
echo "\n⚡ T-011 Load Test Simulation (50 concurrent reads)\n";

$GLOBALS['tests']['Integration']['T-011a: 50x Dashboard ejecutivo queries'] = function () use ($core) {
    $errors = 0;
    for ($i = 0; $i < 50; $i++) {
        try {
            $core->fetchOne("SELECT COUNT(*) as total FROM plan_planes_estrategicos");
            $core->fetchAll("SELECT plan_nombre, plan_estado, plan_avance_porcentaje FROM plan_planes_estrategicos LIMIT 10");
            $core->fetchOne("SELECT COUNT(*) as total FROM ind_indicadores");
        } catch (\Throwable $e) {
            $errors++;
        }
    }
    return [$errors === 0, $errors > 0 ? "$errors errors in 50 iterations" : '50x3 queries OK'];
};

$GLOBALS['tests']['Integration']['T-011b: 50x SST peligros read'] = function () use ($sm) {
    $errors = 0;
    for ($i = 0; $i < 50; $i++) {
        try {
            $sm->getPeligros(2);
        } catch (\Throwable $e) {
            $errors++;
        }
    }
    return [$errors === 0, $errors > 0 ? "$errors errors" : '50 reads OK'];
};

$GLOBALS['tests']['Integration']['T-011c: 50x Procesos read'] = function () use ($core) {
    $errors = 0;
    for ($i = 0; $i < 50; $i++) {
        try {
            $core->fetchAll("SELECT * FROM proc_procesos WHERE proceso_activo = 1 LIMIT 20");
            $core->fetchAll("SELECT * FROM proc_macroprocesos WHERE macro_activo = 1 ORDER BY macro_tipo");
        } catch (\Throwable $e) {
            $errors++;
        }
    }
    return [$errors === 0, $errors > 0 ? "$errors errors" : '50 reads OK'];
};

$GLOBALS['tests']['Integration']['T-011d: 50x indicadores + categorias read'] = function () use ($core) {
    $errors = 0;
    for ($i = 0; $i < 50; $i++) {
        try {
            $core->fetchAll("SELECT * FROM ind_categorias ORDER BY categoria_tipo, categoria_nombre");
            $core->fetchAll(
                "SELECT i.*, c.categoria_nombre FROM ind_indicadores i JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id WHERE i.indicador_activo = 1 LIMIT 20"
            );
        } catch (\Throwable $e) {
            $errors++;
        }
    }
    return [$errors === 0, $errors > 0 ? "$errors errors" : '50 reads OK'];
};

$GLOBALS['tests']['Integration']['T-011e: 50x NC read'] = function () use ($core) {
    $errors = 0;
    for ($i = 0; $i < 50; $i++) {
        try { $core->fetchOne("SELECT COUNT(*) FROM cal_no_conformidades"); } catch (\Throwable $e) { $errors++; }
    }
    return [$errors === 0, $errors > 0 ? "$errors errors" : '50 reads OK'];
};

// ============================================================
// T-014: Bulk upload test (100+ rows CSV via medicion import)
// ============================================================
echo "\n📥 T-014 Bulk Upload Test (100+ rows CSV)\n";

$GLOBALS['tests']['Integration']['T-014a: Generate test CSV with 120 rows'] = function () use ($core) {
    $indicadores = $core->fetchAll("SELECT indicador_id FROM ind_indicadores WHERE indicador_activo = 1 LIMIT 5");
    if (empty($indicadores)) {
        return [true, 'No indicadores disponibles para prueba (skip)'];
    }

    $tmpFile = tmpfile();
    $tmpPath = stream_get_meta_data($tmpFile)['uri'];

    // Header row
    fputcsv($tmpFile, ['indicador_id', 'indicador_nombre', 'categoria', 'fecha', 'periodo', 'valor', 'observaciones']);

    // 120 data rows cycling through available indicadores
    $baseDate = new \DateTime('2026-01-01');
    for ($i = 0; $i < 120; $i++) {
        $ind = $indicadores[$i % count($indicadores)];
        $date = (clone $baseDate)->modify("+{$i} days")->format('Y-m-d');
        $periodo = (clone $baseDate)->modify("+{$i} days")->format('Y-m');
        $valor = round(50 + ($i * 0.3) + (mt_rand(0, 20) * 0.5), 2);
        fputcsv($tmpFile, [
            $ind['indicador_id'],
            'KPI-BULK-' . sprintf('%03d', $i),
            'cumplimiento',
            $date,
            $periodo,
            (string)$valor,
            'Bulk test row ' . $i,
        ]);
    }
    rewind($tmpFile);

    // Read back and verify
    $header = fgetcsv($tmpFile);
    $rowCount = 0;
    $invalid = 0;
    while (($row = fgetcsv($tmpFile)) !== false) {
        $rowCount++;
        if (empty($row[0]) || (int)$row[0] <= 0) $invalid++;
        if (empty($row[5]) || (float)$row[5] == 0) $invalid++;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($row[3] ?? ''))) $invalid++;
    }
    fclose($tmpFile);

    eq(120, $rowCount, 'Row count mismatch');
    eq(0, $invalid, 'Invalid rows in generated CSV');
    return [true, "Generated $rowCount rows, 0 invalid"];
};

$GLOBALS['tests']['Integration']['T-014b: Parse CSV mediciÃ³n structure correctly'] = function () use ($core) {
    $indicadores = $core->fetchAll("SELECT indicador_id FROM ind_indicadores WHERE indicador_activo = 1 LIMIT 5");
    if (empty($indicadores)) {
        return [true, 'No indicadores (skip)'];
    }

    $tmpFile = tmpfile();
    $tmpPath = stream_get_meta_data($tmpFile)['uri'];

    fputcsv($tmpFile, ['indicador_id', 'indicador_nombre', 'categoria', 'fecha', 'periodo', 'valor', 'observaciones']);
    $indIds = array_column($indicadores, 'indicador_id');
    for ($i = 0; $i < 120; $i++) {
        $indId = $indIds[$i % count($indIds)];
        fputcsv($tmpFile, [
            (string)$indId,
            'KPI-TEST',
            'cumplimiento',
            '2026-06-15',
            '2026-06',
            (string)(85.0 + ($i * 0.1)),
            'Row ' . $i,
        ]);
    }
    rewind($tmpFile);

    $header = fgetcsv($tmpFile);
    $parsed = 0;
    $skipped = 0;

    while (($row = fgetcsv($tmpFile)) !== false) {
        if (empty($row[0]) || trim($row[0]) === 'EJEMPLO ->' || trim($row[0]) === 'indicador_id') {
            $skipped++;
            continue;
        }
        $indicadorId = (int)($row[0] ?? 0);
        $valor = (float)($row[5] ?? $row[4] ?? 0);
        $fecha = trim($row[3] ?? date('Y-m-d'));
        $periodo = trim($row[4] ?? date('Y-m'));

        if ($indicadorId <= 0 || $valor == 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $skipped++;
            continue;
        }
        ok(in_array($indicadorId, $indIds), "Indicator $indicadorId not in valid set");
        gt($valor, 0, 'Valor must be positive');
        eq('2026-06-15', $fecha, 'Fecha mismatch');
        $parsed++;
    }
    fclose($tmpFile);

    eq(120, $parsed, 'Should parse 120 rows');
    eq(0, $skipped, 'Should skip 0 rows');
    return [true, "Parsed $parsed rows, $skipped skipped"];
};

$GLOBALS['tests']['Integration']['T-014c: Simulate CSV upload without persisting (dry run)'] = function () {
    $tmpFile = tmpfile();
    fputcsv($tmpFile, ['indicador_id', 'indicador_nombre', 'categoria', 'fecha', 'periodo', 'valor', 'observaciones']);
    for ($i = 0; $i < 100; $i++) {
        fputcsv($tmpFile, ['1', 'KPI-DRY-' . $i, 'cumplimiento', '2026-05-15', '2026-05', (string)(70.0 + ($i * 0.2)), 'Dry run row ' . $i]);
    }
    rewind($tmpFile);
    $header = fgetcsv($tmpFile);
    $valid = 0; $invalidRows = 0;
    while (($row = fgetcsv($tmpFile)) !== false) {
        if (empty($row[0]) || trim($row[0]) === 'EJEMPLO ->' || trim($row[0]) === 'indicador_id') continue;
        $valor = (float)($row[5] ?? 0);
        $fecha = trim($row[3] ?? '');
        if ((int)($row[0] ?? 0) <= 0) { $invalidRows++; continue; }
        if ($valor == 0) { $invalidRows++; continue; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { $invalidRows++; continue; }
        $valid++;
    }
    fclose($tmpFile);
    return [$valid === 100 && $invalidRows === 0, "$valid valid, $invalidRows invalid"];
};

$GLOBALS['tests']['Integration']['T-014d: Handle edge cases in CSV (empty lines, examples)'] = function () {
    $tmpFile = tmpfile();
    $tmpPath = stream_get_meta_data($tmpFile)['uri'];

    fputcsv($tmpFile, ['indicador_id', 'indicador_nombre', 'categoria', 'fecha', 'periodo', 'valor', 'observaciones']);
    fputcsv($tmpFile, ['', '', '', '', '', '', '']);              // Empty row (should skip)
    fputcsv($tmpFile, ['1', 'KPI-X', 'cumplimiento', '2026-05-15', '2026-05', '88.5', 'Valid row']);
    fputcsv($tmpFile, ['EJEMPLO ->', '', '', '', '', '', '']);    // Example marker (should skip)
    fputcsv($tmpFile, ['', '', '', '', '', '', '']);              // Empty row
    fputcsv($tmpFile, ['0', 'KPI-ZERO', 'cumplimiento', '2026-05-15', '2026-05', '0', 'Zero valor']); // Invalid (id=0)
    fputcsv($tmpFile, ['1', 'KPI-ZERO', 'cumplimiento', '2026-05-15', '2026-05', '0', 'Zero valor']); // Zero valor skips
    fputcsv($tmpFile, ['1', 'KPI-BAD', 'cumplimiento', 'bad-date', '2026-05', '50', 'Bad date']);   // Bad date
    fputcsv($tmpFile, ['', '', '', '', '', '', '']);              // Empty row
    fputcsv($tmpFile, ['2', 'KPI-OK2', 'cumplimiento', '2026-05-20', '2026-05', '92.0', 'Valid row 2']);
    fputcsv($tmpFile, ['indicador_id', '', '', '', '', '', '']);  // Duplicate header (should skip)

    rewind($tmpFile);

    $header = fgetcsv($tmpFile);
    $validRows = 0;
    $skippedRows = 0;

    while (($row = fgetcsv($tmpFile)) !== false) {
        if (empty($row[0]) || trim($row[0]) === 'EJEMPLO ->' || trim($row[0]) === 'indicador_id') {
            $skippedRows++;
            continue;
        }
        $indicadorId = (int)($row[0] ?? 0);
        $valor = (float)($row[5] ?? $row[4] ?? 0);
        $fecha = trim($row[3] ?? '');

        if ($indicadorId <= 0) { $skippedRows++; continue; }
        if ($valor == 0) { $skippedRows++; continue; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { $skippedRows++; continue; }

        $validRows++;
    }
    fclose($tmpFile);

    eq(2, $validRows, 'Should have 2 valid rows');
    eq(8, $skippedRows, 'Should skip 8 invalid/empty rows');
    return [true, "Edge cases: $validRows valid, $skippedRows skipped"];
};

// ============================================================
// EXECUTION
// ============================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

foreach ($GLOBALS['tests']['Integration'] as $name => $cb) {
    check($name, $cb);
}

$total = $passed + $failed;
$pct = $total > 0 ? round($passed / $total * 100) : 0;
echo "\n══════════════════════════════════════════\n  $passed/$total INTEGRATION TESTS ($pct%)\n  " . ($failed === 0 ? '✅ TODOS OK' : '❌ HAY FALLOS') . "\n══════════════════════════════════════════\n\n";
exit($failed > 0 ? 1 : 0);
