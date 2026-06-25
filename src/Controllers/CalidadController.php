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

    public function habilitacionDashboard(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $servicios = $this->safeAll("SELECT h.*, (SELECT COUNT(*) FROM cal_habilitacion_estandares he WHERE he.hab_id=h.hab_id AND he_cumple='si') as estandares_cumplen, (SELECT COUNT(*) FROM cal_habilitacion_estandares he WHERE he.hab_id=h.hab_id) as total_estandares FROM cal_habilitacion h WHERE h.empresa_id=? ORDER BY FIELD(h.hab_estado,'habilitado','en_proceso','pendiente_renovacion','cerrado'), h.hab_servicio", [$empresaId]);
        $pageTitle = 'Habilitación - Res 3100/2019';
        ob_start(); require BASE_PATH . '/templates/calidad/habilitacion.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function evaluarEstandarHabilitacion(): void {
        $heId = (int)$_POST['he_id'];
        $this->safeUpdate('cal_habilitacion_estandares', ['he_cumple' => $_POST['cumple'] ?? 'no', 'he_evidencia' => $_POST['evidencia'] ?? '', 'he_fecha_verificacion' => date('Y-m-d')], 'he_id = ?', [$heId]);
        header('Location: /habilitacion?evaluado=1'); exit;
    }

    // ============================================================
    // FARMACOVIGILANCIA
    // ============================================================
    public function farmacovigilancia(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $medicamentos = $this->safeAll("SELECT * FROM far_medicamentos WHERE empresa_id=? AND med_activo=1 ORDER BY med_clasificacion, med_nombre_generico", [$empresaId]);
        $eventos = $this->safeAll("SELECT ev.*, m.med_nombre_generico, m.med_laboratorio FROM far_eventos_adversos ev JOIN far_medicamentos m ON ev.med_id=m.med_id WHERE ev.empresa_id=? ORDER BY ev.evento_fecha_reporte DESC", [$empresaId]);
        $pageTitle = 'Farmacovigilancia - Res 1403/2007';
        ob_start(); require BASE_PATH . '/templates/calidad/farmacovigilancia.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }
    public function crearEventoFarmaco(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('far_eventos_adversos', [
            'empresa_id' => $empresaId, 'med_id' => (int)$_POST['med_id'], 'evento_tipo' => $_POST['tipo'] ?? 'reaccion_adversa',
            'evento_paciente_identificacion' => $_POST['paciente_id'] ?? '', 'evento_paciente_edad' => (int)($_POST['edad'] ?? 0),
            'evento_paciente_sexo' => $_POST['sexo'] ?? null, 'evento_fecha_ocurrencia' => $_POST['fecha_ocurrencia'] ?? date('Y-m-d'),
            'evento_fecha_reporte' => date('Y-m-d'), 'evento_descripcion' => $_POST['descripcion'] ?? '',
            'evento_dosis_administrada' => $_POST['dosis'] ?? '', 'evento_via_administracion' => $_POST['via'] ?? '',
            'evento_lote' => $_POST['lote'] ?? '', 'evento_gravedad' => $_POST['gravedad'] ?? 'moderada',
            'evento_causalidad' => $_POST['causalidad'] ?? 'posible', 'evento_responsable_id' => Auth::userId(),
        ]);
        header('Location: /farmacovigilancia?ok=1'); exit;
    }
    public function reportarInvimaFarmaco(): void {
        $id = (int)$_POST['evento_id'];
        $this->safeUpdate('far_eventos_adversos', ['evento_reporte_invima' => 1, 'evento_fecha_reporte_invima' => date('Y-m-d'), 'evento_invima_folio' => $_POST['folio'] ?? '', 'evento_estado' => 'cerrado'], 'evento_id = ?', [$id]);
        header('Location: /farmacovigilancia?invima=1'); exit;
    }

    // ============================================================
    // TECNOVIGILANCIA
    // ============================================================
    public function tecnovigilancia(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $dispositivos = $this->safeAll("SELECT * FROM tec_dispositivos WHERE empresa_id=? AND disp_activo=1 ORDER BY disp_clasificacion_riesgo DESC, disp_nombre", [$empresaId]);
        $eventos = $this->safeAll("SELECT ev.*, d.disp_nombre, d.disp_marca FROM tec_eventos_adversos ev JOIN tec_dispositivos d ON ev.disp_id=d.disp_id WHERE ev.empresa_id=? ORDER BY ev.tec_evento_fecha_reporte DESC", [$empresaId]);
        $pageTitle = 'Tecnovigilancia - Res 4816/2008';
        ob_start(); require BASE_PATH . '/templates/calidad/tecnovigilancia.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }
    public function crearEventoTecno(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('tec_eventos_adversos', [
            'empresa_id' => $empresaId, 'disp_id' => (int)$_POST['disp_id'], 'tec_evento_tipo' => $_POST['tipo'] ?? 'falla_funcionamiento',
            'tec_evento_paciente_identificacion' => $_POST['paciente_id'] ?? '', 'tec_evento_fecha_ocurrencia' => $_POST['fecha_ocurrencia'] ?? date('Y-m-d'),
            'tec_evento_fecha_reporte' => date('Y-m-d'), 'tec_evento_descripcion' => $_POST['descripcion'] ?? '',
            'tec_evento_gravedad' => $_POST['gravedad'] ?? 'moderada', 'tec_evento_accion_inmediata' => $_POST['accion'] ?? '',
            'tec_evento_responsable_id' => Auth::userId(),
        ]);
        header('Location: /tecnovigilancia?ok=1'); exit;
    }
    public function reportarInvimaTecno(): void {
        $id = (int)$_POST['evento_id'];
        $this->safeUpdate('tec_eventos_adversos', ['tec_evento_reporte_invima' => 1, 'tec_evento_fecha_reporte_invima' => date('Y-m-d'), 'tec_evento_invima_folio' => $_POST['folio'] ?? '', 'tec_evento_estado' => 'cerrado'], 'tec_evento_id = ?', [$id]);
        header('Location: /tecnovigilancia?invima=1'); exit;
    }

    // ============================================================
    // RIPS
    // ============================================================
    public function ripsDashboard(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $periodo = $_GET['periodo'] ?? date('Y-m');
        $rips = $this->safeAll("SELECT * FROM cal_rips WHERE empresa_id=? AND rips_periodo=? ORDER BY rips_fecha_ingreso DESC", [$empresaId, $periodo]);
        $resumen = $this->safeAll("SELECT rips_tipo, COUNT(*) as total, SUM(rips_valor) as valor_total FROM cal_rips WHERE empresa_id=? AND rips_periodo=? GROUP BY rips_tipo", [$empresaId, $periodo]);
        $pageTitle = 'RIPS - Registro Individual de Prestaciones';
        ob_start(); require BASE_PATH . '/templates/calidad/rips.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }
    public function crearRIPS(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('cal_rips', [
            'empresa_id' => $empresaId, 'rips_periodo' => $_POST['periodo'] ?? date('Y-m'), 'rips_tipo' => $_POST['tipo'] ?? 'consulta',
            'rips_paciente_tipo_id' => $_POST['tipo_id'] ?? '', 'rips_paciente_id' => $_POST['paciente_id'] ?? '',
            'rips_paciente_nombre' => $_POST['nombre'] ?? '', 'rips_paciente_edad' => (int)($_POST['edad'] ?? 0),
            'rips_paciente_sexo' => $_POST['sexo'] ?? null, 'rips_paciente_regimen' => $_POST['regimen'] ?? 'contributivo',
            'rips_fecha_ingreso' => $_POST['fecha_ingreso'] ?? date('Y-m-d'), 'rips_diagnostico_principal' => $_POST['diagnostico'] ?? '',
            'rips_codigo_cups' => $_POST['cups'] ?? '', 'rips_finalidad' => $_POST['finalidad'] ?? 'diagnostico',
            'rips_ambito' => $_POST['ambito'] ?? 'ambulatorio', 'rips_forma_pago' => $_POST['forma_pago'] ?? 'evento',
            'rips_valor' => (float)($_POST['valor'] ?? 0), 'rips_eps' => $_POST['eps'] ?? '',
        ]);
        header('Location: /rips?ok=1'); exit;
    }
    public function exportarRIPS(string $periodo): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $rips = $this->safeAll("SELECT * FROM cal_rips WHERE empresa_id=? AND rips_periodo=? ORDER BY rips_fecha_ingreso", [$empresaId, $periodo]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="RIPS_' . $periodo . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Tipo ID','Num ID','Nombre','Edad','Sexo','Regimen','Fecha Ingreso','Hora','Fecha Egreso','DX Principal','DX Relacionado','Causa Externa','CUPS','CUPS2','CUPS3','Finalidad','Ambito','Forma Pago','Valor','EPS']);
        foreach ($rips as $r) {
            fputcsv($out, [$r['rips_paciente_tipo_id'], $r['rips_paciente_id'], $r['rips_paciente_nombre'], $r['rips_paciente_edad'], $r['rips_paciente_sexo'], $r['rips_paciente_regimen'], $r['rips_fecha_ingreso'], $r['rips_hora_ingreso'], $r['rips_fecha_egreso'], $r['rips_diagnostico_principal'], $r['rips_diagnostico_relacionado'], $r['rips_causa_externa'], $r['rips_codigo_cups'], $r['rips_codigo_cups_2'], $r['rips_codigo_cups_3'], $r['rips_finalidad'], $r['rips_ambito'], $r['rips_forma_pago'], $r['rips_valor'], $r['rips_eps']]);
        }
        fclose($out);
        $this->safeUpdate('cal_rips', ['rips_exportado' => 1, 'rips_fecha_exportacion' => date('Y-m-d')], 'empresa_id = ? AND rips_periodo = ? AND rips_exportado = 0', [$empresaId, $periodo]);
        exit;
    }
}
