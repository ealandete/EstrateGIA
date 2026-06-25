<?php
class ProveedoresManager {

    private $core;

    public function __construct() { $this->core = EstrateGiaCore::getInstance(); }

    public function getProveedores(int $empresaId, ?string $tipo = null, ?string $estado = null, ?string $buscar = null, int $page = 1, int $perPage = 20): array {
        $sql = "SELECT p.*,
                (SELECT eval_total FROM cal_proveedor_evaluaciones WHERE eval_proveedor_id = p.prov_id ORDER BY eval_fecha DESC LIMIT 1) as ultima_eval_total,
                (SELECT eval_fecha FROM cal_proveedor_evaluaciones WHERE eval_proveedor_id = p.prov_id ORDER BY eval_fecha DESC LIMIT 1) as ultima_eval_fecha
                FROM cal_proveedores p WHERE p.prov_empresa_id = :eid";
        $params = ['eid' => $empresaId];
        if ($tipo) { $sql .= " AND p.prov_tipo = :tipo"; $params['tipo'] = $tipo; }
        if ($estado) { $sql .= " AND p.prov_estado = :estado"; $params['estado'] = $estado; }
        else { $sql .= " AND p.prov_estado = 'activo'"; }
        if ($buscar) { $sql .= " AND (p.prov_nombre LIKE :buscar OR p.prov_codigo LIKE :buscar)"; $params['buscar'] = "%$buscar%"; }
        $sql .= " ORDER BY p.prov_calificacion DESC";
        return $this->core->paginate($sql, $params, $page, $perPage);
    }

    public function getProveedor(int $id): ?array { return $this->core->fetchOne("SELECT * FROM cal_proveedores WHERE prov_id = :id", ['id' => $id]); }

    public function crearProveedor(array $data): int {
        return $this->core->insert('cal_proveedores', [
            'prov_empresa_id' => $data['empresa_id'],
            'prov_codigo' => $data['codigo'] ?? ('PRV-' . date('Y') . '-' . rand(100, 999)),
            'prov_nombre' => $data['nombre'], 'prov_tipo' => $data['tipo'] ?? 'servicios',
            'prov_contacto' => $data['contacto'] ?? '', 'prov_email' => $data['email'] ?? '',
            'prov_telefono' => $data['telefono'] ?? '', 'prov_estado' => 'activo',
            'prov_proxima_evaluacion' => date('Y-m-d', strtotime('+6 months')),
        ]);
    }

    public function editarProveedor(int $id, array $data): void {
        $this->core->update('cal_proveedores', [
            'prov_nombre' => $data['nombre'], 'prov_tipo' => $data['tipo'] ?? 'servicios',
            'prov_contacto' => $data['contacto'] ?? '', 'prov_email' => $data['email'] ?? '',
            'prov_telefono' => $data['telefono'] ?? '',
        ], 'prov_id = :id', ['id' => $id]);
    }

    public function eliminarProveedor(int $id): void {
        $this->core->delete('cal_proveedor_evaluaciones', 'eval_proveedor_id = :id', ['id' => $id]);
        $this->core->delete('cal_proveedores', 'prov_id = :id', ['id' => $id]);
    }

    public function evaluar(int $proveedorId, array $criterios, string $observaciones = ''): void {
        $prov = $this->getProveedor($proveedorId);
        if (!$prov) return;
        $criteriosDef = $this->getCriteriosPorTipo($prov['prov_tipo']);
        $total = 0;
        foreach ($criteriosDef as $c) { $val = $criterios[$c['id']] ?? 80; $total += $val * $c['peso'] / 100; }
        $total = round($total, 1);
        $this->core->insert('cal_proveedor_evaluaciones', [
            'eval_proveedor_id' => $proveedorId, 'eval_fecha' => date('Y-m-d'),
            'eval_calidad' => $criterios['calidad_producto'] ?? $criterios['calidad'] ?? 0,
            'eval_entrega' => $criterios['tiempos_entrega'] ?? $criterios['entrega'] ?? 0,
            'eval_precio' => $criterios['precio'] ?? 0,
            'eval_servicio' => $criterios['servicio'] ?? 0,
            'eval_total' => $total, 'eval_observaciones' => $observaciones,
            'eval_evaluador_id' => Auth::userId(),
        ]);
        $estado = $total >= 80 ? 'activo' : ($total >= 60 ? 'evaluacion' : 'suspendido');
        $this->core->update('cal_proveedores', [
            'prov_calificacion' => $total, 'prov_ultima_evaluacion' => date('Y-m-d'),
            'prov_proxima_evaluacion' => date('Y-m-d', strtotime('+6 months')), 'prov_estado' => $estado,
        ], 'prov_id = :id', ['id' => $proveedorId]);
    }

