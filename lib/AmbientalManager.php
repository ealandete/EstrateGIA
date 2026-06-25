<?php
class AmbientalManager extends BaseHSEManager {

    public function __construct() {
        parent::__construct();
        $this->prefijoInd = 'aind_';
        $this->prefijoReq = 'amb_';
        $this->prefijoRep = 'amb_';
        $this->tablaIndicadores = 'amb_indicadores';
        $this->tablaReqLegales = 'amb_requisitos_legales';
        $this->tablaReportes = 'amb_reportes_ley';
        $this->colEmpresaInd = 'aind_empresa_id';
        $this->colEmpresaReq = 'empresa_id';
        $this->colEmpresaRep = 'empresa_id';
    }

    // ========================================================================
    // ASPECTOS E IMPACTOS AMBIENTALES (AIA)
    // ========================================================================

    public function getAspectos(int $empresaId, ?string $recurso = null, ?int $areaId = null): array {
        $sql = "SELECT a.*, pr.proceso_nombre, pl.plan_nombre FROM amb_aspectos a 
                LEFT JOIN proc_procesos pr ON a.asp_proceso_id=pr.proceso_id 
                LEFT JOIN plan_planes_estrategicos pl ON a.asp_plan_id=pl.plan_id 
                WHERE a.asp_empresa_id=:eid";
        $params = ['eid' => $empresaId];
        if ($recurso) { $sql .= " AND a.asp_recurso=:recurso"; $params['recurso'] = $recurso; }
        if ($areaId) { $sql .= " AND a.asp_area_id=:areaId"; $params['areaId'] = $areaId; }
        $sql .= " ORDER BY a.asp_significancia DESC, a.asp_recurso ASC";
        return $this->core->fetchAll($sql, $params);
    }

    public function crearAspecto(array $data): int {
        $id = $this->core->insert('amb_aspectos', [
            'asp_empresa_id'        => $data['empresa_id'],
            'asp_proceso_id'        => $data['proceso_id'] ?? null,
            'asp_plan_id'           => $data['plan_id'] ?? null,
            'asp_area_id'           => $data['area_id'] ?? null,
            'asp_recurso'           => $data['recurso'] ?? 'aire',
            'asp_codigo'            => $data['codigo'] ?? ('ASP-' . date('Y') . '-' . rand(100, 999)),
            'asp_descripcion'       => $data['descripcion'] ?? '',
            'asp_tipo'              => $data['tipo'] ?? 'generacion_residuos',
            'asp_impacto'           => $data['impacto'] ?? '',
            'asp_significancia'     => $data['significancia'] ?? 'medio',
            'asp_controles'         => $data['controles'] ?? '',
            'asp_estado'            => $data['estado'] ?? 'identificado',
            'asp_operacion_descripcion' => $data['operacion_descripcion'] ?? '',
            'asp_calculo_posible'   => $data['calculo_posible'] ?? '',
            'asp_proporcion_cientificamente_estimada' => $data['proporcion_cientificamente_estimada'] ?? '',
            'asp_plan_accion_actual' => $data['plan_accion_actual'] ?? '',
        ]);
        $this->core->logAction(Auth::userId(), 'crear', 'ambiental', 'aspecto', $id);
        $this->calcularImpactoResidual($id);
        return $id;
    }

    public function editarAspecto(int $id, array $data): void {
        $this->core->update('amb_aspectos', [
            'asp_descripcion'       => $data['descripcion'] ?? '',
            'asp_tipo'              => $data['tipo'] ?? 'generacion_residuos',
            'asp_impacto'           => $data['impacto'] ?? '',
            'asp_significancia'     => $data['significancia'] ?? 'medio',
            'asp_controles'         => $data['controles'] ?? '',
            'asp_estado'            => $data['estado'] ?? 'identificado',
            'asp_proceso_id'        => $data['proceso_id'] ?? null,
            'asp_plan_id'           => $data['plan_id'] ?? null,
            'asp_recurso'           => $data['recurso'] ?? 'aire',
            'asp_area_id'           => $data['area_id'] ?? null,
            'asp_operacion_descripcion' => $data['operacion_descripcion'] ?? '',
            'asp_calculo_posible'   => $data['calculo_posible'] ?? '',
            'asp_proporcion_cientificamente_estimada' => $data['proporcion_cientificamente_estimada'] ?? '',
            'asp_plan_accion_actual' => $data['plan_accion_actual'] ?? '',
        ], 'asp_id=:id', ['id' => $id]);
        $this->calcularImpactoResidual($id);
    }

