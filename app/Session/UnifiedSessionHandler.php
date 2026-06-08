<?php

namespace App\Session;

use SessionHandlerInterface;
use PDO;
use Exception;

class UnifiedSessionHandler implements SessionHandlerInterface {
    private $db = null;
    private $redis = null;
    private $driver;
    private $sessionLifetime = 86400; // 24 hours default

    public function __construct() {
        // Driver can be 'database', 'redis', or 'hybrid'
        $this->driver = $_ENV['SESSION_DRIVER'] ?? 'hybrid';
        $lifetime = $_ENV['SESSION_LIFETIME'] ?? getenv('SESSION_LIFETIME');
        if ($lifetime && is_numeric($lifetime)) {
            $this->sessionLifetime = (int)$lifetime;
        }
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
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $redis = $this->getRedisConnection();
        $data = '';
        $loaded = false;

        // 1. Try reading from Redis (first line of defense)
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

        // 2. Try reading from Database (second line of defense)
        if (!$loaded && ($this->driver === 'database' || $this->driver === 'hybrid')) {
            try {
                $db = $this->getDbConnection();
                $stmt = $db->prepare("SELECT payload, last_activity FROM sessions WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $payload = $row['payload'];
                    $lastActivity = (int)$row['last_activity'];
                    
                    $remember = $this->isRememberMe($payload);
                    if ($remember) {
                        // Enforce a strict/fixed 7-day expiry from the initial login time
                        $loginTime = $this->extractLoginTime($payload) ?? $lastActivity;
                        if (time() - $loginTime > 7 * 86400) {
                            $this->destroy($id);
                            return '';
                        }
                    } else {
                        // Enforce sliding window (24 hours default) from last activity
                        if (time() - $lastActivity > $this->sessionLifetime) {
                            $this->destroy($id);
                            return '';
                        }
                    }

                    $data = $payload;
                    $loaded = true;

                    // Backfill/sync to Redis if driver is hybrid
                    if ($this->driver === 'hybrid' && $redis) {
                        try {
                            if ($remember) {
                                $loginTime = $this->extractLoginTime($data) ?? $lastActivity;
                                $remaining = max(1, ($loginTime + 7 * 86400) - time());
                            } else {
                                $remaining = $this->sessionLifetime;
                            }
                            $redis->setex("sessions:{$id}", $remaining, $data);
                        } catch (Exception $e) {
                            // Ignore
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignore and return empty session
            }
        }

        // 3. Extend/slide cookie lifetime dynamically based on session type
        if ($loaded && !headers_sent()) {
            $remember = $this->isRememberMe($data);
            if ($remember) {
                $loginTime = $this->extractLoginTime($data) ?? time();
                $remaining = max(1, ($loginTime + 7 * 86400) - time());
            } else {
                $remaining = $this->sessionLifetime;
            }
            
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                $id,
                time() + $remaining,
                $params['path'],
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }

        return $data;
    }

    public function write(string $id, string $data): bool {
        $redis = $this->getRedisConnection();
        
        $remember = $this->isRememberMe($data);
        if ($remember) {
            $loginTime = $this->extractLoginTime($data) ?? time();
            $lifetime = max(1, ($loginTime + 7 * 86400) - time());
        } else {
            $lifetime = $this->sessionLifetime;
        }
        
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
            
            $normalExpiry = time() - $this->sessionLifetime;
            $rememberExpiry = time() - (7 * 86400);

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
     * Helper to extract login_time from serialized session payload.
     */
    private function extractLoginTime(string $payload): ?int {
        if (preg_match('/login_time\|i:(\d+)/', $payload, $matches)) {
            return (int)$matches[1];
        }
        return null;
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
        return false;
    }
}
