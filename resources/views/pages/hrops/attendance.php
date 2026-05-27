<?php
// HR Ops — Presensi Karyawan (Live DB)
$db = \App\Config\Database::getInstance()->getConnection();
$today = (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) ? $_GET['date'] : date('Y-m-d');

// ── Stats for today ──
$stmtHadir = $db->prepare("
    SELECT
        COUNT(*) AS hadir,
        SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
        SUM(CASE WHEN status IN ('sakit/izin') THEN 1 ELSE 0 END) AS sakit,
        SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) AS alpa
    FROM employee_attendance
    WHERE attendance_date = :date
");
$stmtHadir->execute(['date' => $today]);
$todayStats = $stmtHadir->fetch() ?: ['hadir' => 0, 'terlambat' => 0, 'sakit' => 0, 'alpa' => 0];

// Total employees
$stmtTotal = $db->query("SELECT COUNT(*) FROM users WHERE role = 'employee'");
$totalEmp  = (int)$stmtTotal->fetchColumn();

// Number actually present (tepat waktu + terlambat)
$hadirCount    = (int)$todayStats['hadir'];
$terlambatCount = (int)$todayStats['terlambat'];
$sakitCount    = (int)$todayStats['sakit'];
$alpaCount     = (int)$todayStats['alpa'];
$hadirHariIni  = $hadirCount; // includes terlambat

