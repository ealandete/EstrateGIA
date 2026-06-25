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

        $pageTitle = 'Integraciones CRM';
        ob_start();
        require BASE_PATH . '/templates/crm/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }
}
