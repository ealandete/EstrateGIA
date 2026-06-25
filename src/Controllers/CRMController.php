<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

require_once BASE_PATH . '/lib/CRMManager.php';
class CRMController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $cm = new CRMManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $conexiones = $cm->getConexiones($empresaId);
        $dash = $cm->getDashboardIntegraciones($empresaId);
        $minerias = $cm->getMineriaConfigs();

        $mapeos = $this->safeAll(
            "SELECT m.*, c.conexion_nombre, i.indicador_nombre
             FROM crm_mapeos_datos m
             JOIN crm_conexiones c ON m.mapeo_conexion_id = c.conexion_id
             LEFT JOIN ind_indicadores i ON m.mapeo_indicador_id = i.indicador_id
             WHERE c.conexion_empresa_id = ? AND m.mapeo_activo = 1",
            [$empresaId]
        );

        $indicadores = $this->safeAll(
            "SELECT i.*, c.categoria_nombre FROM ind_indicadores i JOIN ind_categorias c ON i.indicador_categoria_id=c.categoria_id WHERE i.indicador_plan_id IN (SELECT plan_id FROM plan_planes_estrategicos WHERE plan_empresa_id=?) AND i.indicador_activo=1",
            [$empresaId]
        );

        $sincros = $this->safeAll(
            "SELECT m.mapeo_nombre, m.mapeo_ultima_ejecucion, m.mapeo_tipo_indicador, c.conexion_nombre
             FROM crm_mapeos_datos m JOIN crm_conexiones c ON m.mapeo_conexion_id=c.conexion_id
             WHERE c.conexion_empresa_id=? AND m.mapeo_ultima_ejecucion IS NOT NULL ORDER BY m.mapeo_ultima_ejecucion DESC LIMIT 20",
            [$empresaId]
        );

        $pageTitle = 'Integraciones CRM';
        ob_start();
        require BASE_PATH . '/templates/crm/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }
}
