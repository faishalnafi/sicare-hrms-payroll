<?php
// Employee Attendance Page — ESS Portal
$userId = $_SESSION['user_id'] ?? null;
$attData = ['today' => null, 'monthly' => [], 'countHadir' => 0, 'countTerlambat' => 0, 'countAlpa' => 0, 'totalLateMin' => 0, 'workHoursToday' => '-', 'settings' => []];
$homeLat = null;
$homeLng = null;

$daysIndo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$daysShortIndo = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
$monthsIndo = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'];
$monthsFullIndo = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];

if ($userId) {
    try {
        $ctrl = new \App\Controllers\AttendanceController();
        $attData = $ctrl->getEmployeeData($userId);
        
        $db = \App\Config\Database::getInstance()->getConnection();
        $userStmt = $db->prepare("SELECT home_latitude, home_longitude FROM users WHERE id = :uid LIMIT 1");
        $userStmt->execute(['uid' => $userId]);
        $dbUser = $userStmt->fetch();
        $homeLat = $dbUser && $dbUser['home_latitude'] !== null ? (float)$dbUser['home_latitude'] : null;
        $homeLng = $dbUser && $dbUser['home_longitude'] !== null ? (float)$dbUser['home_longitude'] : null;
    } catch (Exception $e) {
        // silently fail, show empty state
    }
}

$today       = $attData['today'];
$hasClockIn  = $today && !empty($today['clock_in']);
$hasClockOut = $today && !empty($today['clock_out']);
$todayMode   = $today['work_mode'] ?? null;
$cfg         = $attData['settings'];
$ipAddr      = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$isOfficeWifi = str_starts_with($ipAddr, $cfg['office_wifi_prefix'] ?? '192.168.10.');

$todayDate = date('Y-m-d');
$todayDayName = date('D'); // Mon, Tue, Wed, Thu, Fri, Sat, Sun
$allDaysForHoliday = ['Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu', 'Sun' => 'Minggu'];

$weeklyHolidays = explode(',', $cfg['weekly_holidays'] ?? 'Sat,Sun');
$isWeeklyHoliday = in_array($todayDayName, $weeklyHolidays);

// Check if today is a national holiday
$isHoliday = $isWeeklyHoliday;
$holidayReason = '';
if ($userId) {
    try {
        $db = \App\Config\Database::getInstance()->getConnection();
        $holidayStmt = $db->prepare("SELECT description FROM company_holidays WHERE holiday_date = :date LIMIT 1");
        $holidayStmt->execute(['date' => $todayDate]);
        $nationalHoliday = $holidayStmt->fetch();
        
        if ($nationalHoliday) {
            $isHoliday = true;
            $holidayReason = "Hari Libur Nasional: " . $nationalHoliday['description'];
        } elseif ($isWeeklyHoliday) {
            $holidayReason = "Hari Libur Akhir Pekan (" . ($allDaysForHoliday[$todayDayName] ?? $todayDayName) . ")";
        }
    } catch (Exception $e) {
        if ($isWeeklyHoliday) {
            $holidayReason = "Hari Libur Akhir Pekan (" . ($allDaysForHoliday[$todayDayName] ?? $todayDayName) . ")";
        }
    }
}

$todayStatus = ($isHoliday && !$hasClockIn) ? 'libur' : ($today['status'] ?? 'alpa');


// Status colour helpers
function statusBadge($status) {
    return match($status) {
        'tepat waktu' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'awal'        => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'terlambat'   => 'bg-amber-50 text-amber-700 border-amber-200',
        'sakit/izin'  => 'bg-blue-50 text-blue-700 border-blue-200',
        'libur'       => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        default       => 'bg-red-50 text-red-700 border-red-200',
    };
}
function statusDot($status) {
    return match($status) {
        'tepat waktu' => 'bg-emerald-500',
        'awal'        => 'bg-indigo-500',
        'terlambat'   => 'bg-amber-500',
        'sakit/izin'  => 'bg-blue-500',
        'libur'       => 'bg-indigo-500',
        default       => 'bg-red-500',
    };
}
function statusLabel($status) {
    return match($status) {
        'tepat waktu' => 'Tepat Waktu',
        'awal'        => 'Masuk Awal',
        'terlambat'   => 'Terlambat',
        'sakit/izin'  => 'Sakit / Izin',
        'libur'       => 'Libur',
        default       => 'Alpa',
    };
}

// Clock-Out status helpers
function clockOutStatusBadge($status) {
    return match($status) {
        'pulang lambat' => 'bg-amber-50 text-amber-700 border-amber-200',
        'pulang cepat'  => 'bg-red-50 text-red-700 border-red-200',
        'wajar', 'normal' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'tidak presensi pulang' => 'bg-rose-50 text-rose-700 border-rose-200',
        default         => 'bg-surface-container-low text-on-surface-variant/40 border-outline-variant/10',
    };
}
function clockOutStatusDot($status) {
    return match($status) {
        'pulang lambat' => 'bg-amber-500',
        'pulang cepat'  => 'bg-red-500',
        'wajar', 'normal' => 'bg-emerald-500',
        'tidak presensi pulang' => 'bg-rose-500',
        default         => 'bg-on-surface-variant/20',
    };
}
function clockOutStatusLabel($status) {
    return match($status) {
        'pulang lambat' => 'Pulang Lambat',
        'pulang cepat'  => 'Pulang Cepat',
        'wajar', 'normal' => 'Wajar',
        'tidak presensi pulang' => 'Tidak Presensi Pulang',
        default         => '—',
    };
}
?>

