<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/ProveedoresManager.php';
require_once BASE_PATH . '/lib/PlanManager.php';
require_once BASE_PATH . '/lib/SafeQuery.php';

class ProveedoresController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = (new PlanManager())->getEmpresa($empresaId);
        $m = new ProveedoresManager();
        $proveedores = $m->getProveedores($empresaId);
        if (isset($proveedores['data'])) $proveedores = $proveedores['data'];

        $criteriosPorTipo = [
            'medicamentos' => ['calidad_producto'=>'Calidad del Producto','cumplimiento_bpa'=>'Cumplimiento BPA/BPD','cadena_frio'=>'Cadena de Frio','tiempos_entrega'=>'Tiempos de Entrega','documentacion'=>'Documentacion Regulatoria','precio'=>'Competitividad Precio'],
            'insumos' => ['calidad'=>'Calidad Insumos','entrega'=>'Oportunidad Entrega','precio'=>'Precio','empaque'=>'Estado Empaque','servicio'=>'Servicio Post-venta'],
            'equipos' => ['estado_equipo'=>'Estado del Equipo','mantenimiento'=>'Mantenimiento/Soporte','garantia'=>'Cumplimiento Garantia','capacitacion'=>'Capacitacion','disponibilidad'=>'Disponibilidad Repuestos'],
            'servicios' => ['calidad_servicio'=>'Calidad del Servicio','cumplimiento_plazos'=>'Cumplimiento Plazos','personal'=>'Idoneidad Personal','respuesta'=>'Tiempo Respuesta','precio'=>'Precio'],
            'consultoria' => ['conocimiento'=>'Conocimiento Tecnico','metodologia'=>'Metodologia','resultados'=>'Resultados Entregables','comunicacion'=>'Comunicacion','costo_beneficio'=>'Costo/Beneficio'],
        ];

        $pageTitle = 'Proveedores';
        ob_start(); require BASE_PATH . '/templates/extras/proveedores.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function ver(int $id): void {
        $m = new ProveedoresManager();
        $prov = $m->getProveedor($id);
        if (!$prov) { header('Location: /proveedores'); exit; }
        $evals = $m->getEvaluaciones($id);
        $criteriosPorTipo = [
            'medicamentos' => ['calidad_producto'=>'Calidad Producto (0-100)','cumplimiento_bpa'=>'Cumplimiento BPA/BPD','cadena_frio'=>'Cadena de Frio','tiempos_entrega'=>'Tiempos Entrega','documentacion'=>'Documentacion Regulatoria','precio'=>'Precio'],
            'insumos' => ['calidad'=>'Calidad Insumos','entrega'=>'Oportunidad Entrega','precio'=>'Precio','empaque'=>'Estado Empaque','servicio'=>'Servicio Post-venta'],
            'equipos' => ['estado_equipo'=>'Estado Equipo','mantenimiento'=>'Mantenimiento/Soporte','garantia'=>'Cumplimiento Garantia','capacitacion'=>'Capacitacion','disponibilidad'=>'Disponibilidad Repuestos'],
            'servicios' => ['calidad_servicio'=>'Calidad Servicio','cumplimiento_plazos'=>'Cumplimiento Plazos','personal'=>'Idoneidad Personal','respuesta'=>'Tiempo Respuesta','precio'=>'Precio'],
            'consultoria' => ['conocimiento'=>'Conocimiento Tecnico','metodologia'=>'Metodologia','resultados'=>'Resultados','comunicacion'=>'Comunicacion','costo_beneficio'=>'Costo/Beneficio'],
            'otro' => ['calidad'=>'Calidad','entrega'=>'Entrega','precio'=>'Precio','servicio'=>'Servicio'],
        ];
        $criterios = $criteriosPorTipo[$prov['prov_tipo']] ?? $criteriosPorTipo['otro'];
        $pageTitle = 'Proveedor: ' . htmlspecialchars($prov['prov_nombre']);
        ob_start(); require BASE_PATH . '/templates/extras/proveedor_detalle.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearProveedor(): void { $id = (new ProveedoresManager())->crearProveedor($_POST); header('Location: /proveedores?ver='.$id); exit; }
    public function editarProveedor(): void { (new ProveedoresManager())->editarProveedor((int)$_POST['id'], $_POST); header('Location: /proveedores?ok=1'); exit; }
    public function eliminarProveedor(): void { (new ProveedoresManager())->eliminarProveedor((int)$_POST['id']); header('Location: /proveedores?ok=1'); exit; }
    public function evaluar(): void { $m = new ProveedoresManager(); $pid = (int)$_POST['proveedor_id']; $m->evaluar($pid, $_POST['criterio'] ?? [], $_POST['observaciones'] ?? ''); header('Location: /proveedores?ver='.$pid.'&eval_ok=1'); exit; }
    public function crearPlanEvaluacion(): void { (new ProveedoresManager())->crearPlanEvaluacion($_POST); header('Location: /proveedores?seccion=plan&ok=1'); exit; }
    public function programarEvaluacion(): void { (new ProveedoresManager())->programarEvaluacion($_POST); header('Location: /proveedores?seccion=plan&ok=1'); exit; }
    public function completarEvaluacion(): void { (new ProveedoresManager())->actualizarProgramacion((int)$_POST['id'], $_POST); header('Location: /proveedores?seccion=plan&ok=1'); exit; }
    public function crearContrato(): void { (new ProveedoresManager())->crearContrato($_POST); header('Location: /proveedores?seccion=contratos&ok=1'); exit; }
    public function descargarReporte(): void { $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2)); $formato = $_GET['formato'] ?? 'json'; (new ProveedoresManager())->descargarReporte($empresaId, $formato); }

    public function checklist(): void {
        $checklists = $this->safeAll("SELECT * FROM prov_checklists ORDER BY checklist_nombre");
        $pageTitle = 'Checklists de Proveedores';
        ob_start(); require BASE_PATH . '/templates/proveedores/checklist_admin.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function guardarChecklist(): void {
        $id = (int)($_POST['checklist_id'] ?? 0);
        $data = [
            'checklist_nombre' => $_POST['checklist_nombre'] ?? '',
            'checklist_tipo_proveedor' => $_POST['checklist_tipo_proveedor'] ?? 'servicios',
            'checklist_criterios_json' => $_POST['checklist_criterios_json'] ?? '[]',
            'checklist_activo' => 1,
        ];
        if ($id > 0) {
            $this->safeUpdate('prov_checklists', $data, 'checklist_id = ?', [$id]);
        } else {
            $id = $this->safeInsert('prov_checklists', $data);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'checklist_id' => $id]);
        exit;
    }
}
