<?php
/**
 * EstrateGIA v1.0 - Core del Sistema de Gestión de Planeación Estratégica
 * Clase principal con patrón Singleton, conexión PDO y utilidades base.
 *
 * Sigue la misma arquitectura que GanaderoCore.php del sistema agropecuario.
 */

class EstrateGiaCore {

    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct() {
        $this->config = [
            'db_host'     => 'localhost',
            'db_name'     => 'estrategia_v1',
            'db_user'     => 'emilio',
            'db_pass'     => 's1gma',
            'db_charset'  => 'utf8mb4',
            'timezone'    => 'America/Bogota',
            'debug_mode'  => false,
            'log_queries' => false,
            'jwt_secret'  => 'Estr@teG1A_2025_S3cr3t_K3y!',
            'jwt_expire'  => 28800,
            'encrypt_key' => '3str4t3G1A_AES_256_C0R3',
            'app_name'    => 'EstrateGIA',
            'app_version' => '1.0.0',
            'api_base_url'=> '/api',
            'upload_dir'  => __DIR__ . '/../uploads/',
            'log_dir'     => __DIR__ . '/../logs/',
            'cache_ttl'   => 3600
        ];

        date_default_timezone_set($this->config['timezone']);
        $this->conectarDB();
        $this->inicializarDirectorios();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function conectarDB(): void {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['db_host'],
                $this->config['db_name'],
                $this->config['db_charset']
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES    => false,
                1002  => "SET NAMES {$this->config['db_charset']}", // PDO::MYSQL_ATTR_INIT_COMMAND / Pdo\Mysql::ATTR_INIT_COMMAND
                PDO::ATTR_PERSISTENT          => false
            ];

            $this->pdo = new PDO(
                $dsn,
                $this->config['db_user'],
                $this->config['db_pass'],
                $options
            );
        } catch (PDOException $e) {
            $this->logError('DB Connection', $e->getMessage());
            throw new RuntimeException('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    private function inicializarDirectorios(): void {
        $dirs = [$this->config['upload_dir'], $this->config['log_dir']];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    // ========================================================================
    // Getters
    // ========================================================================

    public function getPDO(): PDO {
        return $this->pdo;
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function getConfigValue(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    // ========================================================================
    // Operaciones CRUD Genéricas
    // ========================================================================

    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $this->logQuery($sql, $data);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "{$col} = :set_{$col}";
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(':set_' . $key, $value);
        }
        foreach ($whereParams as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $this->logQuery($sql, array_merge($data, $whereParams));
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $this->logQuery($sql, $params);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $this->logQuery($sql, $params);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $this->logQuery($sql, $params);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0) {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        return $stmt->fetchColumn($column);
    }

    public function execute(string $sql, array $params = []): int {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $this->logQuery($sql, $params);
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ========================================================================
    // Autenticación y JWT
    // ========================================================================

    // ===== AUTH (delegado a AuthService) =====
    public function authenticateUser(string $email, string $password): ?array {
        require_once __DIR__ . '/AuthService.php';
        return (new AuthService())->authenticateUser($email, $password);
    }

    public function validateJWT(string $token): ?array {
        require_once __DIR__ . '/AuthService.php';
        return (new AuthService())->validateJWT($token);
    }

    public function userHasPermission(int $userId, string $module, string $action): bool {
        require_once __DIR__ . '/AuthService.php';
        return (new AuthService())->userHasPermission($userId, $module, $action);
    }

    public function getUserPermissions(int $userId): array {
        require_once __DIR__ . '/AuthService.php';
        return (new AuthService())->getUserPermissions($userId);
    }

    public function canAccess(string $modulo): bool {
        require_once __DIR__ . '/AuthService.php';
        return (new AuthService())->canAccess($modulo);
    }

    public function requires2FA(array $user): bool {
        require_once __DIR__ . '/AuthService.php';
        return (new AuthService())->requires2FA($user);
    }

    public function verify2FACode(array $user, string $code): bool {
        require_once __DIR__ . '/AuthService.php';
        return (new AuthService())->verify2FACode($user, $code);
    }

    // ========================================================================
    // Auditoria con JSON snapshots (22_UNIFICACION_TRANSVERSAL.md §2)
    // ========================================================================

    public function audit(string $accion, ?string $tabla = null, ?int $registroId = null,
                          ?array $datosAnteriores = null, ?array $datosNuevos = null,
                          ?string $descripcion = null): void {
        try {
            $userId = $_SESSION['auth_user']['usuario_id'] ?? null;
            $userName = $_SESSION['auth_user']['usuario_nombre'] ?? null;
            $empresaId = (int)($_COOKIE['empresa_activa'] ?? 1);

            $this->execute(
                "INSERT INTO auditoria (id_empresa, id_usuario, usuario_nombre, accion, tabla_afectada,
                 registro_id, datos_anteriores, datos_nuevos, ip_origen, user_agent, descripcion)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $empresaId,
                    $userId,
                    $userName,
                    $accion,
                    $tabla,
                    $registroId,
                    $datosAnteriores ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE) : null,
                    $datosNuevos ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $descripcion
                ]
            );
        } catch (\Exception $e) {}
    }

    // ========================================================================
    // Control de Acceso
    // ========================================================================

    // ========================================================================
    // Notificaciones
    // ========================================================================

    public function sendNotification(int $userId, string $title, string $message,
                                      string $type = 'info', ?string $url = null,
                                      ?string $entity = null, ?int $entityId = null): int {
        return $this->insert('sys_notificaciones', [
            'notif_usuario_id'  => $userId,
            'notif_titulo'      => $title,
            'notif_mensaje'     => $message,
            'notif_tipo'        => $type,
            'notif_url'         => $url,
            'notif_entidad'     => $entity,
            'notif_entidad_id'  => $entityId
        ]);
    }

    public function getUnreadNotifications(int $userId, int $limit = 20): array {
        return $this->fetchAll(
            'SELECT * FROM sys_notificaciones
             WHERE notif_usuario_id = :uid AND notif_leida = 0
             ORDER BY created_at DESC LIMIT :limit',
            ['uid' => $userId, 'limit' => $limit]
        );
    }

    public function markNotificationRead(int $notificationId): void {
        $this->execute(
            'UPDATE sys_notificaciones SET notif_leida = 1 WHERE notif_id = :id',
            ['id' => $notificationId]
        );
    }

    // ========================================================================
    // Logging del Sistema
    // ========================================================================

    public function logAction(?int $userId, string $action, string $module,
                               string $entity, ?int $entityId, ?array $details = null): void {
        $this->insert('sys_logs_sistema', [
            'log_usuario_id' => $userId,
            'log_accion'     => $action,
            'log_modulo'     => $module,
            'log_entidad'    => $entity,
            'log_entidad_id' => $entityId,
            'log_detalle'    => $details ? json_encode($details) : null,
            'log_ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'log_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    private function logQuery(string $sql, array $params): void {
        if ($this->config['log_queries']) {
            $log = sprintf("[%s] %s | Params: %s\n", date('Y-m-d H:i:s'), $sql, json_encode($params));
            file_put_contents($this->config['log_dir'] . 'queries.log', $log, FILE_APPEND);
        }
    }

    public function logError(string $context, string $message): void {
        $log = sprintf("[%s] [ERROR] [%s] %s\n", date('Y-m-d H:i:s'), $context, $message);
        file_put_contents($this->config['log_dir'] . 'errors.log', $log, FILE_APPEND);
        if ($this->config['debug_mode']) {
            error_log("EstrateGIA Error [{$context}]: {$message}");
        }
    }

    // ========================================================================
    // Utilidades
    // ========================================================================

    public function apiResponse(bool $success, $data = null, string $message = '', int $httpCode = 200): array {
        http_response_code($httpCode);
        return [
            'success'   => $success,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date('c')
        ];
    }

    public function apiError(string $code, string $message, array $details = [], int $httpCode = 400): array {
        return $this->apiResponse(false, [
            'error' => [
                'code'    => $code,
                'message' => $message,
                'details' => $details
            ]
        ], $message, $httpCode);
    }

    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public function validateRequired(array $data, array $fields): array {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $errors[$field] = "El campo '{$field}' es requerido.";
            }
        }
        return $errors;
    }

    public function paginate(string $baseQuery, array $params, int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;

        $countSql = preg_replace('/SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $baseQuery, 1);
        $total = $this->fetchColumn($countSql, $params);

        $data = $this->fetchAll("{$baseQuery} LIMIT :limit OFFSET :offset", array_merge($params, [
            'limit'  => $perPage,
            'offset' => $offset
        ]));

        return [
            'data'         => $data,
            'pagination'   => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => (int) $total,
                'total_pages' => ceil($total / $perPage),
                'has_more'    => ($offset + $perPage) < $total
            ]
        ];
    }

    public function encryptData(string $data): string {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->config['encrypt_key'], 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    public function decryptData(string $encryptedData): string {
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        return openssl_decrypt(substr($data, 16), 'AES-256-CBC', $this->config['encrypt_key'], 0, $iv) ?: '';
    }

    // base64 helpers moved to AuthService (kept for backward compat if needed)
    private function base64UrlEncode(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
    private function base64UrlDecode(string $data): string { return base64_decode(strtr($data, '-_', '+/')); }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}
