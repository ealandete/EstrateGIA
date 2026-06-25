<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class PlaneacionController {
    use \SafeQuery;

    private $core;
    private PlanManager $pm;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
        Auth::guard();
        $this->pm = new PlanManager();
    }

    public function index(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 0));
        $planes = $empresaId ? $this->pm->getPlanes($empresaId) : $this->pm->getPlanes();
        $metodologias = $this->pm->getMetodologias();

        $pageTitle = 'Planeación Estratégica';
        ob_start();
        require BASE_PATH . '/templates/planeacion/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function create(): void {
        $empresas = $this->pm->getEmpresas();
        $metodologias = $this->pm->getMetodologias();
        require_once BASE_PATH . '/lib/DocManager.php';
        $sectores = (new DocManager())->getSectores();
        $empresaPreseleccionada = $_GET['empresa_ok'] ?? null;

        $pageTitle = 'Nuevo Plan Estratégico';
        ob_start();
        require BASE_PATH . '/templates/planeacion/create.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function store(): void {
        try {
            $id = $this->pm->createPlan([
                'plan_empresa_id'     => (int)$_POST['empresa_id'],
                'plan_metodologia_id' => (int)$_POST['metodologia_id'],
                'plan_nombre'         => $_POST['nombre'],
                'plan_descripcion'    => $_POST['descripcion'] ?? '',
                'plan_fecha_inicio'   => $_POST['fecha_inicio'] ?? null,
                'plan_fecha_fin'      => $_POST['fecha_fin'] ?? null,
                'plan_periodo'        => $_POST['periodo'] ?? null,
                'plan_presupuesto_total' => $_POST['presupuesto'] ? (float)$_POST['presupuesto'] : null,
                'plan_responsable_id' => Auth::userId(),
                'usuario_id'          => Auth::userId(),
            ]);
            header('Location: /planeacion/' . $id . '?created=1');
        } catch (Exception $e) {
            header('Location: /planeacion/crear?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function crearEmpresa(): void {
        try {
            $id = $this->pm->createEmpresa([
                'empresa_nombre'      => $_POST['empresa_nombre'],
                'empresa_razon_social'=> $_POST['empresa_razon_social'] ?? '',
                'empresa_nit'         => $_POST['empresa_nit'] ?? '',
                'empresa_sector_id'   => $_POST['empresa_sector_id'] ?? null,
                'empresa_direccion'   => $_POST['empresa_direccion'] ?? '',
                'empresa_telefono'    => $_POST['empresa_telefono'] ?? '',
                'usuario_id'          => Auth::userId(),
            ]);

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
            if ($isAjax || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'empresa_id' => $id, 'empresa_nombre' => $_POST['empresa_nombre']]);
                exit;
            }

            header('Location: /planeacion/crear?empresa_ok=' . $id);
        } catch (Exception $e) {
            header('Location: /planeacion/crear?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function detail(int $id): void {
        $plan = $this->pm->getPlan($id);
        if (!$plan) { http_response_code(404); echo 'Plan no encontrado'; return; }

        $empresa = $this->pm->getEmpresa($plan['plan_empresa_id'] ?? 0);
        $arbol = $this->pm->getPlanTree($id);
        $progreso = $this->pm->getPlanProgress($id);
        $foda = $this->pm->getFODA($id);
        $pestel = $this->pm->getPESTEL($id);
        $carga = $this->pm->getCargaTrabajoColaboradores($id);

        $indicadores = (new IndicatorManager())->getResumen4Variantes($id);
        $presupuesto = $this->pm->getPresupuestosByPlan($id);

        $pageTitle = htmlspecialchars($plan['plan_nombre']);
        ob_start();
        require BASE_PATH . '/templates/planeacion/detail.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function foda(int $id): void {
        $plan = $this->pm->getPlan($id);
        $foda = $this->pm->getFODA($id);
        $pageTitle = 'Análisis FODA';
        ob_start();
        require BASE_PATH . '/templates/planeacion/foda.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function pestel(int $id): void {
        $plan = $this->pm->getPlan($id);
        if (!$plan) { http_response_code(404); echo 'Plan no encontrado'; return; }
        $pestel = $this->pm->getPESTEL($id);
        $pageTitle = 'Análisis PESTEL';
        ob_start();
        require BASE_PATH . '/templates/planeacion/pestel.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function edit(int $id): void {
        $plan = $this->pm->getPlan($id);
        if (!$plan) { header('Location: /planeacion'); exit; }
        $pageTitle = 'Editar: ' . htmlspecialchars($plan['plan_nombre']);
        ob_start();
        require BASE_PATH . '/templates/planeacion/edit.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function update(int $id): void {
        $this->pm->updatePlan($id, [
            'plan_nombre' => $_POST['nombre'] ?? '',
            'plan_descripcion' => $_POST['descripcion'] ?? '',
            'plan_fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'plan_fecha_fin' => $_POST['fecha_fin'] ?? null,
            'plan_presupuesto_total' => $_POST['presupuesto'] ? (float)$_POST['presupuesto'] : null,
            'plan_estado' => $_POST['estado'] ?? null,
        ]);
        header('Location: /planeacion/' . $id . '?updated=1');
        exit;
    }

    public function delete(int $id): void {
        $this->pm->deletePlan($id);
        header('Location: /planeacion?deleted=1');
        exit;
    }

    public function reporte(int $id): void {
        $plan = $this->pm->getPlan($id);
        if (!$plan) { http_response_code(404); echo 'Plan no encontrado'; return; }

        $empresa = $this->pm->getEmpresa($plan['plan_empresa_id']);
        $arbol = $this->pm->getPlanTree($id);
        $foda = $this->pm->getFODA($id);
        $pestel = $this->pm->getPESTEL($id);
        $fases = $this->pm->getFases($id);
        $progreso = $this->pm->getPlanProgress($id);

        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();
        $indicadores = $im->getIndicadores($id);

        require_once BASE_PATH . '/lib/DocManager.php';
        $dm = new DocManager();
        $sectorInfo = $dm->getSectorInfo($plan['plan_empresa_id']);
        $normas = ($empresa['empresa_sector_id'] ?? null) ? $dm->getNormas($empresa['empresa_sector_id']) : [];

        $print = isset($_GET['print']);

        if ($print) {
            $pageTitle = 'Reporte: ' . htmlspecialchars($plan['plan_nombre']);
            require BASE_PATH . '/templates/planeacion/reporte.php';
        } else {
            $pageTitle = 'Reporte: ' . htmlspecialchars($plan['plan_nombre']);
            ob_start();
            require BASE_PATH . '/templates/planeacion/reporte.php';
            $content = ob_get_clean();
            require BASE_PATH . '/templates/layout.php';
        }
    }
}
