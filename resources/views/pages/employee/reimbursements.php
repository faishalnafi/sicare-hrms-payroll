<?php
// Employee ESS Reimbursement Dashboard
$db = \App\Config\Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? '';

if (empty($userId)) {
    echo "<div class='p-6 text-red-600 font-bold'>Akses Ditolak: Sesi kedaluwarsa. Silakan login kembali.</div>";
    return;
}

// Fetch all claims for current employee
$stmt = $db->prepare("
    SELECT * FROM employee_reimbursement_claims 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC
");
$stmt->execute(['user_id' => $userId]);
$claims = $stmt->fetchAll();

// Calculate Plafons Dynamically
$initialPlafons = [
    'medis' => 5000000.00,
    'transport' => 3000000.00,
    'operasional' => 4000000.00,
    'makan' => 2500000.00
];

// Fetch approved spent amount per category (Current Month Only)
$stmt = $db->prepare("
    SELECT category, SUM(amount) as total_spent 
    FROM employee_reimbursement_claims 
    WHERE user_id = :user_id AND status = 'approved'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    GROUP BY category
");
$stmt->execute(['user_id' => $userId]);
$spentByCategory = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch pending amount per category (Current Month Only)
$stmt = $db->prepare("
    SELECT category, SUM(amount) as total_pending 
    FROM employee_reimbursement_claims 
    WHERE user_id = :user_id AND status = 'pending'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    GROUP BY category
");
$stmt->execute(['user_id' => $userId]);
$pendingByCategory = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Sum totals for Global KPI overview
$totalApproved = 0;
$totalPending = 0;
$globalInitial = array_sum($initialPlafons);

foreach ($initialPlafons as $cat => $limit) {
    $totalApproved += $spentByCategory[$cat] ?? 0;
    $totalPending += $pendingByCategory[$cat] ?? 0;
}

$globalAvailable = $globalInitial - $totalApproved;
$globalAvailablePercent = ($globalInitial > 0) ? round(($globalAvailable / $globalInitial) * 100, 1) : 0;

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
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Klaim Reimbursement</h1>
            <p class="text-on-surface-variant font-medium text-sm">Ajukan klaim pengembalian dana, monitor sisa plafon, dan tinjau riwayat pencairan keuangan pribadi Anda.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openClaimModal()" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all shadow-md shadow-primary/10 cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">add_circle</span> Ajukan Klaim Reimbursement
            </button>
        </div>
    </div>

    <!-- Bento Grid KPI Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Card Plafon Global -->
        <div class="bg-gradient-to-br from-primary to-blue-900 text-white rounded-2xl p-5 shadow-md flex flex-col justify-between min-h-[140px] relative overflow-hidden">
            <div class="absolute right-0 bottom-0 opacity-10 translate-x-2 translate-y-2">
                <span class="material-symbols-outlined text-9xl">account_balance_wallet</span>
            </div>
            <div class="flex items-center justify-between z-10">
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-200">Sisa Plafon Global</span>
                <span class="material-symbols-outlined text-blue-200 bg-white/10 p-1.5 rounded-lg text-sm">payments</span>
            </div>
            <div class="mt-4 z-10">
                <h3 class="text-2xl font-black">Rp <?= number_format($globalAvailable, 0, ',', '.') ?></h3>
                <p class="text-[10px] text-blue-200 font-semibold mt-1 flex items-center gap-1">
                    <?= $globalAvailablePercent ?>% Tersedia dari Rp <?= number_format($globalInitial, 0, ',', '.') ?>
                </p>
            </div>
        </div>

        <!-- Card Terbayar -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between min-h-[140px]">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Cair (Disetujui)</span>
                <span class="material-symbols-outlined text-green-600 bg-green-50 p-2 rounded-lg text-sm font-bold">price_check</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-green-700">Rp <?= number_format($totalApproved, 0, ',', '.') ?></h3>
                <p class="text-[10px] text-green-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">check_circle</span> Telah ditransfer ke rekening penggajian
                </p>
            </div>
        </div>

        <!-- Card Pending -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between min-h-[140px]">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Pending Review</span>
                <span class="material-symbols-outlined text-amber-600 bg-amber-50 p-2 rounded-lg text-sm font-bold <?= $totalPending > 0 ? 'animate-pulse' : '' ?>">hourglass_empty</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-amber-700">Rp <?= number_format($totalPending, 0, ',', '.') ?></h3>
                <p class="text-[10px] text-amber-600 font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">sync</span> Sedang divalidasi dokumen oleh HR Ops
                </p>
            </div>
        </div>

        <!-- Card SLA Waktu -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between min-h-[140px]">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">SLA Proses</span>
                <span class="material-symbols-outlined text-primary bg-primary/5 p-2 rounded-lg text-sm font-bold">speed</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-primary">24 <span class="text-xs font-semibold text-on-surface-variant">Jam Kerja</span></h3>
                <p class="text-[10px] text-on-surface-variant font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">verified</span> Jaminan verifikasi cepat & transparan
                </p>
            </div>
        </div>
    </div>

    <!-- Bento Grid Category Limits -->
    <div class="space-y-3">
        <h3 class="text-sm font-bold text-on-surface uppercase tracking-wider">Rincian Plafon Per Kategori</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?php
            $categories = ['medis', 'transport', 'operasional', 'makan'];
            $icons = [
                'medis' => 'medical_services',
                'transport' => 'directions_car',
                'operasional' => 'keyboard',
                'makan' => 'restaurant'
            ];
            $progressColors = [
                'medis' => 'bg-teal-600',
                'transport' => 'bg-blue-600',
                'operasional' => 'bg-purple-600',
                'makan' => 'bg-amber-600'
            ];
            foreach ($categories as $cat):
                $limit = $initialPlafons[$cat];
                $spent = $spentByCategory[$cat] ?? 0;
                $rem = $limit - $spent;
                $percent = ($limit > 0) ? round(($rem / $limit) * 100) : 0;
                $barPercent = min(100, max(0, $percent));
            ?>
            <div class="bg-surface-container-lowest rounded-xl p-4 border border-outline-variant/15 shadow-sm hover:shadow-md transition-shadow space-y-3">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-lg p-2 rounded-lg <?= getCategoryColor($cat) ?>"><?= $icons[$cat] ?></span>
                    <div>
                        <h4 class="text-xs font-extrabold text-on-surface"><?= getCategoryLabel($cat) ?></h4>
                        <p class="text-[10px] text-on-surface-variant font-semibold">Limit: Rp <?= number_format($limit, 0, ',', '.') ?></p>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-[10px] font-bold">
                        <span class="text-on-surface-variant">Sisa Saldo</span>
                        <span class="text-primary">Rp <?= number_format($rem, 0, ',', '.') ?> (<?= $percent ?>%)</span>
                    </div>
                    <!-- Custom Progress Bar -->
                    <div class="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden">
                        <div class="<?= $progressColors[$cat] ?> h-full rounded-full transition-all duration-500" style="width: <?= $barPercent ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Riwayat Klaim Table Panel -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <!-- Control Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary font-bold">history</span>
                <h2 class="text-lg font-extrabold text-on-surface">Riwayat Pengajuan Reimbursement</h2>
            </div>
            <!-- Search & Filters -->
            <div class="flex items-center gap-3">
                <div class="relative">
                    <select id="employeeCategoryFilter" onchange="filterClaimsTable()" class="py-2 pl-3 pr-8 appearance-none text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant w-full">
                        <option value="">Semua Kategori</option>
                        <option value="medis">Kesehatan & Medis</option>
                        <option value="transport">Transportasi & Tol</option>
                        <option value="operasional">Alat Kerja & Operasional</option>
                        <option value="makan">Makan & Bisnis</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/70 text-sm">arrow_drop_down</span>
                </div>
                <div class="relative">
                    <select id="employeeStatusFilter" onchange="filterClaimsTable()" class="py-2 pl-3 pr-8 appearance-none text-xs rounded-lg border border-outline-variant/50 bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant w-full">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/70 text-sm">arrow_drop_down</span>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Tanggal Diajukan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Kategori</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Jumlah Klaim</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Keterangan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Bukti Nota</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="employeeClaimsTableBody" class="divide-y divide-outline-variant/10">
                    <?php if (empty($claims)): ?>
                    <tr class="empty-row">
                        <td colspan="7" class="py-8 text-center text-on-surface-variant font-medium text-xs">
                            <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">inbox</span>
                            <p>Belum ada riwayat pengajuan reimbursement.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($claims as $claim): ?>
                    <tr class="hover:bg-surface-container-low/30 transition-colors" data-status="<?= htmlspecialchars($claim['status']) ?>" data-category="<?= htmlspecialchars($claim['category']) ?>">
                        <td class="py-4 px-6 font-semibold text-xs text-on-surface">
                            <?= date('d M Y, H:i', strtotime($claim['created_at'])) ?>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold border <?= getCategoryColor($claim['category']) ?>">
                                <?= getCategoryLabel($claim['category']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 font-mono text-xs font-bold text-on-surface">
                            Rp <?= number_format($claim['amount'], 0, ',', '.') ?>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-xs text-on-surface font-semibold truncate max-w-[200px]" title="<?= htmlspecialchars($claim['description']) ?>"><?= htmlspecialchars($claim['description']) ?></div>
                        </td>
                        <td class="py-4 px-6">
                            <button onclick="viewReceipt('<?= htmlspecialchars($claim['receipt_path']) ?>', 'Rp <?= number_format($claim['amount'], 0, ',', '.') ?>')" class="text-[10px] text-primary hover:text-primary/80 no-underline hover:no-underline font-extrabold flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-xs font-bold text-primary hover:text-primary/80">attachment</span>
                                <span>Lihat Berkas</span>
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
                            <button onclick="cancelClaim('<?= $claim['id'] ?>', 'Rp <?= number_format($claim['amount'], 0, ',', '.') ?>')" class="bg-red-50 hover:bg-red-100 text-red-700 font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-red-200 transition-colors inline-flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-xs">cancel</span> Batalkan Klaim
                            </button>
                            <?php elseif ($claim['status'] === 'rejected' && !empty($claim['rejection_reason'])): ?>
                            <button onclick="showRejectionReason('<?= htmlspecialchars(addslashes($claim['rejection_reason'])) ?>')" class="bg-surface-container-high hover:bg-surface-container-high/80 text-on-surface-variant font-bold text-[10px] py-1.5 px-2.5 rounded-lg border border-outline-variant/30 transition-colors flex items-center gap-1 justify-end ml-auto cursor-pointer">
                                <span class="material-symbols-outlined text-xs">info</span> Alasan Penolakan
                            </button>
                            <?php else: ?>
                            <span class="text-[10px] text-on-surface-variant/40 font-bold italic">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination Controls -->
        <div id="reimbursePagination" class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end bg-surface-container-low/30 hidden">
            <div class="flex items-center gap-2">
                <button onclick="prevReimbursePage()" id="reimbursePrevBtn" class="p-1.5 rounded-lg border border-outline-variant/20 text-on-surface hover:bg-surface-container-high transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <button onclick="nextReimbursePage()" id="reimburseNextBtn" class="p-1.5 rounded-lg border border-outline-variant/20 text-on-surface hover:bg-surface-container-high transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Submit Claim (Hidden by default) -->
<div id="claimModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300" id="claimModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary font-bold">receipt_long</span>
                <h3 class="text-base font-extrabold text-on-surface">Ajukan Klaim Reimbursement</h3>
            </div>
            <button onclick="closeClaimModal()" class="p-1.5 hover:bg-surface-container-high rounded-full transition-colors cursor-pointer flex items-center justify-center text-on-surface-variant">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body Form -->
        <form id="claimSubmitForm" onsubmit="submitClaimForm(event)" class="p-6 space-y-4">
            <!-- Category -->
            <div class="space-y-1.5">
                <label for="claimCategory" class="block text-xs font-bold text-on-surface-variant uppercase">Kategori Reimbursement <span class="text-red-500">*</span></label>
                <select name="category" id="claimCategory" required class="w-full py-2.5 px-3.5 text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface-variant">
                    <option value="" disabled selected>Pilih Kategori...</option>
                    <?php
                    $categoriesList = ['medis', 'transport', 'operasional', 'makan'];
                    foreach ($categoriesList as $cat):
                        $limitVal = $initialPlafons[$cat];
                        $usedVal = ($spentByCategory[$cat] ?? 0) + ($pendingByCategory[$cat] ?? 0);
                        $isLimitReached = $usedVal >= $limitVal;
                        $disabledAttr = $isLimitReached ? 'disabled' : '';
                        $labelPrefix = $isLimitReached ? '❌ ' : '';
                        $labelSuffix = $isLimitReached ? ' - Limit Tercapai' : '';
                    ?>
                    <option value="<?= $cat ?>" <?= $disabledAttr ?>>
                        <?= $labelPrefix . getCategoryLabel($cat) ?> (Limit: Rp <?= number_format($limitVal, 0, ',', '.') ?>)<?= $labelSuffix ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Amount -->
            <div class="space-y-1.5">
                <label for="claimAmount" class="block text-xs font-bold text-on-surface-variant uppercase">Nominal Pengeluaran (Rupiah) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-on-surface-variant">
                        Rp
                    </span>
                    <input type="number" name="amount" id="claimAmount" placeholder="Contoh: 450000" min="1000" required class="pl-10 pr-4 py-2.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-bold text-on-surface" />
                </div>
            </div>

            <!-- Description -->
            <div class="space-y-1.5">
                <label for="claimDescription" class="block text-xs font-bold text-on-surface-variant uppercase">Keterangan Penggunaan <span class="text-red-500">*</span></label>
                <textarea name="description" id="claimDescription" rows="3" placeholder="Tuliskan detail barang/jasa yang diklaim beserta alasan operasional bisnis..." required class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface"></textarea>
            </div>

            <!-- File Upload Dropzone -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-on-surface-variant uppercase">Unggah Bukti Nota / Kuitansi Fisik <span class="text-red-500">*</span></label>
                <div id="dropzone" class="border-2 border-dashed border-outline-variant/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-2.5 hover:border-primary hover:bg-primary/5 transition-all cursor-pointer relative group">
                    <input type="file" name="receipt" id="claimReceipt" accept=".jpg,.jpeg,.png,.pdf" required class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="handleFileSelect(event)" />
                    <span class="material-symbols-outlined text-3xl text-outline-variant group-hover:text-primary transition-colors">cloud_upload</span>
                    <div class="text-center">
                        <p class="text-xs font-bold text-on-surface" id="uploadFilename">Tarik & lepas berkas di sini, atau klik untuk mencari</p>
                        <p class="text-[10px] text-on-surface-variant/80 font-semibold mt-1">Format didukung: JPG, PNG, atau PDF (Ukuran maksimal: 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-outline-variant/15 mt-6">
                <button type="button" onclick="closeClaimModal()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-5 rounded-xl transition-all shadow-md shadow-primary/10 cursor-pointer">
                    Kirim Klaim
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Pagination state
    let currentReimbursePage = 1;
    const itemsPerReimbursePage = 10;
    
    function initReimbursePagination() {
        const rows = Array.from(document.querySelectorAll('#employeeClaimsTableBody tr:not(.empty-row)'));
        if (rows.length === 0) return;
        
        const paginationContainer = document.getElementById('reimbursePagination');
        if (paginationContainer) paginationContainer.classList.add('hidden'); // ensure hidden first
        renderReimbursePage();
    }
    
    function renderReimbursePage() {
        const category = document.getElementById('employeeCategoryFilter').value;
        const status = document.getElementById('employeeStatusFilter').value;
        
        const allRows = Array.from(document.querySelectorAll('#employeeClaimsTableBody tr:not(.empty-row)'));
        const filteredRows = allRows.filter(row => {
            const catAttr = row.getAttribute('data-category');
            const statusAttr = row.getAttribute('data-status');
            const matchesCategory = !category || catAttr === category;
            const matchesStatus = !status || statusAttr === status;
            return matchesCategory && matchesStatus;
        });
        
        const totalItems = filteredRows.length;
        const totalPages = Math.ceil(totalItems / itemsPerReimbursePage) || 1;
        
        const paginationContainer = document.getElementById('reimbursePagination');
        if (totalItems === 0 || totalPages <= 1) {
            if (paginationContainer) paginationContainer.classList.add('hidden');
            if (totalItems > 0) {
                // If there's 1 page, just show them all
                allRows.forEach(row => row.style.display = 'none');
                filteredRows.forEach(row => row.style.display = '');
            }
            return;
        } else {
            if (paginationContainer) paginationContainer.classList.remove('hidden');
        }
        
        if (currentReimbursePage > totalPages) currentReimbursePage = totalPages;
        if (currentReimbursePage < 1) currentReimbursePage = 1;
        
        const startIdx = (currentReimbursePage - 1) * itemsPerReimbursePage;
        const endIdx = Math.min(startIdx + itemsPerReimbursePage, totalItems);
        
        // Hide all rows
        allRows.forEach(row => row.style.display = 'none');
        
        // Show paginated rows
        for (let i = startIdx; i < endIdx; i++) {
            if (filteredRows[i]) filteredRows[i].style.display = '';
        }
        
        // Update pagination buttons
        const prevBtn = document.getElementById('reimbursePrevBtn');
        const nextBtn = document.getElementById('reimburseNextBtn');
        if (prevBtn) prevBtn.disabled = currentReimbursePage === 1;
        if (nextBtn) nextBtn.disabled = currentReimbursePage === totalPages || totalPages === 0;
    }
    
    function prevReimbursePage() {
        if (currentReimbursePage > 1) {
            currentReimbursePage--;
            renderReimbursePage();
        }
    }
    
    function nextReimbursePage() {
        currentReimbursePage++;
        renderReimbursePage();
    }

    // Local filters for claims history table
    function filterClaimsTable() {
        currentReimbursePage = 1; // Reset to page 1 on filter change
        renderReimbursePage();
    }

    // Modal Control
    function openClaimModal() {
        const modal = document.getElementById('claimModal');
        const container = document.getElementById('claimModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }

    function closeClaimModal() {
        const modal = document.getElementById('claimModal');
        const container = document.getElementById('claimModalContainer');
        modal.classList.add('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        document.getElementById('claimSubmitForm').reset();
        document.getElementById('uploadFilename').innerHTML = 'Tarik & lepas berkas di sini, atau klik untuk mencari';
    }

    // File Boundary check on Client-Side
    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        const maxLimit = 10 * 1024 * 1024; // 10MB
        if (file.size > maxLimit) {
            Swal.fire({
                title: 'Ukuran File Terlalu Besar',
                text: 'Batas maksimal ukuran berkas struk/nota pengeluaran adalah 10MB.',
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
                title: 'Format File Tidak Didukung',
                text: 'Hanya format JPG, JPEG, PNG, dan PDF resmi yang diperbolehkan demi keamanan.',
                icon: 'error',
                confirmButtonColor: '#ba1a1a'
            });
            e.target.value = '';
            document.getElementById('uploadFilename').innerHTML = 'Tarik & lepas berkas di sini, atau klik untuk mencari';
            return;
        }

        document.getElementById('uploadFilename').innerHTML = `<span class="text-primary font-extrabold flex items-center justify-center gap-1"><span class="material-symbols-outlined text-sm">check_circle</span> ${file.name}</span> <span class="text-[10px] text-on-surface-variant">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>`;
    }

    // View rejection reason in modal
    function showRejectionReason(reason) {
        Swal.fire({
            title: 'Alasan Penolakan Klaim',
            text: reason,
            icon: 'info',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#000666'
        });
    }

    // Cancel pending reimbursement claim
    function cancelClaim(id, amount) {
        Swal.fire({
            title: 'Batalkan Pengajuan?',
            text: `Apakah Anda yakin ingin membatalkan pengajuan reimbursement senilai ${amount}? Tindakan ini permanen dan berkas bukti nota akan dihapus.`,
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
                    text: 'Sedang memproses pembatalan...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData();
                formData.append('claim_id', id);

                fetch('/employee/reimbursements/cancel', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil Dibatalkan',
                            text: 'Pengajuan reimbursement Anda berhasil dibatalkan.',
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => {
                            if (window.loadPage) {
                                window.loadPage('/employee/reimbursements');
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal Membatalkan',
                            text: data.message || 'Terjadi kesalahan saat membatalkan klaim.',
                            icon: 'error',
                            confirmButtonColor: '#ba1a1a'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Kesalahan Sistem',
                        text: 'Gagal menghubungkan ke server. Silakan coba sesaat lagi.',
                        icon: 'error',
                        confirmButtonColor: '#ba1a1a'
                    });
                    console.error(err);
                });
            }
        });
    }

    // Secure Receipt view modal using streaming endpoint
    function viewReceipt(filename, amount) {
        const url = `/hrops/reimbursements/view_receipt?file=${filename}`;
        window.viewAttachmentGlobal(url, 'Bukti Nota Pengeluaran', `Nominal Klaim: ${amount}`);
    }

    // AJAX Form submission with SweetAlert2
    function submitClaimForm(e) {
        e.preventDefault();
        
        const form = document.getElementById('claimSubmitForm');
        const formData = new FormData(form);

        Swal.fire({
            title: 'Mengirim Pengajuan...',
            text: 'Mengevaluasi ukuran file, memproses enkripsi file, dan menyimpan klaim ke database siCare.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/employee/reimbursements/submit', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil Diajukan!',
                    text: `Klaim reimbursement senilai ${data.amount_formatted} berhasil dikirim! Status: PENDING.`,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    closeClaimModal();
                    // Reload SPA content dynamically
                    if (window.loadPage) {
                        window.loadPage('/employee/reimbursements');
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Gagal Mengirimkan Klaim',
                    text: data.message || 'Terjadi kesalahan internal saat mengunggah.',
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

    // Expose all functions to global scope to prevent IIFE isolation issues
    window.initReimbursePagination = initReimbursePagination;
    window.renderReimbursePage = renderReimbursePage;
    window.prevReimbursePage = prevReimbursePage;
    window.nextReimbursePage = nextReimbursePage;
    window.filterClaimsTable = filterClaimsTable;
    window.openClaimModal = openClaimModal;
    window.closeClaimModal = closeClaimModal;
    window.handleFileSelect = handleFileSelect;
    window.showRejectionReason = showRejectionReason;
    window.cancelClaim = cancelClaim;
    window.viewReceipt = viewReceipt;
    window.submitClaimForm = submitClaimForm;

    // Initialize pagination shortly after script load
    setTimeout(initReimbursePagination, 100);
</script>