<?php
// HR Operations Leaves Dynamic Dashboard
$db = \App\Config\Database::getInstance()->getConnection();

// Fetch counts and sums for KPIs
// 1. Pending requests
$stmt = $db->query("SELECT COUNT(*) FROM employee_leave_requests WHERE status = 'pending'");
$pendingCount = intval($stmt->fetchColumn());

// 2. Active out-of-office today (approved and today is between start_date and end_date)
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) FROM employee_leave_requests WHERE status = 'approved' AND :today BETWEEN start_date AND end_date");
$stmt->execute(['today' => $today]);
$activeToday = intval($stmt->fetchColumn());

// 3. Approved this month
$currentMonth = date('Y-m');
$stmt = $db->prepare("SELECT COUNT(*) FROM employee_leave_requests WHERE status = 'approved' AND DATE_FORMAT(start_date, '%Y-%m') = :current_month");
$stmt->execute(['current_month' => $currentMonth]);
$approvedThisMonth = intval($stmt->fetchColumn());

// 4. Company Leave Quota average & sum
$stmt = $db->query("SELECT SUM(annual_leave_quota) FROM users WHERE role = 'employee'");
$totalQuota = intval($stmt->fetchColumn());

$stmt = $db->query("SELECT AVG(annual_leave_quota) FROM users WHERE role = 'employee'");
$avgQuota = round(floatval($stmt->fetchColumn()), 1);

