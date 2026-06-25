<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class GeneradorController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function generar(): void {
        $tipo = $_POST['tipo'] ?? '';
        $planId = (int)($_POST['plan_id'] ?? 0);
        $contextoExtra = $_POST['contexto'] ?? '';

        require_once BASE_PATH . '/lib/AIManager.php';
        require_once BASE_PATH . '/lib/PlanManager.php';
        require_once BASE_PATH . '/lib/DocManager.php';
        require_once BASE_PATH . '/lib/IndicatorManager.php';

        $ai = new AIManager();
        $pm = new PlanManager();
        $dm = new DocManager();

        $empresaNombre = '';
        $sectorNombre = 'General';
        $metodologiaNombre = '';

        if ($planId) {
            $plan = $pm->getPlan($planId);
            $empresa = $pm->getEmpresa($plan['plan_empresa_id'] ?? 0);
            $empresaNombre = $empresa['empresa_nombre'] ?? '';
            $sectorNombre = $empresa['sector_nombre'] ?? 'General';
            $metodologiaNombre = $plan['metodologia_nombre'] ?? '';
        }

        $contexto = [
            'empresa' => $empresaNombre,
            'sector' => $sectorNombre,
            'metodologia' => $metodologiaNombre,
            'objetivo' => $contextoExtra,
            'tipo_indicador' => $_POST['tipo_indicador'] ?? 'cumplimiento',
            'tipo_proceso' => $_POST['tipo_proceso'] ?? 'misional',
        ];

        try {
            $result = $ai->generarContenido($tipo, $contexto);

            if ($tipo === 'foda' && $planId && $result['success']) {
                $fodaData = json_decode($result['contenido'], true);
                if ($fodaData && isset($fodaData['fortalezas'])) {
                    $pm->createAnalisis([
                        'analisis_plan_id' => $planId,
                        'analisis_tipo' => 'FODA',
                        'analisis_titulo' => 'Análisis FODA - ' . $empresaNombre,
                        'analisis_contenido' => $fodaData,
                        'analisis_conclusiones' => 'Generado por IA',
                        'analisis_fecha' => date('Y-m-d'),
                        'analisis_responsable_id' => Auth::userId(),
                    ]);
                    $result['guardado'] = true;
                }
            }

            if ($tipo === 'objetivos' && $planId && $result['success']) {
                $objetivos = array_slice(explode("\n", trim($result['contenido'])), 0, 5);
                foreach ($objetivos as $obj) {
                    $obj = trim($obj);
                    if (strlen($obj) > 10) {
                        $pm->createObjetivo([
                            'objetivo_plan_id' => $planId,
                            'objetivo_nombre' => substr($obj, 0, 300),
                            'objetivo_descripcion' => $obj,
                            'objetivo_tipo' => 'estrategico',
                            'objetivo_responsable_id' => Auth::userId(),
                        ]);
                    }
                }
                $result['objetivos_creados'] = count($objetivos);
            }

            if ($tipo === 'indicadores' && $planId && $result['success']) {
                $im = new IndicatorManager();
                $categorias = $im->getCategorias();
                $catIds = array_column($categorias, 'categoria_id', 'categoria_tipo');
                $creados = 0;
                $jsonData = json_decode($result['contenido'], true);
                if (is_array($jsonData) && isset($jsonData[0]) && is_array($jsonData[0]) && isset($jsonData[0]['nombre'])) {
                    $objetivos = $pm->getObjetivos($planId);
                    $existentes = $im->getIndicadores($planId);
                    $yaExiste = function($nombre) use ($existentes) {
                        foreach ($existentes as $e) {
                            similar_text(strtolower($e['indicador_nombre'] ?? ''), strtolower($nombre), $sim);
                            if ($sim > 80) return true;
                        }
                        return false;
                    };
                    foreach ($jsonData as $kpi) {
                        $nombre = $kpi['nombre'] ?? '';
                        if ($yaExiste($nombre)) continue;
                        $oid = null; $bestSim = 0;
                        foreach ($objetivos as $obj) {
                            if (($obj['objetivo_perspectiva'] ?? '') === ($kpi['perspectiva'] ?? '')) {
                                similar_text(strtolower($obj['objetivo_nombre'] ?? ''), strtolower($nombre), $sim);
                                if ($sim > 40 && $sim > $bestSim) { $bestSim = $sim; $oid = $obj['objetivo_id']; }
                            }
                        }
                        $im->createIndicador([
                            'indicador_categoria_id' => $catIds[$_POST['tipo_indicador'] ?? 'cumplimiento'] ?? 1,
                            'indicador_plan_id' => $planId,
                            'indicador_objetivo_id' => $oid,
                            'indicador_nombre' => substr($nombre, 0, 300),
                            'indicador_formula' => $kpi['formula'] ?? null,
                            'indicador_unidad_medida' => $kpi['unidad'] ?? null,
                            'indicador_rango_maximo' => (float)($kpi['meta'] ?? 0),
                            'indicador_frecuencia_medicion' => 'mensual',
                            'indicador_fuente_datos' => $kpi['fuente'] ?? null,
                        ]);
                        $creados++;
                    }
                } else {
                    $lineas = explode("\n", trim($result['contenido']));
                    foreach ($lineas as $linea) {
                        if (strlen(trim($linea)) > 5) {
                            $im->createIndicador([
                                'indicador_categoria_id' => $catIds[$_POST['tipo_indicador'] ?? 'cumplimiento'] ?? 1,
                                'indicador_plan_id' => $planId,
                                'indicador_nombre' => substr(trim($linea), 0, 300),
                                'indicador_descripcion' => trim($linea),
                                'indicador_frecuencia_medicion' => 'mensual',
                            ]);
                            $creados++;
                        }
                    }
                }
                $result['indicadores_creados'] = $creados;
            }

            if ($tipo === 'bsc' && $planId && $result['success']) {
                $persps = ['Financiera'=>'financiera','Cliente'=>'cliente','Procesos'=>'procesos','Aprendizaje'=>'aprendizaje'];
                $currentPersp = 'financiera';
                $existentes = $pm->getObjetivos($planId);
                $yaExiste = function($nombre, $persp) use ($existentes) {
                    foreach ($existentes as $e) {
                        if ($e['objetivo_perspectiva'] === $persp) {
                            similar_text(strtolower($e['objetivo_nombre']), strtolower($nombre), $sim);
                            if ($sim > 70) return true;
                        }
                    }
                    return false;
                };
                $creados = 0;
                $lineas = explode("\n", $result['contenido']);
                foreach ($lineas as $linea) {
                    $linea = trim($linea); if (empty($linea)) continue;
                    foreach ($persps as $label => $key) { if (str_contains($linea, "Perspectiva: $label")) { $currentPersp = $key; continue 2; } }
                    if (str_starts_with($linea, '- Objetivo:') || str_starts_with($linea, '-')) {
                        $nombre = preg_replace('/^-\s*Objetivo:\s*/', '', $linea);
                        $nombre = preg_replace('/\(KPI:.*/', '', $nombre);
                        $nombre = trim($nombre);
                        if (strlen($nombre) > 5 && !$yaExiste($nombre, $currentPersp)) {
                            $pm->createObjetivo(['objetivo_plan_id'=>$planId,'objetivo_nombre'=>substr($nombre,0,300),'objetivo_descripcion'=>$linea,'objetivo_perspectiva'=>$currentPersp,'objetivo_responsable_id'=>Auth::userId()]);
                            $creados++;
                        }
                    }
                }
                $result['bsc_creado'] = $creados;
            }

            if ($tipo === 'iniciativas' && $planId && $result['success']) {
                $jsonData = json_decode($result['contenido'], true);
                $creadas = 0;
                if (is_array($jsonData) && isset($jsonData[0]['nombre'])) {
                    $objCache = $pm->getObjetivos($planId);
                    $existentes = [];
                    foreach ($objCache as $obj) {
                        foreach ($pm->getEstrategias($obj['objetivo_id']) as $e) { $existentes[] = $e; }
                    }
                    $yaExiste = function($nombre) use ($existentes) {
                        foreach ($existentes as $e) {
                            similar_text(strtolower($e['estrategia_nombre'] ?? ''), strtolower($nombre), $sim);
                            if ($sim > 70) return true;
                        }
                        return false;
                    };
                    $objetivos = $pm->getObjetivos($planId);
                    foreach ($jsonData as $ini) {
                        if ($yaExiste($ini['nombre'] ?? '')) continue;
                        $oid = null; $bestSim = 0;
                        foreach ($objetivos as $obj) {
                            similar_text(strtolower($obj['objetivo_nombre'] ?? ''), strtolower($ini['nombre'] ?? ''), $sim);
                            if ($sim > 40 && $sim > $bestSim) { $bestSim = $sim; $oid = $obj['objetivo_id']; }
                        }
                        $pm->createEstrategia([
                            'estrategia_objetivo_id' => $oid,
                            'estrategia_nombre' => $ini['nombre'] ?? '',
                            'estrategia_descripcion' => $ini['descripcion'] ?? '',
                            'estrategia_tipo' => $ini['tipo'] ?? 'crecimiento',
                            'estrategia_prioridad' => $ini['prioridad'] ?? 'medio',
                            'estrategia_presupuesto' => (float)($ini['presupuesto'] ?? 0),
                        ]);
                        $creadas++;
                    }
                }
                $result['creadas'] = $creadas;
            }

            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
