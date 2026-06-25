<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';
require_once BASE_PATH . '/lib/BaseHSEManager.php';
require_once BASE_PATH . '/lib/AmbientalManager.php';
require_once BASE_PATH . '/lib/PlanManager.php';

class AmbientalController {
    use \SafeQuery;
    private $core;

    public function __construct() { $this->core = EstrateGiaCore::getInstance(); Auth::guard(); }

    public function index(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        $seccion = $_GET['seccion'] ?? 'dashboard';
        $m = new AmbientalManager();
        $empresa = (new PlanManager())->getEmpresa($empresaId);
        $aspectos = $m->getAspectos($empresaId, $_GET['recurso'] ?? null, (int)($_GET['area_id'] ?? 0) ?: null);
        $emisionesGEI = $m->getEmisionesGEI($empresaId, $anio);
        $huellaCarbono = $m->getHuellaCarbono($empresaId, $anio);
        $indicadoresCarbono = $m->getIndicadoresCarbono($empresaId, $anio);
        $controles = $m->getControles($empresaId);
        $planesTrabajo = $m->getPlanesTrabajo($empresaId);
        $actividadesPorPlan = [];
        foreach ($planesTrabajo as $p) {
            $actividadesPorPlan[$p['plan_id']] = $m->getActividadesPlan((int)$p['plan_id']);
        }
        $metasAmbientales = $m->getMetasAmbientales($empresaId);
        $reportes = $m->getReportes($empresaId);
        $indicadores = $m->getIndicadores($empresaId);
        $registros = $m->getRegistros($empresaId, $anio);
        $tendenciaAgua = $m->getTendencia($empresaId, 'consumo_agua', 12);
        $tendenciaEnergia = $m->getTendencia($empresaId, 'consumo_energia', 12);
        $proyeccionEnergia = $this->calcularProyeccion($tendenciaEnergia);
        $proyeccionAgua = $this->calcularProyeccion($tendenciaAgua);
        $estadisticas = $m->getEstadisticasAmbiental($empresaId, $anio);
        $planGestion = $m->getPlanGestion($empresaId, $anio);
        $planesGestion = $m->getPlanesGestion($empresaId);
        $programas = $m->getProgramas($empresaId);
        $reqLegales = $m->getRequisitosLegales($empresaId);
        $auditorias = $m->getAuditorias($empresaId);
        $planVinculado = null;
        if ($planGestion && !empty($planGestion['plan_estrategico_id'])) {
            $planVinculado = $this->safeOne("SELECT plan_nombre, plan_avance_porcentaje, plan_estado FROM plan_planes_estrategicos WHERE plan_id=?", [$planGestion['plan_estrategico_id']]);
        }
        $procesos = $this->safeAll("SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p JOIN plan_planes_estrategicos pl ON p.proceso_plan_id=pl.plan_id WHERE pl.plan_empresa_id=?", [$empresaId]);
        $planesEstrategicos = (new PlanManager())->getPlanes();
        $usuarios = $m->getUsuarios($empresaId);
        $recursos = ['agua' => 'Agua', 'aire' => 'Aire', 'suelo' => 'Suelo', 'flora' => 'Flora', 'fauna' => 'Fauna', 'energia' => 'Energia', 'residuos' => 'Residuos'];
        $pageTitle = 'Gestion Ambiental';
        ob_start(); require BASE_PATH . '/templates/hse/ambiental_completo.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    // --- ASPECTOS E IMPACTOS AMBIENTALES (AIA) ---
    public function crearAspecto(): void {
        (new AmbientalManager())->crearAspecto($_POST);
        header('Location: /ambiental?seccion=aspectos&ok=1'); exit;
    }
    public function editarAspecto(): void {
        (new AmbientalManager())->editarAspecto((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=aspectos&ok=1'); exit;
    }
    public function eliminarAspecto(): void {
        (new AmbientalManager())->eliminarAspecto((int)$_POST['id']);
        header('Location: /ambiental?seccion=aspectos&ok=1'); exit;
    }

    // --- HUELLA DE CARBONO ISO 14064 ---
    public function crearEmisionGEI(): void {
        (new AmbientalManager())->crearEmisionGEI($_POST);
        header('Location: /ambiental?seccion=huella&ok=1'); exit;
    }
    public function editarEmisionGEI(): void {
        (new AmbientalManager())->editarEmisionGEI((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=huella&ok=1'); exit;
    }
    public function eliminarEmisionGEI(): void {
        (new AmbientalManager())->eliminarEmisionGEI((int)$_POST['id']);
        header('Location: /ambiental?seccion=huella&ok=1'); exit;
    }

    // --- CONTROLES ---
    public function crearControl(): void {
        (new AmbientalManager())->crearControl($_POST);
        header('Location: /ambiental?seccion=controles&ok=1'); exit;
    }
    public function editarControl(): void {
        (new AmbientalManager())->editarControl((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=controles&ok=1'); exit;
    }
    public function eliminarControl(): void {
        (new AmbientalManager())->eliminarControl((int)$_POST['id']);
        header('Location: /ambiental?seccion=controles&ok=1'); exit;
    }

    // --- PLANES DE TRABAJO ---
    public function crearPlanTrabajo(): void {
        (new AmbientalManager())->crearPlanTrabajo($_POST);
        header('Location: /ambiental?seccion=planes&ok=1'); exit;
    }
    public function editarPlanTrabajo(): void {
        (new AmbientalManager())->editarPlanTrabajo((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=planes&ok=1'); exit;
    }
    public function crearActividadPlan(): void {
        (new AmbientalManager())->crearActividadPlan($_POST);
        header('Location: /ambiental?seccion=planes&ok=1'); exit;
    }
    public function editarActividadPlan(): void {
        (new AmbientalManager())->editarActividadPlan((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=planes&ok=1'); exit;
    }

    // --- METAS AMBIENTALES ---
    public function crearMetaAmbiental(): void {
        (new AmbientalManager())->crearMetaAmbiental($_POST);
        header('Location: /ambiental?seccion=metas&ok=1'); exit;
    }
    public function editarMetaAmbiental(): void {
        (new AmbientalManager())->editarMetaAmbiental((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=metas&ok=1'); exit;
    }

    // --- REGISTROS ---
    public function registrarMedicion(): void {
        (new AmbientalManager())->crearRegistro($_POST);
        header('Location: /ambiental?seccion=registros&ok=1'); exit;
    }

    // --- INDICADORES ---
    public function crearIndicador(): void {
        (new AmbientalManager())->crearIndicador($_POST);
        header('Location: /ambiental?seccion=dashboard&ok=1'); exit;
    }

    // --- PLAN DE GESTION ---
    public function crearPlanGestion(): void {
        (new AmbientalManager())->crearPlanGestion($_POST);
        header('Location: /ambiental?seccion=plan&ok=1'); exit;
    }
    public function actualizarPlanGestion(): void {
        (new AmbientalManager())->actualizarPlanGestion((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=plan&ok=1'); exit;
    }
    public function crearPrograma(): void {
        (new AmbientalManager())->crearPrograma($_POST);
        header('Location: /ambiental?seccion=plan&ok=1'); exit;
    }
    public function actualizarPrograma(): void {
        (new AmbientalManager())->actualizarPrograma((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=plan&ok=1'); exit;
    }

    // --- REQUISITOS LEGALES ---
    public function crearReqLegal(): void {
        (new AmbientalManager())->crearRequisitoLegal($_POST);
        header('Location: /ambiental?seccion=normatividad&ok=1'); exit;
    }
    public function actualizarReqLegal(): void {
        (new AmbientalManager())->actualizarRequisito((int)$_POST['id'], $_POST);
        header('Location: /ambiental?seccion=normatividad&ok=1'); exit;
    }

    // --- AUDITORIAS ---
    public function crearAuditoria(): void {
        (new AmbientalManager())->crearAuditoria($_POST);
        header('Location: /ambiental?seccion=auditorias&ok=1'); exit;
    }

    // --- REPORTES ---
    public function generarReporte(): void {
        (new AmbientalManager())->generarReporteLey((int)$_POST['empresa_id'], $_POST['norma'], $_POST['nombre'], $_POST['periodo']);
        header('Location: /ambiental?seccion=reportes&ok=1'); exit;
    }
    public function descargarReporte(int $id): void {
        (new AmbientalManager())->descargarReporte($id);
    }

    // --- API JSON ---
    public function apiHuellaCarbono(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        $m = new AmbientalManager();
        header('Content-Type: application/json');
        echo json_encode($m->getHuellaCarbono($empresaId, $anio));
        exit;
    }
    public function apiIndicadoresCarbono(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        $m = new AmbientalManager();
        header('Content-Type: application/json');
        echo json_encode($m->getIndicadoresCarbono($empresaId, $anio));
        exit;
    }
    public function apiDashboardAmbiental(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        $m = new AmbientalManager();
        header('Content-Type: application/json');
        echo json_encode([
            'estadisticas' => $m->getEstadisticasAmbiental($empresaId, $anio),
            'huella' => $m->getHuellaCarbono($empresaId, $anio),
            'programas' => $m->getDashboardProgramas($empresaId),
            'metas' => $m->getMetasAmbientales($empresaId),
        ]);
        exit;
    }

    private function calcularProyeccion(array $datos): array {
        $n = count($datos);
        if ($n < 3) return ['labels' => [], 'values' => []];
        $valores = array_column($datos, 'total');
        $x = 0; $y = 0; $xx = 0; $xy = 0;
        for ($i = 0; $i < $n; $i++) { $x += $i; $y += $valores[$i]; $xx += $i * $i; $xy += $i * $valores[$i]; }
        $m = ($n * $xy - $x * $y) / ($n * $xx - $x * $x);
        $b = ($y - $m * $x) / $n;
        $labels = []; $values = [];
        $ultimoLabel = end($datos)['mes'];
        for ($i = 1; $i <= 3; $i++) {
            $proyMes = date('Y-m', strtotime($ultimoLabel . ' +' . $i . ' month'));
            $labels[] = $proyMes;
            $values[] = round(max(0, $m * ($n + $i - 1) + $b), 1);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public function guardarAutoevaluacion(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? 2);
        $valores = json_decode($_POST['valores'] ?? '[]', true);
        $puntaje = (float)($_POST['puntaje'] ?? 0);
        $max = (float)($_POST['max'] ?? 52.5);
        $empresa = $this->safeOne('SELECT empresa_autoeval_ambiental_json, empresa_autoeval_ambiental_historial_json FROM plan_empresas WHERE empresa_id = ?', [$empresaId]);
        $historial = json_decode($empresa['empresa_autoeval_ambiental_historial_json'] ?? '[]', true) ?: [];
        $historial[] = ['fecha' => date('Y-m-d H:i'), 'puntaje' => $puntaje, 'max' => $max, 'valores' => $valores];
        $this->safeUpdate('plan_empresas', [
            'empresa_autoeval_ambiental_json' => json_encode($valores),
            'empresa_autoeval_ambiental_historial_json' => json_encode($historial),
        ], 'empresa_id = ?', [$empresaId]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'puntaje' => $puntaje]);
        exit;
    }
}
