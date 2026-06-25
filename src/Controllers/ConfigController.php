<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class ConfigController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $pm = new PlanManager();
        $empresas = $pm->getEmpresas();
        $sectores = (new DocManager())->getSectores();
        $planes = $pm->getPlanes();
        $roles = $this->safeAll('SELECT * FROM sys_roles WHERE rol_activo=1');
        $usuarios = $this->safeAll("SELECT u.*, r.rol_nombre FROM sys_usuarios u JOIN sys_roles r ON u.usuario_rol_id=r.rol_id ORDER BY u.usuario_nombre");

        // Asignaciones usuario-empresa
        $asignaciones = $this->safeAll("SELECT ue.*, u.usuario_nombre, e.empresa_nombre FROM sys_usuario_empresa ue JOIN sys_usuarios u ON ue.ue_usuario_id=u.usuario_id JOIN plan_empresas e ON ue.ue_empresa_id=e.empresa_id");

        $pageTitle = 'Configuración';
        ob_start(); require BASE_PATH . '/templates/admin/config.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearEmpresa(): void {
        $pm = new PlanManager();
        $pm->createEmpresa([
            'empresa_nombre' => $_POST['nombre'],
            'empresa_razon_social' => $_POST['razon_social'] ?? '',
            'empresa_nit' => $_POST['nit'] ?? '',
            'empresa_sector_id' => $_POST['sector_id'] ?? null,
            'empresa_direccion' => $_POST['direccion'] ?? '',
            'empresa_telefono' => $_POST['telefono'] ?? '',
            'empresa_email' => $_POST['email'] ?? '',
            'usuario_id' => Auth::userId(),
        ]);
        header('Location: /admin/config?empresa_ok=1'); exit;
    }

    public function editarEmpresa(): void {
        $pm = new PlanManager();
        $pm->updateEmpresa((int)$_POST['empresa_id'], [
            'empresa_nombre' => $_POST['nombre'],
            'empresa_razon_social' => $_POST['razon_social'] ?? '',
            'empresa_nit' => $_POST['nit'] ?? '',
            'empresa_sector_id' => $_POST['sector_id'] ?? null,
            'empresa_direccion' => $_POST['direccion'] ?? '',
            'empresa_telefono' => $_POST['telefono'] ?? '',
            'empresa_email' => $_POST['email'] ?? '',
        ]);
        header('Location: /admin/config?empresa_ok=1'); exit;
    }

    public function asignarUsuarioEmpresa(): void {
        $this->safeExec('INSERT IGNORE INTO sys_usuario_empresa VALUES (?, ?, ?)', [
            (int)$_POST['usuario_id'],
            (int)$_POST['empresa_id'],
            $_POST['rol_empresa'] ?? 'colaborador',
        ]);
        header('Location: /admin/config?asignado=1');
        exit;
    }

    public function guardarPersonalizacion(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $this->safeExec("UPDATE plan_empresas SET empresa_tipo=? WHERE empresa_id=?", [$_POST['empresa_tipo']??'general', $empresaId]);
        $configs = [
            'empresa_color_primario' => $_POST['color_primario'] ?? '#1a73e8',
            'empresa_nombre_corto' => $_POST['nombre_corto'] ?? '',
            'empresa_formato_fecha' => $_POST['formato_fecha'] ?? 'd/m/Y',
            'empresa_moneda' => $_POST['moneda'] ?? 'COP',
            'empresa_logo_url' => $_POST['logo_url'] ?? '',
        ];
        foreach ($configs as $k => $v) {
            $this->safeExec("INSERT INTO sys_configuraciones (config_clave, config_valor, config_descripcion) VALUES (?,?,'') ON DUPLICATE KEY UPDATE config_valor=?", [$k, $v, $v]);
        }
        header('Location: /admin/config?ok=1');
        exit;
    }
}
