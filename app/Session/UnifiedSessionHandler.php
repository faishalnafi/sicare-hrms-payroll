<?php

namespace App\Session;

use SessionHandlerInterface;
use PDO;
use Exception;

class UnifiedSessionHandler implements SessionHandlerInterface {
    private $db = null;
    private $redis = null;
    private $driver;
    private $normalLifetime = 21600; // 6 hours
    private $rememberLifetime = 86400; // 24 hours sliding idle timeout

    public function __construct() {
        // Driver can be 'database', 'redis', or 'hybrid'
        $this->driver = $_ENV['SESSION_DRIVER'] ?? 'hybrid';
    }

    /**
     * Retrieve Redis connection using php_redis native extension if available.
     * Safe and self-healing: falls back to Database on failure/unavailability.
     */
    private function getRedisConnection() {
        if ($this->redis === null) {
            if (extension_loaded('redis')) {
                try {
                    $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
                    $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
                    $pass = $_ENV['REDIS_PASSWORD'] ?? null;
                    $dbIndex = (int)($_ENV['REDIS_DB'] ?? 0);
                    
                    $redis = new \Redis();
                    if ($redis->connect($host, $port, 1.0)) {
                        if (!empty($pass)) {
                            $redis->auth($pass);
                        }
                        if ($dbIndex > 0) {
                            $redis->select($dbIndex);
                        }
                        $this->redis = $redis;
                    } else {
                        $this->redis = false;
                    }
                } catch (Exception $e) {
                    $this->redis = false;
                }
            } else {
                $this->redis = false;
            }
        }
        return $this->redis ?: null;
    }

    /**
     * Retrieve Database connection.
     */
    private function getDbConnection() {
        if ($this->db === null) {
            $this->db = \App\Config\Database::getInstance()->getConnection();
        }
        return $this->db;
    }

