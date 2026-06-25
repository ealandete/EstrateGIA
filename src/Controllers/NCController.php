<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class NCController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $estado = $_GET['estado'] ?? '';
        $where = "nc.nc_empresa_id = ?";
        $params = [$empresaId];
        if ($estado) { $where .= " AND nc.nc_estado = ?"; $params[] = $estado; }

        $ncs = $this->safeAll(
            "SELECT nc.*, p.proceso_nombre, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre
             FROM cal_no_conformidades nc
             LEFT JOIN proc_procesos p ON nc.nc_proceso_id = p.proceso_id
             LEFT JOIN sys_usuarios u ON nc.nc_responsable_id = u.usuario_id
             WHERE $where ORDER BY nc.nc_fecha_deteccion DESC, nc.nc_id DESC LIMIT 100",
            $params
        );

        $procesos = $this->safeAll(
            'SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id
             WHERE m.macro_empresa_id=? AND p.proceso_activo=1 ORDER BY p.proceso_nombre',
            [$empresaId]
        );

        $pageTitle = 'No Conformidades';
        ob_start(); require BASE_PATH . '/templates/nc/index.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crear(): void {
        $id = $this->safeInsert('cal_no_conformidades', [
            'nc_empresa_id' => (int)$_POST['empresa_id'],
            'nc_proceso_id' => $_POST['proceso_id'] ? (int)$_POST['proceso_id'] : null,
            'nc_codigo' => 'NC-' . date('Y') . '-' . str_pad(rand(1,999), 3, '0', STR_PAD_LEFT),
            'nc_tipo' => $_POST['tipo'] ?? 'no_conformidad',
            'nc_origen' => $_POST['origen'] ?? 'auditoria_interna',
            'nc_descripcion' => $_POST['descripcion'],
            'nc_requisito_iso' => $_POST['requisito_iso'] ?? '',
            'nc_gravedad' => $_POST['gravedad'] ?? 'menor',
            'nc_fecha_deteccion' => $_POST['fecha'] ?? date('Y-m-d'),
            'nc_responsable_id' => $_POST['responsable_id'] ? (int)$_POST['responsable_id'] : null,
            'nc_creado_por' => Auth::userId(),
        ]);
        $this->core->logAction(Auth::userId(), 'crear', 'calidad', 'no_conformidad', $id);
        header('Location: /nc?created=1'); exit;
    }

    public function ver(int $id): void {
        $nc = $this->safeOne(
            "SELECT nc.*, p.proceso_nombre, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre
             FROM cal_no_conformidades nc
             LEFT JOIN proc_procesos p ON nc.nc_proceso_id = p.proceso_id
             LEFT JOIN sys_usuarios u ON nc.nc_responsable_id = u.usuario_id
             WHERE nc.nc_id = ?", [$id]
        );
        if (!$nc) { http_response_code(404); echo 'No encontrado'; return; }

        $seguimiento = json_decode($nc['nc_seguimiento'] ?? '[]', true) ?: [];
        $pageTitle = 'NC #' . $nc['nc_codigo'];
        ob_start(); require BASE_PATH . '/templates/nc/ver.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function actualizar(int $id): void {
        $data = [
            'nc_estado' => $_POST['estado'],
            'nc_analisis_causa' => $_POST['analisis_causa'] ?? null,
            'nc_plan_accion' => $_POST['plan_accion'] ?? null,
        ];
        if ($_POST['estado'] === 'cerrada') $data['nc_fecha_cierre'] = date('Y-m-d');

        $nc = $this->safeOne('SELECT nc_seguimiento FROM cal_no_conformidades WHERE nc_id=?', [$id]);
        $seguimiento = json_decode($nc['nc_seguimiento'] ?? '[]', true) ?: [];
        $seguimiento[] = [
            'fecha' => date('Y-m-d H:i'),
            'estado' => $_POST['estado'],
            'usuario' => Auth::userId(),
            'nota' => $_POST['nota_seguimiento'] ?? '',
        ];
        $data['nc_seguimiento'] = json_encode($seguimiento);

        $this->safeUpdate('cal_no_conformidades', $data, 'nc_id = ?', [$id]);
        $this->core->logAction(Auth::userId(), 'actualizar', 'calidad', 'no_conformidad', $id);
        header('Location: /nc/ver/' . $id . '?updated=1'); exit;
    }
}
