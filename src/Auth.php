<?php

class Auth {
    public static function login(string $email, string $password): bool {
        $core = EstrateGiaCore::getInstance();
        $user = $core->authenticateUser($email, $password);

        if ($user) {
            // Si tiene 2FA activo, solo guardar temporal y pedir código
            if (!empty($user['usuario_2fa_activo'])) {
                $_SESSION['auth_temp'] = $user;
                return true; // Login exitoso, pero requiere 2FA
            }
            $_SESSION['auth_user'] = $user;
            $_SESSION['auth_token'] = $user['token'];
            return true;
        }
        return false;
    }

    public static function verify2FA(string $code): bool {
        require_once BASE_PATH . '/lib/TwoFactorAuth.php';
        $user = $_SESSION['auth_temp'] ?? null;
        if (!$user || empty($user['usuario_2fa_secret'])) return false;
        if (TwoFactorAuth::verify($user['usuario_2fa_secret'], $code)) {
            $_SESSION['auth_user'] = $user;
            $_SESSION['auth_token'] = $user['token'];
            unset($_SESSION['auth_temp']);
            return true;
        }
        return false;
    }

    public static function needs2FA(): bool {
        return isset($_SESSION['auth_temp']) && !isset($_SESSION['auth_user']);
    }

    public static function logout(): void {
        unset($_SESSION['auth_user'], $_SESSION['auth_token']);
        session_destroy();
    }

    public static function check(): bool {
        return isset($_SESSION['auth_user']);
    }

    public static function user(): ?array {
        return $_SESSION['auth_user'] ?? null;
    }

    public static function userId(): ?int {
        return $_SESSION['auth_user']['usuario_id'] ?? null;
    }

    public static function userRol(): ?int {
        return $_SESSION['auth_user']['rol_id'] ?? null;
    }

    public static function userName(): string {
        $u = self::user();
        return $u ? ($u['nombre'] . ' ' . ($u['apellido'] ?? '')) : 'Invitado';
    }

    public static function userCargo(): string {
        return self::user()['cargo'] ?? '';
    }

    public static function userDepartamento(): string {
        return self::user()['departamento'] ?? '';
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function guard(): void {
        self::requireAuth();
    }
}
