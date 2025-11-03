<?php
/**
 * Cache Manager - Sistema di caching multi-layer
 *
 * Supporta Redis (preferito) con fallback a file system
 * Gestisce automaticamente serializzazione, TTL, invalidation
 *
 * @package ControlloDomin
 * @subpackage Cache
 * @version 1.0
 */

class CacheManager {

    /** @var Redis|null Istanza Redis */
    private $redis = null;

    /** @var bool Redis disponibile */
    private $redis_available = false;

    /** @var string Directory cache file */
    private $cache_dir;

    /** @var bool Cache abilitata */
    private $enabled = true;

    /** @var array Statistiche cache */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0
    ];

    /**
     * Constructor
     *
     * @param array $config Configurazione cache
     */
    public function __construct($config = []) {
        // Default config
        $defaults = [
            'enabled' => defined('CACHE_ENABLED') ? CACHE_ENABLED : true,
            'driver' => defined('CACHE_DRIVER') ? CACHE_DRIVER : 'auto',
            'redis_host' => defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1',
            'redis_port' => defined('REDIS_PORT') ? REDIS_PORT : 6379,
            'redis_password' => defined('REDIS_PASSWORD') ? REDIS_PASSWORD : '',
            'redis_database' => defined('REDIS_DATABASE') ? REDIS_DATABASE : 0,
            'cache_dir' => defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . '/../cache'
        ];

        $config = array_merge($defaults, $config);

        $this->enabled = $config['enabled'];
        $this->cache_dir = $config['cache_dir'];

        // Se cache disabilitata, skip setup
        if (!$this->enabled) {
            return;
        }

        // Crea directory cache se non esiste
        if (!is_dir($this->cache_dir)) {
            @mkdir($this->cache_dir, 0755, true);
        }

        // Setup Redis se richiesto
        if ($config['driver'] === 'redis' || $config['driver'] === 'auto') {
            $this->setupRedis($config);
        }
    }

    /**
     * Setup connessione Redis
     *
     * @param array $config Configurazione
     * @return bool Success
     */
    private function setupRedis($config) {
        if (!class_exists('Redis')) {
            return false;
        }

        try {
            $this->redis = new Redis();

            // Connessione con timeout
            $connected = $this->redis->connect(
                $config['redis_host'],
                $config['redis_port'],
                2 // timeout 2 secondi
            );

            if (!$connected) {
                $this->redis = null;
                return false;
            }

            // Auth se password presente
            if (!empty($config['redis_password'])) {
                $this->redis->auth($config['redis_password']);
            }

            // Seleziona database
            $this->redis->select($config['redis_database']);

            // Test connessione
            $this->redis->ping();

            $this->redis_available = true;
            return true;

        } catch (Exception $e) {
            $this->redis = null;
            $this->redis_available = false;
            return false;
        }
    }

    /**
     * Get value from cache
     *
     * @param string $key Cache key
     * @return mixed|null Valore o null se non trovato
     */
    public function get($key) {
        if (!$this->enabled) {
            return null;
        }

        // Sanitize key
        $key = $this->sanitizeKey($key);

        // Try Redis first
        if ($this->redis_available) {
            try {
                $value = $this->redis->get($key);
                if ($value !== false) {
                    $this->stats['hits']++;
                    return $this->unserialize($value);
                }
            } catch (Exception $e) {
                // Redis fallito, prova file
            }
        }

        // Fallback to file cache
        $filepath = $this->getFilePath($key);
        if (file_exists($filepath)) {
            $data = file_get_contents($filepath);
            $cache_data = json_decode($data, true);

            // Check expiration
            if ($cache_data && $cache_data['expires'] > time()) {
                $this->stats['hits']++;
                return $this->unserialize($cache_data['data']);
            } else {
                // Expired, delete
                @unlink($filepath);
            }
        }

        $this->stats['misses']++;
        return null;
    }

    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Valore da cachare
     * @param int $ttl Time to live in secondi (default 1 ora)
     * @return bool Success
     */
    public function set($key, $value, $ttl = 3600) {
        if (!$this->enabled) {
            return false;
        }

        $key = $this->sanitizeKey($key);
        $serialized = $this->serialize($value);

        $this->stats['sets']++;

        // Try Redis first
        if ($this->redis_available) {
            try {
                return $this->redis->setex($key, $ttl, $serialized);
            } catch (Exception $e) {
                // Redis fallito, prova file
            }
        }

        // Fallback to file cache
        $filepath = $this->getFilePath($key);
        $cache_data = [
            'key' => $key,
            'data' => $serialized,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        return file_put_contents($filepath, json_encode($cache_data), LOCK_EX) !== false;
    }

    /**
     * Delete key from cache
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete($key) {
        if (!$this->enabled) {
            return false;
        }

        $key = $this->sanitizeKey($key);

        // Delete from Redis
        if ($this->redis_available) {
            try {
                $this->redis->del($key);
            } catch (Exception $e) {
                // Ignore
            }
        }

        // Delete from file
        $filepath = $this->getFilePath($key);
        if (file_exists($filepath)) {
            return @unlink($filepath);
        }

        return true;
    }

    /**
     * Clear all cache (or pattern)
     *
     * @param string|null $pattern Pattern per clear selettivo (es: "dns:*")
     * @return bool Success
     */
    public function clear($pattern = null) {
        if (!$this->enabled) {
            return false;
        }

        // Clear Redis
        if ($this->redis_available) {
            try {
                if ($pattern) {
                    $keys = $this->redis->keys($pattern);
                    if ($keys) {
                        $this->redis->del($keys);
                    }
                } else {
                    $this->redis->flushDB();
                }
            } catch (Exception $e) {
                // Ignore
            }
        }

        // Clear file cache
        if ($pattern) {
            // Pattern matching per file
            $files = glob($this->cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $basename = basename($file);
                    // Simple pattern matching
                    if (fnmatch($pattern, $basename)) {
                        @unlink($file);
                    }
                }
            }
        } else {
            // Clear all files
            $files = glob($this->cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        return true;
    }

    /**
     * Get or set (callback pattern)
     *
     * @param string $key Cache key
     * @param callable $callback Callback per generare valore se non in cache
     * @param int $ttl TTL in secondi
     * @return mixed Valore
     */
    public function remember($key, $callback, $ttl = 3600) {
        // Try get
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        // Generate value
        $value = $callback();

        // Set in cache
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics
     */
    public function getStats() {
        $stats = $this->stats;
        $stats['driver'] = $this->redis_available ? 'redis' : 'file';
        $stats['enabled'] = $this->enabled;

        if ($this->redis_available) {
            try {
                $info = $this->redis->info();
                $stats['redis_memory'] = $info['used_memory_human'] ?? 'N/A';
                $stats['redis_keys'] = $this->redis->dbSize();
            } catch (Exception $e) {
                // Ignore
            }
        }

        return $stats;
    }

    /**
     * Sanitize cache key
     *
     * @param string $key Raw key
     * @return string Sanitized key
     */
    private function sanitizeKey($key) {
        // Remove special chars, keep alphanumeric + : . -
        return preg_replace('/[^a-zA-Z0-9:.\-_]/', '_', $key);
    }

    /**
     * Get file path for key
     *
     * @param string $key Sanitized key
     * @return string Full path
     */
    private function getFilePath($key) {
        $hash = md5($key);
        // Usa subdirectory per evitare troppi file in una cartella
        $subdir = substr($hash, 0, 2);
        $dir = $this->cache_dir . '/' . $subdir;

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . '/' . $hash . '.cache';
    }

    /**
     * Serialize value
     *
     * @param mixed $value Value to serialize
     * @return string Serialized
     */
    private function serialize($value) {
        return serialize($value);
    }

    /**
     * Unserialize value
     *
     * @param string $value Serialized value
     * @return mixed Unserialized value
     */
    private function unserialize($value) {
        return unserialize($value);
    }

    /**
     * Cleanup expired cache files
     *
     * @return int Number of files deleted
     */
    public function cleanup() {
        $deleted = 0;

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cache_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'cache') {
                $data = json_decode(file_get_contents($file->getPathname()), true);

                if ($data && $data['expires'] < time()) {
                    @unlink($file->getPathname());
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}

/**
 * Get global cache instance (Singleton)
 *
 * @return CacheManager Cache instance
 */
function getCache() {
    static $cache = null;

    if ($cache === null) {
        $cache = new CacheManager();
    }

    return $cache;
}

/**
 * Helper: Get from cache or execute callback
 *
 * @param string $key Cache key
 * @param callable $callback Callback function
 * @param int $ttl TTL in seconds
 * @return mixed Result
 */
function cache_remember($key, $callback, $ttl = 3600) {
    return getCache()->remember($key, $callback, $ttl);
}

/**
 * Helper: Clear cache
 *
 * @param string|null $pattern Pattern or null for all
 * @return bool Success
 */
function cache_clear($pattern = null) {
    return getCache()->clear($pattern);
}
