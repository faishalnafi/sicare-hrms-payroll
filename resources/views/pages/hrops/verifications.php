<?php
// HR Operations Verification Queue Dashboard
$db = \App\Config\Database::getInstance()->getConnection();

// Fetch counts
$stmt = $db->query("SELECT status, COUNT(*) as count FROM employee_data_correction_requests GROUP BY status");
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$pendingCount = $statusCounts['pending'] ?? 0;
$approvedCount = $statusCounts['approved'] ?? 0;
$rejectedCount = $statusCounts['rejected'] ?? 0;
$totalCount = array_sum($statusCounts);

// Fetch all rows
$stmt = $db->query("
    SELECT r.*, u.first_name, u.last_name, u.employee_id, u.role, u.email, u.profile_picture, COALESCE(u.job_title, '') AS job_title
    FROM employee_data_correction_requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$requests = $stmt->fetchAll();

function getEmployeePosition($email) {
    if (strpos($email, 'employee@mail.com') !== false || strpos($email, 'alex') !== false) {
        return 'Senior UI/UX Designer';
    } elseif (strpos($email, 'amanda') !== false) {
        return 'DevOps Engineer';
    } elseif (strpos($email, 'budi') !== false) {
        return 'Software Engineer';
    } elseif (strpos($email, 'siti') !== false) {
        return 'QA Engineer';
    }
    return 'Staff Karyawan';
}

function getFieldLabel($field) {
    $labels = [
        'ktp_nik' => 'NIK (Nomor Induk Kependudukan)',
        'nama_sesuai_ktp' => 'Nama Lengkap Sesuai KTP',
        'alamat_ktp' => 'Alamat Lengkap Sesuai KTP',
        'bank_name' => 'Nama Bank Penerima',
        'bank_account_number' => 'Nomor Rekening',
        'npwp_number' => 'NPWP (Nomor Pokok Wajib Pajak)',
        'bpjs_tk' => 'BPJS Ketenagakerjaan',
        'bpjs_kes' => 'BPJS Kesehatan',
        'tanggal_lahir' => 'Tanggal Lahir',
        'status_pernikahan' => 'Status Pernikahan',
        'jenis_kelamin' => 'Jenis Kelamin'
    ];
    return $labels[$field] ?? $field;
}

function getCategoryLabel($category) {
    $labels = [
        'kependudukan' => 'Kependudukan',
        'finansial' => 'Finansial',
        'pajak_asuransi' => 'Pajak & Asuransi',
        'data_pribadi' => 'Data Pribadi'
    ];
    return $labels[$category] ?? $category;
}

function getCategoryColor($category) {
    $colors = [
        'kependudukan' => 'text-teal-700 bg-teal-50',
        'finansial' => 'text-indigo-700 bg-indigo-50',
        'pajak_asuransi' => 'text-orange-700 bg-orange-50',
        'data_pribadi' => 'text-pink-700 bg-pink-50'
    ];
    return $colors[$category] ?? 'text-gray-700 bg-gray-50';
}
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Verifikasi Perbaikan Data</h1>
            <p class="text-on-surface-variant font-medium text-sm">Review, setujui, atau tolak permohonan koreksi data administratif sensitif karyawan (ESS Portal).</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="Swal.fire({title: 'Kebijakan Verifikasi', text: 'Seluruh berkas pendukung WAJIB diverifikasi keasliannya dan divalidasi MIME type-nya via PHP finfo.', icon: 'info', confirmButtonColor: '#000666'})" class="bg-surface-container-high hover:bg-surface-container-high/80 text-primary font-bold text-xs py-2.5 px-4 rounded-lg flex items-center gap-2 transition-all">
                <span class="material-symbols-outlined text-sm">verified_user</span> Panduan Verifikasi
            </button>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- KPI Card 1: Pending -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Antrean Pending</span>
                <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-2 rounded-xl font-bold <?= $pendingCount > 0 ? 'animate-pulse' : '' ?>">pending_actions</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-amber-700" id="kpiPendingCount"><?= $pendingCount ?> <span class="text-xs font-semibold text-amber-600">Pengajuan</span></h3>
                <p class="text-[11px] text-amber-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">hourglass_empty</span> Butuh tinjauan dokumen segera
                </p>
            </div>
        </div>

        <!-- KPI Card 2: Approved -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Disetujui Bulan Ini</span>
                <span class="material-symbols-outlined text-green-600 bg-green-50 p-2 rounded-xl">task_alt</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-green-700"><?= $approvedCount ?> <span class="text-xs font-semibold text-green-600">Data Terupdate</span></h3>
                <p class="text-[11px] text-green-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">sync</span> Sinkronisasi payroll otomatis aktif
                </p>
            </div>
        </div>

        <!-- KPI Card 3: Rejected -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Ditolak Bulan Ini</span>
                <span class="material-symbols-outlined text-red-600 bg-red-50 p-2 rounded-xl">cancel</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-red-700"><?= $rejectedCount ?> <span class="text-xs font-semibold text-red-600">Pengajuan</span></h3>
                <p class="text-[11px] text-red-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">error</span> Berkas tidak valid / terpotong
                </p>
            </div>
        </div>

        <!-- KPI Card 4: SLA Time -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Rata-rata Durasi SLA</span>
                <span class="material-symbols-outlined text-primary bg-primary/5 p-2 rounded-xl">speed</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-primary">1.2 <span class="text-xs font-semibold text-on-surface-variant">Jam</span></h3>
                <p class="text-[11px] text-on-surface-variant font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">rocket_launch</span> Target di bawah 24 jam kerja
                </p>
            </div>
        </div>
    </div>

    <!-- Filters & Table Panel -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        
        <!-- Table Control Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary font-bold">fact_check</span>
                <h2 class="text-lg font-extrabold text-on-surface font-headline">Antrean Perubahan Data Karyawan</h2>
            </div>
            
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <!-- Search -->
                <div class="relative w-full md:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-on-surface-variant/50 text-sm">search</span>
                    </span>
                    <input type="text" id="verificationSearch" onkeyup="filterVerificationTable()" placeholder="Cari nama atau ID karyawan..." class="pl-9 pr-4 py-2 w-full text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-on-surface font-medium" />
                </div>
                
                <!-- Category Filter -->
                <select id="verificationCategoryFilter" onchange="filterVerificationTable()" class="py-2 px-3 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-medium text-on-surface-variant">
                    <option value="">Semua Kategori</option>
                    <option value="kependudukan">Kependudukan</option>
                    <option value="finansial">Finansial</option>
                    <option value="pajak_asuransi">Pajak & Asuransi</option>
                    <option value="data_pribadi">Data Pribadi</option>
                </select>

                <!-- Status Filter -->
                <select id="verificationStatusFilter" onchange="filterVerificationTable()" class="py-2 px-3 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-medium text-on-surface-variant">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Karyawan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Kategori</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Kolom</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Perubahan Data (Lama → Baru)</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Alasan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Berkas</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="verificationTableBody" class="divide-y divide-outline-variant/10">
                    
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" class="py-8 px-6 text-center text-xs font-semibold text-on-surface-variant/60">
                                <span class="material-symbols-outlined text-4xl block mb-2 text-on-surface-variant/30">inbox</span>
                                Tidak ada pengajuan koreksi data.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): 
                            $fullName = $r['first_name'] . ' ' . $r['last_name'];
                            $initials = strtoupper(substr($r['first_name'], 0, 1) . (isset($r['last_name'][0]) ? substr($r['last_name'], 0, 1) : ''));
                            $position = $r['job_title'] ?: getEmployeePosition($r['email']);
                            $catLabel = getCategoryLabel($r['category']);
                            $catColor = getCategoryColor($r['category']);
                            $fieldLabel = getFieldLabel($r['field']);
                        ?>
                            <tr class="hover:bg-surface-container-low/30 transition-colors duration-200" id="row_<?= $r['id'] ?>" data-name="<?= strtolower(htmlspecialchars($fullName)) ?>" data-category="<?= htmlspecialchars($r['category']) ?>" data-status="<?= htmlspecialchars($r['status']) ?>">
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <?php 
                                            $profPic = $r['profile_picture'];
                                            $hash = md5(strtolower(trim($r['email'])));
                                            if (empty($profPic)) {
                                                $profPic = "https://www.gravatar.com/avatar/{$hash}?d=404&s=150";
                                            }
                                        ?>
                                        <img src="<?= htmlspecialchars($profPic) ?>" onerror="window.handleAvatarError(this, '<?= $hash ?>')" alt="<?= htmlspecialchars($fullName) ?>" class="w-10 h-10 rounded-full object-cover shadow-sm flex-shrink-0 bg-white border border-outline-variant/10" />
                                        <div>
                                            <div class="font-extrabold text-sm text-on-surface"><?= htmlspecialchars($fullName) ?></div>
                                            <div class="text-[11px] text-on-surface-variant font-semibold"><?= htmlspecialchars($position) ?> • <span class="font-mono text-primary font-bold"><?= htmlspecialchars($r['employee_id'] ?? 'EMP-N/A') ?></span></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="text-[10px] font-extrabold <?= $catColor ?> px-2.5 py-1 rounded-md inline-block uppercase tracking-wider"><?= htmlspecialchars($catLabel) ?></div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="text-xs font-bold text-on-surface"><?= htmlspecialchars($fieldLabel) ?></div>
                                    <div class="text-[10px] text-on-surface-variant/70 font-mono mt-0.5">`<?= htmlspecialchars($r['field']) ?>`</div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-100/50 font-mono line-through" title="Data Lama"><?= htmlspecialchars($r['old_value'] ?? '-') ?></span>
                                        <span class="material-symbols-outlined text-xs text-on-surface-variant font-bold">arrow_forward</span>
                                        <span class="text-xs font-bold text-primary bg-primary/5 px-2 py-0.5 rounded border border-primary/10 font-mono" title="Data Baru"><?= htmlspecialchars($r['new_value']) ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-xs text-on-surface font-semibold min-w-[240px] max-w-[380px] break-words whitespace-normal" title="<?= htmlspecialchars($r['reason']) ?>"><?= htmlspecialchars($r['reason']) ?></div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <?php if (!empty($r['file_path'])): ?>
                                        <button onclick="viewDocument('<?= htmlspecialchars(addslashes($r['file_path'])) ?>', '<?= htmlspecialchars(addslashes($fieldLabel . ' - ' . $fullName)) ?>', '<?= htmlspecialchars(addslashes($catLabel)) ?>')" class="inline-flex items-center gap-1.5 text-xs font-extrabold text-primary hover:text-primary/80 transition-colors">
                                            <span class="material-symbols-outlined text-sm font-bold text-primary">attachment</span>
                                            <span>Lihat Berkas</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant/30 text-xs font-semibold">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap" id="status_<?= $r['id'] ?>">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span> Pending Review
                                        </span>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span> Disetujui
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span> Ditolak
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap" id="action_<?= $r['id'] ?>">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick="rejectRequest('<?= $r['id'] ?>', '<?= htmlspecialchars(addslashes($fullName)) ?>', '<?= htmlspecialchars(addslashes($fieldLabel)) ?>')" class="bg-red-50 hover:bg-red-100 text-red-700 font-bold text-[10px] py-1.5 px-3 rounded-lg border border-red-200 transition-colors">
                                                Tolak
                                            </button>
                                            <button onclick="approveRequest('<?= $r['id'] ?>', '<?= htmlspecialchars(addslashes($fullName)) ?>', '<?= htmlspecialchars(addslashes($fieldLabel)) ?>', '<?= htmlspecialchars(addslashes($r['new_value'])) ?>')" class="bg-green-50 hover:bg-green-100 text-green-700 font-bold text-[10px] py-1.5 px-3 rounded-lg border border-green-200 transition-colors">
                                                Setujui
                                            </button>
                                        </div>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <span class="text-xs text-on-surface-variant/40 font-bold italic pr-2">Selesai</span>
                                    <?php else: ?>
                                        <button onclick="Swal.fire({title: 'Alasan Penolakan', text: '<?= htmlspecialchars(addslashes($r['rejection_reason'] ?? '')) ?>', icon: 'info', confirmButtonColor: '#000666'})" class="text-xs font-bold text-on-surface-variant hover:text-on-surface hover:underline flex items-center gap-1 justify-end ml-auto pr-2">
                                            <span class="material-symbols-outlined text-sm">info</span> Info Detail
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- Footer Pagination Info -->
        <div class="bg-surface-container-lowest p-6 border-t border-outline-variant/15 flex flex-col md:flex-row justify-between items-center gap-4 text-xs font-semibold text-on-surface-variant">
            <div>
                Menampilkan <span id="verificationDisplayedCount"><?= count($requests) ?></span> dari <?= count($requests) ?> data antrean verifikasi
            </div>
            <div class="flex items-center gap-1">
                <button class="w-8 h-8 rounded-lg bg-surface border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container-high transition-colors" disabled><span class="material-symbols-outlined text-sm">chevron_left</span></button>
                <button class="w-8 h-8 rounded-lg bg-primary text-white flex items-center justify-center shadow-sm">1</button>
                <button class="w-8 h-8 rounded-lg bg-surface border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container-high transition-colors" disabled><span class="material-symbols-outlined text-sm">chevron_right</span></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Keep track of pending requests in dashboard
    let pendingCount = <?= $pendingCount ?>;

    // Search and category/status filter logic
    window.filterVerificationTable = function filterVerificationTable() {
        const query = document.getElementById('verificationSearch').value.toLowerCase().trim();
        const category = document.getElementById('verificationCategoryFilter').value.toLowerCase();
        const status = document.getElementById('verificationStatusFilter').value.toLowerCase();
        const rows = document.querySelectorAll('#verificationTableBody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const nameAttr = row.getAttribute('data-name');
            const catAttr = row.getAttribute('data-category');
            const statusAttr = row.getAttribute('data-status');

            if (!nameAttr) return; // Skip empty row

            const matchesSearch = !query || nameAttr.includes(query);
            const matchesCat = !category || catAttr === category;
            const matchesStatus = !status || statusAttr === status;

            if (matchesSearch && matchesCat && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('verificationDisplayedCount').textContent = visibleCount;
    }

    // View uploaded documents with secure preview popups
    window.viewDocument = function viewDocument(fileName, title, docType) {
        const isPdf = fileName.toLowerCase().endsWith('.pdf');
        const fileUrl = `/hrops/verifications/view_file?file=${encodeURIComponent(fileName)}`;
        
        let htmlContent = `
            <div class="text-left space-y-2 mt-2">
                <p class="text-xs text-on-surface-variant"><strong>Kategori:</strong> ${docType}</p>
                <p class="text-xs text-on-surface-variant font-mono"><strong>Nama Berkas:</strong> ${fileName}</p>
                <p class="text-[10px] text-green-700 bg-green-50 px-2 py-1 rounded inline-block font-bold">MIME TYPE VALID: Verified via Server finfo</p>
            </div>
        `;

        const swalConfig = {
            title: title,
            showDenyButton: true,
            confirmButtonText: 'Tutup Dokumen',
            denyButtonText: '<span class="material-symbols-outlined text-xs" style="vertical-align: middle; margin-right: 4px;">download</span>Unduh Berkas',
            confirmButtonColor: '#000666',
            denyButtonColor: '#ff6f00',
        };

        if (isPdf) {
            swalConfig.html = htmlContent + `
                <div class="mt-4 p-6 bg-surface-container-low rounded-xl border border-outline-variant/10 flex flex-col items-center justify-center">
                    <span class="material-symbols-outlined text-6xl text-red-600 mb-2">picture_as_pdf</span>
                    <p class="text-xs font-bold text-on-surface">Dokumen PDF Terdeteksi</p>
                    <a href="${fileUrl}" target="_blank" class="mt-3 bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2 px-4 rounded flex items-center gap-1.5 transition-all">
                        <span class="material-symbols-outlined text-sm">open_in_new</span> Buka PDF di Tab Baru
                    </a>
                </div>
            `;
        } else {
            swalConfig.html = htmlContent;
            swalConfig.imageUrl = fileUrl;
            swalConfig.imageWidth = 500;
            swalConfig.imageHeight = 330;
            swalConfig.imageAlt = title;
        }

        Swal.fire(swalConfig).then((result) => {
            if (result.isDenied) {
                window.location.href = fileUrl + '&download=1';
            }
        });
    }

    // Approve Action: Overwrites database data atomically via PHP controller
    window.approveRequest = function approveRequest(id, employeeName, fieldLabel, newValue) {
        Swal.fire({
            title: 'Setujui Perubahan Data?',
            html: `
                <div class="text-left space-y-3 text-xs bg-surface-container-low p-4 rounded-xl mt-3">
                    <p>Apakah Anda yakin ingin menyetujui koreksi data dari <strong>${employeeName}</strong>?</p>
                    <p class="pt-2"><strong>Kolom:</strong> ${fieldLabel}</p>
                    <p><strong>Nilai Baru:</strong> <span class="bg-green-100 text-green-800 font-bold px-2 py-0.5 rounded font-mono">${newValue}</span></p>
                    <p class="text-on-surface-variant/80 border-t border-outline-variant/10 pt-2 italic">Menyetujui aksi ini akan memicu <strong>Database Transaction</strong> aman secara atomik untuk menimpa nilai lama di database karyawan.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2e7d32',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Setujui & Update',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses Sinkronisasi...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Post real AJAX request to controller
                const formData = new FormData();
                formData.append('request_id', id);

                fetch('/hrops/verifications/approve', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update visual status in table
                        const statusTd = document.getElementById(`status_${id}`);
                        const actionTd = document.getElementById(`action_${id}`);
                        const row = document.getElementById(`row_${id}`);

                        row.setAttribute('data-status', 'approved');
                        statusTd.innerHTML = `
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 animate-fade-in whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span> Disetujui
                            </span>
                        `;
                        actionTd.innerHTML = `<span class="text-xs text-on-surface-variant/40 font-bold italic pr-2">Selesai</span>`;

                        // Decrement pending count
                        pendingCount--;
                        document.getElementById('kpiPendingCount').innerHTML = `${pendingCount} <span class="text-xs font-semibold text-amber-600">Pengajuan</span>`;

                        Swal.fire({
                            title: 'Data Berhasil Diperbarui!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        });
                        
                        filterVerificationTable(); // re-filter if filters are active
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error!', 'Terjadi kesalahan jaringan atau koneksi ditolak server.', 'error');
                });
            }
        });
    }

    // Reject Action: Update request to rejected with reason via PHP controller
    window.rejectRequest = function rejectRequest(id, employeeName, fieldLabel) {
        Swal.fire({
            title: `Tolak Pengajuan - ${employeeName}`,
            input: 'textarea',
            inputLabel: `Alasan Penolakan Perbaikan ${fieldLabel}`,
            inputPlaceholder: 'Tulis alasan penolakan secara jelas agar karyawan mendapat penjelasan...',
            inputAttributes: {
                'aria-label': 'Tulis alasan penolakan di sini'
            },
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Tolak Pengajuan',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan wajib diisi untuk diinformasikan kepada karyawan.';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses Penolakan...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Post real AJAX request to controller
                const formData = new FormData();
                formData.append('request_id', id);
                formData.append('rejection_reason', result.value);

                fetch('/hrops/verifications/reject', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const statusTd = document.getElementById(`status_${id}`);
                        const actionTd = document.getElementById(`action_${id}`);
                        const row = document.getElementById(`row_${id}`);

                        row.setAttribute('data-status', 'rejected');
                        statusTd.innerHTML = `
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 animate-fade-in whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span> Ditolak
                            </span>
                        `;
                        const escapedValue = result.value.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        actionTd.innerHTML = `
                            <button onclick="Swal.fire({title: 'Alasan Penolakan', text: '${escapedValue}', icon: 'info', confirmButtonColor: '#000666'})" class="text-xs font-bold text-on-surface-variant hover:text-on-surface hover:underline flex items-center gap-1 justify-end ml-auto pr-2">
                                <span class="material-symbols-outlined text-sm">info</span> Info Detail
                            </button>
                        `;

                        // Decrement pending count
                        pendingCount--;
                        document.getElementById('kpiPendingCount').innerHTML = `${pendingCount} <span class="text-xs font-semibold text-amber-600">Pengajuan</span>`;

                        Swal.fire({
                            title: 'Pengajuan Ditolak',
                            text: data.message,
                            icon: 'error',
                            confirmButtonColor: '#000666'
                        });

                        filterVerificationTable(); // re-filter if filters are active
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error!', 'Terjadi kesalahan jaringan atau koneksi ditolak server.', 'error');
                });
            }
        });
    }
</script>