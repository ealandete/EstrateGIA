<?php
declare(strict_types=1);

require_once BASE_PATH . '/lib/SafeQuery.php';

class AdminController {
    use \SafeQuery;
    
    private $core;
    
    public function __construct() { 
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
    }

    // ========================================================================
    // USUARIOS
    // ========================================================================
    public function usuarios(): void {
        $pm = new PlanManager();
        $usuarios = $this->safeAll(
            'SELECT u.*, r.rol_nombre,
                    GROUP_CONCAT(CONCAT(e.empresa_nombre, ":", COALESCE(ue.ue_rol_empresa,"sin asignar")) SEPARATOR " | ") as empresas_asignadas
             FROM sys_usuarios u
             JOIN sys_roles r ON u.usuario_rol_id = r.rol_id
             LEFT JOIN sys_usuario_empresa ue ON u.usuario_id = ue.ue_usuario_id
             LEFT JOIN plan_empresas e ON ue.ue_empresa_id = e.empresa_id AND e.empresa_activo = 1
             WHERE u.usuario_activo = 1
             GROUP BY u.usuario_id
             ORDER BY u.usuario_nombre'
        );
        $roles = $this->safeAll('SELECT * FROM sys_roles WHERE rol_activo = 1');
        $empresasLocal = $pm->getEmpresas();
        $asignaciones = $this->safeAll("SELECT ue.*, u.usuario_nombre, e.empresa_nombre FROM sys_usuario_empresa ue JOIN sys_usuarios u ON ue.ue_usuario_id=u.usuario_id JOIN plan_empresas e ON ue.ue_empresa_id=e.empresa_id");
        $pageTitle = 'Gestión de Usuarios';
        ob_start(); require BASE_PATH . '/templates/admin/usuarios.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearUsuario(): void {
        try {
            $userId = $this->safeInsert('sys_usuarios', [
                'usuario_email' => $_POST['email'],
                'usuario_nombre' => $_POST['nombre'],
                'usuario_apellido' => $_POST['apellido'] ?? '',
                'usuario_password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT),
                'usuario_rol_id' => (int)$_POST['rol_id'],
                'usuario_cargo' => $_POST['cargo'] ?? '',
                'usuario_departamento' => $_POST['departamento'] ?? '',
            ]);
            (EstrateGiaCore::getInstance())->logAction(Auth::userId(), 'crear', 'usuarios', 'usuario', $userId);
            (EstrateGiaCore::getInstance())->audit('crear', 'sys_usuarios', $userId, null,
                ['usuario_email' => $_POST['email'], 'usuario_nombre' => $_POST['nombre'], 'usuario_rol_id' => (int)$_POST['rol_id']],
                'Usuario creado por admin');
            header('Location: /admin/usuarios?created=1');
        } catch (Exception $e) {
            header('Location: /admin/usuarios?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    // ========================================================================
    // ROLES Y PERMISOS
    // ========================================================================
    public function roles(): void {
        $roles = $this->safeAll('SELECT * FROM sys_roles ORDER BY rol_id');
        $modulos = $this->safeAll('SELECT * FROM sys_modulos WHERE modulo_activo = 1 ORDER BY modulo_orden');
        $permisos = $this->safeAll('
            SELECT rp.rp_rol_id, p.permiso_id, p.permiso_accion, m.modulo_nombre
            FROM sys_rol_permisos rp
            JOIN sys_permisos p ON rp.rp_permiso_id = p.permiso_id
            JOIN sys_modulos m ON p.permiso_modulo_id = m.modulo_id
            ORDER BY rp.rp_rol_id, m.modulo_orden
        ');

        // Organizar por rol
        $permisosPorRol = [];
        foreach ($permisos as $p) {
            $permisosPorRol[$p['rp_rol_id']][$p['modulo_nombre']][] = $p['permiso_accion'];
        }

        $pageTitle = 'Roles y Permisos';
        ob_start(); require BASE_PATH . '/templates/admin/roles.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function guardarPermisos(): void {
        $rolId = (int)$_POST['rol_id'];

        // Eliminar permisos existentes del rol
        $this->safeExec('DELETE FROM sys_rol_permisos WHERE rp_rol_id = ?', [$rolId]);

        // Insertar nuevos permisos
        if (!empty($_POST['permisos'])) {
            foreach ($_POST['permisos'] as $permisoId) {
                $this->safeInsert('sys_rol_permisos', [
                    'rp_rol_id' => $rolId,
                    'rp_permiso_id' => (int)$permisoId,
                ]);
            }
        }

        (EstrateGiaCore::getInstance())->logAction(Auth::userId(), 'editar', 'usuarios', 'permisos', $rolId); 
        header('Location: /admin/roles?saved=1');
        exit;
    }

    // ========================================================================
    // CONFIGURACIÓN
    // ========================================================================
    public function config(): void {
        $configs = $this->safeAll('SELECT * FROM sys_configuraciones ORDER BY config_modulo, config_clave');
        $pageTitle = 'Configuración del Sistema';
        ob_start(); require BASE_PATH . '/templates/admin/config.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    // ========================================================================
    // AUDITORÍA (LOG)
    // ========================================================================
    public function auditoria(): void {
        $pagina = (int)($_GET['pagina'] ?? 1);
        $modulo = $_GET['modulo'] ?? null;
        $fechaDesde = $_GET['fecha_desde'] ?? null;
        $fechaHasta = $_GET['fecha_hasta'] ?? null;
        $accion = $_GET['accion'] ?? null;
        $usuarioId = $_GET['usuario_id'] ?? null;
        $busqueda = trim($_GET['q'] ?? '');

        $sql = 'SELECT l.*, CONCAT(u.usuario_nombre, " ", u.usuario_apellido) as usuario_nombre
                FROM sys_logs_sistema l
                LEFT JOIN sys_usuarios u ON l.log_usuario_id = u.usuario_id WHERE 1=1';
        $params = [];
        if ($modulo) { $sql .= ' AND l.log_modulo = ?'; $params[] = $modulo; }
        if ($accion) { $sql .= ' AND l.log_accion = ?'; $params[] = $accion; }
        if ($usuarioId) { $sql .= ' AND l.log_usuario_id = ?'; $params[] = (int)$usuarioId; }
        if ($fechaDesde) { $sql .= ' AND l.created_at >= ?'; $params[] = $fechaDesde . ' 00:00:00'; }
        if ($fechaHasta) { $sql .= ' AND l.created_at <= ?'; $params[] = $fechaHasta . ' 23:59:59'; }
        if ($busqueda) { $sql .= ' AND (l.log_entidad LIKE ? OR l.log_detalle LIKE ? OR l.log_accion LIKE ?)'; $params[]="%$busqueda%"; $params[]="%$busqueda%"; $params[]="%$busqueda%"; }
        $sql .= ' ORDER BY l.created_at DESC';

        // Paginación manual
        $total = (int)$this->safe("SELECT COUNT(*) FROM ($sql) as t", $params);
        $porPagina = 25;
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $offset = ($pagina - 1) * $porPagina;
        
        $sql .= " LIMIT $porPagina OFFSET $offset";
        $logs = $this->safeAll($sql, $params);
        
        $result = [
            'data' => $logs,
            'pagina' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_registros' => $total
        ];
        
        $modulos = $this->safeAll('SELECT DISTINCT log_modulo FROM sys_logs_sistema WHERE log_modulo IS NOT NULL ORDER BY log_modulo');
        $acciones = $this->safeAll('SELECT DISTINCT log_accion FROM sys_logs_sistema WHERE log_accion IS NOT NULL ORDER BY log_accion');
        $usuarios = $this->safeAll('SELECT usuario_id, usuario_nombre, usuario_apellido FROM sys_usuarios WHERE usuario_activo=1 ORDER BY usuario_nombre');

        $pageTitle = 'Auditoría del Sistema';
        ob_start(); require BASE_PATH . '/templates/admin/auditoria.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }
}
