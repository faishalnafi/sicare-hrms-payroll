<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$db = App\Config\Database::getInstance()->getConnection();
$stmt = $db->query('DESCRIBE system_menus');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
