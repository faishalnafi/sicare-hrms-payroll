<?php
/**
 * siCare - Built-in Development Server Router
 * Emulates Apache mod_rewrite / Nginx try_files for PHP CLI Server.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

$publicDir = __DIR__ . '/public';

// If requested URI is a physical file in public folder, let PHP built-in server serve it directly
if ($uri !== '/') {
    $filePath = $publicDir . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        return false;
    }
}

require_once $publicDir . '/index.php';
