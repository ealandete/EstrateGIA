<?php
/**
 * EstrateGIA - SystemIntegrator
 * Orquestador central que integra todos los módulos.
 * Proporciona endpoints unificados para dashboards y vistas consolidadas.
 * Sigue el patrón de SystemIntegratorV4.php del sistema agropecuario.
 */

require_once __DIR__ . '/EstrateGiaCore.php';
require_once __DIR__ . '/PlanManager.php';
require_once __DIR__ . '/ProcessManager.php';
require_once __DIR__ . '/IndicatorManager.php';
require_once __DIR__ . '/DocManager.php';
require_once __DIR__ . '/CRMManager.php';
require_once __DIR__ . '/AIManager.php';

class SystemIntegrator {

    private $core;
    private $planManager;
    private $processManager;
    private $indicatorManager;
    private $docManager;
    private $crmManager;
    private $aiManager;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
        $this->planManager = new PlanManager();
        $this->processManager = new ProcessManager();
        $this->indicatorManager = new IndicatorManager();
        $this->docManager = new DocManager();
        $this->crmManager = new CRMManager();
        $this->aiManager = new AIManager();
    }

    // ========================================================================
    // DASHBOARD PRINCIPAL - Vista Ejecutiva
    // ========================================================================

    public function getDashboardEjecutivo(int $empresaId): array {
        $planes = $this->planManager->getPlanes($empresaId);
        $planActivo = !empty($planes) ? $planes[0] : null;

        return [
            'empresa' => $this->planManager->getEmpresa($empresaId),
            'resumen_planeacion' => $this->getResumenPlaneacion($empresaId),
            'resumen_procesos' => $this->getResumenProcesos($empresaId),
            'semaforo_kpis' => $planActivo
                ? $this->indicatorManager->getSemaforoDashboard($planActivo['plan_id'])
                : [],
            'alertas' => $this->getAlertasConsolidadas($empresaId),
            'proximos_hitos' => $this->getProximosHitos($empresaId),
            'ranking_colaboradores' => $this->indicatorManager->getRankingColaboradores(
                date('Y-m'), null, 5
            ),
            'estado_integraciones' => $this->crmManager->getDashboardIntegraciones($empresaId),
            'ia_stats' => $this->aiManager->getUsageStats(),
            'timestamp' => date('c')
        ];
    }

    private function getResumenPlaneacion(int $empresaId): array {
        $planes = $this->planManager->getPlanes($empresaId);
        $planActivo = null;
        // Priorizar: completado > ejecucion > aprobado > en_proceso
        foreach (['completado', 'ejecucion', 'aprobado', 'en_proceso'] as $est) {
            foreach ($planes as $p) {
                if ($p['plan_estado'] === $est) {
                    $planActivo = $p;
                    break 2;
                }
            }
        }
        if (!$planActivo) $planActivo = $planes[0] ?? null;

        if (!$planActivo) return ['estado' => 'Sin plan activo'];

        $progreso = $this->planManager->getPlanProgress($planActivo['plan_id']);
        $variantes = $this->indicatorManager->getResumen4Variantes($planActivo['plan_id']);

        return [
            'plan_activo' => [
                'id' => $planActivo['plan_id'],
                'nombre' => $planActivo['plan_nombre'],
                'metodologia' => $planActivo['metodologia_nombre'],
                'estado' => $planActivo['plan_estado'],
                'avance' => $planActivo['plan_avance_porcentaje'],
                'periodo' => $planActivo['plan_periodo'],
                'dias_restantes' => $planActivo['plan_fecha_fin']
                    ? max(0, (new DateTime($planActivo['plan_fecha_fin']))->diff(new DateTime())->days)
                    : null
            ],
            'progreso' => $progreso,
            'variantes_kpi' => $variantes,
            'total_planes' => count($planes)
        ];
    }

    private function getResumenProcesos(int $empresaId): array {
        $dashboard = $this->processManager->getDashboardProcesos($empresaId);
        return [
            'total_macroprocesos' => $dashboard['total_macroprocesos'],
            'total_procesos' => $dashboard['total_procesos'],
            'total_procedimientos' => $dashboard['total_procedimientos'],
            'total_tareas' => $dashboard['total_tareas'],
            'total_documentos' => $dashboard['total_documentos'],
            'distribucion_tipos' => $dashboard['procesos_por_tipo']
        ];
    }

    private function getAlertasConsolidadas(int $empresaId): array {
        $alertas = [];

        // Actividades retrasadas
        $retrasadas = $this->planManager->getActividadesRetrasadas();
        foreach ($retrasadas as $act) {
            $alertas[] = [
                'tipo' => 'retraso',
                'prioridad' => 'alta',
                'mensaje' => "Actividad retrasada: {$act['actividad_nombre']}",
                'responsable' => $act['responsable_nombre'],
                'fecha_limite' => $act['actividad_fecha_fin_planeada'],
                'url' => "/actividades/{$act['actividad_id']}"
            ];
        }

        // Indicadores en rojo
        $planes = $this->planManager->getPlanes($empresaId);
        if (!empty($planes)) {
            $semaforo = $this->indicatorManager->getSemaforoDashboard($planes[0]['plan_id']);
            foreach ($semaforo as $sem) {
                if ($sem['rojo'] > 0) {
                    $alertas[] = [
                        'tipo' => 'indicador_rojo',
                        'prioridad' => 'alta',
                        'mensaje' => "{$sem['rojo']} indicadores en rojo en {$sem['categoria_nombre']}",
                        'url' => "/indicadores"
                    ];
                }
            }
        }

        // Documentos por vencer
        $docsPorRevisar = $this->docManager->getDashboardDocumental($empresaId)['documentos_por_revisar'] ?? [];
        foreach ($docsPorRevisar as $doc) {
            $alertas[] = [
                'tipo' => 'documento_por_revisar',
                'prioridad' => 'media',
                'mensaje' => "Documento por revisar: {$doc['documento_titulo']}",
                'fecha_revision' => $doc['documento_fecha_proxima_revision'],
                'url' => "/docs"
            ];
        }

        return $alertas;
    }

    private function getProximosHitos(int $empresaId): array {
        return $this->core->fetchAll(
            'SELECT a.actividad_nombre, a.actividad_fecha_fin_planeada, a.actividad_tipo,
                    CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable
             FROM plan_actividades a
             LEFT JOIN sys_usuarios u ON a.actividad_responsable_id = u.usuario_id
             WHERE a.actividad_tipo = \'hito\'
               AND a.actividad_estado NOT IN (\'completada\', \'cancelada\')
               AND a.actividad_fecha_fin_planeada >= CURDATE()
             ORDER BY a.actividad_fecha_fin_planeada ASC
             LIMIT 10'
        );
    }

    // ========================================================================
    // DASHBOARD DE PROCESOS - Vista Operativa
    // ========================================================================

    public function getDashboardProcesos(int $empresaId): array {
        return $this->processManager->getDashboardProcesos($empresaId);
    }

    // ========================================================================
    // DASHBOARD INDIVIDUAL - Vista del Colaborador
    // ========================================================================

    public function getDashboardColaborador(int $usuarioId): array {
        $usuario = $this->core->fetchOne(
            'SELECT * FROM sys_usuarios WHERE usuario_id = :id', ['id' => $usuarioId]
        );

        $periodo = date('Y-m');

        return [
            'usuario' => [
                'id' => $usuarioId,
                'nombre' => $usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido'],
                'cargo' => $usuario['usuario_cargo'],
                'departamento' => $usuario['usuario_departamento'],
                'foto' => $usuario['usuario_foto_url']
            ],
            'mis_actividades' => [
                'pendientes' => count($this->planManager->getMapaActividadesByUser($usuarioId, 'asignado'))
                              + count($this->planManager->getMapaActividadesByUser($usuarioId, 'en_progreso')),
                'completadas' => count($this->planManager->getMapaActividadesByUser($usuarioId, 'completado')),
                'proximas' => $this->planManager->getMapaActividadesByUser($usuarioId)
            ],
            'mi_evaluacion' => $this->indicatorManager->getEvaluacion($usuarioId, $periodo)
                ?? $this->indicatorManager->calcularEvaluacionDesempeno($usuarioId, $periodo),
            'mis_tiempos' => $this->processManager->getEstadisticasTiempoUsuario($usuarioId),
            'mis_notificaciones' => $this->core->getUnreadNotifications($usuarioId, 10),
            'mi_ranking' => $this->indicatorManager->getRankingColaboradores($periodo, $usuario['usuario_departamento'], 100)
        ];
    }

    // ========================================================================
    // VISTA DE PLANEACIÓN - Árbol Completo
    // ========================================================================

    public function getVistaPlaneacion(int $planId): array {
        return [
            'plan' => $this->planManager->getPlan($planId),
            'arbol' => $this->planManager->getPlanTree($planId),
            'analisis' => [
                'foda' => $this->planManager->getFODA($planId),
                'pestel' => $this->planManager->getPESTEL($planId)
            ],
            'indicadores' => $this->indicatorManager->getIndicadores($planId),
            'variantes' => $this->indicatorManager->getResumen4Variantes($planId),
            'presupuesto' => $this->planManager->getPresupuestosByPlan($planId),
            'carga_trabajo' => $this->planManager->getCargaTrabajoColaboradores($planId)
        ];
    }

    // ========================================================================
    // VISTA DE PROCESOS - Mapa de Procesos
    // ========================================================================

    public function getVistaProcesos(int $empresaId): array {
        return $this->processManager->getDashboardProcesos($empresaId);
    }

    public function getVistaProceso(int $procesoId): array {
        return $this->processManager->getProcesoEstado($procesoId);
    }

    // ========================================================================
    // REPORTE CONSOLIDADO DE GESTIÓN
    // ========================================================================

    public function getReporteGestion(int $empresaId, string $periodo): array {
        $planes = $this->planManager->getPlanes($empresaId);
        $planActivo = null;
        foreach ($planes as $p) {
            if (in_array($p['plan_estado'], ['ejecucion', 'aprobado', 'en_proceso', 'completado'])) {
                $planActivo = $p;
                break;
            }
        }

        return [
            'periodo' => $periodo,
            'empresa' => $this->planManager->getEmpresa($empresaId),
            'plan_estrategico' => $planActivo ? $this->planManager->getPlanProgress($planActivo['plan_id']) : null,
            'cumplimiento' => $this->indicatorManager->getResumen4Variantes($planActivo['plan_id'] ?? 0),
            'desempeno_por_departamento' => $this->indicatorManager->getDesempenoPorDepartamento($periodo),
            'ranking_individual' => $this->indicatorManager->getRankingColaboradores($periodo),
            'estado_documental' => $this->docManager->getDashboardDocumental($empresaId),
            'integraciones' => $this->crmManager->getDashboardIntegraciones($empresaId),
            'recomendaciones_ia' => $planActivo
                ? $this->aiManager->getRecomendaciones('plan', $planActivo['plan_id'])
                : []
        ];
    }

    /**
     * Ejecuta el ciclo completo de actualización automática:
     * Sincroniza CRM, mina datos, actualiza KPIs y evaluaciones
     */
    public function ejecutarCicloActualizacion(int $empresaId): array {
        $resultados = [
            'empresa_id' => $empresaId,
            'fecha' => date('Y-m-d H:i:s'),
            'sincronizaciones' => null,
            'evaluaciones' => null,
            'recomendaciones' => null
        ];

        // 1. Sincronizar datos desde CRMs y ejecutar minerías
        $resultados['sincronizaciones'] = $this->crmManager->ejecutarTodasSincronizaciones($empresaId);

        // 2. Calcular evaluaciones de desempeño del período actual
        $usuarios = $this->core->fetchAll(
            'SELECT usuario_id FROM sys_usuarios WHERE usuario_activo = 1'
        );
        $periodo = date('Y-m');
        $evalsProcesadas = 0;
        foreach ($usuarios as $u) {
            $this->indicatorManager->calcularEvaluacionDesempeno($u['usuario_id'], $periodo);
            $evalsProcesadas++;
        }
        $resultados['evaluaciones'] = [
            'procesadas' => $evalsProcesadas,
            'periodo' => $periodo
        ];

        // 3. Generar recomendaciones IA para elementos críticos
        $planes = $this->planManager->getPlanes($empresaId);
        $recsGeneradas = 0;
        foreach ($planes as $plan) {
            if (in_array($plan['plan_estado'], ['ejecucion', 'en_proceso', 'aprobado', 'completado'])) {
                $this->aiManager->generarRecomendacion('plan', $plan['plan_id'], 'mejora');
                $recsGeneradas++;
            }
        }
        $resultados['recomendaciones'] = ['generadas' => $recsGeneradas];

        return $resultados;
    }
}
