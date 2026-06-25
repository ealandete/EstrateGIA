<?php
class SSTManager extends BaseHSEManager {

    public function __construct() {
        parent::__construct();
        $this->prefijoInd = 'sind_';
        $this->prefijoReq = 'sst_';
        $this->prefijoRep = 'sst_';
        $this->tablaIndicadores = 'sst_indicadores';
        $this->tablaReqLegales = 'sst_requisitos_legales';
        $this->tablaReportes = 'sst_reportes_ley';
        $this->colEmpresaInd = 'sind_empresa_id';
        $this->colEmpresaReq = 'empresa_id';
        $this->colEmpresaRep = 'empresa_id';
    }

    public function getPeligros(int $empresaId): array {
        return $this->core->fetchAll("SELECT p.*, pr.proceso_nombre FROM sst_peligros p LEFT JOIN proc_procesos pr ON p.peligro_proceso_id=pr.proceso_id WHERE p.peligro_empresa_id=:eid ORDER BY p.peligro_nivel DESC", ['eid'=>$empresaId]);
    }
    public function crearPeligro(array $data): int {
        $id = $this->core->insert('sst_peligros', [
            'peligro_empresa_id'=>$data['empresa_id'], 'peligro_proceso_id'=>$data['proceso_id']??null,
            'peligro_codigo'=>$data['codigo']??('PEL-'.date('Y').'-'.rand(100,999)),
            'peligro_descripcion'=>$data['descripcion'], 'peligro_tipo'=>$data['tipo']??'fisico',
            'peligro_probabilidad'=>$data['probabilidad']??'medio', 'peligro_consecuencia'=>$data['consecuencia']??'moderado',
            'peligro_nivel'=>$data['nivel']??'tolerable', 'peligro_controles'=>$data['controles']??'',
            'peligro_estado'=>$data['estado']??'identificado',
        ]);
        $this->core->logAction(Auth::userId(), 'crear', 'sst', 'peligro', $id);
        return $id;
    }
    public function editarPeligro(int $id, array $data): void {
        $this->core->update('sst_peligros', [
            'peligro_descripcion'=>$data['descripcion'], 'peligro_tipo'=>$data['tipo']??'fisico',
            'peligro_probabilidad'=>$data['probabilidad']??'medio', 'peligro_consecuencia'=>$data['consecuencia']??'moderado',
            'peligro_nivel'=>$data['nivel']??'tolerable', 'peligro_controles'=>$data['controles']??'',
            'peligro_estado'=>$data['estado']??'identificado', 'peligro_proceso_id'=>$data['proceso_id']??null,
        ], 'peligro_id=:id', ['id'=>$id]);
    }
    public function eliminarPeligro(int $id): void { $this->core->delete('sst_peligros','peligro_id=:id',['id'=>$id]); }

    public function getIncidentes(int $empresaId, ?int $anio=null, ?string $tipo=null, int $page=1, int $perPage=20): array {
        $sql = "SELECT i.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as usuario_nombre, pr.proceso_nombre FROM sst_incidentes i LEFT JOIN sys_usuarios u ON i.inc_usuario_id=u.usuario_id LEFT JOIN proc_procesos pr ON i.inc_proceso_id=pr.proceso_id WHERE i.inc_empresa_id=:eid";
        $params = ['eid'=>$empresaId];
        if($anio){$sql.=" AND YEAR(i.inc_fecha)=:anio"; $params['anio']=$anio;}
        if($tipo){$sql.=" AND i.inc_tipo=:tipo"; $params['tipo']=$tipo;}
        return $this->core->paginate($sql." ORDER BY i.inc_fecha DESC", $params, $page, $perPage);
    }
    public function crearIncidente(array $data): int {
        $id = $this->core->insert('sst_incidentes', [
            'inc_empresa_id'=>$data['empresa_id'], 'inc_codigo'=>$data['codigo']??('INC-'.date('Y').'-'.rand(100,999)),
            'inc_fecha'=>$data['fecha']??date('Y-m-d'), 'inc_tipo'=>$data['tipo']??'incidente',
            'inc_descripcion'=>$data['descripcion'], 'inc_proceso_id'=>$data['proceso_id']??null,
            'inc_gravedad'=>$data['gravedad']??'leve', 'inc_dias_incapacidad'=>$data['dias_incapacidad']??0,
            'inc_costo'=>$data['costo']??0, 'inc_parte_cuerpo'=>$data['parte_cuerpo']??'',
            'inc_agente'=>$data['agente']??'', 'inc_estado'=>'reportado',
        ]);
        if (in_array($data['gravedad']??'leve', ['grave','fatal'])) {
            $this->core->sendNotification(Auth::userId(), 'Incidente ' . ($data['gravedad']??'grave'), 'Se reportó un incidente ' . ($data['gravedad']??'grave') . ': ' . substr($data['descripcion']??'',0,80), 'danger', '/sst?seccion=incidentes', 'sst', $id);
        }
        return $id;
    }
    public function investigarIncidente(int $id, array $data): void {
        $this->core->update('sst_incidentes', [
            'inc_investigacion'=>$data['investigacion'], 'inc_accion_correctiva'=>$data['accion_correctiva']??'',
            'inc_estado'=>'investigado', 'inc_dias_incapacidad'=>$data['dias_incapacidad']??0,
            'inc_costo'=>$data['costo']??0,
        ], 'inc_id=:id', ['id'=>$id]);
    }
    public function cerrarIncidente(int $id): void { $this->core->update('sst_incidentes',['inc_estado'=>'cerrado'],'inc_id=:id',['id'=>$id]); }

