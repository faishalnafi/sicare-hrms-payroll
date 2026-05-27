<?php
namespace App\Middleware;

class SecurityMiddleware {
    /**
     * Run all security checks and sanitizations
     */
    public static function run() {
        self::sanitizeGlobals();
        self::verifyCsrfToken();
    }

    /**
     * Prevent XSS by recursively sanitizing all $_GET, $_POST, and $_COOKIE variables
     */
    private static function sanitizeGlobals() {
        $_GET = self::sanitizeArray($_GET);
        $_POST = self::sanitizeArray($_POST);
        $_COOKIE = self::sanitizeArray($_COOKIE);
    }

    /**
     * Recursively escape HTML entities in an array
     */
    private static function sanitizeArray($array) {
        if (!is_array($array)) return $array;
        $sanitized = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                // Remove HTML tags and escape special chars for XSS protection
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }

    /**
     * Verify CSRF token for state-changing requests (POST, PUT, DELETE, PATCH)
     */
    private static function verifyCsrfToken() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only check CSRF for state-changing methods
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            // Check if token exists in session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Exclude public endpoints if any (like auth login/register or webhooks)
            // Note: For siCare, login and register should ideally also have CSRF, but if it breaks we can exclude them.
            // Let's enforce CSRF for all POST requests.
            $clientToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $serverToken = $_SESSION['csrf_token'] ?? '';

            if (empty($serverToken)) {
                // Generate token if not exists
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $serverToken = $_SESSION['csrf_token'];
            }

            if (!empty($clientToken) && hash_equals($serverToken, $clientToken)) {
                // Valid token
                return;
            }

            // Invalid token
            file_put_contents(__DIR__ . '/../../storage/csrf_error.log', "CSRF Failed. Method: $method, Client: '$clientToken', Server: '$serverToken', POST: " . print_r($_POST, true) . ", COOKIE: " . print_r($_COOKIE, true) . "\n", FILE_APPEND);
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'CSRF Token Validation Failed.']);
            exit;
        } else {
            // For GET requests, just ensure a token exists in session so views can use it
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }

    /**
     * Get the current CSRF token to be placed in forms or meta tags
     */
    public static function getCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
