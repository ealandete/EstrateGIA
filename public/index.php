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
$router->get('/mediciones', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->index(); });
$router->post('/indicadores/meta/crear', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->crearMeta(); });
$router->post('/mediciones/registrar', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->crear(); });
$router->post('/mediciones/subir-csv', function () { require_once BASE_PATH.'/src/Controllers/IndicadoresController.php'; (new IndicadoresController())->cargaMediciones(); });

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
$router->post('/documentos/nueva-version/{id}', function ($id) { require_once BASE_PATH.'/src/Controllers/DocumentosController.php'; (new DocumentosController())->nuevaVersion((int)$id); });

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
$router->post('/financiero/guardar', function () { require_once BASE_PATH.'/lib/FinancialManager.php'; $fm = new FinancialManager(); $id = $fm->savePresupuesto($_POST); header('Content-Type: application/json'); echo json_encode(['success' => true, 'id' => $id]); exit; });
$router->post('/financiero/eliminar', function () { require_once BASE_PATH.'/lib/FinancialManager.php'; $fm = new FinancialManager(); $fm->deletePresupuesto((int)($_POST['id'] ?? 0)); header('Content-Type: application/json'); echo json_encode(['success' => true]); exit; });

// ===== EXPORT PDF =====
$router->get('/planeacion/{id}/pdf', function ($id) {
    $html = file_get_contents('http://localhost:81/planeacion/'.$id.'/reporte?print=1');
    $tmp = tempnam(sys_get_temp_dir(), 'report_').'.html';
    file_put_contents($tmp, $html);
    $pdf = str_replace('.html','.pdf',$tmp);
    exec("pandoc $tmp -o $pdf --pdf-engine=xelatex -V lang=es -V geometry:margin=1.5cm 2>/dev/null");
    if (file_exists($pdf)) { header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="reporte_'.$id.'.pdf"'); readfile($pdf); unlink($tmp); unlink($pdf); exit; }
    header('Location: /planeacion/'.$id.'/reporte');
});

// ===== DOCUMENTACIÓN =====
$router->get('/documentacion', function () {
    require_once BASE_PATH.'/src/Controllers/DocsController.php';
    (new DocsController())->index();
});

// ===== SWAGGER UI =====
$router->get('/docs', function () {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>EstrateGIA API Docs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>body{margin:0;background:#f8fafc}.topbar{background:#1a73e8;color:#fff;padding:12px 24px;font-size:1.2rem}</style></head><body>
    <div class="topbar">EstrateGIA API v2.1</div>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>SwaggerUIBundle({url:"/docs/openapi.json",dom_id:"#swagger-ui",deepLinking:true,defaultModelsExpandDepth:-1})</script></body></html>';
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

// ===== POWER BI / ANALÍTICA =====
$router->get('/api/powerbi', function () {
    require_once BASE_PATH.'/lib/PlanManager.php';
    require_once BASE_PATH.'/lib/IndicatorManager.php';
    $planId = (int)($_GET['plan_id'] ?? 5);
    $pm = new PlanManager(); $im = new IndicatorManager();
    $objs = $pm->getObjetivos($planId);
    $inds = $im->getIndicadores($planId);
    $data = [];
    foreach ($objs as $o) {
        $kpis = array_filter($inds, fn($i) => (int)($i['indicador_objetivo_id']??0) === (int)$o['objetivo_id']);
        foreach ($kpis as $k) {
            $data[] = [
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

// ===== OTROS MÓDULOS =====
$router->get('/', function () { require_once BASE_PATH.'/src/Controllers/SIGController.php'; (new SIGController())->index(); });
$router->get('/calendario', function () { require_once BASE_PATH.'/src/Controllers/CalendarioController.php'; (new CalendarioController())->index(); });
$router->get('/ia', function () { require_once BASE_PATH.'/src/Controllers/IAController.php'; (new IAController())->index(); });
$router->get('/formacion', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->formacion(); });
$router->post('/formacion/crear', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->crearFormacion(); });
$router->get('/satisfaccion', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->satisfaccion(); });
$router->post('/satisfaccion/crear', function () { require_once BASE_PATH.'/src/Controllers/ExtrasController.php'; (new ExtrasController())->crearSatisfaccion(); });
$router->get('/crm', function () { require_once BASE_PATH.'/src/Controllers/CRMController.php'; (new CRMController())->index(); });
$router->post('/crm/conexion/crear', function () { require_once BASE_PATH.'/src/Controllers/IntegracionesController.php'; (new IntegracionesController())->crearConexion(); });
$router->post('/crm/mapeo/crear', function () { require_once BASE_PATH.'/src/Controllers/IntegracionesController.php'; (new IntegracionesController())->crearMapeo(); });
$router->get('/admin/usuarios', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->usuarios(); });
$router->post('/admin/usuarios/crear', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->crearUsuario(); });
$router->get('/admin/roles', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->roles(); });
$router->post('/admin/roles/guardar', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->guardarPermisos(); });
$router->get('/admin/auditoria', function () { require_once BASE_PATH.'/src/Controllers/AdminController.php'; (new AdminController())->auditoria(); });
$router->get('/admin/config', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->index(); });
$router->post('/admin/config/asignar-usuario', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->asignarUsuarioEmpresa(); });
$router->post('/admin/config/crear-empresa', function () { require_once BASE_PATH.'/src/Controllers/ConfigController.php'; (new ConfigController())->crearEmpresa(); });
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
    echo json_encode(['openapi'=>'3.0.0','info'=>['title'=>'EstrateGIA API','version'=>'2.1'],'paths'=>['/planeacion'=>['get'=>['summary'=>'Listar planes']],'/indicadores'=>['get'=>['summary'=>'Dashboard KPIs']],'/generar'=>['post'=>['summary'=>'Generar IA']],'/sst'=>['get'=>['summary'=>'Dashboard SST']],'/ambiental'=>['get'=>['summary'=>'Dashboard Ambiental']],'/calidad'=>['get'=>['summary'=>'Dashboard Calidad']]]], JSON_PRETTY_PRINT);
    exit;
});

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
if (str_starts_with($uri, '/api/')) {
    require_once BASE_PATH . '/public/api.php';
    exit;
}

echo $router->dispatch($method, $uri);
