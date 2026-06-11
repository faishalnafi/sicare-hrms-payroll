<?php
// Check quarterly self-reflection compliance before letting them access finance page
$db = \App\Config\Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? '';
$currentPeriod = date('Y') . '-Q' . ceil(date('n') / 3);

$stmt = $db->prepare("SELECT COUNT(*) FROM self_reflections WHERE user_id = :user_id AND period = :period AND status IN ('submitted', 'completed')");
$stmt->execute(['user_id' => $userId, 'period' => $currentPeriod]);
$hasQuarterly = $stmt->fetchColumn() > 0;

if (!$hasQuarterly) {
    echo "<div class='p-6 text-red-650 font-bold bg-red-50 border border-red-200 rounded-2xl shadow-sm flex items-center gap-3'>
            <span class='material-symbols-outlined text-red-600'>lock</span>
            <div>
                <h4 class='text-sm text-red-900'>Akses Ditolak: Refleksi Kinerja Belum Diisi</h4>
                <p class='text-xs text-red-800 mt-0.5 font-medium'>Anda wajib menyelesaikan pengisian Refleksi Kinerja & Rencana Karir (IDP) untuk kuartal ini terlebih dahulu sebelum dapat mengakses menu finansial mandiri atau melihat draf slip gaji Anda.</p>
            </div>
          </div>";
    return;
}

$sessName  = $_SESSION['name'] ?? 'Alex Rivera';
$sessEmail = $_SESSION['email'] ?? 'alex.rivera@example.com';
$sessRole  = $_SESSION['role'] ?? 'employee';

// Fetch dynamic user data from DB
$db = \App\Config\Database::getInstance()->getConnection();
$companyName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_company_name' LIMIT 1")->fetchColumn() ?: 'PT SI CARE ENTERPRISE';
$companyNameForStamp = $companyName;
if (stripos($companyName, 'Mango') !== false) {
    $companyNameForStamp = 'PT. MANGO TEKNUSA INOVASI';
}
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
$appLogoIcon = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_icon' LIMIT 1")->fetchColumn() ?: 'local_police';
$appLogoType = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_type' LIMIT 1")->fetchColumn() ?: 'icon';
$appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';

$bpjsTkPct = (float)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'payroll_bpjs_tk_pct' LIMIT 1")->fetchColumn() ?: 2);
$bpjsKesPct = (float)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'payroll_bpjs_kes_pct' LIMIT 1")->fetchColumn() ?: 1);
$pph21Pct = (float)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'payroll_pph21_pct' LIMIT 1")->fetchColumn() ?: 2.5);

