<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class DashboardController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $integrator = new SystemIntegrator();
        $pm = new PlanManager();
        $empresas = $pm->getEmpresas();

        // Respetar el selector del header (GET > cookie > auto-detección)
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 0));
        if (!$empresaId) {
            $planes = $pm->getPlanes(null, 'completado');
            if (empty($planes)) $planes = $pm->getPlanes(null, 'ejecucion');
            if (!empty($planes)) {
                $empresaId = (int)$planes[0]['plan_empresa_id'];
            } elseif (!empty($empresas)) {
                $empresaId = (int)$empresas[0]['empresa_id'];
            } else {
                $empresaId = 1;
            }
        }
        $dashboard = $integrator->getDashboardEjecutivo($empresaId);

        $resumen = $dashboard['resumen_planeacion'] ?? [];
        $plan = $resumen['plan_activo'] ?? null;
        $variantes = $resumen['variantes_kpi'] ?? [];
        $semaforo = $dashboard['semaforo_kpis'] ?? [];
        $alertas = $dashboard['alertas'] ?? [];
        $ranking = $dashboard['ranking_colaboradores'] ?? [];
        $procesos = $dashboard['resumen_procesos'] ?? [];

        $pageTitle = 'Dashboard Ejecutivo';
        ob_start();
        require BASE_PATH . '/templates/dashboard.php';
        $content = ob_get_clean();

        require BASE_PATH . '/templates/layout.php';
    }

    public function tableros(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 0));
        if (!$empresaId) {
            $empresas = $pm->getEmpresas();
            $empresaId = !empty($empresas) ? (int)$empresas[0]['empresa_id'] : 1;
        }

        $tableros = $this->safeAll(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM dash_widgets w WHERE w.widget_tablero_id = t.tablero_id AND w.widget_activo = 1) as total_widgets
             FROM dash_tableros t
             WHERE (t.tablero_empresa_id = ? OR t.tablero_es_plantilla = 1) AND t.tablero_activo = 1
             ORDER BY t.tablero_tipo, t.tablero_nombre",
            [$empresaId]
        );

        $pageTitle = 'Dashboards';
        ob_start();
        require BASE_PATH . '/templates/dashboards.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }
}
