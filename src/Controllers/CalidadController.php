<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class CalidadController {
    use \SafeQuery;
    private $core;

    public function __construct() {
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
    }

    public function dashboard(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $sectorNombre = $empresa['sector_nombre'] ?? 'General';
        $tiposPorSector = [
            'Salud' => ['SUA','ISO7101','Habilitacion'],
            'Inmobiliario' => ['INMOBILIARIO'],
            'Logística Farmacéutica' => ['LOGFARMA'],
            'Tecnología' => ['TECNOLOGIA'],
            'Manufactura' => ['MANUFACTURA'],
            'General' => ['ISO7101','Habilitacion','INMOBILIARIO','LOGFARMA'],
        ];
        $tiposAplicables = $tiposPorSector[$sectorNombre] ?? ['SUA','ISO7101','Habilitacion'];

        $inParams = [];
        $inPlaceholders = [];
        foreach ($tiposAplicables as $tipo) {
            $inParams[] = $tipo;
            $inPlaceholders[] = '?';
        }
        $inClause = implode(',', $inPlaceholders);
        
        $estandares = $this->safeAll(
            "SELECT e.*, 
                    (SELECT evidencia_cumplimiento FROM cal_evidencias_acreditacion WHERE evidencia_estandar_id=e.estandar_id AND evidencia_empresa_id=? ORDER BY evidencia_fecha_evaluacion DESC LIMIT 1) as ultimo_cumplimiento,
                    (SELECT evidencia_puntaje FROM cal_evidencias_acreditacion WHERE evidencia_estandar_id=e.estandar_id AND evidencia_empresa_id=? ORDER BY evidencia_fecha_evaluacion DESC LIMIT 1) as ultimo_puntaje
             FROM cal_estandares_acreditacion e WHERE e.estandar_activo=1 AND e.estandar_tipo IN ($inClause) ORDER BY e.estandar_tipo, e.estandar_grupo",
            array_merge([$empresaId, $empresaId], $inParams)
        );

        $cumplen = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'cumple'));
        $parcial = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'cumple_parcial'));
        $noCumplen = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'no_cumple'));
        $total = count($estandares);
        $pctCumplimiento = $total > 0 ? round(($cumplen / $total) * 100, 1) : 0;

        $porTipo = [];
        foreach ($estandares as $e) {
            $tipo = $e['estandar_tipo'];
            if (!isset($porTipo[$tipo])) $porTipo[$tipo] = ['total' => 0, 'cumple' => 0, 'parcial' => 0, 'no_cumple' => 0];
            $porTipo[$tipo]['total']++;
            $cumpl = $e['ultimo_cumplimiento'] ?? 'no_aplica';
            if ($cumpl === 'cumple') $porTipo[$tipo]['cumple']++;
            elseif ($cumpl === 'cumple_parcial') $porTipo[$tipo]['parcial']++;
            elseif ($cumpl === 'no_cumple') $porTipo[$tipo]['no_cumple']++;
        }

        $pamec = $this->safeAll(
            "SELECT pa.*, p.proceso_nombre FROM cal_pamec_auditorias pa LEFT JOIN proc_procesos p ON pa.pamec_proceso_id=p.proceso_id WHERE pa.pamec_empresa_id=? ORDER BY pa.pamec_fecha_programada DESC LIMIT 10",
            [$empresaId]
        );

        $riesgos = $this->safeAll(
            "SELECT r.*, p.proceso_nombre FROM cal_riesgos r LEFT JOIN proc_procesos p ON r.riesgo_proceso_id=p.proceso_id WHERE r.riesgo_empresa_id=? ORDER BY FIELD(r.riesgo_nivel,'extremo','alto','medio','bajo'), r.riesgo_fecha_identificacion DESC",
            [$empresaId]
        );

        $ncs = $this->safeAll(
            "SELECT * FROM cal_no_conformidades WHERE nc_empresa_id=? ORDER BY nc_fecha_deteccion DESC LIMIT 20",
            [$empresaId]
        );

        $ciclos = $this->safeAll(
            "SELECT * FROM cal_acreditacion_niveles WHERE nivel_empresa_id = ? ORDER BY nivel_estandar_tipo",
            [$empresaId]
        );

        $actividades = $this->safeAll(
            "SELECT a.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre 
             FROM cal_actividades_acreditacion a LEFT JOIN sys_usuarios u ON a.act_responsable_id=u.usuario_id
             WHERE a.act_empresa_id=? ORDER BY a.act_fecha_fin ASC LIMIT 20",
            [$empresaId]
        );

        $reportes = $this->safeAll(
            "SELECT r.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre 
             FROM cal_reportes_regulatorios r LEFT JOIN sys_usuarios u ON r.rep_responsable_id=u.usuario_id
             WHERE r.rep_empresa_id=? ORDER BY r.rep_fecha_limite ASC",
            [$empresaId]
        );

        $pageTitle = 'Sistema de Calidad y Acreditación';
        ob_start(); require BASE_PATH . '/templates/calidad/dashboard.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function autoevaluacion(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $estandares = $this->safeAll(
            "SELECT e.*, ev.evidencia_cumplimiento as ultimo_cumplimiento, ev.evidencia_descripcion, ev.evidencia_plan_mejora, ev.evidencia_id
             FROM cal_estandares_acreditacion e
             LEFT JOIN cal_evidencias_acreditacion ev ON e.estandar_id=ev.evidencia_estandar_id AND ev.evidencia_empresa_id=?
             WHERE e.estandar_activo=1 ORDER BY e.estandar_tipo, e.estandar_grupo",
            [$empresaId]
        );

        $procesos = $this->safeAll(
            'SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id WHERE m.macro_empresa_id=? AND p.proceso_activo=1',
            [$empresaId]
        );

        $pageTitle = 'Autoevaluación de Estándares';
        ob_start(); require BASE_PATH . '/templates/calidad/autoevaluacion.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function guardarAutoevaluacion(): void {
        $empresaId = (int)($_POST['empresa_id']);

        foreach ($_POST['estandar'] as $estandarId => $cumplimiento) {
            $this->safeInsert('cal_evidencias_acreditacion', [
                'evidencia_empresa_id' => $empresaId,
                'evidencia_estandar_id' => (int)$estandarId,
                'evidencia_proceso_id' => $_POST['proceso'][$estandarId] ? (int)$_POST['proceso'][$estandarId] : null,
                'evidencia_descripcion' => $_POST['evidencia'][$estandarId] ?? '',
                'evidencia_cumplimiento' => $cumplimiento,
                'evidencia_plan_mejora' => $_POST['plan_mejora'][$estandarId] ?? '',
                'evidencia_fecha_evaluacion' => date('Y-m-d'),
                'evidencia_evaluador_id' => Auth::userId(),
            ]);
        }

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'autoevaluacion', 'calidad', 'estandares', $empresaId);
        header('Location: /calidad?saved=1'); exit;
    }

    public function reporte(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $estandares = $this->safeAll(
            "SELECT e.*, ev.evidencia_cumplimiento as ultimo_cumplimiento
             FROM cal_estandares_acreditacion e
             LEFT JOIN cal_evidencias_acreditacion ev ON e.estandar_id=ev.evidencia_estandar_id AND ev.evidencia_empresa_id=?
             WHERE e.estandar_activo=1 ORDER BY e.estandar_tipo, e.estandar_grupo",
            [$empresaId]
        );

        $ncs = $this->safeAll("SELECT * FROM cal_no_conformidades WHERE nc_empresa_id=? ORDER BY nc_fecha_deteccion DESC", [$empresaId]);
        $pamec = $this->safeAll("SELECT * FROM cal_pamec_auditorias WHERE pamec_empresa_id=? ORDER BY pamec_fecha_programada DESC", [$empresaId]);
        $riesgos = $this->safeAll("SELECT * FROM cal_riesgos WHERE riesgo_empresa_id=? ORDER BY FIELD(riesgo_nivel,'extremo','alto','medio','bajo')", [$empresaId]);
        $reportes = $this->safeAll("SELECT * FROM cal_reportes_regulatorios WHERE rep_empresa_id=? ORDER BY rep_fecha_limite ASC", [$empresaId]);

        require BASE_PATH . '/templates/calidad/reporte.php';
    }

    public function crearActividad(): void {
        $this->safeInsert('cal_actividades_acreditacion', [
            'act_empresa_id' => (int)$_POST['empresa_id'],
            'act_estandar_tipo' => $_POST['estandar_tipo'],
            'act_descripcion' => $_POST['descripcion'],
            'act_responsable_id' => Auth::userId(),
            'act_fecha_inicio' => date('Y-m-d'),
            'act_fecha_fin' => $_POST['fecha_fin'] ?? date('Y-m-d'),
        ]);
        header('Location: /calidad?act=1'); exit;
    }

    public function crearReporte(): void {
        $this->safeInsert('cal_reportes_regulatorios', [
            'rep_empresa_id' => (int)$_POST['empresa_id'],
            'rep_ente_control' => $_POST['ente_control'],
            'rep_nombre' => $_POST['nombre'],
            'rep_norma' => $_POST['norma'] ?? '',
            'rep_periodicidad' => $_POST['periodicidad'] ?? 'trimestral',
            'rep_fecha_limite' => $_POST['fecha_limite'],
            'rep_estado' => $_POST['estado'] ?? 'pendiente',
            'rep_responsable_id' => Auth::userId(),
        ]);
        header('Location: /calidad?rep=1'); exit;
    }

    public function cargarCriteriosMinisterio(): void {
        $tipo = $_GET['tipo'] ?? 'SUA';
        $criterios = $this->safeAll(
            "SELECT * FROM cal_estandares_acreditacion WHERE estandar_tipo=? AND estandar_activo=1 ORDER BY estandar_grupo, estandar_codigo",
            [$tipo]
        );
        header('Content-Type: application/json');
        echo json_encode(['tipo' => $tipo, 'total' => count($criterios), 'criterios' => $criterios], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function actualizarChecklist(): void {
        $itemId = (int)$_POST['item_id'];
        $this->safeUpdate('cal_checklist_items', [
            'item_servicio' => $_POST['item_servicio'] ?? '',
            'item_criterio' => $_POST['item_criterio'] ?? '',
            'item_estandar' => $_POST['item_estandar'] ?? '',
            'item_tipo' => $_POST['item_tipo'] ?? 'general',
            'item_orden' => (int)($_POST['item_orden'] ?? 0),
            'item_activo' => (int)($_POST['item_activo'] ?? 1),
        ], 'item_id = ?', [$itemId]);
        header('Location: /calidad/checklist?updated=1'); exit;
    }

    public function eliminarChecklist(): void {
        $itemId = (int)$_POST['item_id'];
        $this->safeUpdate('cal_checklist_items', ['item_activo' => 0], 'item_id = ?', [$itemId]);
        header('Location: /calidad/checklist?deleted=1'); exit;
    }
}
