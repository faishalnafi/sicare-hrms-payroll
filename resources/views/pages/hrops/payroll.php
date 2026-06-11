<?php
// HR Operations — Pemrosesan Penggajian (Payroll Portal)
if (session_status() === PHP_SESSION_NONE) session_start();

$db = \App\Config\Database::getInstance()->getConnection();
$companyName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_company_name' LIMIT 1")->fetchColumn() ?: 'PT SI CARE ENTERPRISE';
$companyNameForStamp = $companyName;
if (stripos($companyName, 'Mango') !== false) {
    $companyNameForStamp = 'PT. MANGO TEKNUSA INOVASI';
}
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
$appLogoIcon = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_icon' LIMIT 1")->fetchColumn() ?: 'local_police';
$appLogoType = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_type' LIMIT 1")->fetchColumn() ?: 'icon';
$appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';
$bpjsTkPct = (float)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'payroll_bpjs_tk_pct' LIMIT 1")->fetchColumn() ?: 2);
$bpjsKesPct = (float)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'payroll_bpjs_kes_pct' LIMIT 1")->fetchColumn() ?: 1);
$pph21Pct = (float)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'payroll_pph21_pct' LIMIT 1")->fetchColumn() ?: 2.5);

// Fetch HR Director (Hiring Manager of HR department) dynamically, fallback to Zanuba Arifatul Khafsoh
$hrQuery = $db->query("
    SELECT u.first_name, u.last_name, u.job_title 
    FROM users u
    JOIN departments d ON u.department_id = d.id
    WHERE u.role = 'hiring_manager' AND (d.name LIKE '%Human Resources%' OR d.name LIKE '%HR%')
    LIMIT 1
");
$hrDirectorData = $hrQuery->fetch();
$hrDirectorName = $hrDirectorData ? trim($hrDirectorData['first_name'] . ' ' . ($hrDirectorData['last_name'] ?? '')) : 'Zanuba Arifatul Khafsoh';
$hrDirectorTitle = $hrDirectorData ? ($hrDirectorData['job_title'] ?? 'HR Operations Director') : 'HR Operations Director';
?>
<style>
    .kpi-card {
        background: #ffffff;
        border: 1px solid rgba(0, 6, 102, 0.06);
        border-radius: 1.25rem;
        box-shadow: 0 4px 20px rgba(0, 6, 102, 0.02);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 28px rgba(0, 6, 102, 0.06);
    }
    .input-bonus {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        text-align: right;
        transition: all 0.2s;
    }
    .input-bonus:focus {
        border-color: #000666;
        box-shadow: 0 0 0 3px rgba(0, 6, 102, 0.08);
        background: #ffffff !important;
    }
    .badge-status {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 0.35rem 0.75rem;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .badge-status.draft {
        background-color: #f1f3f4;
        color: #5f6368;
        border: 1.5px solid #dadce0;
    }
    .badge-status.approved {
        background-color: #e8f0fe;
        color: #1a73e8;
        border: 1.5px solid #d2e3fc;
    }
    .badge-status.paid {
        background-color: #e6f4ea;
        color: #137333;
        border: 1.5px solid #ceead6;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 1.27cm; /* Narrow margin */
        }
        html, body.printing-active {
            background: white !important;
            background-color: white !important;
        }
        body.printing-active > :not(#printArea) {
            display: none !important;
        }
        body.printing-active #printArea {
            display: block !important;
            background: white !important;
            color: black !important;
            width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        body.printing-active #printArea * {
            visibility: visible !important;
        }
        .no-print {
            display: none !important;
        }
        
        /* Force print layout styles to maintain side-by-side structure */
        #printArea .print-row {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        #printArea .print-row-end {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: flex-end !important;
        }
        #printArea .print-grid-4 {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 1.5rem !important;
        }
        #printArea .print-grid-6 {
            display: grid !important;
            grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
            gap: 1.5rem !important;
        }
        #printArea .print-grid-2 {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 2rem !important;
        }
    }
</style>

