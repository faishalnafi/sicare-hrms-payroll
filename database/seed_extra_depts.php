<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$db = App\Config\Database::getInstance()->getConnection();

$depts = [
    ['dept-bph', 'Badan Pengurus Harian (BPH)', null, 1],
    ['dept-postprod', 'Post Production Department (Editor)', null, 1],
    ['dept-editorial', 'Editorial Department (Penulis)', null, 1],
    ['dept-broadcast', 'Broadcast Department (Host)', null, 1],
    ['dept-visprod', 'Visual Production Department (Kameramen)', null, 1],
];

// 1. Upsert the 5 official extracurricular departments
$stmt = $db->prepare("
    INSERT INTO departments (id, name, parent_id, level)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE name = VALUES(name), parent_id = VALUES(parent_id), level = VALUES(level)
");

foreach ($depts as $d) {
    $stmt->execute($d);
}

// 2. Safely reassign orphaned references in users and job_requisitions before purging
$allowedIds = ['dept-bph', 'dept-postprod', 'dept-editorial', 'dept-broadcast', 'dept-visprod'];
$inClause = "'" . implode("','", $allowedIds) . "'";

$db->exec("UPDATE users SET department_id = 'dept-bph' WHERE department_id IS NOT NULL AND department_id NOT IN ($inClause)");
$db->exec("UPDATE job_requisitions SET department_id = 'dept-postprod' WHERE department_id IS NOT NULL AND department_id NOT IN ($inClause)");

// 3. Purge all legacy/corporate departments & sub-departments outside the 5 official ones
$db->exec("DELETE FROM departments WHERE id NOT IN ($inClause)");

echo "Extracurricular departments seeded & purged successfully (5 main depts only, no sub-depts).\n";
