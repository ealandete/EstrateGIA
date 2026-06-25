<?php
/**
 * EstrateGIA - PlanManager
 * Gestión completa de Planeación Estratégica:
 * Metodologías, Planes, Fases, Análisis (FODA, PESTEL, etc.),
 * Objetivos, Estrategias, Actividades, Mapa de Actividades.
 */

require_once __DIR__ . '/EstrateGiaCore.php';

class PlanManager {

    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    // ========================================================================
    // METODOLOGÍAS
    // ========================================================================

    public function getMetodologias(bool $activas = true): array {
        $sql = 'SELECT * FROM plan_metodologias';
        if ($activas) $sql .= ' WHERE metodologia_activo = 1';
        $sql .= ' ORDER BY metodologia_nombre';
        return $this->core->fetchAll($sql);
    }

    public function getMetodologia(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM plan_metodologias WHERE metodologia_id = :id', ['id' => $id]
        );
    }

    // ========================================================================
    // EMPRESAS
    // ========================================================================

    public function createEmpresa(array $data): int {
        $required = ['empresa_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        $id = $this->core->insert('plan_empresas', [
            'empresa_nombre'      => $data['empresa_nombre'],
            'empresa_razon_social'=> $data['empresa_razon_social'] ?? null,
            'empresa_nit'         => $data['empresa_nit'] ?? null,
            'empresa_sector_id'   => $data['empresa_sector_id'] ?? null,
            'empresa_direccion'   => $data['empresa_direccion'] ?? null,
            'empresa_telefono'    => $data['empresa_telefono'] ?? null,
            'empresa_email'       => $data['empresa_email'] ?? null,
            'empresa_logo_url'    => $data['empresa_logo_url'] ?? null,
            'empresa_mision'      => $data['empresa_mision'] ?? null,
            'empresa_vision'      => $data['empresa_vision'] ?? null,
            'empresa_valores'     => isset($data['empresa_valores']) ? json_encode($data['empresa_valores']) : null
        ]);

        $this->core->logAction($data['usuario_id'] ?? null, 'crear', 'planeacion', 'empresa', $id);
        return $id;
    }

    public function getEmpresas(): array {
        return $this->core->fetchAll(
            'SELECT e.*, s.sector_nombre FROM plan_empresas e
             LEFT JOIN doc_sectores s ON e.empresa_sector_id = s.sector_id
             WHERE e.empresa_activo = 1 ORDER BY e.empresa_nombre'
        );
    }

    public function getEmpresa(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT e.*, s.sector_nombre FROM plan_empresas e
             LEFT JOIN doc_sectores s ON e.empresa_sector_id = s.sector_id
             WHERE e.empresa_id = :id', ['id' => $id]
        );
    }

    public function updateEmpresa(int $id, array $data): bool {
        if (isset($data['empresa_valores']) && is_array($data['empresa_valores'])) {
            $data['empresa_valores'] = json_encode($data['empresa_valores']);
        }
        $affected = $this->core->update('plan_empresas', $data, 'empresa_id = :id', ['id' => $id]);
        $this->core->logAction($data['usuario_id'] ?? null, 'actualizar', 'planeacion', 'empresa', $id);
        return $affected > 0;
    }

    // ========================================================================
    // PLANES ESTRATÉGICOS
    // ========================================================================

    public function createPlan(array $data): int {
        $required = ['plan_empresa_id', 'plan_metodologia_id', 'plan_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        $id = $this->core->insert('plan_planes_estrategicos', [
            'plan_empresa_id'       => $data['plan_empresa_id'],
            'plan_metodologia_id'   => $data['plan_metodologia_id'],
            'plan_nombre'           => $data['plan_nombre'],
            'plan_descripcion'      => $data['plan_descripcion'] ?? null,
            'plan_fecha_inicio'     => $data['plan_fecha_inicio'] ?? null,
            'plan_fecha_fin'        => $data['plan_fecha_fin'] ?? null,
            'plan_periodo'          => $data['plan_periodo'] ?? null,
            'plan_estado'           => $data['plan_estado'] ?? 'borrador',
            'plan_presupuesto_total'=> $data['plan_presupuesto_total'] ?? null,
            'plan_responsable_id'   => $data['plan_responsable_id'] ?? null
        ]);

        // Crear fases automáticamente desde la metodología
        $this->createFasesFromMetodologia($id, $data['plan_metodologia_id']);

        $this->core->logAction($data['usuario_id'] ?? null, 'crear', 'planeacion', 'plan', $id);
        $this->core->sendNotification(
            $data['plan_responsable_id'] ?? 0,
            'Nuevo Plan Estratégico',
            "Se ha creado el plan '{$data['plan_nombre']}'",
            'exito', '/planeacion/planes/' . $id, 'plan', $id
        );

        return $id;
    }

    private function createFasesFromMetodologia(int $planId, int $metodologiaId): void {
        $metodologia = $this->getMetodologia($metodologiaId);
        if (!$metodologia || !$metodologia['metodologia_fases_json']) return;

        $fases = json_decode($metodologia['metodologia_fases_json'], true);
        if (!isset($fases['fases'])) return;

        $plan = $this->getPlan($planId);
        $fechaInicio = $plan['plan_fecha_inicio'] ?? date('Y-m-d');
        $esPrimera = true;

        foreach ($fases['fases'] as $index => $fase) {
            $fechaFin = null;
            $duracion = $fase['duracion_dias'] ?? 0;
            if ($duracion > 0 && $fechaInicio) {
                $fechaFin = date('Y-m-d', strtotime($fechaInicio . " + {$duracion} days"));
            }

            $this->core->insert('plan_fases', [
                'fase_plan_id'      => $planId,
                'fase_nombre'       => $fase['nombre'],
                'fase_descripcion'  => $fase['descripcion'] ?? null,
                'fase_orden'        => $fase['orden'],
                'fase_duracion_dias'=> $duracion > 0 ? $duracion : null,
                'fase_fecha_inicio' => $fechaInicio,
                'fase_fecha_fin'    => $fechaFin,
                'fase_guia_paso_a_paso' => isset($fase['pasos']) ? json_encode(['pasos' => $fase['pasos']]) : null,
                'fase_estado'       => $esPrimera ? 'en_proceso' : 'pendiente'
            ]);

            $esPrimera = false;

            if ($duracion > 0 && $fechaFin) {
                $fechaInicio = date('Y-m-d', strtotime($fechaFin . ' + 1 day'));
            }
        }
    }

    public function getPlanes(?int $empresaId = null, ?string $estado = null): array {
        $sql = 'SELECT p.*, e.empresa_nombre, m.metodologia_nombre, m.metodologia_icono,
                       CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
                FROM plan_planes_estrategicos p
                JOIN plan_empresas e ON p.plan_empresa_id = e.empresa_id
                JOIN plan_metodologias m ON p.plan_metodologia_id = m.metodologia_id
                LEFT JOIN sys_usuarios u ON p.plan_responsable_id = u.usuario_id
                WHERE p.plan_activo = 1';
        $params = [];

        if ($empresaId) {
            $sql .= ' AND p.plan_empresa_id = :empresa';
            $params['empresa'] = $empresaId;
        }
        if ($estado) {
            $sql .= ' AND p.plan_estado = :estado';
            $params['estado'] = $estado;
        }
        $sql .= ' ORDER BY p.created_at DESC';

        return $this->core->fetchAll($sql, $params);
    }

    public function getPlan(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT p.*, e.empresa_nombre, m.metodologia_nombre, m.metodologia_icono,
                    m.metodologia_fases_json,
                    CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
             FROM plan_planes_estrategicos p
             JOIN plan_empresas e ON p.plan_empresa_id = e.empresa_id
             JOIN plan_metodologias m ON p.plan_metodologia_id = m.metodologia_id
             LEFT JOIN sys_usuarios u ON p.plan_responsable_id = u.usuario_id
             WHERE p.plan_id = :id', ['id' => $id]
        );
    }

    public function updatePlan(int $id, array $data): bool {
        $anterior = $this->getPlan($id);
        $affected = $this->core->update('plan_planes_estrategicos', $data, 'plan_id = :id', ['id' => $id]);

        if (isset($data['plan_estado']) && $data['plan_estado'] !== $anterior['plan_estado']) {
            $this->core->logAction($data['usuario_id'] ?? null, 'cambio_estado', 'planeacion', 'plan', $id);
        }

        return $affected > 0;
    }

    public function getPlanProgress(int $planId): array {
        return $this->core->fetchOne('CALL sp_resumen_ejecutivo_plan(:pid)', ['pid' => $planId]) ?? [];
    }

    // ========================================================================
    // FASES
    // ========================================================================

    public function getFases(int $planId): array {
        return $this->core->fetchAll(
            'SELECT f.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
             FROM plan_fases f
             LEFT JOIN sys_usuarios u ON f.fase_responsable_id = u.usuario_id
             WHERE f.fase_plan_id = :plan_id
             ORDER BY f.fase_orden',
            ['plan_id' => $planId]
        );
    }

    public function getFase(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM plan_fases WHERE fase_id = :id', ['id' => $id]
        );
    }

    public function updateFase(int $id, array $data): bool {
        return $this->core->update('plan_fases', $data, 'fase_id = :id', ['id' => $id]) > 0;
    }

    public function getFasePasoAPaso(int $faseId): array {
        $fase = $this->getFase($faseId);
        if ($fase && $fase['fase_guia_paso_a_paso']) {
            return json_decode($fase['fase_guia_paso_a_paso'], true) ?? [];
        }
        return [];
    }

    // ========================================================================
    // ANÁLISIS DE CONTEXTO
    // ========================================================================

    public function createAnalisis(array $data): int {
        $required = ['analisis_plan_id', 'analisis_tipo'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('plan_analisis_contexto', [
            'analisis_plan_id'      => $data['analisis_plan_id'],
            'analisis_tipo'         => $data['analisis_tipo'],
            'analisis_titulo'       => $data['analisis_titulo'] ?? null,
            'analisis_contenido'    => isset($data['analisis_contenido']) ? json_encode($data['analisis_contenido']) : null,
            'analisis_conclusiones' => $data['analisis_conclusiones'] ?? null,
            'analisis_fecha'        => $data['analisis_fecha'] ?? date('Y-m-d'),
            'analisis_responsable_id' => $data['analisis_responsable_id'] ?? null
        ]);
    }

    public function getAnalisisByPlan(int $planId, ?string $tipo = null): array {
        $sql = 'SELECT a.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
                FROM plan_analisis_contexto a
                LEFT JOIN sys_usuarios u ON a.analisis_responsable_id = u.usuario_id
                WHERE a.analisis_plan_id = :pid';
        $params = ['pid' => $planId];
        if ($tipo) {
            $sql .= ' AND a.analisis_tipo = :tipo';
            $params['tipo'] = $tipo;
        }
        return $this->core->fetchAll($sql, $params);
    }

    public function getFODA(int $planId): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM plan_analisis_contexto WHERE analisis_plan_id = :pid AND analisis_tipo = \'FODA\' ORDER BY created_at DESC LIMIT 1',
            ['pid' => $planId]
        );
    }

    public function getPESTEL(int $planId): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM plan_analisis_contexto WHERE analisis_plan_id = :pid AND analisis_tipo = \'PESTEL\' ORDER BY created_at DESC LIMIT 1',
            ['pid' => $planId]
        );
    }

    // ========================================================================
    // OBJETIVOS ESTRATÉGICOS
    // ========================================================================

    public function createObjetivo(array $data): int {
        $required = ['objetivo_plan_id', 'objetivo_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        $codigo = $data['objetivo_codigo'] ?? $this->generateCodigo($data['objetivo_plan_id'], 'plan_objetivos', 'objetivo_codigo', 'OBJ');

        return $this->core->insert('plan_objetivos', [
            'objetivo_plan_id'      => $data['objetivo_plan_id'],
            'objetivo_fase_id'      => $data['objetivo_fase_id'] ?? null,
            'objetivo_codigo'       => $codigo,
            'objetivo_nombre'       => $data['objetivo_nombre'],
            'objetivo_descripcion'  => $data['objetivo_descripcion'] ?? null,
            'objetivo_tipo'         => $data['objetivo_tipo'] ?? 'estrategico',
            'objetivo_perspectiva'  => $data['objetivo_perspectiva'] ?? 'financiera',
            'objetivo_peso_relativo'=> $data['objetivo_peso_relativo'] ?? null,
            'objetivo_prioridad'    => $data['objetivo_prioridad'] ?? 'medio',
            'objetivo_responsable_id'=> $data['objetivo_responsable_id'] ?? null
        ]);
    }

    public function getObjetivos(int $planId, ?string $perspectiva = null): array {
        $sql = 'SELECT o.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre,
                       (SELECT COUNT(*) FROM plan_estrategias e WHERE e.estrategia_objetivo_id = o.objetivo_id) as total_estrategias
                FROM plan_objetivos o
                LEFT JOIN sys_usuarios u ON o.objetivo_responsable_id = u.usuario_id
                WHERE o.objetivo_plan_id = :pid';
        $params = ['pid' => $planId];
        if ($perspectiva) {
            $sql .= ' AND o.objetivo_perspectiva = :persp';
            $params['persp'] = $perspectiva;
        }
        $sql .= ' ORDER BY o.objetivo_prioridad DESC, o.objetivo_peso_relativo DESC';
        return $this->core->fetchAll($sql, $params);
    }

    public function getObjetivo(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT o.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
             FROM plan_objetivos o
             LEFT JOIN sys_usuarios u ON o.objetivo_responsable_id = u.usuario_id
             WHERE o.objetivo_id = :id', ['id' => $id]
        );
    }

    public function updateObjetivo(int $id, array $data): bool {
        return $this->core->update('plan_objetivos', $data, 'objetivo_id = :id', ['id' => $id]) > 0;
    }

    public function deleteObjetivo(int $id): bool {
        return $this->core->delete('plan_objetivos', 'objetivo_id = :id', ['id' => $id]) > 0;
    }

    // ========================================================================
    // ESTRATEGIAS
    // ========================================================================

    public function createEstrategia(array $data): int {
        $required = ['estrategia_objetivo_id', 'estrategia_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('plan_estrategias', [
            'estrategia_objetivo_id'    => $data['estrategia_objetivo_id'],
            'estrategia_codigo'         => $data['estrategia_codigo'] ?? null,
            'estrategia_nombre'         => $data['estrategia_nombre'],
            'estrategia_descripcion'    => $data['estrategia_descripcion'] ?? null,
            'estrategia_tipo'           => $data['estrategia_tipo'] ?? 'crecimiento',
            'estrategia_prioridad'      => $data['estrategia_prioridad'] ?? 'medio',
            'estrategia_presupuesto'    => $data['estrategia_presupuesto'] ?? null,
            'estrategia_responsable_id' => $data['estrategia_responsable_id'] ?? null
        ]);
    }

    public function getEstrategias(int $objetivoId): array {
        return $this->core->fetchAll(
            'SELECT e.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre,
                    (SELECT COUNT(*) FROM plan_actividades WHERE actividad_estrategia_id = e.estrategia_id) as total_actividades
             FROM plan_estrategias e
             LEFT JOIN sys_usuarios u ON e.estrategia_responsable_id = u.usuario_id
             WHERE e.estrategia_objetivo_id = :oid
             ORDER BY e.estrategia_prioridad DESC',
            ['oid' => $objetivoId]
        );
    }

    public function updateEstrategia(int $id, array $data): bool {
        $result = $this->core->update('plan_estrategias', [
            'estrategia_nombre' => $data['estrategia_nombre'] ?? null,
            'estrategia_descripcion' => $data['estrategia_descripcion'] ?? null,
            'estrategia_tipo' => $data['estrategia_tipo'] ?? null,
            'estrategia_prioridad' => $data['estrategia_prioridad'] ?? null,
            'estrategia_estado' => $data['estrategia_estado'] ?? null,
            'estrategia_avance_porcentaje' => $data['estrategia_avance_porcentaje'] ?? null,
            'estrategia_responsable_id' => $data['estrategia_responsable_id'] ?? null,
            'estrategia_presupuesto' => $data['estrategia_presupuesto'] ?? null,
        ], 'estrategia_id = :id', ['id' => $id]);
        return $result > 0;
    }

    public function deleteEstrategia(int $id): bool {
        $this->core->delete('plan_actividades', 'actividad_estrategia_id = :id', ['id' => $id]);
        return $this->core->delete('plan_estrategias', 'estrategia_id = :id', ['id' => $id]) > 0;
    }

    // ========================================================================
    // ACTIVIDADES
    // ========================================================================

    public function createActividad(array $data): int {
        $required = ['actividad_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        $actividad = [
            'actividad_estrategia_id'       => $data['actividad_estrategia_id'] ?? null,
            'actividad_objetivo_id'         => $data['actividad_objetivo_id'] ?? null,
            'actividad_proceso_id'          => $data['actividad_proceso_id'] ?? null,
            'actividad_nombre'              => $data['actividad_nombre'],
            'actividad_descripcion'         => $data['actividad_descripcion'] ?? null,
            'actividad_tipo'                => $data['actividad_tipo'] ?? 'tarea',
            'actividad_fecha_inicio'        => $data['actividad_fecha_inicio'] ?? null,
            'actividad_fecha_fin_planeada'  => $data['actividad_fecha_fin_planeada'] ?? null,
            'actividad_duracion_estimada_horas' => $data['actividad_duracion_estimada_horas'] ?? null,
            'actividad_prioridad'           => $data['actividad_prioridad'] ?? 'medio',
            'actividad_dependencia_id'      => $data['actividad_dependencia_id'] ?? null,
            'actividad_responsable_id'      => $data['actividad_responsable_id'] ?? null,
            'actividad_recursos'            => isset($data['actividad_recursos']) ? json_encode($data['actividad_recursos']) : null,
            'actividad_entregables'         => isset($data['actividad_entregables']) ? json_encode($data['actividad_entregables']) : null
        ];

        // Generar código automático
        $actividad['actividad_codigo'] = $data['actividad_codigo'] ?? $this->generateActividadCodigo($actividad);

        $id = $this->core->insert('plan_actividades', $actividad);

        // Notificar al responsable
        if ($actividad['actividad_responsable_id']) {
            $this->core->sendNotification(
                $actividad['actividad_responsable_id'],
                'Nueva Actividad Asignada',
                "Se te ha asignado la actividad '{$actividad['actividad_nombre']}'",
                'info', '/planeacion/actividades/' . $id, 'actividad', $id
            );
        }

        return $id;
    }

    public function getActividades(?int $estrategiaId = null, ?int $objetivoId = null,
                                    ?int $responsableId = null, ?string $estado = null): array {
        $sql = 'SELECT a.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre,
                       a2.actividad_nombre as dependencia_nombre
                FROM plan_actividades a
                LEFT JOIN sys_usuarios u ON a.actividad_responsable_id = u.usuario_id
                LEFT JOIN plan_actividades a2 ON a.actividad_dependencia_id = a2.actividad_id
                WHERE 1=1';
        $params = [];

        if ($estrategiaId) { $sql .= ' AND a.actividad_estrategia_id = :eid'; $params['eid'] = $estrategiaId; }
        if ($objetivoId)   { $sql .= ' AND a.actividad_objetivo_id = :oid'; $params['oid'] = $objetivoId; }
        if ($responsableId){ $sql .= ' AND a.actividad_responsable_id = :rid'; $params['rid'] = $responsableId; }
        if ($estado)       { $sql .= ' AND a.actividad_estado = :est'; $params['est'] = $estado; }

        $sql .= ' ORDER BY a.actividad_prioridad DESC, a.actividad_fecha_fin_planeada ASC';
        return $this->core->fetchAll($sql, $params);
    }

    public function getActividad(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT a.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre,
                    a2.actividad_nombre as dependencia_nombre
             FROM plan_actividades a
             LEFT JOIN sys_usuarios u ON a.actividad_responsable_id = u.usuario_id
             LEFT JOIN plan_actividades a2 ON a.actividad_dependencia_id = a2.actividad_id
             WHERE a.actividad_id = :id', ['id' => $id]
        );
    }

    public function updateActividad(int $id, array $data): bool {
        $result = $this->core->update('plan_actividades', [
            'actividad_nombre' => $data['actividad_nombre'] ?? null,
            'actividad_descripcion' => $data['actividad_descripcion'] ?? null,
            'actividad_estado' => $data['actividad_estado'] ?? null,
            'actividad_avance_porcentaje' => $data['actividad_avance_porcentaje'] ?? null,
            'actividad_fecha_fin_real' => $data['actividad_fecha_fin_real'] ?? null,
        ], 'actividad_id = :id', ['id' => $id]);
        return $result > 0;
    }

    public function deleteActividad(int $id): bool {
        return $this->core->delete('plan_actividades', 'actividad_id = :id', ['id' => $id]) > 0;
    }

    public function deletePlan(int $id): bool {
        $plan = $this->getPlan($id);
        if (!$plan) return false;
        $objetivos = $this->getObjetivos($id);
        foreach ($objetivos as $obj) $this->deleteObjetivo($obj['objetivo_id']);
        $this->core->delete('plan_fases', 'fase_plan_id = :pid', ['pid' => $id]);
        $this->core->delete('plan_mapa_actividades', 'mapa_plan_id = :pid', ['pid' => $id]);
        $this->core->delete('plan_presupuestos', 'presupuesto_plan_id = :pid', ['pid' => $id]);
        return $this->core->delete('plan_planes_estrategicos', 'plan_id = :pid', ['pid' => $id]) > 0;
    }

    public function getActividadesRetrasadas(): array {
        return $this->core->fetchAll(
            'SELECT a.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
             FROM plan_actividades a
             LEFT JOIN sys_usuarios u ON a.actividad_responsable_id = u.usuario_id
             WHERE a.actividad_estado NOT IN (\'completada\', \'cancelada\')
               AND a.actividad_fecha_fin_planeada < CURDATE()
             ORDER BY a.actividad_fecha_fin_planeada ASC'
        );
    }

    // ========================================================================
    // MAPA DE ACTIVIDADES (Usuario - Actividad - Tiempo)
    // ========================================================================

    public function assignUserToActivity(int $actividadId, int $usuarioId, string $rol = 'ejecutor',
                                          ?int $tiempoEstimado = null): int {
        return $this->core->insert('plan_mapa_actividades', [
            'mapa_actividad_id'             => $actividadId,
            'mapa_usuario_id'               => $usuarioId,
            'mapa_rol'                      => $rol,
            'mapa_tiempo_estimado_minutos'  => $tiempoEstimado,
            'mapa_fecha_asignacion'         => date('Y-m-d'),
            'mapa_estado'                   => 'asignado'
        ]);
    }

    public function getMapaActividadesByUser(int $usuarioId, ?string $estado = null): array {
        $sql = 'SELECT ma.*, a.actividad_nombre, a.actividad_descripcion, a.actividad_tipo,
                       a.actividad_fecha_fin_planeada, a.actividad_prioridad,
                       CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
                FROM plan_mapa_actividades ma
                JOIN plan_actividades a ON ma.mapa_actividad_id = a.actividad_id
                LEFT JOIN sys_usuarios u ON a.actividad_responsable_id = u.usuario_id
                WHERE ma.mapa_usuario_id = :uid';
        $params = ['uid' => $usuarioId];
        if ($estado) { $sql .= ' AND ma.mapa_estado = :est'; $params['est'] = $estado; }
        $sql .= ' ORDER BY a.actividad_prioridad DESC, a.actividad_fecha_fin_planeada ASC';
        return $this->core->fetchAll($sql, $params);
    }

    public function getMapaActividadesByActivity(int $actividadId): array {
        return $this->core->fetchAll(
            'SELECT ma.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as usuario_nombre,
                    u.usuario_departamento, u.usuario_cargo
             FROM plan_mapa_actividades ma
             JOIN sys_usuarios u ON ma.mapa_usuario_id = u.usuario_id
             WHERE ma.mapa_actividad_id = :aid',
            ['aid' => $actividadId]
        );
    }

    public function updateMapaEstado(int $mapaId, string $estado, ?int $tiempoReal = null): bool {
        $data = ['mapa_estado' => $estado];
        if ($estado === 'completado') {
            $data['mapa_fecha_completado'] = date('Y-m-d');
            if ($tiempoReal) $data['mapa_tiempo_real_minutos'] = $tiempoReal;
        }
        if ($estado === 'en_progreso' && !$this->core->fetchOne(
            'SELECT mapa_id FROM plan_mapa_actividades WHERE mapa_id = :id AND mapa_estado = \'asignado\'',
            ['id' => $mapaId]
        )) {
            $data['mapa_fecha_asignacion'] = date('Y-m-d');
        }
        return $this->core->update('plan_mapa_actividades', $data, 'mapa_id = :id', ['id' => $mapaId]) > 0;
    }

    // ========================================================================
    // PRESUPUESTOS
    // ========================================================================

    public function createPresupuesto(array $data): int {
        return $this->core->insert('plan_presupuestos', [
            'presupuesto_plan_id'       => $data['presupuesto_plan_id'],
            'presupuesto_categoria'     => $data['presupuesto_categoria'] ?? null,
            'presupuesto_monto_planeado'=> $data['presupuesto_monto_planeado'],
            'presupuesto_periodo'       => $data['presupuesto_periodo'] ?? null,
            'presupuesto_responsable_id'=> $data['presupuesto_responsable_id'] ?? null
        ]);
    }

    public function getPresupuestosByPlan(int $planId): array {
        return $this->core->fetchAll(
            'SELECT p.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
             FROM plan_presupuestos p
             LEFT JOIN sys_usuarios u ON p.presupuesto_responsable_id = u.usuario_id
             WHERE p.presupuesto_plan_id = :pid
             ORDER BY p.presupuesto_categoria',
            ['pid' => $planId]
        );
    }

    public function updatePresupuestoEjecucion(int $presupuestoId, float $montoEjecutado): bool {
        $presupuesto = $this->core->fetchOne(
            'SELECT * FROM plan_presupuestos WHERE presupuesto_id = :id', ['id' => $presupuestoId]
        );
        if (!$presupuesto) return false;

        $porcentaje = $presupuesto['presupuesto_monto_planeado'] > 0
            ? ($montoEjecutado / $presupuesto['presupuesto_monto_planeado']) * 100
            : 0;

        return $this->core->update('plan_presupuestos', [
            'presupuesto_monto_ejecutado'      => $montoEjecutado,
            'presupuesto_porcentaje_ejecucion' => round($porcentaje, 2)
        ], 'presupuesto_id = :id', ['id' => $presupuestoId]) > 0;
    }

    // ========================================================================
    // UTILIDADES
    // ========================================================================

    private function generateCodigo(int $planId, string $table, string $col, string $prefix): string {
        $count = $this->core->fetchColumn(
            "SELECT COUNT(*) FROM {$table} WHERE {$col} LIKE :like",
            ['like' => "{$prefix}-%"]
        );
        return sprintf('%s-%03d', $prefix, $count + 1);
    }

    private function generateActividadCodigo(array $data): string {
        if ($data['actividad_estrategia_id']) {
            $context = 'EST-' . $data['actividad_estrategia_id'];
        } elseif ($data['actividad_objetivo_id']) {
            $context = 'OBJ-' . $data['actividad_objetivo_id'];
        } else {
            $context = 'GEN';
        }
        $count = $this->core->fetchColumn(
            "SELECT COUNT(*) FROM plan_actividades WHERE actividad_codigo LIKE :like",
            ['like' => "ACT-{$context}-%"]
        );
        return sprintf('ACT-%s-%03d', $context, $count + 1);
    }

    /**
     * Obtiene el árbol completo de un plan: Fases -> Objetivos -> Estrategias -> Actividades
     */
    public function getPlanTree(int $planId): array {
        $fases = $this->getFases($planId);
        foreach ($fases as &$fase) {
            $fase['objetivos'] = $this->core->fetchAll(
                'SELECT o.* FROM plan_objetivos o WHERE o.objetivo_fase_id = :fid AND o.objetivo_plan_id = :pid',
                ['fid' => $fase['fase_id'], 'pid' => $planId]
            );
            foreach ($fase['objetivos'] as &$objetivo) {
                $objetivo['estrategias'] = $this->getEstrategias($objetivo['objetivo_id']);
                foreach ($objetivo['estrategias'] as &$estrategia) {
                    $estrategia['actividades'] = $this->getActividades($estrategia['estrategia_id']);
                }
            }
        }
        return $fases;
    }

    /**
     * Obtiene la carga de trabajo por colaborador
     */
    public function getCargaTrabajoColaboradores(int $planId): array {
        return $this->core->fetchAll(
            'SELECT u.usuario_id, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as nombre,
                    u.usuario_departamento, u.usuario_cargo,
                    COUNT(DISTINCT ma.mapa_actividad_id) as total_actividades,
                    SUM(CASE WHEN ma.mapa_estado = \'completado\' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN ma.mapa_estado IN (\'asignado\',\'aceptado\',\'en_progreso\') THEN 1 ELSE 0 END) as en_progreso,
                    SUM(COALESCE(ma.mapa_tiempo_estimado_minutos, 0)) as tiempo_estimado_total_min,
                    SUM(COALESCE(ma.mapa_tiempo_real_minutos, 0)) as tiempo_real_total_min
             FROM plan_mapa_actividades ma
             JOIN sys_usuarios u ON ma.mapa_usuario_id = u.usuario_id
             JOIN plan_actividades a ON ma.mapa_actividad_id = a.actividad_id
             LEFT JOIN plan_estrategias e ON a.actividad_estrategia_id = e.estrategia_id
             LEFT JOIN plan_objetivos o ON (e.estrategia_objetivo_id = o.objetivo_id OR a.actividad_objetivo_id = o.objetivo_id)
             WHERE o.objetivo_plan_id = :pid OR a.actividad_objetivo_id IN
                   (SELECT objetivo_id FROM plan_objetivos WHERE objetivo_plan_id = :pid2)
             GROUP BY u.usuario_id
             ORDER BY total_actividades DESC',
            ['pid' => $planId, 'pid2' => $planId]
        );
    }
}
