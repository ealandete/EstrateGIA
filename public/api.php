<?php
/**
 * EstrateGIA - API REST
 * Endpoint: /api/{recurso}
 * Autenticación: JWT Bearer token
 */
require_once __DIR__ . '/../lib/EstrateGiaCore.php';
require_once __DIR__ . '/../src/Auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ===== ENDPOINTS PUBLICOS (sin autenticacion) =====

// Health check publico
if ($uri === '/api/health') {
    $db = 'error'; $tables = 0; $fks = 0;
    try {
        $c = EstrateGiaCore::getInstance();
        $t = $c->fetchOne("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()");
        $tables = (int)($t['cnt'] ?? 0);
        $f = $c->fetchOne("SELECT COUNT(*) as cnt FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
        $fks = (int)($f['cnt'] ?? 0);
        $db = 'ok';
    } catch (\Throwable $e) { $db = $e->getMessage(); }
    echo json_encode(['status'=>'ok','app'=>'EstrateGIA','version'=>'1.0','db_tables'=>$tables,'timestamp'=>date('c')]);
    exit;
}

// JS error catcher (publico, sin autenticacion)
if ($uri === '/api/error/report' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        $c = EstrateGiaCore::getInstance();
        $c->execute(
            "INSERT INTO error_log (tipo, mensaje, archivo, linea, url, user_agent, created_at)
             VALUES ('JS_ERROR',?,?,?,?,?,NOW())",
            [$data['message']??'Unknown', $data['source']??'client', $data['lineno']??0, $data['url']??'', $data['userAgent']??'']
        );
    } catch (\Throwable $e) {}
    echo json_encode(['status'=>'ok']);
    exit;
}

// ===== AUTENTICACION JWT (para el resto de endpoints) =====
$token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
$core = EstrateGiaCore::getInstance();
$payload = $core->validateJWT($token);
if (!$payload && !isset($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
$userId = $payload['sub'] ?? ($_SESSION['auth_user']['usuario_id'] ?? 0);
$empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));

// Router API
$response = null;
$code = 200;

if (preg_match('#^/api/peligros$#', $uri) && $method === 'GET') {
    require_once __DIR__ . '/../lib/SSTManager.php';
    $response = (new SSTManager())->getPeligros($empresaId);
}
elseif (preg_match('#^/api/peligros/(\d+)$#', $uri, $m) && $method === 'GET') {
    require_once __DIR__ . '/../lib/SSTManager.php';
    $r = (new SSTManager())->getPeligro((int)$m[1]);
    $response = $r ?? ['error'=>'No encontrado']; if(!$r) $code=404;
}
elseif (preg_match('#^/api/incidentes$#', $uri) && $method === 'GET') {
    require_once __DIR__ . '/../lib/SSTManager.php';
    $response = (new SSTManager())->getIncidentes($empresaId, (int)($_GET['anio']??date('Y')));
}
elseif (preg_match('#^/api/indicadores/sst$#', $uri) && $method === 'GET') {
    require_once __DIR__ . '/../lib/SSTManager.php';
    $response = (new SSTManager())->getIndicadores($empresaId);
}
elseif (preg_match('#^/api/indicadores/ambiental$#', $uri) && $method === 'GET') {
    require_once __DIR__ . '/../lib/AmbientalManager.php';
    $response = (new AmbientalManager())->getIndicadores($empresaId);
}
elseif (preg_match('#^/api/proveedores$#', $uri) && $method === 'GET') {
    require_once __DIR__ . '/../lib/ProveedoresManager.php';
    $response = (new ProveedoresManager())->getProveedores($empresaId);
}
elseif (preg_match('#^/api/registros/ambiental$#', $uri) && $method === 'GET') {
    require_once __DIR__ . '/../lib/AmbientalManager.php';
    $response = (new AmbientalManager())->getRegistros($empresaId, (int)($_GET['anio']??date('Y')));
}


// ===== TICKETS ROUTES (22_UNIFICACION_TRANSVERSAL.md §3) =====