// Fetch all leave requests
$stmt = $db->query("
    SELECT r.*, u.first_name, u.last_name, u.employee_id, u.role, u.email, u.profile_picture
    FROM employee_leave_requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
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

function getEmployeePosition($email) {
    if (strpos($email, 'employee@mail.com') !== false || strpos($email, 'alex') !== false) {
        return 'Senior UI/UX Designer';
    } elseif (strpos($email, 'rian') !== false) {
        return 'DevOps Engineer';
    } elseif (strpos($email, 'budi') !== false) {
        return 'Software Engineer';
    } elseif (strpos($email, 'siti') !== false) {
        return 'QA Engineer';
    } elseif (strpos($email, 'amanda') !== false) {
        return 'UI/UX Designer';
    } elseif (strpos($email, 'farhan') !== false) {
        return 'Product Owner';
    }
    return 'Staff Karyawan';
}

function formatIndonesianDate($dateStr) {
    if (empty($dateStr)) return '';
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return $dateStr;
    
    $months = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $day = date('d', $timestamp);
    $monthNum = intval(date('m', $timestamp));
    $year = date('Y', $timestamp);
    
    return $day . ' ' . $months[$monthNum] . ' ' . $year;
}
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Cuti Karyawan</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola, setujui, atau tolak permohonan cuti dan izin staf perusahaan secara terpusat.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="Swal.fire({title: 'Konfigurasi Kebijakan Cuti', text: 'Konfigurasi jatah cuti tahunan berhasil disinkronisasikan secara terpusat.', icon: 'success', confirmButtonColor: '#000666'})" class="bg-surface-container-high hover:bg-surface-container-high/80 text-primary font-bold text-xs py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm">settings_accessibility</span> Atur Kebijakan Cuti
            </button>
            <button onclick="showCreateLeaveModal()" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all shadow-md shadow-primary/10 cursor-pointer">
                <span class="material-symbols-outlined text-sm">add</span> Daftarkan Cuti Staf
            </button>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Menunggu Persetujuan</span>
                <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-2 rounded-xl text-sm font-bold <?= $pendingCount > 0 ? 'animate-pulse' : '' ?>">pending_actions</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-amber-700"><?= $pendingCount ?> <span class="text-xs font-semibold text-amber-600">Pengajuan</span></h3>
                <p class="text-[10px] text-amber-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">hourglass_empty</span> Butuh persetujuan segera
                </p>
            </div>
        </div>
        <!-- Card 2 -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Sedang Cuti Hari Ini</span>
                <span class="material-symbols-outlined text-indigo-600 bg-indigo-50 p-2 rounded-xl text-sm font-bold">flight_takeoff</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-indigo-700"><?= $activeToday ?> <span class="text-xs font-semibold text-indigo-600">Staf</span></h3>
                <p class="text-[10px] text-indigo-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">info</span> Staf out-of-office aktif
                </p>
            </div>
        </div>
        <!-- Card 3 -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Disetujui Bulan Ini</span>
                <span class="material-symbols-outlined text-green-600 bg-green-50 p-2 rounded-xl text-sm font-bold">verified</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-green-700"><?= $approvedThisMonth ?> <span class="text-xs font-semibold text-green-600">Disetujui</span></h3>
                <p class="text-[10px] text-green-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">thumb_up</span> Tertib administrasi HRIS
                </p>
            </div>
        </div>
        <!-- Card 4 -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Kuota Cuti Perusahaan</span>
                <span class="material-symbols-outlined text-primary bg-primary/5 p-2 rounded-xl text-sm font-bold">calendar_month</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-on-surface"><?= number_format($totalQuota, 0, ',', '.') ?> <span class="text-xs font-semibold text-on-surface-variant">Hari</span></h3>
                <p class="text-[10px] text-on-surface-variant font-semibold mt-1 flex items-center gap-1">
                    Rata-rata sisa cuti: <?= $avgQuota ?> hari/staf
                </p>
            </div>
        </div>
    </div>

    <!-- Filters & Requests List Panel -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        
        <!-- Filter Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary font-bold">pageview</span>
                <h2 class="text-lg font-extrabold text-on-surface">Pengajuan & Status</h2>
            </div>
            
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <!-- Search Input -->
                <div class="relative w-full md:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-on-surface-variant/50 text-sm">search</span>
                    </span>
                    <input type="text" id="leaveSearch" onkeyup="filterLeaveTable()" placeholder="Cari nama karyawan..." class="pl-9 pr-4 py-2 w-full text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-on-surface font-semibold" />
                </div>
                
                <!-- Status Filter -->
                <select id="leaveStatusFilter" onchange="filterLeaveTable()" class="py-2 px-3 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending (Menunggu)</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>

                <!-- Type Filter -->
                <select id="leaveTypeFilter" onchange="filterLeaveTable()" class="py-2 px-3 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant">
                    <option value="">Semua Tipe Cuti</option>
                    <option value="cuti tahunan">Cuti Tahunan</option>
                    <option value="cuti sakit">Cuti Sakit</option>
                    <option value="cuti melahirkan">Cuti Melahirkan</option>
                    <option value="izin khusus">Izin Khusus</option>
                </select>
            </div>
        </div>

        <!-- Leave Requests Table -->
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Karyawan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Jenis Cuti</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Durasi & Tanggal</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Alasan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Berkas Bukti</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="leaveTableBody" class="divide-y divide-outline-variant/10">
                    <?php if (empty($leaves)): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-on-surface-variant font-medium text-xs">
                            <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">inbox</span>
                            <p>Tidak ada pengajuan cuti/izin di database.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($leaves as $leave): 
                        $leaveFirstName = (string)($leave['first_name'] ?? '');
                        $leaveLastName = (string)($leave['last_name'] ?? '');
                        $fullname = trim($leaveFirstName . ' ' . $leaveLastName);
                        $initials = strtoupper(
                            ($leaveFirstName !== '' ? substr($leaveFirstName, 0, 1) : '') .
                            ($leaveLastName !== '' ? substr($leaveLastName, 0, 1) : '')
                        );
                    ?>
                    <tr class="hover:bg-surface-container-low/30 transition-colors" data-name="<?= htmlspecialchars(strtolower($fullname)) ?>" data-status="<?= htmlspecialchars($leave['status']) ?>" data-type="<?= htmlspecialchars($leave['leave_type']) ?>">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3 w-52 min-w-[200px]">
                                <?php 
                                    $profPic = $leave['profile_picture'];
                                    $hash = md5(strtolower(trim($leave['email'])));
                                    if (empty($profPic)) {
                                        $profPic = "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
                                    }
                                ?>
                                <img src="<?= htmlspecialchars($profPic) ?>" onerror="window.handleAvatarError(this, '<?= $hash ?>')" alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-outline-variant/15" />
                                <div>
                                    <div class="font-extrabold text-sm text-on-surface"><?= htmlspecialchars($fullname) ?></div>
                                    <div class="text-[11px] text-on-surface-variant font-semibold"><?= getEmployeePosition($leave['email']) ?> • <span class="font-mono text-primary font-bold"><?= htmlspecialchars($leave['employee_id'] ?? '') ?></span></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold border <?= getLeaveTypeColor($leave['leave_type']) ?>">
                                <?= getLeaveTypeLabel($leave['leave_type']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-bold text-xs text-on-surface"><?= $leave['duration'] ?> Hari</div>
                            <div class="text-[10px] text-on-surface-variant font-mono font-bold mt-0.5"><?= formatIndonesianDate($leave['start_date']) ?> - <?= formatIndonesianDate($leave['end_date']) ?></div>
                        </td>
                        <td class="py-4 px-6 min-w-[380px]">
                            <div class="text-xs text-on-surface font-semibold whitespace-normal break-words leading-relaxed" title="<?= htmlspecialchars($leave['reason']) ?>"><?= htmlspecialchars($leave['reason']) ?></div>
                        </td>
                        <td class="py-4 px-6 whitespace-nowrap">
                            <?php if (!empty($leave['attachment_path'])): ?>
                            <?php 
                                $attachmentPath = (string)$leave['attachment_path'];
                            ?>
                            <button onclick="viewAttachment('<?= htmlspecialchars($attachmentPath) ?>', 'Berkas Cuti <?= htmlspecialchars(addslashes($fullname)) ?>')" class="text-[10px] text-primary hover:underline font-bold flex items-center gap-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-xs">attach_file</span> <?= htmlspecialchars(strlen($attachmentPath) > 25 ? substr($attachmentPath, 0, 10) . '...' . substr($attachmentPath, -8) : $attachmentPath) ?>
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
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="rejectLeave('<?= $leave['id'] ?>', '<?= htmlspecialchars(addslashes($fullname)) ?>')" class="bg-red-50 hover:bg-red-100 text-red-700 font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-red-200 transition-colors cursor-pointer">
                                    Tolak
                                </button>
                                <button onclick="approveLeave('<?= $leave['id'] ?>', '<?= htmlspecialchars(addslashes($fullname)) ?>', <?= $leave['duration'] ?>)" class="bg-green-50 hover:bg-green-100 text-green-700 font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-green-200 transition-colors cursor-pointer">
                                    Setujui
                                </button>
                            </div>
                            <?php elseif ($leave['status'] === 'approved'): ?>
                            <span class="text-xs text-on-surface-variant/40 font-bold italic">Selesai</span>
                            <?php else: ?>
                            <button onclick="Swal.fire({title: 'Detail Penolakan', text: '<?= htmlspecialchars(addslashes($leave['rejection_reason'] ?? 'Tidak ada alasan.')) ?>', icon: 'info', confirmButtonColor: '#000666'})" class="text-xs font-bold text-on-surface-variant hover:text-on-surface hover:underline flex items-center gap-1 justify-end ml-auto cursor-pointer">
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


    </div>
</div>

<script>
    // Search and filters
    window.filterLeaveTable = function filterLeaveTable() {
        const query = document.getElementById('leaveSearch').value.toLowerCase().trim();
        const status = document.getElementById('leaveStatusFilter').value.toLowerCase();
        const type = document.getElementById('leaveTypeFilter').value.toLowerCase();
        const rows = document.querySelectorAll('#leaveTableBody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.cells.length <= 1) return; // Fallback empty row
            
            const nameAttr = row.getAttribute('data-name');
            const statusAttr = row.getAttribute('data-status');
            const typeAttr = row.getAttribute('data-type');
            
            const matchesSearch = nameAttr.includes(query);
            const matchesStatus = !status || statusAttr === status;
            const matchesType = !type || typeAttr === type;

            if (matchesSearch && matchesStatus && matchesType) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('displayedCount').textContent = visibleCount;
    }

    // Approve Leave action
    window.approveLeave = function approveLeave(id, name, days) {
        Swal.fire({
            title: 'Setujui Pengajuan Cuti?',
            text: `Apakah Anda ingin menyetujui cuti selama ${days} hari untuk ${name}? Kuota cuti tahunan karyawan akan didebet secara otomatis jika bertipe Cuti Tahunan.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2e7d32',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const formData = new FormData();
                formData.append('id', id);
                return fetch('/hrops/leaves/approve', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Gagal menyetujui permohonan cuti.');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Error: ${error.message}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Cuti Disetujui!',
                    text: `Permohonan cuti ${name} telah berhasil disetujui. Kuota dipotong secara otomatis.`,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    if (window.loadPage) {
                        window.loadPage('/hrops/leaves');
                    } else {
                        window.location.reload();
                    }
                });
            }
        });
    }

    // Reject Leave action
    window.rejectLeave = function rejectLeave(id, name) {
        Swal.fire({
            title: `Tolak Cuti - ${name}`,
            input: 'textarea',
            inputLabel: 'Alasan Penolakan Cuti',
            inputPlaceholder: 'Tulis alasan penolakan cuti di sini...',
            inputAttributes: {
                'aria-label': 'Tulis alasan penolakan cuti di sini'
            },
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Tolak Pengajuan',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan wajib diisi agar karyawan mendapat penjelasan.';
                }
            },
            showLoaderOnConfirm: true,
            preConfirm: (value) => {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('rejection_reason', value);
                return fetch('/hrops/leaves/reject', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Gagal menolak permohonan cuti.');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Error: ${error.message}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Cuti Ditolak',
                    text: `Pengajuan cuti ${name} telah ditolak dengan sukses.`,
                    icon: 'error',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    if (window.loadPage) {
                        window.loadPage('/hrops/leaves');
                    } else {
                        window.location.reload();
                    }
                });
            }
        });
    }

    // View Attachment dialog
    window.viewAttachment = function viewAttachment(filename, title) {
        const url = `/hrops/leaves/view_attachment?file=${filename}`;
        window.viewAttachmentGlobal(url, title, 'MIME-type divalidasi server menggunakan PHP finfo (Aman & Terenkripsi).');
    }

    // Register leave manual form info
    window.showCreateLeaveModal = function showCreateLeaveModal() {
        Swal.fire({
            title: 'Daftarkan Cuti Karyawan',
            text: 'Fitur pendaftaran manual cuti staf secara langsung oleh HR Operations diintegrasikan langsung melalui antarmuka ESS Karyawan demi ketertiban rekam berkas medis & penanggung jawab masing-masing divisi.',
            icon: 'info',
            confirmButtonColor: '#000666',
            confirmButtonText: 'Kembali'
        });
    }
</script>
