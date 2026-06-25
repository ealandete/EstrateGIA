<?php

/**
 * AuthService — Autenticación y autorización
 * Extraído de EstrateGiaCore (refactor v2.1)
 * v2.2: Unificado con patron GMD360-IPS (22_UNIFICACION_TRANSVERSAL.md)
 *   - Login lockout: 5 intentos → 15 min bloqueo
 *   - 2FA TOTP verify para SUPER_ADMIN/ADMIN
 *   - canAccess(modulo) via roles_permisos_detalle
 */
class AuthService {

    private EstrateGiaCore $core;
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function authenticateUser(string $email, string $password): ?array {
        $user = $this->core->fetchOne(
            'SELECT u.*, r.rol_nombre
             FROM sys_usuarios u
             LEFT JOIN sys_roles r ON u.usuario_rol_id = r.rol_id
             WHERE u.usuario_email = :email AND u.usuario_activo = 1',
            ['email' => $email]
        );

        if (!$user || !password_verify($password, $user['usuario_password_hash'] ?? '')) {
            $this->recordFailedAttempt($email);
            $this->logAudit(null, 'LOGIN_FAIL', 'usuarios', $user['usuario_id'] ?? null,
                "Intento fallido para: {$email}");
            return null;
        }

        if ($this->isLockedOut($email, $user)) {
            $this->logAudit($user['usuario_id'], 'LOGIN_BLOCKED', 'usuarios', $user['usuario_id'],
                "Bloqueo activo para: {$email}");
            return null;
        }

        $this->clearLoginAttempts($email);
        $token = $this->generateJWT($user);
        $user['token'] = $token;
        $this->core->update('sys_usuarios',
            ['usuario_ultimo_acceso' => date('Y-m-d H:i:s')],
            'usuario_id = :id', ['id' => $user['usuario_id']]
        );
        return [
            'usuario_id' => $user['usuario_id'],
            'usuario_nombre' => $user['usuario_nombre'],
            'usuario_apellido' => $user['usuario_apellido'],
            'usuario_email' => $user['usuario_email'],
            'usuario_cargo' => $user['usuario_cargo'],
            'usuario_rol_id' => $user['usuario_rol_id'],
            'nombre' => $user['usuario_nombre'],
            'apellido' => $user['usuario_apellido'],
            'email' => $user['usuario_email'],
            'cargo' => $user['usuario_cargo'],
            'rol_id' => $user['usuario_rol_id'],
            'token' => $token,
        ];
    }

