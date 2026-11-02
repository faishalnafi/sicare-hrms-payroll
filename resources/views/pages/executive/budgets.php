<?php
use App\Services\ViewHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$deptFilter = $_GET['dept_id'] ?? 'all';
$budgetData = ViewHelper::getExecutiveBudgetMatrix($deptFilter);
$items = $budgetData['items'] ?? [];
$summary = $budgetData['summary'] ?? [];

$userRole = $_SESSION['role'] ?? '';
$userJob = strtolower($_SESSION['job_title'] ?? '');
$userEmail = strtolower($_SESSION['email'] ?? '');
$isSuperAdmin = ($userRole === 'superadmin');

$isCFO = ($isSuperAdmin || str_contains($userEmail, 'cfo') || str_contains($userJob, 'cfo') || str_contains($userJob, 'chief financial'));
$isCEO = ($isSuperAdmin || str_contains($userEmail, 'ceo') || str_contains($userJob, 'ceo') || str_contains($userJob, 'chief executive'));

$approverType = 'dept_clevel';
if ($isCEO) {
    $approverType = 'ceo';
} elseif ($isCFO) {
    $approverType = 'cfo';
}
?>

<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-outline-variant/30 pb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 bg-primary/10 text-primary rounded-xl material-symbols-outlined text-2xl">request_quote</span>
                <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Persetujuan Anggaran SDM (Budget Matrix)</h1>
            </div>
            <p class="text-on-surface-variant font-medium text-sm mt-1">
                Tinjau analisis dampak biaya rekrutmen (*Cost Impact Analysis*) dan berikan pengesahan alokasi anggaran penambahan personel skala divisional.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/60">
                <span class="material-symbols-outlined text-base">verified_user</span>
                Otoritas Akses: <?= strtoupper($approverType) ?> Sign-Off
            </span>
        </div>
    </div>

    <!-- Summary KPI Financial Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-amber-800 uppercase tracking-wider block mb-1">Anggaran Menunggu Approval</span>
                <span class="text-2xl font-black text-amber-700">Rp <?= number_format($summary['pending_annual_budget'] ?? 0, 0, ',', '.') ?></span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5"><?= (int)($summary['pending_headcount'] ?? 0) ?> Headcount diajukan</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">pending_actions</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-emerald-200/60 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block mb-1">Anggaran Disetujui (Approved)</span>
                <span class="text-2xl font-black text-emerald-700">Rp <?= number_format($summary['approved_annual_budget'] ?? 0, 0, ',', '.') ?></span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5"><?= (int)($summary['approved_headcount'] ?? 0) ?> Personel telah disetujui</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-blue-200/60 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider block mb-1">Rata-Rata Biaya / Personel</span>
                <?php
                $totalHc = (int)($summary['pending_headcount'] ?? 0) + (int)($summary['approved_headcount'] ?? 0);
                $totalBud = (float)($summary['pending_annual_budget'] ?? 0) + (float)($summary['approved_annual_budget'] ?? 0);
                $avgCost = $totalHc > 0 ? round($totalBud / $totalHc) : 0;
                ?>
                <span class="text-2xl font-black text-blue-700">Rp <?= number_format($avgCost, 0, ',', '.') ?></span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5">Estimasi beban gaji tahunan</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">calculate</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-purple-200/60 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-purple-800 uppercase tracking-wider block mb-1">Total Permintaan Anggaran</span>
                <span class="text-2xl font-black text-purple-700"><?= count($items) ?> Berkas</span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5">FPTK lintas divisi</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
        </div>
    </div>

    <!-- Department Budget Allocation Breakdown -->
    <?php if (!empty($summary['dept_costs'])): ?>
        <div class="bg-white p-6 rounded-2xl border border-outline-variant/30 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">pie_chart</span>
                    <span>Proyeksi Distribusi Alokasi Anggaran Menurut Departemen</span>
                </h3>
                <span class="text-xs text-on-surface-variant">Beban Anggaran Tahunan</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php
                $maxDeptCost = max(array_column($summary['dept_costs'], 'total_cost')) ?: 1;
                foreach ($summary['dept_costs'] as $dName => $dInfo):
                    $pct = round(($dInfo['total_cost'] / $maxDeptCost) * 100);
                ?>
                    <div class="p-3.5 bg-surface-container-lowest rounded-xl border border-outline-variant/20 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-on-surface"><?= htmlspecialchars($dName) ?></span>
                            <span class="font-semibold text-primary"><?= (int)$dInfo['headcount'] ?> Staf</span>
                        </div>
                        <div class="w-full bg-outline-variant/20 rounded-full h-2 overflow-hidden">
                            <div class="bg-primary h-2 rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="text-right text-[11px] font-bold text-on-surface-variant">
                            Rp <?= number_format($dInfo['total_cost'], 0, ',', '.') ?> / tahun
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Budget Matrix Table -->
    <div class="bg-white rounded-2xl border border-outline-variant/30 shadow-sm overflow-hidden space-y-4 p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-base font-bold text-on-surface">Matriks Permohonan Anggaran & Rekomendasi C-Level</h3>
                <p class="text-xs text-on-surface-variant">Persetujuan di bawah ini akan mengikat alokasi pagu anggaran tahunan perusahaan.</p>
            </div>
        </div>

        <?php if (empty($items)): ?>
            <div class="py-12 text-center text-on-surface-variant text-sm">
                <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">savings</span>
                Belum ada berkas permohonan anggaran FPTK yang diajukan.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container-lowest text-on-surface-variant font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">No. FPTK & Posisi</th>
                            <th class="py-3 px-4">Departemen</th>
                            <th class="py-3 px-4 text-center">Headcount</th>
                            <th class="py-3 px-4">Plafon Gaji / Bln</th>
                            <th class="py-3 px-4">Estimasi Beban / Thn</th>
                            <th class="py-3 px-4">Status Persetujuan</th>
                            <th class="py-3 px-4 text-center">Aksi Anggaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $isPending = str_starts_with($item['status'], 'pending');
                            $isApproved = in_array($item['status'], ['approved', 'published']);
                            $isRejected = ($item['status'] === 'rejected');

                            $statusBadge = 'bg-gray-100 text-gray-700 border-gray-200';
                            $statusTxt = ucwords(str_replace('_', ' ', $item['status']));
                            if ($isPending) {
                                $statusBadge = 'bg-amber-50 text-amber-700 border-amber-200/60';
                                if ($item['status'] === 'pending_dept_clevel') $statusTxt = 'Menunggu Dept Head';
                                if ($item['status'] === 'pending_cfo_chro') $statusTxt = 'Menunggu CFO / CHRO';
                                if ($item['status'] === 'pending_ceo') $statusTxt = 'Menunggu CEO Final';
                            } elseif ($isApproved) {
                                $statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200/60';
                                $statusTxt = 'Anggaran Disetujui';
                            } elseif ($isRejected) {
                                $statusBadge = 'bg-red-50 text-red-700 border-red-200/60';
                                $statusTxt = 'Anggaran Ditolak';
                            }
                            ?>
                            <tr class="hover:bg-surface-container-lowest/50 transition">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-on-surface"><?= htmlspecialchars($item['title']) ?></div>
                                    <div class="font-mono text-[10px] text-on-surface-variant">#<?= htmlspecialchars($item['req_number']) ?></div>
                                </td>
                                <td class="py-4 px-4 text-on-surface font-medium">
                                    <?= htmlspecialchars($item['department_name'] ?? '-') ?>
                                </td>
                                <td class="py-4 px-4 text-center font-bold text-primary">
                                    <?= (int)$item['headcount'] ?> Staf
                                </td>
                                <td class="py-4 px-4 text-on-surface font-semibold whitespace-nowrap">
                                    Rp <?= number_format($item['max_salary'], 0, ',', '.') ?>
                                </td>
                                <td class="py-4 px-4 font-extrabold text-emerald-700 whitespace-nowrap">
                                    Rp <?= number_format($item['estimated_annual_cost'], 0, ',', '.') ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold border <?= $statusBadge ?>">
                                        <?= $statusTxt ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <?php if ($isPending): ?>
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button onclick="approveBudget('<?= $item['id'] ?>', '<?= htmlspecialchars(addslashes($item['title'])) ?>', '<?= $approverType ?>')" class="p-1.5 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition" title="Setujui Anggaran">
                                                <span class="material-symbols-outlined text-sm font-bold">check</span>
                                            </button>
                                            <button onclick="rejectBudget('<?= $item['id'] ?>', '<?= htmlspecialchars(addslashes($item['title'])) ?>')" class="p-1.5 text-red-700 bg-red-50 hover:bg-red-100 rounded-lg border border-red-200 transition" title="Tolak Anggaran">
                                                <span class="material-symbols-outlined text-sm font-bold">close</span>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[10px] uppercase font-bold text-on-surface-variant/60">Terselesaikan</span>
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
    function approveBudget(reqId, title, approverType) {
        Swal.fire({
            title: 'Setujui Alokasi Anggaran?',
            text: `Anda akan mengesahkan alokasi pagu anggaran untuk posisi "${title}" sebagai ${approverType.toUpperCase()}.`,
            icon: 'question',
            input: 'textarea',
            inputPlaceholder: 'Catatan anggaran eksekutif (opsional)...',
            showCancelButton: true,
            confirmButtonColor: '#1b5e20',
            cancelButtonColor: '#757575',
            confirmButtonText: 'Ya, Sahkan Anggaran',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                Swal.showLoading();
                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
                formData.append('requisition_id', reqId);
                formData.append('approver_type', approverType);
                formData.append('notes', result.value || 'Anggaran disetujui');

                fetch('/executive/requisitions/approve', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Anggaran Disetujui!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
                    }
                })
                .catch(err => {
                    Swal.fire({ title: 'Kesalahan Sistem', text: err.message, icon: 'error' });
                });
            }
        });
    }

    function rejectBudget(reqId, title) {
        Swal.fire({
            title: 'Tolak Permohonan Anggaran?',
            text: `Harap berikan alasan penolakan finansial untuk posisi "${title}":`,
            input: 'textarea',
            inputPlaceholder: 'Tuliskan alasan penolakan anggaran...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d32f2f',
            cancelButtonColor: '#757575',
            confirmButtonText: 'Ya, Tolak Anggaran',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan anggaran wajib diisi!';
                }
            }
        }).then(result => {
            if (result.isConfirmed) {
                Swal.showLoading();
                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
                formData.append('requisition_id', reqId);
                formData.append('rejection_reason', result.value);

                fetch('/executive/requisitions/reject', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Anggaran Ditolak!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
                    }
                })
                .catch(err => {
                    Swal.fire({ title: 'Kesalahan Sistem', text: err.message, icon: 'error' });
                });
            }
        });
    }
</script>
