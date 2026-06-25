<?php
declare(strict_types=1);

/**
 * CacheService - Caché con Redis (fallback a archivo)
 * EstrateGIA v2.1
 */
class CacheService {
    private static ?CacheService $instance = null;
    private bool $redisAvailable = false;
    private $redis = null;
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(int $defaultTtl = 3600) {
        $this->defaultTtl = $defaultTtl;
        $this->cacheDir = BASE_PATH . '/cache/';
        if (!is_dir($this->cacheDir)) mkdir($this->cacheDir, 0755, true);

        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379, 0.5);
                $this->redisAvailable = true;
            } catch (\Throwable $e) {
                $this->redisAvailable = false;
            }
        }
    }

    public static function getInstance(int $defaultTtl = 3600): self {
        if (!self::$instance) self::$instance = new self($defaultTtl);
        return self::$instance;
    }

    public function get(string $key): mixed {
        if ($this->redisAvailable) {
            $val = $this->redis->get($key);
            return $val !== false ? json_decode($val, true) : null;
        }
        $file = $this->cacheDir . md5($key) . '.cache';
        if (!file_exists($file)) return null;
        $data = json_decode(file_get_contents($file), true);
        if (!$data || ($data['expires'] ?? 0) < time()) { @unlink($file); return null; }
        return $data['value'] ?? null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool {
        $ttl ??= $this->defaultTtl;
        if ($this->redisAvailable) {
            return $this->redis->setex($key, $ttl, json_encode($value));
        }
        $data = ['value' => $value, 'expires' => time() + $ttl];
        return (bool)file_put_contents($this->cacheDir . md5($key) . '.cache', json_encode($data));
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed {
        $cached = $this->get($key);
        if ($cached !== null) return $cached;
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function delete(string $key): void {
        if ($this->redisAvailable) { $this->redis->del($key); return; }
        @unlink($this->cacheDir . md5($key) . '.cache');
    }

    public function flush(): void {
        if ($this->redisAvailable) { $this->redis->flushDB(); return; }
        foreach (glob($this->cacheDir . '*.cache') as $f) @unlink($f);
    }

    public function clear(): void { $this->flush(); }

    public function isRedisAvailable(): bool { return $this->redisAvailable; }
}