    public function open(string $path, string $name): bool {
        // Ensure the sessions table exists in the database
        try {
            $db = $this->getDbConnection();
            $db->exec("
                CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(255) PRIMARY KEY,
                    user_id CHAR(36) NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    payload LONGTEXT NOT NULL,
                    last_activity INT NOT NULL,
                    KEY sessions_user_id_index (user_id),
                    KEY sessions_last_activity_index (last_activity)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (Exception $e) {
            // Ignore error or log it
        }
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $redis = $this->getRedisConnection();
        $data = '';
        $loaded = false;

        // 1. Try reading from Redis
        if (($this->driver === 'redis' || $this->driver === 'hybrid') && $redis) {
            try {
                $payload = $redis->get("sessions:{$id}");
                if ($payload !== false) {
                    $data = $payload;
                    $loaded = true;
                }
            } catch (Exception $e) {
                // Fail-safe: fallback to database
            }
        }

        // 2. Try reading from Database
        if (!$loaded && ($this->driver === 'database' || $this->driver === 'hybrid')) {
            try {
                $db = $this->getDbConnection();
                $stmt = $db->prepare("SELECT payload, last_activity FROM sessions WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $payload = $row['payload'];
                    $lastActivity = (int)$row['last_activity'];
                    
                    // Enforce session expiration limits dynamically
                    $lifetime = $this->isRememberMe($payload) ? $this->rememberLifetime : $this->normalLifetime;
                    if (time() - $lastActivity > $lifetime) {
                        $this->destroy($id);
                        return '';
                    }

                    $data = $payload;
                    $loaded = true;

                    // Backfill/sync to Redis if driver is hybrid
                    if ($this->driver === 'hybrid' && $redis) {
                        try {
                            $redis->setex("sessions:{$id}", $lifetime, $data);
                        } catch (Exception $e) {
                            // Ignore
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignore and return empty session
            }
        }

        // 3. Dynamic persistent cookie extension: if remember_me is set, renew cookie lifetime
        if ($loaded && $this->isRememberMe($data) && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                $id,
                time() + 30 * 86400, // 30 days persistent cookie
                $params['path'],
                $params['domain'],
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }

        return $data;
    }

    public function write(string $id, string $data): bool {
        $redis = $this->getRedisConnection();
        $remember = $this->isRememberMe($data);
        $lifetime = $remember ? $this->rememberLifetime : $this->normalLifetime;
        
        $userId = $this->extractUserId($data);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $time = time();

        // 1. Write to Redis
        if (($this->driver === 'redis' || $this->driver === 'hybrid') && $redis) {
            try {
                $redis->setex("sessions:{$id}", $lifetime, $data);
            } catch (Exception $e) {
                // Ignore and write to DB
            }
        }

        // 2. Write to Database
        if ($this->driver === 'database' || $this->driver === 'hybrid') {
            try {
                $db = $this->getDbConnection();
                $stmt = $db->prepare("
                    INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity)
                    VALUES (:id, :user_id, :ip, :ua, :payload, :la)
                    ON DUPLICATE KEY UPDATE 
                        user_id = VALUES(user_id),
                        ip_address = VALUES(ip_address),
                        user_agent = VALUES(user_agent),
                        payload = VALUES(payload),
                        last_activity = VALUES(last_activity)
                ");
                $stmt->execute([
                    'id' => $id,
                    'user_id' => $userId,
                    'ip' => $ip,
                    'ua' => $ua,
                    'payload' => $data,
                    'la' => $time
                ]);
            } catch (Exception $e) {
                if ($this->driver === 'database') {
                    return false;
                }
            }
        }

        return true;
    }

    public function destroy(string $id): bool {
        $redis = $this->getRedisConnection();
        
        // 1. Delete from Redis
        if (($this->driver === 'redis' || $this->driver === 'hybrid') && $redis) {
            try {
                $redis->del("sessions:{$id}");
            } catch (Exception $e) {
                // Ignore
            }
        }

        // 2. Delete from Database
        if ($this->driver === 'database' || $this->driver === 'hybrid') {
            try {
                $db = $this->getDbConnection();
                $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
                $stmt->execute([$id]);
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    public function gc(int $max_lifetime): int|false {
        try {
            $db = $this->getDbConnection();
            $normalExpiry = time() - $this->normalLifetime;
            $rememberExpiry = time() - $this->rememberLifetime;

            $stmt = $db->prepare("
                DELETE FROM sessions 
                WHERE (payload LIKE '%remember_me|b:1%' AND last_activity < :remember_expiry)
                   OR (payload NOT LIKE '%remember_me|b:1%' AND last_activity < :normal_expiry)
            ");
            $stmt->execute([
                'remember_expiry' => $rememberExpiry,
                'normal_expiry' => $normalExpiry
            ]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Checks if the remember_me flag is present in the serialized session payload.
     */
    private function isRememberMe(string $payload): bool {
        return str_contains($payload, 'remember_me|b:1');
    }

    /**
     * Helper to extract user_id from serialized session payload.
     */
    private function extractUserId(string $payload): ?string {
        if (preg_match('/user_id\|s:\d+:"([^"]+)"/', $payload, $matches)) {
            return $matches[1];
        }
        if (preg_match('/user_id\|i:(\d+)/', $payload, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Static helper for early configuration check in index.php
     */
    public static function checkRememberMeStatus(string $id): bool {
        $driver = $_ENV['SESSION_DRIVER'] ?? 'hybrid';
        
        // 1. Check Redis first
        if ($driver === 'redis' || $driver === 'hybrid') {
            if (extension_loaded('redis')) {
                try {
                    $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
                    $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
                    $pass = $_ENV['REDIS_PASSWORD'] ?? null;
                    $dbIndex = (int)($_ENV['REDIS_DB'] ?? 0);
                    
                    $redis = new \Redis();
                    if ($redis->connect($host, $port, 0.5)) {
                        if (!empty($pass)) {
                            $redis->auth($pass);
                        }
                        if ($dbIndex > 0) {
                            $redis->select($dbIndex);
                        }
                        $payload = $redis->get("sessions:{$id}");
                        if ($payload !== false && str_contains($payload, 'remember_me|b:1')) {
                            return true;
                        }
                    }
                } catch (Exception $e) {
                    // Ignore
                }
            }
        }

        // 2. Check Database
        if ($driver === 'database' || $driver === 'hybrid') {
            try {
                $db = \App\Config\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT payload FROM sessions WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && str_contains($row['payload'], 'remember_me|b:1')) {
                    return true;
                }
            } catch (Exception $e) {
                // Ignore
            }
        }

        return false;
    }
}