    public function getEvaluaciones(int $proveedorId): array {
        return $this->core->fetchAll("SELECT e.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as evaluador FROM cal_proveedor_evaluaciones e LEFT JOIN sys_usuarios u ON e.eval_evaluador_id=u.usuario_id WHERE e.eval_proveedor_id=:id ORDER BY e.eval_fecha DESC", ['id'=>$proveedorId]);
    }

    public function getCriteriosPorTipo(string $tipo): array {
        $map = [
            'medicamentos' => [
                ['id'=>'calidad_producto','item'=>'Calidad del Producto','peso'=>25,'desc'=>'Certificación vigente, registros INVIMA, almacenamiento'],
                ['id'=>'cumplimiento_bpa','item'=>'Cumplimiento BPA/BPD','peso'=>20,'desc'=>'Buenas prácticas, documentación regulatoria'],
                ['id'=>'cadena_frio','item'=>'Cadena de Frío','peso'=>20,'desc'=>'Monitoreo temperatura, calibración, contingencia'],
                ['id'=>'tiempos_entrega','item'=>'Tiempos de Entrega','peso'=>15,'desc'=>'Fechas pactadas, transporte, documentación'],
                ['id'=>'precio','item'=>'Competitividad Precio','peso'=>10,'desc'=>'Comparación mercado, estabilidad'],
                ['id'=>'documentacion','item'=>'Documentación Regulatoria','peso'=>10,'desc'=>'Certificados, fichas técnicas, registros'],
            ],
            'insumos' => [
                ['id'=>'calidad','item'=>'Calidad de Insumos','peso'=>30,'desc'=>'Especificaciones, certificaciones, homogeneidad'],
                ['id'=>'entrega','item'=>'Oportunidad Entrega','peso'=>25,'desc'=>'Tiempos, cantidades, estado llegada'],
                ['id'=>'precio','item'=>'Precio','peso'=>20,'desc'=>'Comparativo, estabilidad, costo/beneficio'],
                ['id'=>'servicio','item'=>'Servicio','peso'=>15,'desc'=>'Atención, flexibilidad, comunicación'],
                ['id'=>'empaque','item'=>'Estado Empaque','peso'=>10,'desc'=>'Protección, identificación, trazabilidad'],
            ],
            'servicios' => [
                ['id'=>'calidad_servicio','item'=>'Calidad del Servicio','peso'=>30,'desc'=>'Especificaciones, profesionalismo, resultados'],
                ['id'=>'cumplimiento_plazos','item'=>'Cumplimiento Plazos','peso'=>25,'desc'=>'Entregas a tiempo, hitos, cronograma'],
                ['id'=>'personal','item'=>'Idoneidad del Personal','peso'=>20,'desc'=>'Competencias, certificaciones, rotación'],
                ['id'=>'precio','item'=>'Precio','peso'=>15,'desc'=>'Competitividad, facturación, costos ocultos'],
                ['id'=>'respuesta','item'=>'Tiempo de Respuesta','peso'=>10,'desc'=>'Velocidad atención, disponibilidad'],
            ],
        ];
        return $map[$tipo] ?? [
            ['id'=>'calidad','item'=>'Calidad','peso'=>30,'desc'=>'Cumplimiento de requisitos y especificaciones'],
            ['id'=>'entrega','item'=>'Entrega','peso'=>25,'desc'=>'Oportunidad y cumplimiento'],
            ['id'=>'precio','item'=>'Precio','peso'=>25,'desc'=>'Competitividad y estabilidad'],
            ['id'=>'servicio','item'=>'Servicio','peso'=>20,'desc'=>'Atención y soporte post-venta'],
        ];
    }

    public function getEstadisticas(int $empresaId): array {
        $total = $this->core->fetchColumn("SELECT COUNT(*) FROM cal_proveedores WHERE prov_empresa_id=:eid",['eid'=>$empresaId])??0;
        $activos = $this->core->fetchColumn("SELECT COUNT(*) FROM cal_proveedores WHERE prov_empresa_id=:eid AND prov_estado='activo'",['eid'=>$empresaId])??0;
        $evaluados = $this->core->fetchColumn("SELECT COUNT(*) FROM cal_proveedores WHERE prov_empresa_id=:eid AND prov_calificacion IS NOT NULL",['eid'=>$empresaId])??0;
        $promedio = $this->core->fetchColumn("SELECT AVG(prov_calificacion) FROM cal_proveedores WHERE prov_empresa_id=:eid AND prov_calificacion IS NOT NULL",['eid'=>$empresaId])??0;
        $porEvaluar = $this->core->fetchColumn("SELECT COUNT(*) FROM cal_proveedores WHERE prov_empresa_id=:eid AND prov_proxima_evaluacion<=CURDATE() AND prov_estado!='suspendido'",['eid'=>$empresaId])??0;
        return compact('total','activos','evaluados','promedio','porEvaluar');
    }