    public function getPlanTrabajo(int $empresaId, ?int $anio=null): ?array {
        return $this->core->fetchOne("SELECT * FROM sst_plan_trabajo WHERE empresa_id=:eid AND sst_plan_anio=:anio", ['eid'=>$empresaId,'anio'=>$anio??(int)date('Y')]);
    }
    public function getPlanesTrabajo(int $empresaId): array { return $this->core->fetchAll("SELECT * FROM sst_plan_trabajo WHERE empresa_id=:eid ORDER BY sst_plan_anio DESC",['eid'=>$empresaId]); }
    public function crearPlanTrabajo(array $data): int {
        return $this->core->insert('sst_plan_trabajo', [
            'empresa_id'=>$data['empresa_id'], 'plan_estrategico_id'=>$data['plan_estrategico_id']??null,
            'sst_plan_anio'=>$data['anio'], 'sst_plan_objetivo'=>$data['objetivo']??'',
            'sst_plan_alcance'=>$data['alcance']??'', 'sst_plan_responsable_id'=>$data['responsable_id']??Auth::userId(),
            'sst_plan_presupuesto'=>$data['presupuesto']??0, 'sst_plan_fecha_aprobacion'=>$data['fecha_aprobacion']??null,
            'sst_plan_estado'=>$data['estado']??'borrador',
        ]);
    }
    public function actualizarPlanTrabajo(int $id, array $data): void {
        $this->core->update('sst_plan_trabajo', [
            'plan_estrategico_id'=>$data['plan_estrategico_id']??null, 'sst_plan_objetivo'=>$data['objetivo']??'',
            'sst_plan_alcance'=>$data['alcance']??'', 'sst_plan_presupuesto'=>$data['presupuesto']??0,
            'sst_plan_estado'=>$data['estado']??'borrador',
        ], 'sst_plan_id=:id', ['id'=>$id]);
    }
    public function getActividades(int $planId): array {
        return $this->core->fetchAll("SELECT a.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable FROM sst_plan_actividades a LEFT JOIN sys_usuarios u ON a.sst_act_responsable_id=u.usuario_id WHERE a.sst_plan_id=:pid ORDER BY a.sst_act_fecha_inicio",['pid'=>$planId]);
    }
    public function crearActividad(array $data): int {
        return $this->core->insert('sst_plan_actividades', [
            'sst_plan_id'=>$data['plan_id'], 'sst_act_nombre'=>$data['nombre'],
            'sst_act_tipo'=>$data['tipo']??'gestion', 'sst_act_fecha_inicio'=>$data['fecha_inicio']??null,
            'sst_act_fecha_fin'=>$data['fecha_fin']??null, 'sst_act_responsable_id'=>$data['responsable_id']??null,
            'sst_act_recursos'=>$data['recursos']??'', 'sst_act_estado'=>$data['estado']??'pendiente',
        ]);
    }
    public function actualizarActividad(int $id, array $data): void {
        $this->core->update('sst_plan_actividades', [
            'sst_act_nombre'=>$data['nombre'], 'sst_act_tipo'=>$data['tipo']??'gestion',
            'sst_act_fecha_inicio'=>$data['fecha_inicio']??null, 'sst_act_fecha_fin'=>$data['fecha_fin']??null,
            'sst_act_estado'=>$data['estado']??'pendiente', 'sst_act_porcentaje'=>$data['porcentaje']??0,
        ], 'sst_act_id=:id', ['id'=>$id]);
    }
    public function eliminarActividad(int $id): void { $this->core->delete('sst_plan_actividades','sst_act_id=:id',['id'=>$id]); }