<style>
/* ── Attendance Page Premium Styles ── */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap');

.att-digital-clock {
    font-family: 'Outfit', 'Manrope', monospace;
    letter-spacing: 0.05em;
    background: linear-gradient(135deg, #000666 0%, #1a237e 50%, #0d47a1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.att-hero-card {
    background: linear-gradient(135deg, #000666 0%, #1a237e 50%, #0d47a1 100%);
    position: relative;
    overflow: hidden;
}
.att-hero-card::before {
    content: '';
    position: absolute;
    top: -40%; right: -10%;
    width: 380px; height: 380px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
    pointer-events: none;
}
.att-hero-card::after {
    content: '';
    position: absolute;
    bottom: -60%; left: -5%;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,0.03);
    pointer-events: none;
}
.att-btn-clockin {
    background: linear-gradient(135deg, #00c853 0%, #69f0ae 100%);
    box-shadow: 0 8px 24px rgba(0,200,83,0.35);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.att-btn-clockin:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0,200,83,0.45);
}
.att-btn-clockout {
    background: linear-gradient(135deg, #ff6f00 0%, #ffa726 100%);
    box-shadow: 0 8px 24px rgba(255,111,0,0.35);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.att-btn-clockout:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(255,111,0,0.45);
}
.att-btn-disabled {
    background: rgba(255,255,255,0.12);
    cursor: not-allowed;
    opacity: 0.55;
    box-shadow: none;
}
.att-pulse-ring {
    animation: attPulse 2s ease-in-out infinite;
}
@keyframes attPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(0,200,83,0.4); }
    50% { box-shadow: 0 0 0 12px rgba(0,200,83,0); }
}
.att-stat-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,6,102,0.06);
}
.att-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,6,102,0.08);
}
.att-table-row {
    transition: background 0.15s ease;
}
.att-table-row:hover {
    background: rgba(0,6,102,0.02);
}
.att-location-tag {
    font-family: 'Outfit', monospace;
    font-size: 10px;
}
/* Spinner */
.att-spinner {
    width: 18px; height: 18px;
    border: 2.5px solid rgba(255,255,255,0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="space-y-6" id="attPageRoot">

    <!-- ── HEADER ── -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Menu Presensi</h1>
            <p class="text-on-surface-variant font-medium text-sm">
                Catat kehadiran harian Anda, pantau riwayat, dan lihat akumulasi keterlambatan bulanan.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl px-4 py-2.5 flex items-center gap-2 text-sm font-semibold text-on-surface-variant shadow-sm">
                <span class="material-symbols-outlined text-primary text-sm">calendar_today</span>
                <span id="attLiveDate"><?= date('d ') . ($monthsFullIndo[date('F')] ?? date('F')) . date(' Y') ?></span>
            </div>
        </div>
    </div>

    <!-- ── BANNER HARI LIBUR ── -->
    <?php if ($isHoliday): ?>
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm flex items-start gap-4 animate-fade-in">
        <div class="bg-red-100 text-red-700 p-3 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">celebration</span>
        </div>
        <div class="flex-1 space-y-1">
            <h3 class="font-bold text-red-900 text-base">Hari Ini Adalah Hari Libur!</h3>
            <p class="text-red-700 font-semibold text-sm">
                <?= htmlspecialchars($holidayReason) ?>.
            </p>
            <p class="text-red-600/80 text-xs font-medium">
                Anda tidak diwajibkan untuk melakukan presensi hari ini. Nikmati hari libur Anda!
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── HERO CLOCK-IN CARD ── -->
    <div class="att-hero-card rounded-2xl p-6 md:p-8 shadow-xl">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">

            <!-- Left: Clock Display -->
            <div class="flex-1">
                <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-2">Waktu Saat Ini</p>
                <div id="attLiveClock" class="text-6xl md:text-7xl font-black att-digital-clock" style="-webkit-text-fill-color: white; color: white; font-family: 'Outfit', monospace; letter-spacing: 0.05em;">
                    00:00:00
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="w-2 h-2 bg-green-400 rounded-full att-pulse-ring"></span>
                    <p class="text-white/70 text-sm font-medium">
                        <?= ($daysIndo[date('l')] ?? date('l')) . ', ' . date('d ') . ($monthsIndo[date('M')] ?? date('M')) . date(' Y') ?>
                    </p>
                </div>
                <!-- Location status tag (updated by JS) -->
                <div id="locationStatusTag" class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-white/10 text-white/80 border border-white/15">
                    <span class="material-symbols-outlined text-sm">my_location</span>
                    <span id="locationTagText">Mendeteksi lokasi...</span>
                </div>
            </div>

            <!-- Right: Clock-In/Out Buttons + Today Status -->
            <div class="flex-shrink-0 flex flex-col items-start lg:items-end gap-4 w-full lg:w-auto">

                <!-- Today Status -->
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-4 border border-white/15 w-full lg:w-[320px]">
                    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-3">Status Hari Ini</p>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-3 min-w-[280px]">
                        <div>
                            <p class="text-white/50 text-[9px] font-semibold uppercase tracking-wider">Clock-In</p>
                            <p class="text-white font-bold text-lg font-mono mt-0.5" id="dispClockIn">
                                <?= $hasClockIn ? date('H:i', strtotime($today['clock_in'])) : '--:--' ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-white/50 text-[9px] font-semibold uppercase tracking-wider">Clock-Out</p>
                            <p class="text-white font-bold text-lg font-mono mt-0.5" id="dispClockOut">
                                <?= $hasClockOut ? date('H:i', strtotime($today['clock_out'])) : '--:--' ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-white/50 text-[9px] font-semibold uppercase tracking-wider">Status</p>
                            <p class="text-white font-bold text-sm mt-0.5" id="dispStatus">
                                <?= statusLabel($todayStatus) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-white/50 text-[9px] font-semibold uppercase tracking-wider">Jam Kerja</p>
                            <p class="text-white font-bold text-sm mt-0.5" id="dispWorkHours">
                                <?= $attData['workHoursToday'] ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-white/50 text-[9px] font-semibold uppercase tracking-wider">Mode Masuk</p>
                            <p class="text-white font-bold text-sm mt-0.5" id="dispModeIn">
                                <?= ($today && !empty($today['work_mode'])) ? $today['work_mode'] : '—' ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-white/50 text-[9px] font-semibold uppercase tracking-wider">Mode Pulang</p>
                            <p class="text-white font-bold text-sm mt-0.5" id="dispModeOut">
                                <?= ($today && !empty($today['work_mode_out'])) ? $today['work_mode_out'] : '—' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 w-full lg:w-[320px]">
                    <?php if ($isHoliday && !$hasClockIn): ?>
                    <button class="att-btn-disabled flex-1 flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl text-white font-extrabold text-sm" disabled>
                        <span class="material-symbols-outlined">login</span>
                        Clock-In
                    </button>
                    <button class="att-btn-disabled flex-1 flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl text-white font-extrabold text-sm" disabled>
                        <span class="material-symbols-outlined">logout</span>
                        Clock-Out
                    </button>
                    <?php elseif (!$hasClockIn): ?>
                    <button id="btnClockIn" onclick="window.doClockIn()"
                        class="att-btn-clockin flex-1 flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl text-white font-extrabold text-sm tracking-wide">
                        <span class="material-symbols-outlined font-bold">login</span>
                        Clock-In
                    </button>
                    <button class="att-btn-disabled flex-1 flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl text-white font-extrabold text-sm" disabled>
                        <span class="material-symbols-outlined">logout</span>
                        Clock-Out
                    </button>
                    <?php elseif ($hasClockIn && !$hasClockOut): ?>
                    <button class="att-btn-disabled flex-1 flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl text-white font-extrabold text-sm" disabled>
                        <span class="material-symbols-outlined">login</span>
                        Clock-In ✓
                    </button>
                    <button id="btnClockOut" onclick="window.doClockOut()"
                        class="att-btn-clockout flex-1 flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl text-white font-extrabold text-sm tracking-wide">
                        <span class="material-symbols-outlined font-bold">logout</span>
                        Clock-Out
                    </button>
                    <?php else: ?>
                    <div class="bg-white/10 rounded-2xl px-8 py-4 border border-white/15 flex items-center justify-center gap-3 text-white w-full">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                        <span class="font-bold text-sm">Presensi Hari Ini Selesai</span>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ── STATS CARDS ── -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Hadir Bulan Ini -->
        <div class="att-stat-card bg-surface-container-lowest rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Hadir Bulan Ini</p>
                    <h3 class="text-3xl font-black text-emerald-700 mt-2"><?= $attData['countHadir'] ?></h3>
                    <p class="text-[11px] text-emerald-600 font-semibold mt-1">hari kerja hadir</p>
                </div>
                <span class="material-symbols-outlined text-emerald-600 bg-emerald-50 p-2.5 rounded-xl text-xl">check_circle</span>
            </div>
        </div>

        <!-- Terlambat -->
        <div class="att-stat-card bg-surface-container-lowest rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Terlambat</p>
                    <h3 class="text-3xl font-black text-amber-700 mt-2"><?= $attData['countTerlambat'] ?></h3>
                    <p class="text-[11px] text-amber-600 font-semibold mt-1">kali bulan ini</p>
                </div>
                <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-2.5 rounded-xl text-xl">schedule</span>
            </div>
        </div>

        <!-- Total Keterlambatan -->
        <div class="att-stat-card bg-surface-container-lowest rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Akm. Terlambat</p>
                    <?php 
                        $tlm = $attData['totalLateMin'];
                        $tlh = floor($tlm / 60);
                        $tlm_rem = $tlm % 60;
                        $tl_str = $tlh > 0 ? "{$tlh}j {$tlm_rem}m" : "{$tlm_rem}m";
                    ?>
                    <h3 class="text-3xl font-black text-orange-700 mt-2"><?= $tl_str ?></h3>
                    <p class="text-[11px] text-orange-600 font-semibold mt-1">waktu bulan ini</p>
                </div>
                <span class="material-symbols-outlined text-orange-600 bg-orange-50 p-2.5 rounded-xl text-xl">timer_off</span>
            </div>
        </div>

        <!-- Alpa -->
        <div class="att-stat-card bg-surface-container-lowest rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Alpa</p>
                    <h3 class="text-3xl font-black text-red-700 mt-2"><?= $attData['countAlpa'] ?></h3>
                    <p class="text-[11px] text-red-600 font-semibold mt-1">hari bulan ini</p>
                </div>
                <span class="material-symbols-outlined text-red-600 bg-red-50 p-2.5 rounded-xl text-xl">cancel</span>
            </div>
        </div>
    </div>

    <!-- ── MONTHLY HISTORY TABLE ── -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-outline-variant/10 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary">calendar_month</span>
                <h2 class="text-base font-extrabold text-on-surface">Riwayat Presensi – <?= ($monthsFullIndo[date('F')] ?? date('F')) . date(' Y') ?></h2>
            </div>
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider bg-surface-container-low px-3 py-1.5 rounded-full">
                <?= count($attData['monthly']) ?> Catatan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/10">
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Tanggal</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Clock-In</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Status Clock-In</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Clock-Out</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Status Clock-Out</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Jam Kerja</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Mode Masuk</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Mode Pulang</th>
                        <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">Metode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/8">
                    <?php if (empty($attData['monthly'])): ?>
                    <tr>
                        <td colspan="9" class="py-16 px-6 text-center">
                            <span class="material-symbols-outlined text-4xl text-outline-variant block mb-3">event_busy</span>
                            <p class="text-on-surface-variant font-semibold text-sm">Belum ada riwayat presensi bulan ini.</p>
                            <p class="text-on-surface-variant/60 text-xs mt-1">Lakukan Clock-In pertama Anda hari ini!</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($attData['monthly'] as $rec): ?>
                    <?php
                        $cin  = $rec['clock_in']  ? date('H:i', strtotime($rec['clock_in']))  : '--:--';
                        $cout = $rec['clock_out'] ? date('H:i', strtotime($rec['clock_out'])) : '--:--';
                        $wh   = '-';
                        if ($rec['clock_in'] && $rec['clock_out']) {
                            $diffSec = strtotime($rec['clock_out']) - strtotime($rec['clock_in']);
                            $wh = floor($diffSec/3600) . 'j ' . floor(($diffSec%3600)/60) . 'm';
                        }
                        $dayEng = date('D', strtotime($rec['attendance_date']));
                        $dayShortMap = ['Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'];
                        $recDayLabel = $dayShortMap[$dayEng] ?? $dayEng;
                        
                        $dateDay = date('d', strtotime($rec['attendance_date']));
                        $dateMonthEng = date('M', strtotime($rec['attendance_date']));
                        $recDateLabel = $dateDay . ' ' . ($monthsIndo[$dateMonthEng] ?? $dateMonthEng);
                    ?>
                    <tr class="att-table-row">
                        <td class="py-3.5 px-6">
                            <div class="font-bold text-sm text-on-surface"><?= $recDateLabel ?></div>
                            <div class="text-[10px] font-medium text-on-surface-variant"><?= $recDayLabel ?></div>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="font-mono text-sm font-semibold text-on-surface-variant">
                                <?= $cin ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border <?= statusBadge($rec['status']) ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= statusDot($rec['status']) ?>"></span>
                                <?= statusLabel($rec['status']) ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="font-mono text-sm font-semibold text-on-surface-variant">
                                <?= $cout ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-6">
                            <?php if ($rec['clock_out']): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border <?= clockOutStatusBadge($rec['clock_out_status'] ?? '') ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= clockOutStatusDot($rec['clock_out_status'] ?? '') ?>"></span>
                                    <?= clockOutStatusLabel($rec['clock_out_status'] ?? '') ?>
                                </span>
                            <?php else: ?>
                                <span class="text-on-surface-variant/30 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="text-sm font-semibold text-on-surface-variant"><?= $wh ?></span>
                        </td>
                        <td class="py-3.5 px-6">
                            <?php if (!empty($rec['work_mode'])): ?>
                                <?php
                                    $wm = $rec['work_mode'];
                                    $modeColor = match($wm) {
                                        'WFA' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'WFH' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    };
                                    $modeIcon = match($wm) {
                                        'WFA' => 'home_work',
                                        'WFH' => 'home',
                                        default => 'business',
                                    };
                                    $hasHomeSet = ($homeLat !== null && $homeLng !== null);
                                    $modeText = match($wm) {
                                        'WFA' => $hasHomeSet ? 'WFA/WFC' : 'WFA/WFC/WFH',
                                        'WFH' => 'WFH',
                                        default => 'WFO',
                                    };
                                ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border <?= $modeColor ?>">
                                    <span class="material-symbols-outlined text-[12px]"><?= $modeIcon ?></span>
                                    <?= $modeText ?>
                                </span>
                            <?php else: ?>
                                <span class="text-on-surface-variant/30 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-6">
                            <?php if (!empty($rec['work_mode_out'])): ?>
                                <?php
                                    $wmo = $rec['work_mode_out'];
                                    $modeColorOut = match($wmo) {
                                        'WFA' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'WFH' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    };
                                    $modeIconOut = match($wmo) {
                                        'WFA' => 'home_work',
                                        'WFH' => 'home',
                                        default => 'business',
                                    };
                                    $hasHomeSet = ($homeLat !== null && $homeLng !== null);
                                    $modeTextOut = match($wmo) {
                                        'WFA' => $hasHomeSet ? 'WFA/WFC' : 'WFA/WFC/WFH',
                                        'WFH' => 'WFH',
                                        default => 'WFO',
                                    };
                                ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border <?= $modeColorOut ?>">
                                    <span class="material-symbols-outlined text-[12px]"><?= $modeIconOut ?></span>
                                    <?= $modeTextOut ?>
                                </span>
                            <?php else: ?>
                                <span class="text-on-surface-variant/30 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-6">
                            <?php if ($rec['location_method']): ?>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm <?= $rec['location_method'] === 'WIFI' ? 'text-primary' : 'text-amber-600' ?>">
                                    <?= $rec['location_method'] === 'WIFI' ? 'wifi' : 'location_on' ?>
                                </span>
                                <span class="att-location-tag font-semibold text-on-surface-variant"><?= htmlspecialchars($rec['location_method']) ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-on-surface-variant/30 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="px-6 py-4 border-t border-outline-variant/10 flex items-center justify-between bg-surface-container-low/30 hidden">
            <span class="text-xs font-semibold text-on-surface-variant" id="pageInfo">Menampilkan 1-10 dari X</span>
            <div class="flex items-center gap-2">
                <button id="btnPrevPage" class="p-1.5 rounded-lg border border-outline-variant/20 text-on-surface hover:bg-surface-container-high transition-colors disabled:opacity-30 disabled:cursor-not-allowed"><span class="material-symbols-outlined text-sm">chevron_left</span></button>
                <button id="btnNextPage" class="p-1.5 rounded-lg border border-outline-variant/20 text-on-surface hover:bg-surface-container-high transition-colors disabled:opacity-30 disabled:cursor-not-allowed"><span class="material-symbols-outlined text-sm">chevron_right</span></button>
            </div>
        </div>
    </div>

</div>

<!-- ── JAVASCRIPT ── -->
<script>
// ── Table Pagination Logic ──────────────────────────────────────────
(function() {
    const rows = Array.from(document.querySelectorAll('.att-table-row'));
    const rowsPerPage = 10;
    let currentPage = 1;
    
    function renderPagination() {
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        if (totalPages <= 1) return;
        
        document.getElementById('paginationControls').classList.remove('hidden');
        
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        document.getElementById('pageInfo').innerText = `Menampilkan ${start + 1} - ${Math.min(end, rows.length)} dari ${rows.length}`;
        
        document.getElementById('btnPrevPage').disabled = currentPage === 1;
        document.getElementById('btnNextPage').disabled = currentPage === totalPages;
    }
    
    if (rows.length > rowsPerPage) {
        document.getElementById('btnPrevPage').addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPagination(); } });
        document.getElementById('btnNextPage').addEventListener('click', () => { if (currentPage < Math.ceil(rows.length / rowsPerPage)) { currentPage++; renderPagination(); } });
        renderPagination();
    }
})();

