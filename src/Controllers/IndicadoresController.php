<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class IndicadoresController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $im = new IndicatorManager();
        $pm = new PlanManager();

        // Leer contexto de cookies/GET - priorizar plan BSC activo
        $planes = $pm->getPlanes();
        $planDefault = 1;
        foreach ($planes as $p) { if (in_array($p['plan_estado'], ['completado','ejecucion','en_proceso'])) { $planDefault = $p['plan_id']; break; } }
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $planId = (int)($_GET['plan_id'] ?? ($_COOKIE['plan_activo'] ?? $planDefault));

        $empresa = $pm->getEmpresa($empresaId);
        $plan = $pm->getPlan($planId);

        // Todos los indicadores del plan
        $indicadores = $im->getIndicadores($planId);
        $categorias = $im->getCategorias();

        // Semáforo y tendencias
        $semaforo = $im->getSemaforoDashboard($planId);
        $variantes = $im->getResumen4Variantes($planId);
        $tendencia = $im->getTendencia4Variantes($planId, 12);

        // Procesos de la empresa con sus indicadores
        $procesos = $this->safeAll(
            'SELECT p.proceso_id, p.proceso_nombre, m.macro_nombre FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             WHERE m.macro_empresa_id = ? AND p.proceso_activo = 1 ORDER BY p.proceso_nombre',
            [$empresaId]
        );

        $pageTitle = 'Indicadores KPIs';
        ob_start();
        require BASE_PATH . '/templates/indicadores/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function crear(): void {
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();
        try {
            $im->createIndicador([
                'indicador_categoria_id' => (int)$_POST['categoria_id'],
                'indicador_plan_id' => (int)$_POST['plan_id'],
                'indicador_proceso_id' => $_POST['proceso_id'] ? (int)$_POST['proceso_id'] : null,
                'indicador_nombre' => $_POST['nombre'],
                'indicador_formula' => $_POST['formula'] ?? '',
                'indicador_unidad_medida' => $_POST['unidad'] ?? '%',
                'indicador_frecuencia_medicion' => $_POST['frecuencia'] ?? 'mensual',
                'indicador_tendencia_esperada' => $_POST['tendencia'] ?? 'ascendente',
                'indicador_fuente_datos' => $_POST['fuente'] ?? 'manual',
                'indicador_responsable_id' => $_POST['responsable_id'] ? (int)$_POST['responsable_id'] : null,
                'indicador_sistemas_json' => json_encode($_POST['sistemas'] ?? ['calidad']),
            ]);
            $this->core->logAction(Auth::userId(), 'crear', 'indicadores', 'indicador', $this->core->getPDO()->lastInsertId()); header('Location: /indicadores?created=1&plan_id=' . $_POST['plan_id']);
        } catch (Exception $e) {
            header('Location: /indicadores?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function detail(int $id): void {
        $im = new IndicatorManager();
        $indicador = $im->getIndicador($id);
        $mediciones = $im->getMediciones($id, null, null, 50);
        $metas = $im->getMetas($id);

        $pageTitle = htmlspecialchars($indicador['indicador_nombre'] ?? 'Indicador');
        ob_start();
        require BASE_PATH . '/templates/indicadores/detail.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function crearMeta(): void {
        $im = new IndicatorManager();
        $im->createMeta([
            'meta_indicador_id' => (int)$_POST['indicador_id'],
            'meta_periodo' => $_POST['periodo'] ?? date('Y-m'),
            'meta_valor' => (float)($_POST['valor'] ?? 0),
            'meta_valor_minimo' => (float)($_POST['valor_minimo'] ?? 0),
            'meta_valor_maximo' => (float)($_POST['valor_maximo'] ?? 0),
            'meta_fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'meta_fecha_fin' => $_POST['fecha_fin'] ?? null,
        ]);
        header('Location: /indicadores/ver/' . (int)$_POST['indicador_id'] . '?ok=1');
        exit;
    }

    public function plantillaMediciones(): void {
        $planId = (int)($_GET['plan_id'] ?? 2);
        $im = new IndicatorManager();
        $indicadores = $im->getIndicadores($planId);
        $pm = new PlanManager();
        $objetivos = $pm->getObjetivos($planId);
        $objMap = []; foreach ($objetivos as $o) $objMap[$o['objetivo_id']] = $o;

        require_once BASE_PATH . '/lib/SimpleXLSX.php';
        $rows = [['ID', 'Indicador', 'Fórmula', 'Meta', 'Unidad', 'Mes (1-12)', 'Año (YYYY)', 'Valor']];
        foreach ($indicadores as $ind) {
            $rows[] = [
                $ind['indicador_id'],
                $ind['indicador_nombre'],
                $ind['indicador_formula'] ?? '',
                $ind['indicador_rango_maximo'] ?? '',
                $ind['indicador_unidad_medida'] ?? '',
                '', '', ''
            ];
        }
        $xlsx = new SimpleXLSX();
        $xlsx->setData($rows, [6, 35, 30, 10, 10, 12, 12, 12], 1, [7]);
        $xlsx->download('plantilla_mediciones_' . date('Ymd'));
    }

    public function cargaMediciones(): void {
        $planId = (int)($_POST['plan_id'] ?? 0);
        if (!$planId) { echo json_encode(['success'=>false,'error'=>'plan_id requerido']); exit; }

        $file = $_FILES['archivo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success'=>false,'error'=>'Archivo no recibido']);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($handle);
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) $rows[] = $row;
            fclose($handle);
        } elseif ($ext === 'xlsx') {
            // Leer XLSX como ZIP + XML
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true) {
                echo json_encode(['success'=>false,'error'=>'No se pudo abrir el archivo XLSX']); exit;
            }
            $sharedStrings = [];
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssXml) {
                $ss = new SimpleXMLElement($ssXml);
                foreach ($ss->si as $si) {
                    $sharedStrings[] = (string)($si->t ?? '');
                }
            }
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (!$sheetXml) {
                $workbook = $zip->getFromName('xl/workbook.xml');
                if ($workbook) {
                    $wb = new SimpleXMLElement($workbook);
                    $ns = $wb->getNamespaces(true);
                    foreach (($wb->sheets->sheet ?? []) as $s) {
                        $rId = (string)($s->attributes('r', true)['id'] ?? '');
                        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
                        if ($rels) {
                            $relsXml = new SimpleXMLElement($rels);
                            foreach ($relsXml->Relationship as $rel) {
                                if ((string)$rel['Id'] === $rId) {
                                    $target = (string)$rel['Target'];
                                    $sheetXml = $zip->getFromName('xl/' . $target);
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
            if (!$sheetXml) { echo json_encode(['success'=>false,'error'=>'No se encontro ninguna hoja en el archivo XLSX']); exit; }
            $sheet = new SimpleXMLElement($sheetXml);
            $rows = [];
            $namespaces = $sheet->getNamespaces(true);
            foreach ($sheet->sheetData->row as $row) {
                $r = [];
                foreach ($row->c as $cell) {
                    $t = (string)$cell['t'];
                    $v = (string)$cell->v;
                    if ($t === 's' && isset($sharedStrings[(int)$v])) {
                        $r[] = $sharedStrings[(int)$v];
                    } else {
                        $r[] = $v;
                    }
                }
                $rows[] = $r;
            }
            $zip->close();
            array_shift($rows); // Quitar header
        } else {
            echo json_encode(['success'=>false,'error'=>'Formato no soportado. Use .csv o .xlsx']); exit;
        }

        $im = new IndicatorManager();
        $indicadoresPlan = $im->getIndicadores($planId);
        $metaMap = [];
        foreach ($indicadoresPlan as $ind) {
            $metaMap[$ind['indicador_id']] = (float)($ind['indicador_rango_maximo'] ?? 0);
        }
        $creadas = 0; $errores = 0;
        foreach ($rows as $row) {
            $indicadorId = (int)($row[0] ?? 0);
            $mes = (int)($row[5] ?? 0);
            $anio = (int)($row[6] ?? 0);
            $periodoDirecto = trim($row[5] ?? '');
            if ($mes >= 1 && $mes <= 12 && $anio >= 2000) {
                $periodo = sprintf('%04d-%02d', $anio, $mes);
            } elseif (preg_match('/^\d{4}-\d{2}$/', $periodoDirecto)) {
                $periodo = $periodoDirecto;
            } else {
                $periodo = date('Y-m');
            }
            $valor = (float)($row[7] ?? $row[8] ?? 0);
            // Auto-calcular semáforo según la meta del indicador
            $meta = $metaMap[$indicadorId] ?? 0;
            if ($meta > 0) {
                $cumplimiento = $valor / $meta;
                if ($cumplimiento >= 1.0) $semaforo = 'verde';
                elseif ($cumplimiento >= 0.7) $semaforo = 'amarillo';
                else $semaforo = 'rojo';
            } else {
                $semaforo = 'verde';
            }
            if (!$indicadorId || !$periodo) { $errores++; continue; }
            try {
                $im->createMedicion([
                    'medicion_indicador_id' => $indicadorId,
                    'medicion_periodo' => $periodo,
                    'medicion_valor' => $valor,
                    'medicion_semaforo' => $semaforo,
                    'medicion_cumplimiento_porcentaje' => $valor > 0 ? min(100, $valor) : 0,
                ]);
                $creadas++;
            } catch (\Throwable $e) { $errores++; }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'creadas' => $creadas, 'errores' => $errores]);
        exit;
    }
}
