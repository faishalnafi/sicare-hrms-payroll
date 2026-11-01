<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$db = App\Config\Database::getInstance()->getConnection();

$defaultJobs = [
    [
        'id' => 'req-postprod-001',
        'req_number' => 'REQ-202608-001',
        'title' => 'Staf Editor - Post Production Department',
        'department_id' => 'dept-postprod',
        'headcount' => 10,
        'employment_type' => 'full_time',
        'min_salary' => 0,
        'max_salary' => 0,
        'job_description' => 'Bertanggung jawab melakukan editing video, perakitan footage, color grading, serta pengolahan audio untuk konten-konten ekstrakurikuler.',
        'requirements' => '1. Memiliki ketertarikan pada editing video (Adobe Premiere, CapCut, DaVinci, atau sejenis).\n2. Kreatif, komunikatif, dan mampu bekerja sesuai tenggat waktu.',
        'reason' => 'Rekrutmen Terbuka Anggota Baru Divisi Post Production.',
        'status' => 'published',
        'icon' => 'movie_edit',
        'card_color' => '#7c3aed'
    ],
    [
        'id' => 'req-editorial-002',
        'req_number' => 'REQ-202608-002',
        'title' => 'Staf Penulis - Editorial Department',
        'department_id' => 'dept-editorial',
        'headcount' => 10,
        'employment_type' => 'full_time',
        'min_salary' => 0,
        'max_salary' => 0,
        'job_description' => 'Bertanggung jawab dalam naskah artikel, naskah narasi video, riset materi, serta penulisan caption publikasi.',
        'requirements' => '1. Suka menulis dan membaca.\n2. Mampu menyusun tata bahasa yang baik, komunikatif, dan kreatif.',
        'reason' => 'Rekrutmen Terbuka Anggota Baru Divisi Editorial.',
        'status' => 'published',
        'icon' => 'edit_note',
        'card_color' => '#059669'
    ],
    [
        'id' => 'req-broadcast-003',
        'req_number' => 'REQ-202608-003',
        'title' => 'Staf Host & Public Speaker - Broadcast Department',
        'department_id' => 'dept-broadcast',
        'headcount' => 10,
        'employment_type' => 'full_time',
        'min_salary' => 0,
        'max_salary' => 0,
        'job_description' => 'Bertanggung jawab menjadi pembawa acara (host), pewawancara, serta pembicara utama dalam program liputan & podcast ekstrakurikuler.',
        'requirements' => '1. Percaya diri tampil di depan kamera & publik.\n2. Berbicara dengan jelas, ramah, dan artikulatif.',
        'reason' => 'Rekrutmen Terbuka Anggota Baru Divisi Broadcast.',
        'status' => 'published',
        'icon' => 'mic',
        'card_color' => '#d97706'
    ],
    [
        'id' => 'req-visprod-004',
        'req_number' => 'REQ-202608-004',
        'title' => 'Staf Kameramen - Visual Production Department',
        'department_id' => 'dept-visprod',
        'headcount' => 10,
        'employment_type' => 'full_time',
        'min_salary' => 0,
        'max_salary' => 0,
        'job_description' => 'Bertanggung jawab atas pengambilan gambar, dokumentasi foto/video kegiatan, tata cahaya, serta operasional kamera.',
        'requirements' => '1. Menyukai bidang fotografi & videografi.\n2. Siap belajar mengoperasikan kamera dan mendokumentasikan acara.',
        'reason' => 'Rekrutmen Terbuka Anggota Baru Divisi Visual Production.',
        'status' => 'published',
        'icon' => 'videocam',
        'card_color' => '#e11d48'
    ]
];

// Ensure icon, card_color, and logo_url columns exist in job_requisitions table
try {
    $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT 'work'");
    $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS card_color VARCHAR(50) DEFAULT '#0c145e'");
    $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS logo_url VARCHAR(255) DEFAULT NULL");
} catch (\Exception $e) {}

// Get superadmin user ID for requested_by
$stmtAdmin = $db->query("SELECT id FROM users WHERE role = 'superadmin' LIMIT 1");
$adminId = $stmtAdmin->fetchColumn() ?: 'admin-default-id';

$stmtInsert = $db->prepare("
    INSERT INTO job_requisitions (
        id, req_number, title, department_id, requested_by, headcount,
        employment_type, min_salary, max_salary, job_description, requirements,
        reason, status, card_color, icon
    ) VALUES (
        :id, :req_number, :title, :department_id, :requested_by, :headcount,
        :employment_type, :min_salary, :max_salary, :job_description, :requirements,
        :reason, :status, :card_color, :icon
    ) ON DUPLICATE KEY UPDATE
        title = VALUES(title),
        department_id = VALUES(department_id),
        job_description = VALUES(job_description),
        requirements = VALUES(requirements),
        status = 'published'
");

foreach ($defaultJobs as $job) {
    $job['requested_by'] = $adminId;
    $stmtInsert->execute($job);
}

echo "Extracurricular job requisitions seeded successfully!\n";