// ── Office config from PHP settings ──────────────────────────────
const ATT_CFG = {
    officeLat:    <?= (float)($cfg['office_lat']      ?? -6.2297) ?>,
    officeLng:    <?= (float)($cfg['office_lng']      ?? 106.8164) ?>,
    radiusM:      <?= (int)  ($cfg['office_radius_m'] ?? 150) ?>,
    homeLat:      <?= $homeLat !== null ? (float)$homeLat : 'null' ?>,
    homeLng:      <?= $homeLng !== null ? (float)$homeLng : 'null' ?>,
    homeRadiusM:  <?= (int)  ($cfg['home_radius_m']   ?? 100) ?>,
    wfaAllowed:   <?= ($cfg['wfa_allowed'] ?? 'true') === 'true' ? 'true' : 'false' ?>,
    workStart:    '<?= htmlspecialchars($cfg['work_start_time'] ?? '08:00') ?>',
    workEnd:      '<?= htmlspecialchars($cfg['work_end_time']   ?? '17:00') ?>',
    graceMins:    <?= (int)($cfg['grace_period_min']  ?? 10) ?>,
    wifiPrefix:   '<?= htmlspecialchars($cfg['office_wifi_prefix'] ?? '192.168.10.') ?>',
    isOfficeWifi: <?= $isOfficeWifi ? 'true' : 'false' ?>,
};

