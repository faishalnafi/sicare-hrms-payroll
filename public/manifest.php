<?php
// Set headers
header('Content-Type: application/manifest+json; charset=utf-8');

// Include autoloader
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    $appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
    $appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';
} catch (\Exception $e) {
    $appName = 'siCare';
    $appLogoImage = '';
}

// Fallback to default PWA icon if no logo is uploaded
$iconSrc = $appLogoImage ?: '/images/icons/icon-192x192.png';

// Detect mime type of the logo icon
$iconType = 'image/png';
if (stripos($iconSrc, '.jpg') !== false || stripos($iconSrc, '.jpeg') !== false) {
    $iconType = 'image/jpeg';
} elseif (stripos($iconSrc, '.svg') !== false) {
    $iconType = 'image/svg+xml';
} elseif (stripos($iconSrc, '.ico') !== false) {
    $iconType = 'image/x-icon';
}

$manifest = [
    "name" => $appName . " Portal Mandiri Karyawan",
    "short_name" => $appName,
    "description" => "Portal Layanan Mandiri Karyawan " . $appName . " HRMS & Payroll",
    "start_url" => "/dashboard",
    "display" => "standalone",
    "background_color" => "#000666",
    "theme_color" => "#000666",
    "orientation" => "portrait",
    "icons" => [
        [
            "src" => $iconSrc,
            "sizes" => "72x72",
            "type" => $iconType,
            "purpose" => "any maskable"
        ],
        [
            "src" => $iconSrc,
            "sizes" => "96x96",
            "type" => $iconType,
            "purpose" => "any maskable"
        ],
        [
            "src" => $iconSrc,
            "sizes" => "128x128",
            "type" => $iconType,
            "purpose" => "any maskable"
        ],
        [
            "src" => $iconSrc,
            "sizes" => "144x144",
            "type" => $iconType,
            "purpose" => "any maskable"
        ],
        [
            "src" => $iconSrc,
            "sizes" => "152x152",
            "type" => $iconType,
            "purpose" => "any maskable"
        ],
        [
            "src" => $iconSrc,
            "sizes" => "192x192",
            "type" => $iconType,
            "purpose" => "any maskable"
        ],
        [
            "src" => $iconSrc,
            "sizes" => "384x384",
            "type" => $iconType,
            "purpose" => "any maskable"
        ],
        [
            "src" => $iconSrc,
            "sizes" => "512x512",
            "type" => $iconType,
            "purpose" => "any maskable"
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
