<?php
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables if they exist
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

use App\Config\Database;

// Define employee master list for fallback/mock data matching database seeders
$employees = [
    '0f850153-f9f8-4885-8526-0fda00000033' => [
        'employee_id' => 'EMP-2026-0033',
        'employee_name' => 'Alex Rivera',
        'email' => 'employee@mail.com',
        'pattern' => 'good'
    ],
    '0f850153-f9f8-4885-8526-0fda00000034' => [
        'employee_id' => 'EMP-2026-0034',
        'employee_name' => 'Budi Santoso',
        'email' => 'budi.santoso@example.com',
        'pattern' => 'good'
    ],
    '0f850153-f9f8-4885-8526-0fda00000035' => [
        'employee_id' => 'EMP-2026-0035',
        'employee_name' => 'Amanda Putri',
        'email' => 'amanda.putri@example.com',
        'pattern' => 'late'
    ],
    '0f850153-f9f8-4885-8526-0fda00000036' => [
        'employee_id' => 'EMP-2026-0036',
        'employee_name' => 'Rian Hidayat',
        'email' => 'rian.hidayat@example.com',
        'pattern' => 'absent'
    ],
    '0f850153-f9f8-4885-8526-0fda00000037' => [
        'employee_id' => 'EMP-2026-0037',
        'employee_name' => 'Siti Aminah',
        'email' => 'siti.aminah@example.com',
        'pattern' => 'good'
    ],
    '0f850153-f9f8-4885-8526-0fda00000038' => [
        'employee_id' => 'EMP-2026-0038',
        'employee_name' => 'Farhan Said',
        'email' => 'farhan.said@example.com',
        'pattern' => 'late'
    ],
];

$records = [];
$fromDb = false;

try {
    // Try to connect to Database
    $db = Database::getInstance()->getConnection();
    echo "Connected to database successfully. Fetching checklogs from employee_attendance table...\n";

    $stmt = $db->query("
        SELECT 
            a.id,
            a.attendance_date,
            a.clock_in,
            a.clock_out,
            a.status,
            a.clock_in_latitude,
            a.clock_in_longitude,
            a.clock_out_latitude,
            a.clock_out_longitude,
            a.location_method,
            a.ip_address,
            a.notes,
            u.employee_id,
            CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) AS employee_name,
            u.email
        FROM employee_attendance a
        JOIN users u ON a.user_id = u.id
        ORDER BY a.attendance_date DESC, u.first_name ASC
    ");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fromDb = true;

} catch (Exception $e) {
    echo "Database connection failed (" . $e->getMessage() . ").\n";
    echo "Falling back to generating deterministic mock checklogs matching the 30-day seeder pattern...\n";

    $officeIp = '192.168.10.45';
    $officeLat = -6.2297;
    $officeLng = 106.8164;

    // We will generate logs for the last 30 days deterministically
    // We use a fixed seed or logic so running it multiple times produces the same files
    mt_srand(42); // Seed random generator for determinism

    for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
        $timestamp = strtotime("2026-06-11 -{$daysAgo} days"); // Anchored around 2026-06-11
        $date = date('Y-m-d', $timestamp);
        $dayOfWeek = date('N', $timestamp); // 1=Mon..7=Sun
        if ($dayOfWeek >= 6) continue; // skip weekends

        foreach ($employees as $userId => $info) {
            $pattern = $info['pattern'];
            $rand = mt_rand(1, 100);

            $record = [
                'id' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
                'attendance_date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'status' => 'alpa',
                'clock_in_latitude' => null,
                'clock_in_longitude' => null,
                'clock_out_latitude' => null,
                'clock_out_longitude' => null,
                'location_method' => null,
                'ip_address' => null,
                'notes' => null,
                'employee_id' => $info['employee_id'],
                'employee_name' => $info['employee_name'],
                'email' => $info['email']
            ];

            if ($pattern === 'absent' && $rand <= 20) {
                $record['status'] = 'sakit/izin';
            } elseif ($pattern === 'late' && $rand <= 40) {
                $lateMin = mt_rand(5, 90);
                $baseH = 8; $baseM = 0;
                $totalMin = $baseH * 60 + $baseM + $lateMin;
                $record['clock_in'] = sprintf('%02d:%02d:00', intdiv($totalMin, 60), $totalMin % 60);
                $record['clock_out'] = sprintf('%02d:%02d:00', mt_rand(16, 18), mt_rand(0, 59));
                $record['status'] = 'terlambat';
                $record['clock_in_latitude'] = $officeLat + (mt_rand(-5, 5) / 10000);
                $record['clock_in_longitude'] = $officeLng + (mt_rand(-5, 5) / 10000);
                $record['clock_out_latitude'] = $officeLat + (mt_rand(-5, 5) / 10000);
                $record['clock_out_longitude'] = $officeLng + (mt_rand(-5, 5) / 10000);
                $record['location_method'] = 'GPS';
                $record['ip_address'] = $officeIp;
            } elseif ($rand <= 5) {
                $record['status'] = 'alpa';
            } else {
                $earlyMin = mt_rand(0, 25);
                $clockInH = 8; $clockInM = 0;
                $totalMin = $clockInH * 60 + $clockInM - $earlyMin;
                $record['clock_in'] = sprintf('%02d:%02d:00', intdiv($totalMin, 60), $totalMin % 60);
                $record['clock_out'] = sprintf('%02d:%02d:00', mt_rand(17, 19), mt_rand(0, 59));
                $record['status'] = 'tepat waktu';
                $record['clock_in_latitude'] = $officeLat + (mt_rand(-3, 3) / 10000);
                $record['clock_in_longitude'] = $officeLng + (mt_rand(-3, 3) / 10000);
                $record['clock_out_latitude'] = $officeLat + (mt_rand(-3, 3) / 10000);
                $record['clock_out_longitude'] = $officeLng + (mt_rand(-3, 3) / 10000);
                $record['location_method'] = (mt_rand(0, 1) === 0) ? 'WIFI' : 'GPS';
                $record['ip_address'] = $officeIp;
            }

            $records[] = $record;
        }
    }

    // Sort records in reverse date order, then employee name order (to mimic original SQL ORDER BY)
    usort($records, function($a, $b) {
        $dateCompare = strcmp($b['attendance_date'], $a['attendance_date']);
        if ($dateCompare !== 0) {
            return $dateCompare;
        }
        return strcmp($a['employee_name'], $b['employee_name']);
    });
}