// Fetch HR Director (Hiring Manager of HR department) dynamically, fallback to Zanuba Arifatul Khafsoh
$hrQuery = $db->query("
    SELECT u.first_name, u.last_name, u.job_title 
    FROM users u
    JOIN departments d ON u.department_id = d.id
    WHERE u.role = 'hiring_manager' AND (d.name LIKE '%Human Resources%' OR d.name LIKE '%HR%')
    LIMIT 1
");
$hrDirectorData = $hrQuery->fetch();
$hrDirectorName = $hrDirectorData ? trim($hrDirectorData['first_name'] . ' ' . ($hrDirectorData['last_name'] ?? '')) : 'Zanuba Arifatul Khafsoh';
$hrDirectorTitle = $hrDirectorData ? ($hrDirectorData['job_title'] ?? 'HR Operations Director') : 'HR Operations Director';

$userQuery = $db->prepare("
    SELECT u.*, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.id = :id
");
$userQuery->execute(['id' => $_SESSION['user_id']]);
$dbUser = $userQuery->fetch();

$jobTitle = $dbUser['job_title'] ?? 'Karyawan';
$deptName = $dbUser['department_name'] ?? '';
$jobAndDept = !empty($deptName) ? "{$jobTitle} / {$deptName}" : $jobTitle;

$employeeId = $dbUser['employee_id'] ?? 'EMP-2026-0033';
$ktpNik = $dbUser['ktp_nik'] ?? '3275012309990001';
$namaSesuaiKtp = $dbUser['nama_sesuai_ktp'] ?? $sessName;
$rawBankName = $dbUser['bank_name'] ?? '';
$rawBankAccount = $dbUser['bank_account_number'] ?? '';
$isBankEmpty = empty($rawBankName) || $rawBankName === '-' || empty($rawBankAccount) || $rawBankAccount === '-';
$bankName = !$isBankEmpty ? $rawBankName : '';
$bankAccountNumber = !$isBankEmpty ? $rawBankAccount : '';
$npwpNumber = !empty($dbUser['npwp_number']) && trim($dbUser['npwp_number']) !== '-' ? trim($dbUser['npwp_number']) : '-';
$bpjsTk = !empty($dbUser['bpjs_tk']) && trim($dbUser['bpjs_tk']) !== '-' ? trim($dbUser['bpjs_tk']) : '-';
$bpjsKes = !empty($dbUser['bpjs_kes']) && trim($dbUser['bpjs_kes']) !== '-' ? trim($dbUser['bpjs_kes']) : '-';
$tanggalLahir = $dbUser['tanggal_lahir'] ?? '12 September 1995';
$statusPernikahan = $dbUser['status_pernikahan'] ?? 'Belum Menikah';
$jenisKelamin = $dbUser['jenis_kelamin'] ?? 'Laki-Laki';

// Base Salary setup based on profile
$baseSalary = !empty($dbUser['base_salary']) ? (float)$dbUser['base_salary'] : 0;

// ======================================================
// FETCH APPROVED PAYROLL RECORDS FROM DATABASE
// Only show Approved or Paid records for the current year
// ======================================================
$currentYear = date('Y');

// Fetch all approved payroll records for this user in the current year
$payrollStmt = $db->prepare("
    SELECT ep.*
    FROM employee_payroll ep
    WHERE ep.user_id = :user_id
      AND ep.status IN ('Approved', 'Paid')
      AND ep.month_year LIKE :year_pattern
    ORDER BY ep.month_year DESC
");
$payrollStmt->execute([
    'user_id'      => $_SESSION['user_id'],
    'year_pattern' => '%-' . $currentYear,
]);
$approvedPayrolls = $payrollStmt->fetchAll(\PDO::FETCH_ASSOC);

// Build months array from DB records
$months = [];
$monthNames = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
    '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
    '10' => 'Oktober', '11' => 'November',  '12' => 'Desember'
];

foreach ($approvedPayrolls as $record) {
    // month_year format: MM-YYYY
    $parts = explode('-', $record['month_year']);
    $mm = $parts[0] ?? '01';
    $yyyy = $parts[1] ?? $currentYear;
    $monthName = ($monthNames[$mm] ?? $mm) . ' ' . $yyyy;

    // Format payment_date (approved/transfer date)
    $paymentDateRaw = $record['payment_date'] ?? null;
    $paymentDateFormatted = '';
    if ($paymentDateRaw) {
        $ts = strtotime($paymentDateRaw);
        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $paymentDateFormatted = date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }

    // Calculate dynamic working days count
    $workingDaysCount = 22; // default
    $weeklyHolidays = ['Sat', 'Sun'];
    $val = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'weekly_holidays' LIMIT 1")->fetchColumn();
    if (!empty($val)) {
        $weeklyHolidays = array_filter(array_map('trim', explode(',', $val)));
    }
    $stmtHolidays = $db->prepare("
        SELECT holiday_date 
        FROM company_holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ?
    ");
    $stmtHolidays->execute([intval($mm), intval($yyyy)]);
    $companyHolidays = $stmtHolidays->fetchAll(\PDO::FETCH_COLUMN) ?: [];

    $daysInMonth = (int)date('t', strtotime(sprintf('%04d-%02d-01', intval($yyyy), intval($mm))));
    $workingDays = 0;
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dateStr = sprintf('%04d-%02d-%02d', intval($yyyy), intval($mm), $day);
        $ts = strtotime($dateStr);
        $dayName = date('D', $ts);
        if (!in_array($dayName, $weeklyHolidays) && !in_array($dateStr, $companyHolidays)) {
            $workingDays++;
        }
    }
    $workingDaysCount = $workingDays > 0 ? $workingDays : 22;

    // Calculate present days (clock-in)
    $stmtPresent = $db->prepare("
        SELECT COUNT(*) 
        FROM employee_attendance 
        WHERE user_id = :user_id 
          AND status IN ('tepat waktu', 'awal', 'terlambat')
          AND MONTH(attendance_date) = :month 
          AND YEAR(attendance_date) = :year
    ");
    $stmtPresent->execute([
        'user_id' => $_SESSION['user_id'],
        'month'   => intval($mm),
        'year'    => intval($yyyy)
    ]);
    $presentDaysCount = (int)$stmtPresent->fetchColumn();

    // Calculate unpaid leave days
    $stmtLeaves = $db->prepare("
        SELECT start_date, end_date 
        FROM employee_leave_requests
        WHERE user_id = :user_id
          AND status = 'approved'
          AND LOWER(leave_type) = 'cuti tidak dibayar'
          AND start_date <= :last_day
          AND end_date >= :first_day
    ");
    $firstDay = sprintf('%04d-%02d-01', intval($yyyy), intval($mm));
    $lastDay = date('Y-m-t', strtotime($firstDay));
    $stmtLeaves->execute([
        'user_id' => $_SESSION['user_id'],
        'first_day' => $firstDay,
        'last_day' => $lastDay
    ]);
    $leaves = $stmtLeaves->fetchAll(\PDO::FETCH_ASSOC);
    $unpaidDays = 0;
    foreach ($leaves as $l) {
        $start = max(strtotime($firstDay), strtotime($l['start_date']));
        $end = min(strtotime($lastDay), strtotime($l['end_date']));
        for ($curr = $start; $curr <= $end; $curr = strtotime('+1 day', $curr)) {
            $currDateStr = date('Y-m-d', $curr);
            $dayName = date('D', $curr);
            if (!in_array($dayName, $weeklyHolidays) && !in_array($currDateStr, $companyHolidays)) {
                $unpaidDays++;
            }
        }
    }

    // Calculate alpa days
    $stmtAlpa = $db->prepare("
        SELECT COUNT(*) 
        FROM employee_attendance
        WHERE user_id = :user_id
          AND status = 'alpa'
          AND MONTH(attendance_date) = :month
          AND YEAR(attendance_date) = :year
    ");
    $stmtAlpa->execute([
        'user_id' => $_SESSION['user_id'],
        'month' => intval($mm),
        'year' => intval($yyyy)
    ]);
    $alpaDays = (int)$stmtAlpa->fetchColumn();

    // Calculate late days
    $stmtLate = $db->prepare("
        SELECT COUNT(*) 
        FROM employee_attendance
        WHERE user_id = :user_id
          AND status = 'terlambat'
          AND MONTH(attendance_date) = :month
          AND YEAR(attendance_date) = :year
    ");
    $stmtLate->execute([
        'user_id' => $_SESSION['user_id'],
        'month' => intval($mm),
        'year' => intval($yyyy)
    ]);
    $lateDays = (int)$stmtLate->fetchColumn();

    // Calculate deductions
    $unpaidDeduction = 0.0;
    if ($workingDaysCount > 0 && $unpaidDays > 0) {
        $unpaidDeduction = ($record['base_salary'] / $workingDaysCount) * $unpaidDays;
    }
    $alpaDeduction = 0.0;
    if ($workingDaysCount > 0 && $alpaDays > 0) {
        $alpaDeduction = ($record['base_salary'] / $workingDaysCount) * $alpaDays;
    }
    $latePenalty = 50000.0;
    $latePenaltyVal = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'payroll_late_deduction' LIMIT 1")->fetchColumn();
    if ($latePenaltyVal !== false && $latePenaltyVal !== null) {
        $latePenalty = (float)$latePenaltyVal;
    }
    $lateDeduction = $lateDays * $latePenalty;

    $tunjanganTotal = (float)$record['tunj_jabatan'] + (float)$record['tunj_transport_makan'] + (float)$record['tunj_komunikasi'] + (float)$record['bonus'] + (float)$record['reimbursement'];
    $totDed = (float)$record['bpjs_tk'] + (float)$record['bpjs_kes'] + (float)$record['pph21'] + (float)$record['other_deduction'];
    $grossEarning = (float)$record['base_salary'] + $tunjanganTotal + (float)$record['overtime'];
    $netPay = (float)$record['net_salary'];

    $months[] = [
        'id'                     => $record['id'],
        'code'                   => $record['month_year'],
        'month_name'             => $monthName,
        'date'                   => $paymentDateFormatted,
        'status'                 => $record['status'],
        'base_salary'            => (float)$record['base_salary'],
        'tunj_jabatan'           => (float)$record['tunj_jabatan'],
        'tunj_transport_makan'   => (float)$record['tunj_transport_makan'],
        'tunj_komunikasi'        => (float)$record['tunj_komunikasi'],
        'bonus'                  => (float)$record['bonus'],
        'thr'                    => 0,
        'overtime'               => (float)$record['overtime'],
        'reimbursement'          => (float)$record['reimbursement'],
        'other_deduction'        => (float)$record['other_deduction'],
        'unpaid_leave_days'      => $unpaidDays,
        'unpaid_leave_deduction' => $unpaidDeduction,
        'alpa_days'              => $alpaDays,
        'alpa_deduction'         => $alpaDeduction,
        'late_days'              => $lateDays,
        'late_deduction'         => $lateDeduction,
        'bpjs_tk'                => (float)$record['bpjs_tk'],
        'bpjs_kes'               => (float)$record['bpjs_kes'],
        'pph21'                  => (float)$record['pph21'],
        'tunjangan_total'        => $tunjanganTotal,
        'total_deductions'       => $totDed + $unpaidDeduction + $alpaDeduction + $lateDeduction,
        'gross_earning'          => $grossEarning - $unpaidDeduction - $alpaDeduction - $lateDeduction,
        'net_pay'                => $netPay,
        'working_days'           => $workingDaysCount,
        'present_days'           => $presentDaysCount,
    ];
}

// Latest month (most recent approved payroll)
$hasApprovedPayroll = !empty($months);
$latest = $hasApprovedPayroll ? $months[0] : null;
$latestNetPay = $latest ? $latest['net_pay'] : 0;
$latestDate = $latest ? $latest['date'] : '';
$latestBaseSalary = $latest ? $latest['base_salary'] : $baseSalary;
?>

<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 1.27cm; /* Narrow margin */
        }
        html, body.printing-active {
            background: white !important;
            background-color: white !important;
        }
        body.printing-active > :not(#printArea) {
            display: none !important;
        }
        body.printing-active #printArea {
            display: block !important;
            background: white !important;
            color: black !important;
            width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        body.printing-active #printArea * {
            visibility: visible !important;
        }
        .no-print {
            display: none !important;
        }
        
        /* Force print layout styles to maintain side-by-side structure */
        #printArea .print-row {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        #printArea .print-row-end {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: flex-end !important;
        }
        #printArea .print-grid-4 {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 1.5rem !important;
        }
        #printArea .print-grid-6 {
            display: grid !important;
            grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
            gap: 1.5rem !important;
        }
        #printArea .print-grid-2 {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 2rem !important;
        }
    }
