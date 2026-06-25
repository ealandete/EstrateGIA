<?php
/**
 * EstrateGIA - ProcessManager
 * Gestión de Procesos, Procedimientos, Tareas, Workflows y Mapeo de Tiempos.
 */

require_once __DIR__ . '/EstrateGiaCore.php';

class ProcessManager {

    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    // ========================================================================
    // MACROPROCESOS
    // ========================================================================

    public function createMacroproceso(array $data): int {
        $required = ['macro_empresa_id', 'macro_nombre', 'macro_tipo'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        $id = $this->core->insert('proc_macroprocesos', [
            'macro_empresa_id'  => $data['macro_empresa_id'],
            'macro_codigo'      => $data['macro_codigo'] ?? 'MP-' . str_pad($data['macro_orden'] ?? 1, 2, '0', STR_PAD_LEFT),
            'macro_nombre'      => $data['macro_nombre'],
            'macro_descripcion' => $data['macro_descripcion'] ?? null,
            'macro_tipo'        => $data['macro_tipo'],
            'macro_orden'       => $data['macro_orden'] ?? 0
        ]);

        $this->core->logAction($data['usuario_id'] ?? null, 'crear', 'procesos', 'macroproceso', $id);
        return $id;
    }

    public function getMacroprocesos(int $empresaId): array {
        return $this->core->fetchAll(
            'SELECT m.*, (SELECT COUNT(*) FROM proc_procesos p WHERE p.proceso_macro_id = m.macro_id) as total_procesos
             FROM proc_macroprocesos m
             WHERE m.macro_empresa_id = :eid AND m.macro_activo = 1
             ORDER BY m.macro_tipo, m.macro_orden',
            ['eid' => $empresaId]
        );
    }

    public function getMacroproceso(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM proc_macroprocesos WHERE macro_id = :id', ['id' => $id]
        );
    }

    // ========================================================================
    // PROCESOS
    // ========================================================================

    public function createProceso(array $data): int {
        $required = ['proceso_macro_id', 'proceso_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        $id = $this->core->insert('proc_procesos', [
            'proceso_macro_id'      => $data['proceso_macro_id'],
            'proceso_plan_id'       => $data['proceso_plan_id'] ?? null,
            'proceso_codigo'        => $data['proceso_codigo'] ?? null,
            'proceso_nombre'        => $data['proceso_nombre'],
            'proceso_descripcion'   => $data['proceso_descripcion'] ?? null,
            'proceso_objetivo'      => $data['proceso_objetivo'] ?? null,
            'proceso_alcance'       => $data['proceso_alcance'] ?? null,
            'proceso_tipo'          => $data['proceso_tipo'] ?? 'misional',
            'proceso_responsable_id'=> $data['proceso_responsable_id'] ?? null
        ]);

