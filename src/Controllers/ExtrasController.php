<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class ExtrasController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function proveedores(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = (new PlanManager())->getEmpresa($empresaId);
        $proveedores = $this->safeAll("SELECT * FROM cal_proveedores WHERE prov_empresa_id=? ORDER BY prov_nombre", [$empresaId]);
        $pageTitle = 'Proveedores'; ob_start(); require BASE_PATH . '/templates/extras/proveedores.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearProveedor(): void {
        $this->safeInsert('cal_proveedores', ['prov_empresa_id'=>(int)$_POST['empresa_id'],'prov_codigo'=>'PRV-'.date('Y').'-'.rand(100,999),'prov_nombre'=>$_POST['nombre'],'prov_tipo'=>$_POST['tipo']??'servicios','prov_contacto'=>$_POST['contacto']??'','prov_email'=>$_POST['email']??'','prov_telefono'=>$_POST['telefono']??'','prov_calificacion'=>$_POST['calificacion']??null]);
        header('Location: /proveedores?created=1'); exit;
    }

    public function evaluarProveedor(): void {
        $pid = (int)$_POST['proveedor_id'];
        $calidad = (float)($_POST['calidad'] ?? 0);
        $entrega = (float)($_POST['entrega'] ?? 0);
        $precio = (float)($_POST['precio'] ?? 0);
        $servicio = (float)($_POST['servicio'] ?? 0);
        $total = round(($calidad + $entrega + $precio + $servicio) / 4, 1);

        $this->safeInsert('cal_proveedor_evaluaciones', ['eval_proveedor_id'=>$pid,'eval_fecha'=>date('Y-m-d'),'eval_calidad'=>$calidad,'eval_entrega'=>$entrega,'eval_precio'=>$precio,'eval_servicio'=>$servicio,'eval_total'=>$total,'eval_observaciones'=>$_POST['observaciones']??'','eval_evaluador_id'=>Auth::userId()]);

        $this->safeUpdate('cal_proveedores', ['prov_criterio_calidad'=>$calidad,'prov_criterio_entrega'=>$entrega,'prov_criterio_precio'=>$precio,'prov_criterio_servicio'=>$servicio,'prov_calificacion'=>$total,'prov_ultima_evaluacion'=>date('Y-m-d'),'prov_estado'=>$total>=70?'activo':'evaluacion'], 'prov_id=?', [$pid]);

        header('Location: /proveedores?ver='.$pid.'&eval=1'); exit;
    }

    public function formacion(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = (new PlanManager())->getEmpresa($empresaId);
        $formaciones = $this->safeAll("SELECT f.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as usuario_nombre FROM cal_formacion f JOIN sys_usuarios u ON f.form_usuario_id=u.usuario_id WHERE f.form_empresa_id=? ORDER BY f.form_fecha DESC", [$empresaId]);
        $usuarios = $this->safeAll("SELECT usuario_id, usuario_nombre, usuario_apellido FROM sys_usuarios WHERE usuario_activo=1");
        $pageTitle = 'Formación'; ob_start(); require BASE_PATH . '/templates/extras/formacion.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearFormacion(): void {
        $this->safeInsert('cal_formacion', ['form_empresa_id'=>(int)$_POST['empresa_id'],'form_usuario_id'=>(int)$_POST['usuario_id'],'form_tema'=>$_POST['tema'],'form_tipo'=>$_POST['tipo']??'tecnica','form_fecha'=>$_POST['fecha']??date('Y-m-d'),'form_horas'=>(int)($_POST['horas']??0),'form_instructor'=>$_POST['instructor']??'','form_estado'=>$_POST['estado']??'programada']);
        header('Location: /formacion?created=1'); exit;
    }

    public function satisfaccion(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = (new PlanManager())->getEmpresa($empresaId);
        $satisfaccion = $this->safeAll("SELECT s.*, p.proceso_nombre FROM cal_satisfaccion s LEFT JOIN proc_procesos p ON s.sat_proceso_id=p.proceso_id WHERE s.sat_empresa_id=? ORDER BY s.sat_periodo DESC LIMIT 12", [$empresaId]);
        $procesos = $this->safeAll('SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id WHERE m.macro_empresa_id=?',[$empresaId]);
        $pageTitle = 'Satisfacción'; ob_start(); require BASE_PATH . '/templates/extras/satisfaccion.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearSatisfaccion(): void {
        $nps = round((($_POST['promotores'] - $_POST['detractores']) / max($_POST['total']??1, 1)) * 100, 1);
        $this->safeInsert('cal_satisfaccion', ['sat_empresa_id'=>(int)$_POST['empresa_id'],'sat_proceso_id'=>$_POST['proceso_id']?:null,'sat_periodo'=>$_POST['periodo']??date('Y-m'),'sat_nps'=>$nps,'sat_total_encuestas'=>(int)$_POST['total'],'sat_promotores'=>(int)$_POST['promotores'],'sat_neutros'=>(int)$_POST['neutros'],'sat_detractores'=>(int)$_POST['detractores']]);
        header('Location: /satisfaccion?created=1'); exit;
    }
}
