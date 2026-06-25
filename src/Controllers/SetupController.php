<?php
declare(strict_types=1);

require_once BASE_PATH . '/lib/SafeQuery.php';

class SetupController {
    use \SafeQuery;
    
    private $core;
    
    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }
    
    /**
     * Wizard de configuración inicial
     */
    public function index(): void {
        // Verificar si ya hay configuración
        $adminRoleIds = $this->safeAll("SELECT rol_id FROM sys_roles WHERE rol_nombre LIKE '%ADMIN%' OR rol_id = 1");
        $adminIds = array_column($adminRoleIds, 'rol_id');
        $placeholders = implode(',', array_fill(0, count($adminIds), '?'));
        $yaConfigurado = count($adminIds) > 0 && $this->safe("SELECT COUNT(*) FROM sys_usuarios WHERE usuario_rol_id IN ($placeholders)", $adminIds) > 0;
        
        if ($yaConfigurado && !isset($_GET['force'])) {
            header('Location: /');
            exit;
        }
        
        $paso = (int)($_GET['paso'] ?? 1);
        $error = $_GET['error'] ?? '';
        $exito = $_GET['exito'] ?? '';
        
        $pageTitle = 'Configuración Inicial — EstrateGIA';
        ob_start();
        require BASE_PATH . '/templates/setup/index.php';
        $content = ob_get_clean();
        
        // Layout especial sin sidebar para setup
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($pageTitle) . '</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .setup-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .setup-card { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .setup-header { text-align: center; margin-bottom: 30px; }
        .setup-header h1 { color: #1a73e8; font-size: 2rem; margin-bottom: 10px; }
        .setup-steps { display: flex; justify-content: center; gap: 10px; margin: 30px 0; }
        .setup-step { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .setup-step.active { background: #1a73e8; color: white; }
        .setup-step.completed { background: #28a745; color: white; }
        .setup-step.pending { background: #e0e0e0; color: #666; }
        .check-item { padding: 10px; margin: 5px 0; border-radius: 8px; display: flex; align-items: center; gap: 10px; }
        .check-ok { background: #d4edda; color: #155724; }
        .check-fail { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="setup-container">
        ' . $content . '
    </div>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
        exit;
    }
    
    /**
     * Paso 1: Verificar requisitos del sistema
     */
    public function requisitos(): void {
        $checks = [
            'PHP >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'JSON' => extension_loaded('json'),
            'OpenSSL' => extension_loaded('openssl'),
            'Mbstring' => extension_loaded('mbstring'),
            'Directorio logs/ escribible' => is_writable(BASE_PATH . '/logs'),
            'Directorio uploads/ escribible' => is_writable(BASE_PATH . '/uploads'),
            'Conexión BD' => $this->verificarConexionBD(),
        ];
        
        $todosOk = !in_array(false, $checks, true);
        
        $pageTitle = 'Paso 1: Requisitos del Sistema';
        ob_start();
        require BASE_PATH . '/templates/setup/requisitos.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }
    
    /**
     * Paso 2: Configurar empresa principal
     */
    public function empresa(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nombre = trim($_POST['nombre'] ?? '');
                $nit = trim($_POST['nit'] ?? '');
                $sector = trim($_POST['sector'] ?? '');
                
                if (empty($nombre)) {
                    header('Location: /setup?error=nombre_requerido');
                    exit;
                }
                
                // Verificar si ya existe
                $existe = $this->safe("SELECT COUNT(*) FROM plan_empresas WHERE empresa_nombre = ?", [$nombre]);
                
                if ($existe > 0) {
                    // Actualizar
                    $this->safeExec("UPDATE plan_empresas SET empresa_nit = ?, empresa_sector = ? WHERE empresa_nombre = ?", 
                        [$nit, $sector, $nombre]);
                } else {
                    // Insertar
                    $this->safeInsert('plan_empresas', [
                        'empresa_nombre' => $nombre,
                        'empresa_nit' => $nit,
                        'empresa_sector' => $sector,
                        'empresa_activo' => 1
                    ]);
                }
                
                header('Location: /setup/usuario');
                exit;
            } catch (Exception $e) {
                header('Location: /setup/empresa?error=' . urlencode($e->getMessage()));
                exit;
            }
        }
        
        $empresa = $this->safeOne("SELECT * FROM plan_empresas LIMIT 1");
        
        $pageTitle = 'Paso 2: Configurar Empresa';
        ob_start();
        require BASE_PATH . '/templates/setup/empresa.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }
    
    /**
     * Paso 3: Crear usuario administrador
     */
    public function usuario(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nombre = trim($_POST['nombre'] ?? '');
                $apellido = trim($_POST['apellido'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmar = $_POST['confirmar'] ?? '';
                
                if (empty($nombre) || empty($email) || empty($password)) {
                    header('Location: /setup/usuario?error=campos_requeridos');
                    exit;
                }
                
                if ($password !== $confirmar) {
                    header('Location: /setup/usuario?error=passwords_no_coinciden');
                    exit;
                }
                
                if (strlen($password) < 8) {
                    header('Location: /setup/usuario?error=password_corto');
                    exit;
                }
                
                // Verificar si ya existe
                $existe = $this->safe("SELECT COUNT(*) FROM sys_usuarios WHERE usuario_email = ?", [$email]);
                
                if ($existe > 0) {
                    // Actualizar
                    $this->safeExec("UPDATE sys_usuarios SET usuario_nombre = ?, usuario_apellido = ?, usuario_password_hash = ? WHERE usuario_email = ?",
                        [$nombre, $apellido, password_hash($password, PASSWORD_BCRYPT), $email]);
                } else {
                    // Insertar
                    $this->safeInsert('sys_usuarios', [
                        'usuario_nombre' => $nombre,
                        'usuario_apellido' => $apellido,
                        'usuario_email' => $email,
                        'usuario_password_hash' => password_hash($password, PASSWORD_BCRYPT),
                        'usuario_rol_id' => $this->getAdminRoleId(),
                        'usuario_activo' => 1
                    ]);
                }
                
                header('Location: /setup/finalizar');
                exit;
            } catch (Exception $e) {
                header('Location: /setup/usuario?error=' . urlencode($e->getMessage()));
                exit;
            }
        }
        
        $usuario = $this->getExistingAdminUser();
        
        $pageTitle = 'Paso 3: Crear Administrador';
        ob_start();
        require BASE_PATH . '/templates/setup/usuario.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }
    
    /**
     * Paso 4: Finalizar configuración
     */
    public function finalizar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Crear plan estratégico inicial
            $empresaId = (int)$this->safe("SELECT empresa_id FROM plan_empresas LIMIT 1");
            
            if ($empresaId > 0) {
                $existePlan = $this->safe("SELECT COUNT(*) FROM plan_planes WHERE plan_empresa_id = ?", [$empresaId]);
                
                if ($existePlan === 0) {
                    $this->safeInsert('plan_planes', [
                        'plan_empresa_id' => $empresaId,
                        'plan_nombre' => 'Plan Estratégico ' . date('Y'),
                        'plan_metodologia' => 'BSC',
                        'plan_periodo_inicio' => date('Y') . '-01-01',
                        'plan_periodo_fin' => date('Y') . '-12-31',
                        'plan_estado' => 'borrador'
                    ]);
                }
            }
            
            // Redirigir al login
            header('Location: /login.php?setup=completado');
            exit;
        }
        
        $resumen = [
            'empresa' => $this->safeOne("SELECT * FROM plan_empresas LIMIT 1"),
            'usuario' => $this->safeOne("SELECT * FROM sys_usuarios WHERE usuario_rol_id IN (1, 9, 10) LIMIT 1"),
            'planes' => $this->safe("SELECT COUNT(*) FROM plan_planes"),
        ];
        
        $pageTitle = 'Paso 4: Finalizar Configuración';
        ob_start();
        require BASE_PATH . '/templates/setup/finalizar.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }
    
    /**
     * Verificar conexión a BD
     */
    private function verificarConexionBD(): bool {
        try {
            $this->safe("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAdminRoleId(): int {
        $rol = $this->safeOne("SELECT rol_id FROM sys_roles WHERE rol_nombre LIKE '%SUPER%ADMIN%' OR rol_id = 1 ORDER BY rol_id ASC LIMIT 1");
        return (int)($rol['rol_id'] ?? 9);
    }

    private function getExistingAdminUser(): ?array {
        $roles = $this->safeAll("SELECT rol_id FROM sys_roles WHERE rol_nombre LIKE '%ADMIN%' OR rol_id = 1");
        $ids = array_column($roles, 'rol_id');
        if (empty($ids)) return null;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return $this->safeOne("SELECT * FROM sys_usuarios WHERE usuario_rol_id IN ($placeholders) LIMIT 1", $ids);
    }
}
