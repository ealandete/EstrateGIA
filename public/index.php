<?php
/**
 * EstrateGIA - Front Controller
 */

session_start();

if (!isset($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
    catch (\Throwable $e) { $_SESSION['csrf_token'] = md5(time().mt_rand()); }
}
$csrfToken = $_SESSION['csrf_token'];

define('IS_AJAX', ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

define('BASE_PATH', dirname(__DIR__));
define('APP_DEBUG', false); // Cambiar a false en produccion

require_once BASE_PATH . '/lib/EstrateGiaCore.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Router.php';
// N1SupportEngine removed

// ===== Error handler simple =====
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<div style='background:#fff3cd;border:1px solid #ffc107;padding:10px;margin:5px;border-radius:4px;font-family:monospace;font-size:13px'><strong>Warning:</strong> " .
             htmlspecialchars($message) . "<br><small>{$file}:{$line}</small></div>";
    }
    return true;
});

set_exception_handler(function($e) {
    http_response_code(500);
    if (str_starts_with(($_SERVER['REQUEST_URI']??''), '/api/')) {
        header('Content-Type: application/json');
        echo json_encode(['error'=>$e->getMessage()]);
    } else {
        echo '<h1>500 - Error interno</h1><p>'.$e->getMessage().'</p><pre>'.$e->getTraceAsString().'</pre>';
    }
    exit;
});
require_once BASE_PATH . '/lib/PlanManager.php';
require_once BASE_PATH . '/lib/IndicatorManager.php';
require_once BASE_PATH . '/lib/DocManager.php';

$router = new Router();

// ===== SETUP (Configuración Inicial) =====
$router->get('/setup', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->index(); });
$router->get('/setup/requisitos', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->requisitos(); });
$router->get('/setup/empresa', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->empresa(); });
$router->post('/setup/empresa', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->empresa(); });
$router->get('/setup/usuario', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->usuario(); });
$router->post('/setup/usuario', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->usuario(); });
$router->get('/setup/finalizar', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->finalizar(); });
$router->post('/setup/finalizar', function () { require_once BASE_PATH.'/src/Controllers/SetupController.php'; (new SetupController())->finalizar(); });

// ===== PLANEACIÓN =====
$router->get('/planeacion', function () { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->index(); });
$router->get('/planeacion/crear', function () { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->create(); });
$router->post('/planeacion/store', function () { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->store(); });
$router->post('/planeacion/crear-empresa', function () { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->crearEmpresa(); });
$router->get('/planeacion/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->detail((int)$id); });
$router->get('/planeacion/{id}/editar', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->edit((int)$id); });
$router->post('/planeacion/{id}/update', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->update((int)$id); });
$router->post('/planeacion/{id}/eliminar', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->delete((int)$id); });
$router->get('/planeacion/{id}/reporte', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->reporte((int)$id); });
$router->get('/planeacion/{id}/foda', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->foda((int)$id); });
$router->get('/planeacion/{id}/pestel', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->pestel((int)$id); });
$router->get('/planeacion/{id}/gantt', function ($id) { require_once BASE_PATH.'/src/Controllers/PlaneacionController.php'; (new PlaneacionController())->gantt((int)$id); });
$router->get('/planeacion/{id}/verificar-hash', function ($id) {
    Auth::guard();
    $core = EstrateGiaCore::getInstance();
    $plan = $core->fetchOne("SELECT * FROM plan_planes_estrategicos WHERE plan_id = :id", ['id' => (int)$id]);
    if (!$plan) { header('Content-Type: application/json'); echo json_encode(['valid' => false, 'error' => 'Plan no encontrado']); exit; }
    $storedHash = $plan['integrity_hash'] ?? '';
    unset($plan['integrity_hash'], $plan['plan_id'], $plan['created_at'], $plan['updated_at']);
    $computedHash = hash('sha256', json_encode($plan));
    $valid = $storedHash && hash_equals($storedHash, $computedHash);
    header('Content-Type: application/json');
    echo json_encode(['valid' => $valid, 'hash' => $storedHash, 'expected' => $valid ? $storedHash : $computedHash]);
    exit;
});

// ===== WORKBENCH =====
$router->get('/workbench/{planId}/{faseId}', function ($planId, $faseId) { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->workbench((int)$planId, (int)$faseId); });
$router->post('/tools/completar-fase', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->completarFase(); });
$router->post('/tools/save-foda', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->saveFoda(); });
$router->post('/tools/save-scenarios', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->saveScenarios(); });
$router->post('/tools/save-objetivo', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->saveObjetivo(); });
$router->post('/tools/edit-objetivo', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->editObjetivo(); });
$router->post('/tools/delete-objetivo', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->deleteObjetivo(); });
$router->post('/tools/save-indicador', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->saveIndicador(); });
$router->post('/tools/edit-indicador', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->editIndicador(); });
$router->post('/tools/delete-indicador', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->deleteIndicador(); });
$router->post('/tools/save-estrategia', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->saveEstrategia(); });
$router->post('/tools/edit-estrategia', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->editEstrategia(); });
$router->post('/tools/delete-estrategia', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->deleteEstrategia(); });
$router->post('/tools/save-okr', function () { require_once BASE_PATH.'/src/Controllers/WorkbenchController.php'; (new WorkbenchController())->saveOkr(); });

// ===== GENERADOR IA =====
$router->post('/generar', function () { require_once BASE_PATH.'/src/Controllers/GeneradorController.php'; (new GeneradorController())->generar(); });
$router->post('/ia/preguntar', function () { require_once BASE_PATH.'/src/Controllers/IAController.php'; (new IAController())->preguntar(); });

