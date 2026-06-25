<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class EvaluacionController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $im = new IndicatorManager();
        $pm = new PlanManager();
        $periodo = $_GET['periodo'] ?? date('Y-m');
        $planId = (int)($_GET['plan_id'] ?? ($_COOKIE['plan_activo'] ?? 0));
        if (!$planId) {
            header('Location: /planeacion?msg=seleccione_plan');
            exit;
        }
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));

        $plan = $pm->getPlan($planId);
        $nivel = $_GET['nivel'] ?? 'colaboradores';
        $procesoId = (int)($_GET['proceso_id'] ?? 0);

        $uid = (int)(Auth::userId() ?? 0);
        $evaluacion = $im->getEvaluacion($uid, $periodo);
        if (!$evaluacion && !isset($_GET['periodo'])) {
            $ultimo = $this->safe("SELECT evaluacion_periodo FROM ind_evaluaciones_desempeno WHERE evaluacion_usuario_id=? ORDER BY evaluacion_periodo DESC LIMIT 1", [$uid]);
            if ($ultimo) $periodo = $ultimo;
            $evaluacion = $im->getEvaluacion($uid, $periodo);
        }
        if (!$evaluacion) $evaluacion = $im->calcularEvaluacionDesempeno($uid, $periodo);
        if (!$evaluacion) $evaluacion = ['evaluacion_puntaje_cumplimiento'=>0,'evaluacion_puntaje_oportunidad'=>0,'evaluacion_puntaje_calidad'=>0,'evaluacion_puntaje_productividad'=>0,'evaluacion_puntaje_total'=>0,'evaluacion_periodo'=>$periodo];

        $macroprocesos = $this->safeAll(
            "SELECT m.*, 
                    COALESCE((SELECT AVG(e.evaluacion_puntaje_total) 
                     FROM ind_evaluaciones_desempeno e 
                     WHERE e.evaluacion_usuario_id IN (
                         SELECT DISTINCT ma.mapa_usuario_id FROM plan_mapa_actividades ma
                         JOIN plan_actividades a ON ma.mapa_actividad_id = a.actividad_id
                         WHERE a.actividad_proceso_id IN (
                             SELECT p.proceso_id FROM proc_procesos p WHERE p.proceso_macro_id = m.macro_id
                         )
                     ) AND e.evaluacion_periodo = ?), 0) as puntaje_promedio
             FROM proc_macroprocesos m WHERE m.macro_empresa_id = ? AND m.macro_activo = 1
             ORDER BY FIELD(m.macro_tipo,'estrategico','misional','apoyo','evaluacion')",
            [$periodo, $empresaId]
        );

        $procesos = [];
        if ($procesoId) {
            $procesos = $this->safeAll(
                "SELECT p.*, 
                        COALESCE((SELECT AVG(e.evaluacion_puntaje_total) 
                         FROM ind_evaluaciones_desempeno e 
                         WHERE e.evaluacion_usuario_id IN (
                             SELECT DISTINCT ma.mapa_usuario_id FROM plan_mapa_actividades ma
                             JOIN plan_actividades a ON ma.mapa_actividad_id = a.actividad_id
                             WHERE a.actividad_proceso_id = p.proceso_id
                         ) AND e.evaluacion_periodo = ?), 0) as puntaje_promedio
                 FROM proc_procesos p WHERE p.proceso_macro_id = ? AND p.proceso_activo = 1
                 ORDER BY p.proceso_nombre",
                [$periodo, $procesoId]
            );
        }

        $ranking = $im->getRankingColaboradores($periodo, null, 50);

        $indicadores = $im->getIndicadores($planId);
        $porCategoria = [];
        foreach ($indicadores as $ind) {
            $porCategoria[$ind['categoria_tipo']][] = $ind;
        }

        $pageTitle = 'Evaluación de Desempeño';
        ob_start();
        if ($nivel === 'procesos' && $procesoId) {
            require BASE_PATH . '/templates/evaluacion/procesos.php';
        } else {
            require BASE_PATH . '/templates/evaluacion/index.php';
        }
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function guardar(): void {
        header('Location: /evaluacion?ok=1');
        exit;
    }

    public function ranking(): void {
        $im = new IndicatorManager();
        $periodo = $_GET['periodo'] ?? date('Y-m');
        $ranking = $im->getRankingColaboradores($periodo, null, 50);
        $porDepto = $im->getDesempenoPorDepartamento($periodo);

        $pageTitle = 'Ranking de Desempeño';
        ob_start();
        require BASE_PATH . '/templates/evaluacion/ranking.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }
}
