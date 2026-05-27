<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = App\Config\Database::getInstance()->getConnection();

$newMenus = [
    [
        'id' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
        'menu_name' => 'Analitik Pelanggan & Pemasaran',
        'menu_key' => 'marketing_analytics',
        'description' => 'Akses metrik kampanye, kepuasan pelanggan (CSAT), dan tren pasar.'
    ],
    [
        'id' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
        'menu_name' => 'Inovasi & Teknologi Data',
        'menu_key' => 'tech_innovation',
        'description' => 'Akses transformasi digital, infrastruktur IT, dan tata kelola data.'
    ]
];

$db->beginTransaction();
try {
    $stmt = $db->prepare("INSERT INTO system_menus (id, menu_name, menu_key, description) VALUES (?, ?, ?, ?)");
    foreach ($newMenus as $m) {
        // Ignore if exists
        try {
            $stmt->execute([$m['id'], $m['menu_name'], $m['menu_key'], $m['description']]);
        } catch(PDOException $e) {
            // Probably duplicate key on menu_key, ignore
        }
    }
    $db->commit();
    echo "Added 2 new menus.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Failed: " . $e->getMessage() . "\n";
}
