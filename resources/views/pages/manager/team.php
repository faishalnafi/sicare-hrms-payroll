<?php
// Hiring Manager: Team Members Dashboard with Strict Hierarchical Isolation and Granular Permitted Personal Data
$db = \App\Config\Database::getInstance()->getConnection();

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? '';

// Helper to get descendant department IDs recursively
if (!function_exists('getDescendantsTeam')) {
    function getDescendantsTeam($db, $deptId) {
        $ids = [$deptId];
        $stmt = $db->prepare("SELECT id FROM departments WHERE parent_id = ?");
        $stmt->execute([$deptId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($children as $childId) {
            $ids = array_merge($ids, getDescendantsTeam($db, $childId));
        }
        return $ids;
    }
}

// Get manager's department
$stmt = $db->prepare("SELECT department_id FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$managerDeptId = $stmt->fetchColumn();

$allowedDepts = [];
if (!empty($managerDeptId)) {
    $allowedDepts = getDescendantsTeam($db, $managerDeptId);
}

$hasDepartment = !empty($allowedDepts);
$inClause = $hasDepartment ? implode(',', array_map(fn($id) => $db->quote($id), $allowedDepts)) : "''";

// Fetch Team Members with strictly permitted operational & contact details
// (Under GDPR & UU PDP, private demographic data like Tanggal Lahir, Gender, and Status Pernikahan are hidden from Manager)
$teamMembers = [];
if ($hasDepartment) {
    $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.employee_id, u.role, u.job_title, u.profile_picture, 
               u.home_latitude, u.home_longitude, u.no_telepon, u.alamat_domisili, u.annual_leave_quota, d.name AS department_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.department_id IN ($inClause) AND u.id != :manager_id
        ORDER BY d.name ASC, u.first_name ASC
    ");
    $stmt->execute(['manager_id' => $userId]);
    $teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to generate Gravatar avatar
function getGravatarTeam($email, $profilePic = null) {
    if (!empty($profilePic)) {
        return $profilePic;
    }
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
}

// Helper to format clean WhatsApp link
function getWhatsAppLinkTeam($phone) {
    if (empty($phone)) return '#';
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '0')) {
        $clean = '62' . substr($clean, 1);
    }
    return "https://wa.me/" . $clean;
}
?>

<style>
.team-hero-card {
    background: linear-gradient(135deg, #000666 0%, #1a237e 50%, #0d47a1 100%);
    position: relative;
    overflow: hidden;
}
.team-hero-card::before {
    content: '';
    position: absolute;
    top: -40%; right: -10%;
    width: 380px; height: 380px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
    pointer-events: none;
}
.team-hero-card::after {
    content: '';
    position: absolute;
    bottom: -60%; left: -5%;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,0.03);
    pointer-events: none;
}
</style>

<div class="space-y-6">
    <!-- Header Banner - Styled exactly like the soft, premium deep navy Hero Card from Gambar 2 -->
    <div class="team-hero-card rounded-3xl p-6 md:p-8 text-white shadow-xl">
        <div class="relative z-10 space-y-3 max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-[10px] font-bold bg-white/10 text-white/90 border border-white/15 uppercase tracking-wide">
                <span class="material-symbols-outlined text-[13px] font-bold">group</span>
                <span>Divisi Fungsional Terhubung</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-black font-headline tracking-tight leading-tight text-white">Daftar Anggota Tim</h1>
            <p class="text-white/80 text-xs md:text-sm font-medium leading-relaxed max-w-2xl">
                Pantau profil, posisi fungsional, dan konfigurasi koordinat rumah (WFH) seluruh staf di bawah divisi Anda dengan prinsip isolasi data hierarkis. 
                <span class="text-amber-400 font-extrabold">Data administratif rahasia (KTP, NPWP, BPJS, Bank, & Gaji) dilindungi di bawah aturan Segregation of Duties.</span>
            </p>
        </div>
    </div>

    <!-- Search & Filters Container with View Mode Toggle -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-3xl p-5 shadow-sm flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4">
        <!-- Search bar -->
        <div class="relative flex-grow max-w-xl">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                <span class="material-symbols-outlined text-lg">search</span>
            </span>
            <input type="text" id="teamSearchInput" oninput="window.filterTeamMembers()" placeholder="Cari nama, NIK, jabatan, divisi, no. HP, atau domisili..." 
                class="w-full pl-10 pr-4 py-2.5 border border-outline-variant/30 rounded-2xl bg-surface-container-low text-xs text-on-surface font-semibold placeholder-on-surface-variant/55 focus:outline-none focus:border-primary transition-all" />
        </div>
        
        <!-- Controls Right: View Toggle + Stats -->
        <div class="flex flex-wrap items-center gap-4 self-start lg:self-auto">
            <!-- View Mode Switcher -->
            <div class="flex items-center bg-surface-container-low border border-outline-variant/20 rounded-2xl p-1">
                <button onclick="window.toggleTeamView('grid')" id="viewBtnGrid" class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer transition-all bg-primary text-white shadow-sm">
                    <span class="material-symbols-outlined text-xs">grid_view</span> Grid
                </button>
                <button onclick="window.toggleTeamView('table')" id="viewBtnTable" class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer transition-all text-on-surface-variant hover:bg-surface-container-high/50">
                    <span class="material-symbols-outlined text-xs">table_chart</span> Tabel
                </button>
            </div>

            <!-- Stats widget -->
            <div class="flex items-center gap-4 bg-surface-container-low/40 border border-outline-variant/10 rounded-2xl px-4 py-1.5">
                <div class="text-center border-r border-outline-variant/20 pr-4">
                    <p class="text-[9px] text-on-surface-variant uppercase font-extrabold tracking-wider">Total Anggota</p>
                    <p class="text-base font-black text-primary" id="stat-total"><?= count($teamMembers) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-on-surface-variant uppercase font-extrabold tracking-wider">WFH Setel</p>
                    <p class="text-base font-black text-indigo-600" id="stat-wfh-set">
                        <?= count(array_filter($teamMembers, fn($tm) => $tm['home_latitude'] !== null && $tm['home_longitude'] !== null)) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW CONTAINER: GRID VIEW (DEFAULT) -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="teamGridContainer">
        <?php if (empty($teamMembers)): ?>
        <div class="col-span-full bg-surface-container-lowest border border-outline-variant/15 rounded-3xl py-16 px-6 text-center shadow-sm">
            <span class="material-symbols-outlined text-5xl text-outline-variant block mb-3">group_off</span>
            <h3 class="text-on-surface font-extrabold text-base">Belum Ada Anggota Tim</h3>
            <p class="text-on-surface-variant/70 text-xs mt-1">Tidak ada karyawan yang terdaftar di bawah divisi Anda saat ini.</p>
        </div>
        <?php else: ?>
            <?php foreach ($teamMembers as $tm): ?>
            <?php 
                $hasWfhGeofence = ($tm['home_latitude'] !== null && $tm['home_longitude'] !== null);
                $hasPhone = !empty($tm['no_telepon']);
                $waLink = $hasPhone ? getWhatsAppLinkTeam($tm['no_telepon']) : '#';
                $waOnclick = $hasPhone ? '' : 'onclick="event.preventDefault(); Swal.fire({ title: \'WhatsApp Belum Setel\', text: \'Staf fungsional terkait belum mencantumkan nomor telepon aktif di profil mandiri mereka.\', icon: \'warning\', confirmButtonColor: \'#000666\' })"';
                
                $searchString = strtolower($tm['first_name'] . ' ' . $tm['last_name'] . ' ' . ($tm['employee_id'] ?? '') . ' ' . ($tm['job_title'] ?? $tm['role']) . ' ' . ($tm['department_name'] ?? '') . ' ' . ($tm['no_telepon'] ?? '') . ' ' . ($tm['alamat_domisili'] ?? ''));
            ?>
            <div class="team-card bg-surface-container-lowest border border-outline-variant/15 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,6,102,0.005)] hover:shadow-[0_8px_30px_rgba(0,6,102,0.015)] hover:border-primary/20 transition-all duration-300 flex flex-col justify-between" data-search="<?= htmlspecialchars($searchString) ?>">
                <div class="space-y-4">
                    <!-- Top row: Avatar & Profile info -->
                    <div class="flex items-start gap-4">
                        <div class="relative flex-shrink-0">
                            <?php $hash = md5(strtolower(trim($tm['email'] ?? ''))); ?>
                            <img src="<?= htmlspecialchars(getGravatarTeam($tm['email'], $tm['profile_picture'])) ?>" alt="Avatar" class="w-14 h-14 rounded-full border border-outline-variant/20 object-cover shadow-sm" onerror="window.handleAvatarError(this, '<?= $hash ?>')" />
                            <span class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full border-2 border-surface-container-lowest bg-green-500 animate-pulse" title="Aktif"></span>
                        </div>
                        <div class="space-y-0.5 min-w-0">
                            <h3 class="font-extrabold text-sm text-on-surface truncate pr-1" title="<?= htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name']) ?>">
                                <?= htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name']) ?>
                            </h3>
                            <!-- JABATAN (Job Title) - Bold and explicitly visible under the name -->
                            <p class="text-primary font-bold text-xs truncate max-w-[170px]" title="<?= htmlspecialchars($tm['job_title'] ?: ucwords($tm['role'])) ?>">
                                <?= htmlspecialchars($tm['job_title'] ?: ucwords($tm['role'])) ?>
                            </p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-extrabold bg-surface-container-high text-on-surface-variant border border-outline-variant/20 tracking-wider">
                                <?= htmlspecialchars($tm['employee_id'] ?: 'NIK BELUM DISETEL') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-outline-variant/8"></div>

                    <!-- Middle Info list: Strictly permitted operational and contact details only -->
                    <div class="space-y-2.5 text-xs">
                        <!-- Department -->
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant font-bold text-[10px] uppercase">Divisi/Dept</span>
                            <span class="font-extrabold text-on-surface text-right truncate max-w-[150px] bg-surface-container-low px-2 py-0.5 rounded" title="<?= htmlspecialchars($tm['department_name'] ?? 'Pusat') ?>">
                                <?= htmlspecialchars($tm['department_name'] ?? 'Pusat') ?>
                            </span>
                        </div>
                        <!-- Email -->
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant font-bold text-[10px] uppercase">Email Resmi</span>
                            <a href="mailto:<?= htmlspecialchars($tm['email']) ?>" class="font-bold text-primary hover:underline truncate max-w-[160px] text-right font-mono" title="Hubungi via Email">
                                <?= htmlspecialchars($tm['email']) ?>
                            </a>
                        </div>
                        <!-- WhatsApp -->
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant font-bold text-[10px] uppercase">No. WhatsApp</span>
                            <span class="font-extrabold text-on-surface text-right font-mono">
                                <?= htmlspecialchars($tm['no_telepon'] ?: 'Belum Diisi') ?>
                            </span>
                        </div>
                        <!-- Sisa Cuti -->
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant font-bold text-[10px] uppercase">Sisa Kuota Cuti</span>
                            <span class="font-extrabold text-on-surface text-right">
                                <?= $tm['annual_leave_quota'] ?> Hari
                            </span>
                        </div>
                        <!-- WFH Geofence -->
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant font-bold text-[10px] uppercase">Titik WFH (Home)</span>
                            <?php if ($hasWfhGeofence): ?>
                                <span class="inline-flex items-center gap-1 font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-150 rounded-lg px-2 py-0.5 text-[10px]" title="<?= $tm['home_latitude'] ?>, <?= $tm['home_longitude'] ?>">
                                    <span class="material-symbols-outlined text-xs">home_pin</span>
                                    <span>Terseting</span>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 border border-amber-150 rounded-lg px-2 py-0.5 text-[10px]">
                                    <span class="material-symbols-outlined text-xs">warning</span>
                                    <span>Belum Disetel</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Collapsible section for other operational data (Addresses, Coordinates) -->
                    <details class="group/detail mt-3 pt-3 border-t border-outline-variant/8 text-[11px] text-on-surface-variant font-medium">
                        <summary class="flex justify-between items-center cursor-pointer list-none hover:text-primary font-bold transition-colors select-none">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">home_work</span>
                                Lihat Detail Lokasi Domisili
                            </span>
                            <span class="material-symbols-outlined text-[16px] group-open/detail:rotate-180 transition-transform">expand_more</span>
                        </summary>
                        <div class="mt-3 space-y-2 border-l-2 border-primary/20 pl-2.5 py-1 text-xs">
                            <div class="space-y-2">
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Koordinat WFH (GPS)</span>
                                    <span class="font-extrabold text-[#000666] text-[10px] font-mono tracking-tight">
                                        <?= $hasWfhGeofence ? $tm['home_latitude'] . ', ' . $tm['home_longitude'] : 'Belum Dikonfigurasi' ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Alamat Domisili</span>
                                    <span class="font-bold text-on-surface text-[11px] leading-relaxed block mt-0.5">
                                        <?= htmlspecialchars($tm['alamat_domisili'] ?: 'Alamat domisili lengkap belum dikonfigurasi.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>

                <!-- Action Buttons (Strictly Bound Global Scope handlers) -->
                <div class="pt-4 mt-4 border-t border-outline-variant/8 flex gap-2">
                    <button type="button" data-name="<?= htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name'], ENT_QUOTES) ?>" onclick="window.openMonthlyAttendanceAuditModal('<?= $tm['id'] ?>', this.getAttribute('data-name'))" class="flex-1 py-2 px-3 text-center border border-primary/20 hover:border-primary/40 text-primary bg-primary/5 rounded-xl text-[10px] font-extrabold transition-all cursor-pointer flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-xs">calendar_month</span>
                        <span>Audit Kehadiran</span>
                    </button>
                    <a href="<?= $waLink ?>" <?= $waOnclick ?> <?= $hasPhone ? 'target="_blank"' : '' ?> class="flex-grow py-2 px-3 text-center border border-outline-variant/20 hover:border-outline-variant/40 text-on-surface-variant hover:bg-surface-container-low rounded-xl text-[10px] font-extrabold transition-all flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-xs text-green-600 font-bold">chat</span>
                        <span>Hubungi Staf</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <!-- VIEW CONTAINER: DETAILED TABLE VIEW (HIDDEN BY DEFAULT) -->
    <div class="hidden bg-surface-container-lowest border border-outline-variant/15 rounded-3xl overflow-hidden shadow-sm animate-fade-in" id="teamTableContainer">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/30 border-b border-outline-variant/10 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                        <th class="py-4 px-6">Karyawan / ID</th>
                        <th class="py-4 px-6">Posisi & Dept</th>
                        <th class="py-4 px-6">Kontak</th>
                        <th class="py-4 px-6 text-center">Sisa Cuti</th>
                        <th class="py-4 px-6 text-center">Titik WFH</th>
                        <th class="py-4 px-6">Alamat Domisili</th>
                        <th class="py-4 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10 text-xs font-semibold text-on-surface" id="teamTableTbody">
                    <?php if (empty($teamMembers)): ?>
                    <tr class="empty-table-row">
                        <td colspan="7" class="py-16 text-center text-on-surface-variant font-medium">
                            <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">group_off</span>
                            Belum ada karyawan terdaftar di divisi Anda.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($teamMembers as $tm): ?>
                        <?php 
                            $hasWfhGeofence = ($tm['home_latitude'] !== null && $tm['home_longitude'] !== null);
                            $hasPhone = !empty($tm['no_telepon']);
                            $waLink = $hasPhone ? getWhatsAppLinkTeam($tm['no_telepon']) : '#';
                            $waOnclick = $hasPhone ? '' : 'onclick="event.preventDefault(); Swal.fire({ title: \'WhatsApp Belum Setel\', text: \'Staf fungsional terkait belum mencantumkan nomor telepon aktif di profil mandiri mereka.\', icon: \'warning\', confirmButtonColor: \'#000666\' })"';
                            
                            $searchString = strtolower($tm['first_name'] . ' ' . $tm['last_name'] . ' ' . ($tm['employee_id'] ?? '') . ' ' . ($tm['job_title'] ?? $tm['role']) . ' ' . ($tm['department_name'] ?? '') . ' ' . ($tm['no_telepon'] ?? '') . ' ' . ($tm['alamat_domisili'] ?? ''));
                        ?>
                        <tr class="team-table-row hover:bg-surface-container-low/20 transition-colors" data-search="<?= htmlspecialchars($searchString) ?>">
                            <!-- Profile / Avatar / ID -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <?php $hash = md5(strtolower(trim($tm['email'] ?? ''))); ?>
                                    <img src="<?= htmlspecialchars(getGravatarTeam($tm['email'], $tm['profile_picture'])) ?>" alt="Avatar" class="w-9 h-9 rounded-full border object-cover" onerror="window.handleAvatarError(this, '<?= $hash ?>')" />
                                    <div>
                                        <h4 class="font-extrabold text-on-surface whitespace-nowrap"><?= htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name']) ?></h4>
                                        <span class="inline-block text-[9px] font-extrabold px-1.5 py-0.5 mt-0.5 rounded bg-surface-container-high text-on-surface-variant border border-outline-variant/15 tracking-wider">
                                            <?= htmlspecialchars($tm['employee_id'] ?: 'BELUM DISETEL') ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <!-- Position & Dept (Job Title explicitly visible) -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <p class="font-extrabold text-on-surface"><?= htmlspecialchars($tm['job_title'] ?: ucwords($tm['role'])) ?></p>
                                <p class="text-[10px] text-on-surface-variant mt-0.5"><?= htmlspecialchars($tm['department_name'] ?? 'Pusat') ?></p>
                            </td>
                            <!-- Contacts -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <a href="mailto:<?= htmlspecialchars($tm['email']) ?>" class="font-bold text-primary hover:underline block font-mono text-[11px]"><?= htmlspecialchars($tm['email']) ?></a>
                                <p class="text-[10px] text-on-surface-variant font-mono mt-0.5"><?= htmlspecialchars($tm['no_telepon'] ?: 'WA: Belum Diisi') ?></p>
                            </td>
                            <!-- Annual Leave Quota -->
                            <td class="py-4 px-6 text-center font-extrabold text-on-surface text-sm">
                                <?= $tm['annual_leave_quota'] ?> <span class="text-[10px] font-normal text-on-surface-variant">Hari</span>
                            </td>
                            <!-- WFH Location -->
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <?php if ($hasWfhGeofence): ?>
                                    <span class="inline-flex items-center gap-1 font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-150 rounded-lg px-2 py-0.5 text-[10px]" title="<?= $tm['home_latitude'] ?>, <?= $tm['home_longitude'] ?>">
                                        <span class="material-symbols-outlined text-xs">home_pin</span>
                                        <span>Terseting</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 border border-amber-150 rounded-lg px-2 py-0.5 text-[10px]">
                                        <span class="material-symbols-outlined text-xs">warning</span>
                                        <span>Belum Disetel</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <!-- Domisili Address -->
                            <td class="py-4 px-6 max-w-xs truncate" title="<?= htmlspecialchars($tm['alamat_domisili'] ?: '') ?>">
                                <?= htmlspecialchars($tm['alamat_domisili'] ?: 'Belum Dikonfigurasi') ?>
                            </td>
                            <!-- Actions -->
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" data-name="<?= htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name'], ENT_QUOTES) ?>" onclick="window.openMonthlyAttendanceAuditModal('<?= $tm['id'] ?>', this.getAttribute('data-name'))" class="p-1.5 text-primary bg-primary/5 hover:bg-primary/10 rounded-lg border border-primary/20 transition-colors cursor-pointer" title="Audit Presensi">
                                        <span class="material-symbols-outlined text-sm font-bold">calendar_month</span>
                                    </button>
                                    <a href="<?= $waLink ?>" <?= $waOnclick ?> <?= $hasPhone ? 'target="_blank"' : '' ?> class="p-1.5 text-green-600 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200 transition-colors flex items-center justify-center" title="Hubungi via WhatsApp">
                                        <span class="material-symbols-outlined text-sm font-bold">chat</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: DETAILED MONTHLY ATTENDANCE AUDIT FOR SPECIFIC SELECTED EMPLOYEE (1 FULL MONTH LOGS) -->
<div id="monthlyAuditModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-4xl rounded-3xl overflow-hidden shadow-2xl flex flex-col border border-outline-variant/10 animate-fade-in max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-outline-variant/15 bg-surface-container-low/10 flex-shrink-0 flex items-center justify-between">
            <div class="space-y-0.5">
                <h3 class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">calendar_month</span>
                    Audit Presensi Bulanan Staf
                </h3>
                <p class="text-[11px] text-on-surface-variant font-bold">
                    Menampilkan laporan riwayat presensi lengkap selama 1 bulan berjalan untuk karyawan: 
                    <span class="text-primary font-black" id="audit-employee-name">-</span>
                </p>
            </div>
            <button onclick="window.closeMonthlyAttendanceAuditModal()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-high cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body (Table Container) -->
        <div class="p-6 overflow-y-auto flex-grow space-y-4">
            <!-- Filter Bar for Month & Year -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-container-low/30 border border-outline-variant/10 rounded-2xl p-4 shadow-[0_4px_20px_rgba(0,6,102,0.002)]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-primary">filter_list</span>
                    <span class="text-[11px] font-extrabold text-on-surface uppercase tracking-wider">Filter Periode Audit</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <label for="audit-month-select" class="text-[10px] font-bold text-on-surface-variant uppercase">Bulan:</label>
                        <select id="audit-month-select" onchange="window.reloadMonthlyAttendanceAudit()" class="px-3 py-1.5 border border-outline-variant/30 rounded-xl bg-surface-container-lowest text-xs text-on-surface font-semibold focus:outline-none focus:border-primary transition-all cursor-pointer">
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <label for="audit-year-select" class="text-[10px] font-bold text-on-surface-variant uppercase">Tahun:</label>
                        <select id="audit-year-select" onchange="window.reloadMonthlyAttendanceAudit()" class="px-3 py-1.5 border border-outline-variant/30 rounded-xl bg-surface-container-lowest text-xs text-on-surface font-semibold focus:outline-none focus:border-primary transition-all cursor-pointer">
                            <?php 
                            $currentYear = (int)date('Y');
                            for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++): 
                            ?>
                                <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto border border-outline-variant/10 rounded-2xl">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/30 border-b border-outline-variant/10 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                            <th class="py-3.5 px-6">Hari & Tanggal</th>
                            <th class="py-3.5 px-6 text-center">Clock-In</th>
                            <th class="py-3.5 px-6 text-center">Status Masuk</th>
                            <th class="py-3.5 px-6 text-center">Clock-Out</th>
                            <th class="py-3.5 px-6 text-center">Status Pulang</th>
                            <th class="py-3.5 px-6 text-center">Jam Kerja</th>
                            <th class="py-3.5 px-6 text-center">Mode Masuk</th>
                            <th class="py-3.5 px-6 text-center">Mode Pulang</th>
                            <th class="py-3.5 px-6 text-center">Metode</th>
                            <th class="py-3.5 px-6 text-center">IP &amp; Lokasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 text-xs font-semibold text-on-surface" id="audit-modal-tbody">
                        <!-- Loaded dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex justify-end bg-surface-container-low/10 flex-shrink-0">
            <button onclick="window.closeMonthlyAttendanceAuditModal()" class="px-5 py-2.5 bg-surface-container-high hover:bg-surface-container-high/80 text-on-surface-variant font-bold text-xs rounded-xl transition-all cursor-pointer">
                Tutup Laporan
            </button>
        </div>
    </div>
</div>

<script>
    // View Switcher (Grid vs Table)
    window.toggleTeamView = function(mode) {
        const gridContainer = document.getElementById('teamGridContainer');
        const tableContainer = document.getElementById('teamTableContainer');
        
        const btnGrid = document.getElementById('viewBtnGrid');
        const btnTable = document.getElementById('viewBtnTable');

        if (mode === 'grid') {
            gridContainer.classList.remove('hidden');
            tableContainer.classList.add('hidden');

            btnGrid.className = "flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer transition-all bg-primary text-white shadow-sm";
            btnTable.className = "flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer transition-all text-on-surface-variant hover:bg-surface-container-high/50";
        } else {
            gridContainer.classList.add('hidden');
            tableContainer.classList.remove('hidden');

            btnTable.className = "flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer transition-all bg-primary text-white shadow-sm";
            btnGrid.className = "flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer transition-all text-on-surface-variant hover:bg-surface-container-high/50";
        }
    }

    // Instant Zero-Latency Live-Search Filter for BOTH Grid & Table View
    window.filterTeamMembers = function() {
        const query = document.getElementById('teamSearchInput').value.trim().toLowerCase();
        
        // 1. Filter Grid Cards
        const cards = document.querySelectorAll('.team-card');
        let cardVisibleCount = 0;
        cards.forEach(card => {
            const searchData = card.getAttribute('data-search') || '';
            if (searchData.includes(query)) {
                card.style.display = 'flex';
                cardVisibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // 2. Filter Table Rows
        const rows = document.querySelectorAll('.team-table-row');
        let rowVisibleCount = 0;
        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            if (searchData.includes(query)) {
                row.style.display = 'table-row';
                rowVisibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle Empty Search States for both Grid and Table
        toggleEmptyState(cardVisibleCount, cards.length, 'grid');
        toggleEmptyState(rowVisibleCount, rows.length, 'table');
    }

    function toggleEmptyState(visibleCount, totalCount, mode) {
        let container = mode === 'grid' ? document.getElementById('teamGridContainer') : document.getElementById('teamTableTbody');
        let emptyStateId = `searchEmptyState-${mode}`;
        let emptyState = document.getElementById(emptyStateId);

        if (visibleCount === 0 && totalCount > 0) {
            if (!emptyState) {
                if (mode === 'grid') {
                    emptyState = document.createElement('div');
                    emptyState.id = emptyStateId;
                    emptyState.className = 'col-span-full bg-surface-container-lowest border border-outline-variant/15 rounded-3xl py-12 px-6 text-center shadow-sm animate-fade-in';
                    emptyState.innerHTML = `
                        <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">person_search</span>
                        <h3 class="text-on-surface font-extrabold text-sm">Tidak Ada Kecocokan Anggota</h3>
                        <p class="text-on-surface-variant/70 text-xs mt-1">Tidak ada anggota tim yang cocok dengan kata kunci pencarian Anda.</p>
                    `;
                    container.appendChild(emptyState);
                } else {
                    emptyState = document.createElement('tr');
                    emptyState.id = emptyStateId;
                    emptyState.className = 'empty-table-row animate-fade-in';
                    emptyState.innerHTML = `
                        <td colspan="7" class="py-12 text-center text-on-surface-variant font-medium">
                            <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">person_search</span>
                            <h3 class="text-on-surface font-extrabold text-sm">Tidak Ada Kecocokan Anggota</h3>
                            <p class="text-on-surface-variant/70 text-xs mt-1">Tidak ada anggota tim yang cocok dengan kata kunci pencarian Anda.</p>
                        </td>
                    `;
                    container.appendChild(emptyState);
                }
            }
        } else if (emptyState) {
            emptyState.remove();
        }
    }

    // JS Modal Helpers for Monthly Attendance Audit Modal (Exposed globally to window)
    window.openMonthlyAttendanceAuditModal = function(userId, fullName) {
        console.log("openMonthlyAttendanceAuditModal triggered for:", fullName, "(ID:", userId, ")");
        if (!userId) {
            console.error("openMonthlyAttendanceAuditModal: userId is empty!");
            Swal.fire('Error', 'ID Staf tidak valid.', 'error');
            return;
        }
        document.getElementById('audit-employee-name').innerText = fullName;
        window.currentAuditEmployeeName = fullName;
        
        // Save current userId to a window variable
        window.currentAuditUserId = userId;
        
        // Reset selectors to current month and year
        const now = new Date();
        document.getElementById('audit-month-select').value = now.getMonth() + 1;
        document.getElementById('audit-year-select').value = now.getFullYear();

        window.reloadMonthlyAttendanceAudit();
    }

    window.reloadMonthlyAttendanceAudit = function() {
        const userId = window.currentAuditUserId;
        if (!userId) return;

        const month = document.getElementById('audit-month-select').value;
        const year = document.getElementById('audit-year-select').value;

        window.mapConfigs = [];

        const tbody = document.getElementById('audit-modal-tbody');
        tbody.innerHTML = `<tr><td colspan="10" class="py-8 px-6 text-center text-on-surface-variant font-medium">Sedang memuat riwayat presensi...</td></tr>`;
        document.getElementById('monthlyAuditModal').classList.remove('hidden');

        fetch(`/manager/attendance/member_monthly?user_id=${userId}&month=${month}&year=${year}`)
        .then(res => {
            console.log("Fetch response received, status code:", res.status);
            return res.json();
        })
        .then(res => {
            console.log("Parsed JSON response:", res);
            if (res.success) {
                const cfg = res.settings || {};
                if (!res.data || res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="10" class="py-12 px-6 text-center text-on-surface-variant font-medium">Belum ada riwayat kehadiran tercatat untuk staf ini di periode terpilih.</td></tr>`;
                    return;
                }

                let html = '';
                res.data.forEach(r => {
                    const clockInStr = r.clock_in ? r.clock_in.substring(0, 5) : '--:--';
                    const clockOutStr = r.clock_out ? r.clock_out.substring(0, 5) : '--:--';

                    // Clock-in status badge
                    const statusClass = getAttendanceStatusBadgeClass(r.status);
                    const statusDot = getAttendanceStatusBadgeDot(r.status);
                    const statusLabel = getAttendanceStatusBadgeLabel(r.status);
                    const statusClockInHtml = `
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${statusClass}">
                            <span class="w-1.5 h-1.5 rounded-full ${statusDot}"></span>
                            ${statusLabel}
                        </span>
                    `;

                    // Clock-out status badge
                    let statusClockOutHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.clock_out || r.clock_out_status === 'tidak presensi pulang') {
                        const statusOutClass = getClockOutStatusBadgeClass(r.clock_out_status);
                        const statusOutDot = getClockOutStatusBadgeDot(r.clock_out_status);
                        const statusOutLabel = getClockOutStatusBadgeLabel(r.clock_out_status);
                        statusClockOutHtml = `
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${statusOutClass}">
                                <span class="w-1.5 h-1.5 rounded-full ${statusOutDot}"></span>
                                ${statusOutLabel}
                            </span>
                        `;
                    }

                    // Jam Kerja
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

                    // Mode Masuk Badge
                    let modeMasukHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.work_mode) {
                        let color = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        let icon = 'business';
                        let text = 'WFO';
                        if (r.work_mode === 'WFA') {
                            color = 'bg-blue-50 text-blue-700 border-blue-200';
                            icon = 'home_work';
                            text = 'WFA';
                        } else if (r.work_mode === 'WFH') {
                            color = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            icon = 'home';
                            text = 'WFH';
                        }
                        modeMasukHtml = `
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold border ${color}">
                                <span class="material-symbols-outlined text-[10px]">${icon}</span>
                                ${text}
                            </span>
                        `;
                    }

                    // Mode Pulang Badge
                    let modePulangHtml = '<span class="text-on-surface-variant/30 text-xs">—</span>';
                    if (r.work_mode_out) {
                        let colorOut = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        let iconOut = 'business';
                        let textOut = 'WFO';
                        if (r.work_mode_out === 'WFA') {
                            colorOut = 'bg-blue-50 text-blue-700 border-blue-200';
                            iconOut = 'home_work';
                            textOut = 'WFA';
                        } else if (r.work_mode_out === 'WFH') {
                            colorOut = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            iconOut = 'home';
                            textOut = 'WFH';
                        }
                        modePulangHtml = `
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold border ${colorOut}">
                                <span class="material-symbols-outlined text-[10px]">${iconOut}</span>
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
                            <span class="inline-flex items-center gap-1 text-[10px] font-extrabold ${colorClass}">
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
                            details += `<div class="text-[9px] text-on-surface-variant/60 font-mono">IP: ${escapeHtml(r.ip_address)}</div>`;
                        }
                        if (r.clock_in_latitude) {
                            details += `<div class="text-[9px] text-on-surface-variant/50 font-mono mt-0.5" title="Koordinat Masuk">In: ${parseFloat(r.clock_in_latitude).toFixed(4)}, ${parseFloat(r.clock_in_longitude).toFixed(4)}</div>`;
                        }
                        if (r.clock_out_latitude) {
                            details += `<div class="text-[9px] text-on-surface-variant/50 font-mono mt-0.5" title="Koordinat Pulang">Out: ${parseFloat(r.clock_out_latitude).toFixed(4)}, ${parseFloat(r.clock_out_longitude).toFixed(4)}</div>`;
                        }
                        if (r.clock_in_latitude || r.clock_out_latitude) {
                            const empName = window.currentAuditEmployeeName || 'Staf';
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

                    html += `
                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                            <td class="py-3 px-6 font-bold whitespace-nowrap">${formatJsDateIndo(r.attendance_date)}</td>
                            <td class="py-3 px-6 text-center font-mono text-sm">${clockInStr}</td>
                            <td class="py-3 px-6 text-center whitespace-nowrap">${statusClockInHtml}</td>
                            <td class="py-3 px-6 text-center font-mono text-sm">${clockOutStr}</td>
                            <td class="py-3 px-6 text-center whitespace-nowrap">${statusClockOutHtml}</td>
                            <td class="py-3 px-6 text-center whitespace-nowrap font-bold">${workingHours}</td>
                            <td class="py-3 px-6 text-center whitespace-nowrap">${modeMasukHtml}</td>
                            <td class="py-3 px-6 text-center whitespace-nowrap">${modePulangHtml}</td>
                            <td class="py-3 px-6 text-center whitespace-nowrap">${metodeHtml}</td>
                            <td class="py-3 px-6 text-center whitespace-nowrap">${lokasiHtml}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `<tr><td colspan="10" class="py-8 px-6 text-center text-red-600 font-bold">${res.message}</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="10" class="py-8 px-6 text-center text-red-600 font-bold">Terjadi kendala saat memuat data.</td></tr>`;
        });
    }

    window.closeMonthlyAttendanceAuditModal = function() {
        document.getElementById('monthlyAuditModal').classList.add('hidden');
    }

    // JS Helpers for Status badging in Modal table
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

    function formatJsDateIndo(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length < 3) return dateStr;
        
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        
        const dateObj = new Date(year, month, day);
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        
        const dayName = days[dateObj.getDay()] || '';
        const dayNum = dateObj.getDate() || day;
        const monthName = months[dateObj.getMonth()] || '';
        const yearNum = dateObj.getFullYear() || year;
        
        return `${dayName}, ${dayNum} ${monthName} ${yearNum}`;
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
</script>
