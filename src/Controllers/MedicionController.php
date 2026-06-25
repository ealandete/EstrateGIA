<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class MedicionController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();
        $pm = new PlanManager();

        $planId = (int)($_GET['plan_id'] ?? ($_COOKIE['plan_activo'] ?? 2));
        $indicadores = $im->getIndicadores($planId);
        $categorias = $im->getCategorias();
        $ultimasMediciones = [];

        if ($indicadores) {
            foreach ($indicadores as $ind) {
                $meds = $im->getMediciones($ind['indicador_id'], null, null, 5);
                if ($meds) $ultimasMediciones[$ind['indicador_id']] = $meds;
            }
        }

        $pageTitle = 'Registro de Mediciones';
        ob_start();
        require BASE_PATH . '/templates/mediciones/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function registrar(): void {
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();

        try {
            $id = $im->registrarMedicion([
                'medicion_indicador_id' => (int)$_POST['indicador_id'],
                'medicion_valor' => (float)$_POST['valor'],
                'medicion_fecha' => $_POST['fecha'] ?? date('Y-m-d'),
                'medicion_periodo' => $_POST['periodo'] ?? date('Y-m'),
                'medicion_origen' => 'manual',
                'medicion_registrado_por' => Auth::userId(),
                'medicion_observaciones' => $_POST['observaciones'] ?? '',
            ]);
            $this->core->logAction(Auth::userId(), 'registrar', 'indicadores', 'medicion', $id ?? 0); header('Location: /mediciones?ok=1&plan_id=' . ($_POST['plan_id'] ?? 2));
        } catch (Exception $e) {
            header('Location: /mediciones?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    // Descargar plantilla Excel (CSV)
    public function descargarPlantilla(): void {
        $planId = (int)($_GET['plan_id'] ?? 2);
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();
        $indicadores = $im->getIndicadores($planId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_mediciones_plan_' . $planId . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['indicador_id', 'indicador_nombre', 'categoria', 'fecha', 'periodo', 'valor', 'observaciones']);

        foreach ($indicadores as $ind) {
            fputcsv($out, [
                $ind['indicador_id'],
                $ind['indicador_nombre'],
                $ind['categoria_tipo'],
                date('Y-m-d'),
                date('Y-m'),
                '',
                ''
            ]);
        }

        // Fila de ejemplo
        fputcsv($out, ['', '', '', '', '', '', '']);
        fputcsv($out, ['EJEMPLO ->', 'KPI-C01', 'cumplimiento', '2026-05-15', '2026-05', '85.5', 'Medición manual mayo']);
        fclose($out);
        exit;
    }

    // Subir archivo CSV con mediciones
    public function subirCSV(): void {
        $planId = (int)($_POST['plan_id'] ?? 2);
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();

        $archivo = $_FILES['archivo'] ?? null;
        if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
            header('Location: /mediciones?error=Archivo no válido');
            exit;
        }

        $handle = fopen($archivo['tmp_name'], 'r');
        $cabecera = fgetcsv($handle); // Saltar cabecera
        $registros = 0;
        $errores = 0;

        while (($fila = fgetcsv($handle)) !== false) {
            if (empty($fila[0]) || trim($fila[0]) === 'EJEMPLO ->' || trim($fila[0]) === 'indicador_id') continue;
            $indicadorId = (int)($fila[0] ?? 0);
            $valor = (float)($fila[5] ?? $fila[4] ?? 0);
            $fecha = trim($fila[3] ?? date('Y-m-d'));
            $periodo = trim($fila[4] ?? date('Y-m'));

            // Validación
            if ($indicadorId <= 0) { $errores++; continue; }
            if ($valor == 0) { $errores++; continue; }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { $errores++; continue; }
                try {
                    $im->registrarMedicion([
                        'medicion_indicador_id' => $indicadorId,
                        'medicion_valor' => $valor,
                        'medicion_fecha' => $fecha,
                        'medicion_periodo' => $periodo,
                        'medicion_origen' => 'manual',
                        'medicion_registrado_por' => Auth::userId(),
                        'medicion_observaciones' => 'Importado desde CSV',
                    ]);
                    $registros++;
                } catch (Exception $e) {
                    $errores++;
                }
        }
        fclose($handle);

        $this->core->logAction(Auth::userId(), 'importar', 'indicadores', 'medicion', $registros);
        header('Location: /mediciones?ok=1&plan_id=' . $planId . '&importados=' . $registros);
        exit;
    }

    // Asistente de minería CRM
    public function mineria(): void {
        $pageTitle = 'Asistente de Minería CRM';
        ob_start();
        require BASE_PATH . '/templates/mediciones/mineria.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    // Ejecutar minería
    public function ejecutarMineria(): void {
        require_once BASE_PATH . '/lib/CRMManager.php';
        $cm = new CRMManager();
        $empresaId = (int)($_POST['empresa_id'] ?? 1);

        $result = $cm->ejecutarTodasSincronizaciones($empresaId);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
