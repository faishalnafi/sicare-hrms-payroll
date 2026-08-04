<?php
use App\Config\Database;
use App\Services\ViewHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? null;
$userEmail = strtolower($_SESSION['email'] ?? '');
$userJob = strtolower($_SESSION['job_title'] ?? '');
$userRole = $_SESSION['role'] ?? '';
$isSuperAdmin = ($userRole === 'superadmin');

// Identify C-Level role
$isCEO = (str_contains($userEmail, 'ceo') || str_contains($userJob, 'ceo') || str_contains($userJob, 'chief executive'));
$isCFO = (str_contains($userEmail, 'cfo') || str_contains($userJob, 'cfo') || str_contains($userJob, 'chief financial'));
$isCOO = (str_contains($userEmail, 'coo') || str_contains($userJob, 'coo') || str_contains($userJob, 'chief operating'));
$isDeptCLevel = (!$isCEO && !$isCFO && !$isCOO);

// Fetch all requisitions
$stmtReqs = $db->query("
    SELECT r.*, d.name AS dept_name, u.first_name, u.last_name, u.email AS requester_email
    FROM job_requisitions r
    LEFT JOIN departments d ON r.department_id = d.id
    LEFT JOIN users u ON r.requested_by = u.id
    ORDER BY r.created_at DESC
");
$requisitions = $stmtReqs->fetchAll(PDO::FETCH_ASSOC);

// Department C-Level (e.g. CBO, CTO, COO) should ONLY see requisitions for their own department/subdepartments
$userDeptId = $_SESSION['department_id'] ?? null;
if ($isDeptCLevel && !$isSuperAdmin && !empty($userDeptId)) {
    $allowedDeptIds = [$userDeptId];
    $stmtSub = $db->prepare("SELECT id FROM departments WHERE parent_id = :p_id");
    $stmtSub->execute(['p_id' => $userDeptId]);
    $subDepts = $stmtSub->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($subDepts)) {
        $allowedDeptIds = array_merge($allowedDeptIds, $subDepts);
    }
    
    $requisitions = array_filter($requisitions, function($r) use ($allowedDeptIds) {
        return in_array($r['department_id'], $allowedDeptIds);
    });
    $requisitions = array_values($requisitions);
}

// Counts by stage
$countLevel1 = 0;
$countLevel2 = 0;
$countLevel3 = 0;
$countApproved = 0;
$countRejected = 0;