// ── Live clock ───────────────────────────────────────────────────
(function updateClock() {
    const el = document.getElementById('attLiveClock');
    if (el) {
        const now = new Date();
        el.textContent = [now.getHours(), now.getMinutes(), now.getSeconds()]
            .map(n => String(n).padStart(2, '0')).join(':');
    }
    setTimeout(updateClock, 1000);
})();

// ── Haversine distance (metres) ──────────────────────────────────
function haversineM(lat1, lng1, lat2, lng2) {
    const R = 6371e3; // Earth radius in metres
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c);
}

function formatJsDistance(meters) {
    if (meters === null || meters === undefined) return '';
    if (meters < 1000) {
        return Math.round(meters) + 'm';
    }
    return (meters / 1000).toFixed(1) + ' km';
}

// ── Live location status detector ────────────────────────────────
function detectLocationStatus() {
    const tag  = document.getElementById('locationStatusTag');
    const text = document.getElementById('locationTagText');
    if (!tag || !text) return;

    if (!navigator.geolocation) {
        text.textContent = 'GPS tidak tersedia';
        return;
    }
    navigator.geolocation.getCurrentPosition(pos => {
        const dist = haversineM(ATT_CFG.officeLat, ATT_CFG.officeLng, pos.coords.latitude, pos.coords.longitude);
        const distKm = formatJsDistance(dist);
        
        let distHome = null;
        if (ATT_CFG.homeLat !== null && ATT_CFG.homeLng !== null) {
            distHome = haversineM(ATT_CFG.homeLat, ATT_CFG.homeLng, pos.coords.latitude, pos.coords.longitude);
        }

        const hasHomeSet = (ATT_CFG.homeLat !== null && ATT_CFG.homeLng !== null);
        const modeLabel = hasHomeSet ? 'WFA/WFC' : 'WFA/WFC/WFH';

        if (distHome !== null && distHome <= ATT_CFG.homeRadiusM) {
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-500/20 text-indigo-200 border border-indigo-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">home</span><span>WFH · Dalam radius rumah (' + formatJsDistance(distHome) + ')</span>';
        } else if (dist <= ATT_CFG.radiusM) {
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-green-500/20 text-green-200 border border-green-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">business</span><span>WFO · Dalam radius kantor (' + formatJsDistance(dist) + ')</span>';
        } else if (ATT_CFG.wfaAllowed) {
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-500/20 text-blue-200 border border-blue-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">home_work</span><span>' + modeLabel + ' · ' + distKm + ' dari kantor (' + modeLabel + ' aktif)</span>';
        } else {
            let homeStr = distHome !== null ? ' & rumah (' + formatJsDistance(distHome) + ')' : '';
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-red-500/20 text-red-200 border border-red-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">location_off</span><span>Di luar kantor (' + distKm + ')' + homeStr + ' · ' + modeLabel + ' nonaktif</span>';
        }
    }, () => {
        if (ATT_CFG.isOfficeWifi) {
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-green-500/20 text-green-200 border border-green-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">wifi</span><span>WFO · Terdeteksi via WIFI Kantor</span>';
        } else {
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-200 border border-amber-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">warning</span><span>GPS Gagal Dideteksi. Aktifkan lokasi atau gunakan WIFI Kantor.</span>';
        }
    }, { timeout: 7000, enableHighAccuracy: true, maximumAge: 0 });
}
window.detectLocationStatus = detectLocationStatus;

