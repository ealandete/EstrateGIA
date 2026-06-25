<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class WorkbenchController {
    use \SafeQuery;
    private $core;
    private PlanManager $pm;

    public function __construct() {
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
        $this->pm = new PlanManager();
    }

    public function workbench(int $planId, int $faseId): void {
        $plan = $this->pm->getPlan($planId);
        $fase = $this->pm->getFase($faseId);
        if (!$plan || !$fase) { http_response_code(404); echo 'No encontrado'; return; }

        $todasLasFases = $this->pm->getFases($planId);

        // Si la fase ya está completada, mostrar con aviso (no redirigir automáticamente)
        $faseCompletada = in_array($fase['fase_estado'], ['completada', 'aprobada']);

        // Verificar ruta crítica: no se puede acceder sin completar fases previas
        $fasesPreviasPendientes = [];
        $faseActualIdx = 0;

        foreach ($todasLasFases as $i => $f) {
            if ($f['fase_id'] == $faseId) {
                $faseActualIdx = $i;
                break;
            }
            if (!in_array($f['fase_estado'], ['completada', 'aprobada'])) {
                $fasesPreviasPendientes[] = $f;
            }
        }

        $bloquearAcceso = !empty($fasesPreviasPendientes);

        $empresa = $this->pm->getEmpresa($plan['plan_empresa_id']);
        $nombreFase = strtolower($fase['fase_nombre'] ?? '');
        $metodologia = strtolower($plan['metodologia_nombre'] ?? '');

        $tool = $this->detectarHerramientaFase($nombreFase, $metodologia);
        if (!empty($_GET['tool'])) $tool = $_GET['tool'];

        $foda = $this->pm->getFODA($planId);
        $pestel = $this->pm->getPESTEL($planId);
        $guia = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true);
        $pasos = $guia['pasos'] ?? [];

        require_once BASE_PATH . '/lib/DocManager.php';
        $docManager = new DocManager();
        $normasAplicables = ($empresa['empresa_sector_id'] ?? null) ? $docManager->getNormas($empresa['empresa_sector_id']) : [];

        $pageTitle = htmlspecialchars($fase['fase_nombre']) . ' - ' . htmlspecialchars($plan['metodologia_nombre']);
        ob_start();
        require BASE_PATH . '/templates/tools/workbench_layout.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function detectarHerramientaFase(string $fase, string $metodologia): string {
        $fase = mb_strtolower($fase, 'UTF-8');
        $met = mb_strtolower($metodologia, 'UTF-8');

        $herramientasEspecificas = [
            // BSC (completo)
            'análisis del entorno' => 'pestel',
            'definición de visión' => 'vision',
            'mapa estratégico' => 'bsc',
            'objetivos' => $met === 'okr' ? 'okr' : ($met === 'hoshin' ? 'generic' : 'bsc'),
            'cuadro de mando' => 'bsc',
            'despliegue en cascada' => 'bsc',
            'iniciativas' => 'iniciativas',
            'indicadores' => 'indicadores',
            'evaluación y ajuste' => 'evaluacion',
            'evaluación del desempeño' => 'evaluacion',

            // OKR
            'contexto estratégico' => 'vision',
            'key result' => 'okr',
            'objetivos maestros' => 'okr',
            'despliegue okrs' => 'okr',
            'ejecución y check' => 'okr',
            'cierre y retrospectiva' => 'okr',

            // Escenarios (corregido)
            'identificación de incertidumbres' => 'scenarios',
            'construcción de escenarios' => 'scenarios',
            'estrategias robusta' => 'scenarios',
            'monitoreo de señales' => 'scenarios',

            // Hoshin Kanri
            'plan hoshin' => 'vision',
            'despliegue en catchball' => 'hoshin',
            'control diario' => 'hoshin',
            'revisión del presidente' => 'hoshin',

            // Design Thinking (builder unificado adaptativo por fase)
            'empatizar' => 'design',
            'definir' => 'design',
            'idear' => 'design',
            'prototipar' => 'design',
            'testear' => 'design',

            // Genéricos (al final para no capturar antes)
            'foda' => 'foda',
            'pestel' => 'pestel',
            'análisis' => 'pestel',
            'visión' => 'vision',
            'misión' => 'vision',
        ];

        foreach ($herramientasEspecificas as $keyword => $tool) {
            if (mb_strpos($fase, $keyword) !== false) {
                return $tool;
            }
        }

        return 'generic';
    }

    // Guardar FODA
    public function saveFoda(): void {
        $planId = (int)$_POST['plan_id'];
        $fodaData = json_decode($_POST['foda_data'] ?? $_POST['data'] ?? '{}', true);
        if (empty($fodaData)) { echo json_encode(['success'=>false,'error'=>'sin datos']); exit; }

        $existente = $this->pm->getFODA($planId);
        if ($existente) {
            $contenidoActual = json_decode($existente['analisis_contenido'] ?? '{}', true) ?: [];
            $merged = array_merge($contenidoActual, $fodaData);
            $this->safeUpdate('plan_analisis_contexto', ['analisis_contenido' => json_encode($merged)], 'analisis_id = ?', [$existente['analisis_id']]);
        } else {
            $this->pm->createAnalisis([
                'analisis_plan_id' => $planId, 'analisis_tipo' => 'FODA', 'analisis_titulo' => 'Análisis FODA',
                'analisis_contenido' => $fodaData, 'analisis_conclusiones' => 'Actualizado desde herramienta interactiva',
                'analisis_fecha' => date('Y-m-d'), 'analisis_responsable_id' => Auth::userId(),
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // Guardar OKR
    public function saveOkr(): void {
        $planId = (int)$_POST['plan_id'];
        $objNombre = $_POST['obj_nombre'] ?? '';
        $krs = json_decode($_POST['krs'] ?? '[]', true);

        $this->pm->createObjetivo([
            'objetivo_plan_id' => $planId,
            'objetivo_nombre' => $objNombre,
            'objetivo_descripcion' => json_encode($krs),
            'objetivo_tipo' => 'estrategico',
            'objetivo_perspectiva' => 'cliente',
            'objetivo_prioridad' => 'alto',
            'objetivo_responsable_id' => Auth::userId(),
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // Guardar objetivo (BSC)
    public function saveObjetivo(): void {
        $planId = (int)$_POST['plan_id'];
        $this->pm->createObjetivo([
            'objetivo_plan_id' => $planId,
            'objetivo_nombre' => $_POST['obj_nombre'] ?? '',
            'objetivo_perspectiva' => $_POST['obj_perspectiva'] ?? 'financiera',
            'objetivo_tipo' => 'estrategico',
            'objetivo_responsable_id' => Auth::userId(),
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // Completar fase - solo actualiza la fase, NO cascada ciega
    public function completarFase(): void {
        $planId = (int)$_POST['plan_id'];
        $faseId = (int)$_POST['fase_id'];

        $this->pm->updateFase($faseId, [
            'fase_estado' => 'completada',
            'fase_avance_porcentaje' => 100
        ]);

        $fases = $this->pm->getFases($planId);
        foreach ($fases as $i => $f) {
            if ($f['fase_id'] == $faseId && isset($fases[$i + 1])) {
                $this->pm->updateFase($fases[$i + 1]['fase_id'], [
                    'fase_estado' => 'en_proceso'
                ]);
                break;
            }
        }

        // Calcular avance real del plan basado en fases completadas
        $total = count($fases);
        $completadas = 0;
        foreach ($fases as $f) {
            if ($f['fase_estado'] === 'completada') $completadas++;
            elseif ($f['fase_id'] == $faseId) $completadas++;
        }
        $avance = $total > 0 ? round(($completadas / $total) * 100, 2) : 0;

        $this->pm->updatePlan($planId, [
            'plan_avance_porcentaje' => $avance,
            'plan_estado' => $avance >= 100 ? 'completado' : 'ejecucion'
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'avance' => $avance]);
        exit;
    }

    // Guardar avance del wizard (contenido del textarea)
    public function guardarAvance(): void {
        $planId = (int)$_POST['plan_id'];
        $faseId = (int)$_POST['fase_id'];
        $contenido = $_POST['contenido'] ?? '';

        $fase = $this->pm->getFase($faseId);
        $guiaExistente = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true) ?: [];
        $guiaExistente['contenido_redactado'] = $contenido;
        $guiaExistente['ultima_modificacion'] = date('Y-m-d H:i:s');

        $this->pm->updateFase($faseId, [
            'fase_guia_paso_a_paso' => json_encode($guiaExistente),
            'fase_estado' => $fase['fase_estado'] === 'pendiente' ? 'en_proceso' : $fase['fase_estado']
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Avance guardado']);
        exit;
    }

    // Guardar cualquier tipo de contenido de herramienta
    public function saveScenarios(): void {
        $planId = (int)$_POST['plan_id'];
        $faseId = (int)$_POST['fase_id'];
        $data = json_decode($_POST['data'] ?? '{}', true);
        if (empty($data)) { echo json_encode(['success' => false]); exit; }

        $fase = $this->pm->getFase($faseId);
        $guiaExistente = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true) ?: [];
        
        // Fusionar todos los datos nuevos con los existentes
        foreach ($data as $key => $val) {
            $guiaExistente[$key] = $val;
        }

        // Si son datos de FODA o PESTEL, guardar también en plan_analisis_contexto
        if (isset($data['fortalezas']) || isset($data['politico']) || isset($data['economico'])) {
            $tipo = isset($data['fortalezas']) ? 'FODA' : 'PESTEL';
            $this->pm->createAnalisis([
                'analisis_plan_id' => $planId,
                'analisis_tipo' => $tipo,
                'analisis_titulo' => 'Análisis ' . $tipo,
                'analisis_contenido' => $data,
                'analisis_conclusiones' => 'Guardado desde herramienta',
                'analisis_fecha' => date('Y-m-d'),
                'analisis_responsable_id' => Auth::userId(),
            ]);
        }

        $this->pm->updateFase($faseId, [
            'fase_guia_paso_a_paso' => json_encode($guiaExistente),
            'fase_estado' => in_array($fase['fase_estado'], ['completada']) ? 'completada' : 'en_proceso',
            'fase_avance_porcentaje' => 50
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function generarPestel(): void {
        $planId = (int)$_POST['plan_id'];
        $plan = $this->pm->getPlan($planId);
        $empresa = $this->pm->getEmpresa($plan['plan_empresa_id']);
        $sector = $empresa['sector_nombre'] ?? 'General';

        $pestelData = $this->generarPestelPorSector($sector);

        $this->pm->createAnalisis([
            'analisis_plan_id' => $planId,
            'analisis_tipo' => 'PESTEL',
            'analisis_titulo' => 'Análisis PESTEL',
            'analisis_contenido' => $pestelData,
            'analisis_conclusiones' => 'Generado para sector ' . $sector,
            'analisis_fecha' => date('Y-m-d'),
            'analisis_responsable_id' => Auth::userId(),
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'pestel' => $pestelData]);
        exit;
    }

    private function generarPestelPorSector(string $sector): array {
        $templates = [
            'Salud' => [
                'politico' => ['Reforma al sistema de salud en debate legislativo','Cambios en políticas de aseguramiento','Regulación de tarifas de medicamentos'],
                'economico' => ['Crecimiento del gasto en salud del PIB','Presión inflacionaria en insumos médicos','Tasa de cambio afecta equipos importados'],
                'social' => ['Envejecimiento poblacional aumenta demanda','Pacientes más informados y exigentes','Migración de profesionales de salud'],
                'tecnologico' => ['Telemedicina y consulta virtual en expansión','IA en diagnóstico por imágenes','Historia clínica electrónica interoperable'],
                'ecologico' => ['Gestión de residuos hospitalarios más estricta','Eficiencia energética en infraestructura','Huella de carbono del sector salud'],
                'legal' => ['Ley de protección de datos personales','Normas de habilitación más exigentes','Responsabilidad médica y jurisprudencia'],
            ],
            'Logística Farmacéutica' => [
                'politico' => ['Regulación INVIMA de importación de medicamentos','Política farmacéutica nacional','Acuerdos comerciales internacionales'],
                'economico' => ['Volatilidad del dólar afecta costos','Crecimiento del mercado farmacéutico','Inversión en infraestructura logística'],
                'social' => ['Mayor demanda de medicamentos biológicos','Conciencia sobre cadena de frío','Envejecimiento aumenta consumo'],
                'tecnologico' => ['Blockchain para trazabilidad de medicamentos','IoT en monitoreo de temperatura','Automatización de almacenes'],
                'ecologico' => ['Disposición final de medicamentos vencidos','Logística verde y vehículos eléctricos','Empaques sostenibles'],
                'legal' => ['Buenas Prácticas de Distribución (GDP)','Resolución 1160 de INVIMA','Normas de seguridad en transporte'],
            ],
        ];

        return $templates[$sector] ?? [
            'politico' => ['Estabilidad del gobierno','Políticas fiscales y tributarias','Regulación del sector'],
            'economico' => ['Crecimiento del PIB','Tasas de interés','Inflación y poder adquisitivo'],
            'social' => ['Cambios demográficos','Tendencias de consumo','Nivel educativo de la fuerza laboral'],
            'tecnologico' => ['Digitalización del sector','Automatización de procesos','Innovación en productos/servicios'],
            'ecologico' => ['Regulación ambiental','Sostenibilidad en la cadena de valor','Cambio climático'],
            'legal' => ['Legislación laboral','Protección de datos','Normativas específicas del sector'],
        ];
    }

    // ===== CRUD Objetivos =====
    public function editObjetivo(): void {
        $id = (int)($_POST['obj_id'] ?? $_POST['id']);
        $data = [];
        if (!empty($_POST['obj_nombre'] ?? $_POST['nombre'] ?? '')) {
            $data['objetivo_nombre'] = $_POST['obj_nombre'] ?? $_POST['nombre'];
        }
        if (!empty($_POST['obj_perspectiva'] ?? $_POST['perspectiva'] ?? '')) {
            $data['objetivo_perspectiva'] = $_POST['obj_perspectiva'] ?? $_POST['perspectiva'];
        }
        if (!empty($_POST['descripcion'] ?? '')) {
            $data['objetivo_descripcion'] = $_POST['descripcion'];
        }
        if (isset($_POST['peso_relativo'])) {
            $data['objetivo_peso_relativo'] = (float)$_POST['peso_relativo'];
        }
        if (empty($data)) $data['objetivo_nombre'] = $_POST['obj_nombre'] ?? $_POST['nombre'] ?? '';
        $this->pm->updateObjetivo($id, $data);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function deleteObjetivo(): void {
        $this->pm->deleteObjetivo((int)($_POST['obj_id'] ?? $_POST['id']));
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // ===== CRUD Indicadores =====
    public function saveIndicador(): void {
        $data = json_decode($_POST['data'] ?? '{}', true);
        if (empty($data['indicador_nombre'])) { echo json_encode(['success'=>false,'error'=>'Nombre requerido']); exit; }
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();
        $id = $im->createIndicador($data);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    public function editIndicador(): void {
        $data = json_decode($_POST['data'] ?? '{}', true);
        $id = (int)($data['indicador_id'] ?? 0);
        if (!$id || empty($data['indicador_nombre'])) { echo json_encode(['success'=>false]); exit; }
        unset($data['indicador_id']);
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();
        $im->updateIndicador($id, $data);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function deleteIndicador(): void {
        $id = (int)($_POST['id'] ?? 0);
        require_once BASE_PATH . '/lib/IndicatorManager.php';
        $im = new IndicatorManager();
        $im->deleteIndicador($id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // ===== CRUD Estrategias =====
    public function saveEstrategia(): void {
        $raw = $_POST['data'] ?? null;
        $d = $raw ? json_decode($raw, true) : $_POST;
        $this->pm->createEstrategia([
            'estrategia_objetivo_id' => (int)($d['estrategia_objetivo_id'] ?? $d['objetivo_id'] ?? 0),
            'estrategia_nombre' => $d['estrategia_nombre'] ?? $d['nombre'] ?? '',
            'estrategia_descripcion' => $d['estrategia_descripcion'] ?? $d['descripcion'] ?? '',
            'estrategia_tipo' => $d['estrategia_tipo'] ?? $d['tipo'] ?? 'ofensiva',
            'estrategia_prioridad' => $d['estrategia_prioridad'] ?? $d['prioridad'] ?? 'media',
            'estrategia_presupuesto' => (float)($d['estrategia_presupuesto'] ?? $d['presupuesto'] ?? 0),
            'estrategia_avance_porcentaje' => (float)($d['estrategia_avance_porcentaje'] ?? 0),
            'estrategia_responsable_id' => (int)($d['estrategia_responsable_id'] ?? $d['responsable_id'] ?? 0) ?: null,
        ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function editEstrategia(): void {
        $id = (int)$_POST['id'];
        $this->pm->updateEstrategia($id, [
            'estrategia_nombre' => $_POST['nombre'] ?? '',
            'estrategia_descripcion' => $_POST['descripcion'] ?? '',
            'estrategia_tipo' => $_POST['tipo'] ?? 'ofensiva',
            'estrategia_estado' => $_POST['estado'] ?? null,
            'estrategia_avance_porcentaje' => (int)($_POST['avance'] ?? 0),
        ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function deleteEstrategia(): void {
        $this->pm->deleteEstrategia((int)$_POST['id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // ===== CRUD Actividades =====
    public function saveActividad(): void {
        $this->pm->createActividad([
            'actividad_estrategia_id' => (int)$_POST['estrategia_id'],
            'actividad_nombre' => $_POST['nombre'] ?? '',
            'actividad_descripcion' => $_POST['descripcion'] ?? '',
            'actividad_fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'actividad_fecha_fin' => $_POST['fecha_fin'] ?? null,
            'actividad_prioridad' => $_POST['prioridad'] ?? 'media',
            'actividad_responsable_id' => (int)$_POST['responsable_id'] ?? null,
        ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function editActividad(): void {
        $id = (int)$_POST['id'];
        $this->pm->updateActividad($id, [
            'actividad_nombre' => $_POST['nombre'] ?? '',
            'actividad_descripcion' => $_POST['descripcion'] ?? '',
            'actividad_estado' => $_POST['estado'] ?? null,
            'actividad_avance_porcentaje' => (int)($_POST['avance'] ?? 0),
            'actividad_fecha_fin_real' => $_POST['fecha_fin_real'] ?? null,
        ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function deleteActividad(): void {
        $this->pm->deleteActividad((int)$_POST['id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