// ===== INDICADORES =====
$router->get('/indicadores', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->index(); });
$router->get('/indicadores/ver/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->detail((int)$id); });
$router->post('/indicadores/crear', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->crear(); });
$router->get('/indicadores/plantilla-mediciones', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->plantillaMediciones(); });
$router->post('/indicadores/carga-mediciones', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->cargaMediciones(); });
$router->get('/mediciones', function () { require_once BASE_PATH.'/src/Controllers/MedicionController.php'; (new MedicionController())->index(); });
$router->post('/indicadores/meta/crear', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->crearMeta(); });
$router->post('/mediciones/registrar', function () { require_once BASE_PATH.'/src/Controllers/MedicionController.php'; (new MedicionController())->registrar(); });
$router->get('/mediciones/plantilla', function () { require_once BASE_PATH.'/src/Controllers/MedicionController.php'; (new MedicionController())->descargarPlantilla(); });
$router->post('/mediciones/subir-csv', function () { require_once BASE_PATH.'/src/Controllers/MedicionController.php'; (new MedicionController())->subirCSV(); });
$router->post('/mediciones/mineria/ejecutar', function () { require_once BASE_PATH.'/src/Controllers/MedicionController.php'; (new MedicionController())->ejecutarMineria(); });

// ===== EVALUACIÓN =====
$router->get('/evaluacion', function () { require_once BASE_PATH.'/src/Controllers/EvaluacionController.php'; (new EvaluacionController())->index(); });
$router->post('/evaluacion/guardar', function () { require_once BASE_PATH.'/src/Controllers/EvaluacionController.php'; (new EvaluacionController())->guardar(); });
$router->get('/evaluacion/procesos/{id}', function ($id) { $_GET['nivel'] = 'procesos'; $_GET['proceso_id'] = $id; require_once BASE_PATH.'/src/Controllers/EvaluacionController.php'; (new EvaluacionController())->index(); });

// ===== PHVA (Ciclo de Mejora Continua) =====
$router->get('/phva', function () { require_once BASE_PATH.'/src/Controllers/PhvaController.php'; (new PhvaController())->index(); });

// ===== PROCESOS =====
$router->get('/procesos', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->index(); });
$router->get('/procesos/ver/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->verProceso((int)$id); });
$router->post('/procesos/crear-macroproceso', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->crearMacroproceso(); });
$router->post('/procesos/editar-macroproceso', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->editarMacroproceso(); });
$router->post('/procesos/eliminar-macroproceso', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->eliminarMacroproceso(); });
$router->post('/procesos/crear-proceso', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->crearProceso(); });
$router->post('/procesos/editar-proceso', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->editarProceso(); });
$router->post('/procesos/crear-procedimiento', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->crearProcedimiento(); });
$router->post('/procesos/crear-tarea', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->crearTarea(); });
$router->post('/procesos/eliminar-tarea', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->eliminarTarea(); });
$router->get('/procesos/workflows', function () { require_once BASE_PATH.'/src/Controllers/ProcesosController.php'; (new ProcesosController())->workflows(); });

// ===== CALIDAD =====
$router->get('/calidad', function () { require_once BASE_PATH.'/src/Controllers/CalidadController.php'; (new CalidadController())->dashboard(); });
$router->get('/calidad/autoevaluacion', function () { require_once BASE_PATH.'/src/Controllers/CalidadController.php'; (new CalidadController())->autoevaluacion(); });
$router->post('/calidad/autoevaluacion/guardar', function () { require_once BASE_PATH.'/src/Controllers/CalidadController.php'; (new CalidadController())->guardarAutoevaluacion(); });
$router->get('/calidad/pamec', function () { require_once BASE_PATH.'/src/Controllers/AcreditacionController.php'; (new AcreditacionController())->pamec(); });
$router->get('/calidad/riesgos', function () { require_once BASE_PATH.'/src/Controllers/AcreditacionController.php'; (new AcreditacionController())->riesgos(); });
$router->get('/calidad/estandares', function () { require_once BASE_PATH.'/src/Controllers/AcreditacionController.php'; (new AcreditacionController())->estandares(); });
$router->get('/calidad/reporte', function () { require_once BASE_PATH.'/src/Controllers/CalidadController.php'; (new CalidadController())->reporte(); });
$router->post('/calidad/actividad/crear', function () { require_once BASE_PATH.'/src/Controllers/CalidadController.php'; (new CalidadController())->crearActividad(); });
$router->post('/calidad/reporte/crear', function () { require_once BASE_PATH.'/src/Controllers/CalidadController.php'; (new CalidadController())->crearReporte(); });
$router->post('/calidad/estandares/crear', function () { require_once BASE_PATH.'/src/Controllers/AcreditacionController.php'; (new AcreditacionController())->crearEstandar(); });
$router->post('/calidad/estandares/eliminar', function () { require_once BASE_PATH.'/src/Controllers/AcreditacionController.php'; (new AcreditacionController())->eliminarEstandar(); });
$router->post('/calidad/pamec/crear', function () { require_once BASE_PATH.'/src/Controllers/AcreditacionController.php'; (new AcreditacionController())->crearPamec(); });
$router->post('/calidad/riesgos/crear', function () { require_once BASE_PATH.'/src/Controllers/AcreditacionController.php'; (new AcreditacionController())->crearRiesgo(); });

// ===== NC =====
$router->get('/nc', function () { require_once BASE_PATH.'/src/Controllers/NCController.php'; (new NCController())->index(); });
$router->post('/nc/crear', function () { require_once BASE_PATH.'/src/Controllers/NCController.php'; (new NCController())->crear(); });
$router->post('/nc/actualizar/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/NCController.php'; (new NCController())->actualizar((int)$id); });

// ===== PROVEEDORES =====
$router->get('/proveedores', function () { require_once BASE_PATH.'/src/Controllers/ProveedoresController.php'; (new ProveedoresController())->index(); });
$router->get('/proveedores/ver/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/ProveedoresController.php'; (new ProveedoresController())->ver((int)$id); });
$router->post('/proveedores/crear', function () { require_once BASE_PATH.'/src/Controllers/ProveedoresController.php'; (new ProveedoresController())->crearProveedor(); });
$router->post('/proveedores/evaluar', function () { require_once BASE_PATH.'/src/Controllers/ProveedoresController.php'; (new ProveedoresController())->evaluar(); });

