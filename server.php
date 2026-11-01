<?php
/**
 * siCare - Built-in Development Server Router
 * Emulates Apache mod_rewrite / Nginx try_files for PHP CLI Server.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// If the requested URI corresponds to an actual file in public/, return false to let PHP serve it
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