<div class="space-y-6">

    <!-- ── Header ── -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Pemrosesan Penggajian</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola draf payroll karyawan, adjust bonus, verifikasi reimbursement, dan proses pembayaran gaji.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="month" id="payrollMonth" value="<?= date('Y-m') ?>"
                    class="bg-surface-container-lowest border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold"
                    onchange="window.loadPayrollData()" />
            </div>
            <button onclick="window.generatePayroll()" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-primary hover:bg-blue-900 text-white rounded-xl text-xs font-bold transition-all shadow-[0_4px_12px_rgba(0,6,102,0.15)] hover:shadow-[0_6px_16px_rgba(0,6,102,0.25)]">
                <span class="material-symbols-outlined text-sm">autorenew</span>
                Generate Payroll
            </button>
        </div>
    </div>

    <!-- ── Summary Statistics KPI Cards ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Gross Outflow -->
        <div class="kpi-card p-5 flex items-center gap-4">
            <div class="p-3.5 bg-primary/5 rounded-2xl text-primary">
                <span class="material-symbols-outlined text-2xl font-bold">payments</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Gaji Kotor</p>
                <h3 id="statGross" class="text-lg font-black text-on-surface mt-0.5">Rp 0</h3>
            </div>
        </div>

        <!-- KPI 2: Total Deductions -->
        <div class="kpi-card p-5 flex items-center gap-4">
            <div class="p-3.5 bg-red-50 text-red-600 rounded-2xl">
                <span class="material-symbols-outlined text-2xl font-bold">price_check</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Potongan (Pajak + BPJS)</p>
                <h3 id="statDeductions" class="text-lg font-black text-on-surface mt-0.5">Rp 0</h3>
            </div>
        </div>

        <!-- KPI 3: Total Reimbursements -->
        <div class="kpi-card p-5 flex items-center gap-4">
            <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl">
                <span class="material-symbols-outlined text-2xl font-bold">receipt_long</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Reimbursement Dicairkan</p>
                <h3 id="statReimburse" class="text-lg font-black text-on-surface mt-0.5">Rp 0</h3>
            </div>
        </div>

        <!-- KPI 4: Net Outflow -->
        <div class="kpi-card p-5 flex items-center gap-4">
            <div class="p-3.5 bg-blue-50 text-blue-800 rounded-2xl">
                <span class="material-symbols-outlined text-2xl font-bold">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Pengeluaran Bersih</p>
                <h3 id="statNet" class="text-lg font-black text-on-surface mt-0.5">Rp 0</h3>
            </div>
        </div>
    </div>

    <!-- ── Batch Actions and Table Container ── -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl shadow-sm overflow-hidden">
        
        <!-- Action bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-5 border-b border-outline-variant/15 bg-surface-container-lowest/50">
            <div class="flex items-center gap-2">
                <span id="payrollCount" class="bg-primary/5 text-primary text-xs font-bold px-3 py-1 rounded-full">0 Karyawan</span>
                <span id="selectedCount" class="hidden bg-amber-50 text-amber-800 text-xs font-bold px-3 py-1 rounded-full border border-amber-200">0 dipilih</span>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <button onclick="window.batchStatusUpdate('Approved')" id="btnApproveAll" class="hidden inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all">
                    <span class="material-symbols-outlined text-sm font-bold">verified</span>
                    Setujui yang Dipilih
                </button>
                <button onclick="window.batchStatusUpdate('Paid')" id="btnPayAll" class="hidden inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition-all">
                    <span class="material-symbols-outlined text-sm font-bold">payments</span>
                    Bayar yang Dipilih
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/20">
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox" id="selectAllCheckbox" onchange="window.toggleSelectAll(this)"
                                class="rounded border-outline-variant/30 text-primary focus:ring-primary focus:ring-opacity-20 cursor-pointer" />
                        </th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider">Karyawan</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right">Gaji Pokok</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right">Tunj. Jabatan</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right">Tunj. Flat (Trs+Kom)</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right w-32">Bonus (IDR)</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right w-32">Lemburan (IDR)</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right font-semibold">Reimbursement</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right text-red-700 font-semibold">Potongan (Pjk+BPJS)</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right w-32 text-red-750">Potongan Lain (IDR)</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-right">Gaji Bersih</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-center">Status</th>
                        <th class="p-4 text-xs font-extrabold text-on-surface-variant uppercase tracking-wider text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody id="payrollTableBody" class="divide-y divide-outline-variant/10">
                    <!-- Dynamic Rows here -->
                </tbody>
            </table>
        </div>

        <!-- Empty state placeholder -->
        <div id="emptyState" class="hidden p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">account_balance_wallet</span>
            <h4 class="text-base font-bold text-on-surface">Belum Ada Data Payroll</h4>
            <p class="text-xs text-on-surface-variant mt-1 max-w-sm mx-auto">Silakan pilih bulan/tahun lalu tekan tombol <strong>Generate Payroll</strong> di kanan atas untuk membuat draf baru secara otomatis.</p>
        </div>
    </div>

</div>

