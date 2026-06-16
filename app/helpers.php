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