    public function eliminarAspecto(int $id): void {
        $this->core->delete('amb_aspectos', 'asp_id=:id', ['id' => $id]);
    }

    private function calcularImpactoResidual(int $aspId): void {
        $resultado = $this->core->fetchOne(
            "SELECT a.asp_significancia,
                    (CASE WHEN a.asp_significancia='critico' THEN 10
                          WHEN a.asp_significancia='alto' THEN 7
                          WHEN a.asp_significancia='medio' THEN 5
                          WHEN a.asp_significancia='bajo' THEN 2 ELSE 5 END) as nivel_impacto,
                    (SELECT COUNT(*) FROM amb_controles c WHERE c.asp_id=a.asp_id AND c.control_efectivo=1) as controles_efectivos
             FROM amb_aspectos a WHERE a.asp_id=:id",
            ['id' => $aspId]
        );
        if (!$resultado) return;
        $impactoOriginal = (int)$resultado['nivel_impacto'];
        $controlesEfectivos = (int)$resultado['controles_efectivos'];
        $reduccionPorcentaje = $controlesEfectivos > 0 ? min(100, $controlesEfectivos * 20) : 0;
        $impactoResidual = max(0, $impactoOriginal - round($impactoOriginal * $reduccionPorcentaje / 100, 1));
        $this->core->update('amb_aspectos', [
            'asp_impacto_residual' => $impactoResidual,
            'asp_controles_efectivos' => $controlesEfectivos,
            'asp_reduccion_por_controles' => $reduccionPorcentaje,
        ], 'asp_id=:id', ['id' => $aspId]);
    }

    // ========================================================================
    // EMISIONES GEI - HUELLA DE CARBONO ISO 14064
    // ========================================================================

    public function getEmisionesGEI(int $empresaId, ?int $anio = null): array {
        $sql = "SELECT * FROM amb_emisiones_gei WHERE empresa_id=:eid";
        $params = ['eid' => $empresaId];
        if ($anio) { $sql .= " AND gei_periodo=:anio"; $params['anio'] = $anio; }
        $sql .= " ORDER BY fecha_registro DESC";
        return $this->core->fetchAll($sql, $params);
    }

    public function crearEmisionGEI(array $data): int {
        $id = $this->core->insert('amb_emisiones_gei', [
            'empresa_id'           => $data['empresa_id'],
            'gei_alcance'          => $data['alcance'] ?? 'alcance_1',
            'gei_tipo_fuente'      => $data['tipo_fuente'] ?? 'combustible',
            'gei_fuente'           => $data['fuente'] ?? '',
            'gei_descripcion'      => $data['descripcion'] ?? '',
            'gei_cantidad'         => $data['cantidad'] ?? 0,
            'gei_unidad'           => $data['unidad'] ?? 'tCO2e',
            'gei_factor_emision'   => $data['factor_emision'] ?? 1.0,
            'gei_periodo'          => $data['periodo'] ?? date('Y'),
            'gei_coordenadas_generacion' => $data['coordenadas'] ?? '',
            'fecha_registro'       => date('Y-m-d H:i:s'),
        ]);
        return $id;
    }

    public function editarEmisionGEI(int $id, array $data): void {
        $this->core->update('amb_emisiones_gei', [
            'gei_alcance'          => $data['alcance'] ?? 'alcance_1',
            'gei_tipo_fuente'      => $data['tipo_fuente'] ?? 'combustible',
            'gei_fuente'           => $data['fuente'] ?? '',
            'gei_descripcion'      => $data['descripcion'] ?? '',
            'gei_cantidad'         => $data['cantidad'] ?? 0,
            'gei_unidad'           => $data['unidad'] ?? 'tCO2e',
            'gei_factor_emision'   => $data['factor_emision'] ?? 1.0,
            'gei_periodo'          => $data['periodo'] ?? date('Y'),
        ], 'gei_id=:id', ['id' => $id]);
    }