        $this->core->logAction($data['usuario_id'] ?? null, 'crear', 'procesos', 'proceso', $id);
        return $id;
    }

    public function getProcesos(?int $macroId = null, ?string $tipo = null): array {
        $sql = 'SELECT p.*, m.macro_nombre, m.macro_tipo as macro_tipo_padre,
                       CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre,
                       (SELECT COUNT(*) FROM proc_procedimientos pr WHERE pr.procedimiento_proceso_id = p.proceso_id) as total_procedimientos
                FROM proc_procesos p
                JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
                LEFT JOIN sys_usuarios u ON p.proceso_responsable_id = u.usuario_id
                WHERE p.proceso_activo = 1';
        $params = [];

        if ($macroId) { $sql .= ' AND p.proceso_macro_id = :mid'; $params['mid'] = $macroId; }
        if ($tipo)    { $sql .= ' AND p.proceso_tipo = :tipo'; $params['tipo'] = $tipo; }

        $sql .= ' ORDER BY m.macro_tipo, p.proceso_nombre';
        return $this->core->fetchAll($sql, $params);
    }

    public function getProceso(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT p.*, m.macro_nombre,
                    CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
             FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             LEFT JOIN sys_usuarios u ON p.proceso_responsable_id = u.usuario_id
             WHERE p.proceso_id = :id', ['id' => $id]
        );
    }

    public function updateProceso(int $id, array $data): bool {
        return $this->core->update('proc_procesos', $data, 'proceso_id = :id', ['id' => $id]) > 0;
    }

    public function getProcesoEstado(int $id): array {
        return [
            'proceso' => $this->getProceso($id),
            'procedimientos' => $this->getProcedimientos($id),
            'documentos' => $this->getDocumentosByProceso($id),
            'indicadores' => $this->getIndicadoresByProceso($id)
        ];
    }

    private function getDocumentosByProceso(int $procesoId): array {
        return $this->core->fetchAll(
            'SELECT * FROM doc_documentos WHERE documento_proceso_id = :pid AND documento_activo = 1',
            ['pid' => $procesoId]
        );
    }

    private function getIndicadoresByProceso(int $procesoId): array {
        return $this->core->fetchAll(
            'SELECT i.*, c.categoria_nombre, c.categoria_tipo
             FROM ind_indicadores i
             JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
             WHERE i.indicador_proceso_id = :pid AND i.indicador_activo = 1',
            ['pid' => $procesoId]
        );
    }

    // ========================================================================
    // PROCEDIMIENTOS
    // ========================================================================

    public function createProcedimiento(array $data): int {
        $required = ['procedimiento_proceso_id', 'procedimiento_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('proc_procedimientos', [
            'procedimiento_proceso_id'  => $data['procedimiento_proceso_id'],
            'procedimiento_codigo'      => $data['procedimiento_codigo'] ?? null,
            'procedimiento_nombre'      => $data['procedimiento_nombre'],
            'procedimiento_descripcion' => $data['procedimiento_descripcion'] ?? null,
            'procedimiento_objetivo'    => $data['procedimiento_objetivo'] ?? null,
            'procedimiento_orden'       => $data['procedimiento_orden'] ?? 0,
            'procedimiento_documento_id'=> $data['procedimiento_documento_id'] ?? null
        ]);
    }

    public function getProcedimientos(int $procesoId): array {
        return $this->core->fetchAll(
            'SELECT pr.*, d.documento_titulo,
                    (SELECT COUNT(*) FROM proc_tareas t WHERE t.tarea_procedimiento_id = pr.procedimiento_id) as total_tareas
             FROM proc_procedimientos pr
             LEFT JOIN doc_documentos d ON pr.procedimiento_documento_id = d.documento_id
             WHERE pr.procedimiento_proceso_id = :pid AND pr.procedimiento_activo = 1
             ORDER BY pr.procedimiento_orden',
            ['pid' => $procesoId]
        );
    }

    // ========================================================================
    // TAREAS
    // ========================================================================

    public function createTarea(array $data): int {
        $required = ['tarea_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('proc_tareas', [
            'tarea_procedimiento_id'            => $data['tarea_procedimiento_id'] ?? null,
            'tarea_proceso_id'                   => $data['tarea_proceso_id'] ?? null,
            'tarea_nombre'                       => $data['tarea_nombre'],
            'tarea_descripcion'                  => $data['tarea_descripcion'] ?? null,
            'tarea_orden'                        => $data['tarea_orden'] ?? 0,
            'tarea_tipo'                         => $data['tarea_tipo'] ?? 'manual',
            'tarea_tiempo_estimado_minutos'      => $data['tarea_tiempo_estimado_minutos'] ?? null,
            'tarea_tiempo_maximo_permitido_minutos' => $data['tarea_tiempo_maximo_permitido_minutos'] ?? null,
            'tarea_frecuencia'                   => $data['tarea_frecuencia'] ?? 'unica',
            'tarea_responsable_id'               => $data['tarea_responsable_id'] ?? null,
            'tarea_requiere_evidencia'           => $data['tarea_requiere_evidencia'] ?? 0,
            'tarea_critica'                      => $data['tarea_critica'] ?? 0
        ]);
    }

    public function getTareas(?int $procedimientoId = null, ?int $procesoId = null, ?int $responsableId = null): array {
        $sql = 'SELECT t.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre,
                       pr.procedimiento_nombre, p.proceso_nombre
                FROM proc_tareas t
                LEFT JOIN proc_procedimientos pr ON t.tarea_procedimiento_id = pr.procedimiento_id
                LEFT JOIN proc_procesos p ON (t.tarea_proceso_id = p.proceso_id OR pr.procedimiento_proceso_id = p.proceso_id)
                LEFT JOIN sys_usuarios u ON t.tarea_responsable_id = u.usuario_id
                WHERE t.tarea_activo = 1';
        $params = [];

        if ($procedimientoId) { $sql .= ' AND t.tarea_procedimiento_id = :prid'; $params['prid'] = $procedimientoId; }
        if ($procesoId)       { $sql .= ' AND (t.tarea_proceso_id = :pid OR pr.procedimiento_proceso_id = :pid2)';
                                 $params['pid'] = $procesoId; $params['pid2'] = $procesoId; }
        if ($responsableId)   { $sql .= ' AND t.tarea_responsable_id = :rid'; $params['rid'] = $responsableId; }

        $sql .= ' ORDER BY COALESCE(pr.procedimiento_orden, 0), t.tarea_orden';
        return $this->core->fetchAll($sql, $params);
    }

    public function getTareasCriticas(?int $procesoId = null): array {
        $sql = 'SELECT * FROM proc_tareas WHERE tarea_critica = 1 AND tarea_activo = 1';
        $params = [];
        if ($procesoId) { $sql .= ' AND tarea_proceso_id = :pid'; $params['pid'] = $procesoId; }
        return $this->core->fetchAll($sql, $params);
    }

    // ========================================================================
    // MAPEO DE TIEMPOS (Registro atómico de tiempos por usuario)
    // ========================================================================

    public function iniciarRegistroTiempo(int $tareaId, int $usuarioId, string $origen = 'manual', ?string $origenDato = null): int {
        return $this->core->insert('proc_mapeo_tiempos', [
            'mapeo_tarea_id'    => $tareaId,
            'mapeo_usuario_id'  => $usuarioId,
            'mapeo_fecha_inicio'=> date('Y-m-d H:i:s'),
            'mapeo_estado'      => 'iniciado',
            'mapeo_tipo_registro'=> $origen,
            'mapeo_origen_dato' => $origenDato
        ]);
    }

    public function finalizarRegistroTiempo(int $mapeoId, ?string $observaciones = null): bool {
        $mapeo = $this->core->fetchOne(
            'SELECT * FROM proc_mapeo_tiempos WHERE mapeo_id = :id', ['id' => $mapeoId]
        );
        if (!$mapeo) return false;

        $fechaFin = date('Y-m-d H:i:s');
        $inicio = new DateTime($mapeo['mapeo_fecha_inicio']);
        $fin = new DateTime($fechaFin);
        $minutos = $inicio->diff($fin)->i + ($inicio->diff($fin)->h * 60) + ($inicio->diff($fin)->days * 1440);

        return $this->core->update('proc_mapeo_tiempos', [
            'mapeo_fecha_fin'           => $fechaFin,
            'mapeo_tiempo_total_minutos'=> $minutos,
            'mapeo_estado'              => 'completado',
            'mapeo_observaciones'       => $observaciones
        ], 'mapeo_id = :id', ['id' => $mapeoId]) > 0;
    }

    public function getMapeoTiempos(int $tareaId): array {
        return $this->core->fetchAll(
            'SELECT mt.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as usuario_nombre
             FROM proc_mapeo_tiempos mt
             JOIN sys_usuarios u ON mt.mapeo_usuario_id = u.usuario_id
             WHERE mt.mapeo_tarea_id = :tid
             ORDER BY mt.mapeo_fecha_inicio DESC',
            ['tid' => $tareaId]
        );
    }

    public function getEstadisticasTiempoUsuario(int $usuarioId, ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $sql = 'SELECT
                    COUNT(*) as total_registros,
                    SUM(mapeo_tiempo_total_minutos) as tiempo_total_minutos,
                    AVG(mapeo_tiempo_total_minutos) as tiempo_promedio_minutos,
                    MIN(mapeo_tiempo_total_minutos) as tiempo_minimo,
                    MAX(mapeo_tiempo_total_minutos) as tiempo_maximo,
                    SUM(CASE WHEN mapeo_tipo_registro = \'manual\' THEN 1 ELSE 0 END) as conteo_manual,
                    SUM(CASE WHEN mapeo_tipo_registro = \'crm\' THEN 1 ELSE 0 END) as conteo_crm,
                    SUM(CASE WHEN mapeo_tipo_registro = \'web_service\' THEN 1 ELSE 0 END) as conteo_ws,
                    SUM(CASE WHEN mapeo_tipo_registro = \'mineria_datos\' THEN 1 ELSE 0 END) as conteo_mineria
                FROM proc_mapeo_tiempos
                WHERE mapeo_usuario_id = :uid AND mapeo_estado = \'completado\'
                AND mapeo_tiempo_total_minutos IS NOT NULL';
        $params = ['uid' => $usuarioId];

        if ($fechaDesde) { $sql .= ' AND mapeo_fecha_inicio >= :desde'; $params['desde'] = $fechaDesde . ' 00:00:00'; }
        if ($fechaHasta) { $sql .= ' AND mapeo_fecha_inicio <= :hasta'; $params['hasta'] = $fechaHasta . ' 23:59:59'; }

        return $this->core->fetchOne($sql, $params) ?? [];
    }

    /**
     * Dashboard: Obtiene el tiempo promedio por tipo de tarea a nivel organizacional
     */
    public function getBenchmarkTiemposPorTarea(): array {
        return $this->core->fetchAll(
            'SELECT t.tarea_tipo, t.tarea_nombre,
                    COUNT(mt.mapeo_id) as cantidad_registros,
                    AVG(mt.mapeo_tiempo_total_minutos) as tiempo_promedio_minutos,
                    t.tarea_tiempo_estimado_minutos as tiempo_estimado,
                    t.tarea_tiempo_real_promedio_minutos as tiempo_real_historico
             FROM proc_tareas t
             LEFT JOIN proc_mapeo_tiempos mt ON t.tarea_id = mt.mapeo_tarea_id AND mt.mapeo_estado = \'completado\'
             WHERE t.tarea_activo = 1
             GROUP BY t.tarea_id
             ORDER BY cantidad_registros DESC'
        );
    }

    // ========================================================================
    // WORKFLOWS
    // ========================================================================

    public function createWorkflow(array $data): int {
        $required = ['workflow_proceso_id', 'workflow_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('proc_workflows', [
            'workflow_proceso_id'   => $data['workflow_proceso_id'],
            'workflow_nombre'       => $data['workflow_nombre'],
            'workflow_descripcion'  => $data['workflow_descripcion'] ?? null,
            'workflow_diagrama_json'=> isset($data['workflow_diagrama_json']) ? json_encode($data['workflow_diagrama_json']) : null,
            'workflow_estado'       => $data['workflow_estado'] ?? 'diseno'
        ]);
    }

    public function getWorkflows(int $procesoId): array {
        return $this->core->fetchAll(
            'SELECT * FROM proc_workflows WHERE workflow_proceso_id = :pid AND workflow_activo = 1',
            ['pid' => $procesoId]
        );
    }

    public function updateWorkflowDiagrama(int $workflowId, array $diagrama): bool {
        return $this->core->update('proc_workflows', [
            'workflow_diagrama_json' => json_encode($diagrama)
        ], 'workflow_id = :id', ['id' => $workflowId]) > 0;
    }

    // ========================================================================
    // DASHBOARD DE PROCESOS
    // ========================================================================

    /**
     * Obtiene el estado general de todos los procesos de una empresa
     */
    public function getDashboardProcesos(int $empresaId): array {
        $macroprocesos = $this->getMacroprocesos($empresaId);
        $procesos = $this->core->fetchAll(
            'SELECT p.*, m.macro_nombre, m.macro_tipo,
                    COUNT(DISTINCT pr.procedimiento_id) as total_procedimientos,
                    COUNT(DISTINCT t.tarea_id) as total_tareas,
                    COUNT(DISTINCT d.documento_id) as total_documentos
             FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             LEFT JOIN proc_procedimientos pr ON p.proceso_id = pr.procedimiento_proceso_id
             LEFT JOIN proc_tareas t ON pr.procedimiento_id = t.tarea_procedimiento_id OR t.tarea_proceso_id = p.proceso_id
             LEFT JOIN doc_documentos d ON p.proceso_id = d.documento_proceso_id
             WHERE m.macro_empresa_id = :eid AND p.proceso_activo = 1
             GROUP BY p.proceso_id',
            ['eid' => $empresaId]
        );

        $estadisticas = [
            'total_macroprocesos' => count($macroprocesos),
            'total_procesos'      => count($procesos),
            'total_procedimientos'=> array_sum(array_column($procesos, 'total_procedimientos')),
            'total_tareas'        => array_sum(array_column($procesos, 'total_tareas')),
            'total_documentos'    => array_sum(array_column($procesos, 'total_documentos')),
            'procesos_por_tipo'   => [
                'estrategico' => count(array_filter($procesos, fn($p) => $p['proceso_tipo'] === 'estrategico')),
                'misional'    => count(array_filter($procesos, fn($p) => $p['proceso_tipo'] === 'misional')),
                'apoyo'       => count(array_filter($procesos, fn($p) => $p['proceso_tipo'] === 'apoyo')),
                'evaluacion'  => count(array_filter($procesos, fn($p) => $p['proceso_tipo'] === 'evaluacion'))
            ],
            'procesos' => $procesos,
            'macroprocesos' => $macroprocesos
        ];

        return $estadisticas;
    }
}
