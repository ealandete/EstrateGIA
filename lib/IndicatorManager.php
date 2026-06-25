<?php
/**
 * EstrateGIA - IndicatorManager
 * Gestión de Indicadores (KPIs), las 4 variantes:
 * Cumplimiento, Oportunidad, Calidad y Productividad.
 * Mediciones, Metas, Evaluaciones de Desempeño.
 */

require_once __DIR__ . '/EstrateGiaCore.php';

class IndicatorManager {

    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    // ========================================================================
    // CATEGORÍAS DE INDICADORES
    // ========================================================================

    public function getCategorias(?string $tipo = null): array {
        $sql = 'SELECT * FROM ind_categorias';
        $params = [];
        if ($tipo) { $sql .= ' WHERE categoria_tipo = :tipo'; $params['tipo'] = $tipo; }
        $sql .= ' ORDER BY categoria_tipo, categoria_nombre';
        return $this->core->fetchAll($sql, $params);
    }

    public function getCategoria(int $id): ?array {
        return $this->core->fetchOne('SELECT * FROM ind_categorias WHERE categoria_id = :id', ['id' => $id]);
    }

    // ========================================================================
    // INDICADORES
    // ========================================================================

    public function createIndicador(array $data): int {
        $required = ['indicador_categoria_id', 'indicador_nombre'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('ind_indicadores', [
            'indicador_categoria_id'    => $data['indicador_categoria_id'],
            'indicador_plan_id'         => $data['indicador_plan_id'] ?? null,
            'indicador_proceso_id'      => $data['indicador_proceso_id'] ?? null,
            'indicador_objetivo_id'     => $data['indicador_objetivo_id'] ?? null,
            'indicador_codigo'          => $data['indicador_codigo'] ?? null,
            'indicador_nombre'          => $data['indicador_nombre'],
            'indicador_descripcion'     => $data['indicador_descripcion'] ?? null,
            'indicador_formula'         => $data['indicador_formula'] ?? null,
            'indicador_unidad_medida'   => $data['indicador_unidad_medida'] ?? null,
            'indicador_frecuencia_medicion' => $data['indicador_frecuencia_medicion'] ?? 'mensual',
            'indicador_fuente_datos'    => $data['indicador_fuente_datos'] ?? 'manual',
            'indicador_responsable_id'  => $data['indicador_responsable_id'] ?? null,
            'indicador_tendencia_esperada' => $data['indicador_tendencia_esperada'] ?? 'estable',
            'indicador_rango_minimo'    => $data['indicador_rango_minimo'] ?? null,
            'indicador_rango_maximo'    => $data['indicador_rango_maximo'] ?? null,
            'indicador_semaforo_json'   => isset($data['indicador_semaforo_json']) ? json_encode($data['indicador_semaforo_json']) : null,
            'indicador_activo'          => 1,
        ]);
    }

    public function getIndicadores(?int $planId = null, ?int $procesoId = null,
                                    ?string $categoriaTipo = null, ?int $objetivoId = null): array {
        $sql = 'SELECT i.*, c.categoria_nombre, c.categoria_tipo, c.categoria_color, c.categoria_icono,
                       CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre,
                       (SELECT COUNT(*) FROM ind_metas m WHERE m.meta_indicador_id = i.indicador_id) as total_metas,
                       (SELECT COUNT(*) FROM ind_mediciones med WHERE med.medicion_indicador_id = i.indicador_id) as total_mediciones
                FROM ind_indicadores i
                JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
                LEFT JOIN sys_usuarios u ON i.indicador_responsable_id = u.usuario_id
                WHERE i.indicador_activo = 1';
        $params = [];

        if ($planId)       { $sql .= ' AND i.indicador_plan_id = :pid'; $params['pid'] = $planId; }
        if ($procesoId)    { $sql .= ' AND i.indicador_proceso_id = :prid'; $params['prid'] = $procesoId; }
        if ($categoriaTipo){ $sql .= ' AND c.categoria_tipo = :ct'; $params['ct'] = $categoriaTipo; }
        if ($objetivoId)   { $sql .= ' AND i.indicador_objetivo_id = :oid'; $params['oid'] = $objetivoId; }

        $sql .= ' ORDER BY c.categoria_tipo, i.indicador_nombre';
        return $this->core->fetchAll($sql, $params);
    }

    public function getIndicador(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT i.*, c.categoria_nombre, c.categoria_tipo, c.categoria_color,
                    CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as responsable_nombre
             FROM ind_indicadores i
             JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
             LEFT JOIN sys_usuarios u ON i.indicador_responsable_id = u.usuario_id
             WHERE i.indicador_id = :id', ['id' => $id]
        );
    }