</style>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight mt-1">Finansial Mandiri</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola slip gaji bulanan, rekening payroll, kepesertaan sosial, dan dokumen perpajakan Anda.</p>
        </div>
    </div>

    <!-- Bento Grid Metrik Keuangan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Bento Card 1: Last Salary Received -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.03)] relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-primary/5 rounded-full blur-xl group-hover:bg-primary/10 transition-colors duration-300"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Gaji Bersih Bulan Ini</span>
                <span class="material-symbols-outlined text-primary text-2xl bg-primary/5 p-2 rounded-xl">payments</span>
            </div>
            <div class="mt-4">
                <?php if ($hasApprovedPayroll): ?>
                    <h3 class="text-3xl font-black text-on-surface font-headline tracking-tight">
                        Rp <?= number_format($latestNetPay, 0, ',', '.') ?>
                    </h3>
                    <p class="text-[11px] font-semibold text-green-600 flex items-center gap-1 mt-1">
                        <span class="material-symbols-outlined text-xs">check_circle</span>
                        Ditransfer pada <?= htmlspecialchars($latestDate) ?>
                    </p>
                <?php else: ?>
                    <h3 class="text-2xl font-black text-on-surface-variant/60 font-headline tracking-tight">
                        —
                    </h3>
                    <p class="text-[11px] font-semibold text-on-surface-variant/60 flex items-center gap-1 mt-1">
                        <span class="material-symbols-outlined text-xs">schedule</span>
                        Menunggu approval payroll
                    </p>
                <?php endif; ?>
            </div>
            <div class="mt-4 pt-4 border-t border-outline-variant/10 text-xs text-on-surface-variant flex justify-between font-medium">
                <span>Metode Transfer</span>
                <span class="font-bold text-on-surface"><?= $hasApprovedPayroll ? 'Payroll Auto-Credit' : '-' ?></span>
            </div>
        </div>

        <!-- Bento Card 2: Payroll Bank Account -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.03)] relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-blue-600/5 rounded-full blur-xl group-hover:bg-blue-600/10 transition-colors duration-300"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Rekening Penerima Payroll</span>
                <span class="material-symbols-outlined text-blue-700 text-2xl bg-blue-500/5 p-2 rounded-xl">account_balance</span>
            </div>
            <div class="mt-4">
                <?php if (!$isBankEmpty): ?>
                    <h3 class="text-xl font-extrabold text-on-surface font-headline leading-tight">
                        <?= htmlspecialchars($bankName) ?>
                    </h3>
                    <p class="text-xs font-mono font-bold text-primary bg-primary/5 px-2 py-0.5 rounded inline-block mt-1">
                        <?= htmlspecialchars($bankAccountNumber) ?>
                    </p>
                <?php else: ?>
                    <h3 class="text-2xl font-black text-on-surface-variant/60 font-headline tracking-tight">
                        —
                    </h3>
                    <p class="text-[11px] font-semibold text-on-surface-variant/60 flex items-center gap-1 mt-1">
                        <span class="material-symbols-outlined text-xs">schedule</span>
                        Rekening belum terkonfigurasi
                    </p>
                <?php endif; ?>
            </div>
            <div class="mt-4 pt-4 border-t border-outline-variant/10 text-xs text-on-surface-variant flex justify-between font-medium">
                <span>Atas Nama</span>
                <span class="font-bold text-on-surface uppercase"><?= !$isBankEmpty ? htmlspecialchars($namaSesuaiKtp) : '-' ?></span>
            </div>
        </div>

        <!-- Bento Card 3: Social & Tax Membership -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.03)] relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-600/5 rounded-full blur-xl group-hover:bg-indigo-600/10 transition-colors duration-300"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Proteksi Sosial & Pajak</span>
                <span class="material-symbols-outlined text-indigo-700 text-2xl bg-indigo-500/5 p-2 rounded-xl">verified_user</span>
            </div>
            <div class="mt-3 space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-on-surface-variant font-medium">NPWP</span>
                    <span class="font-mono font-bold text-on-surface flex items-center gap-1">
                        <?= htmlspecialchars($npwpNumber) ?>
                        <?php if ($npwpNumber !== '-'): ?>
                            <span class="material-symbols-outlined text-green-500 text-sm font-bold">verified</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-on-surface-variant font-medium">BPJS Ketenagakerjaan</span>
                    <span class="font-mono font-bold text-on-surface flex items-center gap-1">
                        <?= htmlspecialchars($bpjsTk) ?>
                        <?php if ($bpjsTk !== '-'): ?>
                            <span class="material-symbols-outlined text-green-500 text-sm font-bold">verified</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-on-surface-variant font-medium">BPJS Kesehatan</span>
                    <span class="font-mono font-bold text-on-surface flex items-center gap-1">
                        <?= htmlspecialchars($bpjsKes) ?>
                        <?php if ($bpjsKes !== '-'): ?>
                            <span class="material-symbols-outlined text-green-500 text-sm font-bold">verified</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left: Payslips History Table -->
        <div class="lg:col-span-8 bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.02)] overflow-hidden">
            <div class="p-6 border-b border-outline-variant/10 flex justify-between items-center bg-gradient-to-r from-surface-container-lowest to-surface-container-low/30">
                <div>
                    <h2 class="text-lg font-extrabold text-on-surface font-headline">Riwayat Slip Gaji</h2>
                    <p class="text-xs text-on-surface-variant mt-0.5">Daftar slip gaji resmi yang telah disetujui untuk tahun <?= $currentYear ?>.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 border-b border-outline-variant/10">
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Periode</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Gaji Pokok</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Tunjangan</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Potongan</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Gaji Bersih (THP)</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/8">
                        <?php if (empty($months)): ?>
                        <tr>
                            <td colspan="6" class="p-10 text-center">
                                <div class="flex flex-col items-center gap-3 text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl opacity-40">receipt_long</span>
                                    <p class="text-sm font-medium">Belum ada slip gaji yang disetujui untuk tahun <?= $currentYear ?>.</p>
                                    <p class="text-xs opacity-70">Slip gaji akan muncul setelah diproses dan disetujui oleh HR.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($months as $m): ?>
                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                            <td class="p-4 font-bold text-xs text-on-surface"><?= htmlspecialchars($m['month_name']) ?></td>
                            <td class="p-4 text-xs font-mono font-medium text-on-surface-variant">Rp <?= number_format($m['base_salary'], 0, ',', '.') ?></td>
                            <td class="p-4 text-xs font-mono font-medium text-green-700 bg-green-50/40">Rp <?= number_format($m['tunjangan_total'], 0, ',', '.') ?></td>
                            <td class="p-4 text-xs font-mono font-medium text-red-600 bg-red-50/20">Rp <?= number_format($m['total_deductions'], 0, ',', '.') ?></td>
                            <td class="p-4 text-xs font-mono font-bold text-primary">Rp <?= number_format($m['net_pay'], 0, ',', '.') ?></td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="previewPayslip('<?= htmlspecialchars($m['code']) ?>')" class="bg-primary/5 hover:bg-primary/10 text-primary p-2 rounded-lg transition-all hover:scale-105 active:scale-95" title="Pratinjau Slip">
                                        <span class="material-symbols-outlined text-sm font-bold">visibility</span>
                                    </button>
                                    <button onclick="printPayslipDirect('<?= htmlspecialchars($m['code']) ?>')" class="bg-primary/5 hover:bg-primary/10 text-primary p-2 rounded-lg transition-all hover:scale-105 active:scale-95" title="Cetak Slip">
                                        <span class="material-symbols-outlined text-sm font-bold">print</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Tax Documents Section -->
        <div class="lg:col-span-4 bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.02)] p-6 space-y-6">
            <div>
                <h2 class="text-lg font-extrabold text-on-surface font-headline">Dokumen Perpajakan</h2>
                <p class="text-xs text-on-surface-variant mt-0.5">Unduh dan tinjau berkas bukti potong pajak penghasilan tahunan Anda.</p>
            </div>

            <!-- Tax Card 1721-A1 -->
            <div class="bg-gradient-to-br from-indigo-550 to-primary text-white rounded-xl p-5 relative overflow-hidden shadow-md group">
                <div class="absolute -top-12 -right-12 w-28 h-28 bg-white/5 rounded-full blur-lg group-hover:bg-white/10 transition-colors"></div>
                <div class="relative z-10 space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="bg-white/20 text-white font-extrabold text-[9px] tracking-wider px-2 py-0.5 rounded uppercase">RESMI DJP</span>
                            <h4 class="text-md font-bold font-headline mt-1.5 leading-tight">Bukti Potong PPh 21<br>Formulir 1721-A1</h4>
                        </div>
                        <span class="material-symbols-outlined text-3xl opacity-75">assignment_turned_in</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs text-white/80">
                            <span>Tahun Pajak</span>
                            <span class="font-bold text-white">2025</span>
                        </div>
                        <div class="flex justify-between text-xs text-white/80">
                            <span>Status Laporan</span>
                            <span class="font-bold text-green-300 flex items-center gap-1 text-[11px]">
                                <span class="material-symbols-outlined text-xs">verified</span> SELESAI
                            </span>
                        </div>
                    </div>
                    <button onclick="previewTaxA1()" class="w-full bg-white text-primary hover:bg-white/95 font-extrabold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-all shadow-sm hover:scale-[1.02] active:scale-95 duration-200">
                        <span class="material-symbols-outlined text-sm">visibility</span> Pratinjau Form 1721-A1
                    </button>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/10 text-xs text-on-surface-variant leading-relaxed">
                <div class="flex gap-2">
                    <span class="material-symbols-outlined text-primary text-lg flex-shrink-0">info</span>
                    <div>
                        <span class="font-bold text-on-surface">Pelaporan SPT Tahunan:</span> Bukti potong Formulir 1721-A1 merupakan dokumen wajib untuk melakukan pelaporan SPT Tahunan Wajib Pajak Orang Pribadi melalui e-Filing DJP Online.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- DETAILED PAYSLIP PREVIEW MODAL -->
