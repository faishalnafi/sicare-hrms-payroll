<?php
// HR Operations Reimbursements Dynamic Dashboard
$db = \App\Config\Database::getInstance()->getConnection();

// Fetch counts and sums for KPIs
$stmt = $db->query("SELECT SUM(amount) FROM employee_reimbursement_claims");
$totalSubmitted = floatval($stmt->fetchColumn());

$stmt = $db->query("SELECT COUNT(*) FROM employee_reimbursement_claims WHERE status = 'pending'");
$pendingCount = intval($stmt->fetchColumn());

$stmt = $db->query("SELECT SUM(amount) FROM employee_reimbursement_claims WHERE status = 'approved'");
$totalApproved = floatval($stmt->fetchColumn());

// Calculate dynamic global available percentage
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'employee'");
$employeeCount = max(1, intval($stmt->fetchColumn()));
$globalInitial = $employeeCount * 14500000.00; // Rp 14.5jt per employee global limit

$globalAvailable = $globalInitial - $totalApproved;
$globalAvailablePercent = round(($globalAvailable / $globalInitial) * 100, 1);

// Fetch all claims from database
$stmt = $db->query("
    SELECT c.*, u.first_name, u.last_name, u.employee_id, u.role, u.email, u.profile_picture
    FROM employee_reimbursement_claims c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$claims = $stmt->fetchAll();

function getCategoryColor($category) {
    $colors = [
        'medis' => 'text-teal-700 bg-teal-50 border-teal-200',
        'transport' => 'text-blue-700 bg-blue-50 border-blue-200',
        'operasional' => 'text-purple-700 bg-purple-50 border-purple-200',
        'makan' => 'text-amber-700 bg-amber-50 border-amber-200'
    ];
    return $colors[$category] ?? 'text-gray-700 bg-gray-50 border-gray-200';
}

function getCategoryLabel($category) {
    $labels = [
        'medis' => 'Kesehatan & Medis',
        'transport' => 'Transportasi & Tol',
        'operasional' => 'Alat Kerja & Operasional',
        'makan' => 'Makan & Bisnis'
    ];
    return $labels[$category] ?? $category;
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
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Reimbursement Karyawan</h1>
            <p class="text-on-surface-variant font-medium text-sm">Verifikasi, review, dan kelola klaim pengembalian dana operasional, medis, atau perjalanan dinas karyawan.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="Swal.fire({title: 'Kebijakan Anggaran', text: 'Konfigurasi batas maksimal plafon otomatis didistribusikan per karyawan: Medis Rp 5jt, Transport Rp 3jt, Operasional Rp 4jt, Makan Rp 2.5jt.', icon: 'info', confirmButtonColor: '#000666'})" class="bg-surface-container-high hover:bg-surface-container-high/80 text-primary font-bold text-xs py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm">payments</span> Info Plafon Klaim
            </button>
            <button onclick="Swal.fire({title: 'Sinkronisasi Sukses', text: 'Seluruh data klaim yang disetujui (Approved) telah disinkronkan ke dalam siklus penggajian payroll bulan ini.', icon: 'success', confirmButtonColor: '#000666'})" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all shadow-md shadow-primary/10 cursor-pointer">
                <span class="material-symbols-outlined text-sm">account_balance</span> Sinkronisasi Penggajian
            </button>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Dana Diajukan</span>
                <span class="material-symbols-outlined text-primary bg-primary/5 p-2 rounded-xl text-sm font-bold">monetization_on</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-on-surface">Rp <?= number_format($totalSubmitted, 0, ',', '.') ?></h3>
                <p class="text-[10px] text-on-surface-variant font-semibold mt-1 flex items-center gap-1">
                    Bulan berjalan (Mei 2026)
                </p>
            </div>
        </div>
        <!-- Card 2 -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Pending Review</span>
                <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-2 rounded-xl text-sm font-bold <?= $pendingCount > 0 ? 'animate-pulse' : '' ?>">receipt_long</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-amber-700" id="hropsPendingCount"><?= $pendingCount ?> <span class="text-xs font-semibold text-amber-600">Klaim</span></h3>
                <p class="text-[10px] text-amber-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">hourglass_empty</span> Butuh verifikasi berkas nota
                </p>
            </div>
        </div>
        <!-- Card 3 -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Disetujui Bulan Ini</span>
                <span class="material-symbols-outlined text-green-600 bg-green-50 p-2 rounded-xl text-sm font-bold">price_check</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-green-700">Rp <?= number_format($totalApproved, 0, ',', '.') ?></h3>
                <p class="text-[10px] text-green-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">done_all</span> Telah diproses ke modul payroll
                </p>
            </div>
        </div>
        <!-- Card 4 -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Sisa Plafon Global</span>
                <span class="material-symbols-outlined text-indigo-600 bg-indigo-50 p-2 rounded-xl text-sm font-bold">account_balance_wallet</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-indigo-700"><?= $globalAvailablePercent ?>% <span class="text-xs font-semibold text-indigo-600">Tersedia</span></h3>
                <p class="text-[10px] text-indigo-600 font-semibold mt-1 flex items-center gap-1">
                    Kuota sisa: Rp <?= number_format($globalAvailable, 0, ',', '.') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Filters & Claims List Panel -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        
        <!-- Filter Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary font-bold">find_in_page</span>
                <h2 class="text-lg font-extrabold text-on-surface">Data Klaim Keuangan</h2>
            </div>
            
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <!-- Search Input -->
                <div class="relative w-full md:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-on-surface-variant/50 text-sm">search</span>
                    </span>
                    <input type="text" id="reimbursementSearch" onkeyup="filterReimbursementTable()" placeholder="Cari nama karyawan..." class="pl-9 pr-4 py-2 w-full text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-on-surface font-semibold" />
                </div>
                
                <!-- Status Filter -->
                <select id="reimbursementStatusFilter" onchange="filterReimbursementTable()" class="py-2 px-3 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending Review</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>

                <!-- Category Filter -->
                <select id="reimbursementCategoryFilter" onchange="filterReimbursementTable()" class="py-2 px-3 text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant">
                    <option value="">Semua Kategori</option>
                    <option value="medis">Kesehatan & Medis</option>
                    <option value="transport">Transportasi & Tol</option>
                    <option value="operasional">Alat Kerja & Operasional</option>
                    <option value="makan">Makan & Bisnis</option>
                </select>
            </div>
        </div>

        <!-- Reimbursements Requests Table -->
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Karyawan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Kategori</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Jumlah Klaim</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Keterangan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Bukti Nota</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reimbursementTableBody" class="divide-y divide-outline-variant/10">
                    <?php if (empty($claims)): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-on-surface-variant font-medium text-xs">
                            <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">inbox</span>
                            <p>Tidak ada pengajuan reimbursement di database.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($claims as $claim): 
                        $claimFirstName = (string)($claim['first_name'] ?? '');
                        $claimLastName = (string)($claim['last_name'] ?? '');
                        $fullname = trim($claimFirstName . ' ' . $claimLastName);
                        $hash = md5(strtolower(trim($claim['email'] ?? '')));
                        $avatarUrl = !empty($claim['profile_picture']) ? $claim['profile_picture'] : "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=120";
                    ?>
                    <tr class="hover:bg-surface-container-low/30 transition-colors" data-name="<?= htmlspecialchars(strtolower($fullname)) ?>" data-status="<?= htmlspecialchars($claim['status']) ?>" data-category="<?= htmlspecialchars($claim['category']) ?>">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3 w-52 min-w-[200px]">
                                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-outline-variant/30 flex-shrink-0" onerror="window.handleAvatarError(this, '<?= $hash ?>')" />
                                <div class="min-w-0">
                                    <div class="font-extrabold text-sm text-on-surface line-clamp-2 whitespace-normal break-words leading-tight" title="<?= htmlspecialchars($fullname) ?>"><?= htmlspecialchars($fullname) ?></div>
                                    <div class="text-[10px] text-on-surface-variant font-semibold truncate" title="<?= getEmployeePosition($claim['email']) ?>"><?= getEmployeePosition($claim['email']) ?></div>
                                    <div class="text-[9px] text-primary font-bold font-mono mt-0.5"><?= htmlspecialchars($claim['employee_id'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold border <?= getCategoryColor($claim['category']) ?>">
                                <?= getCategoryLabel($claim['category']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 font-mono text-sm font-bold text-on-surface whitespace-nowrap">
                            Rp <?= number_format($claim['amount'], 0, ',', '.') ?>
                        </td>
                        <td class="py-4 px-6 min-w-[380px]">
                            <p class="text-xs font-semibold text-on-surface-variant leading-relaxed break-words" title="<?= htmlspecialchars($claim['description']) ?>"><?= htmlspecialchars($claim['description']) ?></p>
                        </td>
                        <td class="py-4 px-6 whitespace-nowrap">
                            <?php 
                                $receiptPath = (string)($claim['receipt_path'] ?? ''); 
                            ?>
                            <button onclick="viewReceipt('<?= htmlspecialchars($receiptPath) ?>', 'Kuitansi <?= htmlspecialchars(addslashes($fullname)) ?>', 'Rp <?= number_format($claim['amount'], 0, ',', '.') ?>')" class="text-[10px] text-primary hover:underline font-bold flex items-center gap-0.5 cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-xs">receipt</span> <?= htmlspecialchars(strlen($receiptPath) > 25 ? substr($receiptPath, 0, 10) . '...' . substr($receiptPath, -8) : $receiptPath) ?>
                            </button>
                        </td>
                        <td class="py-4 px-6">
                            <?php if ($claim['status'] === 'pending'): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending Review
                            </span>
                            <?php elseif ($claim['status'] === 'approved'): ?>
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
                            <?php if ($claim['status'] === 'pending'): ?>
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="rejectClaim('<?= $claim['id'] ?>', '<?= htmlspecialchars(addslashes($fullname)) ?>', 'Rp <?= number_format($claim['amount'], 0, ',', '.') ?>')" class="bg-red-50 hover:bg-red-100 text-red-700 font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-red-200 transition-colors cursor-pointer">
                                    Tolak
                                </button>
                                <button onclick="approveClaim('<?= $claim['id'] ?>', '<?= htmlspecialchars(addslashes($fullname)) ?>', 'Rp <?= number_format($claim['amount'], 0, ',', '.') ?>')" class="bg-green-50 hover:bg-green-100 text-green-700 font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-green-200 transition-colors cursor-pointer">
                                    Setujui
                                </button>
                            </div>
                            <?php elseif ($claim['status'] === 'approved'): ?>
                            <span class="text-xs text-on-surface-variant/40 font-bold italic">Telah Ditransfer</span>
                            <?php else: ?>
                            <button onclick="Swal.fire({title: 'Alasan Penolakan', text: '<?= htmlspecialchars(addslashes($claim['rejection_reason'] ?? 'Tidak ada alasan.')) ?>', icon: 'info', confirmButtonColor: '#000666'})" class="text-xs font-bold text-on-surface-variant hover:text-on-surface hover:underline flex items-center gap-1 justify-end ml-auto cursor-pointer">
                                <span class="material-symbols-outlined text-sm">info</span> Detail Penolakan
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
    window.filterReimbursementTable = function filterReimbursementTable() {
        const query = document.getElementById('reimbursementSearch').value.toLowerCase().trim();
        const status = document.getElementById('reimbursementStatusFilter').value.toLowerCase();
        const category = document.getElementById('reimbursementCategoryFilter').value.toLowerCase();
        const rows = document.querySelectorAll('#reimbursementTableBody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.cells.length <= 1) return; // Fallback empty row
            
            const nameAttr = row.getAttribute('data-name');
            const statusAttr = row.getAttribute('data-status');
            const categoryAttr = row.getAttribute('data-category');
            
            const matchesSearch = nameAttr.includes(query);
            const matchesStatus = !status || statusAttr === status;
            const matchesCategory = !category || categoryAttr === category;

            if (matchesSearch && matchesStatus && matchesCategory) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('displayedCount').textContent = visibleCount;
    }

    // Approve Claim action
    window.approveClaim = function approveClaim(id, name, amount) {
        Swal.fire({
            title: 'Setujui Reimbursement?',
            text: `Apakah Anda yakin ingin menyetujui reimbursement senilai ${amount} untuk ${name}? Transaksi pembayaran akan langsung disinkronkan ke modul payroll.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2e7d32',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Setujui & Cairkan',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const formData = new FormData();
                formData.append('claim_id', id);
                return fetch('/hrops/reimbursements/approve', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Gagal menyetujui klaim.');
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
                    title: 'Disetujui!',
                    text: `Klaim reimbursement ${name} sebesar ${amount} telah berhasil disetujui untuk siklus transfer penggajian.`,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    if (window.loadPage) {
                        window.loadPage('/hrops/reimbursements');
                    } else {
                        window.location.reload();
                    }
                });
            }
        });
    }

    // Reject Claim action with SweetAlert2 input validator
    window.rejectClaim = function rejectClaim(id, name, amount) {
        Swal.fire({
            title: `Tolak Reimbursement - ${name}`,
            input: 'textarea',
            inputLabel: `Alasan Penolakan Klaim (${amount})`,
            inputPlaceholder: 'Tulis alasan penolakan klaim reimbursement...',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Tolak Klaim',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan reimbursement wajib diisi.';
                }
            },
            showLoaderOnConfirm: true,
            preConfirm: (value) => {
                const formData = new FormData();
                formData.append('claim_id', id);
                formData.append('rejection_reason', value);
                return fetch('/hrops/reimbursements/reject', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Gagal menolak klaim.');
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
                    title: 'Reimbursement Ditolak',
                    text: `Klaim reimbursement ${name} senilai ${amount} telah resmi ditolak.`,
                    icon: 'error',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    if (window.loadPage) {
                        window.loadPage('/hrops/reimbursements');
                    } else {
                        window.location.reload();
                    }
                });
            }
        });
    }

    // Secure view receipt using the authenticated secure file streaming path
    window.viewReceipt = function viewReceipt(filename, title, amount) {
        const url = `/hrops/reimbursements/view_receipt?file=${filename}`;
        window.viewAttachmentGlobal(url, title, `Nominal Klaim: ${amount} (Terotentikasi & Divalidasi Server).`);
    }
</script>
