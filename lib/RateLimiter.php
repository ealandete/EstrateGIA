<?php
/**
 * Rate Limiting Middleware para EstrateGIA
 * Protege contra ataques de fuerza bruta y DoS
 */

class RateLimiter {
    private static $instance = null;
    private $maxAttempts = 100; // 100 requests por minuto
    private $windowSeconds = 60;
    
    private function __construct() {}
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function check(string $identifier = ''): bool {
        if (empty($identifier)) {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        // Obtener datos actuales
        $data = $_SESSION[$key] ?? ['count' => 0, 'start' => $now];
        
        // Resetear si pasó el ventana
        if ($now - $data['start'] >= $this->windowSeconds) {
            $data = ['count' => 0, 'start' => $now];
        }
        
        // Incrementar contador
        $data['count']++;
        $_SESSION[$key] = $data;
        
        // Verificar límite
        return $data['count'] <= $this->maxAttempts;
    }
    
    public function getRemaining(): int {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rate_limit_' . md5($identifier);
        $data = $_SESSION[$key] ?? ['count' => 0, 'start' => time()];
        return max(0, $this->maxAttempts - $data['count']);
    }
}