// ── Attendance rows for today ──
$stmtRows = $db->prepare("
    SELECT
        a.id, a.user_id, a.attendance_date, a.clock_in, a.clock_out, a.status,
        a.clock_in_latitude, a.clock_in_longitude,
        a.location_method, a.ip_address, a.correction_reason, a.work_mode,
        u.first_name, u.last_name, u.employee_id, u.profile_picture, u.email,
        COALESCE(u.job_title, '') AS job_title
    FROM employee_attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.attendance_date = :date
    ORDER BY a.clock_in ASC
");
$stmtRows->execute(['date' => $today]);
$attendanceRows = $stmtRows->fetchAll();

// ── Employees with no record today (auto-alpa) removed as per user request ──
$mergedRows = $attendanceRows;

// ── Helpers ──
function hrStatusBadge($s) {
    return match($s) {
        'tepat waktu' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'awal'        => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'terlambat'   => 'bg-amber-50 text-amber-700 border-amber-200',
        'sakit/izin'  => 'bg-blue-50 text-blue-700 border-blue-200',
        default       => 'bg-red-50 text-red-700 border-red-200',
    };
}
function hrStatusDot($s) {
    return match($s) {
        'tepat waktu' => 'bg-emerald-500',
        'awal'        => 'bg-indigo-500',
        'terlambat'   => 'bg-amber-500',
        'sakit/izin'  => 'bg-blue-500',
        default       => 'bg-red-500',
    };
}
function hrStatusLabel($s) {
    return match($s) {
        'tepat waktu' => 'Tepat Waktu',
        'awal'        => 'Masuk Awal',
        'terlambat'   => 'Terlambat',
        'sakit/izin'  => 'Sakit / Izin',
        default       => 'Alpa',
    };
}
function initials($fn, $ln) {
    $fn = (string)($fn ?? '');
    $ln = (string)($ln ?? '');
    $fChar = $fn !== '' ? substr($fn, 0, 1) : '';
    $lChar = $ln !== '' ? substr($ln, 0, 1) : '';
    return strtoupper($fChar . $lChar);
}
?>

<div class="space-y-6">
    <!-- ── Header ── -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Presensi Karyawan</h1>
            <p class="text-on-surface-variant font-medium text-sm">Monitor kehadiran harian, keterlambatan, dan aktivitas presensi secara real-time.</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Date picker to load different dates -->
            <div class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-3.5 py-2 shadow-sm">
                <span class="material-symbols-outlined text-primary text-sm">calendar_today</span>
                <input type="date" id="hrDatePicker" value="<?= $today ?>"
                    class="text-xs font-semibold text-on-surface bg-transparent focus:outline-none cursor-pointer"
                    onchange="loadAttendanceDate(this.value)" />
            </div>
            <button onclick="Swal.fire({title:'Ekspor Excel',text:'Rekap kehadiran <?= date('F Y') ?> berhasil diekspor.', icon:'success', confirmButtonColor:'#000666'})"
                class="bg-surface-container-high hover:bg-surface-container-high/80 text-primary font-bold text-xs py-2.5 px-4 rounded-lg flex items-center gap-2 transition-all border border-outline-variant/20">
                <span class="material-symbols-outlined text-sm">download</span> Ekspor
            </button>
            <button onclick="Swal.fire({title:'Sinkronisasi Berhasil',text:'Berhasil sinkronisasi dengan mesin biometrik ZKTeco.',icon:'success',confirmButtonColor:'#000666'})"
                class="bg-primary hover:bg-primary/90 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center gap-2 transition-all shadow-md shadow-primary/10">
                <span class="material-symbols-outlined text-sm">sync</span> Sinkronisasi
            </button>
        </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Karyawan Aktif</span>
                <span class="material-symbols-outlined text-primary bg-primary/5 p-2 rounded-lg">groups</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-on-surface" id="statTotal"><?= $totalEmp ?> <span class="text-xs font-semibold text-on-surface-variant">Staf</span></h3>
                <p class="text-[11px] text-emerald-600 font-semibold mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-xs">verified</span> Data terintegrasi</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Hadir Hari Ini</span>
                <span class="material-symbols-outlined text-emerald-600 bg-emerald-50 p-2 rounded-lg">check_circle</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-emerald-700" id="statHadir"><?= $hadirHariIni ?> <span class="text-xs font-semibold text-emerald-600">/ <?= $totalEmp ?></span></h3>
                <p class="text-[11px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">trending_up</span>
                    <?= $totalEmp > 0 ? round($hadirHariIni/$totalEmp*100,1) : 0 ?>% Kehadiran
                </p>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Terlambat</span>
                <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-2 rounded-lg">schedule</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-amber-700" id="statTerlambat"><?= $terlambatCount ?> <span class="text-xs font-semibold text-amber-600">Staf</span></h3>
                <p class="text-[11px] text-amber-600 font-semibold mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-xs">error</span> Perlu monitoring</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Absen / Cuti / Izin</span>
                <span class="material-symbols-outlined text-red-600 bg-red-50 p-2 rounded-lg">logout</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-red-700" id="statAbsent"><?= ($alpaCount + $sakitCount) ?> <span class="text-xs font-semibold text-red-600">Staf</span></h3>
                <p class="text-[11px] text-on-surface-variant font-semibold mt-1"><?= $sakitCount ?> Sakit, <?= $alpaCount ?> Alpa</p>
            </div>
        </div>
    </div>

    <!-- ── Filter & Table ── -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <!-- Filter bar -->
        <div class="p-5 border-b border-outline-variant/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">filter_alt</span>
                <h2 class="text-base font-extrabold text-on-surface">Filter &amp; Pencarian</h2>
            </div>
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-on-surface-variant/50 text-sm">search</span>
                    </span>
                    <input type="text" id="attendanceSearch" onkeyup="filterAttendanceTable()" placeholder="Cari nama karyawan..."
                        class="pl-9 pr-4 py-2 w-full text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-medium text-on-surface" />
                </div>
                <select id="attendanceStatusFilter" onchange="filterAttendanceTable()"
                    class="py-2 px-3 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary font-medium text-on-surface-variant">
                    <option value="">Semua Status</option>
                    <option value="tepat waktu">Tepat Waktu</option>
                    <option value="awal">Masuk Awal</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="sakit/izin">Sakit / Izin</option>
                    <option value="alpa">Alpa</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto" id="tableWrap">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/10">
                        <th class="py-4 px-6 text-[10px] font-bold uppercase tracking-wider">Karyawan</th>
                        <th class="py-4 px-6 text-[10px] font-bold uppercase tracking-wider">Masuk (Clock-In)</th>
                        <th class="py-4 px-6 text-[10px] font-bold uppercase tracking-wider">Keluar (Clock-Out)</th>
                        <th class="py-4 px-6 text-[10px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-[10px] font-bold uppercase tracking-wider">Lokasi &amp; Metode</th>
                        <th class="py-4 px-6 text-right text-[10px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody" class="divide-y divide-outline-variant/8">
                    <?php if (empty($mergedRows)): ?>
                    <tr><td colspan="6" class="py-16 text-center">
                        <span class="material-symbols-outlined text-4xl text-outline-variant block mb-3">co_present</span>
                        <p class="text-on-surface-variant font-semibold text-sm">Belum ada data presensi untuk hari ini.</p>
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($mergedRows as $row):
                        $cin  = $row['clock_in']  ? date('H:i:s', strtotime($row['clock_in']))  : '--:--:--';
                        $cout = $row['clock_out'] ? date('H:i:s', strtotime($row['clock_out'])) : '--:--:--';
                                                $empFirstName = (string)($row['first_name'] ?? '');
                        $empLastName = (string)($row['last_name'] ?? '');
                        $empName = htmlspecialchars(trim($empFirstName . ' ' . $empLastName));
                        $empId   = htmlspecialchars($row['employee_id'] ?? '');
                        $ini     = initials($empFirstName, $empLastName);
                        $avatarClr = match($row['status']) {
                            'tepat waktu' => 'bg-emerald-100 text-emerald-700',
                            'awal'        => 'bg-indigo-100 text-indigo-700',
                            'terlambat'   => 'bg-amber-100 text-amber-700',
                            'sakit/izin'  => 'bg-blue-100 text-blue-700',
                            default       => 'bg-red-100 text-red-700',
                        };
                        $cinClr = match($row['status']) {
                            'tepat waktu' => 'text-emerald-600',
                            'awal'        => 'text-indigo-600',
                            'terlambat'   => 'text-amber-600',
                            default       => 'text-on-surface-variant/40',
                        };
                        $attRowId = $row['id'] ?? '';
                        $userId   = $row['user_id'] ?? '';
                    ?>
                    <tr class="hover:bg-surface-container-low/30 transition-colors"
                        data-name="<?= strtolower($empName) ?>"
                        data-status="<?= htmlspecialchars($row['status']) ?>">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <?php 
                                    $profPic = $row['profile_picture'];
                                    if (empty($profPic) && !empty($row['email'])) {
                                        $hash = md5(strtolower(trim($row['email'])));
                                        $profPic = "https://unavatar.io/" . urlencode($row['email']) . "?fallback=" . urlencode("https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150");
                                    }
                                ?>
                                <?php if (!empty($profPic)): ?>
                                    <img src="<?= htmlspecialchars($profPic) ?>" alt="<?= $empName ?>" class="w-10 h-10 rounded-full object-cover shadow-sm flex-shrink-0 bg-white border border-outline-variant/10" />
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full <?= $avatarClr ?> font-bold text-sm flex items-center justify-center flex-shrink-0"><?= $ini ?></div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-extrabold text-sm text-on-surface"><?= $empName ?></div>
                                    <div class="text-[11px] text-on-surface-variant font-medium"><?= htmlspecialchars($row['job_title'] ?: 'Karyawan') ?> · <span class="font-mono"><?= $empId ?></span></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 font-mono text-sm font-semibold <?= $cinClr ?>">
                            <?= $cin === '--:--:--' && $row['status'] === 'alpa' ? '<span class="text-red-500 font-bold">MANGKIR</span>' : $cin ?>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex flex-col gap-1">
                                <span class="font-mono text-sm font-semibold text-on-surface-variant"><?= $cout ?></span>
                                <?php if (!empty($row['clock_out_status'])): ?>
                                    <?php
                                        $coStat = $row['clock_out_status'];
                                        $coBadgeColor = match($coStat) {
                                            'pulang lambat' => 'text-amber-600 bg-amber-50 border-amber-100',
                                            'pulang cepat' => 'text-red-600 bg-red-50 border-red-100',
                                            'tidak presensi pulang' => 'text-rose-600 bg-rose-50 border-rose-100',
                                            default => 'text-emerald-600 bg-emerald-50 border-emerald-100',
                                        };
                                        $coLabel = match($coStat) {
                                            'pulang lambat' => 'Pulang Lambat',
                                            'pulang cepat' => 'Pulang Cepat',
                                            'tidak presensi pulang' => 'Tidak Presensi Pulang',
                                            default => 'Wajar',
                                        };
                                    ?>
                                    <span class="inline-flex items-center justify-center w-max px-2 py-0.5 rounded-full text-[9px] font-bold border <?= $coBadgeColor ?>">
                                        <?= $coLabel ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border <?= hrStatusBadge($row['status']) ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= hrStatusDot($row['status']) ?>"></span>
                                <?= hrStatusLabel($row['status']) ?>
                                <?php if ($row['status'] === 'terlambat' && $row['clock_in']): ?>
                                <?php
                                    $lateM = max(0, (int)(((strtotime($row['clock_in']) - strtotime(date('Y-m-d') . ' 08:00:00'))) / 60));
                                ?>
                                    (<?= $lateM ?>m)
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                <?php if (!empty($row['work_mode'])): ?>
                                    <?php
                                        $wm = $row['work_mode'];
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
                                        $modeText = match($wm) {
                                            'WFA' => 'WFA/WFC',
                                            'WFH' => 'WFH',
                                            default => 'WFO',
                                        };
                                    ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold border <?= $modeColor ?>" title="Mode Masuk">
                                        <span class="material-symbols-outlined text-[11px]"><?= $modeIcon ?></span>
                                        <?= $modeText ?> (Masuk)
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($row['work_mode_out'])): ?>
                                    <?php
                                        $wmo = $row['work_mode_out'];
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
                                        $modeTextOut = match($wmo) {
                                            'WFA' => 'WFA/WFC',
                                            'WFH' => 'WFH',
                                            default => 'WFO',
                                        };
                                    ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold border <?= $modeColorOut ?>" title="Mode Pulang">
                                        <span class="material-symbols-outlined text-[11px]"><?= $modeIconOut ?></span>
                                        <?= $modeTextOut ?> (Pulang)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($row['location_method']): ?>
                            <div class="text-xs font-semibold text-on-surface-variant flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm <?= $row['location_method'] === 'WIFI' ? 'text-primary' : 'text-amber-600' ?>">
                                    <?= $row['location_method'] === 'WIFI' ? 'wifi' : 'location_on' ?>
                                </span>
                                <?= htmlspecialchars($row['location_method']) ?>
                            </div>
                            <?php if ($row['ip_address']): ?>
                            <div class="text-[10px] text-on-surface-variant/60 font-mono mt-0.5">IP: <?= htmlspecialchars($row['ip_address']) ?></div>
                            <?php endif; ?>
                            <?php if ($row['clock_in_latitude']): ?>
                            <div class="text-[10px] text-on-surface-variant/50 font-mono mt-0.5"><?= round($row['clock_in_latitude'],4) ?>, <?= round($row['clock_in_longitude'],4) ?></div>
                            <?php endif; ?>
                            <?php elseif ($row['correction_reason']): ?>
                            <div class="text-xs text-purple-600 font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">edit_note</span> Koreksi HR
                            </div>
                            <?php else: ?>
                            <span class="text-on-surface-variant/30 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <?php if (in_array($row['status'], ['terlambat', 'alpa'])): ?>
                                <button onclick="sendWarning('<?= addslashes($empName) ?>')"
                                    class="text-[11px] font-bold text-red-600 hover:text-red-700 hover:underline flex items-center gap-0.5 whitespace-nowrap">
                                    <span class="material-symbols-outlined text-sm">notifications_active</span> Tegur
                                </button>
                                <?php endif; ?>
                                <button onclick="correctTime('<?= addslashes($attRowId) ?>', '<?= addslashes($userId) ?>', '<?= $today ?>', '<?= addslashes($empName) ?>', '<?= ($cin !== '--:--:--') ? substr($cin,0,5) : '' ?>', '<?= ($cout !== '--:--:--') ? substr($cout,0,5) : '' ?>')"
                                    class="text-[11px] font-bold text-primary hover:underline flex items-center gap-0.5 whitespace-nowrap">
                                    <span class="material-symbols-outlined text-sm">edit</span> Koreksi
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="p-5 border-t border-outline-variant/10 flex justify-between items-center text-xs font-semibold text-on-surface-variant">
            <span>Menampilkan <span id="displayedCount"><?= count($mergedRows) ?></span> dari <?= count($mergedRows) ?> karyawan</span>
            <span class="text-[10px] text-on-surface-variant/50">Data diambil langsung dari database · <?= date('d M Y, H:i') ?></span>
        </div>
    </div>
