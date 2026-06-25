<?php

/**
 * CacheService — Caché en memoria (array + archivo)
 * Extraído de EstrateGiaCore (refactor v2.1)
 */
class CacheService {

    private static array $store = [];
    private string $cacheFile;

    public function __construct() {
        $this->cacheFile = BASE_PATH . '/logs/cache.json';
    }

    public function get(string $key): ?array {
        if (isset(self::$store[$key])) {
            $entry = self::$store[$key];
            if ($entry['expires'] > 0 && $entry['expires'] < time()) { unset(self::$store[$key]); return null; }
            return $entry['data'];
        }
        if (file_exists($this->cacheFile)) {
            $disk = json_decode(file_get_contents($this->cacheFile), true) ?: [];
            if (isset($disk[$key])) {
                $entry = $disk[$key];
                if ($entry['expires'] > 0 && $entry['expires'] < time()) return null;
                self::$store[$key] = $entry;
                return $entry['data'];
            }
        }
        return null;
    }

    public function set(string $key, array $data, ?int $ttl = null): void {
        $expires = $ttl ? time() + $ttl : 0;
        self::$store[$key] = ['data' => $data, 'expires' => $expires, 'created' => time()];
        $disk = file_exists($this->cacheFile) ? (json_decode(file_get_contents($this->cacheFile), true) ?: []) : [];
        $disk[$key] = self::$store[$key];
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($this->cacheFile, json_encode($disk), LOCK_EX);
    }

    public function clear(?string $key = null): void {
        if ($key) {
            unset(self::$store[$key]);
            if (file_exists($this->cacheFile)) {
                $disk = json_decode(file_get_contents($this->cacheFile), true) ?: [];
                unset($disk[$key]);
                file_put_contents($this->cacheFile, json_encode($disk), LOCK_EX);
            }
        } else {
            self::$store = [];
            if (file_exists($this->cacheFile)) unlink($this->cacheFile);
        }
    }
}
