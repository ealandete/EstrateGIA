<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';
require_once BASE_PATH . '/lib/PlanManager.php';

/**
 * SIG - Sistema Integrado de Gestión
 * Dashboard que muestra la interconexión de todos los módulos
 */
class SIGController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        // KPIs consolidados de todos los módulos
        $kpis = [
            'calidad' => [
                'nc_abiertas' => $this->safe("SELECT COUNT(*) FROM cal_no_conformidades WHERE nc_empresa_id=? AND nc_estado!='cerrada'", [$empresaId]),
                'pamec_pendientes' => $this->safe("SELECT COUNT(*) FROM cal_pamec_auditorias WHERE pamec_empresa_id=? AND pamec_estado NOT IN('cerrada','ejecutada')", [$empresaId]),
                'acreditacion_pct' => round((float)($this->safe("SELECT AVG(nivel_puntaje_actual) FROM cal_acreditacion_niveles WHERE nivel_empresa_id=?", [$empresaId]) ?? 0), 1),
            ],
            'sst' => [
                'peligros_inaceptables' => $this->safe("SELECT COUNT(*) FROM sst_peligros WHERE peligro_empresa_id=? AND peligro_nivel='inaceptable'", [$empresaId]),
                'accidentalidad' => (float)($this->safe("SELECT sind_valor FROM sst_indicadores WHERE sind_empresa_id=? AND sind_nombre LIKE '%Accidentalidad%' LIMIT 1", [$empresaId]) ?? 0),
            ],
            'ambiental' => [
                'aspectos_altos' => $this->safe("SELECT COUNT(*) FROM amb_aspectos WHERE asp_empresa_id=? AND asp_significancia='alto'", [$empresaId]),
                'residuos_pct' => (float)($this->safe("SELECT aind_valor FROM amb_indicadores WHERE aind_empresa_id=? AND aind_nombre LIKE '%Residuos%' LIMIT 1", [$empresaId]) ?? 0),
            ],
            'estrategico' => [
                'plan_avance' => round((float)($this->safe("SELECT AVG(plan_avance_porcentaje) FROM plan_planes_estrategicos WHERE plan_empresa_id=? AND plan_activo=1", [$empresaId]) ?? 0), 1),
                'procesos_documentados' => $this->safe("SELECT COUNT(*) FROM proc_procesos p JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id WHERE m.macro_empresa_id=? AND p.proceso_estado='documentado'", [$empresaId]),
            ],
        ];

        // YoY comparativo
        $anioActual = (int)date('Y');
        $anioAnterior = $anioActual - 1;
        $kpisYoY = [
            'nc_abiertas' => ['actual'=>$kpis['calidad']['nc_abiertas'], 'anterior'=>$this->safe("SELECT COUNT(*) FROM cal_no_conformidades WHERE nc_empresa_id=? AND nc_estado!='cerrada' AND YEAR(nc_fecha_deteccion)<=?", [$empresaId,$anioAnterior])],
            'peligros_inaceptables' => ['actual'=>$kpis['sst']['peligros_inaceptables'], 'anterior'=>$this->safe("SELECT COUNT(*) FROM sst_peligros WHERE peligro_empresa_id=? AND peligro_nivel='inaceptable' AND YEAR(created_at)<=?", [$empresaId,$anioAnterior])],
            'aspectos_altos' => ['actual'=>$kpis['ambiental']['aspectos_altos'], 'anterior'=>$this->safe("SELECT COUNT(*) FROM amb_aspectos WHERE asp_empresa_id=? AND asp_significancia='alto' AND YEAR(created_at)<=?", [$empresaId,$anioAnterior])],
            'plan_avance' => ['actual'=>$kpis['estrategico']['plan_avance'], 'anterior'=>round((float)($this->safe("SELECT AVG(plan_avance_porcentaje) FROM plan_planes_estrategicos WHERE plan_empresa_id=? AND plan_activo=1 AND YEAR(created_at)<=?", [$empresaId,$anioAnterior]) ?? 0), 1)],
        ];

        // Tareas urgentes de todos los módulos
        $urgentes = $this->safeAll(
            "SELECT * FROM cal_tareas WHERE tarea_empresa_id=? AND tarea_estado NOT IN('completada','cancelada') AND tarea_fecha_fin <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY tarea_fecha_fin ASC LIMIT 10",
            [$empresaId]
        );

        // Últimas NCs, incidentes SST, hallazgos ambientales
        $ncs = $this->safeAll("SELECT * FROM cal_no_conformidades WHERE nc_empresa_id=? ORDER BY nc_fecha_deteccion DESC LIMIT 5", [$empresaId]);
        $peligros = $this->safeAll("SELECT * FROM sst_peligros WHERE peligro_empresa_id=? ORDER BY created_at DESC LIMIT 5", [$empresaId]);
        $aspectos = $this->safeAll("SELECT * FROM amb_aspectos WHERE asp_empresa_id=? ORDER BY created_at DESC LIMIT 5", [$empresaId]);

        $pageTitle = 'Sistema Integrado de Gestión';

        // Alertas proactivas de vencimiento (30 días)
        $alertas = [];
        $proveedoresVencer = $this->safeAll("SELECT prov_id, prov_nombre, prov_proxima_evaluacion FROM cal_proveedores WHERE prov_empresa_id=? AND prov_estado='activo' AND prov_proxima_evaluacion <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND prov_proxima_evaluacion >= CURDATE() ORDER BY prov_proxima_evaluacion ASC LIMIT 5", [$empresaId]);
        $reqLegalesVencer = $this->safeAll("SELECT sst_req_id, sst_req_norma, sst_req_fecha_limite FROM sst_requisitos_legales WHERE empresa_id=? AND sst_req_fecha_limite <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND sst_req_fecha_limite >= CURDATE() ORDER BY sst_req_fecha_limite ASC LIMIT 5", [$empresaId]);
        $simulacrosVencer = $this->safeAll("SELECT sst_eme_id, sst_eme_nombre, sst_eme_proximo_simulacro FROM sst_emergencias WHERE empresa_id=? AND sst_eme_proximo_simulacro <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND sst_eme_proximo_simulacro >= CURDATE() ORDER BY sst_eme_proximo_simulacro ASC LIMIT 5", [$empresaId]);
        $reqAmbVencer = $this->safeAll("SELECT amb_req_id, amb_req_norma, amb_req_fecha_limite FROM amb_requisitos_legales WHERE empresa_id=? AND amb_req_fecha_limite <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND amb_req_fecha_limite >= CURDATE() ORDER BY amb_req_fecha_limite ASC LIMIT 5", [$empresaId]);
        foreach ($proveedoresVencer as $p) $alertas[] = ['icon'=>'truck','color'=>'#007bff','texto'=>'Evaluación proveedor','nombre'=>htmlspecialchars($p['prov_nombre']),'fecha'=>$p['prov_proxima_evaluacion'],'link'=>'/proveedores?ver='.$p['prov_id']];
        foreach ($reqLegalesVencer as $r) $alertas[] = ['icon'=>'scale-balanced','color'=>'#ffc107','texto'=>'Requisito SST vence','nombre'=>htmlspecialchars($r['sst_req_norma']),'fecha'=>$r['sst_req_fecha_limite'],'link'=>'/sst?seccion=normatividad'];
        foreach ($simulacrosVencer as $s) $alertas[] = ['icon'=>'tower-broadcast','color'=>'#dc3545','texto'=>'Simulacro programado','nombre'=>htmlspecialchars($s['sst_eme_nombre']),'fecha'=>$s['sst_eme_proximo_simulacro'],'link'=>'/sst?seccion=emergencias'];
        foreach ($reqAmbVencer as $r) $alertas[] = ['icon'=>'leaf','color'=>'#28a745','texto'=>'Requisito Amb vence','nombre'=>htmlspecialchars($r['amb_req_norma']),'fecha'=>$r['amb_req_fecha_limite'],'link'=>'/ambiental?seccion=normatividad'];
        usort($alertas, fn($a,$b) => strtotime($a['fecha']) - strtotime($b['fecha']));

        ob_start(); require BASE_PATH . '/templates/sig/dashboard.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }
}