// Only run detector if not yet clocked in today
<?php if (!$hasClockIn): ?>
detectLocationStatus();
<?php elseif ($todayMode): ?>
// Already clocked in — show recorded mode
(function() {
    const tag  = document.getElementById('locationStatusTag');
    const mode = '<?= htmlspecialchars($todayMode) ?>';
    if (!tag) return;
    if (mode === 'WFA') {
        const hasHomeSet = <?= ($homeLat !== null && $homeLng !== null) ? 'true' : 'false' ?>;
        const modeLabel = hasHomeSet ? 'WFA/WFC' : 'WFA/WFC/WFH';
        tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-500/20 text-blue-200 border border-blue-400/30';
        tag.innerHTML = '<span class="material-symbols-outlined text-sm">home_work</span><span>Mode ' + modeLabel + ' Aktif Hari Ini</span>';
    } else if (mode === 'WFH') {
        tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-500/20 text-indigo-200 border border-indigo-400/30';
        tag.innerHTML = '<span class="material-symbols-outlined text-sm">home</span><span>Mode WFH Aktif Hari Ini</span>';
    } else if (mode === 'WFO') {
        tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-green-500/20 text-green-200 border border-green-400/30';
        tag.innerHTML = '<span class="material-symbols-outlined text-sm">business</span><span>Mode WFO Aktif Hari Ini</span>';
    }
})();
<?php endif; ?>

