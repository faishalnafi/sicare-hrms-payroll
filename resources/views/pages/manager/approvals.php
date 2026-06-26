<?php
// Hiring Manager Approvals Dashboard with Strict Hierarchical Isolation
$db = \App\Config\Database::getInstance()->getConnection();

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? '';

// Helper to get descendant department IDs recursively
function getDescendants($db, $deptId) {
    $ids = [$deptId];
    $stmt = $db->prepare("SELECT id FROM departments WHERE parent_id = ?");
    $stmt->execute([$deptId]);
    $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($children as $childId) {
        $ids = array_merge($ids, getDescendants($db, $childId));
    }
    return $ids;
}

// 1. Get manager fungsional's department
$stmt = $db->prepare("SELECT department_id FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$managerDeptId = $stmt->fetchColumn();

$allowedDepts = [];
if (!empty($managerDeptId)) {
    $allowedDepts = getDescendants($db, $managerDeptId);
}

// Check department status
$hasDepartment = !empty($allowedDepts);
$inClause = $hasDepartment ? implode(',', array_map(fn($id) => $db->quote($id), $allowedDepts)) : "''";

// A. Fetch Cuti (Leaves)
$leaves = [];
if ($hasDepartment) {
    $stmt = $db->query("
        SELECT r.*, u.first_name, u.last_name, u.employee_id, u.email, u.profile_picture, u.job_title, u.role
        FROM employee_leave_requests r
        JOIN users u ON r.user_id = u.id
        WHERE u.department_id IN ($inClause)
        ORDER BY r.created_at DESC
    ");
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// B. Fetch Reimbursements
$reimbursements = [];
if ($hasDepartment) {
    $stmt = $db->query("
        SELECT c.*, u.first_name, u.last_name, u.employee_id, u.email, u.profile_picture, u.job_title, u.role
        FROM employee_reimbursement_claims c
        JOIN users u ON c.user_id = u.id
        WHERE u.department_id IN ($inClause)
        ORDER BY c.created_at DESC
    ");
    $reimbursements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// C. Fetch Team Members List for Attendance dropdown select
$teamMembers = [];
if ($hasDepartment) {
    $stmt = $db->query("
        SELECT id, first_name, last_name, employee_id, job_title 
        FROM users 
        WHERE role = 'employee' AND department_id IN ($inClause)
        ORDER BY first_name ASC
    ");
    $teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Compute pending stats for badges
$pendingLeavesCount = count(array_filter($leaves, fn($l) => $l['status'] === 'pending'));
$pendingReimCount = count(array_filter($reimbursements, fn($r) => $r['status'] === 'pending'));

function getStatusBadgeHtml($status) {
    $classes = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200/50',
        'approved' => 'bg-green-50 text-green-700 border-green-200/50',
        'rejected' => 'bg-red-50 text-red-700 border-red-200/50'
    ];
    $labels = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak'
    ];
    $class = $classes[$status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
    $label = $labels[$status] ?? ucwords($status);
    return "<span class='inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-extrabold border {$class}'>{$label}</span>";
}

function getLeaveTypeBadgeHtml($type) {
    $colors = [
        'cuti tahunan' => 'text-primary bg-primary/5 border-primary/20',
        'cuti sakit' => 'text-blue-700 bg-blue-50 border-blue-200',
        'cuti melahirkan' => 'text-pink-700 bg-pink-50 border-pink-200',
        'izin khusus' => 'text-purple-700 bg-purple-50 border-purple-200'
    ];
    $labels = [
        'cuti tahunan' => 'Cuti Tahunan',
        'cuti sakit' => 'Cuti Sakit',
        'cuti melahirkan' => 'Cuti Melahirkan',
        'izin khusus' => 'Izin Khusus'
    ];
    $colorClass = $colors[$type] ?? 'text-gray-700 bg-gray-50 border-gray-200';
    $label = $labels[$type] ?? ucwords($type);
    return "<span class='inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {$colorClass}'>{$label}</span>";
}

function formatIndoDate($dateStr) {
    if (empty($dateStr)) return '-';
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;
    $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('d', $ts) . ' ' . $months[intval(date('m', $ts))] . ' ' . date('Y', $ts);
}

function getGravatarIcon($email) {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=80";
}
?>

<div class="space-y-8 animate-fade-in">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Persetujuan Tim</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola, setujui, tolak, dan audit Cuti, Reimbursement, serta Presensi tim di bawah departemen Anda.</p>
        </div>
        <?php if ($hasDepartment): ?>
        <div class="flex items-center gap-2">
            <span class="bg-green-50 text-green-700 text-xs font-extrabold px-3 py-1.5 rounded-lg border border-green-200/50 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-ping"></span>
                Divisi Fungsional Terhubung
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$hasDepartment): ?>
    <!-- Error No Department Allocated -->
    <div class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center max-w-2xl mx-auto space-y-4">
        <span class="material-symbols-outlined text-6xl text-red-500 animate-bounce">domain_disabled</span>
        <h3 class="text-xl font-bold text-red-800">Departemen Belum Dialokasikan</h3>
        <p class="text-red-600 text-sm leading-relaxed">
            Akun Anda saat ini memiliki peran <strong>Hiring Manager</strong>, namun data Anda di database tidak terhubung dengan divisi atau departemen fungsional mana pun. Silakan hubungi <strong>System Administrator</strong> untuk memperbarui data unit kerja Anda.
        </p>
    </div>
    <?php else: ?>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Leaves KPI -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_4px_20px_rgba(0,6,102,0.01)] flex items-center justify-between">
            <div class="space-y-2">
                <span class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Cuti Butuh ACC</span>
                <h3 class="text-2xl font-black text-amber-700"><?= $pendingLeavesCount ?> <span class="text-xs font-semibold text-amber-600">Pengajuan</span></h3>
            </div>
            <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-3 rounded-2xl text-xl font-bold <?= $pendingLeavesCount > 0 ? 'animate-pulse' : '' ?>">pending_actions</span>
        </div>
        <!-- Reimbursement KPI -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_4px_20px_rgba(0,6,102,0.01)] flex items-center justify-between">
            <div class="space-y-2">
                <span class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Klaim Butuh ACC</span>
                <h3 class="text-2xl font-black text-blue-700"><?= $pendingReimCount ?> <span class="text-xs font-semibold text-blue-600">Klaim</span></h3>
            </div>
            <span class="material-symbols-outlined text-blue-600 bg-blue-50 p-3 rounded-2xl text-xl font-bold <?= $pendingReimCount > 0 ? 'animate-pulse' : '' ?>">receipt_long</span>
        </div>
        <!-- Team Members KPI -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_4px_20px_rgba(0,6,102,0.01)] flex items-center justify-between">
            <div class="space-y-2">
                <span class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Total Anggota Tim</span>
                <h3 class="text-2xl font-black text-primary"><?= count($teamMembers) ?> <span class="text-xs font-semibold text-primary/70">Karyawan</span></h3>
            </div>
            <span class="material-symbols-outlined text-primary bg-primary/5 p-3 rounded-2xl text-xl font-bold">groups</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-2 flex gap-2">
        <button onclick="switchTab('leaves-tab')" id="btn-leaves-tab" class="tab-btn flex-1 py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer bg-primary text-white shadow-sm">
            <span class="material-symbols-outlined text-sm">event_busy</span>
            Permohonan Cuti
            <?php if ($pendingLeavesCount > 0): ?>
                <span class="bg-white text-primary text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $pendingLeavesCount ?></span>
            <?php endif; ?>
        </button>
        <button onclick="switchTab('reimbursements-tab')" id="btn-reimbursements-tab" class="tab-btn flex-1 py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer text-on-surface-variant hover:bg-surface-container-low">
            <span class="material-symbols-outlined text-sm">payments</span>
            Klaim Reimbursement
            <?php if ($pendingReimCount > 0): ?>
                <span class="bg-primary text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $pendingReimCount ?></span>
            <?php endif; ?>
        </button>
        <button onclick="switchTab('attendance-tab'); fetchAttendanceLogs();" id="btn-attendance-tab" class="tab-btn flex-1 py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer text-on-surface-variant hover:bg-surface-container-low">
            <span class="material-symbols-outlined text-sm">co_present</span>
            Presensi Tim
        </button>
    </div>

    <!-- Tab Contents Container -->
    <div class="space-y-6">
        <!-- 1. TAB: LEAVES -->
        <div id="content-leaves-tab" class="tab-content animate-fade-in">
            <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-outline-variant/15 bg-surface-container-low/20">
                    <h3 class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-lg">list_alt</span>
                        Daftar Riwayat Cuti & Izin Tim
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/30 border-b border-outline-variant/10 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                                <th class="py-4 px-6">Karyawan</th>
                                <th class="py-4 px-6">Tipe Cuti</th>
                                <th class="py-4 px-6">Tanggal Pengajuan</th>
                                <th class="py-4 px-6 text-center">Durasi</th>
                                <th class="py-4 px-6">Alasan</th>
                                <th class="py-4 px-6">Dokumen</th>
                                <th class="py-4 px-6 text-center">Status</th>
                                <th class="py-4 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-xs font-semibold text-on-surface">
                            <?php if (empty($leaves)): ?>
                            <tr>
                                <td colspan="8" class="py-12 px-6 text-center text-on-surface-variant font-medium">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">assignment_late</span>
                                    Belum ada riwayat permohonan cuti dari staf fungsional Anda.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($leaves as $req): ?>
                                <tr>
                                    <!-- Karyawan -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3 w-52 min-w-[200px]">
                                            <?php 
                                            $hash = md5(strtolower(trim($req['email'] ?? '')));
                                            $avatarUrl = !empty($req['profile_picture']) ? $req['profile_picture'] : getGravatarIcon($req['email']);
                                            ?>
                                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="w-8 h-8 rounded-full border object-cover" onerror="window.handleAvatarError(this, '<?= $hash ?>')" />
                                            <div>
                                                <h4 class="font-extrabold text-on-surface"><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></h4>
                                                <p class="text-[10px] text-on-surface-variant font-medium"><?= htmlspecialchars($req['employee_id'] ?: ($req['job_title'] ?: ucwords(str_replace('_', ' ', $req['role'] ?? 'Karyawan')))) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Tipe Cuti -->
                                    <td class="py-4 px-6">
                                        <?= getLeaveTypeBadgeHtml($req['leave_type']) ?>
                                    </td>
                                    <!-- Tanggal -->
                                    <td class="py-4 px-6">
                                        <div class="space-y-0.5">
                                            <p class="font-extrabold text-primary"><?= formatIndoDate($req['start_date']) ?></p>
                                            <p class="text-[10px] text-on-surface-variant">s.d. <?= formatIndoDate($req['end_date']) ?></p>
                                        </div>
                                    </td>
                                    <!-- Durasi -->
                                    <td class="py-4 px-6 text-center font-extrabold text-on-surface">
                                        <?= $req['duration'] ?> Hari
                                    </td>
                                    <!-- Alasan -->
                                    <td class="py-4 px-6 min-w-[380px]">
                                        <p class="text-on-surface-variant leading-relaxed break-words font-medium" title="<?= htmlspecialchars($req['reason']) ?>">
                                            <?= htmlspecialchars($req['reason']) ?>
                                        </p>
                                    </td>
                                    <!-- Dokumen -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <?php if (!empty($req['attachment_path'])): ?>
                                            <button onclick="previewFile('/hrops/leaves/view_attachment?file=<?= $req['attachment_path'] ?>', 'Bukti Pengajuan Cuti')" class="text-primary hover:underline font-bold flex items-center gap-1 cursor-pointer whitespace-nowrap">
                                                <span class="material-symbols-outlined text-sm">attach_file</span> Lihat Berkas
                                            </button>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant font-medium">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Status -->
                                    <td class="py-4 px-6 text-center">
                                        <?= getStatusBadgeHtml($req['status']) ?>
                                        <?php if ($req['status'] === 'rejected' && !empty($req['rejection_reason'])): ?>
                                            <p class="text-[9px] text-red-500 font-semibold mt-1 max-w-[120px] truncate" title="<?= htmlspecialchars($req['rejection_reason']) ?>">Alasan: <?= htmlspecialchars($req['rejection_reason']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Aksi -->
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick="processLeave('<?= $req['id'] ?>', 'approve')" class="p-1.5 text-green-600 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200 transition-colors cursor-pointer" title="Setujui Cuti">
                                                    <span class="material-symbols-outlined text-sm font-bold">check</span>
                                                </button>
                                                <button onclick="processLeave('<?= $req['id'] ?>', 'reject')" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg border border-red-200 transition-colors cursor-pointer" title="Tolak Cuti">
                                                    <span class="material-symbols-outlined text-sm font-bold">close</span>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant font-bold text-[10px] uppercase">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. TAB: REIMBURSEMENTS -->
        <div id="content-reimbursements-tab" class="tab-content hidden animate-fade-in">
            <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-outline-variant/15 bg-surface-container-low/20">
                    <h3 class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-lg">receipt_long</span>
                        Daftar Klaim & Reimbursement Tim
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1200px] w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/30 border-b border-outline-variant/10 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                                <th class="py-4 px-6">Karyawan</th>
                                <th class="py-4 px-6">Kategori</th>
                                <th class="py-4 px-6">Tanggal Pengajuan</th>
                                <th class="py-4 px-6">Nominal</th>
                                <th class="py-4 px-6">Keterangan</th>
                                <th class="py-4 px-6">Nota Bukti</th>
                                <th class="py-4 px-6 text-center">Status</th>
                                <th class="py-4 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-xs font-semibold text-on-surface">
                            <?php if (empty($reimbursements)): ?>
                            <tr>
                                <td colspan="8" class="py-12 px-6 text-center text-on-surface-variant font-medium">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">assignment_late</span>
                                    Belum ada riwayat pengajuan reimbursement dari staf fungsional Anda.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($reimbursements as $req): ?>
                                <tr>
                                    <!-- Karyawan -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3 w-52 min-w-[200px]">
                                            <?php 
                                            $hash = md5(strtolower(trim($req['email'] ?? '')));
                                            $avatarUrl = !empty($req['profile_picture']) ? $req['profile_picture'] : getGravatarIcon($req['email']);
                                            ?>
                                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="w-8 h-8 rounded-full border object-cover flex-shrink-0" onerror="window.handleAvatarError(this, '<?= $hash ?>')" />
                                            <div class="min-w-0">
                                                <h4 class="font-extrabold text-on-surface line-clamp-2 whitespace-normal break-words leading-tight" title="<?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?>"><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></h4>
                                                <p class="text-[10px] text-on-surface-variant font-medium font-mono mt-0.5"><?= htmlspecialchars($req['employee_id'] ?: '-') ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Kategori -->
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-surface-container-high text-on-surface border border-outline-variant/30 uppercase tracking-wide">
                                            <?= htmlspecialchars($req['category']) ?>
                                        </span>
                                    </td>
                                    <!-- Tanggal -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <div class="font-bold text-on-surface"><?= formatIndoDate($req['created_at']) ?></div>
                                        <div class="text-[10px] text-on-surface-variant font-mono mt-0.5"><?= date('H:i:s', strtotime($req['created_at'])) ?> WIB</div>
                                    </td>
                                    <!-- Nominal -->
                                    <td class="py-4 px-6 font-extrabold text-primary whitespace-nowrap">
                                        Rp <?= number_format($req['amount'], 0, ',', '.') ?>
                                    </td>
                                    <!-- Keterangan -->
                                    <td class="py-4 px-6 min-w-[380px]">
                                        <p class="text-on-surface-variant leading-relaxed break-words font-medium" title="<?= htmlspecialchars($req['description']) ?>">
                                            <?= htmlspecialchars($req['description']) ?>
                                        </p>
                                    </td>
                                    <!-- Nota -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <button onclick="previewFile('/hrops/reimbursements/view_receipt?file=<?= $req['receipt_path'] ?>', 'Kuitansi Reimbursement')" class="text-primary hover:underline font-bold flex items-center gap-1 cursor-pointer whitespace-nowrap">
                                            <span class="material-symbols-outlined text-sm">attach_file</span> Lihat Berkas
                                        </button>
                                    </td>
                                    <!-- Status -->
                                    <td class="py-4 px-6 text-center">
                                        <?= getStatusBadgeHtml($req['status']) ?>
                                        <?php if ($req['status'] === 'rejected' && !empty($req['rejection_reason'])): ?>
                                            <p class="text-[9px] text-red-500 font-semibold mt-1 max-w-[120px] truncate" title="<?= htmlspecialchars($req['rejection_reason']) ?>">Alasan: <?= htmlspecialchars($req['rejection_reason']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Aksi -->
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick="processReimbursement('<?= $req['id'] ?>', 'approve')" class="p-1.5 text-green-600 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200 transition-colors cursor-pointer" title="Setujui Klaim">
                                                    <span class="material-symbols-outlined text-sm font-bold">check</span>
                                                </button>
                                                <button onclick="processReimbursement('<?= $req['id'] ?>', 'reject')" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg border border-red-200 transition-colors cursor-pointer" title="Tolak Klaim">
                                                    <span class="material-symbols-outlined text-sm font-bold">close</span>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant font-bold text-[10px] uppercase">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. TAB: ATTENDANCE -->
        <div id="content-attendance-tab" class="tab-content hidden animate-fade-in">
            <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-6 shadow-sm space-y-6">
                <!-- Controls Row -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <h3 class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-lg">co_present</span>
                        Audit Presensi & Kehadiran Tim Harian
                    </h3>
                    
                    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto">
                        <!-- Date selector -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-on-surface-variant whitespace-nowrap">Pilih Tanggal:</span>
                            <input type="date" id="attendance-date" value="<?= date('Y-m-d') ?>" onchange="fetchAttendanceLogs()" class="p-2 border border-outline-variant/30 rounded-xl bg-surface-container-low font-semibold text-xs text-on-surface focus:outline-none focus:border-primary" />
                        </div>
                        <!-- Add attendance record manually -->
                        <button onclick="openAddAttendanceModal()" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all shadow-md shadow-primary/10 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">add_task</span> Input Kehadiran Tim
                        </button>
                    </div>
                </div>

                <!-- Stats summary row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-2xl bg-surface-container-low/30 border border-outline-variant/10 text-center font-bold">
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wider">Total Terdaftar</p>
                        <p class="text-xl font-black text-on-surface mt-1" id="att-stat-total">0</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-green-600 uppercase tracking-wider">Hadir Tepat Waktu</p>
                        <p class="text-xl font-black text-green-700 mt-1" id="att-stat-hadir">0</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-amber-600 uppercase tracking-wider">Terlambat</p>
                        <p class="text-xl font-black text-amber-700 mt-1" id="att-stat-terlambat">0</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-red-600 uppercase tracking-wider">Absen/Alpa</p>
                        <p class="text-xl font-black text-red-700 mt-1" id="att-stat-absent">0</p>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto border border-outline-variant/10 rounded-xl">
                    <table class="min-w-[1200px] w-full text-left border-collapse" id="attendance-table">
                        <thead>
                            <tr class="bg-surface-container-low/30 border-b border-outline-variant/10 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                                <th class="py-4 px-6">Karyawan</th>
                                <th class="py-4 px-6 text-center">Clock-In</th>
                                <th class="py-4 px-6 text-center">Status Clock-In</th>
                                <th class="py-4 px-6 text-center">Clock-Out</th>
                                <th class="py-4 px-6 text-center">Status Clock-Out</th>
                                <th class="py-4 px-6 text-center">Jam Kerja</th>
                                <th class="py-4 px-6 text-center">Mode Masuk</th>
                                <th class="py-4 px-6 text-center">Mode Pulang</th>
                                <th class="py-4 px-6 text-center">Metode</th>
                                <th class="py-4 px-6 text-center">IP &amp; Lokasi</th>
                                <th class="py-4 px-6">Alasan Koreksi</th>
                                <th class="py-4 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-xs font-semibold text-on-surface" id="attendance-tbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>


<!-- Modal: Add or Correct Attendance Manual Record -->
<div id="attendanceModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-2xl overflow-hidden shadow-2xl flex flex-col border border-outline-variant/10 animate-fade-in">
        <!-- Title -->
        <div class="px-6 py-4 border-b border-outline-variant/15 bg-surface-container-low/10 flex-shrink-0 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2" id="attendance-modal-title">
                <span class="material-symbols-outlined text-primary">add_task</span>
                Input Kehadiran Staf
            </h3>
            <button onclick="closeAttendanceModal()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-high cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <!-- Form Body -->
        <form id="attendanceForm" onsubmit="submitAttendanceCorrection(event)" class="p-6 space-y-4">
            <input type="hidden" id="form-att-id" name="attendance_id" />
            <input type="hidden" id="form-att-date" name="date" />

            <!-- Select Employee (Active only when creating new) -->
            <div class="space-y-1" id="select-employee-container">
                <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Karyawan Tim</label>
                <select id="form-att-user-id" name="user_id" class="w-full p-2.5 border border-outline-variant/30 bg-surface-container-low text-xs rounded-xl text-on-surface focus:outline-none focus:border-primary">
                    <option value="">-- Pilih Staf Fungsional --</option>
                    <?php foreach($teamMembers as $tm): ?>
                        <option value="<?= $tm['id'] ?>"><?= htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name']) ?> (<?= htmlspecialchars($tm['employee_id'] ?: 'NIK') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Display Name (Static when correcting existing) -->
            <div class="space-y-1 hidden" id="display-employee-container">
                <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Nama Karyawan</label>
                <p class="text-sm font-extrabold text-on-surface p-2.5 rounded-xl bg-surface-container-low" id="form-att-display-name">-</p>
            </div>

            <!-- Time Inputs -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Jam Masuk (Clock In)</label>
                    <input type="time" id="form-att-clock-in" name="clock_in" class="w-full p-2.5 border border-outline-variant/30 bg-surface-container-low text-xs rounded-xl text-on-surface focus:outline-none focus:border-primary" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Jam Pulang (Clock Out)</label>
                    <input type="time" id="form-att-clock-out" name="clock_out" class="w-full p-2.5 border border-outline-variant/30 bg-surface-container-low text-xs rounded-xl text-on-surface focus:outline-none focus:border-primary" />
                </div>
            </div>

            <!-- Reason -->
            <div class="space-y-1">
                <label class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Alasan Koreksi / Input Manual</label>
                <textarea id="form-att-reason" name="reason" placeholder="Contoh: Terjadi kendala mesin presensi / penugasan dinas luar kota..." rows="3" class="w-full p-2.5 border border-outline-variant/30 bg-surface-container-low text-xs rounded-xl text-on-surface focus:outline-none focus:border-primary"></textarea>
            </div>

            <!-- Buttons -->
            <div class="pt-4 flex gap-2 flex-shrink-0">
                <button type="button" onclick="closeAttendanceModal()" class="flex-1 py-2.5 bg-surface-container-high hover:bg-surface-container-high/80 text-on-surface-variant font-bold text-xs rounded-xl transition-all cursor-pointer">Batal</button>
                <button type="submit" class="flex-1 py-2.5 bg-primary hover:bg-primary/95 text-white font-bold text-xs rounded-xl shadow-md shadow-primary/10 transition-all cursor-pointer">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab switching controller
    function switchTab(tabId) {
        // Toggle tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-primary', 'text-white', 'shadow-sm');
            btn.classList.add('text-on-surface-variant', 'hover:bg-surface-container-low');
            // If the badge exists inside the button, reset colors
            const badge = btn.querySelector('span.bg-white');
            if (badge) {
                badge.classList.remove('bg-white', 'text-primary');
                badge.classList.add('bg-primary', 'text-white');
            }
            const activeBadge = btn.querySelector('span.bg-primary');
            if (activeBadge) {
                // Ensure correct state
            }
        });

        const activeBtn = document.getElementById('btn-' + tabId);
        activeBtn.classList.add('bg-primary', 'text-white', 'shadow-sm');
        activeBtn.classList.remove('text-on-surface-variant', 'hover:bg-surface-container-low');
        const activeBadge = activeBtn.querySelector('span.bg-primary');
        if (activeBadge) {
            activeBadge.classList.add('bg-white', 'text-primary');
            activeBadge.classList.remove('bg-primary', 'text-white');
        }

        // Toggle tab content panels
        document.querySelectorAll('.tab-content').forEach(panel => {
            panel.classList.add('hidden');
        });
        document.getElementById('content-' + tabId).classList.remove('hidden');
    }

    // Leave Approval/Rejection Flow
    function processLeave(id, action) {
        if (action === 'approve') {
            Swal.fire({
                title: 'Setujui Permohonan Cuti?',
                text: 'Tindakan ini akan secara otomatis memotong kuota cuti tahunan karyawan terkait.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1b5e20',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    const formData = new FormData();
                    formData.append('id', id);
                    fetch('/hrops/leaves/approve', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Disetujui!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#000666'
                            }).then(() => window.loadPage('/manager/approvals'));
                        } else {
                            Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
                        }
                    });
                }
            });
        } else {
            Swal.fire({
                title: 'Tolak Permohonan Cuti',
                text: 'Harap berikan alasan logis mengapa pengajuan cuti ini ditolak:',
                input: 'textarea',
                inputPlaceholder: 'Tulis alasan penolakan di sini...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Ya, Tolak',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Alasan penolakan wajib diisi!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('rejection_reason', result.value);
                    fetch('/hrops/leaves/reject', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Ditolak!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#000666'
                            }).then(() => window.loadPage('/manager/approvals'));
                        } else {
                            Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
                        }
                    });
                }
            });
        }
    }

    // Reimbursement Approval/Rejection Flow
    function processReimbursement(id, action) {
        if (action === 'approve') {
            Swal.fire({
                title: 'Setujui Klaim Reimbursement?',
                text: 'Tindakan ini akan mengesahkan nominal reimbursement dan memasukannya ke rekapitulasi slip payroll bulanan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1b5e20',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    const formData = new FormData();
                    formData.append('claim_id', id);
                    fetch('/hrops/reimbursements/approve', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Disetujui!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#000666'
                            }).then(() => window.loadPage('/manager/approvals'));
                        } else {
                            Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
                        }
                    });
                }
            });
        } else {
            Swal.fire({
                title: 'Tolak Reimbursement',
                text: 'Harap berikan alasan logis mengapa klaim ini ditolak (misal: nota tidak jelas/buram):',
                input: 'textarea',
                inputPlaceholder: 'Tulis alasan penolakan di sini...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Ya, Tolak',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Alasan penolakan wajib diisi!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    const formData = new FormData();
                    formData.append('claim_id', id);
                    formData.append('rejection_reason', result.value);
                    fetch('/hrops/reimbursements/reject', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Ditolak!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#000666'
                            }).then(() => window.loadPage('/manager/approvals'));
                        } else {
                            Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
                        }
                    });
                }
            });
        }
    }

    // Secure Document Viewer Modals
    function previewFile(url, title) {
        window.viewAttachmentGlobal(url, title);
    }

    function closePreviewModal() {
        window.closeGlobalFilePreview();
    }

    // Attendance Log Fetch and Dynamic Render
    function fetchAttendanceLogs() {
        const date = document.getElementById('attendance-date').value;
        const tbody = document.getElementById('attendance-tbody');
        tbody.innerHTML = `<tr><td colspan="7" class="py-8 px-6 text-center text-on-surface-variant font-medium">Sedang memuat catatan presensi tim...</td></tr>`;
        
        fetch(`/hrops/attendance/logs?date=${date}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const cfg = data.settings || {};
                // Populate KPIs
                document.getElementById('att-stat-total').innerText = data.stats.total;
                document.getElementById('att-stat-hadir').innerText = data.stats.hadir;
                document.getElementById('att-stat-terlambat').innerText = data.stats.terlambat;
                document.getElementById('att-stat-absent').innerText = data.stats.absent;

                window.currentAttendanceRows = data.rows; // Store globally to prevent quote crash

                if (data.rows.length === 0) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="py-12 px-6 text-center text-on-surface-variant font-medium">
                            <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">sensor_occupied</span>
                            Tidak ada data presensi tim yang tercatat pada tanggal ${formatDateIndoStr(date)}.
                        </td>
                    </tr>`;
                    return;
                }

                let html = '';
                data.rows.forEach((r, index) => {
                    const clockInStr = r.clock_in ? r.clock_in.substring(0, 5) : '--:--';
                    const clockOutStr = r.clock_out ? r.clock_out.substring(0, 5) : '--:--';
                    
                    // Status Clock-In Badge
                    const statusBadgeClass = getAttendanceStatusBadgeClass(r.status);
                    const statusBadgeDot = getAttendanceStatusBadgeDot(r.status);
                    const statusBadgeLabel = getAttendanceStatusBadgeLabel(r.status);
                    const statusClockInHtml = `
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ${statusBadgeClass}">
                            <span class="w-1.5 h-1.5 rounded-full ${statusBadgeDot}"></span>
                            ${statusBadgeLabel}
                        </span>
                    `;

                    // Status Clock-Out Badge
                    let statusClockOutHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.clock_out) {
                        const statusOutClass = getClockOutStatusBadgeClass(r.clock_out_status);
                        const statusOutDot = getClockOutStatusBadgeDot(r.clock_out_status);
                        const statusOutLabel = getClockOutStatusBadgeLabel(r.clock_out_status);
                        statusClockOutHtml = `
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ${statusOutClass}">
                                <span class="w-1.5 h-1.5 rounded-full ${statusOutDot}"></span>
                                ${statusOutLabel}
                            </span>
                        `;
                    }

                    // Jam Kerja Calculation
                    let workingHours = '-';
                    if (r.clock_in && r.clock_out) {
                        const parseTimeToSeconds = (t) => {
                            if (!t) return 0;
                            const pts = t.split(':');
                            return parseInt(pts[0])*3600 + parseInt(pts[1])*60 + (parseInt(pts[2] || 0));
                        };
                        const diffSec = parseTimeToSeconds(r.clock_out) - parseTimeToSeconds(r.clock_in);
                        if (diffSec > 0) {
                            const hours = Math.floor(diffSec / 3600);
                            const minutes = Math.floor((diffSec % 3600) / 60);
                            workingHours = `${hours}j ${minutes}m`;
                        } else {
                            workingHours = '0j 0m';
                        }
                    }

                    // Mode Masuk (Clock-in Mode)
                    let modeMasukHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.work_mode) {
                        let color = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        let icon = 'business';
                        let text = 'WFO';
                        if (r.work_mode === 'WFA') {
                            color = 'bg-blue-50 text-blue-700 border-blue-200';
                            icon = 'home_work';
                            text = 'WFA/WFC';
                        } else if (r.work_mode === 'WFH') {
                            color = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            icon = 'home';
                            text = 'WFH';
                        }
                        modeMasukHtml = `
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold border ${color}">
                                <span class="material-symbols-outlined text-[11px]">${icon}</span>
                                ${text}
                            </span>
                        `;
                    }

                    // Mode Pulang (Clock-out Mode)
                    let modePulangHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.work_mode_out) {
                        let colorOut = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        let iconOut = 'business';
                        let textOut = 'WFO';
                        if (r.work_mode_out === 'WFA') {
                            colorOut = 'bg-blue-50 text-blue-700 border-blue-200';
                            iconOut = 'home_work';
                            textOut = 'WFA/WFC';
                        } else if (r.work_mode_out === 'WFH') {
                            colorOut = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            iconOut = 'home';
                            textOut = 'WFH';
                        }
                        modePulangHtml = `
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold border ${colorOut}">
                                <span class="material-symbols-outlined text-[11px]">${iconOut}</span>
                                ${textOut}
                            </span>
                        `;
                    }

                    // Metode Location
                    let metodeHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.location_method) {
                        let icon = 'location_on';
                        let colorClass = 'text-amber-600';
                        let iconColor = 'text-amber-500';
                        if (r.location_method === 'WIFI') {
                            icon = 'wifi';
                            colorClass = 'text-primary';
                            iconColor = 'text-primary';
                        } else if (r.location_method === 'POP') {
                            icon = 'laptop';
                            colorClass = 'text-blue-600';
                            iconColor = 'text-blue-500';
                        }
                        metodeHtml = `
                            <span class="inline-flex items-center gap-1 text-[11px] font-extrabold ${colorClass}">
                                <span class="material-symbols-outlined text-xs ${iconColor}">${icon}</span>
                                ${escapeHtml(r.location_method)}
                            </span>
                        `;
                    }

                    // IP & Lokasi
                    let lokasiHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.location_method) {
                        let details = '';
                        if (r.ip_address) {
                            details += `<div class="text-[10px] text-on-surface-variant/60 font-mono">IP: ${escapeHtml(r.ip_address)}</div>`;
                        }
                        if (r.clock_in_latitude) {
                            details += `<div class="text-[10px] text-on-surface-variant/50 font-mono mt-0.5" title="Koordinat Masuk">In: ${parseFloat(r.clock_in_latitude).toFixed(4)}, ${parseFloat(r.clock_in_longitude).toFixed(4)}</div>`;
                        }
                        if (r.clock_out_latitude) {
                            details += `<div class="text-[10px] text-on-surface-variant/50 font-mono mt-0.5" title="Koordinat Pulang">Out: ${parseFloat(r.clock_out_latitude).toFixed(4)}, ${parseFloat(r.clock_out_longitude).toFixed(4)}</div>`;
                        }
                        if (r.clock_in_latitude || r.clock_out_latitude) {
                            const empName = `${r.first_name} ${r.last_name}`;
                            const mapConfigObj = {
                                employee_name: empName,
                                in_lat: r.clock_in_latitude,
                                in_lng: r.clock_in_longitude,
                                out_lat: r.clock_out_latitude,
                                out_lng: r.clock_out_longitude,
                                office_lat: cfg.office_lat,
                                office_lng: cfg.office_lng,
                                office_radius: cfg.office_radius_m,
                                home_lat: r.home_latitude,
                                home_lng: r.home_longitude,
                                home_radius: cfg.home_radius_m,
                                clock_in: clockInStr,
                                clock_out: clockOutStr,
                                work_mode: r.work_mode,
                                work_mode_out: r.work_mode_out
                            };
                            window.mapConfigs = window.mapConfigs || [];
                            const mapIndex = window.mapConfigs.push(mapConfigObj) - 1;
                            details += `
                                <button type="button" class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary border border-primary/20 rounded-md text-[9px] font-bold hover:bg-primary/20 transition-all cursor-pointer" 
                                        onclick="showLeafletMap(${mapIndex})">
                                    <span class="material-symbols-outlined text-[10px] font-bold">map</span>
                                    <span>Peta</span>
                                </button>
                            `;
                        }
                        lokasiHtml = `
                            <div class="flex flex-col items-center">
                                ${details}
                            </div>
                        `;
                    }

                    const reasonText = r.correction_reason || '-';
                    const hash = md5((r.email || '').trim().toLowerCase());
                    const avatar = r.profile_picture ? r.profile_picture : `https://www.gravatar.com/avatar/${hash}?d=identicon&s=80`;

                    html += `
                    <tr class="hover:bg-surface-container-low/20 transition-colors">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <img src="${avatar}" alt="Avatar" class="w-8 h-8 rounded-full border object-cover" onerror="window.handleAvatarError(this, '${hash}')" />
                                <div>
                                    <h4 class="font-extrabold text-on-surface whitespace-nowrap">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</h4>
                                    <p class="text-[10px] text-on-surface-variant font-medium">${escapeHtml(r.employee_id || r.position || 'Karyawan')}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-center font-mono font-semibold text-on-surface-variant text-sm whitespace-nowrap">${clockInStr}</td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">${statusClockInHtml}</td>
                        <td class="py-4 px-6 text-center font-mono font-semibold text-on-surface-variant text-sm whitespace-nowrap">${clockOutStr}</td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">${statusClockOutHtml}</td>
                        <td class="py-4 px-6 text-center text-sm font-semibold text-on-surface-variant whitespace-nowrap">${workingHours}</td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">${modeMasukHtml}</td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">${modePulangHtml}</td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">${metodeHtml}</td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">${lokasiHtml}</td>
                        <td class="py-4 px-6 min-w-[300px] whitespace-normal break-words font-medium text-on-surface-variant leading-relaxed" title="${escapeHtml(reasonText)}">${escapeHtml(reasonText)}</td>
                        <td class="py-4 px-6 text-center">
                            <button onclick="openEditAttendanceModalByIndex(${index})" class="p-1.5 text-primary bg-primary/5 hover:bg-primary/10 rounded-lg border border-primary/20 transition-colors cursor-pointer" title="Koreksi Presensi">
                                <span class="material-symbols-outlined text-sm font-bold">border_color</span>
                            </button>
                        </td>
                    </tr>`;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `<tr><td colspan="12" class="py-8 px-6 text-center text-red-600 font-bold">${data.message}</td></tr>`;
            }
        });
    }

    function getAttendanceStatusBadgeClass(status) {
        switch (status) {
            case 'tepat waktu': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'awal':        return 'bg-indigo-50 text-indigo-700 border-indigo-200';
            case 'terlambat':   return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'sakit/izin':  return 'bg-blue-50 text-blue-700 border-blue-200';
            case 'libur':       return 'bg-indigo-50 text-indigo-700 border-indigo-200';
            default:            return 'bg-red-50 text-red-700 border-red-200';
        }
    }
    function getAttendanceStatusBadgeDot(status) {
        switch (status) {
            case 'tepat waktu': return 'bg-emerald-500';
            case 'awal':        return 'bg-indigo-500';
            case 'terlambat':   return 'bg-amber-500';
            case 'sakit/izin':  return 'bg-blue-500';
            case 'libur':       return 'bg-indigo-500';
            default:            return 'bg-red-500';
        }
    }
    function getAttendanceStatusBadgeLabel(status) {
        switch (status) {
            case 'tepat waktu': return 'Tepat Waktu';
            case 'awal':        return 'Masuk Awal';
            case 'terlambat':   return 'Terlambat';
            case 'sakit/izin':  return 'Sakit / Izin';
            case 'libur':       return 'Libur';
            default:            return 'Alpa';
        }
    }

    function getClockOutStatusBadgeClass(status) {
        switch (status) {
            case 'pulang lambat': return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'pulang cepat':  return 'bg-red-50 text-red-700 border-red-200';
            case 'tepat waktu':
            case 'wajar':
            case 'normal':        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'tidak presensi pulang': return 'bg-rose-50 text-rose-700 border-rose-200';
            default:              return 'bg-surface-container-low text-on-surface-variant/40 border-outline-variant/10';
        }
    }
    function getClockOutStatusBadgeDot(status) {
        switch (status) {
            case 'pulang lambat': return 'bg-amber-500';
            case 'pulang cepat':  return 'bg-red-500';
            case 'tepat waktu':
            case 'wajar':
            case 'normal':        return 'bg-emerald-500';
            case 'tidak presensi pulang': return 'bg-rose-500';
            default:              return 'bg-on-surface-variant/20';
        }
    }
    function getClockOutStatusBadgeLabel(status) {
        switch (status) {
            case 'pulang lambat': return 'Pulang Lambat';
            case 'pulang cepat':  return 'Pulang Cepat';
            case 'tepat waktu':
            case 'wajar':
            case 'normal':        return 'Tepat Waktu';
            case 'tidak presensi pulang': return 'Tidak Presensi Pulang';
            default:              return '—';
        }
    }

    function formatDateIndoStr(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        return parseInt(parts[2]) + ' ' + months[parseInt(parts[1]) - 1] + ' ' + parts[0];
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // MD5 implementation for local gravatar hash generating
    function md5(string) {
        function RotateLeft(lValue, iShiftBits) {
            return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
        }
        function AddUnsigned(lX,lY) {
            var lX4,lY4,lX8,lY8,lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lResult & 0x40000000) {
                    return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lResult ^ lX8 ^ lY8);
            }
        }
        function F(x,y,z) { return (x & y) | ((~x) & z); }
        function G(x,y,z) { return (x & z) | (y & (~z)); }
        function H(x,y,z) { return (x ^ y ^ z); }
        function I(x,y,z) { return (y ^ (x | (~z))); }
        function II(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }
        function GG(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }
        function HH(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }
        function II2(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }
        function ConvertToWordArray(string) {
            var lWordCount;
            var lMessageLength = string.length;
            var lNumberOfWords_temp1=lMessageLength + 8;
            var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
            var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
            var lWordArray=Array(lNumberOfWords-1);
            var lBytePosition = 0;
            var lByteCount = 0;
            while ( lByteCount < lMessageLength ) {
                lWordCount = (lByteCount-(lByteCount % 4))/4;
                lBytePosition = (lByteCount % 4)*8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount-(lByteCount % 4))/4;
            lBytePosition = (lByteCount % 4)*8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
            lWordArray[lNumberOfWords-2] = lMessageLength<<3;
            lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
            return lWordArray;
        }
        function WordToHex(lValue) {
            var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
            for (lCount = 0;lCount<=3;lCount++) {
                lByte = (lValue>>>(lCount*8)) & 255;
                WordToHexValue_temp = "0" + lByte.toString(16);
                WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
            }
            return WordToHexValue;
        }
        function Utf8Encode(string) {
            string = string.replace(/\r\n/g,"\n");
            var utftext = "";
            for (var n = 0; n < string.length; n++) {
                var c = string.charCodeAt(n);
                if (c < 128) {
                    utftext += String.fromCharCode(c);
                } else if((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                } else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
            }
            return utftext;
        }
        var x=Array();
        var k,AA,BB,CC,DD,a,b,c,d;
        var S11=7, S12=12, S13=17, S14=22;
        var S21=5, S22=9, S23=14, S24=20;
        var S31=4, S32=11, S33=16, S34=23;
        var S41=6, S42=10, S43=15, S44=21;
        string = Utf8Encode(string);
        x = ConvertToWordArray(string);
        a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
        for (k=0;k<x.length;k+=16) {
            AA=a; BB=b; CC=c; DD=d;
            a=II(a,b,c,d,x[k+0], S11,0xD76AA478); d=II(d,a,b,c,x[k+1], S12,0xE8C7B756); c=II(c,d,a,b,x[k+2], S13,0x242070DB); b=II(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
            a=II(a,b,c,d,x[k+4], S11,0xF57C0FAF); d=II(d,a,b,c,x[k+5], S12,0x4787C62A); c=II(c,d,a,b,x[k+6], S13,0xA8304613); b=II(b,c,d,a,x[k+7], S14,0xFD469501);
            a=II(a,b,c,d,x[k+8], S11,0x698098D8); d=II(d,a,b,c,x[k+9], S12,0x8B44F7AF); c=II(c,d,a,b,x[k+10],S13,0xFFFF5BB1); b=II(b,c,d,a,x[k+11],S14,0x895CD7BE);
            a=II(a,b,c,d,x[k+12],S11,0x6B901122); d=II(d,a,b,c,x[k+13],S12,0xFD987193); c=II(c,d,a,b,x[k+14],S13,0xA679438E); b=II(b,c,d,a,x[k+15],S14,0x49B40821);
            a=GG(a,b,c,d,x[k+1], S21,0xF61E2562); d=GG(d,a,b,c,x[k+6], S22,0xC040B340); c=GG(c,d,a,b,x[k+11],S23,0x265E5A51); b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
            a=GG(a,b,c,d,x[k+5], S21,0xD62F105D); d=GG(d,a,b,c,x[k+10],S22,0x2441453);  c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681); b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
            a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6); d=GG(d,a,b,c,x[k+14],S22,0xC33707D6); c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87); b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
            a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905); d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8); c=GG(c,d,a,b,x[k+7], S23,0x676F02D9); b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
            a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942); d=HH(d,a,b,c,x[k+8], S32,0x8771F681); c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122); b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
            a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44); d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9); c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60); b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
            a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6); d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA); c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085); b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
            a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039); d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5); c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8); b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
            a=II2(a,b,c,d,x[k+0], S41,0xF4292244); d=II2(d,a,b,c,x[k+7], S42,0x432AFF97); c=II2(c,d,a,b,x[k+14],S43,0xAB9423A7); b=II2(b,c,d,a,x[k+5], S44,0xFC93A039);
            a=II2(a,b,c,d,x[k+12],S41,0x655B59C3); d=II2(d,a,b,c,x[k+3], S42,0x8F0CCC92); c=II2(c,d,a,b,x[k+10],S43,0xFFEFF47D); b=II2(b,c,d,a,x[k+1], S44,0x85845DD1);
            a=II2(a,b,c,d,x[k+8], S41,0x6FA87E4F); d=II2(d,a,b,c,x[k+15],S42,0xFE2CE6E0); c=II2(c,d,a,b,x[k+6], S43,0xA3014314); b=II2(b,c,d,a,x[k+13],S44,0x4E0811A1);
            a=II2(a,b,c,d,x[k+4], S41,0xF7537E82); d=II2(d,a,b,c,x[k+11],S42,0xBD3AF235); c=II2(c,d,a,b,x[k+2], S43,0x2AD7D2BB); b=II2(b,c,d,a,x[k+9], S44,0xEB86D391);
            a=AddUnsigned(a,AA); b=AddUnsigned(b,BB); c=AddUnsigned(c,CC); d=AddUnsigned(d,DD);
        }
        var temp=WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
        return temp.toLowerCase();
    }

    // Attendance manual input or correction modal controller
    function openAddAttendanceModal() {
        document.getElementById('attendanceForm').reset();
        document.getElementById('form-att-id').value = '';
        document.getElementById('form-att-date').value = document.getElementById('attendance-date').value;
        
        document.getElementById('select-employee-container').classList.remove('hidden');
        document.getElementById('display-employee-container').classList.add('hidden');
        
        document.getElementById('attendance-modal-title').innerHTML = `<span class='material-symbols-outlined text-primary'>add_task</span> Input Kehadiran Staf`;
        document.getElementById('attendanceModal').classList.remove('hidden');
    }

    function openEditAttendanceModal(record) {
        document.getElementById('attendanceForm').reset();
        document.getElementById('form-att-id').value = record.id;
        document.getElementById('form-att-date').value = record.attendance_date;
        document.getElementById('form-att-user-id').value = record.user_id;

        document.getElementById('select-employee-container').classList.add('hidden');
        document.getElementById('display-employee-container').classList.remove('hidden');
        document.getElementById('form-att-display-name').innerText = `${record.first_name} ${record.last_name} (${record.employee_id || record.position || 'Karyawan'})`;
        
        if (record.clock_in) document.getElementById('form-att-clock-in').value = record.clock_in.substring(0, 5);
        if (record.clock_out) document.getElementById('form-att-clock-out').value = record.clock_out.substring(0, 5);
        document.getElementById('form-att-reason').value = record.correction_reason || '';

        document.getElementById('attendance-modal-title').innerHTML = `<span class='material-symbols-outlined text-primary'>border_color</span> Koreksi Presensi Staf`;
        document.getElementById('attendanceModal').classList.remove('hidden');
    }

    function closeAttendanceModal() {
        document.getElementById('attendanceModal').classList.add('hidden');
    }

    function submitAttendanceCorrection(e) {
        e.preventDefault();
        const form = document.getElementById('attendanceForm');
        const reason = document.getElementById('form-att-reason').value.trim();
        const userId = document.getElementById('form-att-user-id').value;

        if (!userId && !document.getElementById('form-att-id').value) {
            Swal.fire({ title: 'Gagal!', text: 'Silakan pilih karyawan terlebih dahulu.', icon: 'error', confirmButtonColor: '#000666' });
            return;
        }

        if (!reason) {
            Swal.fire({ title: 'Gagal!', text: 'Alasan koreksi/input manual wajib diisi.', icon: 'error', confirmButtonColor: '#000666' });
            return;
        }

        Swal.showLoading();
        const formData = new FormData(form);

        fetch('/hrops/attendance/correct', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    closeAttendanceModal();
                    fetchAttendanceLogs();
                });
            } else {
                Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
            }
        });
    }

    function openEditAttendanceModalByIndex(index) {
        const record = window.currentAttendanceRows[index];
        openEditAttendanceModal(record);
    }

    // Expose all functions to global scope to prevent IIFE isolation issues
    window.switchTab = switchTab;
    window.processLeave = processLeave;
    window.processReimbursement = processReimbursement;
    window.previewFile = previewFile;
    window.closePreviewModal = closePreviewModal;
    window.fetchAttendanceLogs = fetchAttendanceLogs;
    window.openAddAttendanceModal = openAddAttendanceModal;
    window.openEditAttendanceModal = openEditAttendanceModal;
    window.openEditAttendanceModalByIndex = openEditAttendanceModalByIndex;
    window.closeAttendanceModal = closeAttendanceModal;
    window.submitAttendanceCorrection = submitAttendanceCorrection;

    // Default init tab
    document.addEventListener('DOMContentLoaded', () => {
        // Tab check
        const activeTabBtn = document.querySelector('.tab-btn.bg-primary');
        if (activeTabBtn && activeTabBtn.id === 'btn-attendance-tab') {
            fetchAttendanceLogs();
        }
    });
</script>