    public function getAusentismo(int $empresaId, ?int $anio=null, int $page = 1, int $perPage = 20): array {
        $sql="SELECT a.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as usuario_nombre FROM sst_ausentismo a LEFT JOIN sys_usuarios u ON a.aus_usuario_id=u.usuario_id WHERE a.empresa_id=:eid";
        $params=['eid'=>$empresaId];
        if($anio){$sql.=" AND YEAR(a.aus_fecha_inicio)=:anio"; $params['anio']=$anio;}
        $sql.=" ORDER BY a.aus_fecha_inicio DESC";
        return $this->core->paginate($sql, $params, $page, $perPage);
    }
    public function crearAusentismo(array $data): int {
        return $this->core->insert('sst_ausentismo', [
            'empresa_id'=>$data['empresa_id'], 'aus_usuario_id'=>$data['usuario_id']??null,
            'aus_tipo'=>$data['tipo']??'enfermedad_general', 'aus_fecha_inicio'=>$data['fecha_inicio'],
            'aus_fecha_fin'=>$data['fecha_fin'], 'aus_dias'=>$data['dias']??0,
            'aus_diagnostico'=>$data['diagnostico']??'', 'aus_observaciones'=>$data['observaciones']??'',
        ]);
    }

    public function getCapacitaciones(int $empresaId, ?int $anio=null, int $page = 1, int $perPage = 20): array {
        $sql="SELECT * FROM sst_capacitaciones WHERE empresa_id=:eid";
        $params=['eid'=>$empresaId];
        if($anio){$sql.=" AND YEAR(sst_cap_fecha)=:anio"; $params['anio']=$anio;}
        return $this->core->paginate($sql." ORDER BY sst_cap_fecha DESC", $params, $page, $perPage);
    }
    public function crearCapacitacion(array $data): int {
        return $this->core->insert('sst_capacitaciones', [
            'empresa_id'=>$data['empresa_id'], 'sst_cap_tema'=>$data['tema'],
            'sst_cap_fecha'=>$data['fecha']??date('Y-m-d'), 'sst_cap_duracion_horas'=>$data['duracion_horas']??1,
            'sst_cap_facilitador'=>$data['facilitador']??'', 'sst_cap_tipo'=>$data['tipo']??'formacion',
            'sst_cap_participantes'=>$data['participantes']??0, 'sst_cap_evaluacion'=>$data['evaluacion']??null,
        ]);
    }

    public function getExamenes(int $empresaId, ?int $anio=null, int $page = 1, int $perPage = 20): array {
        $sql="SELECT e.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as usuario_nombre FROM sst_examenes e LEFT JOIN sys_usuarios u ON e.sst_exm_usuario_id=u.usuario_id WHERE e.empresa_id=:eid";
        $params=['eid'=>$empresaId];
        if($anio){$sql.=" AND YEAR(e.sst_exm_fecha)=:anio"; $params['anio']=$anio;}
        return $this->core->paginate($sql." ORDER BY e.sst_exm_fecha DESC", $params, $page, $perPage);
    }
    public function crearExamen(array $data): int {
        return $this->core->insert('sst_examenes', [
            'empresa_id'=>$data['empresa_id'], 'sst_exm_usuario_id'=>$data['usuario_id'],
            'sst_exm_tipo'=>$data['tipo']??'periodico', 'sst_exm_fecha'=>$data['fecha']??date('Y-m-d'),
            'sst_exm_resultado'=>$data['resultado']??'pendiente', 'sst_exm_restricciones'=>$data['restricciones']??'',
            'sst_exm_ips'=>$data['ips']??'', 'sst_exm_observaciones'=>$data['observaciones']??'',
        ]);
    }

    public function getInspecciones(int $empresaId): array { return $this->core->fetchAll("SELECT * FROM sst_inspecciones WHERE empresa_id=:eid ORDER BY sst_ins_fecha DESC",['eid'=>$empresaId]); }
    public function crearInspeccion(array $data): int {
        return $this->core->insert('sst_inspecciones', [
            'empresa_id'=>$data['empresa_id'], 'sst_ins_tipo'=>$data['tipo']??'locativa',
            'sst_ins_fecha'=>$data['fecha']??date('Y-m-d'), 'sst_ins_area'=>$data['area']??'',
            'sst_ins_responsable_id'=>$data['responsable_id']??Auth::userId(), 'sst_ins_observaciones'=>$data['observaciones']??'',
            'sst_ins_estado'=>'realizada',
        ]);
    }