// ── Get geolocation helper ────────────────────────────────────────
function getLocation(callback) {
    if (!navigator.geolocation) {
        return callback(null, null, 'no_gps', 'Perangkat atau browser Anda tidak mendukung fitur Geolocation.');
    }

    // Detect if getCurrentPosition or watchPosition has been overridden by spoofing extensions
    const isMockedAPI = navigator.geolocation.getCurrentPosition.toString().indexOf('[native code]') === -1 || 
                        navigator.geolocation.watchPosition.toString().indexOf('[native code]') === -1;

    if (isMockedAPI) {
        return callback(null, null, 'fake_gps', 'Sistem mendeteksi adanya manipulasi lokasi menggunakan ekstensi Fake GPS atau lokasi tiruan (Mock Location).');
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            // Suspiciously perfect accuracy check (e.g. exactly 0 or negative)
            if (pos.coords.accuracy <= 0) {
                return callback(null, null, 'fake_gps', 'Akurasi GPS mencurigakan (tidak wajar). Harap gunakan perangkat fisik asli tanpa emulator.');
            }
            callback(pos.coords.latitude, pos.coords.longitude, null, null);
        },
        error => {
            let reason = 'gps_failed';
            let msg = 'Gagal mengambil koordinat lokasi perangkat Anda.';
            if (error.code === error.PERMISSION_DENIED) {
                reason = 'permission_denied';
                msg = 'Izin akses lokasi (Location Permission) diblokir oleh browser Anda.';
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                reason = 'unavailable';
                msg = 'Sinyal GPS lemah atau koordinat lokasi tidak dapat ditentukan.';
            } else if (error.code === error.TIMEOUT) {
                reason = 'timeout';
                msg = 'Waktu pengambilan koordinat lokasi habis (Timeout).';
            }
            callback(null, null, reason, msg);
        },
        { timeout: 6000, enableHighAccuracy: true, maximumAge: 0 }
    );
}
window.getLocation = getLocation;