<!-- ============================================== -->
<div id="payslipModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
    <div class="bg-white text-black w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden scale-95 transform transition-transform duration-300 border border-neutral-200" id="payslipModalContainer">
        <!-- Modal Toolbar -->
        <div class="bg-neutral-100 px-6 py-3 border-b border-neutral-200 flex justify-between items-center no-print">
            <span class="text-xs font-bold text-neutral-600 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">print_disabled</span> Mode Pratinjau Dokumen Resmi
            </span>
            <div class="flex items-center gap-2">
                <button onclick="printPayslipFromModal()" class="bg-primary text-white hover:bg-primary/95 font-bold text-xs py-2 px-4 rounded-lg flex items-center gap-1.5 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-sm">print</span> Cetak / Unduh PDF
                </button>
                <button onclick="closePayslipModal()" class="bg-neutral-200 text-neutral-700 hover:bg-neutral-300 font-bold text-xs py-2 px-3 rounded-lg flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-sm">close</span> Tutup
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Content (The Actual Payslip) -->
        <div class="p-8 md:p-12 overflow-y-auto bg-white flex-grow relative" id="payslipPrintArea">

            <!-- Header Corporate -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b-2 border-neutral-900 pb-6 gap-4 print-row">
                <div class="flex items-center gap-3">
                    <?php if ($appLogoType === 'image' && !empty($appLogoImage)): ?>
                        <div class="bg-white border border-neutral-200 p-1.5 rounded-xl flex items-center justify-center shadow-sm">
                            <img src="<?= htmlspecialchars($appLogoImage) ?>" class="h-9 w-9 object-contain" alt="Logo" />
                        </div>
                    <?php else: ?>
                        <div class="bg-primary text-white p-2.5 rounded-xl flex items-center justify-center shadow-md">
                            <span class="material-symbols-outlined text-2xl font-bold"><?= htmlspecialchars($appLogoIcon) ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h2 class="text-2xl font-black text-neutral-900 tracking-tight leading-none"><?= htmlspecialchars($appName) ?></h2>
                        <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-widest mt-1 block"><?= htmlspecialchars($companyName) ?></span>
                    </div>
                </div>
                <div class="text-left md:text-right">
                    <h3 class="text-lg font-black text-neutral-900 uppercase tracking-wider">SLIP GAJI KARYAWAN</h3>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5" id="payslipNo">SLIP/PAY/20260525-0033</p>
                </div>
            </div>

            <!-- Employee & General Info Metadata Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 py-6 border-b border-neutral-250 text-xs print-grid-6">
                <div>
                    <span class="text-neutral-500 block mb-1">ID KARYAWAN</span>
                    <span class="font-mono font-bold text-neutral-800" id="payEmpId"><?= htmlspecialchars($employeeId) ?></span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">NAMA KARYAWAN</span>
                    <span class="font-bold text-neutral-800 uppercase" id="payEmpName"><?= htmlspecialchars($namaSesuaiKtp) ?></span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">JABATAN / DIVISI</span>
                    <span class="font-bold text-neutral-800" id="payEmpDept"><?= htmlspecialchars($jobAndDept) ?></span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">NPWP</span>
                    <span class="font-mono font-bold text-neutral-800" id="payNpwp"><?= htmlspecialchars($npwpNumber) ?></span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">HARI KERJA AKTIF</span>
                    <span class="font-bold text-neutral-800" id="payWorkDays">-</span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">PERIODE PEMBAYARAN</span>
                    <span class="font-bold text-neutral-800" id="payPeriod">Mei 2026</span>
                </div>
            </div>

            <!-- Financial Details Structure -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-6 items-start print-grid-2">
                <!-- EARNINGS (PENDAPATAN) -->
                <div class="space-y-4">
                    <div class="bg-neutral-100 px-4 py-2 border-l-4 border-green-600">
                        <h4 class="text-xs font-bold text-neutral-850 uppercase tracking-wider">I. PENDAPATAN (EARNINGS)</h4>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Gaji Pokok (Base Salary)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBase">Rp 16.500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Jabatan</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payTunjJabatan">Rp 2.500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Transportasi & Makan</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payTunjTransport">Rp 1.500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Komunikasi</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payTunjKomunikasi">Rp 500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payBonusRow">
                            <span class="text-neutral-600">Bonus & Insentif Kerja</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBonus">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payThrRow">
                            <span class="text-neutral-600">Tunjangan Hari Raya (THR)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payThr">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payOvertimeRow">
                            <span class="text-neutral-600">Lemburan</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payOvertime">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- DEDUCTIONS (POTONGAN) -->
                <div class="space-y-4">
                    <div class="bg-neutral-100 px-4 py-2 border-l-4 border-red-600">
                        <h4 class="text-xs font-bold text-neutral-850 uppercase tracking-wider">II. POTONGAN (DEDUCTIONS)</h4>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">BPJS Ketenagakerjaan (<?= $bpjsTkPct ?>%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBpjsTk">Rp 330.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">BPJS Kesehatan (<?= $bpjsKesPct ?>%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBpjsKes">Rp 165.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Pajak Penghasilan (PPh 21 - <?= $pph21Pct ?>%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payPph21">Rp 425.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payUnpaidDeductionRow">
                            <span class="text-neutral-600 flex items-center gap-1">
                                Potongan Cuti Tidak Dibayar 
                                <span class="bg-red-50 text-red-700 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-red-100" id="payUnpaidDaysBadge">0 Hari</span>
                            </span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="payUnpaidDeduction">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payAlpaDeductionRow">
                            <span class="text-neutral-600 flex items-center gap-1">
                                Potongan Alpa 
                                <span class="bg-red-50 text-red-700 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-red-100" id="payAlpaDaysBadge">0 Hari</span>
                            </span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="payAlpaDeduction">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payLateDeductionRow">
                            <span class="text-neutral-600 flex items-center gap-1">
                                Potongan Keterlambatan 
                                <span class="bg-red-50 text-red-700 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-red-100" id="payLateDaysBadge">0 Kali</span>
                            </span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="payLateDeduction">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payOtherDeductionRow">
                            <span class="text-neutral-600">Potongan Lainnya</span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="payOtherDeduction">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOTAL SUMMARY PANEL -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 pb-6 border-t border-b border-neutral-250 print-grid-2">
                <div class="space-y-2">
                    <div class="flex justify-between text-xs font-bold text-neutral-700">
                        <span>TOTAL PENDAPATAN BRUTO</span>
                        <span class="font-mono" id="payGrossTotal">Rp 21.000.000</span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-red-600">
                        <span>TOTAL POTONGAN KARYAWAN</span>
                        <span class="font-mono" id="payDeductionsTotal">Rp 920.000</span>
                    </div>
                </div>
                <div class="bg-neutral-50 p-4 rounded-xl flex flex-col justify-center border border-neutral-200">
                    <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-widest">TAKE HOME PAY (GAJI BERSIH)</span>
                    <span class="text-2xl font-black text-primary font-headline mt-1 tracking-tight" id="payNetTotal">
                        Rp 20.080.000
                    </span>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="pt-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 text-xs text-neutral-700 print-row-end">
                <div>
                    <span class="text-neutral-500 block mb-1">REKENING PAYROLL TUJUAN</span>
                    <span class="font-bold text-neutral-800" id="payBankInfo">BCA - 8012345678</span>
                    <span class="text-neutral-500 block mt-0.5 uppercase" id="payBankHolder">A/N ALEX RIVERA</span>
                </div>
                
                <!-- Signature Space -->
                <div class="flex flex-col items-center text-center relative">
                    <!-- Circular Digital Stamp SVG Overlay -->
                    <div class="absolute -left-24 -bottom-2 pointer-events-none select-none z-10">
                        <svg class="w-28 h-28 text-blue-800/80 transform rotate-12" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="2.5" stroke-dasharray="3, 1.5" />
                            <circle cx="50" cy="50" r="37" fill="none" stroke="currentColor" stroke-width="1.5" />
                            <path id="stampPath" fill="none" d="M 20 50 A 30 30 0 1 1 80 50" />
                            <text font-size="4.8" font-weight="900" letter-spacing="0.6" fill="currentColor">
                                <textPath href="#stampPath" startOffset="50%" text-anchor="middle">
                                    <?= htmlspecialchars($companyNameForStamp) ?>
                                </textPath>
                            </text>
                            <path id="stampPathBottom" fill="none" d="M 80 50 A 30 30 0 0 1 20 50" />
                            <text font-size="4.8" font-weight="900" letter-spacing="0.6" fill="currentColor">
                                <textPath href="#stampPathBottom" startOffset="50%" text-anchor="middle">
                                    * HR OPERATIONS *
                                </textPath>
                            </text>
                            <circle cx="50" cy="50" r="23" fill="none" stroke="currentColor" stroke-width="1" />
                            <text x="50" y="47" font-size="7" font-weight="900" text-anchor="middle" fill="currentColor">PAID</text>
                            <text x="50" y="57" font-size="6.5" font-weight="800" text-anchor="middle" fill="currentColor">VERIFIED</text>
                        </svg>
                    </div>

                    <span class="text-neutral-500 block mb-2 text-[10px] tracking-wider uppercase">TANDA TANGAN DIGITAL PERUSAHAAN</span>
                    
                    <!-- SVG Barcode for Digital Signature -->
                    <div class="bg-neutral-50 p-2 rounded border border-neutral-200 mb-2">
                        <svg class="h-9 w-44" viewBox="0 0 100 20">
                            <rect x="0" y="0" width="2" height="15" fill="black"/>
                            <rect x="3" y="0" width="1" height="15" fill="black"/>
                            <rect x="5" y="0" width="3" height="15" fill="black"/>
                            <rect x="9" y="0" width="1" height="15" fill="black"/>
                            <rect x="11" y="0" width="2" height="15" fill="black"/>
                            <rect x="15" y="0" width="4" height="15" fill="black"/>
                            <rect x="20" y="0" width="1" height="15" fill="black"/>
                            <rect x="22" y="0" width="2" height="15" fill="black"/>
                            <rect x="25" y="0" width="3" height="15" fill="black"/>
                            <rect x="29" y="0" width="1" height="15" fill="black"/>
                            <rect x="31" y="0" width="2" height="15" fill="black"/>
                            <rect x="35" y="0" width="1" height="15" fill="black"/>
                            <rect x="37" y="0" width="3" height="15" fill="black"/>
                            <rect x="41" y="0" width="1" height="15" fill="black"/>
                            <rect x="43" y="0" width="4" height="15" fill="black"/>
                            <rect x="48" y="0" width="2" height="15" fill="black"/>
                            <rect x="51" y="0" width="1" height="15" fill="black"/>
                            <rect x="53" y="0" width="3" height="15" fill="black"/>
                            <rect x="57" y="0" width="1" height="15" fill="black"/>
                            <rect x="59" y="0" width="2" height="15" fill="black"/>
                            <rect x="62" y="0" width="4" height="15" fill="black"/>
                            <rect x="67" y="0" width="1" height="15" fill="black"/>
                            <rect x="69" y="0" width="2" height="15" fill="black"/>
                            <rect x="72" y="0" width="3" height="15" fill="black"/>
                            <rect x="76" y="0" width="1" height="15" fill="black"/>
                            <rect x="78" y="0" width="2" height="15" fill="black"/>
                            <rect x="81" y="0" width="1" height="15" fill="black"/>
                            <rect x="83" y="0" width="3" height="15" fill="black"/>
                            <rect x="87" y="0" width="1" height="15" fill="black"/>
                            <rect x="89" y="0" width="4" height="15" fill="black"/>
                            <rect x="94" y="0" width="2" height="15" fill="black"/>
                            <rect x="97" y="0" width="1" height="15" fill="black"/>
                            <rect x="99" y="0" width="1" height="15" fill="black"/>
                            <text x="50" y="19" font-size="4" font-family="monospace" text-anchor="middle" fill="#333" id="payBarcodeNo">PAY-20260525-0033</text>
                        </svg>
                    </div>
                    <span class="font-extrabold text-neutral-800 uppercase tracking-tight"><?= htmlspecialchars($hrDirectorName) ?></span>
                    <span class="text-[10px] text-neutral-500 uppercase"><?= htmlspecialchars($hrDirectorTitle) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ============================================== -->
<!-- DETAILED FORMULIR 1721-A1 PREVIEW MODAL -->
<!-- ============================================== -->
<div id="taxModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
    <div class="bg-white text-black w-full max-w-5xl rounded-2xl shadow-2xl flex flex-col max-h-[95vh] overflow-hidden scale-95 transform transition-transform duration-300 border border-neutral-300" id="taxModalContainer">
        <!-- Modal Toolbar -->
        <div class="bg-neutral-100 px-6 py-3 border-b border-neutral-200 flex justify-between items-center no-print">
            <span class="text-xs font-bold text-neutral-600 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">verified</span> Formulir Pajak 1721-A1 Resmi (Tahun Pajak 2025)
            </span>
            <div class="flex items-center gap-2">
                <button onclick="printTaxFromModal()" class="bg-primary text-white hover:bg-primary/95 font-bold text-xs py-2 px-4 rounded-lg flex items-center gap-1.5 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-sm">print</span> Cetak Formulir A1
                </button>
                <button onclick="closeTaxModal()" class="bg-neutral-200 text-neutral-700 hover:bg-neutral-300 font-bold text-xs py-2 px-3 rounded-lg flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-sm">close</span> Tutup
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Content (DJP Form 1721-A1) -->
        <div class="p-8 md:p-12 overflow-y-auto bg-white flex-grow text-[11px]" id="taxPrintArea">
            <!-- Outer Border Box matching actual DJP forms -->
            <div class="border-2 border-neutral-800 p-6 space-y-4">
                <!-- Header Block -->
                <div class="flex items-stretch border border-neutral-800">
                    <div class="w-1/4 border-r border-neutral-800 p-3 flex flex-col items-center justify-center text-center">
                        <span class="material-symbols-outlined text-4xl text-neutral-800">local_police</span>
                        <span class="text-[9px] font-black uppercase mt-1 leading-none">KEMENTERIAN KEUANGAN RI</span>
                        <span class="text-[8px] font-bold text-neutral-500 leading-none">DIREKTORAT JENDERAL PAJAK</span>
                    </div>
                    <div class="w-1/2 border-r border-neutral-800 p-3 flex flex-col items-center justify-center text-center space-y-1">
                        <h4 class="font-black text-sm uppercase leading-tight tracking-tight">BUKTI PEMOTONGAN PAJAK PENGHASILAN PASAL 21 BAGI PEGAWAI TETAP</h4>
                        <p class="text-[9px] text-neutral-500 uppercase leading-none">ATAU PENERIMA PENSIUN ATAU TUNJANGAN HARI TUA/JAMINAN HARI TUA BERKALA</p>
                    </div>
                    <div class="w-1/4 p-3 flex flex-col justify-between items-center text-center">
                        <span class="text-sm font-black tracking-widest border border-neutral-800 px-3 py-1">FORMULIR 1721-A1</span>
                        <div class="text-[9px] text-neutral-550 space-y-0.5 mt-2">
                            <div>MASA PAJAK [ 01 - 12 ]</div>
                            <div>TAHUN PAJAK <strong class="text-neutral-900 text-xs">[ 2025 ]</strong></div>
                        </div>
                    </div>
                </div>

                <div class="text-right font-mono font-bold text-neutral-800 text-[10px]">
                    NOMOR BUKTI POTONG: 1.1.12-25.0000332
                </div>

                <!-- Section I: Identity of Recipient -->
                <div class="space-y-1.5">
                    <div class="bg-neutral-800 text-white font-bold px-3 py-1 text-[10px] tracking-wider uppercase">
                        A. IDENTITAS PENERIMA PENGHASILAN YANG DIPOTONG
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 px-3 py-2 border border-neutral-350">
                        <div class="space-y-1.5">
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">1. NPWP:</span>
                                <span class="w-2/3 font-mono font-bold text-neutral-900"><?= htmlspecialchars($npwpNumber) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">2. NIK (KTP):</span>
                                <span class="w-2/3 font-mono font-bold text-neutral-900"><?= htmlspecialchars($ktpNik) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">3. Nama Lengkap:</span>
                                <span class="w-2/3 font-bold text-neutral-900 uppercase"><?= htmlspecialchars($namaSesuaiKtp) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">4. Alamat KTP:</span>
                                <span class="w-2/3 font-medium text-neutral-900 leading-tight">Jl. Boulevard Gading Serpong No. 12, Tangerang, Banten</span>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">5. Jenis Kelamin:</span>
                                <span class="w-2/3 font-bold text-neutral-800 uppercase"><?= htmlspecialchars($jenisKelamin) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">6. Status PTKP:</span>
                                <span class="w-2/3 font-bold text-neutral-800">TK/0 (Tanpa Tanggungan)</span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">7. Nama Jabatan:</span>
                                <span class="w-2/3 font-bold text-neutral-800">Senior UI/UX Designer</span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">8. Pegawai Asing:</span>
                                <span class="w-2/3 font-bold text-neutral-800">TIDAK</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section II: Calculations List -->
                <div class="space-y-1">
                    <div class="bg-neutral-800 text-white font-bold px-3 py-1 text-[10px] tracking-wider uppercase">
                        B. RINCIAN PENGHASILAN DAN PENGHITUNGAN PPH PASAL 21 (SETAHUN / DISATUKAN)
                    </div>
                    
                    <div class="border border-neutral-350 overflow-hidden font-medium">
                        <!-- Table Headers -->
                        <div class="flex bg-neutral-100 font-bold border-b border-neutral-300 py-1 px-3 text-[10px] text-neutral-700">
                            <div class="w-3/4">URAIAN PENGHITUNGAN</div>
                            <div class="w-1/4 text-right">JUMLAH (RUPIAH)</div>
                        </div>
                        
                        <!-- Rows -->
                        <div class="divide-y divide-neutral-200">
                            <!-- Earnings -->
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">1. Gaji Pokok / Uang Pensiun Berkala</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-900">Rp 198.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">2. Tunjangan PPh 21 (Ditanggung Perusahaan)</div>
                                <div class="w-1/4 text-right font-mono text-neutral-850">Rp 0</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">3. Tunjangan Lainnya, Uang Lembur, dan sejenisnya</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-neutral-900">Rp 54.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">4. Honorarium, Insentif, dan Imbalan Lain sejenis</div>
                                <div class="w-1/4 text-right font-mono text-neutral-850">Rp 0</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">5. Premi Asuransi Kerja yang Dibayar Pemberi Kerja</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-neutral-900">Rp 4.350.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">6. Tantiem, Bonus, Gratifikasi, Jasa Produksi, dan THR</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-900">Rp 16.500.000</div>
                            </div>
                            <div class="flex py-1.5 px-3 bg-neutral-50 border-t border-b border-neutral-350">
                                <div class="w-3/4 font-bold text-neutral-900">7. JUMLAH PENGHASILAN BRUTO (1 s.d. 6)</div>
                                <div class="w-1/4 text-right font-mono font-black text-neutral-950">Rp 272.850.000</div>
                            </div>

                            <!-- Deductions -->
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">8. Biaya Jabatan (5% dari Penghasilan Bruto, Maks Rp 6.000.000)</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-red-700">Rp 6.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">9. Iuran Pensiun atau Iuran JHT/THT (Dibayar Sendiri)</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-red-700">Rp 3.960.000</div>
                            </div>
                            <div class="flex py-1.5 px-3 bg-neutral-55 border-t border-b border-neutral-350">
                                <div class="w-3/4 font-bold text-neutral-900">10. JUMLAH PENGURANGAN (8 + 9)</div>
                                <div class="w-1/4 text-right font-mono font-bold text-red-750">Rp 9.960.000</div>
                            </div>

                            <!-- PPh Calculations -->
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">11. Penghasilan Netto Setahun (7 - 10)</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-900">Rp 262.890.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">12. Penghasilan Tidak Kena Pajak (PTKP) - TK/0</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-850">Rp 54.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">13. Penghasilan Kena Pajak (PKP) Setahun (11 - 12)</div>
                                <div class="w-1/4 text-right font-mono font-black text-neutral-900">Rp 208.890.000</div>
                            </div>
                            <div class="flex py-1.5 px-3 bg-neutral-50 border-t border-b border-neutral-350">
                                <div class="w-3/4 font-bold text-primary">14. PPH PASAL 21 TERUTANG / DIPOTONG SETAHUN</div>
                                <div class="w-1/4 text-right font-mono font-black text-primary">Rp 25.333.500</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section III: Identity of Withholder -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 items-end text-[10px]">
                    <div class="space-y-1.5">
                        <div class="bg-neutral-800 text-white font-bold px-3 py-0.5 tracking-wider uppercase">
                            C. IDENTITAS PEMOTONG PAJAK (PERUSAHAAN)
                        </div>
                        <div class="px-3 py-2 border border-neutral-350 space-y-1">
                            <div class="flex">
                                <span class="w-1/3 text-neutral-500">NPWP Pemotong:</span>
                                <span class="w-2/3 font-mono font-bold text-neutral-800">01.234.567.8-012.000</span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-500">Nama Pemotong:</span>
                                <span class="w-2/3 font-bold text-neutral-800"><?= htmlspecialchars($companyName) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Signature Section -->
                    <div class="flex flex-col items-center text-center relative">
                        <!-- Red Tax Stamp Overlay -->
                        <div class="absolute bottom-4 right-10 pointer-events-none select-none z-10 opacity-75">
                            <svg class="w-24 h-24 text-red-700/80 transform -rotate-12" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="2.5" />
                                <text x="50" y="40" font-size="6" font-weight="900" text-anchor="middle" fill="currentColor" letter-spacing="1">PAJAK RI</text>
                                <text x="50" y="52" font-size="8" font-weight="950" text-anchor="middle" fill="currentColor">LUNAS</text>
                                <text x="50" y="64" font-size="6" font-weight="900" text-anchor="middle" fill="currentColor" letter-spacing="1">DJP - 2025</text>
                                <path d="M 12 50 L 88 50" stroke="currentColor" stroke-width="1" stroke-dasharray="2, 2" />
                            </svg>
                        </div>
                        
                        <span class="text-neutral-500 font-bold block mb-1">TANGERANG, 10 JANUARI 2026</span>
                        <span class="text-[9px] text-neutral-500 block mb-2">TANDA TANGAN & CAP JALUR CEPAT DJP</span>
                        
                        <div class="bg-neutral-50 p-1.5 rounded border border-neutral-200 mb-1">
                            <svg class="h-8 w-36" viewBox="0 0 100 20">
                                <rect x="0" y="0" width="1" height="15" fill="black"/>
                                <rect x="2" y="0" width="3" height="15" fill="black"/>
                                <rect x="6" y="0" width="1" height="15" fill="black"/>
                                <rect x="8" y="0" width="2" height="15" fill="black"/>
                                <rect x="12" y="0" width="4" height="15" fill="black"/>
                                <rect x="17" y="0" width="1" height="15" fill="black"/>
                                <rect x="19" y="0" width="2" height="15" fill="black"/>
                                <rect x="23" y="0" width="3" height="15" fill="black"/>
                                <rect x="27" y="0" width="1" height="15" fill="black"/>
                                <rect x="29" y="0" width="2" height="15" fill="black"/>
                                <rect x="33" y="0" width="1" height="15" fill="black"/>
                                <rect x="35" y="0" width="3" height="15" fill="black"/>
                                <rect x="39" y="0" width="1" height="15" fill="black"/>
                                <rect x="41" y="0" width="4" height="15" fill="black"/>
                                <rect x="46" y="0" width="2" height="15" fill="black"/>
                                <rect x="49" y="0" width="1" height="15" fill="black"/>
                                <rect x="52" y="0" width="3" height="15" fill="black"/>
                                <rect x="56" y="0" width="1" height="15" fill="black"/>
                                <rect x="58" y="0" width="2" height="15" fill="black"/>
                                <rect x="62" y="0" width="4" height="15" fill="black"/>
                                <rect x="67" y="0" width="1" height="15" fill="black"/>
                                <rect x="69" y="0" width="2" height="15" fill="black"/>
                                <rect x="73" y="0" width="3" height="15" fill="black"/>
                                <rect x="77" y="0" width="1" height="15" fill="black"/>
                                <rect x="79" y="0" width="2" height="15" fill="black"/>
                                <rect x="83" y="0" width="1" height="15" fill="black"/>
                                <rect x="85" y="0" width="3" height="15" fill="black"/>
                                <rect x="89" y="0" width="1" height="15" fill="black"/>
                                <rect x="91" y="0" width="4" height="15" fill="black"/>
                                <rect x="96" y="0" width="2" height="15" fill="black"/>
                                <text x="50" y="19" font-size="4" font-family="monospace" text-anchor="middle" fill="#555">TAX-1721A1-2025-0033</text>
                            </svg>
                        </div>
                        <span class="font-extrabold text-neutral-800 uppercase"><?= htmlspecialchars($companyName) ?></span>
                        <span class="text-[9px] text-neutral-500">Pemberi Kerja Pemotong Pajak</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- DEDICATED DOM CONTAINERS FOR PRINT AREA -->
<!-- ============================================== -->
<!-- DYNAMIC PRINT AREA CREATED VIA JS -->

<!-- ============================================== -->
<!-- INTERACTIVE JAVASCRIPT LOGIC -->
<!-- ============================================== -->
<script>
    // Config values matching backend calculations
    const baseSalary = <?= $baseSalary ?>;
    const employeeId = <?= json_encode($employeeId) ?>;
    const employeeName = <?= json_encode($namaSesuaiKtp) ?>;
    const bankName = <?= json_encode($bankName) ?>;
    const bankAccountNumber = <?= json_encode($bankAccountNumber) ?>;
    const bpjsTkPct = <?= $bpjsTkPct ?>;
    const bpjsKesPct = <?= $bpjsKesPct ?>;
    const pph21Pct = <?= $pph21Pct ?>;
    
    // Payslips detail source mapping - dynamically built from DB
    const payslipsData = <?php
        $jsPayslips = [];
        foreach ($months as $m) {
            $code = $m['code'];
            // Generate slip number from month_year code + employee ID
            $parts = explode('-', $code);
            $mm = $parts[0] ?? '00';
            $yyyy = $parts[1] ?? date('Y');
            $empSuffix = preg_replace('/[^0-9]/', '', $employeeId);
            $empSuffix = $empSuffix ? substr($empSuffix, -4) : '0001';
            $slipNo = "SLIP/PAY/{$yyyy}{$mm}25-{$empSuffix}";
            $barcode = "PAY-{$yyyy}{$mm}25-{$empSuffix}";

            $jsPayslips[$code] = [
                'no'                     => $slipNo,
                'barcode'                => $barcode,
                'period'                 => $m['month_name'],
                'date'                   => $m['date'],
                'base_salary'            => $m['base_salary'],
                'tunj_jabatan'           => $m['tunj_jabatan'],
                'tunj_transport'         => $m['tunj_transport_makan'],
                'tunj_komunikasi'        => $m['tunj_komunikasi'],
                'bonus'                  => $m['bonus'],
                'thr'                    => $m['thr'],
                'overtime'               => $m['overtime'],
                'reimbursement'          => $m['reimbursement'],
                'other_deduction'        => $m['other_deduction'],
                'unpaid_leave_days'      => $m['unpaid_leave_days'],
                'unpaid_leave_deduction' => $m['unpaid_leave_deduction'],
                'alpa_days'              => $m['alpa_days'],
                'alpa_deduction'         => $m['alpa_deduction'],
                'late_days'              => $m['late_days'],
                'late_deduction'         => $m['late_deduction'],
                'bpjs_tk'                => $m['bpjs_tk'],
                'bpjs_kes'               => $m['bpjs_kes'],
                'pph21'                  => $m['pph21'],
                'total_earnings'         => $m['gross_earning'],
                'total_deductions'       => $m['total_deductions'],
                'net_pay'                => $m['net_pay'],
                'working_days'           => $m['working_days'],
                'present_days'           => $m['present_days'],
            ];
        }
        echo json_encode($jsPayslips, JSON_UNESCAPED_UNICODE);
    ?>;


    window.formatRupiah = function formatRupiah(number) {
        return 'Rp ' + number.toLocaleString('id-ID');
    }

    // Modal Control: Payslip
    window.previewPayslip = function previewPayslip(code) {
        const data = payslipsData[code];
        if (!data) return;

        // Use pre-computed values from DB (no recalculation needed)
        const slipBaseSalary = data.base_salary || 0;
        const overtime = data.overtime || 0;
        const otherDeduction = data.other_deduction || 0;
        const unpaidDays = data.unpaid_leave_days || 0;
        const unpaidDec = data.unpaid_leave_deduction || 0;
        const alpaDays = data.alpa_days || 0;
        const alpaDec = data.alpa_deduction || 0;
        const lateDays = data.late_days || 0;
        const lateDec = data.late_deduction || 0;

        // Use exact DB values for deductions
        const bpjsTk = data.bpjs_tk || 0;
        const bpjsKes = data.bpjs_kes || 0;
        const pph21 = data.pph21 || 0;

        // Use pre-computed totals from DB
        const totalEarnings = data.total_earnings || (slipBaseSalary + data.tunj_jabatan + data.tunj_transport + data.tunj_komunikasi + data.bonus + data.thr + overtime);
        const totalDeductions = data.total_deductions || (bpjsTk + bpjsKes + pph21 + otherDeduction + unpaidDec + alpaDec + lateDec);
        const netPay = data.net_pay || (totalEarnings - totalDeductions);
        const workingDays = data.working_days || 22;
        const presentDays = data.present_days || 0;

        // Populating DOM elements
        document.getElementById('payslipNo').innerText = data.no;
        document.getElementById('payPeriod').innerText = data.period;
        document.getElementById('payNpwp').innerText = '<?= htmlspecialchars($npwpNumber) ?>';
        document.getElementById('payWorkDays').innerText = `${presentDays} / ${workingDays} Hari Kerja`;
        document.getElementById('payBase').innerText = formatRupiah(slipBaseSalary);

        document.getElementById('payTunjJabatan').innerText = formatRupiah(data.tunj_jabatan);
        document.getElementById('payTunjTransport').innerText = formatRupiah(data.tunj_transport);
        document.getElementById('payTunjKomunikasi').innerText = formatRupiah(data.tunj_komunikasi);
        
        // Bonus Row
        if (data.bonus > 0) {
            document.getElementById('payBonusRow').style.display = 'flex';
            document.getElementById('payBonus').innerText = formatRupiah(data.bonus);
        } else {
            document.getElementById('payBonusRow').style.display = 'none';
        }

        // THR Row
        if (data.thr > 0) {
            document.getElementById('payThrRow').style.display = 'flex';
            document.getElementById('payThr').innerText = formatRupiah(data.thr);
        } else {
            document.getElementById('payThrRow').style.display = 'none';
        }

        // Overtime Row
        if (overtime > 0) {
            document.getElementById('payOvertimeRow').style.display = 'flex';
            document.getElementById('payOvertime').innerText = formatRupiah(overtime);
        } else {
            document.getElementById('payOvertimeRow').style.display = 'none';
        }

        document.getElementById('payBpjsTk').innerText = formatRupiah(bpjsTk);
        document.getElementById('payBpjsKes').innerText = formatRupiah(bpjsKes);
        document.getElementById('payPph21').innerText = formatRupiah(pph21);

        // Unpaid Leave Deduction Row
        if (unpaidDec > 0) {
            document.getElementById('payUnpaidDeductionRow').style.display = 'flex';
            document.getElementById('payUnpaidDaysBadge').innerText = `${unpaidDays} Hari`;
            document.getElementById('payUnpaidDeduction').innerText = formatRupiah(unpaidDec);
        } else {
            document.getElementById('payUnpaidDeductionRow').style.display = 'none';
        }

        // Alpa Deduction Row
        if (alpaDec > 0) {
            document.getElementById('payAlpaDeductionRow').style.display = 'flex';
            document.getElementById('payAlpaDaysBadge').innerText = `${alpaDays} Hari`;
            document.getElementById('payAlpaDeduction').innerText = formatRupiah(alpaDec);
        } else {
            document.getElementById('payAlpaDeductionRow').style.display = 'none';
        }

        // Late Deduction Row
        if (lateDec > 0) {
            document.getElementById('payLateDeductionRow').style.display = 'flex';
            document.getElementById('payLateDaysBadge').innerText = `${lateDays} Kali`;
            document.getElementById('payLateDeduction').innerText = formatRupiah(lateDec);
        } else {
            document.getElementById('payLateDeductionRow').style.display = 'none';
        }

        // Other Deduction Row
        if (otherDeduction > 0) {
            document.getElementById('payOtherDeductionRow').style.display = 'flex';
            document.getElementById('payOtherDeduction').innerText = formatRupiah(otherDeduction);
        } else {
            document.getElementById('payOtherDeductionRow').style.display = 'none';
        }

        document.getElementById('payGrossTotal').innerText = formatRupiah(totalEarnings);
        document.getElementById('payDeductionsTotal').innerText = formatRupiah(totalDeductions);
        document.getElementById('payNetTotal').innerText = formatRupiah(netPay);

        // Bank details
        document.getElementById('payBankInfo').innerText = `${bankName} - ${bankAccountNumber}`;
        document.getElementById('payBankHolder').innerText = `A/N ${employeeName}`;
        
        // Barcode update
        document.getElementById('payBarcodeNo').innerText = data.barcode;

        // Show Modal
        const modal = document.getElementById('payslipModal');
        const container = document.getElementById('payslipModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            container.classList.remove('scale-95');
        }, 50);
    }

    window.closePayslipModal = function closePayslipModal() {
        const modal = document.getElementById('payslipModal');
        const container = document.getElementById('payslipModalContainer');
        modal.classList.add('opacity-0');
        container.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Modal Control: Tax Document A1
    window.previewTaxA1 = function previewTaxA1() {
        const modal = document.getElementById('taxModal');
        const container = document.getElementById('taxModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            container.classList.remove('scale-95');
        }, 50);
    }

    window.closeTaxModal = function closeTaxModal() {
        const modal = document.getElementById('taxModal');
        const container = document.getElementById('taxModalContainer');
        modal.classList.add('opacity-0');
        container.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Printing Operations (Clones target container to dedicated #printArea and triggers system print)
    window.printPayslipDirect = function printPayslipDirect(code) {
        // Build payslip context in modal first
        previewPayslip(code);
        // Print it immediately
        setTimeout(() => {
            printPayslipFromModal();
        }, 200);
    }

    window.printPayslipFromModal = function printPayslipFromModal() {
        const sourceHtml = document.getElementById('payslipPrintArea').innerHTML;
        
        // Remove any existing printArea
        const oldPrintArea = document.getElementById('printArea');
        if (oldPrintArea) oldPrintArea.remove();
        
        // Create dynamic printArea directly under body
        const printArea = document.createElement('div');
        printArea.id = 'printArea';
        printArea.innerHTML = sourceHtml;
        document.body.appendChild(printArea);
        
        // Add printing class to body
        document.body.classList.add('printing-active');
        
        // Trigger system print
        window.print();
        
        // Clean up
        document.body.classList.remove('printing-active');
        printArea.remove();
    }

    window.printTaxFromModal = function printTaxFromModal() {
        const sourceHtml = document.getElementById('taxPrintArea').innerHTML;
        
        // Remove any existing printArea
        const oldPrintArea = document.getElementById('printArea');
        if (oldPrintArea) oldPrintArea.remove();
        
        // Create dynamic printArea directly under body
        const printArea = document.createElement('div');
        printArea.id = 'printArea';
        printArea.innerHTML = sourceHtml;
        document.body.appendChild(printArea);
        
        // Add printing class to body
        document.body.classList.add('printing-active');
        
        // Trigger system print
        window.print();
        
        // Clean up
        document.body.classList.remove('printing-active');
        printArea.remove();
    }

    // Click outside to close modals
    window.addEventListener('click', function(e) {
        const payModal = document.getElementById('payslipModal');
        const payContainer = document.getElementById('payslipModalContainer');
        if (e.target === payModal) {
            closePayslipModal();
        }

        const taxModal = document.getElementById('taxModal');
        const taxContainer = document.getElementById('taxModalContainer');
        if (e.target === taxModal) {
            closeTaxModal();
        }
    });
</script>