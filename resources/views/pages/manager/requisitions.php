<?php
use App\Config\Database;
use App\Services\ViewHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? '';

// Fetch all departments for modal dropdown
$stmtDepts = $db->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = $stmtDepts->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's department
$userDeptId = $_SESSION['department_id'] ?? null;

// Fetch requisitions for this manager (or all if superadmin)
if ($role === 'superadmin') {
    $stmtReqs = $db->query("
        SELECT r.*, d.name AS dept_name, u.first_name, u.last_name
        FROM job_requisitions r
        LEFT JOIN departments d ON r.department_id = d.id
        LEFT JOIN users u ON r.requested_by = u.id
        ORDER BY r.created_at DESC
    ");
} else {
    $stmtReqs = $db->prepare("
        SELECT r.*, d.name AS dept_name, u.first_name, u.last_name
        FROM job_requisitions r
        LEFT JOIN departments d ON r.department_id = d.id
        LEFT JOIN users u ON r.requested_by = u.id
        WHERE r.requested_by = :user_id OR r.department_id = :dept_id
        ORDER BY r.created_at DESC
    ");
    $stmtReqs->execute(['user_id' => $userId, 'dept_id' => $userDeptId]);
}
$requisitions = $stmtReqs->fetchAll(PDO::FETCH_ASSOC);

// Calculate Summary Cards
$totalReqs = count($requisitions);
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$publishedCount = 0;

foreach ($requisitions as $r) {
    if (in_array($r['status'], ['pending_dept_clevel', 'pending_cfo_chro', 'pending_ceo'])) {
        $pendingCount++;
    } elseif ($r['status'] === 'approved') {
        $approvedCount++;
    } elseif ($r['status'] === 'published') {
        $publishedCount++;
    } elseif ($r['status'] === 'rejected') {
        $rejectedCount++;
    }
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Permintaan Tenaga Kerja (FPTK)</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Kelola pengajuan penambahan staf baru dengan persetujuan berjenjang C-Level.</p>
        </div>
        <button type="button" onclick="openNewReqModal()" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/95 text-white text-xs lg:text-sm font-bold py-2.5 px-4 rounded-xl shadow-md transition-all cursor-pointer flex-shrink-0">
            <span class="material-symbols-outlined text-[20px]">person_add</span>
            Buat Permintaan FPTK Baru
        </button>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/15 shadow-sm space-y-1">
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Pengajuan</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-on-surface"><?= $totalReqs ?></span>
                <span class="material-symbols-outlined text-primary text-xl">assignment</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-amber-200/50 bg-amber-50/20 shadow-sm space-y-1">
            <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Proses C-Level</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-amber-700"><?= $pendingCount ?></span>
                <span class="material-symbols-outlined text-amber-600 text-xl">hourglass_top</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-emerald-200/50 bg-emerald-50/20 shadow-sm space-y-1">
            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Disetujui CEO</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-700"><?= $approvedCount + $publishedCount ?></span>
                <span class="material-symbols-outlined text-emerald-600 text-xl">verified</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-red-200/50 bg-red-50/20 shadow-sm space-y-1">
            <span class="text-[10px] font-bold text-red-700 uppercase tracking-wider">Ditolak</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-red-700"><?= $rejectedCount ?></span>
                <span class="material-symbols-outlined text-red-600 text-xl">cancel</span>
            </div>
        </div>
    </div>

    <!-- Requisitions Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 md:p-6 border-b border-outline-variant/15 flex justify-between items-center">
            <h3 class="font-bold text-sm text-on-surface">Daftar Formulir Permintaan Tenaga Kerja</h3>
            <span class="text-xs text-on-surface-variant font-medium">Total <?= count($requisitions) ?> data</span>
        </div>

        <?php if (empty($requisitions)): ?>
            <div class="p-12 text-center space-y-3">
                <span class="material-symbols-outlined text-5xl text-outline-variant/60">assignment_add</span>
                <p class="text-sm font-bold text-on-surface">Belum ada Permintaan Tenaga Kerja</p>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">Klik tombol "Buat Permintaan FPTK Baru" di atas untuk menyeleksi dan mengajukan penambahan anggota tim baru.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-on-surface-variant font-bold border-b border-outline-variant/15 uppercase tracking-wider">
                            <th class="py-3.5 px-4">No. FPTK</th>
                            <th class="py-3.5 px-4">Posisi & Dept</th>
                            <th class="py-3.5 px-4 text-center">Jumlah</th>
                            <th class="py-3.5 px-4">Tipe & Gaji</th>
                            <th class="py-3.5 px-4">Status Approval C-Level</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 text-on-surface font-medium">
                        <?php foreach ($requisitions as $r): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="py-3.5 px-4 font-bold text-primary">
                                    <?= htmlspecialchars($r['req_number']) ?>
                                    <span class="block text-[10px] font-normal text-on-surface-variant"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= htmlspecialchars($r['title']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($r['dept_name'] ?? 'Umum') ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-extrabold text-sm">
                                    <?= (int)$r['headcount'] ?> <span class="text-[10px] font-normal text-on-surface-variant block">Orang</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-xs capitalize"><?= str_replace('_', ' ', $r['employment_type']) ?></span>
                                    <span class="block text-[10px] text-on-surface-variant font-mono">
                                        Rp<?= number_format($r['min_salary'], 0, ',', '.') ?> - Rp<?= number_format($r['max_salary'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <!-- Progress Stepper Badges -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-1.5 text-[10px]">
                                            <!-- Step 1: Dept C-Level -->
                                            <span class="px-2 py-0.5 rounded-md font-bold <?= !empty($r['dept_clevel_approved_at']) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800') ?>">
                                                1. Dept C-Level <?= !empty($r['dept_clevel_approved_at']) ? '✓' : '' ?>
                                            </span>
                                            <span class="text-outline-variant">→</span>

                                            <!-- Step 2: CFO & CHRO -->
                                            <span class="px-2 py-0.5 rounded-md font-bold <?= (!empty($r['cfo_approved_at']) && !empty($r['chro_approved_at'])) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_cfo_chro' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500') ?>">
                                                2. CFO & COO <?= (!empty($r['cfo_approved_at']) && !empty($r['chro_approved_at'])) ? '✓' : '' ?>
                                            </span>
                                            <span class="text-outline-variant">→</span>

                                            <!-- Step 3: CEO -->
                                            <span class="px-2 py-0.5 rounded-md font-bold <?= !empty($r['ceo_approved_at']) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_ceo' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500') ?>">
                                                3. CEO <?= !empty($r['ceo_approved_at']) ? '✓' : '' ?>
                                            </span>
                                        </div>

                                        <!-- Final Status Badge -->
                                        <div>
                                            <?php if ($r['status'] === 'published'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                    <span class="material-symbols-outlined text-[12px]">public</span> Dipublikasi Recruiter
                                                </span>
                                            <?php elseif ($r['status'] === 'approved'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    <span class="material-symbols-outlined text-[12px]">check_circle</span> Disetujui CEO (Siap Publikasi)
                                                </span>
                                            <?php elseif ($r['status'] === 'rejected'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                                    <span class="material-symbols-outlined text-[12px]">cancel</span> Ditolak: <?= htmlspecialchars($r['rejection_reason'] ?? 'Ditolak') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    <span class="material-symbols-outlined text-[12px]">hourglass_top</span> Menunggu Persetujuan C-Level
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button" onclick='viewReqDetail(<?= json_encode($r) ?>)' class="inline-flex items-center gap-1 text-primary hover:text-primary-container font-bold text-xs bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-lg transition-colors cursor-pointer">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: New FPTK Form -->
<div id="newReqModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-2xl w-full max-w-2xl mx-4 overflow-hidden transform scale-95 transition-all duration-300 max-h-[90vh] flex flex-col" id="newReqModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-outline-variant/15 flex items-center justify-between bg-surface-container-low/30">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-lg">person_add</span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-on-surface">Formulir Permintaan Tenaga Kerja (FPTK)</h3>
                    <p class="text-[11px] text-on-surface-variant font-medium">Ajukan penambahan anggota tim baru untuk diproses C-Level & Recruiter.</p>
                </div>
            </div>
            <button type="button" onclick="closeNewReqModal()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Form Body -->
        <form id="newReqForm" onsubmit="submitNewReqForm(event)" class="p-6 space-y-4 overflow-y-auto flex-grow">
            <?= csrfField() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Title -->
                <div class="space-y-1 md:col-span-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Nama Posisi / Jabatan yang Dibutuhkan <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required placeholder="Contoh: Senior Backend Engineer / HR Specialist" class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface" />
                </div>

                <!-- Department -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Departemen <span class="text-red-500">*</span></label>
                    <?php $isDeptLocked = ($role !== 'superadmin' && !empty($userDeptId)); ?>
                    <?php if ($isDeptLocked): ?>
                        <input type="hidden" name="department_id" value="<?= htmlspecialchars($userDeptId) ?>" />
                    <?php endif; ?>
                    <select <?= $isDeptLocked ? 'disabled' : 'name="department_id" required' ?> class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface <?= $isDeptLocked ? 'disabled:opacity-80 disabled:bg-surface-container-low cursor-not-allowed' : '' ?>">
                        <option value="">-- Pilih Departemen --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($d['id'] === $userDeptId) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Headcount -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Jumlah Staf (Headcount) <span class="text-red-500">*</span></label>
                    <input type="number" name="headcount" value="1" min="1" required class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface" />
                </div>

                <!-- Employment Type -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Tipe Kepegawaian <span class="text-red-500">*</span></label>
                    <select name="employment_type" required class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface">
                        <option value="full_time">Full-Time (Penuh Waktu)</option>
                        <option value="contract">Kontrak (PKWT)</option>
                        <option value="internship">Magang (Internship)</option>
                    </select>
                </div>

                <!-- Salary Range -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Kisaran Gaji Min & Max (Rp)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="min_salary" placeholder="Min (5.000.000)" class="py-2.5 px-3 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary font-semibold text-on-surface" />
                        <input type="number" name="max_salary" placeholder="Max (10.000.000)" class="py-2.5 px-3 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary font-semibold text-on-surface" />
                    </div>
                </div>

                <!-- Reason / Justification -->
                <div class="space-y-1 md:col-span-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Alasan & Justifikasi Pengajuan <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="2" required placeholder="Jelaskan kebutuhan penambahan staf (misal: penggantian karyawan resign, ekspansi proyek baru, beban kerja tinggi)..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary font-semibold text-on-surface"></textarea>
                </div>

                <!-- Job Description -->
                <div class="space-y-1 md:col-span-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Deskripsi Pekerjaan <span class="text-red-500">*</span></label>
                    <textarea name="job_description" rows="3" required placeholder="Tuliskan tugas & tanggung jawab utama kandidat..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary font-semibold text-on-surface"></textarea>
                </div>

                <!-- Requirements -->
                <div class="space-y-1 md:col-span-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Kualifikasi & Persyaratan</label>
                    <textarea name="requirements" rows="3" placeholder="Persyaratan pendidikan, skill teknis, pengalaman kerja minimal..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary font-semibold text-on-surface"></textarea>
                </div>
            </div>
        </form>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-2 bg-surface-container-low/20 flex-shrink-0">
            <button type="button" onclick="closeNewReqModal()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer">
                Batal
            </button>
            <button type="submit" form="newReqForm" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-5 rounded-xl transition-all shadow-md cursor-pointer">
                Kirim Pengajuan ke C-Level
            </button>
        </div>
    </div>
</div>

<!-- Modal: View Req Detail -->
<div id="detailReqModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-2xl w-full max-w-xl mx-4 overflow-hidden transform scale-95 transition-all duration-300 max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-outline-variant/15 flex items-center justify-between bg-surface-container-low/30">
            <h3 class="font-bold text-sm text-on-surface" id="detailReqTitle">Detail FPTK</h3>
            <button type="button" onclick="closeDetailReqModal()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 space-y-4 overflow-y-auto text-xs" id="detailReqContent">
            <!-- Dynamic Content -->
        </div>
        <div class="px-6 py-3 border-t border-outline-variant/15 flex justify-end bg-surface-container-low/20">
            <button type="button" onclick="closeDetailReqModal()" class="bg-surface-container-high text-on-surface font-bold text-xs py-2 px-4 rounded-xl cursor-pointer">Tutup</button>
        </div>
    </div>
</div>

<script>
    function openNewReqModal() {
        const modal = document.getElementById('newReqModal');
        const container = document.getElementById('newReqModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }

    function closeNewReqModal() {
        const modal = document.getElementById('newReqModal');
        const container = document.getElementById('newReqModalContainer');
        modal.classList.add('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
    }

    function viewReqDetail(req) {
        document.getElementById('detailReqTitle').innerText = 'Detail FPTK: ' + req.req_number;
        const content = document.getElementById('detailReqContent');
        
        let statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Menunggu Approval C-Level</span>`;
        if (req.status === 'approved') statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Disetujui CEO (Siap Publikasi)</span>`;
        if (req.status === 'published') statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Dipublikasi ke Portal Karir</span>`;
        if (req.status === 'rejected') statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800">Ditolak</span>`;

        content.innerHTML = `
            <div class="space-y-3">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-sm font-extrabold text-primary">${req.title}</h4>
                        <p class="text-on-surface-variant">${req.dept_name || 'Departemen'} | ${req.headcount} Orang | ${req.employment_type}</p>
                    </div>
                    ${statusBadge}
                </div>
                <div class="bg-surface-container-low p-3 rounded-xl space-y-1">
                    <span class="font-bold text-[10px] text-on-surface-variant uppercase">Alasan Kebutuhan</span>
                    <p class="text-on-surface leading-relaxed">${req.reason || '-'}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-bold text-[10px] text-on-surface-variant uppercase">Deskripsi Pekerjaan</span>
                    <p class="text-on-surface leading-relaxed whitespace-pre-wrap">${req.job_description || '-'}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-bold text-[10px] text-on-surface-variant uppercase">Persyaratan</span>
                    <p class="text-on-surface leading-relaxed whitespace-pre-wrap">${req.requirements || '-'}</p>
                </div>
            </div>
        `;

        const modal = document.getElementById('detailReqModal');
        modal.classList.remove('opacity-0', 'pointer-events-none');
    }

    function closeDetailReqModal() {
        document.getElementById('detailReqModal').classList.add('opacity-0', 'pointer-events-none');
    }

    function submitNewReqForm(e) {
        e.preventDefault();
        const form = document.getElementById('newReqForm');
        const formData = new FormData(form);

        Swal.fire({
            title: 'Mengirim FPTK...',
            text: 'Mengirimkan pengajuan ke C-Level Divisi.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('/manager/requisitions/store', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                    closeNewReqModal();
                    if (window.loadPage) {
                        window.loadPage('/manager/requisitions');
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Gagal menyambung ke server.', 'error');
        });
    }

    window.openNewReqModal = openNewReqModal;
    window.closeNewReqModal = closeNewReqModal;
    window.viewReqDetail = viewReqDetail;
    window.closeDetailReqModal = closeDetailReqModal;
    window.submitNewReqForm = submitNewReqForm;
</script>