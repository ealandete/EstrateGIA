<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';
require_once BASE_PATH . '/lib/BaseHSEManager.php';
require_once BASE_PATH . '/lib/SSTManager.php';
require_once BASE_PATH . '/lib/PlanManager.php';

class SSTController {
    use \SafeQuery;
    private $core;

    public function __construct() { 
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
    }

    public function index(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        $seccion = $_GET['seccion'] ?? 'dashboard';
        $empresa = (new PlanManager())->getEmpresa($empresaId);
        $m = new SSTManager();
        $peligros = $m->getPeligros($empresaId);
        $indicadores = $m->getIndicadores($empresaId);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $tipo = $_GET['tipo'] ?? null;
        $incidentes = $m->getIncidentes($empresaId, $anio, $tipo, $page);
        $estadisticas = $m->getEstadisticasSST($empresaId, $anio);
        $planTrabajo = $m->getPlanTrabajo($empresaId, $anio);
        $planVinculado = null;
        if ($planTrabajo && !empty($planTrabajo['plan_estrategico_id'])) {
            $planVinculado = $this->safeOne("SELECT plan_nombre, plan_avance_porcentaje, plan_estado FROM plan_planes_estrategicos WHERE plan_id=?", [$planTrabajo['plan_estrategico_id']]);
        }
        $actividades = $planTrabajo ? $m->getActividades($planTrabajo['sst_plan_id']) : [];
        $reqLegales = $m->getRequisitosLegales($empresaId);
        $ausentismo = $m->getAusentismo($empresaId, $anio, $page);
        $capacitaciones = $m->getCapacitaciones($empresaId, $anio, $page);
        $examenes = $m->getExamenes($empresaId, $anio, $page);
        $inspecciones = $m->getInspecciones($empresaId);
        $emergencias = $m->getEmergencias($empresaId);
        $reportes = $m->getReportes($empresaId);
        $usuarios = $m->getUsuarios($empresaId);
        $procesos = $this->safeAll("SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p JOIN plan_planes_estrategicos pl ON p.proceso_plan_id=pl.plan_id WHERE pl.plan_empresa_id=?", [$empresaId]);
        $planesEstrategicos = (new PlanManager())->getPlanes();
        $pageTitle = 'SST - Seguridad y Salud en el Trabajo';
        ob_start(); require BASE_PATH . '/templates/hse/sst_completo.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    // Peligros
    public function crearPeligro(): void { (new SSTManager())->crearPeligro($_POST); header('Location: /sst?seccion=peligros&ok=1'); exit; }
    public function editarPeligro(): void { (new SSTManager())->editarPeligro((int)$_POST['id'], $_POST); header('Location: /sst?seccion=peligros&ok=1'); exit; }
    public function eliminarPeligro(): void { (new SSTManager())->eliminarPeligro((int)$_POST['id']); header('Location: /sst?seccion=peligros&ok=1'); exit; }

    // Incidentes
    public function reportarIncidente(): void { (new SSTManager())->crearIncidente($_POST); header('Location: /sst?seccion=incidentes&ok=1'); exit; }
    public function investigarIncidente(): void { (new SSTManager())->investigarIncidente((int)$_POST['id'], $_POST); header('Location: /sst?seccion=incidentes&ok=1'); exit; }
    public function cerrarIncidente(): void { (new SSTManager())->cerrarIncidente((int)$_POST['id']); header('Location: /sst?seccion=incidentes&ok=1'); exit; }

    // Indicadores
    public function crearIndicador(): void { (new SSTManager())->crearIndicador($_POST); header('Location: /sst?seccion=dashboard&ok=1'); exit; }

    // Plan
    public function crearPlanTrabajo(): void { (new SSTManager())->crearPlanTrabajo($_POST); header('Location: /sst?seccion=plan&ok=1'); exit; }
    public function actualizarPlanTrabajo(): void { (new SSTManager())->actualizarPlanTrabajo((int)$_POST['id'], $_POST); header('Location: /sst?seccion=plan&ok=1'); exit; }
    public function crearActividad(): void { (new SSTManager())->crearActividad($_POST); header('Location: /sst?seccion=plan&ok=1'); exit; }
    public function actualizarActividad(): void { (new SSTManager())->actualizarActividad((int)$_POST['id'], $_POST); header('Location: /sst?seccion=plan&ok=1'); exit; }
    public function eliminarActividad(): void { (new SSTManager())->eliminarActividad((int)$_POST['id']); header('Location: /sst?seccion=plan&ok=1'); exit; }

    // Requisitos Legales
    public function crearReqLegal(): void { (new SSTManager())->crearRequisitoLegal($_POST); header('Location: /sst?seccion=normatividad&ok=1'); exit; }
    public function actualizarReqLegal(): void { (new SSTManager())->actualizarRequisito((int)$_POST['id'], $_POST); header('Location: /sst?seccion=normatividad&ok=1'); exit; }

    // Otros
    public function crearAusentismo(): void { (new SSTManager())->crearAusentismo($_POST); header('Location: /sst?seccion=ausentismo&ok=1'); exit; }
    public function crearCapacitacion(): void { (new SSTManager())->crearCapacitacion($_POST); header('Location: /sst?seccion=capacitaciones&ok=1'); exit; }
    public function crearExamen(): void { (new SSTManager())->crearExamen($_POST); header('Location: /sst?seccion=examenes&ok=1'); exit; }
    public function crearInspeccion(): void { (new SSTManager())->crearInspeccion($_POST); header('Location: /sst?seccion=inspecciones&ok=1'); exit; }
    public function crearEmergencia(): void { (new SSTManager())->crearEmergencia($_POST); header('Location: /sst?seccion=emergencias&ok=1'); exit; }

    // Reportes
    public function generarReporte(): void { (new SSTManager())->generarReporteLey((int)$_POST['empresa_id'], $_POST['norma'], $_POST['nombre'], $_POST['periodo']); header('Location: /sst?seccion=reportes&ok=1'); exit; }
    public function descargarReporte(int $id): void { (new SSTManager())->descargarReporte($id); }

    public function imprimirReporte(int $id): void {
        $m = new SSTManager();
        $rep = $m->getReporte($id);
        if (!$rep) { header('Location: /sst?seccion=reportes&err=no_encontrado'); exit; }
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = (new PlanManager())->getEmpresa($empresaId);
        $datos = json_decode($rep['sst_rep_contenido_json'], true);
        $titulo = $rep['sst_rep_nombre'] ?? 'Reporte SST';
        $norma = $rep['sst_rep_norma'] ?? '';
        $fecha_generacion = $rep['sst_rep_fecha_generado'] ?? date('Y-m-d');
        $empresa_nombre = $empresa['empresa_nombre'] ?? '';
        $columnas = ['Indicador','Valor'];
        $datosTabla = [];
        if (!empty($datos['estadisticas'])) foreach ($datos['estadisticas'] as $k => $v) $datosTabla[] = [str_replace('_',' ',$k), $v];
        $resumen = [
            'Peligros registrados' => $datos['peligros'] ?? 0,
            'Incidentes en período' => count($datos['incidentes'] ?? []),
            'Indicadores activos' => count($datos['indicadores'] ?? []),
        ];
        require BASE_PATH . '/templates/hse/reporte_imprimible.php';
        exit;
    }

    public function exportarCSV(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $seccion = $_GET['seccion'] ?? 'peligros';
        $m = new SSTManager();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sst_'.$seccion.'_'.date('Ymd').'.csv"');
        $out = fopen('php://output', 'w');
        if ($seccion === 'peligros') {
            fputcsv($out, ['Código','Descripción','Tipo','Probabilidad','Consecuencia','Nivel','Estado']);
            foreach ($m->getPeligros($empresaId) as $r) fputcsv($out, [$r['peligro_codigo'],$r['peligro_descripcion'],$r['peligro_tipo'],$r['peligro_probabilidad'],$r['peligro_consecuencia'],$r['peligro_nivel'],$r['peligro_estado']]);
        } elseif ($seccion === 'ausentismo') {
            fputcsv($out, ['Colaborador','Tipo','Inicio','Fin','Días','Diagnóstico']);
            foreach ($m->getAusentismo($empresaId)['data'] as $r) fputcsv($out, [$r['usuario_nombre'],$r['aus_tipo'],$r['aus_fecha_inicio'],$r['aus_fecha_fin'],$r['aus_dias'],$r['aus_diagnostico']]);
        }
        fclose($out); exit;
    }

    public function guardarAutoevaluacion(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? 2);
        $valores = json_decode($_POST['valores'] ?? '[]', true);
        $puntaje = (float)($_POST['puntaje'] ?? 0);
        $max = (float)($_POST['max'] ?? 35);

        $empresa = $this->safeOne('SELECT empresa_autoeval_sst_json, empresa_autoeval_sst_historial_json FROM plan_empresas WHERE empresa_id = ?', [$empresaId]);

        $historial = json_decode($empresa['empresa_autoeval_sst_historial_json'] ?? '[]', true) ?: [];
        $historial[] = ['fecha' => date('Y-m-d H:i'), 'puntaje' => $puntaje, 'max' => $max, 'valores' => $valores];

        $this->safeUpdate('plan_empresas', [
            'empresa_autoeval_sst_json' => json_encode($valores),
            'empresa_autoeval_sst_historial_json' => json_encode($historial),
        ], 'empresa_id = ?', [$empresaId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'puntaje' => $puntaje]);
        exit;
    }
}
