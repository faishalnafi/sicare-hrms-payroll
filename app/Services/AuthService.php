<?php

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDO;

/**
 * Class AuthService
 * 
 * Business Layer Service encapsulating authentication, session initialization,
 * password verification, remember-me token handling, and password resets.
 */
class AuthService {

    /**
     * Authenticate a user by credentials.
     */
    public function authenticate(string $email, string $password, bool $remember = false, string $ipAddress = '127.0.0.1'): array {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email dan kata sandi wajib diisi.'];
        }

        try {
            $db = Database::getInstance()->getConnection();

            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Email tidak terdaftar. Silakan mendaftar terlebih dahulu atau periksa kembali email yang Anda masukkan.'
                ];
            }

            if (isset($user['is_deleted']) && (int)$user['is_deleted'] === 1) {
                return [
                    'success' => false,
                    'message' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi tim HR.'
                ];
            }

            // Check brute-force throttling (5 attempts within 15 minutes)
            $throttleStmt = $db->prepare("
                SELECT COUNT(*) FROM login_attempts 
                WHERE email = :email AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $throttleStmt->execute(['email' => $email]);
            $attempts = (int)$throttleStmt->fetchColumn();

            if ($attempts >= 5) {
                return [
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan gagal. Akun dikunci sementara selama 15 menit demi keamanan.'
                ];
            }

            if (!password_verify($password, $user['password_hash'])) {
                // Log failed attempt
                $logStmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, attempt_time) VALUES (:email, :ip, NOW())");
                $logStmt->execute(['email' => $email, 'ip' => $ipAddress]);

                return [
                    'success' => false,
                    'message' => 'Email atau kata sandi tidak valid.'
                ];
            }

            if (isset($user['is_suspended']) && (int)$user['is_suspended'] === 1) {
                return [
                    'success' => false,
                    'message' => 'Akun Anda sedang ditangguhkan. Silakan hubungi pihak HR.'
                ];
            }

            // Clear login attempts on success
            $clearStmt = $db->prepare("DELETE FROM login_attempts WHERE email = :email");
            $clearStmt->execute(['email' => $email]);

            // Setup session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department_id'] = $user['department_id'] ?? null;
            $_SESSION['logged_in'] = true;

            // Optional remember-me token handling
            if ($remember) {
                $rememberToken = bin2hex(random_bytes(32));
                $hashedToken = hash('sha256', $rememberToken);
                $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days

                $tokenStmt = $db->prepare("
                    UPDATE users 
                    SET remember_token = :token, remember_token_expires_at = :expires 
                    WHERE id = :id
                ");
                $tokenStmt->execute([
                    'token' => $hashedToken,
                    'expires' => $expiresAt,
                    'id' => $user['id']
                ]);

                setcookie('remember_me', $rememberToken, [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            return [
                'success' => true,
                'message' => 'Login berhasil.',
                'user' => [
                    'id' => $user['id'],
                    'name' => $_SESSION['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process remember-me cookie verification.
     */
    public function processRememberMe(string $cookieToken): bool {
        if (empty($cookieToken)) return false;

        try {
            $db = Database::getInstance()->getConnection();
            $hashedToken = hash('sha256', $cookieToken);

            $stmt = $db->prepare("
                SELECT * FROM users 
                WHERE remember_token = :token 
                  AND remember_token_expires_at > NOW() 
                  AND is_deleted = 0 
                  AND is_suspended = 0 
                LIMIT 1
            ");
            $stmt->execute(['token' => $hashedToken]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''));
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'] ?? null;
                $_SESSION['logged_in'] = true;
                return true;
            }
        } catch (\Exception $e) {
            // Log error if needed
        }
        return false;
    }

    /**
     * Validate profile data reset token.
     */
    public function validateProfileResetToken(string $token): ?array {
        if (empty($token)) return null;

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT * FROM users 
                WHERE reset_password_token = :token 
                  AND reset_password_expires_at > NOW() 
                LIMIT 1
            ");
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
