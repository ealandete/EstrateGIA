<?php

class Logger {

    private static ?Logger $instance = null;
    private string $logFile;

    public function __construct() {
        $this->logFile = BASE_PATH . '/logs/app_' . date('Ymd') . '.log';
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    public static function getInstance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function info(string $message, array $context = []): void {
        $this->write('INFO', $message, $context);
    }

    public function warn(string $message, array $context = []): void {
        $this->write('WARN', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void {
        $line = date('Y-m-d H:i:s') . ' [' . $level . '] '
            . ($_SESSION['auth_user']['usuario_email'] ?? 'anon') . ' '
            . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . ' '
            . $message
            . ($context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '')
            . "\n";
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