// GET /api/tickets/resumen
elseif (preg_match('#^/api/tickets/resumen$#', $uri) && $method === 'GET') {
    $ahora = date('Y-m-d H:i:s');
    $mesInicio = date('Y-m-01 00:00:00');

    $abiertos = (int)($core->fetchOne(
        "SELECT COUNT(*) as cnt FROM soporte_tickets WHERE estado IN ('ABIERTO','ESCALADO_N2','ESCALADO_N3')"
    )['cnt'] ?? 0);

    $en_progreso = (int)($core->fetchOne(
        "SELECT COUNT(*) as cnt FROM soporte_tickets WHERE estado = 'EN_PROGRESO'"
    )['cnt'] ?? 0);

    $resueltos_mes = (int)($core->fetchOne(
        "SELECT COUNT(*) as cnt FROM soporte_tickets WHERE estado IN ('RESUELTO','CERRADO') AND updated_at >= ?",
        [$mesInicio]
    )['cnt'] ?? 0);

    $tiempo_medio = $core->fetchOne(
        "SELECT AVG(tiempo_resolucion_min) as avg FROM soporte_tickets WHERE tiempo_resolucion_min IS NOT NULL"
    )['avg'] ?? 0;

    $criticos_abiertos = (int)($core->fetchOne(
        "SELECT COUNT(*) as cnt FROM soporte_tickets WHERE prioridad = 'CRITICA' AND estado IN ('ABIERTO','EN_PROGRESO','ESCALADO_N2','ESCALADO_N3')"
    )['cnt'] ?? 0);

    $total_resueltos = (int)($core->fetchOne(
        "SELECT COUNT(*) as cnt FROM soporte_tickets WHERE estado IN ('RESUELTO','CERRADO')"
    )['cnt'] ?? 0);

    $total_vencidos = (int)($core->fetchOne(
        "SELECT COUNT(*) as cnt FROM soporte_tickets WHERE sla_vencimiento IS NOT NULL AND sla_vencimiento < ? AND estado IN ('ABIERTO','EN_PROGRESO')",
        [$ahora]
    )['cnt'] ?? 0);

    $sla_cumplidos = max(0, $total_resueltos - $total_vencidos);
    $sla_cumplimiento_pct = round(($sla_cumplidos / max(1, $total_resueltos)) * 100, 1);

    $response = [
        'abiertos' => $abiertos,
        'en_progreso' => $en_progreso,
        'resueltos_mes' => $resueltos_mes,
        'sla_cumplimiento_pct' => $sla_cumplimiento_pct,
        'tiempo_medio_min' => round((float)$tiempo_medio, 1),
        'criticos_abiertos' => $criticos_abiertos,
    ];
}

// GET /api/tickets?estado=&prioridad=&modulo=&page=&limit=
elseif (preg_match('#^/api/tickets$#', $uri) && $method === 'GET') {
    $estado    = $_GET['estado'] ?? null;
    $prioridad = $_GET['prioridad'] ?? null;
    $modulo    = $_GET['modulo'] ?? null;
    $page      = max(1, (int)($_GET['page'] ?? 1));
    $limit     = min(max(1, (int)($_GET['limit'] ?? 20)), 100);

    $where = "WHERE 1=1";
    $params = [];
    if ($estado)    { $where .= " AND estado = ?"; $params[] = $estado; }
    if ($prioridad) { $where .= " AND prioridad = ?"; $params[] = $prioridad; }
    if ($modulo)    { $where .= " AND modulo_afectado = ?"; $params[] = $modulo; }

    $total = (int)($core->fetchOne("SELECT COUNT(*) as cnt FROM soporte_tickets $where", $params)['cnt'] ?? 0);
    $pages = max(1, (int)ceil($total / $limit));
    $offset = ($page - 1) * $limit;

    $data = $core->fetchAll(
        "SELECT * FROM soporte_tickets $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
        $params
    );

    $response = [
        'data'  => $data ?: [],
        'total' => $total,
        'page'  => $page,
        'pages' => $pages,
    ];
}

// ===== BACKUP ROUTES (22_UNIFICACION_TRANSVERSAL.md §4) =====
// POST /api/backup/log — registrar ejecucion de backup
elseif ($uri === '/api/backup/log' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $stmt = $core->getPDO()->prepare(
        "INSERT INTO backup_log (tipo, archivo, tamano_bytes, sha256, estado, mensaje, ejecutado_por, duracion_seg, created_at)
         VALUES (?,?,?,?,?,?,?,?,NOW())"
    );
    $stmt->execute([
        $input['tipo'] ?? 'COMPLETO',
        $input['archivo'] ?? null,
        (int)($input['tamano_bytes'] ?? $input['tamano'] ?? 0),
        $input['sha256'] ?? null,
        $input['estado'] ?? 'OK',
        $input['mensaje'] ?? null,
        $input['ejecutado_por'] ?? 'API',
        (int)($input['duracion_seg'] ?? 0)
    ]);
    $response = ['status' => 'ok', 'id' => (int)$core->getPDO()->lastInsertId()];
}

// GET /api/backup/ultimo — ultimo registro de backup
elseif ($uri === '/api/backup/ultimo' && $method === 'GET') {
    $response = $core->fetchOne(
        "SELECT * FROM backup_log ORDER BY created_at DESC LIMIT 1"
    ) ?: ['message' => 'No hay registros de backup'];
}

// GET /api/backup/log?limit=10 — historial reciente
elseif ($uri === '/api/backup/log' && $method === 'GET') {
    $limit = min((int)($_GET['limit'] ?? 10), 100);
    $response = $core->fetchAll(
        "SELECT * FROM backup_log ORDER BY created_at DESC LIMIT :limit",
        ['limit' => $limit]
    );
}

