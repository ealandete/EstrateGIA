<?php
/**
 * EstrateGIA - Sistema Multi-idioma
 * Soporte Español (default) / English
 */
class Lang {
    private static $instance = null;
    private array $strings = [];
    private string $locale;

    private function __construct() {
        $this->locale = $_COOKIE['lang'] ?? 'es';
        $file = BASE_PATH . '/lang/' . $this->locale . '.php';
        if (file_exists($file)) {
            $this->strings = require $file;
        }
    }

    public static function get(string $key, string $default = ''): string {
        if (!self::$instance) self::$instance = new Lang();
        return self::$instance->strings[$key] ?? ($default ?: $key);
    }

    public static function locale(): string {
        if (!self::$instance) self::$instance = new Lang();
        return self::$instance->locale;
    }

    public static function switch(string $locale): void {
        setcookie('lang', $locale, time() + 86400 * 365, '/');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    // Helper: __() en templates
    public static function __(string $key, string $default = ''): string {
        return self::get($key, $default);
    }
}