// ── Location Error Handler UI ──────────────────────────────────────
function showLocationErrorAlert(titlePrefix, errCode, errMsg, btnEl, originalBtnHtml) {
    let title = '⚠️ Akses Lokasi Ditolak';
    let solutionHtml = '';

    if (errCode === 'fake_gps') {
        title = '⚠️ Manipulasi GPS Terdeteksi!';
        solutionHtml = `
            <div class="text-left bg-red-50 p-4 rounded-xl border border-red-200 mt-3 text-xs space-y-2 text-red-950 leading-relaxed font-medium">
                <p class="font-bold text-red-800 text-sm">Alasan Gagal:</p>
                <p class="text-gray-700">${errMsg}</p>
                <p class="font-bold text-red-800 text-sm mt-3">Solusi Penyelesaian:</p>
                <ul class="list-decimal list-inside space-y-1 text-gray-700">
                    <li>Nonaktifkan atau hapus ekstensi browser <strong>Fake GPS / Location Spoofer</strong>.</li>
                    <li>Pastikan Anda tidak menggunakan emulator Android atau browser developer tool.</li>
                    <li>Buka portal siCare lewat browser resmi di <strong>ponsel fisik asli</strong> Anda.</li>
                    <li>Muat ulang (*refresh*) halaman dan lakukan presensi kembali.</li>
                </ul>
            </div>
        `;
    } else if (errCode === 'permission_denied') {
        title = '⚠️ Izin Lokasi Diblokir';
        solutionHtml = `
            <div class="text-left bg-amber-50 p-4 rounded-xl border border-amber-200 mt-3 text-xs space-y-2 text-amber-950 leading-relaxed font-medium">
                <p class="font-bold text-amber-800 text-sm">Alasan Gagal:</p>
                <p class="text-gray-700">${errMsg}</p>
                <p class="font-bold text-amber-800 text-sm mt-3">Solusi Penyelesaian:</p>
                <ul class="list-decimal list-inside space-y-1 text-gray-700">
                    <li>Klik ikon <strong>gembok / pengaturan situs</strong> di sebelah kiri kolom URL browser Anda.</li>
                    <li>Ubah status izin <strong>"Lokasi" (Location)</strong> menjadi <strong>"Izinkan" (Allow)</strong>.</li>
                    <li>Muat ulang (*refresh*) halaman dan lakukan presensi kembali.</li>
                </ul>
            </div>
        `;
    } else {
        title = '⚠️ Sinyal GPS Lemah / Tidak Aktif';
        solutionHtml = `
            <div class="text-left bg-amber-50 p-4 rounded-xl border border-amber-200 mt-3 text-xs space-y-2 text-amber-950 leading-relaxed font-medium">
                <p class="font-bold text-amber-800 text-sm">Alasan Gagal:</p>
                <p class="text-gray-700">${errMsg || 'Perangkat gagal mendeteksi koordinat GPS aktif.'}</p>
                <p class="font-bold text-amber-800 text-sm mt-3">Solusi Penyelesaian:</p>
                <ul class="list-decimal list-inside space-y-1 text-gray-700">
                    <li>Pastikan fitur <strong>GPS / Layanan Lokasi</strong> di perangkat Anda sudah <strong>AKTIF</strong>.</li>
                    <li>Jika berada di dalam ruangan/gedung, cobalah bergeser mendekati jendela untuk sinyal yang lebih kuat.</li>
                    <li>Hubungkan ke koneksi <strong>Wi-Fi Kantor</strong> jika tersedia untuk mempermudah deteksi.</li>
                    <li>Muat ulang (*refresh*) halaman dan lakukan presensi kembali.</li>
                </ul>
            </div>
        `;
    }

    Swal.fire({
        title: title,
        html: solutionHtml,
        icon: 'error',
        confirmButtonColor: '#ba1a1a',
        confirmButtonText: 'Tutup & Perbaiki'
    });

    if (btnEl) {
        btnEl.innerHTML = originalBtnHtml;
        btnEl.disabled = false;
    }
}