// POST /api/backup/ejecutar — disparar backup manual (SUPER_ADMIN)
elseif ($uri === '/api/backup/ejecutar' && $method === 'POST') {
    $rolNombre = '';
    if ($payload) {
        $user = $core->fetchOne("SELECT u.*, r.rol_nombre FROM sys_usuarios u JOIN sys_roles r ON u.usuario_rol_id = r.rol_id WHERE u.usuario_id = ?", [$userId]);
        $rolNombre = $user['rol_nombre'] ?? '';
    } elseif (isset($_SESSION['auth_user'])) {
        $rolNombre = $_SESSION['auth_user']['rol_nombre'] ?? '';
    }
    if ($rolNombre !== 'SUPER_ADMIN') {
        $code = 403;
        $response = ['error' => 'Solo SUPER_ADMIN puede ejecutar backups manualmente'];
    } else {
        $script = BASE_PATH . '/scripts/backup.sh';
        if (!file_exists($script)) {
            $code = 500;
            $response = ['error' => 'Script backup.sh no encontrado en ' . $script];
        } else {
            $output = [];
            $exitCode = 0;
            exec("bash " . escapeshellarg($script) . " 2>&1", $output, $exitCode);
            $response = [
                'status' => $exitCode === 0 ? 'ok' : 'error',
                'exit_code' => $exitCode,
                'output' => $output
            ];
        }
    }
}

// -- DEMO: Crear empresa demo (publico, sin auth) --
if ($uri === '/api/demo/crear' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $nombreEmpresa = $data['nombre_empresa'] ?? 'Demo ' . date('YmdHis');
    $email = $data['email'] ?? 'admin@' . preg_replace('/[^a-z0-9]/', '', strtolower($nombreEmpresa)) . '.com';
    $nit = $data['nit'] ?? '900' . random_int(100000, 999999);

    try {
        $core->getPDO()->beginTransaction();

        $core->execute(
            "INSERT INTO sys_empresas (empresa_nit, empresa_dv, empresa_razon_social, empresa_estado, empresa_email) VALUES (?, '0', ?, 'ACTIVO', ?)",
            [$nit, $nombreEmpresa, $email]
        );
        $empresaId = $core->getPDO()->lastInsertId();

        $passwordHash = password_hash('Demo123!', PASSWORD_BCRYPT);
        $core->execute(
            "INSERT INTO sys_usuarios (empresa_id, usuario_nombre, usuario_apellido, usuario_email, usuario_password, usuario_rol_id, usuario_rol_nombre, usuario_activo) VALUES (?, 'Admin', 'Demo', ?, ?, 1, 'SUPER_ADMIN', 1)",
            [$empresaId, $email, $passwordHash]
        );

        $licenseToken = bin2hex(random_bytes(32));
        $modulos = json_encode(['planeacion','workbench','indicadores','evaluacion','procesos','calidad','sst','ambiental','nc','documentos','proveedores','crm','ia','soporte','financiero','admin','config']);
        $core->execute(
            "INSERT INTO licencias (id_empresa, app, plan, usuarios_max, modulos_activos, fecha_inicio, fecha_fin, activa, token_licencia) VALUES (?, 'EstrateGIA', 'AVANZADO', 999, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 1, ?)",
            [$empresaId, $modulos, $licenseToken]
        );

        $core->getPDO()->commit();

        $result = [
            'success' => true,
            'empresa_id' => (int)$empresaId,
            'nit' => $nit,
            'razon_social' => $nombreEmpresa,
            'admin_email' => $email,
            'admin_password' => 'Demo123!',
            'trial_dias' => 15,
            'trial_hasta' => date('Y-m-d', strtotime('+15 days')),
            'app_url' => 'http://localhost:90',
            'mensaje' => 'Empresa demo creada.',
        ];

        error_log("[DEMO] EstrateGIA — Nueva empresa: {$nombreEmpresa} (NIT: {$nit}) — Admin: {$email}");
        echo json_encode(['notification_simulated' => true, 'to' => $email, 'subject' => 'Bienvenido a EstrateGIA - Demo 15 dias', 'body' => "Hola Admin,\n\nTu empresa demo '{$nombreEmpresa}' ha sido creada.\n\nAccede en: http://localhost:90\nUsuario: {$email}\nPassword: Demo123!\n\nTienes 15 dias de prueba gratuita.\n\nEquipo EstrateGIA"]);

        http_response_code(201);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        $core->getPDO()->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear demo: ' . $e->getMessage()]);
        exit;
    }
}

else {
    $code = 404;
    $response = ['error'=>'Endpoint no encontrado','available'=>['GET /api/health','POST /api/error/report','GET /api/tickets/resumen','GET /api/tickets','GET /api/peligros','GET /api/incidentes','GET /api/indicadores/sst','GET /api/indicadores/ambiental','GET /api/proveedores','GET /api/registros/ambiental','GET /api/backup/ultimo','GET /api/backup/log','POST /api/backup/ejecutar']];
}

http_response_code($code);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
