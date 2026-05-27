<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = App\Config\Database::getInstance()->getConnection();

$cLevels = [
    'Chief Executive Officer (CEO)',
    'Chief Operating Officer (COO)',
    'Chief Financial Officer (CFO)',
    'Chief Technology Officer (CTO)',
    'Chief Information Officer (CIO)',
    'Chief Marketing Officer (CMO)',
    'Chief Human Resources Officer (CHRO)',
    'Chief People Officer (CPO - HR)',
    'Chief Product Officer (CPO - Product)',
    'Chief Strategy Officer (CSO)',
    'Chief Business Officer (CBO)',
    'Chief Commercial Officer (CCO)',
    'Chief Risk Officer (CRO)',
    'Chief Compliance Officer (CCO - Compliance)',
    'Chief Data Officer (CDO)',
    'Chief Information Security Officer (CISO)',
    'Chief Administrative Officer (CAO)',
    'Chief Investment Officer (CIO - Investment)',
    'Chief Experience Officer (CXO)',
    'Chief Growth Officer (CGO)'
];

$db->beginTransaction();
try {
    // Optionally, delete existing C-levels if we want to replace them
    $db->exec("DELETE FROM departments WHERE is_executive_entity = TRUE");
    
    // Determine a parent for them if needed. For now, they are all root (parent_id = NULL).
    // Or CEO could be the parent of the others. Let's make them all root for simplicity.
    $stmt = $db->prepare("INSERT INTO departments (id, name, parent_id, is_executive_entity, level) VALUES (?, ?, NULL, TRUE, 1)");

    foreach ($cLevels as $roleName) {
        // Generate UUID v4
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $stmt->execute([$uuid, $roleName]);
    }
    
    $db->commit();
    echo "Successfully seeded " . count($cLevels) . " C-Level roles.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Failed: " . $e->getMessage() . "\n";
}
