<?php
/**
 * Smoke Test — EstrateGIA v2.1
 * php tests/smoke_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/lib/EstrateGiaCore.php';
require_once BASE_PATH . '/lib/PlanManager.php';
require_once BASE_PATH . '/lib/IndicatorManager.php';
require_once BASE_PATH . '/lib/TwoFactorAuth.php';
require_once BASE_PATH . '/lib/Logger.php';

$passed = 0; $failed = 0;
function test(string $name, callable $fn): void {
    global $passed, $failed;
    try { $fn(); echo "  ✅ $name\n"; $passed++; }
    catch (\Throwable $e) { echo "  ❌ $name: " . $e->getMessage() . "\n"; $failed++; }
}

echo "=== EstrateGIA Smoke Test ===\n\n";

// DB
$core = EstrateGiaCore::getInstance();
test('Conexión DB activa', fn() => assert($core->fetchOne('SELECT 1') !== false));
test('Tabla plan_objetivos existe', fn() => assert($core->fetchOne("SHOW TABLES LIKE 'plan_objetivos'") !== false));
test('Tabla ind_indicadores existe', fn() => assert($core->fetchOne("SHOW TABLES LIKE 'ind_indicadores'") !== false));
test('Tabla plan_estrategias existe', fn() => assert($core->fetchOne("SHOW TABLES LIKE 'plan_estrategias'") !== false));
test('Tabla plan_fases existe', fn() => assert($core->fetchOne("SHOW TABLES LIKE 'plan_fases'") !== false));
test('Tabla sys_usuarios existe', fn() => assert($core->fetchOne("SHOW TABLES LIKE 'sys_usuarios'") !== false));
test('Tabla sys_audit_log existe', fn() => assert($core->fetchOne("SHOW TABLES LIKE 'sys_audit_log'") !== false));

// Managers
$pm = new PlanManager();
test('getPlanes() retorna array', fn() => assert(is_array($pm->getPlanes())));
test('getObjetivos(5) retorna >0', fn() => assert(count($pm->getObjetivos(5)) > 0));
test('getFases(5) retorna 7', fn() => assert(count($pm->getFases(5)) === 7));
test('getPlanTree(5) retorna array', fn() => assert(is_array($pm->getPlanTree(5))));

$im = new IndicatorManager();
test('getIndicadores(5) retorna >0', fn() => assert(count($im->getIndicadores(5)) > 0));
test('getCategorias() retorna 4', fn() => assert(count($im->getCategorias()) === 4));

// 2FA
$secret = TwoFactorAuth::generateSecret();
test('2FA generateSecret 16 chars', fn() => assert(strlen($secret) >= 16));
test('2FA verify código inválido', fn() => assert(TwoFactorAuth::verify($secret, '000000') === false));

// Logger
$logger = new Logger();
test('Logger escribe sin errores', fn() => $logger->info('Smoke test ejecutado'));

// Export
test('Archivo SimpleXLSX existe', fn() => assert(file_exists(BASE_PATH . '/lib/SimpleXLSX.php')));
test('Archivo ExportManager existe', fn() => assert(file_exists(BASE_PATH . '/lib/ExportManager.php')));

// Assets
test('CSS Bootstrap existe', fn() => assert(file_exists(BASE_PATH . '/public/assets/css/bootstrap.min.css')));
test('CSS App existe', fn() => assert(file_exists(BASE_PATH . '/public/assets/css/app.css')));
test('JS Bootstrap existe', fn() => assert(file_exists(BASE_PATH . '/public/assets/js/bootstrap.bundle.min.js')));
test('JS Chart existe', fn() => assert(file_exists(BASE_PATH . '/public/assets/js/chart.min.js')));

echo "\n=== Resultado: $passed/$((passed+failed)) tests pasados ===\n";
$failedOrig = $failed; $passedOrig = $passed;

// ===== ALL LIBS LOAD TEST =====
echo "\n📚 All Libraries:\n";
$allLibs = ['EstrateGiaCore','BaseHSEManager','AmbientalManager','SSTManager','PlanManager','IndicatorManager','FinancialManager','AIManager','DocManager','CRMManager','ProcessManager','ProveedoresManager','AuthService','CacheService','Logger','ExportManager','SimpleXLSX','TwoFactorAuth','WebhookService'];
foreach ($allLibs as $lib) {
    $file = BASE_PATH . "/lib/$lib.php";
    if (file_exists($file)) {
        require_once $file;
        echo "  ✅ $lib loaded\n"; $passed++;
    } else {
        echo "  ⚠️  $lib file missing\n";
    }
}

// ===== ALL CONTROLLERS LOAD TEST =====
echo "\n🎮 All Controllers:\n";
$allCtrls = ['Planeacion','Indicadores','Evaluacion','Procesos','Documentos','Calidad','Soporte','Extras','CRM','Admin','Config','Ambiental','SST','NC','Proveedores','IA','Workbench','Generador','Acreditacion','Calendario','Fase','Integraciones','Dashboard','Medicion','SIG'];
foreach ($allCtrls as $ctrl) {
    $file = BASE_PATH . "/src/Controllers/{$ctrl}Controller.php";
    if (file_exists($file)) { echo "  ✅ $ctrl\n"; $passed++; }
    else { echo "  ❌ $ctrl MISSING\n"; $failed++; }
}

echo "\n=== UPDATED RESULT: $passed/$((passed+failed)) tests ===\n";