</div>

<script>
function filterAttendanceTable() {
    const query  = document.getElementById('attendanceSearch').value.toLowerCase().trim();
    const status = document.getElementById('attendanceStatusFilter').value.toLowerCase();
    const rows   = document.querySelectorAll('#attendanceTableBody tr');
    let visible  = 0;
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const stat = row.getAttribute('data-status') || '';
        const matchQ = name.includes(query);
        const matchS = !status || stat === status;
        row.style.display = (matchQ && matchS) ? '' : 'none';
        if (matchQ && matchS) visible++;
    });
    document.getElementById('displayedCount').textContent = visible;
}

function sendWarning(name) {
    Swal.fire({
        title: 'Kirim Teguran?',
        text: `Kirim peringatan keterlambatan/keabsenan ke WhatsApp & Email ${name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ba1a1a',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Kirim Teguran',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (r.isConfirmed) {
            Swal.fire({ title: 'Teguran Terkirim!', text: `Pesan teguran berhasil dikirim ke ${name}.`, icon: 'success', confirmButtonColor: '#000666' });
        }
    });
}

function correctTime(attId, userId, date, name, cin, cout) {
    Swal.fire({
        title: `Koreksi Presensi`,
        html: `
            <div class="text-left space-y-4 text-sm mt-2">
                <p class="text-xs text-gray-500 font-semibold bg-gray-50 rounded-lg px-3 py-2">Karyawan: <strong class="text-gray-800">${name}</strong> · Tanggal: <strong>${date}</strong></p>
                <div>
                    <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Waktu Masuk (Clock-In)</label>
                    <input type="time" id="swalCheckIn" value="${cin}" class="w-full p-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm font-mono" />
                </div>
                <div>
                    <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Waktu Keluar (Clock-Out)</label>
                    <input type="time" id="swalCheckOut" value="${cout}" class="w-full p-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm font-mono" />
                </div>
                <div>
                    <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Alasan Koreksi <span class="text-red-500">*</span></label>
                    <textarea id="swalReason" placeholder="Masukkan alasan koreksi untuk keperluan audit log..." class="w-full p-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 text-xs h-20 resize-none"></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#000666',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Simpan Koreksi',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const ci = document.getElementById('swalCheckIn').value;
            const co = document.getElementById('swalCheckOut').value;
            const reason = document.getElementById('swalReason').value.trim();
            if (!reason) { Swal.showValidationMessage('Alasan koreksi wajib diisi untuk audit.'); return false; }
            return { ci, co, reason };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('attendance_id', attId);
        fd.append('user_id', userId);
        fd.append('date', date);
        fd.append('clock_in', result.value.ci);
        fd.append('clock_out', result.value.co);
        fd.append('reason', result.value.reason);

        fetch('/hrops/attendance/correct', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Koreksi Disimpan!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    if (window.loadPage) {
                        window.loadPage(window.location.pathname + window.location.search);
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
            }
        });
    });
}

function loadAttendanceDate(date) {
    if (window.loadPage) {
        window.loadPage('/hrops/attendance?date=' + date);
    } else {
        window.location.href = `/hrops/attendance?date=${date}`;
    }
}

// Expose functions globally to prevent ReferenceErrors due to IIFE encapsulation in SPA router
window.filterAttendanceTable = filterAttendanceTable;
window.sendWarning = sendWarning;
window.correctTime = correctTime;
window.loadAttendanceDate = loadAttendanceDate;
</script>
