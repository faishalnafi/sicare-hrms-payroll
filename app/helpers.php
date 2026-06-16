<?php

/**
 * Global Helper Functions for siCare System
 */

if (!function_exists('e')) {
    /**
     * Escape HTML entities to prevent XSS attacks.
     *
     * @param string|null $value
     * @return string
     */
    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Retrieve or generate CSRF token in session.
     *
     * @return string
     */
    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent() && PHP_SAPI !== 'cli') {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    /**
     * Generate HTML hidden input field for CSRF token (camelCase).
     *
     * @return string
     */
    function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate HTML hidden input field for CSRF token (snake_case).
     *
     * @return string
     */
    function csrf_field() {
        return csrfField();
    }
}

if (!function_exists('db_query')) {
    /**
     * Helper to execute SQL queries using prepared statements dynamically.
     * Prevents raw string interpolation in database operations.
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    function db_query($sql, $params = []) {
        $db = \App\Config\Database::getInstance()->getConnection();
        if (empty($params)) {
            return $db->query($sql);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

if (!function_exists('validate_password_strength')) {
    /**
     * Validate password strength based on configured global settings.
     *
     * @param string $password
     * @param string &$errorMsg
     * @return bool
     */
    function validate_password_strength($password, &$errorMsg) {
        $db = \App\Config\Database::getInstance()->getConnection();
        
        // Fetch security settings
        $stmt = $db->query("SELECT `key`, `value` FROM global_settings WHERE `key` IN ('min_password_length', 'require_uppercase', 'require_number', 'require_special_char')");
        $rules = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rules[$row['key']] = $row['value'];
        }

        $minLen = (int)($rules['min_password_length'] ?? 8);
        $reqUpper = ($rules['require_uppercase'] ?? 'false') === 'true';
        $reqNum = ($rules['require_number'] ?? 'false') === 'true';
        $reqSpecial = ($rules['require_special_char'] ?? 'false') === 'true';

        if (strlen($password) < $minLen) {
            $errorMsg = "Kata sandi minimal harus {$minLen} karakter.";
            return false;
        }

        if ($reqUpper && !preg_match('/[A-Z]/', $password)) {
            $errorMsg = "Kata sandi wajib mengandung setidaknya satu huruf kapital.";
            return false;
        }

        if ($reqNum && !preg_match('/[0-9]/', $password)) {
            $errorMsg = "Kata sandi wajib mengandung setidaknya satu angka.";
            return false;
        }

        if ($reqSpecial && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errorMsg = "Kata sandi wajib mengandung setidaknya satu karakter spesial.";
            return false;
        }

        return true;
    }
}