<!-- ── Payslip Detailed Preview Modal ── -->
<div id="payslipModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
    <div class="bg-white text-black w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden scale-95 transform transition-transform duration-300 border border-neutral-200" id="payslipModalContainer">
        <!-- Modal Toolbar -->
        <div class="bg-neutral-100 px-6 py-3 border-b border-neutral-200 flex justify-between items-center no-print">
            <span class="text-xs font-bold text-neutral-600 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">print_disabled</span> Mode Pratinjau Dokumen Resmi
            </span>
            <div class="flex items-center gap-2">
                <button onclick="window.printPayslipFromModal()" class="bg-primary text-white hover:bg-primary/95 font-bold text-xs py-2 px-4 rounded-lg flex items-center gap-1.5 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-sm">print</span> Cetak / Unduh PDF
                </button>
                <button onclick="window.closePayslipModal()" class="bg-neutral-200 text-neutral-700 hover:bg-neutral-300 font-bold text-xs py-2 px-3 rounded-lg flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-sm">close</span> Tutup
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Content (The Actual Payslip) -->
        <div class="p-8 md:p-12 overflow-y-auto bg-white flex-grow relative" id="payslipPrintArea">

            <!-- Header Corporate -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b-2 border-neutral-900 pb-6 gap-4 print-row">
                <div class="flex items-center gap-3">
                    <?php if ($appLogoType === 'image' && !empty($appLogoImage)): ?>
                        <div class="bg-white border border-neutral-200 p-1.5 rounded-xl flex items-center justify-center shadow-sm">
                            <img src="<?= htmlspecialchars($appLogoImage) ?>" class="h-9 w-9 object-contain" alt="Logo" />
                        </div>
                    <?php else: ?>
                        <div class="bg-primary text-white p-2.5 rounded-xl flex items-center justify-center shadow-md">
                            <span class="material-symbols-outlined text-2xl font-bold"><?= htmlspecialchars($appLogoIcon) ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h2 class="text-2xl font-black text-neutral-900 tracking-tight leading-none"><?= htmlspecialchars($appName) ?></h2>
                        <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-widest mt-1 block"><?= htmlspecialchars($companyName) ?></span>
                    </div>
                </div>
                <div class="text-left md:text-right">
                    <h3 class="text-lg font-black text-neutral-900 uppercase tracking-wider">SLIP GAJI KARYAWAN</h3>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5" id="payslipNo">SLIP/PAY/20260525-0033</p>
                </div>
            </div>

            <!-- Employee & General Info Metadata Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 py-6 border-b border-neutral-250 text-xs print-grid-6">
                <div>
                    <span class="text-neutral-500 block mb-1">ID KARYAWAN</span>
                    <span class="font-mono font-bold text-neutral-800" id="slipNik">EMP-2026-0033</span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">NAMA KARYAWAN</span>
                    <span class="font-bold text-neutral-800 uppercase" id="slipName">ALEX RIVERA</span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">JABATAN / DIVISI</span>
                    <span class="font-bold text-neutral-800" id="slipTitle">Senior UI/UX Designer</span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">NPWP</span>
                    <span class="font-mono font-bold text-neutral-800" id="slipNpwp">12.345.678.9-012.000</span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">HARI KERJA AKTIF</span>
                    <span class="font-bold text-neutral-800" id="slipWorkDays">22 Hari Kerja</span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">PERIODE</span>
                    <span class="font-bold text-neutral-800" id="slipPeriod">Mei 2026</span>
                </div>
            </div>

            <!-- Financial Details Structure -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-6 items-start print-grid-2">
                <!-- EARNINGS (PENDAPATAN) -->
                <div class="space-y-4">
                    <div class="bg-neutral-100 px-4 py-2 border-l-4 border-green-600">
                        <h4 class="text-xs font-bold text-neutral-850 uppercase tracking-wider">I. PENDAPATAN (EARNINGS)</h4>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Gaji Pokok (Base Salary)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="slipBaseSalary">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Jabatan</span>
                            <span class="font-mono font-semibold text-neutral-900" id="slipTunjJabatan">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Transport & Makan</span>
                            <span class="font-mono font-semibold text-neutral-900" id="slipTunjTransport">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Komunikasi</span>
                            <span class="font-mono font-semibold text-neutral-900" id="slipTunjKomunikasi">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="slipReimbursementRow">
                            <span class="text-neutral-600">Reimbursement Klaim</span>
                            <span class="font-mono font-semibold text-neutral-900 text-emerald-600" id="slipReimbursement">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="slipBonusRow">
                            <span class="text-neutral-600">Bonus Khusus & Insentif</span>
                            <span class="font-mono font-semibold text-neutral-900 text-emerald-600" id="slipBonus">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="slipOvertimeRow">
                            <span class="text-neutral-600">Lemburan</span>
                            <span class="font-mono font-semibold text-neutral-900 text-emerald-600" id="slipOvertime">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- DEDUCTIONS (POTONGAN) -->
                <div class="space-y-4">
                    <div class="bg-neutral-100 px-4 py-2 border-l-4 border-red-600">
                        <h4 class="text-xs font-bold text-neutral-850 uppercase tracking-wider">II. POTONGAN (DEDUCTIONS)</h4>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">BPJS Ketenagakerjaan (<?= $bpjsTkPct ?>%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="slipBpjsTk">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">BPJS Kesehatan (<?= $bpjsKesPct ?>%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="slipBpjsKes">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Pajak Penghasilan (PPh 21 - <?= $pph21Pct ?>%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="slipPph21">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="slipUnpaidRow">
                            <span class="text-neutral-600 flex items-center gap-1">
                                Potongan Cuti Tidak Dibayar 
                                <span class="bg-red-50 text-red-700 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-red-100" id="slipUnpaidDaysBadge">0 Hari</span>
                            </span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="slipUnpaidDeduction">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="slipAlpaRow">
                            <span class="text-neutral-600 flex items-center gap-1">
                                Potongan Alpa 
                                <span class="bg-red-50 text-red-700 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-red-100" id="slipAlpaDaysBadge">0 Hari</span>
                            </span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="slipAlpaDeduction">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="slipLateRow">
                            <span class="text-neutral-600 flex items-center gap-1">
                                Potongan Keterlambatan 
                                <span class="bg-red-50 text-red-700 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-red-100" id="slipLateDaysBadge">0 Kali</span>
                            </span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="slipLateDeduction">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="slipOtherDeductionRow">
                            <span class="text-neutral-600">Potongan Lainnya</span>
                            <span class="font-mono font-semibold text-neutral-900 text-red-600" id="slipOtherDeduction">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOTAL SUMMARY PANEL -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 pb-6 border-t border-b border-neutral-250 print-grid-2">
                <div class="space-y-2">
                    <div class="flex justify-between text-xs font-bold text-neutral-700">
                        <span>TOTAL PENDAPATAN BRUTO</span>
                        <span class="font-mono" id="slipTotalEarnings">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-red-600">
                        <span>TOTAL POTONGAN KARYAWAN</span>
                        <span class="font-mono" id="slipTotalDeductions">Rp 0</span>
                    </div>
                </div>
                <div class="bg-neutral-50 p-4 rounded-xl flex flex-col justify-center border border-neutral-200">
                    <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-widest">TAKE HOME PAY (GAJI BERSIH)</span>
                    <span class="text-2xl font-black text-primary font-headline mt-1 tracking-tight" id="slipNetSalary">
                        Rp 0
                    </span>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="pt-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 text-xs text-neutral-700 print-row-end">
                <div>
                    <span class="text-neutral-500 block mb-1">REKENING PAYROLL TUJUAN</span>
                    <span class="font-bold text-neutral-800 text-sm" id="slipBank">BCA - 8012345678</span>
                    <span class="text-neutral-500 block mt-0.5 uppercase" id="slipBankHolder">A/N ALEX RIVERA</span>
                </div>
                
                <!-- Signature Space -->
                <div class="flex flex-col items-center text-center relative">
                    <!-- Circular Digital Stamp SVG Overlay -->
                    <div class="absolute -left-24 -bottom-2 pointer-events-none select-none z-10">
                        <svg class="w-28 h-28 text-blue-800/80 transform rotate-12" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="2.5" stroke-dasharray="3, 1.5" />
                            <circle cx="50" cy="50" r="37" fill="none" stroke="currentColor" stroke-width="1.5" />
                            <path id="stampPath" fill="none" d="M 20 50 A 30 30 0 1 1 80 50" />
                            <text font-size="4.8" font-weight="900" letter-spacing="0.6" fill="currentColor">
                                <textPath href="#stampPath" startOffset="50%" text-anchor="middle">
                                    <?= htmlspecialchars($companyNameForStamp) ?>
                                </textPath>
                            </text>
                            <path id="stampPathBottom" fill="none" d="M 80 50 A 30 30 0 0 1 20 50" />
                            <text font-size="4.8" font-weight="900" letter-spacing="0.6" fill="currentColor">
                                <textPath href="#stampPathBottom" startOffset="50%" text-anchor="middle">
                                    * HR OPERATIONS *
                                </textPath>
                            </text>
                            <circle cx="50" cy="50" r="23" fill="none" stroke="currentColor" stroke-width="1" />
                            <text x="50" y="47" font-size="7" font-weight="900" text-anchor="middle" fill="currentColor">PAID</text>
                            <text x="50" y="57" font-size="6.5" font-weight="800" text-anchor="middle" fill="currentColor">VERIFIED</text>
                        </svg>
                    </div>

                    <span class="text-neutral-500 block mb-2 text-[10px] tracking-wider uppercase">TANDA TANGAN DIGITAL PERUSAHAAN</span>
                    
                    <!-- SVG Barcode for Digital Signature -->
                    <div class="bg-neutral-50 p-2 rounded border border-neutral-200 mb-2">
                        <svg class="h-9 w-44" viewBox="0 0 100 20">
                            <rect x="0" y="0" width="2" height="15" fill="black"/>
                            <rect x="3" y="0" width="1" height="15" fill="black"/>
                            <rect x="5" y="0" width="3" height="15" fill="black"/>
                            <rect x="9" y="0" width="1" height="15" fill="black"/>
                            <rect x="11" y="0" width="2" height="15" fill="black"/>
                            <rect x="15" y="0" width="4" height="15" fill="black"/>
                            <rect x="20" y="0" width="1" height="15" fill="black"/>
                            <rect x="22" y="0" width="2" height="15" fill="black"/>
                            <rect x="25" y="0" width="3" height="15" fill="black"/>
                            <rect x="29" y="0" width="1" height="15" fill="black"/>
                            <rect x="31" y="0" width="2" height="15" fill="black"/>
                            <rect x="35" y="0" width="1" height="15" fill="black"/>
                            <rect x="37" y="0" width="3" height="15" fill="black"/>
                            <rect x="41" y="0" width="1" height="15" fill="black"/>
                            <rect x="43" y="0" width="4" height="15" fill="black"/>
                            <rect x="48" y="0" width="2" height="15" fill="black"/>
                            <rect x="51" y="0" width="1" height="15" fill="black"/>
                            <rect x="53" y="0" width="3" height="15" fill="black"/>
                            <rect x="57" y="0" width="1" height="15" fill="black"/>
                            <rect x="59" y="0" width="2" height="15" fill="black"/>
                            <rect x="62" y="0" width="4" height="15" fill="black"/>
                            <rect x="67" y="0" width="1" height="15" fill="black"/>
                            <rect x="69" y="0" width="2" height="15" fill="black"/>
                            <rect x="72" y="0" width="3" height="15" fill="black"/>
                            <rect x="76" y="0" width="1" height="15" fill="black"/>
                            <rect x="78" y="0" width="2" height="15" fill="black"/>
                            <rect x="81" y="0" width="1" height="15" fill="black"/>
                            <rect x="83" y="0" width="3" height="15" fill="black"/>
                            <rect x="87" y="0" width="1" height="15" fill="black"/>
                            <rect x="89" y="0" width="4" height="15" fill="black"/>
                            <rect x="94" y="0" width="2" height="15" fill="black"/>
                            <rect x="97" y="0" width="1" height="15" fill="black"/>
                            <rect x="99" y="0" width="1" height="15" fill="black"/>
                            <text x="50" y="19" font-size="4" font-family="monospace" text-anchor="middle" fill="#333" id="slipBarcodeNo">PAY-20260525-0033</text>
                        </svg>
                    </div>
                    <span class="font-extrabold text-neutral-800 uppercase tracking-tight" id="slipSignApprover"><?= htmlspecialchars($hrDirectorName) ?></span>
                    <span class="text-[10px] text-neutral-500 uppercase"><?= htmlspecialchars($hrDirectorTitle) ?></span>
                </div>
            </div>
        </div>
</div>

<!-- DYNAMIC PRINT AREA CREATED VIA JS -->

<script>
    let payrollData = [];

    // Helper to fetch and handle errors gracefully
    window.safeFetch = function(url, options = {}) {
        return fetch(url, options)
            .then(res => {
                if (!res.ok) {
                    const resClone = res.clone();
                    return res.text().catch(() => '').then(text => {
                        throw new Error(text || `HTTP ${res.status} ${res.statusText}`);
                    });
                }
                const resClone = res.clone();
                return res.json().catch(err => {
                    return resClone.text().then(text => {
                        throw new Error(`Format JSON tidak valid: ${text}`);
                    });
                });
            });
    }

    // Format currency to IDR rupiah
    window.formatIDR = function(val) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(val);
    }

    // Helper to get formatted MM-YYYY from YYYY-MM input
    window.getPeriodFromInput = function() {
        const val = document.getElementById('payrollMonth').value; // YYYY-MM
        if (!val) return '';
        const parts = val.split('-');
        return parts[1] + '-' + parts[0]; // MM-YYYY
    }

    window.getSelectedPeriodLabel = function() {
        const val = document.getElementById('payrollMonth').value;
        if (!val) return '';
        const parts = val.split('-');
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        return monthNames[parseInt(parts[1]) - 1] + ' ' + parts[0];
    }

    // Load data from backend
    window.loadPayrollData = function() {
        const period = window.getPeriodFromInput();
        if (!period) return;

        const container = document.getElementById('payrollTableBody');
        container.innerHTML = `
            <tr>
                <td colspan="11" class="p-8 text-center text-on-surface-variant">
                    <div class="inline-block w-6 h-6 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></div>
                    <span class="ml-2 font-medium text-xs">Memuat data payroll...</span>
                </td>
            </tr>
        `;

        window.safeFetch(`/hrops/payroll/list?month_year=${period}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (res.success) {
                payrollData = res.data;
                window.renderTable();
                window.calculateSummaryStats();
            } else {
                Swal.fire({ title: 'Gagal', text: res.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
            }
        })
        .catch((err) => {
            Swal.fire({ 
                title: 'Error', 
                html: `Gagal memuat data payroll:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                icon: 'error', 
                confirmButtonColor: '#ba1a1a' 
            });
        });
    }

    // Calculate sum statistics
    window.calculateSummaryStats = function() {
        let grossTotal = 0;
        let deductionsTotal = 0;
        let reimburseTotal = 0;
        let netTotal = 0;

        payrollData.forEach(p => {
            const base = parseFloat(p.base_salary);
            const tunjJab = parseFloat(p.tunj_jabatan);
            const tunjFlat = parseFloat(p.tunj_transport_makan) + parseFloat(p.tunj_komunikasi);
            const reimbursement = parseFloat(p.reimbursement);
            const bonus = parseFloat(p.bonus);
            const overtime = parseFloat(p.overtime || 0);
            const unpaidDec = parseFloat(p.unpaid_leave_deduction || 0);
            const otherDeduction = parseFloat(p.other_deduction || 0);

            // Gross outflow is total cost of employees before employee tax/bpjs/other deductions
            const gross = (base - unpaidDec) + tunjJab + tunjFlat + reimbursement + bonus + overtime;
            
            const bpjs = parseFloat(p.bpjs_tk) + parseFloat(p.bpjs_kes);
            const pph = parseFloat(p.pph21);
            
            grossTotal += gross;
            deductionsTotal += (bpjs + pph + otherDeduction);
            reimburseTotal += reimbursement;
            netTotal += parseFloat(p.net_salary);
        });

        document.getElementById('statGross').textContent = window.formatIDR(grossTotal);
        document.getElementById('statDeductions').textContent = window.formatIDR(deductionsTotal);
        document.getElementById('statReimburse').textContent = window.formatIDR(reimburseTotal);
        document.getElementById('statNet').textContent = window.formatIDR(netTotal);
    }

    // Render table rows
    window.renderTable = function() {
        const body = document.getElementById('payrollTableBody');
        const emptyState = document.getElementById('emptyState');
        const countBadge = document.getElementById('payrollCount');
        
        body.innerHTML = '';
        
        if (!payrollData || payrollData.length === 0) {
            emptyState.classList.remove('hidden');
            countBadge.textContent = '0 Karyawan';
            return;
        }
        
        emptyState.classList.add('hidden');
        countBadge.textContent = `${payrollData.length} Karyawan`;

        payrollData.forEach((p, idx) => {
            const tr = document.createElement('tr');
            tr.id = `row-${p.id}`;
            tr.className = "hover:bg-surface-container-lowest transition-colors text-xs font-semibold text-on-surface";
            
            const isDraft = p.status === 'Draft';
            const flatTunj = parseFloat(p.tunj_transport_makan) + parseFloat(p.tunj_komunikasi);
            const deductions = parseFloat(p.bpjs_tk) + parseFloat(p.bpjs_kes) + parseFloat(p.pph21);
            const formattedBonus = new Intl.NumberFormat('id-ID').format(p.bonus);
            const formattedOvertime = new Intl.NumberFormat('id-ID').format(p.overtime || 0);
            const formattedOtherDeduction = new Intl.NumberFormat('id-ID').format(p.other_deduction || 0);

            const statusClass = p.status.toLowerCase();

            tr.innerHTML = `
                <td class="p-4 text-center">
                    <input type="checkbox" value="${p.id}" onchange="window.updateSelectedCount()"
                        class="row-checkbox rounded border-outline-variant/30 text-primary focus:ring-primary focus:ring-opacity-20 cursor-pointer" />
                </td>
                <td class="p-4">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-800 text-[13px]">${p.first_name} ${p.last_name || ''}</span>
                        <span class="text-[10px] text-on-surface-variant font-medium mt-0.5">${p.employee_id} · ${p.job_title || '-'}</span>
                    </div>
                </td>
                <td class="p-4 text-right font-mono">${window.formatIDR(p.base_salary)}</td>
                <td class="p-4 text-right font-mono text-gray-650">${window.formatIDR(p.tunj_jabatan)}</td>
                <td class="p-4 text-right font-mono text-gray-650">${window.formatIDR(flatTunj)}</td>
                <td class="p-4 text-right font-mono">
                    ${isDraft 
                        ? `<input type="text" class="input-bonus w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-right py-1 px-2.5 rounded-lg focus:outline-none" 
                             value="${formattedBonus}" onchange="window.updateBonus('${p.id}', this)"
                             oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\\B(?=(\\d{3})+(?!\\d))/g, ',');" />`
                        : `<span class="text-gray-850 font-bold">${window.formatIDR(p.bonus)}</span>`
                    }
                </td>
                <td class="p-4 text-right font-mono">
                    ${isDraft 
                        ? `<input type="text" class="input-bonus w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-right py-1 px-2.5 rounded-lg focus:outline-none" 
                             value="${formattedOvertime}" onchange="window.updateOvertime('${p.id}', this)"
                             oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\\B(?=(\\d{3})+(?!\\d))/g, ',');" />`
                        : `<span class="text-gray-850 font-bold">${window.formatIDR(p.overtime || 0)}</span>`
                    }
                </td>
                <td class="p-4 text-right font-mono text-emerald-700">+${window.formatIDR(p.reimbursement)}</td>
                <td class="p-4 text-right font-mono text-red-600">-${window.formatIDR(deductions)}</td>
                <td class="p-4 text-right font-mono text-red-600">
                    ${isDraft 
                        ? `<input type="text" class="input-bonus w-full bg-surface-container-low border border-outline-variant/30 text-red-600 text-right py-1 px-2.5 rounded-lg focus:outline-none" 
                             value="${formattedOtherDeduction}" onchange="window.updateOtherDeduction('${p.id}', this)"
                             oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\\B(?=(\\d{3})+(?!\\d))/g, ',');" />`
                        : `<span class="text-red-650 font-bold">-${window.formatIDR(p.other_deduction || 0)}</span>`
                    }
                </td>
                <td class="p-4 text-right font-mono font-bold text-primary text-[13px]" id="net-${p.id}">${window.formatIDR(p.net_salary)}</td>
                <td class="p-4 text-center">
                    <span class="badge-status ${statusClass}">
                        <span class="material-symbols-outlined text-xs font-bold">
                            ${p.status === 'Draft' ? 'draft' : (p.status === 'Approved' ? 'verified' : 'task_alt')}
                        </span>
                        ${p.status}
                    </span>
                </td>
                <td class="p-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                        <button onclick="window.previewPayslip('${p.id}')" title="Detail Slip Gaji"
                            class="p-1.5 text-primary hover:bg-primary/5 rounded-lg transition-colors flex items-center justify-center">
                            <span class="material-symbols-outlined text-[17px] font-bold">visibility</span>
                        </button>
                        ${isDraft 
                            ? `<button onclick="window.updateSingleStatus('${p.id}', 'Approved')" title="Approve"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[17px] font-bold">verified</span>
                                </button>
                                <button onclick="window.deletePayrollDraft('${p.id}')" title="Hapus Draf"
                                    class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[17px] font-bold">delete</span>
                                </button>`
                            : ''
                        }
                        ${p.status === 'Approved'
                            ? `<button onclick="window.updateSingleStatus('${p.id}', 'Paid')" title="Mark as Paid"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[17px] font-bold">payments</span>
                                </button>`
                            : ''
                        }
                    </div>
                </td>
            `;
            
            body.appendChild(tr);
        });

        // Reset check all state
        document.getElementById('selectAllCheckbox').checked = false;
        window.updateSelectedCount();
    }

    // Toggle select all row checkboxes
    window.toggleSelectAll = function(chk) {
        document.querySelectorAll('.row-checkbox').forEach(c => {
            c.checked = chk.checked;
        });
        window.updateSelectedCount();
    }

    // Update the selected row count badge and show/hide batch action buttons
    window.updateSelectedCount = function() {
        const checked = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(c => c.value);
        const selectedBadge = document.getElementById('selectedCount');
        const btnApproveAll = document.getElementById('btnApproveAll');
        const btnPayAll = document.getElementById('btnPayAll');

        if (checked.length > 0) {
            selectedBadge.textContent = `${checked.length} dipilih`;
            selectedBadge.classList.remove('hidden');
            
            // Check roles and show appropriate batch buttons
            let hasDrafts = false;
            let hasApproved = false;
            
            checked.forEach(id => {
                const payroll = payrollData.find(p => p.id === id);
                if (payroll) {
                    if (payroll.status === 'Draft') hasDrafts = true;
                    if (payroll.status === 'Approved') hasApproved = true;
                }
            });

            if (hasDrafts) btnApproveAll.classList.remove('hidden');
            else btnApproveAll.classList.add('hidden');

            if (hasApproved) btnPayAll.classList.remove('hidden');
            else btnPayAll.classList.add('hidden');
        } else {
            selectedBadge.classList.add('hidden');
            btnApproveAll.classList.add('hidden');
            btnPayAll.classList.add('hidden');
        }
    }

    // Generate Payroll
    window.generatePayroll = function() {
        const period = window.getPeriodFromInput();
        const periodLabel = window.getSelectedPeriodLabel();
        if (!period) return;

        Swal.fire({
            title: 'Generate Payroll?',
            text: `Sistem akan memproses rekapitulasi data dan membuat draf payroll untuk periode ${periodLabel}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Proses!',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;

            Swal.fire({
                title: 'Memproses...',
                html: 'Menghitung gaji, tunjangan, potongan cuti, pajak PPh21, dan klaim reimbursement...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const fd = new FormData();
            fd.append('month_year', period);

            window.safeFetch('/hrops/payroll/generate', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({ title: 'Berhasil!', text: data.message, icon: 'success', confirmButtonColor: '#000666' }).then(() => {
                        window.loadPayrollData();
                    });
                } else {
                    Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
                }
            })
            .catch((err) => {
                Swal.close();
                Swal.fire({ 
                    title: 'Error', 
                    html: `Gagal memproses draf payroll:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                    icon: 'error', 
                    confirmButtonColor: '#ba1a1a' 
                });
            });
        });
    }

    // Update Bonus inline
    window.updateBonus = function(id, input) {
        const val = input.value;
        input.classList.add('bg-amber-50');

        const fd = new FormData();
        fd.append('id', id);
        fd.append('bonus', val);

        window.safeFetch('/hrops/payroll/update-bonus', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(data => {
            input.classList.remove('bg-amber-50');
            if (data.success) {
                const row = document.getElementById(`row-${id}`);
                row.classList.add('bg-green-50/50');
                setTimeout(() => row.classList.remove('bg-green-50/50'), 600);

                const pIndex = payrollData.findIndex(p => p.id === id);
                if (pIndex !== -1) {
                    const rawBonus = parseFloat(val.replace(/,/g, ''));
                    payrollData[pIndex].bonus = rawBonus;
                    payrollData[pIndex].pph21 = parseFloat(data.pph21_formatted.replace(/[^0-9]/g, ''));
                    payrollData[pIndex].net_salary = parseFloat(data.net_salary_formatted.replace(/[^0-9]/g, ''));
                }

                window.renderTable();
                window.calculateSummaryStats();
            } else {
                Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' }).then(() => {
                    window.loadPayrollData();
                });
            }
        })
        .catch((err) => {
            input.classList.remove('bg-amber-50');
            Swal.fire({ 
                title: 'Error', 
                html: `Gagal memperbarui bonus:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                icon: 'error', 
                confirmButtonColor: '#ba1a1a' 
            }).then(() => {
                window.loadPayrollData();
            });
        });
    }

    // Update Overtime inline
    window.updateOvertime = function(id, input) {
        const val = input.value;
        input.classList.add('bg-amber-50');

        const fd = new FormData();
        fd.append('id', id);
        fd.append('overtime', val);

        window.safeFetch('/hrops/payroll/update-overtime', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(data => {
            input.classList.remove('bg-amber-50');
            if (data.success) {
                const row = document.getElementById(`row-${id}`);
                row.classList.add('bg-green-50/50');
                setTimeout(() => row.classList.remove('bg-green-50/50'), 600);

                const pIndex = payrollData.findIndex(p => p.id === id);
                if (pIndex !== -1) {
                    const rawOvertime = parseFloat(val.replace(/,/g, ''));
                    payrollData[pIndex].overtime = rawOvertime;
                    payrollData[pIndex].pph21 = parseFloat(data.pph21_formatted.replace(/[^0-9]/g, ''));
                    payrollData[pIndex].net_salary = parseFloat(data.net_salary_formatted.replace(/[^0-9]/g, ''));
                }

                window.renderTable();
                window.calculateSummaryStats();
            } else {
                Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' }).then(() => {
                    window.loadPayrollData();
                });
            }
        })
        .catch((err) => {
            input.classList.remove('bg-amber-50');
            Swal.fire({ 
                title: 'Error', 
                html: `Gagal memperbarui lemburan:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                icon: 'error', 
                confirmButtonColor: '#ba1a1a' 
            }).then(() => {
                window.loadPayrollData();
            });
        });
    }

    // Update Other Deduction inline
    window.updateOtherDeduction = function(id, input) {
        const val = input.value;
        input.classList.add('bg-amber-50');

        const fd = new FormData();
        fd.append('id', id);
        fd.append('other_deduction', val);

        window.safeFetch('/hrops/payroll/update-other-deduction', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(data => {
            input.classList.remove('bg-amber-50');
            if (data.success) {
                const row = document.getElementById(`row-${id}`);
                row.classList.add('bg-green-50/50');
                setTimeout(() => row.classList.remove('bg-green-50/50'), 600);

                const pIndex = payrollData.findIndex(p => p.id === id);
                if (pIndex !== -1) {
                    const rawOtherDeduction = parseFloat(val.replace(/,/g, ''));
                    payrollData[pIndex].other_deduction = rawOtherDeduction;
                    payrollData[pIndex].pph21 = parseFloat(data.pph21_formatted.replace(/[^0-9]/g, ''));
                    payrollData[pIndex].net_salary = parseFloat(data.net_salary_formatted.replace(/[^0-9]/g, ''));
                }

                window.renderTable();
                window.calculateSummaryStats();
            } else {
                Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' }).then(() => {
                    window.loadPayrollData();
                });
            }
        })
        .catch((err) => {
            input.classList.remove('bg-amber-50');
            Swal.fire({ 
                title: 'Error', 
                html: `Gagal memperbarui potongan lainnya:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                icon: 'error', 
                confirmButtonColor: '#ba1a1a' 
            }).then(() => {
                window.loadPayrollData();
            });
        });
    }

    // Update single status
    window.updateSingleStatus = function(id, status) {
        const textLabel = status === 'Paid' ? 'mengubah status menjadi Lunas/Telah Dibayar (Paid)? Tanggal bayar akan tercatat hari ini.' : 'menyetujui draf payroll ini?';
        
        Swal.fire({
            title: 'Ubah Status?',
            text: `Apakah Anda yakin ingin ${textLabel}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Ubah!',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;

            const fd = new FormData();
            fd.append('id', id);
            fd.append('status', status);

            window.safeFetch('/hrops/payroll/update-status', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Tersimpan!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#000666',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.loadPayrollData();
                    });
                } else {
                    Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
                }
            })
            .catch((err) => {
                Swal.fire({ 
                    title: 'Error', 
                    html: `Koneksi gagal:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                    icon: 'error', 
                    confirmButtonColor: '#ba1a1a' 
                });
            });
        });
    }

    // Batch status update
    window.batchStatusUpdate = function(status) {
        const checked = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(c => c.value);
        if (checked.length === 0) return;

        const textLabel = status === 'Paid' ? `membayar ${checked.length} payroll terpilih secara massal? Tanggal bayar akan tercatat hari ini.` : `menyetujui ${checked.length} draf payroll terpilih secara massal?`;

        Swal.fire({
            title: 'Aksi Massal?',
            text: `Apakah Anda yakin ingin ${textLabel}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Proses!',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;

            const fd = new FormData();
            checked.forEach(id => {
                fd.append('ids[]', id);
            });
            fd.append('status', status);

            window.safeFetch('/hrops/payroll/update-status', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({ title: 'Berhasil!', text: data.message, icon: 'success', confirmButtonColor: '#000666' }).then(() => {
                        window.loadPayrollData();
                    });
                } else {
                    Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
                }
            })
            .catch((err) => {
                Swal.fire({ 
                    title: 'Error', 
                    html: `Koneksi gagal:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                    icon: 'error', 
                    confirmButtonColor: '#ba1a1a' 
                });
            });
        });
    }

    // Delete single payroll draft
    window.deletePayrollDraft = function(id) {
        Swal.fire({
            title: 'Hapus Draf?',
            text: 'Apakah Anda yakin ingin menghapus catatan draf payroll karyawan ini? Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;

            const fd = new FormData();
            fd.append('id', id);

            window.safeFetch('/hrops/payroll/delete', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#000666',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.loadPayrollData();
                    });
                } else {
                    Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
                }
            })
            .catch((err) => {
                Swal.fire({ 
                    title: 'Error', 
                    html: `Koneksi gagal:<br><pre class="text-[10px] text-red-650 font-mono text-left bg-surface-container-low p-3 mt-2 rounded max-h-48 overflow-y-auto whitespace-pre-wrap">${err.message}</pre>`, 
                    icon: 'error', 
                    confirmButtonColor: '#ba1a1a' 
                });
            });
        });
    }

    // Preview detailed payslip
    window.previewPayslip = function(id) {
        const p = payrollData.find(item => item.id === id);
        if (!p) return;

        const periodLabel = window.getSelectedPeriodLabel();
        const baseSalary = parseFloat(p.base_salary);
        const tunjJab = parseFloat(p.tunj_jabatan);
        const tunjTrans = parseFloat(p.tunj_transport_makan);
        const tunjKom = parseFloat(p.tunj_komunikasi);
        const reimburse = parseFloat(p.reimbursement);
        const bonus = parseFloat(p.bonus);
        const overtime = parseFloat(p.overtime || 0);
        
        const bpjsTk = parseFloat(p.bpjs_tk);
        const bpjsKes = parseFloat(p.bpjs_kes);
        const pph21 = parseFloat(p.pph21);
        const unpaidDec = parseFloat(p.unpaid_leave_deduction || 0);
        const unpaidDays = parseInt(p.unpaid_leave_days || 0);
        const alpaDec = parseFloat(p.alpa_deduction || 0);
        const alpaDays = parseInt(p.alpa_days || 0);
        const lateDec = parseFloat(p.late_deduction || 0);
        const lateDays = parseInt(p.late_days || 0);
        const otherDeduction = parseFloat(p.other_deduction || 0);

        const totalEarnings = baseSalary + tunjJab + tunjTrans + tunjKom + reimburse + bonus + overtime;
        const totalDeductions = bpjsTk + bpjsKes + pph21 + unpaidDec + alpaDec + lateDec + otherDeduction;
        const netSalary = parseFloat(p.net_salary);

        // Calculate payslip number and barcode number
        const parts = p.month_year.split('-');
        const datePart = parts[1] + parts[0] + '25';
        const empIdClean = p.employee_id ? p.employee_id.split('-').pop() : '0000';
        const payslipNo = `SLIP/PAY/${datePart}-${empIdClean}`;
        const barcodeNo = `PAY-${datePart}-${empIdClean}`;

        // Fill modal fields
        document.getElementById('payslipNo').textContent = payslipNo;
        document.getElementById('slipPeriod').textContent = periodLabel;
        document.getElementById('slipName').textContent = `${p.first_name} ${p.last_name || ''}`;
        document.getElementById('slipNik').textContent = p.employee_id || '-';
        const titleAndDept = p.department_name ? `${p.job_title || 'Karyawan'} / ${p.department_name}` : (p.job_title || '-');
        document.getElementById('slipTitle').textContent = titleAndDept;
        document.getElementById('slipBank').textContent = `${p.bank_name || '-'} - ${p.bank_account_number || '-'}`;
        document.getElementById('slipBankHolder').textContent = `A/N ${p.first_name} ${p.last_name || ''}`;
        document.getElementById('slipNpwp').textContent = p.npwp_number || '-';
        document.getElementById('slipWorkDays').textContent = `${p.present_days || 0} / ${p.working_days || 22} Hari Kerja`;

        document.getElementById('slipBaseSalary').textContent = window.formatIDR(baseSalary);
        document.getElementById('slipTunjJabatan').textContent = window.formatIDR(tunjJab);
        document.getElementById('slipTunjTransport').textContent = window.formatIDR(tunjTrans);
        document.getElementById('slipTunjKomunikasi').textContent = window.formatIDR(tunjKom);

        // Hide/show dynamic rows
        if (reimburse > 0) {
            document.getElementById('slipReimbursementRow').style.display = 'flex';
            document.getElementById('slipReimbursement').textContent = window.formatIDR(reimburse);
        } else {
            document.getElementById('slipReimbursementRow').style.display = 'none';
        }

        if (bonus > 0) {
            document.getElementById('slipBonusRow').style.display = 'flex';
            document.getElementById('slipBonus').textContent = window.formatIDR(bonus);
        } else {
            document.getElementById('slipBonusRow').style.display = 'none';
        }

        if (overtime > 0) {
            document.getElementById('slipOvertimeRow').style.display = 'flex';
            document.getElementById('slipOvertime').textContent = window.formatIDR(overtime);
        } else {
            document.getElementById('slipOvertimeRow').style.display = 'none';
        }

        document.getElementById('slipBpjsTk').textContent = window.formatIDR(bpjsTk);
        document.getElementById('slipBpjsKes').textContent = window.formatIDR(bpjsKes);
        document.getElementById('slipPph21').textContent = window.formatIDR(pph21);
        
        if (unpaidDec > 0) {
            document.getElementById('slipUnpaidRow').style.display = 'flex';
            document.getElementById('slipUnpaidDaysBadge').textContent = `${unpaidDays} Hari`;
            document.getElementById('slipUnpaidDeduction').textContent = window.formatIDR(unpaidDec);
        } else {
            document.getElementById('slipUnpaidRow').style.display = 'none';
        }

        if (alpaDec > 0) {
            document.getElementById('slipAlpaRow').style.display = 'flex';
            document.getElementById('slipAlpaDaysBadge').textContent = `${alpaDays} Hari`;
            document.getElementById('slipAlpaDeduction').textContent = window.formatIDR(alpaDec);
        } else {
            document.getElementById('slipAlpaRow').style.display = 'none';
        }

        if (lateDec > 0) {
            document.getElementById('slipLateRow').style.display = 'flex';
            document.getElementById('slipLateDaysBadge').textContent = `${lateDays} Kali`;
            document.getElementById('slipLateDeduction').textContent = window.formatIDR(lateDec);
        } else {
            document.getElementById('slipLateRow').style.display = 'none';
        }

        if (otherDeduction > 0) {
            document.getElementById('slipOtherDeductionRow').style.display = 'flex';
            document.getElementById('slipOtherDeduction').textContent = window.formatIDR(otherDeduction);
        } else {
            document.getElementById('slipOtherDeductionRow').style.display = 'none';
        }

        document.getElementById('slipTotalEarnings').textContent = window.formatIDR(totalEarnings);
        document.getElementById('slipTotalDeductions').textContent = window.formatIDR(totalDeductions);
        document.getElementById('slipNetSalary').textContent = window.formatIDR(netSalary);
        document.getElementById('slipBarcodeNo').textContent = barcodeNo;

        // Open modal with transition
        const modal = document.getElementById('payslipModal');
        const container = document.getElementById('payslipModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            container.classList.remove('scale-95');
        }, 50);
    }

    window.closePayslipModal = function() {
        const modal = document.getElementById('payslipModal');
        const container = document.getElementById('payslipModalContainer');
        modal.classList.add('opacity-0');
        container.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    window.printPayslipFromModal = function() {
        const sourceHtml = document.getElementById('payslipPrintArea').innerHTML;
        
        // Remove any existing printArea
        const oldPrintArea = document.getElementById('printArea');
        if (oldPrintArea) oldPrintArea.remove();
        
        // Create dynamic printArea directly under body
        const printArea = document.createElement('div');
        printArea.id = 'printArea';
        printArea.innerHTML = sourceHtml;
        document.body.appendChild(printArea);
        
        // Add printing class to body
        document.body.classList.add('printing-active');
        
        // Trigger system print
        window.print();
        
        // Clean up
        document.body.classList.remove('printing-active');
        printArea.remove();
    }

    // Close modal on click outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('payslipModal');
        if (e.target === modal) {
            window.closePayslipModal();
        }
    });

    // Load data on start
    window.loadPayrollData();
</script>