    public function getPlanEvaluacion(int $empresaId, ?int $anio=null): ?array {
        return $this->core->fetchOne("SELECT * FROM prov_plan_evaluacion WHERE empresa_id=:eid AND prov_plan_anio=:anio",['eid'=>$empresaId,'anio'=>$anio??(int)date('Y')]);
    }
    public function crearPlanEvaluacion(array $data): int {
        return $this->core->insert('prov_plan_evaluacion', [
            'empresa_id'=>$data['empresa_id'], 'plan_estrategico_id'=>$data['plan_estrategico_id']??null,
            'prov_plan_anio'=>$data['anio'], 'prov_plan_nombre'=>$data['nombre'],
            'prov_plan_objetivo'=>$data['objetivo']??'', 'prov_plan_responsable_id'=>$data['responsable_id']??Auth::userId(),
            'prov_plan_estado'=>$data['estado']??'borrador',
        ]);
    }
    public function getProgramaciones(int $planId): array {
        return $this->core->fetchAll("SELECT pp.*, p.prov_nombre, p.prov_codigo, p.prov_tipo FROM prov_programacion pp JOIN cal_proveedores p ON pp.prov_id=p.prov_id WHERE pp.prov_plan_id=:pid ORDER BY pp.prov_prog_fecha_prevista",['pid'=>$planId]);
    }
    public function programarEvaluacion(array $data): int {
        return $this->core->insert('prov_programacion', ['prov_plan_id'=>$data['plan_id'],'prov_id'=>$data['proveedor_id'],'prov_prog_fecha_prevista'=>$data['fecha_prevista'],'prov_prog_estado'=>'programada']);
    }
    public function actualizarProgramacion(int $id, array $data): void {
        $this->core->update('prov_programacion', ['prov_prog_fecha_realizada'=>$data['fecha_realizada']??date('Y-m-d'),'prov_prog_resultado'=>$data['resultado']??null,'prov_prog_estado'=>$data['estado']??'realizada'],'prov_prog_id=:id',['id'=>$id]);
    }
    public function getContratos(int $empresaId): array {
        return $this->core->fetchAll("SELECT c.*, p.prov_nombre, p.prov_codigo FROM prov_contratos c JOIN cal_proveedores p ON c.prov_id=p.prov_id WHERE c.empresa_id=:eid ORDER BY c.prov_contr_fecha_inicio DESC",['eid'=>$empresaId]);
    }
    public function crearContrato(array $data): int {
        return $this->core->insert('prov_contratos', [
            'empresa_id'=>$data['empresa_id'],'prov_id'=>$data['proveedor_id'],
            'prov_contr_numero'=>$data['numero']??'','prov_contr_objeto'=>$data['objeto'],
            'prov_contr_valor'=>$data['valor']??0,'prov_contr_fecha_inicio'=>$data['fecha_inicio']??date('Y-m-d'),
            'prov_contr_fecha_fin'=>$data['fecha_fin']??null,'prov_contr_tipo'=>$data['tipo']??'suministro',
            'prov_contr_estado'=>'vigente','prov_contr_observaciones'=>$data['observaciones']??'',
        ]);
    }
    public function descargarReporte(int $empresaId, string $formato='json'): void {
        $datos = ['empresa_id'=>$empresaId,'fecha'=>date('Y-m-d H:i:s'),'estadisticas'=>$this->getEstadisticas($empresaId),'proveedores'=>$this->getProveedores($empresaId)];
        if($formato==='csv'){
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="proveedores_'.date('Ymd').'.csv"');
            $out=fopen('php://output','w');
            fputcsv($out,['Código','Nombre','Tipo','Calificación','Estado','Última Eval.','Próxima Eval.']);
            foreach($datos['proveedores']['data'] as $p) fputcsv($out,[$p['prov_codigo'],$p['prov_nombre'],$p['prov_tipo'],$p['prov_calificacion'],$p['prov_estado'],$p['prov_ultima_evaluacion'],$p['prov_proxima_evaluacion']]);
            fclose($out);
        }else{
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="proveedores_'.date('Ymd').'.json"');
            echo json_encode($datos,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
