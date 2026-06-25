<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class FaseController {
    use \SafeQuery;
    private $core;
    private PlanManager $pm;

    public function __construct() {
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
        $this->pm = new PlanManager();
    }

    public function wizard(int $planId, int $faseId): void {
        $plan = $this->pm->getPlan($planId);
        $fase = $this->pm->getFase($faseId);
        if (!$plan || !$fase) { http_response_code(404); echo 'No encontrado'; return; }

        $guia = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true);
        $pasos = $guia['pasos'] ?? [];
        $pasoActual = (int)($_GET['paso'] ?? 1);
        $totalPasos = count($pasos);
        if ($pasoActual < 1) $pasoActual = 1;
        if ($pasoActual > $totalPasos) $pasoActual = $totalPasos;
        $pasoData = $pasos[$pasoActual - 1] ?? null;

        require_once BASE_PATH . '/lib/AIManager.php';
        $aiManager = new AIManager();
        $recomendaciones = $aiManager->getRecomendaciones('plan', $planId);

        $empresa = $this->pm->getEmpresa($plan['plan_empresa_id']);
        $sectorId = $empresa['empresa_sector_id'] ?? null;

        require_once BASE_PATH . '/lib/DocManager.php';
        $docManager = new DocManager();
        $normasAplicables = $sectorId ? $docManager->getNormas($sectorId) : [];

        $foda = $this->pm->getFODA($planId);
        $pestel = $this->pm->getPESTEL($planId);

        $pageTitle = 'Fase: ' . $fase['fase_nombre'];
        ob_start();
        require BASE_PATH . '/templates/planeacion/fase_wizard.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function generarContenidoIA(): void {
        $planId = (int)$_POST['plan_id'];
        $tipo = $_POST['tipo'] ?? '';

        $plan = $this->pm->getPlan($planId);
        $empresa = $this->pm->getEmpresa($plan['plan_empresa_id']);
        $sectorNombre = $empresa['sector_nombre'] ?? 'General';

        require_once BASE_PATH . '/lib/AIManager.php';
        $ai = new AIManager();

        $contexto = [
            'empresa' => $empresa['empresa_nombre'],
            'sector' => $sectorNombre,
            'metodologia' => $plan['metodologia_nombre'],
            'objetivo' => $_POST['objetivo'] ?? '',
            'tipo_indicador' => $_POST['tipo_indicador'] ?? 'cumplimiento',
            'tipo_proceso' => $_POST['tipo_proceso'] ?? 'misional',
        ];

        $result = $ai->generarContenido($tipo, $contexto);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