// ── Clock-In ──────────────────────────────────────────────────────
function doClockIn() {
    const btn = document.getElementById('btnClockIn');
    if (!btn) return;
    const originalBtn = '<span class="material-symbols-outlined font-bold">login</span> Clock-In';
    btn.innerHTML = '<div class="att-spinner"></div><span>Memproses...</span>';
    btn.disabled = true;

    getLocation((lat, lng, errCode, errMsg) => {
        if (lat === null || lng === null) {
            showLocationErrorAlert('Gagal Clock-In', errCode, errMsg, btn, originalBtn);
            return;
        }

        const fd = new FormData();
        fd.append('lat', lat);
        fd.append('lng', lng);

        fetch('/employee/attendance/clockin', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const isWfa = data.work_mode === 'WFA';
                const isWfh = data.work_mode === 'WFH';
                const statusColor = data.status === 'tepat waktu' ? 'bg-green-100 text-green-700' : (data.status === 'awal' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700');
                const modeColor   = isWfa ? 'bg-blue-100 text-blue-700' : (isWfh ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700');
                const modeIcon    = isWfa ? '☕' : (isWfh ? '🏠' : '🏢');
                const modeLabel   = isWfa ? 'WFA/WFC/WFH (Work From Anywhere/Cafe/Home)' : (isWfh ? 'WFH (Work From Home)' : 'WFO (Work From Office)');
                const statusText  = data.status === 'tepat waktu' ? '🟢 Tepat Waktu' : (data.status === 'awal' ? '🔵 Masuk Awal' : '🟡 Terlambat');
                Swal.fire({
                    title: '✅ Clock-In Berhasil!',
                    html: `<p class="text-sm text-gray-600 mb-3">${data.message}</p>
                           <div class="flex flex-col gap-2">
                               <div class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-full text-xs font-bold ${statusColor}">
                                   ${statusText}
                                </div>
                               <div class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-full text-xs font-bold ${modeColor}">
                                   ${modeIcon} ${modeLabel}
                                </div>
                           </div>`,
                    icon: 'success',
                    confirmButtonColor: '#000666',
                    confirmButtonText: 'Lihat Status'
                }).then(() => {
                    if (window.loadPage) {
                        window.loadPage('/employee/attendance');
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({ title: 'Gagal Clock-In', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
                btn.innerHTML = originalBtn;
                btn.disabled = false;
            }
        })
        .catch(() => {
            Swal.fire({ title: 'Error Koneksi', text: 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.', icon: 'error', confirmButtonColor: '#ba1a1a' });
            btn.innerHTML = originalBtn;
            btn.disabled = false;
        });
    });
}
window.doClockIn = doClockIn;

// ── Clock-Out ─────────────────────────────────────────────────────
function doClockOut() {
    const btn = document.getElementById('btnClockOut');
    if (!btn) return;
    const originalBtn = '<span class="material-symbols-outlined font-bold">logout</span> Clock-Out';

    Swal.fire({
        title: 'Konfirmasi Clock-Out',
        text: 'Apakah Anda yakin ingin melakukan Clock-Out sekarang? Tindakan ini tidak dapat dibatalkan untuk hari ini.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff6f00',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Ya, Clock-Out',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (!result.isConfirmed) return;
        btn.innerHTML = '<div class="att-spinner"></div><span>Memproses...</span>';
        btn.disabled = true;

        getLocation((lat, lng, errCode, errMsg) => {
            if (lat === null || lng === null) {
                showLocationErrorAlert('Gagal Clock-Out', errCode, errMsg, btn, originalBtn);
                return;
            }

            const fd = new FormData();
            fd.append('lat', lat);
            fd.append('lng', lng);
            
            fetch('/employee/attendance/clockout', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '✅ Clock-Out Berhasil!',
                        html: `<p class="text-sm text-gray-600">${data.message}</p>`,
                        icon: 'success',
                        confirmButtonColor: '#000666',
                    }).then(() => {
                        if (window.loadPage) {
                            window.loadPage('/employee/attendance');
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({ title: 'Gagal Clock-Out', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
                    btn.innerHTML = originalBtn;
                    btn.disabled = false;
                }
            })
            .catch(() => {
                Swal.fire({ title: 'Error Koneksi', text: 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.', icon: 'error', confirmButtonColor: '#ba1a1a' });
                btn.innerHTML = originalBtn;
                btn.disabled = false;
            });
        });
    });
}
window.doClockOut = doClockOut;
</script>