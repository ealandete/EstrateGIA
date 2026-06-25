<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';
require_once BASE_PATH . '/lib/AIManager.php';

class IAController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $am = new AIManager();
        $modelos = $am->getModelos();
        $historial = $am->getHistorialAsistencias((int)(Auth::userId() ?? 0));
        $stats = $am->getUsageStats();

        $pageTitle = 'Asistente IA';
        ob_start();
        require BASE_PATH . '/templates/ia/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function preguntar(): void {
        $am = new AIManager();
        $result = $am->procesarAsistencia(
            Auth::userId(),
            $_POST['contexto'] ?? 'general',
            $_POST['prompt'] ?? ''
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
