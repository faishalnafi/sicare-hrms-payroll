<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM sessions");
    echo "Sessions count: " . $stmt->fetchColumn() . "\n";

    // Also check if csrf_token is empty in the most recent session
    $stmt2 = $db->query("SELECT payload FROM sessions ORDER BY last_activity DESC LIMIT 1");
    $row = $stmt2->fetch();
    if ($row) {
        echo "Latest session payload: " . $row['payload'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
