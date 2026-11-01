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

$selectedMonth = $_GET['month'] ?? date('m');
$selectedYear  = $_GET['year'] ?? date('Y');

if (!is_numeric($selectedMonth) || (int)$selectedMonth < 1 || (int)$selectedMonth > 12) {
    $selectedMonth = date('m');
}
$selectedMonth = str_pad($selectedMonth, 2, '0', STR_PAD_LEFT);

if (!is_numeric($selectedYear) || (int)$selectedYear < 2000 || (int)$selectedYear > 2100) {
    $selectedYear = date('Y');
}

if ($userId) {
    try {
        $ctrl = new \App\Controllers\AttendanceController();
        $attData = $ctrl->getEmployeeData($userId, $selectedMonth, $selectedYear);
        
        $dbUser = \App\Services\ViewHelper::getFullUserProfile($userId);
        $homeLat = $dbUser && isset($dbUser['home_latitude']) && $dbUser['home_latitude'] !== null ? (float)$dbUser['home_latitude'] : null;
        $homeLng = $dbUser && isset($dbUser['home_longitude']) && $dbUser['home_longitude'] !== null ? (float)$dbUser['home_longitude'] : null;
        $userMetodePresensi = $dbUser['metode_presensi'] ?? 'default';
    } catch (Exception $e) {
        $userMetodePresensi = 'default';
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
        $nationalHoliday = \App\Services\ViewHelper::getNationalHoliday($todayDate);
        
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
        'tepat waktu', 'wajar', 'normal' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'tidak presensi pulang' => 'bg-rose-50 text-rose-700 border-rose-200',
        default         => 'bg-surface-container-low text-on-surface-variant/40 border-outline-variant/10',
    };
}
function clockOutStatusDot($status) {
    return match($status) {
        'pulang lambat' => 'bg-amber-500',
        'pulang cepat'  => 'bg-red-500',
        'tepat waktu', 'wajar', 'normal' => 'bg-emerald-500',
        'tidak presensi pulang' => 'bg-rose-500',
        default         => 'bg-on-surface-variant/20',
    };
}
function clockOutStatusLabel($status) {
    return match($status) {
        'pulang lambat' => 'Pulang Lambat',
        'pulang cepat'  => 'Pulang Cepat',
        'tepat waktu', 'wajar', 'normal' => 'Tepat Waktu',
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
    transition: transform 0.5s ease;
}
.att-hero-card:hover::before {
    transform: scale(1.1);
}
.att-hero-card::after {
    content: '';
    position: absolute;
    bottom: -60%; left: -5%;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,0.03);
    pointer-events: none;
    transition: transform 0.5s ease;
}
.att-hero-card:hover::after {
    transform: scale(1.1);
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
                <div class="mt-3 flex flex-wrap gap-2.5">
                    <!-- Location status tag (updated by JS) -->
                    <div id="locationStatusTag" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-white/10 text-white/80 border border-white/15">
                        <span class="material-symbols-outlined text-sm">my_location</span>
                        <span id="locationTagText">Mendeteksi lokasi...</span>
                    </div>
                    

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
        <?php
        $monthsList = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $currentYearInt = (int)date('Y');
        $yearsList = range($currentYearInt - 3, $currentYearInt + 1);

        $selectedMonthName = date('F', strtotime("{$selectedYear}-{$selectedMonth}-01"));
        $monthLabelIndo = $monthsFullIndo[$selectedMonthName] ?? $selectedMonthName;
        ?>
        <div class="p-6 border-b border-outline-variant/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary">calendar_month</span>
                <h2 class="text-base font-extrabold text-on-surface">Riwayat Presensi – <?= $monthLabelIndo . ' ' . $selectedYear ?></h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <select id="filter_month" onchange="window.changeFilter()" class="text-xs font-bold text-on-surface bg-surface-container-low border border-outline-variant/10 pl-3 pr-8 py-1.5 rounded-xl appearance-none outline-none cursor-pointer focus:border-primary transition-all">
                        <?php foreach ($monthsList as $mVal => $mName): ?>
                            <option value="<?= $mVal ?>" <?= $selectedMonth === $mVal ? 'selected' : '' ?>><?= $mName ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filter_year" onchange="window.changeFilter()" class="text-xs font-bold text-on-surface bg-surface-container-low border border-outline-variant/10 pl-3 pr-8 py-1.5 rounded-xl appearance-none outline-none cursor-pointer focus:border-primary transition-all">
                        <?php foreach ($yearsList as $yVal): ?>
                            <option value="<?= $yVal ?>" <?= (int)$selectedYear === $yVal ? 'selected' : '' ?>><?= $yVal ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider bg-surface-container-low px-3 py-1.5 rounded-full whitespace-nowrap">
                    <?= count($attData['monthly']) ?> Catatan
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-standardized" data-has-custom-pagination="true">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/10">
                        <th class="no-col w-12 text-center py-3 px-6 text-[10px] font-bold uppercase tracking-wider">No</th>
                        <th onclick="window.sortDomTable(this, 1, 'date')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Tanggal
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 2, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Clock-In
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 3, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Status Clock-In
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 4, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Clock-Out
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 5, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Status Clock-Out
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 6, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Jam Kerja
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 7, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Mode Masuk
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 8, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Mode Pulang
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                        <th onclick="window.sortDomTable(this, 9, 'string')" class="py-3 px-6 text-[10px] font-bold uppercase tracking-wider">
                            <div class="flex items-center gap-1">
                                Metode
                                <span class="sort-icon-container">
                                    <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                                    <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/8">
                    <?php if (empty($attData['monthly'])): ?>
                    <tr>
                        <td colspan="10" class="py-16 px-6 text-center">
                            <span class="material-symbols-outlined text-4xl text-outline-variant block mb-3">event_busy</span>
                            <p class="text-on-surface-variant font-semibold text-sm">Belum ada riwayat presensi bulan ini.</p>
                            <p class="text-on-surface-variant/60 text-xs mt-1">Lakukan Clock-In pertama Anda hari ini!</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php $rowNo = 1; foreach ($attData['monthly'] as $rec): ?>
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
                    <tr class="att-table-row hover:bg-surface-container-low/20 transition-colors">
                        <td class="py-3.5 px-6 text-center font-bold text-xs text-on-surface-variant"><?= $rowNo++ ?></td>
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
                                <span class="material-symbols-outlined text-sm <?= $rec['location_method'] === 'WIFI' ? 'text-primary' : ($rec['location_method'] === 'POP' ? 'text-blue-600' : 'text-amber-600') ?>">
                                    <?= $rec['location_method'] === 'WIFI' ? 'wifi' : ($rec['location_method'] === 'POP' ? 'laptop' : 'location_on') ?>
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
            <div id="attendancePaginationInfo" class="table-pagination-info text-sm text-on-surface-variant font-medium"></div>
            <div class="flex items-center gap-1.5">
                <!-- First Page -->
                <button id="btnAttendanceFirstPage" onclick="window.firstAttendancePage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Pertama">
                    <span class="material-symbols-outlined text-sm">first_page</span>
                </button>
                <!-- Prev Page -->
                <button id="btnPrevPage" onclick="window.prevAttendancePage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Sebelumnya">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                
                <!-- Page numbers container -->
                <div id="attendancePageNumbers" class="flex items-center gap-1"></div>

                <!-- Next Page -->
                <button id="btnNextPage" onclick="window.nextAttendancePage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Berikutnya">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
                <!-- Last Page -->
                <button id="btnAttendanceLastPage" onclick="window.lastAttendancePage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Terakhir">
                    <span class="material-symbols-outlined text-sm">last_page</span>
                </button>
            </div>
        </div>
    </div>

</div>

<!-- ── MODAL KAMERA SWAFOTO (metode: foto) ── -->
<div id="attCameraModal" class="fixed inset-0 z-[9999] bg-black/85 backdrop-blur-md hidden flex items-center justify-center p-4">
    <div class="bg-surface-container-lowest border border-white/10 rounded-3xl p-6 max-w-md w-full shadow-2xl relative flex flex-col items-center">
        <button type="button" onclick="window.closeCameraModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest flex items-center justify-center transition-all">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-primary text-2xl">photo_camera</span>
            <h3 class="text-base font-bold text-on-surface">Verifikasi Swafoto Kamera</h3>
        </div>

        <!-- Camera Container -->
        <div class="relative w-full aspect-[4/3] bg-black rounded-2xl overflow-hidden border border-outline-variant/30 flex items-center justify-center">
            <video id="attCameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
            <canvas id="attCameraCanvas" class="hidden"></canvas>
            <img id="attPhotoPreview" class="w-full h-full object-cover hidden" alt="Preview Foto">
            
            <!-- Grid overlay for camera composition -->
            <div id="attCameraGrid" class="absolute inset-0 pointer-events-none border-2 border-white/20 rounded-2xl grid grid-cols-3 grid-rows-3 opacity-40">
                <div class="border-r border-b border-white/20"></div>
                <div class="border-r border-b border-white/20"></div>
                <div class="border-b border-white/20"></div>
                <div class="border-r border-b border-white/20"></div>
                <div class="border-r border-b border-white/20"></div>
                <div class="border-b border-white/20"></div>
                <div class="border-r border-white/20"></div>
                <div class="border-r border-white/20"></div>
                <div></div>
            </div>
        </div>

        <!-- Camera Switcher Pill Toggle (Front vs Rear) -->
        <div class="mt-4 flex items-center bg-surface-container-low p-1 rounded-2xl border border-outline-variant/20 gap-1 w-full">
            <button type="button" id="btnCamFront" onclick="window.switchCameraLens('user')" class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-primary text-white shadow-sm">
                <span class="material-symbols-outlined text-sm">person</span> Kamera Depan
            </button>
            <button type="button" id="btnCamRear" onclick="window.switchCameraLens('environment')" class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined text-sm">photo_camera_back</span> Kamera Belakang
            </button>
        </div>

        <!-- Action Controls -->
        <div class="mt-5 flex gap-3 w-full">
            <button type="button" id="btnCapturePhoto" onclick="window.capturePhotoFrame()" class="flex-1 bg-primary hover:bg-primary/90 text-white font-extrabold text-xs py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 shadow-lg transition-all">
                <span class="material-symbols-outlined text-base">aperture</span> Jepret Foto
            </button>
            <button type="button" id="btnRetakePhoto" onclick="window.retakePhotoFrame()" class="hidden flex-1 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-extrabold text-xs py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all">
                <span class="material-symbols-outlined text-base">refresh</span> Foto Ulang
            </button>
            <button type="button" id="btnSubmitPhoto" onclick="window.submitPhotoAttendance()" class="hidden flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 shadow-lg transition-all">
                <span class="material-symbols-outlined text-base">send</span> Kirim Presensi
            </button>
        </div>
        <p class="text-[10px] text-on-surface-variant/70 mt-3 text-center">Ukuran foto otomatis dikompresi maksimal 1MB secara aman di browser.</p>
    </div>
</div>

<!-- ── MODAL SCAN QR CODE (metode: scan_qr) ── -->
<div id="attQrModal" class="fixed inset-0 z-[9999] bg-black/85 backdrop-blur-md hidden flex items-center justify-center p-4">
    <div class="bg-surface-container-lowest border border-white/10 rounded-3xl p-6 max-w-md w-full shadow-2xl relative flex flex-col items-center">
        <button type="button" onclick="window.closeQrModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest flex items-center justify-center transition-all">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-primary text-2xl">qr_code_scanner</span>
            <h3 class="text-base font-bold text-on-surface">Scan QR Code Presensi</h3>
        </div>

        <div class="relative w-full aspect-square bg-black rounded-2xl overflow-hidden border border-outline-variant/30 flex items-center justify-center">
            <video id="attQrVideo" autoplay playsinline class="w-full h-full object-cover"></video>
            <!-- Target Box Frame -->
            <div class="absolute inset-8 border-2 border-primary/80 rounded-2xl pointer-events-none flex items-center justify-center shadow-[0_0_20px_rgba(59,130,246,0.3)]">
                <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-primary to-transparent animate-pulse"></div>
            </div>
        </div>

        <!-- Manual QR Code Text Fallback Input -->
        <div class="mt-4 w-full">
            <p class="text-xs text-on-surface-variant text-center mb-2 font-medium">Arahkan kamera ke QR Code atau ketik kode QR:</p>
            <div class="flex gap-2">
                <input type="text" id="attManualQrInput" onkeypress="if(event.key==='Enter') window.submitManualQrCode()" placeholder="Ketik Kode QR (Cth: QR-839283)..." class="flex-1 bg-surface-container-low border border-outline-variant/30 text-on-surface text-xs rounded-xl px-3 py-2.5 focus:outline-none focus:border-primary">
                <button type="button" onclick="window.submitManualQrCode()" class="bg-primary hover:bg-primary/90 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">check</span> Ok
                </button>
            </div>
        </div>
        <p class="text-[10px] text-emerald-600 font-bold mt-3 text-center bg-emerald-50 px-3 py-1.5 rounded-full flex items-center justify-center gap-1">
            <span class="material-symbols-outlined text-xs">bolt</span> Presensi langsung terproses otomatis begitu QR Code ter-scan!
        </p>
    </div>
</div>

<!-- ── MODAL TAP NFC / KARTU (metode: nfc) ── -->
<div id="attNfcModal" class="fixed inset-0 z-[9999] bg-black/85 backdrop-blur-md hidden flex items-center justify-center p-4">
    <div class="bg-surface-container-lowest border border-white/10 rounded-3xl p-6 max-w-md w-full shadow-2xl relative flex flex-col items-center text-center">
        <button type="button" onclick="window.closeNfcModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest flex items-center justify-center transition-all">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>

        <!-- Hardware Unsupported Warning Banner (Shown on PC / Devices without NFC) -->
        <div id="attNfcHardwareNotice" class="hidden w-full bg-amber-500/10 border border-amber-500/30 text-amber-800 rounded-2xl p-3 text-left text-xs mb-2 flex items-start gap-2.5">
            <span class="material-symbols-outlined text-amber-600 text-lg shrink-0 mt-0.5">warning</span>
            <div>
                <strong class="font-bold block text-amber-900 mb-0.5">Hardware NFC Tidak Didukung</strong>
                <span>Perangkat / browser ini tidak dilengkapi sensor Web NFC internal. Silakan gunakan USB RFID Card Reader atau ketik ID Kartu Akses Anda di bawah.</span>
            </div>
        </div>
        
        <!-- Glowing NFC Card Tap Animation -->
        <div class="relative my-4 flex items-center justify-center">
            <div id="attNfcPingBg" class="w-32 h-32 rounded-full bg-primary/10 animate-ping absolute"></div>
            <div id="attNfcIconBox" class="w-24 h-24 rounded-full bg-primary/20 flex items-center justify-center relative z-10 shadow-[0_0_30px_rgba(59,130,246,0.4)]">
                <span id="attNfcIcon" class="material-symbols-outlined text-5xl text-primary animate-pulse">contactless</span>
            </div>
        </div>

        <h3 id="attNfcTitle" class="text-xl font-black text-on-surface">Tap Kartu Akses / NFC</h3>
        <p id="attNfcDesc" class="text-xs text-on-surface-variant mt-1.5 max-w-xs leading-relaxed">
            Tempelkan Kartu Akses / HP berfitur NFC Anda pada bagian belakang perangkat ini.
        </p>

        <!-- Manual RFID / NFC Card ID Input Fallback -->
        <div class="mt-4 w-full bg-surface-container-low p-4 rounded-2xl border border-outline-variant/20">
            <p class="text-[11px] font-bold text-on-surface-variant mb-2">Input ID Kartu / USB RFID Reader:</p>
            <div class="flex gap-2">
                <input type="text" id="attManualNfcInput" onkeypress="if(event.key==='Enter') window.submitManualNfcCard()" placeholder="Tap Kartu atau ketik ID (Cth: AC-829381)..." class="flex-1 bg-surface-container-lowest border border-outline-variant/30 text-on-surface text-xs rounded-xl px-3 py-2.5 focus:outline-none focus:border-primary font-mono">
                <button type="button" onclick="window.submitManualNfcCard()" class="bg-primary hover:bg-primary/90 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">check</span> Submit
                </button>
            </div>
        </div>

        <p id="attNfcStatusBadge" class="text-[10px] text-emerald-600 font-bold mt-4 text-center bg-emerald-50 px-3 py-1.5 rounded-full flex items-center justify-center gap-1">
            <span class="material-symbols-outlined text-xs">bolt</span> Presensi langsung terproses otomatis begitu kartu ter-tap!
        </p>
    </div>
</div>

<!-- ── JAVASCRIPT ── -->
<script>
window.userMetodePresensi = <?= json_encode($userMetodePresensi) ?>;

// ── Table Pagination Logic ──────────────────────────────────────────
(function() {
    const rows = Array.from(document.querySelectorAll('.att-table-row'));
    const rowsPerPage = 10;
    let currentPage = 1;
    let totalPages = Math.ceil(rows.length / rowsPerPage) || 1;
    
    function renderPagination() {
        if (totalPages <= 1) {
            document.getElementById('paginationControls').classList.add('hidden');
            rows.forEach(row => { row.style.display = ''; });
            return;
        }
        
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
        
        // Info text
        const infoEl = document.getElementById('attendancePaginationInfo');
        if (infoEl) {
            const startShow = rows.length === 0 ? 0 : start + 1;
            const endShow = Math.min(end, rows.length);
            infoEl.textContent = 'Menampilkan data ' + startShow + ' sampai ' + endShow + ' dari ' + rows.length;
        }

        // Disabled states
        const firstBtn = document.getElementById('btnAttendanceFirstPage');
        const prevBtn = document.getElementById('btnPrevPage');
        const nextBtn = document.getElementById('btnNextPage');
        const lastBtn = document.getElementById('btnAttendanceLastPage');

        if (firstBtn) firstBtn.disabled = currentPage === 1;
        if (prevBtn) prevBtn.disabled = currentPage === 1;
        if (nextBtn) nextBtn.disabled = currentPage === totalPages;
        if (lastBtn) lastBtn.disabled = currentPage === totalPages;

        // Page numbers (max 3 centered)
        const pageNumbersContainer = document.getElementById('attendancePageNumbers');
        if (pageNumbersContainer) {
            pageNumbersContainer.innerHTML = '';

            let startPage = currentPage - 1;
            let endPage = currentPage + 1;
            if (startPage < 1) {
                startPage = 1;
                endPage = Math.min(3, totalPages);
            }
            if (endPage > totalPages) {
                endPage = totalPages;
                startPage = Math.max(1, totalPages - 2);
            }

            for (let p = startPage; p <= endPage; p++) {
                (function(pageNum) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-8 h-8 flex items-center justify-center rounded-full text-xs font-semibold transition-all border ';
                    if (pageNum === currentPage) {
                        btn.className += 'bg-primary text-white border-primary shadow-sm';
                    } else {
                        btn.className += 'hover:bg-surface-container-high text-on-surface border-transparent';
                    }
                    btn.textContent = pageNum;
                    btn.onclick = function() {
                        currentPage = pageNum;
                        renderPagination();
                    };
                    pageNumbersContainer.appendChild(btn);
                })(p);
            }
        }
    }
    
    window.firstAttendancePage = function() {
        if (currentPage > 1) {
            currentPage = 1;
            renderPagination();
        }
    };

    window.prevAttendancePage = function() {
        if (currentPage > 1) {
            currentPage--;
            renderPagination();
        }
    };

    window.nextAttendancePage = function() {
        if (currentPage < totalPages) {
            currentPage++;
            renderPagination();
        }
    };

    window.lastAttendancePage = function() {
        if (currentPage < totalPages) {
            currentPage = totalPages;
            renderPagination();
        }
    };

    // Initial render
    renderPagination();
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
    csrfToken:    '<?= csrf_token() ?>',
    hasClockIn:   <?= $hasClockIn ? 'true' : 'false' ?>,
    hasClockOut:  <?= $hasClockOut ? 'true' : 'false' ?>,
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
    const km = meters / 1000;
    return parseFloat(km.toFixed(2)) + ' km';
}

// ── Live location status detector ────────────────────────────────
function detectLocationStatus() {
    const tag  = document.getElementById('locationStatusTag');
    const text = document.getElementById('locationTagText');
    if (!tag || !text) {
        if (window.attendanceLocationInterval) {
            clearInterval(window.attendanceLocationInterval);
            window.attendanceLocationInterval = null;
        }
        return;
    }

    if (!navigator.geolocation) {
        text.textContent = 'GPS tidak tersedia';
        return;
    }
    const processLocationSuccess = (pos) => {
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
            let detailStr = distKm + ' dari kantor';
            if (distHome !== null) {
                detailStr += ', ' + formatJsDistance(distHome) + ' dari rumah';
            }
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-500/20 text-blue-200 border border-blue-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">home_work</span><span>' + modeLabel + ' · ' + detailStr + ' (' + modeLabel + ' aktif)</span>';
        } else {
            let homeStr = distHome !== null ? ' & rumah (' + formatJsDistance(distHome) + ')' : '';
            tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-red-500/20 text-red-200 border border-red-400/30';
            tag.innerHTML = '<span class="material-symbols-outlined text-sm">location_off</span><span>Di luar kantor (' + distKm + ')' + homeStr + ' · ' + modeLabel + ' nonaktif</span>';
        }
    };

    const tryDetectPosition = (highAcc) => {
        navigator.geolocation.getCurrentPosition(
            processLocationSuccess,
            (err) => {
                if (highAcc && (err.code === err.TIMEOUT || err.code === err.POSITION_UNAVAILABLE)) {
                    // Fallback to Network / Wi-Fi Triangulation (Fast Indoor Geolocation)
                    tryDetectPosition(false);
                    return;
                }
                if (ATT_CFG.isOfficeWifi) {
                    tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-green-500/20 text-green-200 border border-green-400/30';
                    tag.innerHTML = '<span class="material-symbols-outlined text-sm">wifi</span><span>WFO · Terdeteksi via WIFI Kantor</span>';
                } else if (err && err.code === err.PERMISSION_DENIED) {
                    tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-200 border border-amber-400/30 cursor-pointer hover:bg-amber-500/30 transition-all';
                    tag.innerHTML = '<span class="material-symbols-outlined text-sm">warning</span><span>Izin Lokasi Diblokir · <button type="button" onclick="window.showLocationPermissionGuide()" class="underline font-black">Klik Buka Izin Lokasi</button></span>';
                } else {
                    tag.className = 'mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-200 border border-amber-400/30';
                    tag.innerHTML = '<span class="material-symbols-outlined text-sm">warning</span><span>GPS Gagal Dideteksi. Pastikan Lokasi/Wi-Fi HP Aktif atau <button type="button" onclick="detectLocationStatus()" class="underline font-black">Coba Lagi</button></span>';
                }
            },
            { enableHighAccuracy: highAcc, timeout: highAcc ? 4000 : 12000, maximumAge: highAcc ? 0 : 30000 }
        );
    };

    tryDetectPosition(true);
}
window.detectLocationStatus = detectLocationStatus;

