<?php
// Employee ESS Leaves & Permits Dashboard
$db = \App\Config\Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? '';

if (empty($userId)) {
    echo "<div class='p-6 text-red-600 font-bold'>Akses Ditolak: Sesi kedaluwarsa. Silakan login kembali.</div>";
    return;
}

// Fetch user leave quota
$stmt = $db->prepare("SELECT annual_leave_quota, updated_at FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
$annualLeaveQuota = intval($userRow['annual_leave_quota'] ?? 12);
$lastUpdateYear = !empty($userRow['updated_at']) ? date('Y', strtotime($userRow['updated_at'])) : date('Y');
$currentYear = date('Y');

if ($currentYear > $lastUpdateYear) {
    // Reset annual leave quota to 12 in the new year
    $stmtReset = $db->prepare("UPDATE users SET annual_leave_quota = 12, updated_at = NOW() WHERE id = :id");
    $stmtReset->execute(['id' => $userId]);
    $annualLeaveQuota = 12;
}

// Fetch approved cuti tahunan duration for the current month
$stmt = $db->prepare("
    SELECT SUM(duration) FROM employee_leave_requests 
    WHERE user_id = :user_id 
      AND leave_type = 'cuti tahunan' 
      AND status = 'approved' 
      AND MONTH(created_at) = MONTH(CURRENT_DATE) 
      AND YEAR(created_at) = YEAR(CURRENT_DATE)
");
$stmt->execute(['user_id' => $userId]);
$monthlyAnnualLeaveApproved = intval($stmt->fetchColumn() ?? 0);
$dropdownAnnualLeaveRemaining = max(0, 12 - $monthlyAnnualLeaveApproved);

// Fetch approved cuti melahirkan duration for the current month
$stmt = $db->prepare("
    SELECT SUM(duration) FROM employee_leave_requests 
    WHERE user_id = :user_id 
      AND leave_type = 'cuti melahirkan' 
      AND status = 'approved' 
      AND MONTH(created_at) = MONTH(CURRENT_DATE) 
      AND YEAR(created_at) = YEAR(CURRENT_DATE)
");
$stmt->execute(['user_id' => $userId]);
$monthlyMaternityLeaveApproved = intval($stmt->fetchColumn() ?? 0);
$dropdownMaternityLeaveRemaining = max(0, 90 - $monthlyMaternityLeaveApproved);

// Fetch total cuti sakit taken (approved)
$stmt = $db->prepare("SELECT SUM(duration) FROM employee_leave_requests WHERE user_id = :user_id AND leave_type = 'cuti sakit' AND status = 'approved' AND YEAR(created_at) = YEAR(CURRENT_DATE)");
$stmt->execute(['user_id' => $userId]);
$sickLeaveTaken = intval($stmt->fetchColumn() ?? 0);

// Fetch pending requests count
$stmt = $db->prepare("SELECT COUNT(*) FROM employee_leave_requests WHERE user_id = :user_id AND status = 'pending'");
$stmt->execute(['user_id' => $userId]);
$pendingCount = intval($stmt->fetchColumn() ?? 0);

// Fetch approved requests count (excluding sick leave)
$stmt = $db->prepare("SELECT COUNT(*) FROM employee_leave_requests WHERE user_id = :user_id AND status = 'approved' AND YEAR(created_at) = YEAR(CURRENT_DATE)");
$stmt->execute(['user_id' => $userId]);
$approvedCount = intval($stmt->fetchColumn() ?? 0);

// Fetch all leave requests for history table
$stmt = $db->prepare("
    SELECT * FROM employee_leave_requests 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC
");
$stmt->execute(['user_id' => $userId]);
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getLeaveTypeColor($type) {
    $colors = [
        'cuti tahunan' => 'text-primary bg-primary/5 border-primary/20',
        'cuti sakit' => 'text-blue-700 bg-blue-50 border-blue-200',
        'cuti melahirkan' => 'text-pink-700 bg-pink-50 border-pink-200',
        'izin khusus' => 'text-purple-700 bg-purple-50 border-purple-200'
    ];
    return $colors[$type] ?? 'text-gray-700 bg-gray-50 border-gray-200';
}

function getLeaveTypeLabel($type) {
    $labels = [
        'cuti tahunan' => 'Cuti Tahunan',
        'cuti sakit' => 'Cuti Sakit',
        'cuti melahirkan' => 'Cuti Melahirkan',
        'izin khusus' => 'Izin Khusus'
    ];
    return $labels[$type] ?? ucwords($type);
}
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Cuti & Izin</h1>
            <p class="text-on-surface-variant font-medium text-sm">Ajukan permohonan cuti baru, kelola jatah cuti tahunan, dan monitor persetujuan izin secara transparan.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openLeaveModal()" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all shadow-md shadow-primary/10 cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">add_circle</span> Ajukan Cuti & Izin Baru
            </button>
        </div>
    </div>

    <!-- Bento Grid KPI Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Card 1: Annual Leave Quota -->
        <div class="bg-gradient-to-br from-primary to-blue-900 text-white rounded-2xl p-5 shadow-md flex flex-col justify-between min-h-[140px] relative overflow-hidden">
            <div class="absolute right-0 bottom-0 opacity-10 translate-x-2 translate-y-2">
                <span class="material-symbols-outlined text-9xl">calendar_month</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-white/80">Sisa Cuti Tahunan</span>
                <span class="material-symbols-outlined text-white/30 text-xl font-bold bg-white/10 p-1.5 rounded-lg">calendar_today</span>
            </div>
            <div class="mt-4 z-10">
                <h3 class="text-3xl font-black"><?= $annualLeaveQuota ?> <span class="text-xs font-semibold text-white/80">Hari</span></h3>
                <div class="mt-2 w-full bg-white/20 h-1 rounded-full overflow-hidden">
                    <div class="bg-white h-full rounded-full" style="width: <?= min(100, max(0, ($annualLeaveQuota / 12) * 100)) ?>%"></div>
                </div>
                <p class="text-[10px] text-white/70 font-semibold mt-1">Kuota dasar setahun: 12 hari</p>
            </div>
        </div>

        <!-- Card 2: Sick Leaves Taken -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between min-h-[140px]">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Cuti Sakit Diambil</span>
                <span class="material-symbols-outlined text-blue-600 bg-blue-50 p-2 rounded-lg font-bold">medical_services</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-blue-700"><?= $sickLeaveTaken ?> <span class="text-xs font-semibold text-blue-600">Hari</span></h3>
                <p class="text-[10px] text-blue-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">verified</span> Tercatat dengan surat sakit
                </p>
            </div>
        </div>

        <!-- Card 3: Pending Review -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between min-h-[140px]">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Menunggu Review</span>
                <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-2 rounded-lg font-bold">hourglass_empty</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-amber-700"><?= $pendingCount ?> <span class="text-xs font-semibold text-amber-600">Pengajuan</span></h3>
                <p class="text-[10px] text-amber-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">info</span> Sedang ditinjau HR Operations
                </p>
            </div>
        </div>

        <!-- Card 4: Total Approved Requests -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between min-h-[140px]">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Disetujui</span>
                <span class="material-symbols-outlined text-green-600 bg-green-50 p-2 rounded-lg font-bold">verified</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-green-700"><?= $approvedCount ?> <span class="text-xs font-semibold text-green-600">Pengajuan</span></h3>
                <p class="text-[10px] text-green-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">check_circle</span> Disetujui secara tertib
                </p>
            </div>
        </div>
    </div>

    <!-- Leaves History Table Panel -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <!-- Control Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary font-bold">history</span>
                <h2 class="text-lg font-extrabold text-on-surface">Riwayat Pengajuan Cuti & Izin</h2>
            </div>
            <!-- Filters -->
            <div class="flex items-center gap-3">
                <select id="employeeLeaveTypeFilter" onchange="filterLeavesTable()" class="py-2 pl-3 pr-8 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant appearance-none cursor-pointer">
                    <option value="">Semua Jenis Cuti</option>
                    <option value="cuti tahunan">Cuti Tahunan</option>
                    <option value="cuti sakit">Cuti Sakit</option>
                    <option value="cuti melahirkan">Cuti Melahirkan</option>
                    <option value="izin khusus">Izin Khusus</option>
                </select>
                <select id="employeeLeaveStatusFilter" onchange="filterLeavesTable()" class="py-2 pl-3 pr-8 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant appearance-none cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Tanggal Diajukan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Jenis Cuti</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Durasi</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Periode</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Alasan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Berkas Bukti</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="employeeLeavesTableBody" class="divide-y divide-outline-variant/10">
                    <?php if (empty($leaves)): ?>
                    <tr>
                        <td colspan="8" class="py-8 text-center text-on-surface-variant font-medium text-xs">
                            <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">inbox</span>
                            <p>Belum ada riwayat pengajuan cuti atau izin.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($leaves as $leave): ?>
                    <tr class="leave-table-row hover:bg-surface-container-low/30 transition-colors" data-status="<?= htmlspecialchars($leave['status']) ?>" data-type="<?= htmlspecialchars($leave['leave_type']) ?>">
                        <td class="py-4 px-6 font-semibold text-xs text-on-surface">
                            <?= date('d M Y, H:i', strtotime($leave['created_at'])) ?>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold border <?= getLeaveTypeColor($leave['leave_type']) ?>">
                                <?= getLeaveTypeLabel($leave['leave_type']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 font-bold text-xs text-on-surface">
                            <?= $leave['duration'] ?> Hari
                        </td>
                        <td class="py-4 px-6 text-[10px] text-on-surface-variant font-mono">
                            <?= date('d M Y', strtotime($leave['start_date'])) ?> - <?= date('d M Y', strtotime($leave['end_date'])) ?>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-xs text-on-surface font-semibold truncate max-w-[200px]" title="<?= htmlspecialchars($leave['reason']) ?>">
                                <?= htmlspecialchars($leave['reason']) ?>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <?php if (!empty($leave['attachment_path'])): ?>
                            <button onclick="viewAttachment('<?= htmlspecialchars($leave['attachment_path']) ?>', '<?= htmlspecialchars(getLeaveTypeLabel($leave['leave_type'])) ?>')" class="text-[10px] text-primary hover:text-primary/80 no-underline hover:no-underline font-extrabold flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-xs font-bold text-primary hover:text-primary/80">attachment</span>
                                <span>Lihat Berkas</span>
                            </button>
                            <?php else: ?>
                            <span class="text-[10px] text-on-surface-variant/40 font-bold italic">Tanpa berkas</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6">
                            <?php if ($leave['status'] === 'pending'): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending Review
                            </span>
                            <?php elseif ($leave['status'] === 'approved'): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Disetujui
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Ditolak
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <?php if ($leave['status'] === 'pending'): ?>
                            <button onclick="cancelLeave('<?= $leave['id'] ?>', '<?= $leave['duration'] ?>')" class="bg-red-50 hover:bg-red-100 text-red-700 font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-red-200 transition-colors inline-flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-xs">cancel</span> Batalkan
                            </button>
                            <?php elseif ($leave['status'] === 'rejected' && !empty($leave['rejection_reason'])): ?>
                            <button onclick="showRejectionReason('<?= htmlspecialchars(addslashes($leave['rejection_reason'])) ?>')" class="bg-surface-container-high hover:bg-surface-container-high/80 text-on-surface-variant font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-outline-variant/30 transition-colors flex items-center gap-1 justify-end ml-auto cursor-pointer">
                                <span class="material-symbols-outlined text-xs">info</span> Info Detail
                            </button>
                            <?php else: ?>
                            <span class="text-[10px] text-on-surface-variant/40 font-bold italic">Selesai</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="leavePaginationControls" class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end bg-surface-container-low/30 hidden">
            <div class="flex items-center gap-2">
                <button id="btnLeavePrevPage" class="p-1.5 rounded-lg border border-outline-variant/20 text-on-surface hover:bg-surface-container-high transition-colors disabled:opacity-30 disabled:cursor-not-allowed"><span class="material-symbols-outlined text-sm">chevron_left</span></button>
                <button id="btnLeaveNextPage" class="p-1.5 rounded-lg border border-outline-variant/20 text-on-surface hover:bg-surface-container-high transition-colors disabled:opacity-30 disabled:cursor-not-allowed"><span class="material-symbols-outlined text-sm">chevron_right</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Submit Leave (Hidden by default) -->
<div id="leaveModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300" id="leaveModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary font-bold">calendar_today</span>
                <h3 class="text-base font-extrabold text-on-surface">Ajukan Cuti & Izin Baru</h3>
            </div>
            <button onclick="closeLeaveModal()" class="p-1.5 hover:bg-surface-container-high rounded-full transition-colors cursor-pointer flex items-center justify-center text-on-surface-variant">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body Form -->
        <form id="leaveSubmitForm" onsubmit="submitLeaveForm(event)" class="p-6 space-y-4">
    <input type="hidden" name="csrf_token" value="<?= \App\Middleware\SecurityMiddleware::getCsrfToken() ?>">
            <!-- Leave Type -->
            <div class="space-y-1.5">
                <label for="leaveTypeSelect" class="block text-xs font-bold text-on-surface-variant uppercase">Tipe Pengajuan <span class="text-red-500">*</span></label>
                <select name="leave_type" id="leaveTypeSelect" onchange="toggleAttachmentField()" required class="w-full py-2.5 px-3.5 text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant">
                    <option value="" disabled selected>Pilih Tipe Cuti/Izin...</option>
                    <option value="cuti tahunan" <?= $dropdownAnnualLeaveRemaining <= 0 ? 'disabled' : '' ?>>Cuti Tahunan (Sisa: <?= $dropdownAnnualLeaveRemaining ?> Hari)</option>
                    <option value="cuti sakit">Cuti Sakit (Wajib unggah surat dokter)</option>
                    <option value="cuti melahirkan" <?= $dropdownMaternityLeaveRemaining <= 0 ? 'disabled' : '' ?>>Cuti Melahirkan (<?= $dropdownMaternityLeaveRemaining ?> Hari - Wajib berkas HPL)</option>
                    <option value="izin khusus">Izin Khusus (Keperluan mendesak/penting)</option>
                </select>
            </div>

            <!-- Date Range Picker -->
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label for="startDateInput" class="block text-xs font-bold text-on-surface-variant uppercase">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" id="startDateInput" onchange="calculateDuration()" required class="py-2 px-3 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant" />
                </div>
                <div class="space-y-1.5">
                    <label for="endDateInput" class="block text-xs font-bold text-on-surface-variant uppercase">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" id="endDateInput" onchange="calculateDuration()" required class="py-2 px-3 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant" />
                </div>
            </div>

            <!-- Duration Indicator -->
            <div class="bg-surface p-3.5 rounded-xl border border-outline-variant/30 flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-primary font-bold">schedule</span> Durasi Pengajuan
                </span>
                <span id="calculatedDuration" class="text-on-surface-variant/40 font-semibold text-xs">0 Hari</span>
            </div>

            <!-- Reason -->
            <div class="space-y-1.5">
                <label for="leaveReason" class="block text-xs font-bold text-on-surface-variant uppercase">Alasan Cuti / Deskripsi Pengajuan <span class="text-red-500">*</span></label>
                <textarea name="reason" id="leaveReason" rows="3" placeholder="Jelaskan secara mendetail alasan pengajuan cuti atau keperluan izin Anda..." required class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface"></textarea>
            </div>

            <!-- File Upload Dropzone -->
            <div class="space-y-1.5" id="attachmentContainer">
                <label id="attachmentLabel" class="block text-xs font-bold text-on-surface-variant uppercase">Unggah Berkas / Dokumen Pendukung <span class="text-on-surface-variant/50 text-[10px] font-semibold font-sans lowercase">(opsional)</span></label>
                <div id="dropzone" class="border-2 border-dashed border-outline-variant/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-2.5 hover:border-primary hover:bg-primary/5 transition-all cursor-pointer relative group">
                    <input type="file" name="attachment" id="leaveAttachment" accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="handleFileSelect(event)" />
                    <span class="material-symbols-outlined text-3xl text-outline-variant group-hover:text-primary transition-colors">cloud_upload</span>
                    <div class="text-center">
                        <p class="text-xs font-bold text-on-surface" id="uploadFilename">Tarik & lepas berkas di sini, atau klik untuk mencari</p>
                        <p class="text-[10px] text-on-surface-variant/80 font-semibold mt-1">Format didukung: JPG, PNG, atau PDF (Ukuran maksimal: 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-outline-variant/15 mt-6">
                <button type="button" onclick="closeLeaveModal()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-5 rounded-xl transition-all shadow-md shadow-primary/10 cursor-pointer">
                    Ajukan Permohonan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Table Pagination & Filter Logic ─────────    // Pagination state
    let leaveCurrentPage = 1;
    const leaveRowsPerPage = 10;
    
    function renderLeavePagination() {
        const allRows = Array.from(document.querySelectorAll('#employeeLeavesTableBody tr.leave-table-row'));
        const type = document.getElementById('employeeLeaveTypeFilter').value;
        const status = document.getElementById('employeeLeaveStatusFilter').value;
        
        let visibleRows = [];
        allRows.forEach(row => {
            const typeAttr = row.getAttribute('data-type');
            const statusAttr = row.getAttribute('data-status');
            const matchesType = !type || typeAttr === type;
            const matchesStatus = !status || statusAttr === status;
            
            if (matchesType && matchesStatus) {
                visibleRows.push(row);
                row.style.display = ''; 
            } else {
                row.style.display = 'none';
            }
        });
        
        const totalPages = Math.ceil(visibleRows.length / leaveRowsPerPage);
        
        const paginationControls = document.getElementById('leavePaginationControls');
        if (visibleRows.length === 0 || totalPages <= 1) {
            if (paginationControls) paginationControls.classList.add('hidden');
            return;
        }
        
        if (paginationControls) paginationControls.classList.remove('hidden');
        
        if (leaveCurrentPage > totalPages) leaveCurrentPage = totalPages;
        if (leaveCurrentPage < 1) leaveCurrentPage = 1;
        
        const start = (leaveCurrentPage - 1) * leaveRowsPerPage;
        const end = start + leaveRowsPerPage;
        
        visibleRows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        const prevBtn = document.getElementById('btnLeavePrevPage');
        const nextBtn = document.getElementById('btnLeaveNextPage');
        if (prevBtn) prevBtn.disabled = leaveCurrentPage === 1;
        if (nextBtn) nextBtn.disabled = leaveCurrentPage === totalPages;
    }

    // Bind event listeners for leave pagination
    document.addEventListener('DOMContentLoaded', () => {
        const prevBtn = document.getElementById('btnLeavePrevPage');
        const nextBtn = document.getElementById('btnLeaveNextPage');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => { 
                if (leaveCurrentPage > 1) { 
                    leaveCurrentPage--; 
                    renderLeavePagination(); 
                } 
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => { 
                const allRows = Array.from(document.querySelectorAll('#employeeLeavesTableBody tr.leave-table-row'));
                const type = document.getElementById('employeeLeaveTypeFilter').value;
                const status = document.getElementById('employeeLeaveStatusFilter').value;
                const visibleCount = allRows.filter(r => (!type || r.getAttribute('data-type') === type) && (!status || r.getAttribute('data-status') === status)).length;
                
                if (leaveCurrentPage < Math.ceil(visibleCount / leaveRowsPerPage)) { 
                    leaveCurrentPage++; 
                    renderLeavePagination(); 
                } 
            });
        }
    });

    function filterLeavesTable() {
        leaveCurrentPage = 1;
        renderLeavePagination();
    }

    // Modal Controls
    function openLeaveModal() {
        const modal = document.getElementById('leaveModal');
        const container = document.getElementById('leaveModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }

    function closeLeaveModal() {
        const modal = document.getElementById('leaveModal');
        const container = document.getElementById('leaveModalContainer');
        modal.classList.add('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        document.getElementById('leaveSubmitForm').reset();
        document.getElementById('calculatedDuration').textContent = '0 Hari';
        document.getElementById('calculatedDuration').className = 'text-on-surface-variant/40 font-semibold text-xs';
        document.getElementById('uploadFilename').innerHTML = 'Tarik & lepas berkas di sini, atau klik untuk mencari';
        document.getElementById('leaveAttachment').required = false;
        document.getElementById('attachmentLabel').innerHTML = 'Unggah Berkas / Dokumen Pendukung <span class="text-on-surface-variant/50 text-[10px] font-semibold font-sans lowercase">(opsional)</span>';
        
        const endDateInput = document.getElementById('endDateInput');
        endDateInput.readOnly = false;
        endDateInput.disabled = false;
        endDateInput.classList.remove('bg-surface-container-low', 'cursor-not-allowed', 'opacity-70');
    }

    // Toggle attachment upload block based on selected leave type
    function toggleAttachmentField() {
        const type = document.getElementById('leaveTypeSelect').value;
        const label = document.getElementById('attachmentLabel');
        const fileInput = document.getElementById('leaveAttachment');
        const endDateInput = document.getElementById('endDateInput');

        if (type === 'cuti sakit') {
            label.innerHTML = 'Unggah Surat Keterangan Dokter <span class="text-red-500 text-[10px] font-bold font-sans lowercase">(wajib)</span>';
            fileInput.required = true;
            endDateInput.readOnly = false;
            endDateInput.disabled = false;
            endDateInput.classList.remove('bg-surface-container-low', 'cursor-not-allowed', 'opacity-70');
        } else if (type === 'cuti melahirkan') {
            label.innerHTML = 'Unggah Surat Rujukan HPL / Medis <span class="text-red-500 text-[10px] font-bold font-sans lowercase">(wajib)</span>';
            fileInput.required = true;
            endDateInput.readOnly = true;
            endDateInput.classList.add('bg-surface-container-low', 'cursor-not-allowed', 'opacity-70');
        } else {
            label.innerHTML = 'Unggah Berkas / Dokumen Pendukung <span class="text-on-surface-variant/50 text-[10px] font-semibold font-sans lowercase">(opsional)</span>';
            fileInput.required = false;
            endDateInput.readOnly = false;
            endDateInput.disabled = false;
            endDateInput.classList.remove('bg-surface-container-low', 'cursor-not-allowed', 'opacity-70');
        }
        
        calculateDuration();
    }

    // Live calculate calendar days duration
    function calculateDuration() {
        const type = document.getElementById('leaveTypeSelect').value;
        const startVal = document.getElementById('startDateInput').value;
        const endDateInput = document.getElementById('endDateInput');
        const durationText = document.getElementById('calculatedDuration');

        if (type === 'cuti melahirkan') {
            if (startVal) {
                const start = new Date(startVal);
                const end = new Date(start);
                end.setDate(start.getDate() + 89);
                
                const yyyy = end.getFullYear();
                const mm = String(end.getMonth() + 1).padStart(2, '0');
                const dd = String(end.getDate()).padStart(2, '0');
                
                endDateInput.value = `${yyyy}-${mm}-${dd}`;
                endDateInput.readOnly = true;
                endDateInput.classList.add('bg-surface-container-low', 'cursor-not-allowed', 'opacity-70');
                
                durationText.textContent = '90 Hari';
                durationText.className = 'text-primary font-extrabold text-sm';
            } else {
                endDateInput.value = '';
                durationText.textContent = '0 Hari';
                durationText.className = 'text-on-surface-variant/40 font-semibold text-xs';
            }
            return;
        }

        const endVal = endDateInput.value;
        if (startVal && endVal) {
            const start = new Date(startVal);
            const end = new Date(endVal);

            if (end >= start) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                durationText.textContent = diffDays + ' Hari';
                durationText.className = 'text-primary font-extrabold text-sm';
            } else {
                durationText.textContent = 'Tanggal selesai harus setelah tanggal mulai';
                durationText.className = 'text-red-600 font-semibold text-[11px] text-right max-w-[200px]';
            }
        } else {
            durationText.textContent = '0 Hari';
            durationText.className = 'text-on-surface-variant/40 font-semibold text-xs';
        }
    }

    // File input validation & client feedback
    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        // 10MB check
        const maxLimit = 10 * 1024 * 1024;
        if (file.size > maxLimit) {
            Swal.fire({
                title: 'Ukuran Berkas Terlalu Besar',
                text: 'Batas maksimum ukuran berkas bukti medis/dokter adalah 10MB.',
                icon: 'warning',
                confirmButtonColor: '#ba1a1a'
            });
            e.target.value = '';
            document.getElementById('uploadFilename').innerHTML = 'Tarik & lepas berkas di sini, atau klik untuk mencari';
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                title: 'Format Berkas Tidak Didukung',
                text: 'Hanya format JPG, JPEG, PNG, dan PDF resmi yang diperbolehkan demi keamanan database.',
                icon: 'error',
                confirmButtonColor: '#ba1a1a'
            });
            e.target.value = '';
            document.getElementById('uploadFilename').innerHTML = 'Tarik & lepas berkas di sini, atau klik untuk mencari';
            return;
        }

        document.getElementById('uploadFilename').innerHTML = `
            <span class="text-primary font-extrabold flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-sm">check_circle</span> ${file.name}
            </span> 
            <span class="text-[10px] text-on-surface-variant">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
        `;
    }

    // Show detailed rejection logs via SweetAlert2
    function showRejectionReason(reason) {
        Swal.fire({
            title: 'Alasan Penolakan Permohonan',
            text: reason,
            icon: 'info',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#000666'
        });
    }

    // Submit leave form via AJAX
    function submitLeaveForm(e) {
        e.preventDefault();

        const form = document.getElementById('leaveSubmitForm');
        const formData = new FormData(form);

        const type = document.getElementById('leaveTypeSelect').value;
        const fileInput = document.getElementById('leaveAttachment');

        if ((type === 'cuti sakit' || type === 'cuti melahirkan') && !fileInput.value) {
            Swal.fire({
                title: 'Dokumen Wajib Diunggah',
                text: type === 'cuti sakit' ? 'Silakan unggah Surat Keterangan Dokter terlebih dahulu.' : 'Silakan unggah Surat Rujukan HPL / Medis terlebih dahulu.',
                icon: 'warning',
                confirmButtonColor: '#ba1a1a'
            });
            return;
        }

        const startVal = document.getElementById('startDateInput').value;
        const endVal = document.getElementById('endDateInput').value;
        const start = new Date(startVal);
        const end = new Date(endVal);

        if (end < start) {
            Swal.fire({
                title: 'Rentang Tanggal Tidak Valid',
                text: 'Urutan tanggal tidak konsisten. Periksa tanggal mulai dan selesai.',
                icon: 'error',
                confirmButtonColor: '#ba1a1a'
            });
            return;
        }

        Swal.fire({
            title: 'Mengirim Pengajuan...',
            text: 'Memproses validasi jatah cuti, memverifikasi tanda tangan digital berkas medis, dan mendaftarkan permohonan.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/employee/leaves/submit', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil Diajukan!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    closeLeaveModal();
                    if (window.loadPage) {
                        window.loadPage('/employee/leaves');
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Pengajuan Cuti Gagal',
                    text: data.message || 'Terjadi kesalahan sistem internal.',
                    icon: 'error',
                    confirmButtonColor: '#ba1a1a'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                title: 'Kesalahan Sistem',
                text: 'Koneksi ke server terputus. Silakan coba sesaat lagi.',
                icon: 'error',
                confirmButtonColor: '#ba1a1a'
            });
            console.error(err);
        });
    }

    // Cancel pending leave request
    function cancelLeave(id, duration) {
        Swal.fire({
            title: 'Batalkan Pengajuan?',
            text: `Apakah Anda yakin ingin membatalkan pengajuan cuti selama ${duration} hari? Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Membatalkan...',
                    text: 'Sedang menghapus pengajuan...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData();
                formData.append('id', id);

                fetch('/employee/leaves/cancel', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil Dibatalkan',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => {
                            if (window.loadPage) {
                                window.loadPage('/employee/leaves');
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal Membatalkan',
                            text: data.message || 'Terjadi kesalahan saat memproses pembatalan.',
                            icon: 'error',
                            confirmButtonColor: '#ba1a1a'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Kesalahan Sistem',
                        text: 'Koneksi ke server terputus. Silakan coba sesaat lagi.',
                        icon: 'error',
                        confirmButtonColor: '#ba1a1a'
                    });
                    console.error(err);
                });
            }
        });
    }

    // Dynamic, secure document previewer with iframe/image routing
    function viewAttachment(filename, title) {
        const url = `/hrops/leaves/view_attachment?file=${filename}`;
        window.viewAttachmentGlobal(url, title, 'Dokumen di-stream aman dengan verifikasi enkripsi server.');
    }

    // Expose all functions to global scope to prevent IIFE isolation issues
    window.renderLeavePagination = renderLeavePagination;
    window.filterLeavesTable = filterLeavesTable;
    window.openLeaveModal = openLeaveModal;
    window.closeLeaveModal = closeLeaveModal;
    window.toggleAttachmentField = toggleAttachmentField;
    window.calculateDuration = calculateDuration;
    window.handleFileSelect = handleFileSelect;
    window.showRejectionReason = showRejectionReason;
    window.submitLeaveForm = submitLeaveForm;
    window.cancelLeave = cancelLeave;
    window.viewAttachment = viewAttachment;

    // Initialize on load
    setTimeout(renderLeavePagination, 100);
</script>