<?php
require 'vendor/autoload.php';

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, first_name, last_name, email, role, department_id FROM users");
    echo "USERS IN DB:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