    public function updateIndicador(int $id, array $data): bool {
        return $this->core->update('ind_indicadores', $data, 'indicador_id = :id', ['id' => $id]) > 0;
    }

    public function deleteIndicador(int $id): bool {
        return $this->core->delete('ind_indicadores', 'indicador_id = :id', ['id' => $id]) > 0;
    }

    // ========================================================================
    // METAS
    // ========================================================================

    public function createMeta(array $data): int {
        $required = ['meta_indicador_id', 'meta_periodo', 'meta_valor'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('ind_metas', [
            'meta_indicador_id' => $data['meta_indicador_id'],
            'meta_periodo'      => $data['meta_periodo'],
            'meta_valor'        => $data['meta_valor'],
            'meta_valor_minimo' => $data['meta_valor_minimo'] ?? null,
            'meta_valor_maximo' => $data['meta_valor_maximo'] ?? null,
            'meta_fecha_inicio' => $data['meta_fecha_inicio'] ?? null,
            'meta_fecha_fin'    => $data['meta_fecha_fin'] ?? null,
            'meta_peso_porcentaje' => $data['meta_peso_porcentaje'] ?? null
        ]);
    }

    public function getMetas(int $indicadorId): array {
        return $this->core->fetchAll(
            'SELECT m.*, (SELECT MAX(medicion_valor) FROM ind_mediciones WHERE medicion_meta_id = m.meta_id) as ultimo_valor,
                    (SELECT MAX(medicion_cumplimiento_porcentaje) FROM ind_mediciones WHERE medicion_meta_id = m.meta_id) as ultimo_cumplimiento
             FROM ind_metas m
             WHERE m.meta_indicador_id = :iid
             ORDER BY m.meta_fecha_inicio DESC',
            ['iid' => $indicadorId]
        );
    }

    public function getMeta(int $id): ?array {
        return $this->core->fetchOne('SELECT * FROM ind_metas WHERE meta_id = :id', ['id' => $id]);
    }

    // ========================================================================
    // MEDICIONES
    // ========================================================================

    public function registrarMedicion(array $data): int {
        $required = ['medicion_indicador_id', 'medicion_valor', 'medicion_fecha'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        // Buscar meta asociada al periodo
        $metaId = $data['medicion_meta_id'] ?? null;
        if (!$metaId) {
            $periodo = $data['medicion_periodo'] ?? date('Y-m');
            $meta = $this->core->fetchOne(
                'SELECT meta_id FROM ind_metas
                 WHERE meta_indicador_id = :iid AND meta_periodo = :per
                 ORDER BY meta_id DESC LIMIT 1',
                ['iid' => $data['medicion_indicador_id'], 'per' => $periodo]
            );
            if ($meta) $metaId = $meta['meta_id'];
        }

        $medicionId = $this->core->insert('ind_mediciones', [
            'medicion_indicador_id'     => $data['medicion_indicador_id'],
            'medicion_meta_id'          => $metaId,
            'medicion_valor'            => $data['medicion_valor'],
            'medicion_valor_numerador'  => $data['medicion_valor_numerador'] ?? null,
            'medicion_valor_denominador'=> $data['medicion_valor_denominador'] ?? null,
            'medicion_fecha'            => $data['medicion_fecha'],
            'medicion_periodo'          => $data['medicion_periodo'] ?? date('Y-m'),
            'medicion_origen'           => $data['medicion_origen'] ?? 'manual',
            'medicion_origen_detalle'   => $data['medicion_origen_detalle'] ?? null,
            'medicion_registrado_por'   => $data['medicion_registrado_por'] ?? null,
            'medicion_observaciones'    => $data['medicion_observaciones'] ?? null
        ]);

        $this->core->logAction($data['medicion_registrado_por'] ?? null, 'registrar_medicion', 'indicadores',
            'medicion', $medicionId);

        return $medicionId;
    }

    public function registrarMedicionDesdeCRM(array $data): int {
        $data['medicion_origen'] = 'crm';
        return $this->registrarMedicion($data);
    }

    public function createMedicion(array $data): int {
        $required = ['medicion_indicador_id', 'medicion_periodo'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('ind_mediciones', [
            'medicion_indicador_id' => $data['medicion_indicador_id'],
            'medicion_periodo' => $data['medicion_periodo'],
            'medicion_valor' => $data['medicion_valor'] ?? null,
            'medicion_semaforo' => $data['medicion_semaforo'] ?? 'verde',
            'medicion_cumplimiento_porcentaje' => $data['medicion_cumplimiento_porcentaje'] ?? 0,
            'medicion_fecha' => date('Y-m-d'),
            'medicion_meta_id' => $data['medicion_meta_id'] ?? null,
        ]);
    }

    public function getMediciones(int $indicadorId, ?string $fechaDesde = null,
                                   ?string $fechaHasta = null, int $limit = 100): array {
        $sql = 'SELECT m.*, i.indicador_nombre, i.indicador_unidad_medida,
                       c.categoria_nombre, c.categoria_tipo, c.categoria_color,
                       mt.meta_valor as valor_meta, mt.meta_estado
                FROM ind_mediciones m
                JOIN ind_indicadores i ON m.medicion_indicador_id = i.indicador_id
                JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
                LEFT JOIN ind_metas mt ON m.medicion_meta_id = mt.meta_id
                WHERE m.medicion_indicador_id = :iid';
        $params = ['iid' => $indicadorId];

        if ($fechaDesde) { $sql .= ' AND m.medicion_fecha >= :desde'; $params['desde'] = $fechaDesde; }
        if ($fechaHasta) { $sql .= ' AND m.medicion_fecha <= :hasta'; $params['hasta'] = $fechaHasta; }

        $sql .= ' ORDER BY m.medicion_fecha DESC LIMIT :limit';
        $params['limit'] = $limit;

        return $this->core->fetchAll($sql, $params);
    }

    /**
     * Obtiene el resumen de mediciones por las 4 variantes para un dashboard
     */
    public function getResumen4Variantes(int $planId): array {
        $result = [];

        $tipos = ['cumplimiento', 'oportunidad', 'calidad', 'productividad'];
        foreach ($tipos as $tipo) {
            $data = $this->core->fetchOne(
                'SELECT c.categoria_nombre, c.categoria_color, c.categoria_icono,
                        COUNT(DISTINCT i.indicador_id) as total_indicadores,
                        COUNT(DISTINCT m.medicion_id) as total_mediciones,
                        AVG(m.medicion_valor) as promedio_valor,
                        AVG(m.medicion_cumplimiento_porcentaje) as promedio_cumplimiento,
                        SUM(CASE WHEN m.medicion_semaforo = \'verde\' THEN 1 ELSE 0 END) as conteo_verde,
                        SUM(CASE WHEN m.medicion_semaforo = \'amarillo\' THEN 1 ELSE 0 END) as conteo_amarillo,
                        SUM(CASE WHEN m.medicion_semaforo = \'rojo\' THEN 1 ELSE 0 END) as conteo_rojo
                 FROM ind_categorias c
                 LEFT JOIN ind_indicadores i ON c.categoria_id = i.indicador_categoria_id AND i.indicador_plan_id = :pid
                 LEFT JOIN ind_mediciones m ON i.indicador_id = m.medicion_indicador_id
                 WHERE c.categoria_tipo = :tipo
                 GROUP BY c.categoria_id',
                ['pid' => $planId, 'tipo' => $tipo]
            );
            if ($data) $result[$tipo] = $data;
        }

        return $result;
    }

    /**
     * Dashboard de semáforo: verde, amarillo, rojo por tipo de indicador
     */
    public function getSemaforoDashboard(int $planId): array {
        return $this->core->fetchAll(
            'SELECT c.categoria_tipo, c.categoria_nombre, c.categoria_color,
                    COUNT(DISTINCT i.indicador_id) as total,
                    SUM(CASE WHEN m.medicion_semaforo = \'verde\' OR m.medicion_semaforo IS NULL THEN 1 ELSE 0 END) as verde,
                    SUM(CASE WHEN m.medicion_semaforo = \'amarillo\' THEN 1 ELSE 0 END) as amarillo,
                    SUM(CASE WHEN m.medicion_semaforo = \'rojo\' THEN 1 ELSE 0 END) as rojo
             FROM ind_categorias c
             JOIN ind_indicadores i ON c.categoria_id = i.indicador_categoria_id
             LEFT JOIN ind_mediciones m ON i.indicador_id = m.medicion_indicador_id
                AND m.medicion_id = (SELECT MAX(med2.medicion_id) FROM ind_mediciones med2 WHERE med2.medicion_indicador_id = i.indicador_id)
             WHERE i.indicador_plan_id = :pid AND i.indicador_activo = 1
             GROUP BY c.categoria_tipo, c.categoria_id
             ORDER BY c.categoria_tipo',
            ['pid' => $planId]
        );
    }

    // ========================================================================
    // SERIE HISTÓRICA (para gráficos de tendencia)
    // ========================================================================

    /**
     * Obtiene la serie histórica de un indicador para gráficos
     */
    public function getSerieHistorica(int $indicadorId, int $periodos = 12): array {
        return $this->core->fetchAll(
            'SELECT medicion_periodo, medicion_valor, medicion_cumplimiento_porcentaje,
                    medicion_semaforo
             FROM ind_mediciones
             WHERE medicion_indicador_id = :iid
             ORDER BY medicion_fecha DESC
             LIMIT :limit',
            ['iid' => $indicadorId, 'limit' => $periodos]
        );
    }

    /**
     * Tendencia consolidada por las 4 variantes
     */
    public function getTendencia4Variantes(int $planId, int $periodos = 6): array {
        $result = [];
        $tipos = ['cumplimiento', 'oportunidad', 'calidad', 'productividad'];

        foreach ($tipos as $tipo) {
            $result[$tipo] = $this->core->fetchAll(
                'SELECT m.medicion_periodo,
                        AVG(m.medicion_valor) as valor_promedio,
                        AVG(m.medicion_cumplimiento_porcentaje) as cumplimiento_promedio
                 FROM ind_mediciones m
                 JOIN ind_indicadores i ON m.medicion_indicador_id = i.indicador_id
                 JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
                 WHERE c.categoria_tipo = :tipo AND i.indicador_plan_id = :pid
                 GROUP BY m.medicion_periodo
                 ORDER BY m.medicion_periodo DESC
                 LIMIT :limit',
                ['tipo' => $tipo, 'pid' => $planId, 'limit' => $periodos]
            );
        }

        return $result;
    }

    // ========================================================================
    // EVALUACIÓN DE DESEMPEÑO (Nivel Individual)
    // ========================================================================

    /**
     * Calcula y guarda evaluación de desempeño del colaborador
     */
    public function calcularEvaluacionDesempeno(int $usuarioId, string $periodo): array {
        try {
            $stmt = $this->core->getPDO()->prepare('CALL sp_calcular_desempeno_usuario(:uid, :periodo)');
            $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':periodo', $periodo, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch();
            $stmt->closeCursor();
            return $result ?: [];
        } catch (\Exception $e) {
            $this->core->logError('Evaluacion', $e->getMessage());
            return [
                'puntaje_cumplimiento' => 0, 'puntaje_oportunidad' => 0,
                'puntaje_calidad' => 0, 'puntaje_productividad' => 0,
                'puntaje_total' => 0
            ];
        }
    }

    public function getEvaluaciones(int $usuarioId): array {
        return $this->core->fetchAll(
            'SELECT * FROM ind_evaluaciones_desempeno
             WHERE evaluacion_usuario_id = :uid
             ORDER BY evaluacion_periodo DESC',
            ['uid' => $usuarioId]
        );
    }

    public function getEvaluacion(int $usuarioId, string $periodo): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM ind_evaluaciones_desempeno
             WHERE evaluacion_usuario_id = :uid AND evaluacion_periodo = :per',
            ['uid' => $usuarioId, 'per' => $periodo]
        );
    }

    public function updateEvaluacion(int $evaluacionId, array $data): bool {
        return $this->core->update('ind_evaluaciones_desempeno', $data,
            'evaluacion_id = :id', ['id' => $evaluacionId]) > 0;
    }

    /**
     * Ranking de colaboradores por desempeño en las 4 variantes
     */
    public function getRankingColaboradores(string $periodo, ?string $departamento = null, int $limit = 20): array {
        $sql = 'SELECT e.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as nombre,
                       u.usuario_cargo, u.usuario_departamento, u.usuario_foto_url
                FROM ind_evaluaciones_desempeno e
                JOIN sys_usuarios u ON e.evaluacion_usuario_id = u.usuario_id
                WHERE e.evaluacion_periodo = :per';
        $params = ['per' => $periodo];

        if ($departamento) { $sql .= ' AND u.usuario_departamento = :depto'; $params['depto'] = $departamento; }

        $sql .= ' ORDER BY e.evaluacion_puntaje_total DESC LIMIT :limit';
        $params['limit'] = $limit;

        return $this->core->fetchAll($sql, $params);
    }

    /**
     * Resumen de desempeño organizacional por departamento
     */
    public function getDesempenoPorDepartamento(string $periodo): array {
        return $this->core->fetchAll(
            'SELECT u.usuario_departamento,
                    COUNT(*) as total_colaboradores,
                    AVG(e.evaluacion_puntaje_cumplimiento) as promedio_cumplimiento,
                    AVG(e.evaluacion_puntaje_oportunidad) as promedio_oportunidad,
                    AVG(e.evaluacion_puntaje_calidad) as promedio_calidad,
                    AVG(e.evaluacion_puntaje_productividad) as promedio_productividad,
                    AVG(e.evaluacion_puntaje_total) as promedio_total,
                    MAX(e.evaluacion_puntaje_total) as mejor_puntaje,
                    MIN(e.evaluacion_puntaje_total) as peor_puntaje
             FROM ind_evaluaciones_desempeno e
             JOIN sys_usuarios u ON e.evaluacion_usuario_id = u.usuario_id
             WHERE e.evaluacion_periodo = :per
             GROUP BY u.usuario_departamento
             ORDER BY promedio_total DESC',
            ['per' => $periodo]
        );
    }

    // ========================================================================
    // INDICADORES AUTOMÁTICOS DESDE CRM/MINERÍA
    // ========================================================================

    public function syncMedicionesFromCRM(int $crmMapeoId): array {
        $mapeo = $this->core->fetchOne(
            'SELECT * FROM crm_mapeos_datos WHERE mapeo_id = :id AND mapeo_activo = 1',
            ['id' => $crmMapeoId]
        );
        if (!$mapeo) return ['success' => false, 'message' => 'Mapeo no encontrado o inactivo'];

        $conexion = $this->core->fetchOne(
            'SELECT * FROM crm_conexiones WHERE conexion_id = :id AND conexion_activo = 1',
            ['id' => $mapeo['mapeo_conexion_id']]
        );
        if (!$conexion) return ['success' => false, 'message' => 'Conexión no encontrada'];

        // Aquí se conectaría al CRM/Web Service para obtener los datos
        // El resultado se almacena como medición automática
        $resultados = [
            'mapeo_id' => $crmMapeoId,
            'conexion' => $conexion['conexion_nombre'],
            'tipo'     => $mapeo['mapeo_tipo_indicador'],
            'estado'   => 'pendiente',
            'registros'=> 0
        ];

        // Actualizar última sincronización
        $this->core->update('crm_mapeos_datos',
            ['mapeo_ultima_ejecucion' => date('Y-m-d H:i:s')],
            'mapeo_id = :id', ['id' => $crmMapeoId]
        );

        $this->core->update('crm_conexiones',
            ['conexion_ultima_sincronizacion' => date('Y-m-d H:i:s')],
            'conexion_id = :id', ['id' => $conexion['conexion_id']]
        );

        return $resultados;
    }
}
