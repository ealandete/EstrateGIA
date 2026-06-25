<?php
abstract class BaseHSEManager {
    protected $core;
    protected string $prefijoInd;
    protected string $prefijoReq;
    protected string $prefijoRep;
    protected string $tablaIndicadores;
    protected string $tablaReqLegales;
    protected string $tablaReportes;
    protected string $colEmpresaInd;
    protected string $colEmpresaReq;
    protected string $colEmpresaRep;

    public function __construct() { $this->core = EstrateGiaCore::getInstance(); }

    public function getIndicadores(int $empresaId): array {
        $col = $this->colEmpresaInd;
        return $this->core->fetchAll("SELECT * FROM {$this->tablaIndicadores} WHERE $col = :eid", ['eid' => $empresaId]);
    }

    public function crearIndicador(array $data): int {
        $pre = $this->prefijoInd;
        $id = $this->core->insert($this->tablaIndicadores, [
            $this->colEmpresaInd => $data['empresa_id'],
            $pre . 'nombre' => $data['nombre'],
            $pre . 'formula' => $data['formula'] ?? '',
            $pre . 'meta' => $data['meta'] ?? 0,
            $pre . 'periodo' => $data['periodo'] ?? date('Y'),
            $pre . 'valor' => $data['valor'] ?? 0,
            $pre . 'unidad' => $data['unidad'] ?? '%',
        ]);
        if (($data['meta'] ?? 0) > 0 && ($data['valor'] ?? 0) > 0) {
            $porcentaje = ($data['valor'] / $data['meta']) * 100;
            if ($porcentaje > 100) {
                $this->core->sendNotification(Auth::userId(), 'Indicador excedido', "{$data['nombre']} superó la meta ({$data['valor']} vs {$data['meta']})", 'warning');
            }
        }
        return $id;
    }

    public function getRequisitosLegales(int $empresaId): array {
        $col = $this->colEmpresaReq;
        $fechaLimite = $this->prefijoReq . 'req_fecha_limite';
        return $this->core->fetchAll("SELECT * FROM {$this->tablaReqLegales} WHERE $col = :eid ORDER BY $fechaLimite", ['eid' => $empresaId]);
    }

    public function crearRequisitoLegal(array $data): int {
        $pre = $this->prefijoReq . 'req_';
        return $this->core->insert($this->tablaReqLegales, [
            $this->colEmpresaReq => $data['empresa_id'],
            $pre . 'norma' => $data['norma'],
            $pre . 'articulo' => $data['articulo'] ?? '',
            $pre . 'descripcion' => $data['descripcion'],
            $pre . 'entidad' => $data['entidad'] ?? 'minTrabajo',
            $pre . 'periodicidad' => $data['periodicidad'] ?? 'anual',
            $pre . 'fecha_limite' => $data['fecha_limite'] ?? null,
            $pre . 'cumplimiento' => $data['cumplimiento'] ?? 'cumple_parcial',
            $pre . 'evidencia' => $data['evidencia'] ?? '',
        ]);
    }

    public function actualizarRequisito(int $id, array $data): void {
        $pre = $this->prefijoReq . 'req_';
        $pkId = $this->prefijoReq . 'req_id';
        $this->core->update($this->tablaReqLegales, [
            $pre . 'cumplimiento' => $data['cumplimiento'] ?? 'cumple_parcial',
            $pre . 'evidencia' => $data['evidencia'] ?? '',
        ], "$pkId = :id", ['id' => $id]);
    }

    public function getReportes(int $empresaId): array {
        $col = $this->colEmpresaRep;
        $fecha = $this->prefijoRep . 'rep_fecha_generado';
        return $this->core->fetchAll("SELECT * FROM {$this->tablaReportes} WHERE $col = :eid ORDER BY $fecha DESC", ['eid' => $empresaId]);
    }

    public function getReporte(int $id): ?array {
        $pkId = $this->prefijoRep . 'rep_id';
        return $this->core->fetchOne("SELECT * FROM {$this->tablaReportes} WHERE $pkId = :id", ['id' => $id]);
    }

    public function descargarReporte(int $id, string $moduloNombre): void {
        $rep = $this->getReporte($id);
        if (!$rep) { header("Location: /$moduloNombre?err=reporte_no_encontrado"); exit; }
        $contenidoCol = $this->prefijoRep . 'rep_contenido_json';
        $normaCol = $this->prefijoRep . 'rep_norma';
        $periodoCol = $this->prefijoRep . 'rep_periodo';
        $datos = json_decode($rep[$contenidoCol], true);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="reporte_' . $rep[$normaCol] . '_' . $rep[$periodoCol] . '.json"');
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function getUsuarios(int $empresaId): array {
        return $this->core->fetchAll(
            "SELECT u.usuario_id, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as nombre,
                    u.usuario_cargo, u.usuario_rol_id
             FROM sys_usuarios u
             JOIN sys_usuario_empresa ue ON u.usuario_id = ue.ue_usuario_id
             WHERE ue.ue_empresa_id = :eid AND u.usuario_activo = 1
             ORDER BY u.usuario_nombre", ['eid' => $empresaId]);
    }
}