    private function recordFailedAttempt(string $email): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        try {
            $this->core->execute(
                "INSERT INTO login_attempts (email, ip_address, intento_fallido, user_agent, created_at) VALUES (?,?,1,?,NOW())",
                [$email, $ip, $ua]
            );
        } catch (\Exception $e) {}
        try {
            $this->core->execute(
                "UPDATE sys_usuarios SET intentos_fallidos = COALESCE(intentos_fallidos,0) + 1 WHERE usuario_email = ?",
                [$email]
            );
        } catch (\Exception $e) {}
        try {
            $count = $this->core->fetchColumn(
                "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$email, self::LOCKOUT_MINUTES]
            );
            if ($count >= self::MAX_ATTEMPTS) {
                $bloqueo = date('Y-m-d H:i:s', time() + (self::LOCKOUT_MINUTES * 60));
                $this->core->execute(
                    "UPDATE sys_usuarios SET bloqueado_hasta = ? WHERE usuario_email = ?",
                    [$bloqueo, $email]
                );
                $this->core->execute(
                    "UPDATE login_attempts SET bloqueado_hasta = ? WHERE email = ? AND bloqueado_hasta IS NULL ORDER BY created_at DESC LIMIT 1",
                    [$bloqueo, $email]
                );
            }
        } catch (\Exception $e) {}
    }

    private function isLockedOut(string $email, ?array $user): bool {
        if ($user && !empty($user['bloqueado_hasta'])) {
            if (strtotime($user['bloqueado_hasta']) > time()) return true;
            $this->clearLoginAttempts($email);
        }
        try {
            $blocked = $this->core->fetchColumn(
                "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND bloqueado_hasta > NOW()",
                [$email]
            );
            if ($blocked > 0) return true;
        } catch (\Exception $e) {}
        return false;
    }

    private function clearLoginAttempts(string $email): void {
        try {
            $this->core->execute(
                "UPDATE login_attempts SET bloqueado_hasta = NULL WHERE email = ? AND bloqueado_hasta > NOW()",
                [$email]
            );
        } catch (\Exception $e) {}
    }

    public function requires2FA(array $user): bool {
        $rolNombre = $user['rol_nombre'] ?? '';
        $has2FA = !empty($user['usuario_2fa_secret']) && !empty($user['usuario_2fa_activo']);
        if (in_array($rolNombre, ['SUPER_ADMIN', 'ADMIN'])) return $has2FA;
        return $has2FA;
    }

    public function verify2FACode(array $user, string $code): bool {
        $secret = $user['usuario_2fa_secret'] ?? '';
        if (empty($secret)) return false;
        require_once __DIR__ . '/TwoFactorAuth.php';
        return TwoFactorAuth::verify($secret, $code);
    }

    public function enable2FA(int $userId): string {
        require_once __DIR__ . '/TwoFactorAuth.php';
        $secret = TwoFactorAuth::generateSecret();
        $this->core->update('sys_usuarios',
            ['2fa_secret' => $secret, '2fa_habilitado' => 1],
            'usuario_id = :id', ['id' => $userId]
        );
        return $secret;
    }

    public function canAccess(string $modulo): bool {
        $userId = $_SESSION['auth_user']['usuario_id'] ?? 0;
        if (!$userId) return false;
        $rolNombre = $this->getUserRolNombre($userId);
        if ($rolNombre === 'SUPER_ADMIN') return true;
        try {
            $count = $this->core->fetchColumn(
                "SELECT COUNT(*) FROM roles_permisos WHERE rol_nombre = ? AND modulo = ?",
                [$rolNombre, $modulo]
            );
            if ($count > 0) return true;
            $count = $this->core->fetchColumn(
                "SELECT COUNT(*) FROM roles_permisos WHERE rol_nombre = ? AND modulo = '*'",
                [$rolNombre]
            );
            return $count > 0;
        } catch (\Exception $e) {
            return $rolNombre === 'SUPER_ADMIN' || $rolNombre === 'ADMIN';
        }
    }

    private function getUserRolNombre(int $userId): string {
        $user = $this->core->fetchOne(
            'SELECT r.rol_nombre FROM sys_usuarios u
             JOIN sys_roles r ON u.usuario_rol_id = r.rol_id
             WHERE u.usuario_id = :uid', ['uid' => $userId]
        );
        return $user['rol_nombre'] ?? '';
    }

    public function userHasPermission(int $userId, string $module, string $action): bool {
        $rolNombre = $this->getUserRolNombre($userId);
        if ($rolNombre === 'SUPER_ADMIN') return true;
        try {
            $count = $this->core->fetchColumn(
                "SELECT COUNT(*) FROM roles_permisos_detalle
                 WHERE rol_nombre = ? AND modulo = ? AND accion = ? AND activo = 1",
                [$rolNombre, $module, $action]
            );
            if ($count > 0) return true;
        } catch (\Exception $e) {}
        $user = $this->core->fetchOne(
            'SELECT r.rol_permisos FROM sys_usuarios u
             JOIN sys_roles r ON u.usuario_rol_id = r.rol_id
             WHERE u.usuario_id = :uid', ['uid' => $userId]
        );
        if (!$user || empty($user['rol_permisos'])) return false;
        $perms = json_decode($user['rol_permisos'], true) ?: [];
        return in_array("{$module}:{$action}", $perms) || in_array('*:*', $perms);
    }

    public function getUserPermissions(int $userId): array {
        $user = $this->core->fetchOne(
            'SELECT r.rol_permisos FROM sys_usuarios u
             JOIN sys_roles r ON u.usuario_rol_id = r.rol_id
             WHERE u.usuario_id = :uid', ['uid' => $userId]
        );
        return json_decode($user['rol_permisos'] ?? '[]', true) ?: [];
    }

    private function logAudit(?int $userId, string $action, string $table, ?int $recordId, string $desc): void {
        try {
            $this->core->execute(
                "INSERT INTO auditoria (id_usuario, usuario_nombre, accion, tabla_afectada, registro_id, descripcion, ip_origen, user_agent)
                 VALUES (?,?,?,?,?,?,?,?)",
                [
                    $userId,
                    $_SESSION['auth_user']['usuario_nombre'] ?? null,
                    $action,
                    $table,
                    $recordId,
                    $desc,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );
        } catch (\Exception $e) {}
    }

    public function generateJWT(array $user): string {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $secret = $this->core->getConfigValue('jwt_secret', 'Estr@teG1A_2025_S3cr3t_K3y!');
        $expire = $this->core->getConfigValue('jwt_expire', 28800);

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => 'EstrateGIA',
            'sub' => $user['usuario_id'],
            'email' => $user['usuario_email'],
            'rol' => $user['usuario_rol_id'] ?? 1,
            'iat' => time(),
            'exp' => time() + $expire,
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $secret, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    public function validateJWT(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;
        $secret = $this->core->getConfigValue('jwt_secret', 'Estr@teG1A_2025_S3cr3t_K3y!');
        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $secret, true)
        );

        if (!hash_equals($expectedSig, $signature)) return null;

        $data = json_decode($this->base64UrlDecode($payload), true);
        if (!$data || ($data['exp'] ?? 0) < time()) return null;

        return $data;
    }

    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
