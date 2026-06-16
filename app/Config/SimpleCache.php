<?php

namespace App\Config;

class SimpleCache {
    private static $instance = null;
    private $cacheDir;

    private function __construct() {
        $this->cacheDir = __DIR__ . '/../../storage/framework/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getFilePath($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Get a cached value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return $default;
        }

        $cached = @unserialize($data);
        if (!$cached || !is_array($cached) || !isset($cached['expire']) || !isset($cached['value'])) {
            $this->delete($key);
            return $default;
        }

        if ($cached['expire'] !== 0 && time() > $cached['expire']) {
            $this->delete($key);
            return $default;
        }

        return $cached['value'];
    }

    /**
     * Set a cached value.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl time to live in seconds (default 3600, 0 means infinite)
     * @return bool
     */
    public function set($key, $value, $ttl = 3600) {
        $file = $this->getFilePath($key);
        $expire = $ttl === 0 ? 0 : time() + $ttl;
        $cached = [
            'key' => $key,
            'value' => $value,
            'expire' => $expire
        ];
        $serialized = @serialize($cached);
        if ($serialized === false) {
            return false;
        }
        return @file_put_contents($file, $serialized) !== false;
    }

    /**
     * Delete a cached value.
     *
     * @param string $key
     * @return bool
     */
    public function delete($key) {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return false;
    }

    /**
     * Clear all cached values matching a key prefix.
     * If prefix is empty, clears all cache files.
     *
     * @param string $prefix
     * @return bool
     */
    public function clear($prefix = '') {
        $files = glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (empty($prefix)) {
                @unlink($file);
            } else {
                $data = @file_get_contents($file);
                if ($data !== false) {
                    $cached = @unserialize($data);
                    if ($cached && is_array($cached) && isset($cached['key'])) {
                        if (str_starts_with($cached['key'], $prefix)) {
                            @unlink($file);
                        }
                    }
                }
            }
        }
        return true;
    }
}