    public function getEmergencias(int $empresaId): array { return $this->core->fetchAll("SELECT * FROM sst_emergencias WHERE empresa_id=:eid ORDER BY sst_eme_tipo",['eid'=>$empresaId]); }
    public function crearEmergencia(array $data): int {
        return $this->core->insert('sst_emergencias', [
            'empresa_id'=>$data['empresa_id'], 'sst_eme_tipo'=>$data['tipo']??'incendio',
            'sst_eme_nombre'=>$data['nombre'], 'sst_eme_procedimiento'=>$data['procedimiento']??'',
            'sst_eme_brigadistas'=>$data['brigadistas']??0, 'sst_eme_ultimo_simulacro'=>$data['ultimo_simulacro']??null,
            'sst_eme_proximo_simulacro'=>$data['proximo_simulacro']??null, 'sst_eme_estado'=>'vigente',
        ]);
    }

    public function getEstadisticasSST(int $empresaId, int $anio): array {
        $incidentes = $this->core->fetchColumn("SELECT COUNT(*) FROM sst_incidentes WHERE inc_empresa_id=:eid AND YEAR(inc_fecha)=:anio",['eid'=>$empresaId,'anio'=>$anio]);
        $accidentes = $this->core->fetchColumn("SELECT COUNT(*) FROM sst_incidentes WHERE inc_empresa_id=:eid AND inc_tipo='accidente' AND YEAR(inc_fecha)=:anio",['eid'=>$empresaId,'anio'=>$anio]);
        $diasPerdidos = $this->core->fetchColumn("SELECT COALESCE(SUM(inc_dias_incapacidad),0) FROM sst_incidentes WHERE inc_empresa_id=:eid AND YEAR(inc_fecha)=:anio",['eid'=>$empresaId,'anio'=>$anio])??0;
        $costos = $this->core->fetchColumn("SELECT COALESCE(SUM(inc_costo),0) FROM sst_incidentes WHERE inc_empresa_id=:eid AND YEAR(inc_fecha)=:anio",['eid'=>$empresaId,'anio'=>$anio])??0;
        $actividadesTotal = $this->core->fetchColumn("SELECT COUNT(*) FROM sst_plan_actividades a JOIN sst_plan_trabajo p ON a.sst_plan_id=p.sst_plan_id WHERE p.empresa_id=:eid AND p.sst_plan_anio=:anio",['eid'=>$empresaId,'anio'=>$anio])?:1;
        $actividadesCompletadas = $this->core->fetchColumn("SELECT COUNT(*) FROM sst_plan_actividades a JOIN sst_plan_trabajo p ON a.sst_plan_id=p.sst_plan_id WHERE p.empresa_id=:eid AND p.sst_plan_anio=:anio AND a.sst_act_estado='completada'",['eid'=>$empresaId,'anio'=>$anio])??0;
        $capacitaciones = $this->core->fetchColumn("SELECT COUNT(*) FROM sst_capacitaciones WHERE empresa_id=:eid AND YEAR(sst_cap_fecha)=:anio",['eid'=>$empresaId,'anio'=>$anio])??0;
        $examenes = $this->core->fetchColumn("SELECT COUNT(*) FROM sst_examenes WHERE empresa_id=:eid AND YEAR(sst_exm_fecha)=:anio",['eid'=>$empresaId,'anio'=>$anio])??0;
        return compact('incidentes','accidentes','diasPerdidos','costos','actividadesTotal','actividadesCompletadas','capacitaciones','examenes');
    }

    public function generarReporteLey(int $empresaId, string $norma, string $nombre, string $periodo): int {
        $datos = $this->generarDatosReporte($empresaId, $norma, $periodo);
        return $this->core->insert('sst_reportes_ley', [
            'empresa_id'=>$empresaId, 'sst_rep_norma'=>$norma, 'sst_rep_nombre'=>$nombre,
            'sst_rep_periodo'=>$periodo, 'sst_rep_fecha_generado'=>date('Y-m-d'),
            'sst_rep_usuario_id'=>Auth::userId(), 'sst_rep_contenido_json'=>json_encode($datos),
            'sst_rep_estado'=>'generado',
        ]);
    }

    public function descargarReporte(int $id, string $moduloNombre = 'sst'): void { parent::descargarReporte($id, $moduloNombre); }

    private function generarDatosReporte(int $empresaId, string $norma, string $periodo): array {
        $anio = (int)substr($periodo,0,4);
        $stats = $this->getEstadisticasSST($empresaId, $anio);
        return [
            'empresa_id'=>$empresaId, 'norma'=>$norma, 'periodo'=>$periodo,
            'fecha_generacion'=>date('Y-m-d H:i:s'), 'estadisticas'=>$stats,
            'peligros'=>count($this->getPeligros($empresaId)),
            'incidentes'=>$this->getIncidentes($empresaId, $anio),
            'indicadores'=>$this->getIndicadores($empresaId),
        ];
    }
}