foreach ($requisitions as $r) {
    if ($r['status'] === 'pending_dept_clevel') $countLevel1++;
    elseif ($r['status'] === 'pending_cfo_chro') $countLevel2++;
    elseif ($r['status'] === 'pending_ceo') $countLevel3++;
    elseif (in_array($r['status'], ['approved', 'published'])) $countApproved++;
    elseif ($r['status'] === 'rejected') $countRejected++;
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Persetujuan FPTK (C-Level Suite)</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Persetujuan berjenjang penambahan karyawan baru oleh C-Level Divisi, CFO, COO, dan CEO (Strict Sequential Governance).</p>
        </div>
        <div class="flex items-center gap-2 bg-surface-container-low px-3 py-1.5 rounded-xl border border-outline-variant/15 text-xs font-bold text-primary">
            <span class="material-symbols-outlined text-[18px]">verified_user</span>
            <span>Role Anda: <?= $isSuperAdmin ? 'Super Admin' : ($isCEO ? 'CEO' : ($isCFO ? 'CFO' : ($isCOO ? 'COO' : 'C-Level Divisi'))) ?></span>
        </div>
    </div>

    <!-- Stepper Legend Banner -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/15 shadow-sm space-y-2">
        <span class="text-xs font-bold text-on-surface block">Alur Persetujuan Berjenjang Tanpa Bypass (Sequential Approval Chain):</span>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 text-xs">
            <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-2">
                <span class="font-black text-amber-700">1</span>
                <div>
                    <span class="font-bold block">Level 1: C-Level Divisi</span>
                    <span class="text-[10px] text-amber-800">Wajib diawali C-Level Divisi</span>
                </div>
            </div>
            <div class="p-2.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 flex items-center gap-2">
                <span class="font-black text-blue-700">2</span>
                <div>
                    <span class="font-bold block">Level 2: CFO & COO</span>
                    <span class="text-[10px] text-blue-800">Paralel (Keduanya wajib)</span>
                </div>
            </div>
            <div class="p-2.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 flex items-center gap-2">
                <span class="font-black text-purple-700">3</span>
                <div>
                    <span class="font-bold block">Level 3: CEO Final</span>
                    <span class="text-[10px] text-purple-800">Approval Akhir CEO</span>
                </div>
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center gap-2">
                <span class="font-black text-emerald-700">4</span>
                <div>
                    <span class="font-bold block">Level 4: Recruiter</span>
                    <span class="text-[10px] text-emerald-800">Publikasi Lowongan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-xs">
        <div class="bg-surface-container-lowest p-3.5 rounded-xl border border-outline-variant/15 shadow-sm">
            <span class="text-on-surface-variant font-bold block text-[10px] uppercase">Level 1 (Dept C-Level)</span>
            <span class="text-xl font-extrabold text-amber-700"><?= $countLevel1 ?></span>
        </div>
        <div class="bg-surface-container-lowest p-3.5 rounded-xl border border-outline-variant/15 shadow-sm">
            <span class="text-on-surface-variant font-bold block text-[10px] uppercase">Level 2 (CFO & COO)</span>
            <span class="text-xl font-extrabold text-blue-700"><?= $countLevel2 ?></span>
        </div>
        <div class="bg-surface-container-lowest p-3.5 rounded-xl border border-outline-variant/15 shadow-sm">
            <span class="text-on-surface-variant font-bold block text-[10px] uppercase">Level 3 (CEO Final)</span>
            <span class="text-xl font-extrabold text-purple-700"><?= $countLevel3 ?></span>
        </div>
        <div class="bg-surface-container-lowest p-3.5 rounded-xl border border-outline-variant/15 shadow-sm">
            <span class="text-on-surface-variant font-bold block text-[10px] uppercase">Disetujui / Rilis</span>
            <span class="text-xl font-extrabold text-emerald-700"><?= $countApproved ?></span>
        </div>
        <div class="bg-surface-container-lowest p-3.5 rounded-xl border border-outline-variant/15 shadow-sm">
            <span class="text-on-surface-variant font-bold block text-[10px] uppercase">Ditolak</span>
            <span class="text-xl font-extrabold text-red-700"><?= $countRejected ?></span>
        </div>
    </div>

    <!-- Requisitions Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-outline-variant/15 flex justify-between items-center">
            <h3 class="font-bold text-sm text-on-surface">Daftar Pengajuan FPTK Masuk</h3>
            <span class="text-xs text-on-surface-variant font-medium">Total <?= count($requisitions) ?> Pengajuan</span>
        </div>

        <?php if (empty($requisitions)): ?>
            <div class="p-12 text-center text-on-surface-variant">Belum ada data pengajuan.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-on-surface-variant font-bold border-b border-outline-variant/15 uppercase">
                            <th class="py-3 px-4">No. FPTK</th>
                            <th class="py-3 px-4">Posisi & Pemohon</th>
                            <th class="py-3 px-4">Headcount & Gaji</th>
                            <th class="py-3 px-4">Progress Approval Berjenjang</th>
                            <th class="py-3 px-4 text-right">Aksi C-Level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 font-medium">
                        <?php foreach ($requisitions as $r): ?>
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="py-3.5 px-4 font-bold text-primary">
                                    <?= htmlspecialchars($r['req_number']) ?>
                                    <span class="block text-[10px] text-on-surface-variant font-normal"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= htmlspecialchars($r['title']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($r['dept_name'] ?? 'Umum') ?> (Oleh: <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>)</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface"><?= (int)$r['headcount'] ?> Orang</span> (<?= str_replace('_', ' ', $r['employment_type']) ?>)
                                    <span class="block text-[10px] text-on-surface-variant font-mono">
                                        Rp<?= number_format($r['min_salary'], 0, ',', '.') ?> - Rp<?= number_format($r['max_salary'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1 text-[10px]">
                                            <span class="px-2 py-0.5 rounded font-bold <?= !empty($r['dept_clevel_approved_at']) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_dept_clevel' ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-400' : 'bg-gray-100 text-gray-400') ?>">
                                                1. Dept C-Level <?= !empty($r['dept_clevel_approved_at']) ? '✓' : '' ?>
                                            </span>
                                            →
                                            <span class="px-2 py-0.5 rounded font-bold <?= (!empty($r['cfo_approved_at']) && !empty($r['chro_approved_at'])) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_cfo_chro' ? 'bg-blue-100 text-blue-800 ring-1 ring-blue-400' : 'bg-gray-100 text-gray-400') ?>">
                                                2. CFO (<?= !empty($r['cfo_approved_at']) ? '✓' : '⌛' ?>) & COO (<?= !empty($r['chro_approved_at']) ? '✓' : '⌛' ?>)
                                            </span>
                                            →
                                            <span class="px-2 py-0.5 rounded font-bold <?= !empty($r['ceo_approved_at']) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_ceo' ? 'bg-purple-100 text-purple-800 ring-1 ring-purple-400' : 'bg-gray-100 text-gray-400') ?>">
                                                3. CEO <?= !empty($r['ceo_approved_at']) ? '✓' : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1">
                                    <?php if ($r['status'] === 'rejected'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Ditolak</span>
                                    <?php elseif (in_array($r['status'], ['approved', 'published'])): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Disetujui</span>
                                    <?php else: ?>
                                        <!-- Strict Sequential Action Buttons -->
                                        <?php if ($r['status'] === 'pending_dept_clevel'): ?>
                                            <?php 
                                                $userDeptId = $_SESSION['department_id'] ?? null;
                                                $isMyDept = false;
                                                if (!empty($userDeptId) && !empty($r['department_id'])) {
                                                    if ($userDeptId === $r['department_id']) {
                                                        $isMyDept = true;
                                                    } else {
                                                        $stmtCheckP = $db->prepare("SELECT parent_id FROM departments WHERE id = :id LIMIT 1");
                                                        $stmtCheckP->execute(['id' => $r['department_id']]);
                                                        $pId = $stmtCheckP->fetchColumn();
                                                        if ($pId === $userDeptId) {
                                                            $isMyDept = true;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <?php if ($isSuperAdmin || ($isDeptCLevel && $isMyDept)): ?>
                                                <button type="button" onclick="execApprove('<?= $r['id'] ?>', 'dept_clevel')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-3 py-1 rounded-lg cursor-pointer flex-inline items-center gap-1">
                                                    Setujui
                                                </button>
                                                <button type="button" onclick="execReject('<?= $r['id'] ?>')" class="bg-red-600 hover:bg-red-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg cursor-pointer">Tolak</button>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Menunggu persetujuan C-Level Divisi <?= htmlspecialchars($r['dept_name'] ?? '') ?>">
                                                    <span class="material-symbols-outlined text-[12px]">hourglass_top</span> Menunggu C-Level Divisi (<?= htmlspecialchars($r['dept_name'] ?? 'Terkait') ?>)
                                                </span>
                                            <?php endif; ?>

                                        <?php elseif ($r['status'] === 'pending_cfo_chro'): ?>
                                            <?php 
                                                $cfoApproved = !empty($r['cfo_approved_at']);
                                                $cooApproved = !empty($r['chro_approved_at']);
                                            ?>
                                            <div class="inline-flex items-center gap-1.5 justify-end">
                                                <?php if ($isCFO || $isSuperAdmin): ?>
                                                    <?php if (!$cfoApproved): ?>
                                                        <button type="button" onclick="execApprove('<?= $r['id'] ?>', 'cfo')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg cursor-pointer">
                                                            Setujui (CFO)
                                                        </button>
                                                        <button type="button" onclick="execReject('<?= $r['id'] ?>')" class="bg-red-600 hover:bg-red-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg cursor-pointer">Tolak</button>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                            <span class="material-symbols-outlined text-[12px]">check_circle</span> CFO Setuju
                                                        </span>
                                                        <button type="button" onclick="execRevoke('<?= $r['id'] ?>', 'cfo')" class="inline-flex items-center gap-1 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 font-bold text-[10px] px-2 py-1 rounded-lg cursor-pointer transition-colors" title="Batalkan Persetujuan Saya">
                                                            <span class="material-symbols-outlined text-[12px]">cancel</span> Batalkan
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if ($isCOO || $isSuperAdmin): ?>
                                                    <?php if (!$cooApproved): ?>
                                                        <button type="button" onclick="execApprove('<?= $r['id'] ?>', 'coo')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg cursor-pointer">
                                                            Setujui (COO)
                                                        </button>
                                                        <?php if (!$isCFO || $cfoApproved): ?>
                                                            <button type="button" onclick="execReject('<?= $r['id'] ?>')" class="bg-red-600 hover:bg-red-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg cursor-pointer">Tolak</button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                            <span class="material-symbols-outlined text-[12px]">check_circle</span> COO Setuju
                                                        </span>
                                                        <button type="button" onclick="execRevoke('<?= $r['id'] ?>', 'coo')" class="inline-flex items-center gap-1 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 font-bold text-[10px] px-2 py-1 rounded-lg cursor-pointer transition-colors" title="Batalkan Persetujuan Saya">
                                                            <span class="material-symbols-outlined text-[12px]">cancel</span> Batalkan
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (!$isCFO && !$isCOO && !$isSuperAdmin): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200" title="Menunggu persetujuan CFO & COO">
                                                        <span class="material-symbols-outlined text-[12px]">hourglass_top</span> Menunggu CFO & COO
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                        <?php elseif ($r['status'] === 'pending_ceo'): ?>
                                            <?php if ($isCEO || $isSuperAdmin): ?>
                                                <button type="button" onclick="execApprove('<?= $r['id'] ?>', 'ceo')" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-[11px] px-3 py-1 rounded-lg cursor-pointer">
                                                    Setujui (CEO)
                                                </button>
                                                <button type="button" onclick="execReject('<?= $r['id'] ?>')" class="bg-red-600 hover:bg-red-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg cursor-pointer">Tolak</button>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-800 border border-purple-200" title="Menunggu persetujuan final CEO">
                                                    <span class="material-symbols-outlined text-[12px]">hourglass_top</span> Menunggu CEO Final
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function execApprove(reqId, approverType) {
        Swal.fire({
            title: 'Konfirmasi Persetujuan C-Level',
            text: 'Apakah Anda yakin ingin menyetujui pengajuan permintaan tenaga kerja ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Setujui!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('requisition_id', reqId);
                formData.append('approver_type', approverType);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                Swal.fire({ title: 'Memproses Persetujuan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                fetch('/requisitions/approve', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => {
                            if (window.loadPage) window.loadPage('/executive/requisitions');
                            else window.location.reload();
                        });
                    } else {
                        Swal.fire('Gagal (Strict Governance)', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Gagal memproses persetujuan.', 'error'));
            }
        });
    }

    function execReject(reqId) {
        Swal.fire({
            title: 'Tolak Permintaan FPTK?',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan',
            inputPlaceholder: 'Tuliskan alasan penolakan permintaan ini...',
            inputValidator: (value) => {
                if (!value) return 'Alasan penolakan wajib diisi!';
            },
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            confirmButtonText: 'Ya, Tolak Permintaan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('requisition_id', reqId);
                formData.append('rejection_reason', result.value);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                Swal.fire({ title: 'Memproses Penolakan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                fetch('/requisitions/reject', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil Ditolak', data.message, 'success').then(() => {
                            if (window.loadPage) window.loadPage('/executive/requisitions');
                            else window.location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Gagal memproses penolakan.', 'error'));
            }
        });
    }

    function execRevoke(reqId, approverType) {
        Swal.fire({
            title: 'Batalkan Persetujuan?',
            text: 'Apakah Anda yakin ingin membatalkan persetujuan Anda? Status akan dikembalikan ke pending.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            confirmButtonText: 'Ya, Batalkan Persetujuan',
            cancelButtonText: 'Kembali'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('requisition_id', reqId);
                formData.append('approver_type', approverType);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                Swal.fire({ title: 'Memproses Pembatalan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                fetch('/requisitions/revoke', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil Dibatalkan', data.message, 'success').then(() => {
                            if (window.loadPage) window.loadPage('/executive/requisitions');
                            else window.location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Gagal memproses pembatalan persetujuan.', 'error'));
            }
        });
    }

    window.execApprove = execApprove;
    window.execReject = execReject;
    window.execRevoke = execRevoke;
</script>