// ===== DOCUMENTOS =====
$router->get('/documentos', function () { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->index(); });
$router->get('/documentos/crear', function () { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->crear(); });
$router->post('/documentos/store', function () { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->store(); });
$router->get('/documentos/ver/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->ver((int)$id); });
$router->get('/documentos/aprobar/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->aprobar((int)$id); });
$router->post('/documentos/rechazar/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->rechazar((int)$id); });
$router->post('/documentos/solicitar-revision/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->solicitarRevision((int)$id); });
$router->post('/documentos/nueva-version/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->nuevaVersion((int)$id); });
$router->post('/documentos/firmar/{id}', function ($id) {
    Auth::guard();
    $core = EstrateGiaCore::getInstance();
    $userId = Auth::userId();
    $userName = Auth::userName();
    $userCargo = Auth::userCargo();
    $firma = hash('sha256', $id . '|' . $userId . '|' . date('c') . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $fecha = date('Y-m-d H:i:s');
    $core->execute(
        "UPDATE doc_documentos SET documento_firma_hash = :fh, documento_firmado_por = :fp, documento_firmado_fecha = :ff, documento_firmado_cargo = :fc WHERE documento_id = :id",
        ['fh' => $firma, 'fp' => $userId, 'ff' => $fecha, 'fc' => $userCargo, 'id' => (int)$id]
    );
    $core->audit('firma_documento', 'doc_documentos', (int)$id, null, ['firma' => $firma, 'firmado_por' => $userId], 'Firma electrónica');
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'firma' => $firma, 'fecha' => $fecha, 'firmado_por' => $userName]);
    exit;
});

// ===== SST =====
$router->get('/sst', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->index(); });
$router->post('/sst/incidente', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->reportarIncidente(); });
$router->post('/sst/incidente/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->reportarIncidente(); });
$router->post('/sst/incidente/eliminar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->cerrarIncidente(); });
$router->post('/sst/incidente/investigar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->investigarIncidente(); });
$router->post('/sst/indicador/crear', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearIndicador(); });
$router->post('/sst/peligro/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearPeligro(); });
$router->post('/sst/peligro/eliminar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->eliminarPeligro(); });
$router->post('/sst/plan/crear', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearPlanTrabajo(); });
$router->post('/sst/plan/actualizar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->actualizarPlanTrabajo(); });
$router->post('/sst/plan/actividad/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearActividad(); });
$router->post('/sst/plan/actividad/avance', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->actualizarActividad(); });
$router->post('/sst/autoevaluacion/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->guardarAutoevaluacion(); });
$router->get('/sst/exportar/csv', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->exportarCSV(); });
$router->post('/sst/ausentismo/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearAusentismo(); });
$router->post('/sst/capacitacion/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearCapacitacion(); });
$router->post('/sst/examen/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearExamen(); });
$router->post('/sst/inspeccion/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearInspeccion(); });
$router->post('/sst/emergencia/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearEmergencia(); });
$router->post('/sst/normatividad/guardar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->crearReqLegal(); });
$router->post('/sst/reporte/generar', function () { require_once BASE_PATH.'/src/Controllers/SSTController.php'; (new SSTController())->generarReporte(); });

// ===== AMBIENTAL =====
$router->get('/ambiental', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->index(); });
$router->post('/ambiental/registrar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->registrarMedicion(); });
$router->post('/ambiental/registro/guardar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->registrarMedicion(); });
$router->post('/ambiental/indicador/crear', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearIndicador(); });
$router->post('/ambiental/aspecto/guardar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearAspecto(); });
$router->post('/ambiental/aspecto/eliminar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->eliminarAspecto(); });
$router->post('/ambiental/plan/guardar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearPlanGestion(); });
$router->post('/ambiental/programa/guardar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearPrograma(); });
$router->post('/ambiental/norma/guardar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearReqLegal(); });
$router->post('/ambiental/auditoria/guardar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearAuditoria(); });
$router->post('/ambiental/reporte/generar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->generarReporte(); });
$router->post('/ambiental/autoevaluacion/guardar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->guardarAutoevaluacion(); });
// Ambiental - Editar aspecto
$router->post('/ambiental/aspecto/editar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->editarAspecto(); });
// Ambiental - Huella de Carbono ISO 14064
$router->post('/ambiental/emision/crear', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearEmisionGEI(); });
$router->post('/ambiental/emision/editar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->editarEmisionGEI(); });
$router->post('/ambiental/emision/eliminar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->eliminarEmisionGEI(); });
// Ambiental - Controles
$router->post('/ambiental/control/crear', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearControl(); });
$router->post('/ambiental/control/editar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->editarControl(); });
$router->post('/ambiental/control/eliminar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->eliminarControl(); });
// Ambiental - Planes de Trabajo
$router->post('/ambiental/plan-trabajo/crear', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearPlanTrabajo(); });
$router->post('/ambiental/plan-trabajo/editar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->editarPlanTrabajo(); });
$router->post('/ambiental/plan-trabajo/actividad/crear', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearActividadPlan(); });
$router->post('/ambiental/plan-trabajo/actividad/editar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->editarActividadPlan(); });
// Ambiental - Metas Ambientales
$router->post('/ambiental/meta/crear', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->crearMetaAmbiental(); });
$router->post('/ambiental/meta/editar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->editarMetaAmbiental(); });
// Ambiental - Actualizar existentes
$router->post('/ambiental/plan/actualizar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->actualizarPlanGestion(); });
$router->post('/ambiental/programa/actualizar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->actualizarPrograma(); });
$router->post('/ambiental/norma/actualizar', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->actualizarReqLegal(); });
// Ambiental - APIs JSON
$router->get('/ambiental/api/huella', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->apiHuellaCarbono(); });
$router->get('/ambiental/api/indicadores-carbono', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->apiIndicadoresCarbono(); });
$router->get('/ambiental/api/dashboard', function () { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->apiDashboardAmbiental(); });
// Ambiental - Reporte
$router->get('/ambiental/reporte/descargar/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/AmbientalController.php'; (new AmbientalController())->descargarReporte((int)$id); });

// ===== SOPORTE =====
$router->get('/soporte', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->index(); });
$router->get('/soporte/tickets', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->tickets(); });
$router->get('/soporte/crear', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->crearTicket(); });
$router->post('/soporte/crear', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->crearTicket(); });
$router->get('/soporte/ver/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->verTicket((int)$id); });
$router->post('/soporte/responder/{id}', function ($id) {
    $_POST['_accion'] = 'responder';
    $_POST['respuesta'] = $_POST['respuesta'] ?? $_POST['mensaje'] ?? '';
    $_SERVER['REQUEST_URI'] = '/soporte/ver/' . (int)$id;
    require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->verTicket((int)$id);
});
$router->post('/soporte/escalar/{id}', function ($id) {
    $_POST['_accion'] = 'escalar';
    $_POST['nivel_escalar'] = $_POST['nivel_escalar'] ?? $_POST['nivel'] ?? 'N2';
    $_SERVER['REQUEST_URI'] = '/soporte/ver/' . (int)$id;
    require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->verTicket((int)$id);
});
$router->post('/soporte/cerrar/{id}', function ($id) {
    $_POST['_accion'] = 'cerrar';
    $_POST['resolucion'] = $_POST['resolucion'] ?? $_POST['mensaje'] ?? '';
    $_SERVER['REQUEST_URI'] = '/soporte/ver/' . (int)$id;
    require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->verTicket((int)$id);
});
$router->get('/soporte/kb', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->kb(); });
$router->get('/soporte/kb/crear', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->kbCrear(); });
$router->post('/soporte/kb/crear', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->kbCrear(); });
$router->get('/soporte/kb/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->kbArticulo((int)$id); });
$router->get('/soporte/reporte/sla', function () { require_once BASE_PATH.'/src/Controllers/SoporteController.php'; (new SoporteController())->reporteSLA(); });

// ===== FINANCIERO =====
$router->get('/financiero', function () {
    Auth::guard();
    $pm = new PlanManager();
    $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 0));
    $planes = $pm->getPlanes(null, 'completado');
    if (!$empresaId && !empty($planes)) $empresaId = (int)$planes[0]['plan_empresa_id'];
    $planId = (int)($_GET['plan_id'] ?? ($planes[0]['plan_id'] ?? 0));
    require_once BASE_PATH.'/lib/FinancialManager.php';
    $fm = new FinancialManager();
    $resumen = $fm->getResumen($planId);
    $presupuestos = $fm->getPresupuesto($planId);
    $porPerspectiva = $fm->getPresupuestoByPerspectiva($planId);
    $pageTitle = 'Gestión Financiera';
    ob_start();
    echo '<div class="container-fluid"><h4 class="mb-3"><i class="fas fa-dollar-sign me-2"></i>Gestión Financiera</h4>';
    echo '<div class="row g-3 mb-4">';
    echo '<div class="col-md-3"><div class="card-box text-center"><div class="card-box-body"><h3 class="text-success">$' . number_format($resumen['total_presupuestado'] ?? 0, 0) . '</h3><small>Presupuestado</small></div></div></div>';
    echo '<div class="col-md-3"><div class="card-box text-center"><div class="card-box-body"><h3 class="text-primary">$' . number_format($resumen['total_ejecutado'] ?? 0, 0) . '</h3><small>Ejecutado</small></div></div></div>';
    $pct = ($resumen['total_presupuestado'] ?? 0) > 0 ? round(($resumen['total_ejecutado'] / $resumen['total_presupuestado']) * 100, 1) : 0;
    echo '<div class="col-md-3"><div class="card-box text-center"><div class="card-box-body"><h3>' . $pct . '%</h3><small>% Ejecución</small></div></div></div>';
    echo '<div class="col-md-3"><div class="card-box text-center"><div class="card-box-body"><h3>' . ($resumen['periodos'] ?? 0) . '</h3><small>Periodos</small></div></div></div>';
    echo '</div>';
    if (!empty($porPerspectiva)) {
        $names = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos','aprendizaje'=>'Aprendizaje'];
        $colors = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1'];
        echo '<h5>Presupuesto por Perspectiva</h5><table class="table table-sm"><thead><tr><th>Perspectiva</th><th>Presupuestado</th><th>Ejecutado</th><th>%</th></tr></thead><tbody>';
        foreach ($porPerspectiva as $pp) {
            $p = $pp['objetivo_perspectiva'] ?? 'sin_clasificar';
            $name = $names[$p] ?? ucfirst($p);
            $color = $colors[$p] ?? '#999';
            $ppct = $pp['presupuestado'] > 0 ? round(($pp['ejecutado'] / $pp['presupuestado']) * 100, 1) : 0;
            echo "<tr><td><span style='color:$color'>●</span> $name</td><td>\$" . number_format($pp['presupuestado'], 0) . "</td><td>\$" . number_format($pp['ejecutado'], 0) . "</td><td>$ppct%</td></tr>";
        }
        echo '</tbody></table>';
    }
    echo '</div>';
    $content = ob_get_clean();
    require BASE_PATH . '/templates/layout.php';
});
$router->post('/financiero/guardar', function () { require_once BASE_PATH.'/lib/FinancialManager.php'; $fm = new FinancialManager(); $id = $fm->savePresupuesto($_POST); header('Content-Type: application/json'); echo json_encode(['success' => true, 'id' => $id]); exit; });
$router->post('/financiero/eliminar', function () { require_once BASE_PATH.'/lib/FinancialManager.php'; $fm = new FinancialManager(); $fm->deletePresupuesto((int)($_POST['id'] ?? 0)); header('Content-Type: application/json'); echo json_encode(['success' => true]); exit; });

// ===== EXPORT PDF =====
$router->get('/planeacion/{id}/pdf', function ($id) {
    require_once BASE_PATH.'/lib/PlanPDF.php';
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:81';
    $html = file_get_contents("$proto://$host/planeacion/$id/reporte?print=1");
    PlanPDF::generarDesdeHTML($html, "reporte_$id.pdf");
    exit;
});

// ===== DOCUMENTACIÓN =====
$router->get('/documentacion', function () {
    require_once BASE_PATH.'/src/Controllers/DocsController.php';
    (new DocsController())->index();
});

// ===== SWAGGER UI =====
$router->get('/docs', function () {
    header('Content-Type: text/html; charset=UTF-8');
    $openapiPath = BASE_PATH . '/docs/openapi.json';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>EstrateGIA API Docs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>body{margin:0;background:#f8fafc}.topbar{background:#1a73e8;color:#fff;padding:12px 24px;font-size:1.2rem;display:flex;justify-content:space-between;align-items:center}.topbar a{color:#fff;font-size:0.8rem;text-decoration:underline}</style></head><body>
    <div class="topbar"><span><i class="fas fa-book"></i> EstrateGIA API v2.1</span><a href="/docs/openapi.json" target="_blank">openapi.json</a></div>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>SwaggerUIBundle({url:"/docs/openapi.json",dom_id:"#swagger-ui",deepLinking:true,defaultModelsExpandDepth:-1,defaultModelExpandDepth:2})</script></body></html>';
    exit;
});

// ===== COMENTARIOS =====
$router->post('/tools/save-comentario', function () {
    $core = EstrateGiaCore::getInstance();
    $core->execute("CREATE TABLE IF NOT EXISTS plan_comentarios (id INT AUTO_INCREMENT PRIMARY KEY, objetivo_id INT NOT NULL, usuario_id INT, comentario TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (objetivo_id) REFERENCES plan_objetivos(objetivo_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $id = $core->insert('plan_comentarios', ['objetivo_id'=>(int)$_POST['obj_id'],'usuario_id'=>$_SESSION['auth_user']['usuario_id']??1,'comentario'=>$_POST['comentario']??'']);
    header('Content-Type: application/json');
    echo json_encode(['success'=>true, 'id'=>$id]);
    exit;
});

// ===== KANBAN =====
$router->get('/tools/kanban', function () {
    Auth::guard();
    $core = EstrateGiaCore::getInstance();
    $planId = (int)($_GET['plan_id'] ?? ($_COOKIE['plan_activo'] ?? 0));
    $actividades = $core->fetchAll(
        "SELECT * FROM plan_kanban WHERE kanban_plan_id = :pid ORDER BY kanban_orden ASC, created_at DESC",
        ['pid' => $planId]
    );
    $core->execute("CREATE TABLE IF NOT EXISTS plan_kanban (
        kanban_id INT AUTO_INCREMENT PRIMARY KEY,
        kanban_plan_id INT NOT NULL,
        kanban_titulo VARCHAR(255) NOT NULL,
        kanban_descripcion TEXT,
        kanban_estado VARCHAR(30) DEFAULT 'pendiente',
        kanban_responsable VARCHAR(150),
        kanban_fecha_limite DATE,
        kanban_orden INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (kanban_plan_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pageTitle = 'Tablero Kanban';
    ob_start();
    require BASE_PATH . '/templates/tools/kanban.php';
    $content = ob_get_clean();
    require BASE_PATH . '/templates/layout.php';
});
$router->post('/tools/kanban/guardar', function () {
    Auth::guard();
    $core = EstrateGiaCore::getInstance();
    $planId = (int)($_POST['plan_id'] ?? ($_COOKIE['plan_activo'] ?? 0));
    $core->insert('plan_kanban', [
        'kanban_plan_id' => $planId,
        'kanban_titulo' => $_POST['titulo'] ?? '',
        'kanban_descripcion' => $_POST['descripcion'] ?? '',
        'kanban_estado' => $_POST['estado'] ?? 'pendiente',
        'kanban_responsable' => $_POST['responsable'] ?? '',
        'kanban_fecha_limite' => $_POST['fecha_limite'] ?? null,
    ]);
    header('Location: /tools/kanban?plan_id=' . $planId . '&ok=1');
    exit;
});
$router->post('/tools/kanban/mover', function () {
    Auth::guard();
    $core = EstrateGiaCore::getInstance();
    $id = (int)($_POST['id']);
    $estado = $_POST['estado'] ?? 'pendiente';
    $core->execute("UPDATE plan_kanban SET kanban_estado = :e WHERE kanban_id = :id", ['e' => $estado, 'id' => $id]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
});

// ===== POWER BI / ANALÍTICA =====
$router->get('/api/powerbi', function () {
    require_once BASE_PATH.'/lib/PlanManager.php';
    require_once BASE_PATH.'/lib/IndicatorManager.php';
    require_once BASE_PATH.'/lib/SSTManager.php';
    require_once BASE_PATH.'/lib/AmbientalManager.php';
    $planId = (int)($_GET['plan_id'] ?? 5);
    $empresaId = (int)($_GET['empresa_id'] ?? 2);
    $pm = new PlanManager(); $im = new IndicatorManager();
    $sm = new SSTManager(); $am = new AmbientalManager();
    $objs = $pm->getObjetivos($planId);
    $inds = $im->getIndicadores($planId);
    $data = [];
    foreach ($objs as $o) {
        $kpis = array_filter($inds, fn($i) => (int)($i['indicador_objetivo_id']??0) === (int)$o['objetivo_id']);
        foreach ($kpis as $k) {
            $data[] = [
                'Tipo' => 'Indicador',
                'Perspectiva' => $o['objetivo_perspectiva'],
                'Objetivo' => $o['objetivo_nombre'],
                'Indicador' => $k['indicador_nombre'],
                'Formula' => $k['indicador_formula'] ?? '',
                'Unidad' => $k['indicador_unidad_medida'] ?? '',
                'Meta' => (float)($k['indicador_rango_maximo'] ?? 0),
                'Frecuencia' => $k['indicador_frecuencia_medicion'] ?? 'mensual'
            ];
        }
    }
    $sstInds = $sm->getIndicadores($empresaId);
    foreach ($sstInds as $si) {
        $data[] = [
            'Tipo' => 'SST',
            'Perspectiva' => 'Seguridad y Salud',
            'Objetivo' => 'Seguridad y Salud en el Trabajo',
            'Indicador' => $si['sst_ind_nombre'] ?? ($si['indicador_nombre'] ?? ''),
            'Formula' => $si['sst_ind_formula'] ?? '',
            'Unidad' => $si['sst_ind_unidad'] ?? '',
            'Meta' => (float)($si['sst_ind_meta'] ?? 0),
            'Frecuencia' => $si['sst_ind_frecuencia'] ?? 'mensual'
        ];
    }
    $ambInds = $am->getIndicadores($empresaId);
    foreach ($ambInds as $ai) {
        $data[] = [
            'Tipo' => 'Ambiental',
            'Perspectiva' => 'Ambiental',
            'Objetivo' => 'Gestión Ambiental',
            'Indicador' => $ai['amb_ind_nombre'] ?? ($ai['indicador_nombre'] ?? ''),
            'Formula' => $ai['amb_ind_formula'] ?? '',
            'Unidad' => $ai['amb_ind_unidad'] ?? '',
            'Meta' => (float)($ai['amb_ind_meta'] ?? 0),
            'Frecuencia' => $ai['amb_ind_frecuencia'] ?? 'mensual'
        ];
    }
    $format = $_GET['format'] ?? 'json';
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        $out = fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
        fputcsv($out, array_keys($data[0]??[]));
        foreach ($data as $r) fputcsv($out, $r); exit;
    }
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
});

// ===== LOGIN =====
$router->get('/login', function () {
    if (Auth::check()) { header('Location: /'); exit; }
    require BASE_PATH . '/public/login.php';
    exit;
});

// ===== PORTAL PROVEEDOR (acceso publico) =====
$router->get('/portal-proveedor', function () {
    require BASE_PATH . '/public/portal-proveedor.php';
    exit;
});

// ===== OTROS MÓDULOS =====
$router->get('/', function () { require_once BASE_PATH.'/src/Controllers/SIGController.php'; (new SIGController())->index(); });
$router->get('/sig', function () { require_once BASE_PATH.'/src/Controllers/SIGController.php'; (new SIGController())->index(); });
$router->get('/dashboard', function () { require_once BASE_PATH.'/src/Controllers/DashboardController.php'; (new DashboardController())->index(); });
$router->get('/dashboards', function () { require_once BASE_PATH.'/src/Controllers/DashboardController.php'; (new DashboardController())->tableros(); });
$router->get('/calendario', function () { require_once BASE_PATH.'/src/Controllers/CalendarioController.php'; (new CalendarioController())->index(); });
$router->get('/ia', function () { require_once BASE_PATH.'/src/Controllers/IAController.php'; (new IAController())->index(); });
$router->get('/formacion', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->formacion(); });
$router->get('/extras/formacion', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->formacion(); });
$router->post('/formacion/crear', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->crearFormacion(); });
$router->get('/satisfaccion', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->satisfaccion(); });
$router->get('/extras/satisfaccion', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->satisfaccion(); });
$router->post('/satisfaccion/crear', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->crearSatisfaccion(); });
$router->get('/crm', function () { require_once BASE_PATH.'/src/Controllers/CRMController.php'; (new CRMController())->index(); });
$router->get('/crm/mineria', function () { require_once BASE_PATH.'/src/Controllers/MedicionController.php'; (new MedicionController())->mineria(); });
$router->post('/crm/conexion/crear', function () { require_once BASE_PATH.'/src/Controllers/IntegracionesController.php'; (new IntegracionesController())->crearConexion(); });

// ===== LICENCIAS =====
$router->get('/licencias', function () { require_once BASE_PATH.'/src/Controllers/LicenciasController.php'; (new LicenciasController())->index(); });
$router->get('/licencias/crear', function () { require_once BASE_PATH.'/src/Controllers/LicenciasController.php'; (new LicenciasController())->create(); });
$router->post('/licencias/crear', function () { require_once BASE_PATH.'/src/Controllers/LicenciasController.php'; (new LicenciasController())->store(); });
$router->get('/licencias/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/LicenciasController.php'; (new LicenciasController())->edit((int)$id); });
$router->post('/licencias/{id}/update', function ($id) { require_once BASE_PATH.'/src/Controllers/LicenciasController.php'; (new LicenciasController())->update((int)$id); });
$router->post('/crm/mapeo/crear', function () { require_once BASE_PATH.'/src/Controllers/IntegracionesController.php'; (new IntegracionesController())->crearMapeo(); });
$router->get('/admin', function () { header('Location: /admin/usuarios'); exit; });
$router->get('/admin/usuarios', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->usuarios(); });
$router->post('/admin/usuarios/crear', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->crearUsuario(); });
$router->get('/admin/roles', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->roles(); });
$router->post('/admin/roles/guardar', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->guardarPermisos(); });
$router->get('/admin/auditoria', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->auditoria(); });
$router->get('/admin/config', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->index(); });
$router->get('/config', function () { header('Location: /admin/config'); exit; });
$router->post('/admin/config/asignar-usuario', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->asignarUsuarioEmpresa(); });
$router->post('/admin/config/crear-empresa', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->crearEmpresa(); });
$router->post('/admin/config/editar-empresa', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->editarEmpresa(); });
$router->post('/admin/config/guardar-personalizacion', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->guardarPersonalizacion(); });

// ===== LICENCIAS (SUPER_ADMIN only) — Politica 23 §5.3 =====

// ===== DESCARGAS =====
$router->get('/descargar-doc/{dir}/{file}', function ($dir, $file) {
    $paths = [BASE_PATH.'/public/docs/', '/home/emilio/ContextoGeneral/', '/home/emilio/Proyectos/02.AnalisisPGP/docs/'];
    foreach ($paths as $basePath) {
        $fullPath = rtrim($basePath,'/').'/'.basename($dir).'/'.basename($file);
        if (file_exists($fullPath)) { header('Content-Type: '.mime_content_type($fullPath)?:'application/octet-stream'); header('Content-Disposition: attachment; filename="'.basename($fullPath).'"'); header('Content-Length: '.filesize($fullPath)); readfile($fullPath); exit; }
        $mdPath = rtrim($basePath,'/').'/'.basename($dir).'/'.basename($file,'.pdf').'.md';
        if (file_exists($mdPath)) { header('Content-Type: text/html; charset=UTF-8'); echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:Arial;max-width:900px;margin:40px auto;padding:20px;line-height:1.6}h1{color:#1a73e8}h2{color:#333}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#f5f5f5}code{background:#f0f0f0;padding:2px 6px}</style></head><body>'.nl2br(htmlspecialchars(file_get_contents($mdPath))).'</body></html>'; exit; }
    }
    http_response_code(404); echo 'Documento no encontrado'; exit;
});
$router->get('/docs/openapi.json', function () {
    header('Content-Type: application/json');
    $path = BASE_PATH . '/docs/openapi.json';
    if (file_exists($path)) {
        header('Content-Length: ' . filesize($path));
        readfile($path);
    } else {
        echo json_encode(['openapi'=>'3.0.0','info'=>['title'=>'EstrateGIA API','version'=>'2.1'],'paths'=>['/planeacion'=>['get'=>['summary'=>'Listar planes']],'/indicadores'=>['get'=>['summary'=>'Dashboard KPIs']],'/generar'=>['post'=>['summary'=>'Generar IA']],'/sst'=>['get'=>['summary'=>'Dashboard SST']],'/ambiental'=>['get'=>['summary'=>'Dashboard Ambiental']],'/calidad'=>['get'=>['summary'=>'Dashboard Calidad']]]], JSON_PRETTY_PRINT);
    }
    exit;
});

// ===== SSE DASHBOARD TIEMPO REAL =====
$router->get('/api/alertas/vencimientos', function () {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    require_once BASE_PATH . '/lib/EstrateGiaCore.php';
    require_once BASE_PATH . '/src/Auth.php';
    Auth::guard();
    $core = EstrateGiaCore::getInstance();
    $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
    $dias = (int)($_GET['dias'] ?? 30);
    $dias_corte = min($dias, 90);

    $alertas = [];
    $resumen = [
        'requisitos_legales_sst' => 0,
        'requisitos_legales_ambiental' => 0,
        'evaluaciones_proveedores' => 0,
        'simulacros' => 0,
        'metas_ambientales_bajo_avance' => 0,
    ];

    $reqLegalesSST = $core->fetchAll(
        "SELECT sst_req_id, sst_req_norma, sst_req_fecha_limite FROM sst_requisitos_legales WHERE empresa_id=? AND sst_req_fecha_limite <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND sst_req_fecha_limite >= CURDATE() ORDER BY sst_req_fecha_limite ASC",
        [$empresaId, $dias_corte]
    );
    foreach ($reqLegalesSST as $r) {
        $alertas[] = [
            'tipo' => 'requisito_legal_sst',
            'nombre' => $r['sst_req_norma'],
            'fecha_limite' => $r['sst_req_fecha_limite'],
            'dias_restantes' => max(0, (int)((strtotime($r['sst_req_fecha_limite']) - time()) / 86400)),
            'color' => '#ffc107',
            'link' => '/sst?seccion=normatividad',
        ];
    }
    $resumen['requisitos_legales_sst'] = count($reqLegalesSST);

    $reqAmbVencer = $core->fetchAll(
        "SELECT amb_req_id, amb_req_norma, amb_req_fecha_limite FROM amb_requisitos_legales WHERE empresa_id=? AND amb_req_fecha_limite <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND amb_req_fecha_limite >= CURDATE() ORDER BY amb_req_fecha_limite ASC",
        [$empresaId, $dias_corte]
    );
    foreach ($reqAmbVencer as $r) {
        $alertas[] = [
            'tipo' => 'requisito_legal_ambiental',
            'nombre' => $r['amb_req_norma'],
            'fecha_limite' => $r['amb_req_fecha_limite'],
            'dias_restantes' => max(0, (int)((strtotime($r['amb_req_fecha_limite']) - time()) / 86400)),
            'color' => '#28a745',
            'link' => '/ambiental?seccion=normatividad',
        ];
    }
    $resumen['requisitos_legales_ambiental'] = count($reqAmbVencer);

    $proveedoresVencer = $core->fetchAll(
        "SELECT prov_id, prov_nombre, prov_proxima_evaluacion FROM cal_proveedores WHERE prov_empresa_id=? AND prov_estado='activo' AND prov_proxima_evaluacion <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND prov_proxima_evaluacion >= CURDATE() ORDER BY prov_proxima_evaluacion ASC",
        [$empresaId, $dias_corte]
    );
    foreach ($proveedoresVencer as $p) {
        $alertas[] = [
            'tipo' => 'evaluacion_proveedor',
            'nombre' => $p['prov_nombre'],
            'fecha_limite' => $p['prov_proxima_evaluacion'],
            'dias_restantes' => max(0, (int)((strtotime($p['prov_proxima_evaluacion']) - time()) / 86400)),
            'color' => '#007bff',
            'link' => '/proveedores/ver/' . $p['prov_id'],
        ];
    }
    $resumen['evaluaciones_proveedores'] = count($proveedoresVencer);

    $simulacrosVencer = $core->fetchAll(
        "SELECT sst_eme_id, sst_eme_nombre, sst_eme_proximo_simulacro FROM sst_emergencias WHERE empresa_id=? AND sst_eme_proximo_simulacro <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND sst_eme_proximo_simulacro >= CURDATE() ORDER BY sst_eme_proximo_simulacro ASC",
        [$empresaId, $dias_corte]
    );
    foreach ($simulacrosVencer as $s) {
        $alertas[] = [
            'tipo' => 'simulacro',
            'nombre' => $s['sst_eme_nombre'],
            'fecha_limite' => $s['sst_eme_proximo_simulacro'],
            'dias_restantes' => max(0, (int)((strtotime($s['sst_eme_proximo_simulacro']) - time()) / 86400)),
            'color' => '#dc3545',
            'link' => '/sst?seccion=emergencias',
        ];
    }
    $resumen['simulacros'] = count($simulacrosVencer);

    $metasBajoAvance = $core->fetchAll(
        "SELECT meta_id, meta_nombre, meta_tipo, meta_valor_objetivo, meta_valor_actual, meta_unidad, meta_anio FROM amb_metas_ambientales WHERE empresa_id=? AND meta_estado='activa' AND meta_valor_objetivo > 0 AND (meta_valor_actual / meta_valor_objetivo) < 0.5 ORDER BY (meta_valor_actual / meta_valor_objetivo) ASC",
        [$empresaId]
    );
    foreach ($metasBajoAvance as $m) {
        $avance = round(((float)$m['meta_valor_actual'] / (float)$m['meta_valor_objetivo']) * 100, 1);
        $alertas[] = [
            'tipo' => 'meta_ambiental',
            'nombre' => $m['meta_nombre'] . ' (' . $m['meta_anio'] . ')',
            'fecha_limite' => $m['meta_anio'] . '-12-31',
            'dias_restantes' => max(0, (int)((strtotime($m['meta_anio'] . '-12-31') - time()) / 86400)),
            'avance_pct' => $avance,
            'valor_objetivo' => (float)$m['meta_valor_objetivo'],
            'valor_actual' => (float)$m['meta_valor_actual'],
            'unidad' => $m['meta_unidad'],
            'color' => '#fd7e14',
            'link' => '/ambiental?seccion=metas',
        ];
    }
    $resumen['metas_ambientales_bajo_avance'] = count($metasBajoAvance);

    usort($alertas, fn($a, $b) => ($a['dias_restantes'] ?? 365) - ($b['dias_restantes'] ?? 365));

    echo json_encode([
        'total' => count($alertas),
        'fecha_consulta' => date('Y-m-d H:i:s'),
        'empresa_id' => $empresaId,
        'dias_ventana' => $dias_corte,
        'resumen' => $resumen,
        'alertas' => $alertas,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
});

$router->get('/sse/dashboard', function () { require BASE_PATH . '/public/sse_dashboard.php'; });

// ===== TEST RUNNER =====
$router->get('/tests', function () {
    Auth::guard();
    header('Content-Type: text/html; charset=UTF-8');
    $tests = ['Smoke Test'=>BASE_PATH.'/tests/smoke_test.php','Unit Tests'=>BASE_PATH.'/tests/unit_test.php','Simulador Experto'=>BASE_PATH.'/tests/simulador_experto.php'];
    if (!empty($_GET['run'])) {
        $file = $tests[$_GET['run']] ?? null;
        if ($file && file_exists($file)) {
            echo '<pre style="background:#1e293b;color:#e2e8f0;padding:20px;font-size:13px;line-height:1.4;white-space:pre-wrap;max-height:80vh;overflow:auto;margin:20px">';
            echo htmlspecialchars(shell_exec("php $file 2>&1") ?: '');
            echo '</pre><a href="/tests" class="btn btn-sm btn-outline-primary" style="margin:20px">← Volver</a>';
            exit;
        }
    }
    $pageTitle = 'Test Runner';
    ob_start();
    echo '<div class="card-box"><div class="card-box-header"><i class="fas fa-flask me-2"></i>Test Runner — EstrateGIA v2.1</div><div class="card-box-body"><div class="row g-3">';
    $colors = ['#1a73e8','#28a745','#6f42c1']; $i = 0;
    foreach ($tests as $name => $path) {
        $exists = file_exists($path); $c = $colors[$i++ % 3];
        echo "<div class=\"col-md-4\"><div class=\"card-box text-center\"><div class=\"card-box-body\"><i class=\"fas fa-".($i==1?'check-circle':($i==2?'vial':'robot'))."\" style=\"font-size:2rem;color:$c;margin-bottom:8px\"></i><h6>$name</h6><p class=\"small text-muted\">".($exists?round(filesize($path)/1024).' KB':'MISSING')."</p><a href=\"/tests?run=".urlencode($name)."\" class=\"btn btn-sm btn-outline-primary\">▶ Ejecutar</a></div></div></div>";
    }
    echo '</div><hr><h6>Terminal:</h6><code class="d-block bg-light p-2 rounded small">php tests/smoke_test.php</code><code class="d-block bg-light p-2 rounded small mt-1">php tests/unit_test.php</code><code class="d-block bg-light p-2 rounded small mt-1">php tests/simulador_experto.php</code></div></div>';
    $content = ob_get_clean();
    require BASE_PATH . '/templates/layout.php';
    exit;
});

// ===== DESPACHAR =====
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_GET['__route'] ?? $_SERVER['REQUEST_URI'];
if (str_ends_with($uri, '/index.php') || $uri === '/index.php') $uri = '/';


// Rutas /api/ van a api.php (REST con JWT)
// Excepción: /api/alertas/vencimientos se maneja en el router
if (str_starts_with($uri, '/api/') && strtok($uri, '?') !== '/api/alertas/vencimientos') {
    require_once BASE_PATH . '/public/api.php';
    exit;
}

echo $router->dispatch($method, $uri);