    public function eliminarEmisionGEI(int $id): void {
        $this->core->delete('amb_emisiones_gei', 'gei_id=:id', ['id' => $id]);
    }

    public function getHuellaCarbono(int $empresaId, int $anio): array {
        $cache = CacheService::getInstance(1800);
        return $cache->remember("huella:$empresaId:$anio", function() use ($empresaId, $anio) {
        $alcance1 = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(gei_cantidad * gei_factor_emision), 0) FROM amb_emisiones_gei WHERE empresa_id=:eid AND gei_alcance='alcance_1' AND gei_periodo=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $alcance2 = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(gei_cantidad * gei_factor_emision), 0) FROM amb_emisiones_gei WHERE empresa_id=:eid AND gei_alcance='alcance_2' AND gei_periodo=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $alcance3 = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(gei_cantidad * gei_factor_emision), 0) FROM amb_emisiones_gei WHERE empresa_id=:eid AND gei_alcance='alcance_3' AND gei_periodo=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $total = $alcance1 + $alcance2 + $alcance3;

        $meta = $this->core->fetchColumn(
            "SELECT amb_meta_huella FROM amb_metas_ambientales WHERE empresa_id=:eid AND meta_anio=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0;

        $totalAnterior = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(gei_cantidad * gei_factor_emision), 0) FROM amb_emisiones_gei WHERE empresa_id=:eid AND gei_periodo=:anio_ant",
            ['eid' => $empresaId, 'anio_ant' => $anio - 1]
        ) ?? 0);

        $variacion = $totalAnterior > 0 ? round((($total - $totalAnterior) / $totalAnterior) * 100, 1) : 0;
        $cumplimientoMeta = $meta > 0 ? round(min(100, ($total / $meta) * 100), 1) : 0;

        return compact('alcance1', 'alcance2', 'alcance3', 'total', 'meta', 'variacion', 'cumplimientoMeta', 'anio');
        });
    }

    public function getFactorEmision(string $fuente, string $version = 'IPCC-2021'): ?array {
        return $this->core->fetchOne(
            "SELECT * FROM amb_factores_emision WHERE factor_fuente=:fuente AND factor_version=:version AND factor_activo=1 LIMIT 1",
            ['fuente' => $fuente, 'version' => $version]
        );
    }

    public function getFactoresEmisionPorAlcance(string $alcance, string $version = 'IPCC-2021'): array {
        return $this->core->fetchAll(
            "SELECT * FROM amb_factores_emision WHERE factor_alcance=:alcance AND factor_version=:version AND factor_activo=1 ORDER BY factor_tipo_fuente, factor_fuente",
            ['alcance' => $alcance, 'version' => $version]
        );
    }

    public function getVersionesFactores(): array {
        return $this->core->fetchAll(
            "SELECT factor_version, COUNT(*) as total, MIN(factor_vigencia_inicio) as desde FROM amb_factores_emision GROUP BY factor_version ORDER BY desde DESC"
        );
    }

    public function getIndicadoresCarbono(int $empresaId, int $anio): array {
        $carbonoEvitado = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(ce.cantidad * ce.factor), 0) FROM amb_carbono_evitado ce WHERE ce.empresa_id=:eid AND ce.periodo=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $energiaRenovable = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(energia.generacion_mwh), 0) FROM amb_energia_renovable energia WHERE energia.empresa_id=:eid AND energia.periodo=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $energiaTotal = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(reg_valor), 0) FROM amb_registros WHERE reg_empresa_id=:eid AND reg_tipo='consumo_energia' AND YEAR(reg_fecha)=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $eficiencia = $energiaTotal > 0 ? round(($energiaRenovable / $energiaTotal) * 100, 1) : 0;
        return compact('carbonoEvitado', 'energiaRenovable', 'eficiencia', 'anio');
    }

    public function getReporteHuellaCarbon(int $empresaId, int $anio): array {
        $emisiones = $this->core->fetchAll(
            "SELECT gei_alcance as alcance, gei_tipo_fuente as tipo, gei_fuente as fuente,
                    gei_cantidad as cantidad, gei_unidad as unidad, gei_factor_emision as factor,
                    (gei_cantidad * gei_factor_emision) as tco2e
             FROM amb_emisiones_gei WHERE empresa_id=:eid AND gei_periodo=:anio
             ORDER BY gei_alcance, gei_tipo_fuente",
            ['eid' => $empresaId, 'anio' => $anio]
        );
        $resumen = $this->getHuellaCarbono($empresaId, $anio);
        return compact('emisiones', 'resumen');
    }

    // ========================================================================
    // CONTROLES AMBIENTALES
    // ========================================================================

    public function getControles(int $empresaId): array {
        return $this->core->fetchAll(
            "SELECT c.*, a.asp_descripcion, a.asp_recurso, a.asp_significancia
             FROM amb_controles c LEFT JOIN amb_aspectos a ON c.asp_id=a.asp_id
             WHERE c.empresa_id=:eid ORDER BY c.control_criticidad DESC",
            ['eid' => $empresaId]
        );
    }

    public function crearControl(array $data): int {
        return $this->core->insert('amb_controles', [
            'empresa_id'              => $data['empresa_id'],
            'asp_id'                  => $data['asp_id'] ?? null,
            'control_criticidad'      => $data['criticidad'] ?? 'media',
            'control_descripcion'     => $data['descripcion'] ?? '',
            'control_efectividad'     => $data['efectividad'] ?? 'baja',
            'control_efectivo'        => $data['efectivo'] ?? 0,
            'control_estado'          => $data['estado'] ?? 'activo',
            'control_fecha_implantacion' => $data['fecha_implantacion'] ?? date('Y-m-d'),
            'control_responsable_id'  => $data['responsable_id'] ?? Auth::userId(),
        ]);
    }

    public function editarControl(int $id, array $data): void {
        $this->core->update('amb_controles', [
            'control_criticidad'      => $data['criticidad'] ?? 'media',
            'control_descripcion'     => $data['descripcion'] ?? '',
            'control_efectividad'     => $data['efectividad'] ?? 'baja',
            'control_efectivo'        => $data['efectivo'] ?? 0,
            'control_estado'          => $data['estado'] ?? 'activo',
        ], 'control_id=:id', ['id' => $id]);
        $control = $this->core->fetchOne('SELECT asp_id FROM amb_controles WHERE control_id=:id', ['id' => $id]);
        if ($control && $control['asp_id']) $this->calcularImpactoResidual((int)$control['asp_id']);
    }

    public function eliminarControl(int $id): void {
        $control = $this->core->fetchOne('SELECT asp_id FROM amb_controles WHERE control_id=:id', ['id' => $id]);
        $this->core->delete('amb_controles', 'control_id=:id', ['id' => $id]);
        if ($control && $control['asp_id']) $this->calcularImpactoResidual((int)$control['asp_id']);
    }

    // ========================================================================
    // PLANES DE TRABAJO AMBIENTAL
    // ========================================================================

    public function getPlanesTrabajo(int $empresaId, ?int $anio = null): array {
        $sql = "SELECT pt.*, u.usuario_nombre as responsable_nombre
                FROM amb_planes_trabajo pt
                LEFT JOIN conf_usuarios u ON pt.plan_responsable_id=u.usuario_id
                WHERE pt.empresa_id=:eid";
        $params = ['eid' => $empresaId];
        if ($anio) { $sql .= " AND pt.plan_anio=:anio"; $params['anio'] = $anio; }
        $sql .= " ORDER BY pt.plan_fecha_inicio DESC";
        return $this->core->fetchAll($sql, $params);
    }

    public function crearPlanTrabajo(array $data): int {
        return $this->core->insert('amb_planes_trabajo', [
            'empresa_id'           => $data['empresa_id'],
            'plan_nombre'          => $data['nombre'] ?? '',
            'plan_anio'            => $data['anio'] ?? date('Y'),
            'plan_objetivo'        => $data['objetivo'] ?? '',
            'plan_fecha_inicio'    => $data['fecha_inicio'] ?? date('Y-m-d'),
            'plan_fecha_fin'       => $data['fecha_fin'] ?? null,
            'plan_responsable_id'  => $data['responsable_id'] ?? Auth::userId(),
            'plan_presupuesto'     => $data['presupuesto'] ?? 0,
            'plan_porcentaje_avance' => $data['avance'] ?? 0,
            'plan_estado'          => $data['estado'] ?? 'planificado',
        ]);
    }

    public function editarPlanTrabajo(int $id, array $data): void {
        $this->core->update('amb_planes_trabajo', [
            'plan_nombre'          => $data['nombre'] ?? '',
            'plan_objetivo'        => $data['objetivo'] ?? '',
            'plan_fecha_fin'       => $data['fecha_fin'] ?? null,
            'plan_porcentaje_avance' => $data['avance'] ?? 0,
            'plan_estado'          => $data['estado'] ?? 'planificado',
        ], 'plan_id=:id', ['id' => $id]);
    }

    public function getActividadesPlan(int $planId): array {
        return $this->core->fetchAll(
            "SELECT * FROM amb_plan_actividades WHERE plan_id=:pid ORDER BY actividad_fecha_inicio",
            ['pid' => $planId]
        );
    }

    public function crearActividadPlan(array $data): int {
        return $this->core->insert('amb_plan_actividades', [
            'plan_id'               => $data['plan_id'],
            'actividad_nombre'      => $data['nombre'] ?? '',
            'actividad_descripcion' => $data['descripcion'] ?? '',
            'actividad_fecha_inicio'=> $data['fecha_inicio'] ?? date('Y-m-d'),
            'actividad_fecha_fin'   => $data['fecha_fin'] ?? null,
            'actividad_responsable_id' => $data['responsable_id'] ?? Auth::userId(),
            'actividad_porcentaje'  => $data['porcentaje'] ?? 0,
            'actividad_estado'      => $data['estado'] ?? 'pendiente',
        ]);
    }

    public function editarActividadPlan(int $id, array $data): void {
        $this->core->update('amb_plan_actividades', [
            'actividad_nombre'       => $data['nombre'] ?? '',
            'actividad_descripcion'  => $data['descripcion'] ?? '',
            'actividad_fecha_fin'    => $data['fecha_fin'] ?? null,
            'actividad_porcentaje'   => $data['porcentaje'] ?? 0,
            'actividad_estado'       => $data['estado'] ?? 'pendiente',
        ], 'actividad_id=:id', ['id' => $id]);
    }

    // ========================================================================
    // METAS AMBIENTALES
    // ========================================================================

    public function getMetasAmbientales(int $empresaId, ?int $anio = null): array {
        $sql = "SELECT * FROM amb_metas_ambientales WHERE empresa_id=:eid";
        $params = ['eid' => $empresaId];
        if ($anio) { $sql .= " AND meta_anio=:anio"; $params['anio'] = $anio; }
        $sql .= " ORDER BY meta_anio DESC, meta_nombre ASC";
        return $this->core->fetchAll($sql, $params);
    }

    public function crearMetaAmbiental(array $data): int {
        return $this->core->insert('amb_metas_ambientales', [
            'empresa_id'        => $data['empresa_id'],
            'meta_nombre'       => $data['nombre'] ?? '',
            'meta_anio'         => $data['anio'] ?? date('Y'),
            'meta_tipo'         => $data['tipo'] ?? 'reduccion_gei',
            'meta_valor_objetivo' => $data['valor_objetivo'] ?? 0,
            'meta_valor_actual' => $data['valor_actual'] ?? 0,
            'meta_unidad'       => $data['unidad'] ?? 'tCO2e',
            'meta_responsable_id' => $data['responsable_id'] ?? Auth::userId(),
            'meta_estado'       => $data['estado'] ?? 'activa',
        ]);
    }

    public function editarMetaAmbiental(int $id, array $data): void {
        $this->core->update('amb_metas_ambientales', [
            'meta_nombre'        => $data['nombre'] ?? '',
            'meta_valor_objetivo' => $data['valor_objetivo'] ?? 0,
            'meta_valor_actual'  => $data['valor_actual'] ?? 0,
            'meta_estado'        => $data['estado'] ?? 'activa',
        ], 'meta_id=:id', ['id' => $id]);
    }

    // ========================================================================
    // DASHBOARD DE PROGRAMAS AMBIENTALES
    // ========================================================================

    public function getDashboardProgramas(int $empresaId): array {
        $programas = $this->getProgramas($empresaId);
        $resultado = [];
        foreach ($programas as $p) {
            $actividades = $this->core->fetchAll(
                "SELECT * FROM amb_plan_actividades WHERE plan_id IN (SELECT plan_id FROM amb_planes_trabajo WHERE empresa_id=:eid AND plan_anio=:anio)",
                ['eid' => $empresaId, 'anio' => date('Y')]
            );
            $totalActividades = count($actividades);
            $completadas = count(array_filter($actividades, fn($a) => ($a['actividad_estado'] ?? '') === 'completada'));
            $adherencia = $totalActividades > 0 ? round(($completadas / $totalActividades) * 100, 1) : 0;
            $resultado[] = [
                'programa_id' => $p['amb_prog_id'] ?? 0,
                'nombre' => $p['amb_prog_nombre'] ?? '',
                'tipo' => $p['amb_prog_tipo'] ?? '',
                'estado' => $p['amb_prog_estado'] ?? '',
                'adherencia' => $adherencia,
                'total_actividades' => $totalActividades,
                'completadas' => $completadas,
                'alerta' => $adherencia < 50 ? 'critica' : ($adherencia < 80 ? 'advertencia' : 'ok'),
            ];
        }
        return $resultado;
    }

    // ========================================================================
    // REGISTROS DE MEDICION
    // ========================================================================

    public function getRegistros(int $empresaId, ?int $anio = null, ?string $tipo = null, int $limit = 100): array {
        $sql = "SELECT * FROM amb_registros WHERE reg_empresa_id=:eid";
        $params = ['eid' => $empresaId];
        if ($anio) { $sql .= " AND YEAR(reg_fecha)=:anio"; $params['anio'] = $anio; }
        if ($tipo) { $sql .= " AND reg_tipo=:tipo"; $params['tipo'] = $tipo; }
        $sql .= " ORDER BY reg_fecha DESC LIMIT :limit"; $params['limit'] = $limit;
        return $this->core->fetchAll($sql, $params);
    }

    public function crearRegistro(array $data): int {
        return $this->core->insert('amb_registros', [
            'reg_empresa_id'   => $data['empresa_id'],
            'reg_tipo'         => $data['tipo'] ?? 'consumo_agua',
            'reg_fecha'        => $data['fecha'] ?? date('Y-m-d'),
            'reg_valor'        => $data['valor'] ?? 0,
            'reg_unidad'       => $data['unidad'] ?? '',
            'reg_observaciones' => $data['observaciones'] ?? '',
        ]);
    }

    public function getTendencia(int $empresaId, string $tipo, int $meses = 12): array {
        return $this->core->fetchAll(
            "SELECT DATE_FORMAT(reg_fecha,'%Y-%m') as mes, SUM(reg_valor) as total, AVG(reg_valor) as promedio
             FROM amb_registros WHERE reg_empresa_id=:eid AND reg_tipo=:tipo
             AND reg_fecha>=DATE_SUB(NOW(),INTERVAL :mes MONTH)
             GROUP BY DATE_FORMAT(reg_fecha,'%Y-%m') ORDER BY mes",
            ['eid' => $empresaId, 'tipo' => $tipo, 'mes' => $meses]
        );
    }

    // ========================================================================
    // PLAN DE GESTION
    // ========================================================================

    public function getPlanGestion(int $empresaId, ?int $anio = null): ?array {
        return $this->core->fetchOne(
            "SELECT * FROM amb_plan_gestion WHERE empresa_id=:eid AND amb_plan_anio=:anio",
            ['eid' => $empresaId, 'anio' => $anio ?? (int)date('Y')]
        );
    }

    public function getPlanesGestion(int $empresaId): array {
        return $this->core->fetchAll(
            "SELECT * FROM amb_plan_gestion WHERE empresa_id=:eid ORDER BY amb_plan_anio DESC",
            ['eid' => $empresaId]
        );
    }

    public function crearPlanGestion(array $data): int {
        return $this->core->insert('amb_plan_gestion', [
            'empresa_id'           => $data['empresa_id'],
            'plan_estrategico_id'  => $data['plan_estrategico_id'] ?? null,
            'amb_plan_anio'        => $data['anio'] ?? date('Y'),
            'amb_plan_objetivo'    => $data['objetivo'] ?? '',
            'amb_plan_alcance'     => $data['alcance'] ?? '',
            'amb_plan_responsable_id' => $data['responsable_id'] ?? Auth::userId(),
            'amb_plan_presupuesto' => $data['presupuesto'] ?? 0,
            'amb_plan_estado'      => $data['estado'] ?? 'borrador',
        ]);
    }

    public function actualizarPlanGestion(int $id, array $data): void {
        $this->core->update('amb_plan_gestion', [
            'plan_estrategico_id'   => $data['plan_estrategico_id'] ?? null,
            'amb_plan_objetivo'     => $data['objetivo'] ?? '',
            'amb_plan_alcance'      => $data['alcance'] ?? '',
            'amb_plan_presupuesto'  => $data['presupuesto'] ?? 0,
            'amb_plan_estado'       => $data['estado'] ?? 'borrador',
        ], 'amb_plan_id=:id', ['id' => $id]);
    }

    public function getProgramas(int $empresaId, ?int $planId = null): array {
        if ($planId) return $this->core->fetchAll(
            "SELECT * FROM amb_programas WHERE amb_plan_id=:pid ORDER BY amb_prog_nombre",
            ['pid' => $planId]
        );
        return $this->core->fetchAll(
            "SELECT * FROM amb_programas WHERE empresa_id=:eid ORDER BY amb_prog_nombre",
            ['eid' => $empresaId]
        );
    }

    public function crearPrograma(array $data): int {
        return $this->core->insert('amb_programas', [
            'amb_plan_id'            => $data['plan_id'] ?? null,
            'empresa_id'             => $data['empresa_id'],
            'amb_prog_nombre'        => $data['nombre'] ?? '',
            'amb_prog_tipo'          => $data['tipo'] ?? 'residuos',
            'amb_prog_objetivo'      => $data['objetivo'] ?? '',
            'amb_prog_fecha_inicio'  => $data['fecha_inicio'] ?? date('Y-m-d'),
            'amb_prog_fecha_fin'     => $data['fecha_fin'] ?? null,
            'amb_prog_responsable_id' => $data['responsable_id'] ?? Auth::userId(),
            'amb_prog_indicador_meta' => $data['meta'] ?? 0,
            'amb_prog_unidad'        => $data['unidad'] ?? '%',
            'amb_prog_estado'        => $data['estado'] ?? 'planificado',
        ]);
    }

    public function actualizarPrograma(int $id, array $data): void {
        $this->core->update('amb_programas', [
            'amb_prog_nombre'         => $data['nombre'] ?? '',
            'amb_prog_objetivo'       => $data['objetivo'] ?? '',
            'amb_prog_fecha_fin'      => $data['fecha_fin'] ?? null,
            'amb_prog_indicador_valor' => $data['valor'] ?? 0,
            'amb_prog_estado'         => $data['estado'] ?? 'planificado',
        ], 'amb_prog_id=:id', ['id' => $id]);
    }

    // ========================================================================
    // AUDITORIAS
    // ========================================================================

    public function getAuditorias(int $empresaId): array {
        return $this->core->fetchAll(
            "SELECT * FROM amb_auditorias WHERE empresa_id=:eid ORDER BY amb_aud_fecha DESC",
            ['eid' => $empresaId]
        );
    }

    public function crearAuditoria(array $data): int {
        return $this->core->insert('amb_auditorias', [
            'empresa_id'             => $data['empresa_id'],
            'amb_aud_tipo'           => $data['tipo'] ?? 'interna',
            'amb_aud_fecha'          => $data['fecha'] ?? date('Y-m-d'),
            'amb_aud_auditor'        => $data['auditor'] ?? '',
            'amb_aud_alcance'        => $data['alcance'] ?? '',
            'amb_aud_hallazgos'      => $data['hallazgos'] ?? 0,
            'amb_aud_no_conformidades' => $data['no_conformidades'] ?? 0,
            'amb_aud_resultado'      => $data['resultado'] ?? 'conforme_observaciones',
            'amb_aud_estado'         => $data['estado'] ?? 'programada',
        ]);
    }

    // ========================================================================
    // REPORTES
    // ========================================================================

    public function getReportes(int $empresaId): array {
        return $this->core->fetchAll(
            "SELECT * FROM amb_reportes_ley WHERE empresa_id=:eid ORDER BY amb_rep_fecha_generado DESC",
            ['eid' => $empresaId]
        );
    }

    public function generarReporteLey(int $empresaId, string $norma, string $nombre, string $periodo): int {
        $anio = (int)substr($periodo, 0, 4);
        $datos = [
            'empresa_id'       => $empresaId,
            'norma'            => $norma,
            'periodo'          => $periodo,
            'fecha_generacion' => date('Y-m-d H:i:s'),
            'estadisticas'     => $this->getEstadisticasAmbiental($empresaId, $anio),
            'aspectos'         => $this->getAspectos($empresaId),
            'registros'        => $this->getRegistros($empresaId, $anio),
            'indicadores'      => $this->getIndicadores($empresaId),
            'huella_carbono'   => $this->getHuellaCarbono($empresaId, $anio),
        ];
        return $this->core->insert('amb_reportes_ley', [
            'empresa_id'           => $empresaId,
            'amb_rep_norma'        => $norma,
            'amb_rep_nombre'       => $nombre,
            'amb_rep_periodo'      => $periodo,
            'amb_rep_fecha_generado' => date('Y-m-d'),
            'amb_rep_usuario_id'   => Auth::userId(),
            'amb_rep_contenido_json' => json_encode($datos),
            'amb_rep_estado'       => 'generado',
        ]);
    }

    public function descargarReporte(int $id, string $moduloNombre = 'ambiental'): void {
        parent::descargarReporte($id, $moduloNombre);
    }

    // ========================================================================
    // ESTADISTICAS
    // ========================================================================

    public function getEstadisticasAmbiental(int $empresaId, int $anio): array {
        $cache = CacheService::getInstance(1800);
        return $cache->remember("estadisticas:$empresaId:$anio", function() use ($empresaId, $anio) {
        $agua = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(reg_valor), 0) FROM amb_registros WHERE reg_empresa_id=:eid AND reg_tipo='consumo_agua' AND YEAR(reg_fecha)=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $energia = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(reg_valor), 0) FROM amb_registros WHERE reg_empresa_id=:eid AND reg_tipo='consumo_energia' AND YEAR(reg_fecha)=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $residuos = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(reg_valor), 0) FROM amb_registros WHERE reg_empresa_id=:eid AND reg_tipo='residuos_generados' AND YEAR(reg_fecha)=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $reciclaje = (float)($this->core->fetchColumn(
            "SELECT COALESCE(SUM(reg_valor), 0) FROM amb_registros WHERE reg_empresa_id=:eid AND reg_tipo='reciclaje' AND YEAR(reg_fecha)=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $aspectos = (int)($this->core->fetchColumn(
            "SELECT COUNT(*) FROM amb_aspectos WHERE asp_empresa_id=:eid", ['eid' => $empresaId]
        ) ?? 0);
        $programas = (int)($this->core->fetchColumn(
            "SELECT COUNT(*) FROM amb_programas WHERE empresa_id=:eid", ['eid' => $empresaId]
        ) ?? 0);
        $emisiones = (int)($this->core->fetchColumn(
            "SELECT COUNT(*) FROM amb_emisiones_gei WHERE empresa_id=:eid AND gei_periodo=:anio",
            ['eid' => $empresaId, 'anio' => $anio]
        ) ?? 0);
        $controles = (int)($this->core->fetchColumn(
            "SELECT COUNT(*) FROM amb_controles WHERE empresa_id=:eid", ['eid' => $empresaId]
        ) ?? 0);
        return compact('agua', 'energia', 'residuos', 'reciclaje', 'aspectos', 'programas', 'emisiones', 'controles');
        });
    }
}
