<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
session_start();
$_SESSION['role'] = 'superadmin';

function renderView($viewPath, $data = []) {
    extract($data);
    $fullPath = __DIR__ . '/../resources/views/' . $viewPath . '.php';
    if (file_exists($fullPath)) {
        ob_start();
        require $fullPath;
        return ob_get_clean();
    }
    return "View not found: " . $viewPath;
}

$c = new App\Controllers\MenuMappingController();
ob_start();
$c->list();
$output = ob_get_clean();
echo $output;