// Ensure files are saved
// 1. JSON
$jsonPath = __DIR__ . '/../checklog.json';
file_put_contents($jsonPath, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved JSON to: checklog.json (" . count($records) . " records)\n";

// 2. TXT
$txtPath = __DIR__ . '/../checklog.txt';
$txtContent = "=== SI CARE HRMS PAYROLL - ATTENDANCE CHECKLOG ===\n";
$txtContent .= "Generated at: " . date('Y-m-d H:i:s') . " " . ($fromDb ? "(from database)" : "(mock fallback data)") . "\n";
$txtContent .= "Total records: " . count($records) . "\n\n";
$txtContent .= str_pad("Date", 12) . " | " . str_pad("ID Staff", 15) . " | " . str_pad("Employee Name", 25) . " | " . str_pad("Clock In", 10) . " | " . str_pad("Clock Out", 10) . " | " . str_pad("Status", 15) . " | Method | IP Address\n";
$txtContent .= str_repeat("-", 120) . "\n";
foreach ($records as $r) {
    $txtContent .= str_pad($r['attendance_date'], 12) . " | " . 
                   str_pad($r['employee_id'] ?? 'N/A', 15) . " | " . 
                   str_pad(substr($r['employee_name'], 0, 25), 25) . " | " . 
                   str_pad($r['clock_in'] ?? '--:--:--', 10) . " | " . 
                   str_pad($r['clock_out'] ?? '--:--:--', 10) . " | " . 
                   str_pad($r['status'], 15) . " | " . 
                   str_pad($r['location_method'] ?? 'N/A', 6) . " | " . 
                   ($r['ip_address'] ?? 'N/A') . "\n";
}
file_put_contents($txtPath, $txtContent);
echo "Saved TXT to: checklog.txt\n";

// 3. MD
$mdPath = __DIR__ . '/../checklog.md';
$mdContent = "# siCare Attendance Checklog\n\n";
$mdContent .= "*Generated on: " . date('Y-m-d H:i:s') . " " . ($fromDb ? "(from database)" : "(mock fallback data)") . "*\n";
$mdContent .= "*Total Records: " . count($records) . "*\n\n";
$mdContent .= "| Date | Employee ID | Employee Name | Clock In | Clock Out | Status | Method | IP Address |\n";
$mdContent .= "| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |\n";
foreach ($records as $r) {
    $mdContent .= "| {$r['attendance_date']} | " . 
                   ($r['employee_id'] ?? 'N/A') . " | " . 
                   $r['employee_name'] . " | " . 
                   ($r['clock_in'] ?? '--:--:--') . " | " . 
                   ($r['clock_out'] ?? '--:--:--') . " | " . 
                   $r['status'] . " | " . 
                   ($r['location_method'] ?? 'N/A') . " | " . 
                   ($r['ip_address'] ?? 'N/A') . " |\n";
}
file_put_contents($mdPath, $mdContent);
echo "Saved MD to: checklog.md\n";