// Run detector if not clocked in, OR clocked in but not clocked out yet
<?php if (!$hasClockIn || ($hasClockIn && !$hasClockOut)): ?>
detectLocationStatus();
if (window.attendanceLocationInterval) {
    clearInterval(window.attendanceLocationInterval);
}
window.attendanceLocationInterval = setInterval(detectLocationStatus, 60000);
<?php elseif ($todayMode): ?>
// Already clocked in and clocked out — show recorded mode
(function() {
    const tag  = document.getElementById('locationStatusTag');
    const modeIn = '<?= htmlspecialchars($today['work_mode'] ?? '') ?>';
    const modeOut = '<?= htmlspecialchars($today['work_mode_out'] ?? '') ?>';
    if (!tag) return;
    
    const hasHomeSet = <?= ($homeLat !== null && $homeLng !== null) ? 'true' : 'false' ?>;
    const modeLabelIn = modeIn === 'WFA' ? (hasHomeSet ? 'WFA/WFC' : 'WFA/WFC/WFH') : modeIn;
    const modeLabelOut = modeOut === 'WFA' ? (hasHomeSet ? 'WFA/WFC' : 'WFA/WFC/WFH') : modeOut;
    
    tag.className = 'mt-3 inline-flex flex-col gap-1 items-start px-3 py-1.5 rounded-xl text-xs font-bold bg-white/10 text-white/80 border border-white/15';
    tag.innerHTML = `
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">login</span>
            <span>Masuk: Mode ${modeLabelIn}</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">logout</span>
            <span>Pulang: Mode ${modeLabelOut || '—'}</span>
        </div>
    `;
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

    const tryGetPos = (highAcc) => {
        navigator.geolocation.getCurrentPosition(
            pos => {
                if (pos.coords.accuracy <= 0) {
                    return callback(null, null, 'fake_gps', 'Akurasi GPS mencurigakan (tidak wajar). Harap gunakan perangkat fisik asli tanpa emulator.');
                }
                callback(pos.coords.latitude, pos.coords.longitude, null, null);
            },
            error => {
                if (highAcc && (error.code === error.TIMEOUT || error.code === error.POSITION_UNAVAILABLE)) {
                    // Fallback to Network / Wi-Fi Triangulation (Fast Indoor Geolocation)
                    tryGetPos(false);
                    return;
                }

                let reason = 'gps_failed';
                let msg = 'Gagal mengambil koordinat lokasi perangkat Anda.';
                if (error.code === error.PERMISSION_DENIED) {
                    reason = 'permission_denied';
                    msg = 'Izin akses lokasi (Location Permission) diblokir oleh browser Anda.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    reason = 'unavailable';
                    msg = 'Sinyal GPS lemah atau koordinat lokasi tidak dapat ditentukan. Pastikan Wi-Fi atau GPS HP aktif.';
                } else if (error.code === error.TIMEOUT) {
                    reason = 'timeout';
                    msg = 'Waktu pengambilan koordinat lokasi habis (Timeout). Pastikan fitur Lokasi HP Anda aktif.';
                }
                callback(null, null, reason, msg);
            },
            { enableHighAccuracy: highAcc, timeout: highAcc ? 4000 : 12000, maximumAge: highAcc ? 0 : 30000 }
        );
    };

    tryGetPos(true);
}
window.getLocation = getLocation;

// ── Location Error Handler UI ──────────────────────────────────────
function showLocationErrorAlert(titlePrefix, errCode, errMsg, btnEl, originalBtnHtml) {
    let title = 'Akses Lokasi Ditolak';
    let solutionHtml = '';

    if (errCode === 'fake_gps') {
        title = 'Manipulasi GPS Terdeteksi!';
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
        if (typeof window.showLocationPermissionGuide === 'function') {
            window.showLocationPermissionGuide(errMsg);
            if (btnEl) {
                btnEl.innerHTML = originalBtnHtml;
                btnEl.disabled = false;
            }
            return;
        }
        title = 'Izin Lokasi Diblokir';
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
        let isHttpLocal = (window.isSecureContext === false && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1');
        title = isHttpLocal ? 'Browser Memblokir Akses HTTP' : 'Sinyal GPS / Izin Perangkat Lemah';
        solutionHtml = `
            <div class="text-left bg-amber-50 p-4 rounded-xl border border-amber-200 mt-3 text-xs space-y-2 text-amber-950 leading-relaxed font-medium">
                <p class="font-bold text-amber-800 text-sm">Mengapa Terjadi (Meskipun GPS & Wi-Fi HP Aktif)?</p>
                <p class="text-gray-700">
                    ${isHttpLocal 
                        ? 'HP Anda sudah aktif GPS & Wi-Fi nya, namun <strong>browser Google Chrome memblokir penuh fitur GPS</strong> karena web diakses melalui koneksi <strong>HTTP IP (' + location.origin + ')</strong> tanpa SSL/HTTPS.' 
                        : (errMsg || 'Perangkat gagal mengambil koordinat lokasi aktif.')}
                </p>
                <p class="font-bold text-amber-800 text-sm mt-3">Solusi Penyelesaian:</p>
                <ul class="list-decimal list-inside space-y-1 text-gray-700">
                    ${isHttpLocal ? `
                        <li>Buka tab baru Chrome di HP: <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code></li>
                        <li>Masukkan alamat <code>${location.origin}</code> di kolom yang tersedia.</li>
                        <li>Ubah status menjadi <strong>Enabled</strong> lalu tekan <strong>Relaunch</strong>.</li>
                        <li>Atau gunakan URL HTTPS resmi (seperti ngrok / localtunnel).</li>
                    ` : `
                        <li>Buka <strong>Pengaturan HP -> Aplikasi -> Chrome -> Izin -> Lokasi</strong> dan pilih <strong>Izinkan</strong>.</li>
                        <li>Pastikan opsi <strong>Akurasi Lokasi Tepat (Precise Location)</strong> di HP dalam posisi <strong>AKTIF</strong>.</li>
                        <li>Muat ulang (*refresh*) halaman dan lakukan presensi kembali.</li>
                    `}
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

// ── LAYER 2 SECURITY STATE & MODALS ─────────────────────────────────
window.attCurrentAction = 'clockin';
window.attCurrentLat = null;
window.attCurrentLng = null;
window.attCameraStream = null;
window.attCurrentFacingMode = 'user';
window.attCapturedPhotoBase64 = null;

window.switchCameraLens = function(facingMode) {
    window.attCurrentFacingMode = facingMode;
    const btnFront = document.getElementById('btnCamFront');
    const btnRear = document.getElementById('btnCamRear');
    if (facingMode === 'user') {
        btnFront.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-primary text-white shadow-sm';
        btnRear.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-on-surface-variant hover:text-on-surface';
    } else {
        btnRear.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-primary text-white shadow-sm';
        btnFront.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-on-surface-variant hover:text-on-surface';
    }
    window.startCameraStream();
};

window.startCameraStream = function() {
    if (window.attCameraStream) {
        window.attCameraStream.getTracks().forEach(track => track.stop());
    }
    const constraints = {
        video: { facingMode: window.attCurrentFacingMode, width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false
    };
    navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
            window.attCameraStream = stream;
            const video = document.getElementById('attCameraVideo');
            video.srcObject = stream;
            video.classList.remove('hidden');
            document.getElementById('attPhotoPreview').classList.add('hidden');
            document.getElementById('attCameraGrid').classList.remove('hidden');
            document.getElementById('btnCapturePhoto').classList.remove('hidden');
            document.getElementById('btnRetakePhoto').classList.add('hidden');
            document.getElementById('btnSubmitPhoto').classList.add('hidden');
        })
        .catch(err => {
            Swal.fire('Error Kamera', 'Gagal mengakses kamera perangkat: ' + err.message, 'error');
        });
};

window.openCameraModal = function(action, lat, lng) {
    window.attCurrentAction = action;
    window.attCurrentLat = lat;
    window.attCurrentLng = lng;
    window.attCapturedPhotoBase64 = null;
    const modal = document.getElementById('attCameraModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    window.switchCameraLens('user');
};

window.closeCameraModal = function() {
    if (window.attCameraStream) {
        window.attCameraStream.getTracks().forEach(track => track.stop());
        window.attCameraStream = null;
    }
    const modal = document.getElementById('attCameraModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    resetClockInBtn();
};

window.capturePhotoFrame = function() {
    const video = document.getElementById('attCameraVideo');
    const canvas = document.getElementById('attCameraCanvas');
    const preview = document.getElementById('attPhotoPreview');

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    let quality = 0.85;
    let dataUrl = canvas.toDataURL('image/jpeg', quality);
    while (dataUrl.length * 0.75 > 1048576 && quality > 0.3) {
        quality -= 0.1;
        dataUrl = canvas.toDataURL('image/jpeg', quality);
    }
    window.attCapturedPhotoBase64 = dataUrl;

    preview.src = dataUrl;
    preview.classList.remove('hidden');
    video.classList.add('hidden');
    document.getElementById('attCameraGrid').classList.add('hidden');

    document.getElementById('btnCapturePhoto').classList.add('hidden');
    document.getElementById('btnRetakePhoto').classList.remove('hidden');
    document.getElementById('btnSubmitPhoto').classList.remove('hidden');
};

window.retakePhotoFrame = function() {
    window.attCapturedPhotoBase64 = null;
    document.getElementById('attPhotoPreview').classList.add('hidden');
    document.getElementById('attCameraVideo').classList.remove('hidden');
    document.getElementById('attCameraGrid').classList.remove('hidden');
    document.getElementById('btnCapturePhoto').classList.remove('hidden');
    document.getElementById('btnRetakePhoto').classList.add('hidden');
    document.getElementById('btnSubmitPhoto').classList.add('hidden');
};

window.submitPhotoAttendance = function() {
    if (!window.attCapturedPhotoBase64) {
        Swal.fire('Error', 'Harap jepret foto terlebih dahulu.', 'warning');
        return;
    }
    window.executeFinalAttendance(window.attCurrentAction, window.attCurrentLat, window.attCurrentLng, {
        photo_base64: window.attCapturedPhotoBase64
    });
    window.closeCameraModal();
};

// ── QR CODE MODAL ──
window.openQrModal = function(action, lat, lng) {
    window.attCurrentAction = action;
    window.attCurrentLat = lat;
    window.attCurrentLng = lng;
    window.attQrScanning = true;

    const modal = document.getElementById('attQrModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const video = document.getElementById('attQrVideo');

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: { ideal: 'environment' },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } 
        })
        .then(stream => {
            window.attCameraStream = stream;
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            video.play();

            // Start Realtime Frame Scanning Loop
            startQrDecodingLoop(video);
        }).catch(e => {
            console.error('Camera access error:', e);
        });
    }

    setTimeout(() => {
        const manualInput = document.getElementById('attManualQrInput');
        if (manualInput) {
            manualInput.value = '';
            manualInput.focus();
        }
    }, 150);
};

function startQrDecodingLoop(video) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    let hasBarcodeDetector = ('BarcodeDetector' in window);
    let detector = null;
    if (hasBarcodeDetector) {
        try {
            detector = new BarcodeDetector({ formats: ['qr_code'] });
        } catch(e) {
            hasBarcodeDetector = false;
        }
    }

    function scanFrame() {
        if (!window.attQrScanning || !window.attCameraStream) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            if (hasBarcodeDetector && detector) {
                detector.detect(video).then(barcodes => {
                    if (barcodes.length > 0 && barcodes[0].rawValue) {
                        const code = barcodes[0].rawValue.trim();
                        if (code) {
                            window.onQrCodeScanned(code);
                            return;
                        }
                    }
                    if (window.attQrScanning) requestAnimationFrame(scanFrame);
                }).catch(() => {
                    scanWithJsQr();
                });
            } else {
                scanWithJsQr();
            }
        } else {
            if (window.attQrScanning) requestAnimationFrame(scanFrame);
        }
    }

    function scanWithJsQr() {
        if (typeof jsQR === 'function') {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert'
            });

            if (code && code.data && code.data.trim()) {
                window.onQrCodeScanned(code.data.trim());
                return;
            }
        }
        if (window.attQrScanning) requestAnimationFrame(scanFrame);
    }

    requestAnimationFrame(scanFrame);
}

window.onQrCodeScanned = function(qrVal) {
    if (!window.attQrScanning) return;
    window.attQrScanning = false;

    if (typeof window.playSuccessSound === 'function') {
        window.playSuccessSound();
    }

    window.closeQrModal();
    window.executeFinalAttendance(window.attCurrentAction, window.attCurrentLat, window.attCurrentLng, { qr_code: qrVal });
};

window.closeQrModal = function() {
    window.attQrScanning = false;
    if (window.attCameraStream) {
        window.attCameraStream.getTracks().forEach(t => t.stop());
        window.attCameraStream = null;
    }
    const modal = document.getElementById('attQrModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    resetClockInBtn();
};

window.submitManualQrCode = function() {
    const val = (document.getElementById('attManualQrInput').value || '').trim();
    if (!val) {
        Swal.fire('Error', 'Masukkan kode QR terlebih dahulu.', 'warning');
        return;
    }
    window.closeQrModal();
    window.executeFinalAttendance(window.attCurrentAction, window.attCurrentLat, window.attCurrentLng, { qr_code: val });
};

// ── NFC CARD MODAL ──
window.openNfcModal = function(action, lat, lng) {
    window.attCurrentAction = action;
    window.attCurrentLat = lat;
    window.attCurrentLng = lng;
    const modal = document.getElementById('attNfcModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const noticeEl = document.getElementById('attNfcHardwareNotice');
    const pingEl = document.getElementById('attNfcPingBg');
    const iconEl = document.getElementById('attNfcIcon');
    const iconBox = document.getElementById('attNfcIconBox');
    const titleEl = document.getElementById('attNfcTitle');
    const descEl = document.getElementById('attNfcDesc');
    const badgeEl = document.getElementById('attNfcStatusBadge');

    const isSupported = ('NDEFReader' in window);

    if (isSupported) {
        if (noticeEl) noticeEl.classList.add('hidden');
        if (pingEl) pingEl.classList.remove('hidden');
        if (iconEl) {
            iconEl.textContent = 'contactless';
            iconEl.className = 'material-symbols-outlined text-5xl text-primary animate-pulse';
        }
        if (iconBox) iconBox.className = 'w-24 h-24 rounded-full bg-primary/20 flex items-center justify-center relative z-10 shadow-[0_0_30px_rgba(59,130,246,0.4)]';
        if (titleEl) titleEl.textContent = 'Tap Kartu Akses / NFC';
        if (descEl) descEl.textContent = 'Tempelkan Kartu Akses / HP berfitur NFC Anda pada bagian belakang perangkat ini.';
        if (badgeEl) {
            badgeEl.className = 'text-[10px] text-emerald-600 font-bold mt-4 text-center bg-emerald-50 px-3 py-1.5 rounded-full flex items-center justify-center gap-1';
            badgeEl.innerHTML = '<span class="material-symbols-outlined text-xs">bolt</span> Sensor NFC aktif & siap membaca kartu!';
        }

        try {
            const ndef = new NDEFReader();
            ndef.scan().then(() => {
                ndef.onreading = event => {
                    const serialNum = event.serialNumber || 'NFC-' + Date.now();
                    window.closeNfcModal();
                    window.executeFinalAttendance(window.attCurrentAction, window.attCurrentLat, window.attCurrentLng, { nfc_card_id: serialNum });
                };
            }).catch(e => {});
        } catch(e) {}
    } else {
        // Hardware NFC not supported on this device/browser
        if (noticeEl) noticeEl.classList.remove('hidden');
        if (pingEl) pingEl.classList.add('hidden');
        if (iconEl) {
            iconEl.textContent = 'phonelink_erase';
            iconEl.className = 'material-symbols-outlined text-5xl text-amber-600';
        }
        if (iconBox) iconBox.className = 'w-24 h-24 rounded-full bg-amber-500/10 flex items-center justify-center relative z-10 border border-amber-500/20';
        if (titleEl) titleEl.textContent = 'Tap Kartu Akses / NFC';
        if (descEl) descEl.textContent = 'Perangkat / browser ini tidak memiliki sensor Web NFC internal.';
        if (badgeEl) {
            badgeEl.className = 'text-[10px] text-amber-700 font-bold mt-4 text-center bg-amber-50 px-3 py-1.5 rounded-full flex items-center justify-center gap-1 border border-amber-200';
            badgeEl.innerHTML = '<span class="material-symbols-outlined text-xs">info</span> Gunakan USB RFID Reader / ketik ID Kartu Akses di bawah.';
        }
    }

    setTimeout(() => {
        const manualInput = document.getElementById('attManualNfcInput');
        if (manualInput) {
            manualInput.value = '';
            manualInput.focus();
        }
    }, 150);
};

window.closeNfcModal = function() {
    const modal = document.getElementById('attNfcModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    resetClockInBtn();
};

window.submitManualNfcCard = function() {
    const val = (document.getElementById('attManualNfcInput').value || '').trim();
    if (!val) {
        Swal.fire('Error', 'Tap kartu atau masukkan ID kartu terlebih dahulu.', 'warning');
        return;
    }
    window.closeNfcModal();
    window.executeFinalAttendance(window.attCurrentAction, window.attCurrentLat, window.attCurrentLng, { nfc_card_id: val });
};

function resetClockInBtn() {
    const btnIn = document.getElementById('btnClockIn');
    if (btnIn) {
        btnIn.innerHTML = '<span class="material-symbols-outlined font-bold">login</span> Clock-In';
        btnIn.disabled = false;
    }
    const btnOut = document.getElementById('btnClockOut');
    if (btnOut) {
        btnOut.innerHTML = '<span class="material-symbols-outlined font-bold">logout</span> Clock-Out';
        btnOut.disabled = false;
    }
}

// ── FINAL ATTENDANCE SUBMITTER ──
window.executeFinalAttendance = function(action, lat, lng, extraParams = {}) {
    const endpoint = action === 'clockout' ? '/employee/attendance/clockout' : '/employee/attendance/clockin';
    const fd = new FormData();
    fd.append('lat', lat);
    fd.append('lng', lng);
    fd.append('csrf_token', ATT_CFG.csrfToken);
    for (const [k, v] of Object.entries(extraParams)) {
        fd.append(k, v);
    }

    fetch(endpoint, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.text())
    .then(text => {
        resetClockInBtn();
        let data;
        let cleanText = text.trim();
        const jsonMatch = cleanText.match(/\{[\s\S]*\}/);
        if (jsonMatch) {
            cleanText = jsonMatch[0];
        }
        try {
            data = JSON.parse(cleanText);
        } catch (e) {
            console.error('Server response non-JSON:', text);
            Swal.fire({ title: 'Gagal Presensi', text: 'Respon server tidak valid. Teks: ' + text.substring(0, 100), icon: 'error', confirmButtonColor: '#ba1a1a' });
            return;
        }
        if (data.success) {
            if (typeof window.playSuccessSound === 'function') window.playSuccessSound();
            const title = action === 'clockout' ? 'Clock-Out Berhasil!' : 'Clock-In Berhasil!';
            Swal.fire({
                title: title,
                html: `<p class="text-sm text-gray-600 mb-3">${data.message}</p>`,
                icon: 'success',
                confirmButtonColor: '#000666',
                confirmButtonText: 'OK'
            }).then(() => {
                if (window.loadPage) window.loadPage('/employee/attendance');
                else window.location.reload();
            });
        } else {
            Swal.fire({ title: 'Gagal Presensi', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
        }
    })
    .catch(err => {
        resetClockInBtn();
        Swal.fire({ title: 'Error Koneksi', text: 'Gagal menghubungi server: ' + (err.message || err), icon: 'error', confirmButtonColor: '#ba1a1a' });
    });
};

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

        const metode = window.userMetodePresensi || 'default';
        if (metode === 'foto') {
            window.openCameraModal('clockin', lat, lng);
        } else if (metode === 'scan_qr') {
            window.openQrModal('clockin', lat, lng);
        } else if (metode === 'nfc') {
            window.openNfcModal('clockin', lat, lng);
        } else {
            window.executeFinalAttendance('clockin', lat, lng);
        }
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
        text: 'Apakah Anda yakin ingin melakukan Clock-Out sekarang?',
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

            const metode = window.userMetodePresensi || 'default';
            if (metode === 'foto') {
                window.openCameraModal('clockout', lat, lng);
            } else if (metode === 'scan_qr') {
                window.openQrModal('clockout', lat, lng);
            } else if (metode === 'nfc') {
                window.openNfcModal('clockout', lat, lng);
            } else {
                window.executeFinalAttendance('clockout', lat, lng);
            }
        });
    });
}
window.doClockOut = doClockOut;

function changeFilter() {
    const month = document.getElementById('filter_month').value;
    const year = document.getElementById('filter_year').value;
    const url = window.location.pathname + '?month=' + month + '&year=' + year;
    if (window.loadPage) {
        window.loadPage(url);
    } else {
        window.location.href = url;
    }
}
window.changeFilter = changeFilter;

function playSuccessSound() {
    const successAudio = new Audio('/ringtones/freesound_community-header-39344.mp3');
    successAudio.play().catch(e => console.error('Play success audio failed:', e));
}
window.playSuccessSound = playSuccessSound;


</script>