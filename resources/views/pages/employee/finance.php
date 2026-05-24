<?php
$sessName  = $_SESSION['name'] ?? 'Alex Rivera';
$sessEmail = $_SESSION['email'] ?? 'alex.rivera@example.com';
$sessRole  = $_SESSION['role'] ?? 'employee';

// Fetch dynamic user data from DB
$db = \App\Config\Database::getInstance()->getConnection();
$userQuery = $db->prepare("SELECT * FROM users WHERE id = :id");
$userQuery->execute(['id' => $_SESSION['user_id']]);
$dbUser = $userQuery->fetch();

$employeeId = $dbUser['employee_id'] ?? 'EMP-2026-0033';
$ktpNik = $dbUser['ktp_nik'] ?? '3275012309990001';
$namaSesuaiKtp = $dbUser['nama_sesuai_ktp'] ?? $sessName;
$bankName = $dbUser['bank_name'] ?? 'Bank Central Asia (BCA)';
$bankAccountNumber = $dbUser['bank_account_number'] ?? '8012345678';
$npwpNumber = $dbUser['npwp_number'] ?? '12.345.678.9-012.000';
$bpjsTk = $dbUser['bpjs_tk'] ?? '12098765432';
$bpjsKes = $dbUser['bpjs_kes'] ?? '0001234567890';
$tanggalLahir = $dbUser['tanggal_lahir'] ?? '12 September 1995';
$statusPernikahan = $dbUser['status_pernikahan'] ?? 'Belum Menikah';
$jenisKelamin = $dbUser['jenis_kelamin'] ?? 'Laki-Laki';

// Base Salary setup based on profile
$baseSalary = !empty($dbUser['base_salary']) ? (float)$dbUser['base_salary'] : 12000000;

// Generate monthly payslips dynamically (January - May 2026)
$months = [
    [
        'code' => '05-2026',
        'month_name' => 'Mei 2026',
        'date' => '25 Mei 2026',
        'tunj_jabatan' => 2500000,
        'tunj_transport_makan' => 1500000,
        'tunj_komunikasi' => 500000,
        'bonus' => 0,
        'thr' => 0,
        'pph21' => round($baseSalary * 0.025),
    ],
    [
        'code' => '04-2026',
        'month_name' => 'April 2026',
        'date' => '24 April 2026',
        'tunj_jabatan' => 2500000,
        'tunj_transport_makan' => 1500000,
        'tunj_komunikasi' => 500000,
        'bonus' => 0,
        'thr' => $baseSalary, // Eid THR equivalent to 1-month salary!
        'pph21' => round($baseSalary * 0.06), // Higher tax rate on THR month
    ],
    [
        'code' => '03-2026',
        'month_name' => 'Maret 2026',
        'date' => '25 Maret 2026',
        'tunj_jabatan' => 2500000,
        'tunj_transport_makan' => 1350000, // Slightly lower due to remote days
        'tunj_komunikasi' => 500000,
        'bonus' => 500000, // Project incentive
        'thr' => 0,
        'pph21' => round($baseSalary * 0.026),
    ],
    [
        'code' => '02-2026',
        'month_name' => 'Februari 2026',
        'date' => '25 Februari 2026',
        'tunj_jabatan' => 2500000,
        'tunj_transport_makan' => 1450000,
        'tunj_komunikasi' => 500000,
        'bonus' => 0,
        'thr' => 0,
        'pph21' => round($baseSalary * 0.024),
    ],
    [
        'code' => '01-2026',
        'month_name' => 'Januari 2026',
        'date' => '25 Januari 2026',
        'tunj_jabatan' => 2500000,
        'tunj_transport_makan' => 1500000,
        'tunj_komunikasi' => 500000,
        'bonus' => 1000000, // New year bonus
        'thr' => 0,
        'pph21' => round($baseSalary * 0.028),
    ],
];

// Calculated variables for latest month (Mei 2026)
$latest = $months[0];
$latestTotalEarnings = $baseSalary + $latest['tunj_jabatan'] + $latest['tunj_transport_makan'] + $latest['tunj_komunikasi'] + $latest['bonus'] + $latest['thr'];
$latestBpjsTk = round($baseSalary * 0.02); // 2% employee contribution
$latestBpjsKes = round($baseSalary * 0.01); // 1% employee contribution
$latestTotalDeductions = $latestBpjsTk + $latestBpjsKes + $latest['pph21'];
$latestNetPay = $latestTotalEarnings - $latestTotalDeductions;
?>

<style>
    @media print {
        body {
            visibility: hidden !important;
        }
        #printArea, #printArea * {
            visibility: visible !important;
        }
        #printArea {
            display: block !important;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            background: white !important;
            color: black !important;
            padding: 24px !important;
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight mt-1">Finansial Mandiri</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola slip gaji bulanan, rekening payroll, kepesertaan sosial, dan dokumen perpajakan Anda.</p>
        </div>
    </div>

    <!-- Bento Grid Metrik Keuangan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Bento Card 1: Last Salary Received -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.03)] relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-primary/5 rounded-full blur-xl group-hover:bg-primary/10 transition-colors duration-300"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Gaji Bersih Bulan Ini</span>
                <span class="material-symbols-outlined text-primary text-2xl bg-primary/5 p-2 rounded-xl">payments</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-on-surface font-headline tracking-tight">
                    Rp <?= number_format($latestNetPay, 0, ',', '.') ?>
                </h3>
                <p class="text-[11px] font-semibold text-green-600 flex items-center gap-1 mt-1">
                    <span class="material-symbols-outlined text-xs">check_circle</span>
                    Ditransfer pada <?= $latest['date'] ?>
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-outline-variant/10 text-xs text-on-surface-variant flex justify-between font-medium">
                <span>Metode Transfer</span>
                <span class="font-bold text-on-surface">Payroll Auto-Credit</span>
            </div>
        </div>

        <!-- Bento Card 2: Payroll Bank Account -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.03)] relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-blue-600/5 rounded-full blur-xl group-hover:bg-blue-600/10 transition-colors duration-300"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Rekening Penerima Payroll</span>
                <span class="material-symbols-outlined text-blue-700 text-2xl bg-blue-500/5 p-2 rounded-xl">account_balance</span>
            </div>
            <div class="mt-4">
                <h3 class="text-xl font-extrabold text-on-surface font-headline leading-tight">
                    <?= htmlspecialchars($bankName) ?>
                </h3>
                <p class="text-xs font-mono font-bold text-primary bg-primary/5 px-2 py-0.5 rounded inline-block mt-1">
                    <?= htmlspecialchars($bankAccountNumber) ?>
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-outline-variant/10 text-xs text-on-surface-variant flex justify-between font-medium">
                <span>Atas Nama</span>
                <span class="font-bold text-on-surface uppercase"><?= htmlspecialchars($namaSesuaiKtp) ?></span>
            </div>
        </div>

        <!-- Bento Card 3: Social & Tax Membership -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.03)] relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-600/5 rounded-full blur-xl group-hover:bg-indigo-600/10 transition-colors duration-300"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Proteksi Sosial & Pajak</span>
                <span class="material-symbols-outlined text-indigo-700 text-2xl bg-indigo-500/5 p-2 rounded-xl">verified_user</span>
            </div>
            <div class="mt-3 space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-on-surface-variant font-medium">NPWP</span>
                    <span class="font-mono font-bold text-on-surface flex items-center gap-1">
                        <?= htmlspecialchars($npwpNumber) ?>
                        <span class="material-symbols-outlined text-green-500 text-sm font-bold">verified</span>
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-on-surface-variant font-medium">BPJS Ketenagakerjaan</span>
                    <span class="font-mono font-bold text-on-surface flex items-center gap-1">
                        <?= htmlspecialchars($bpjsTk) ?>
                        <span class="material-symbols-outlined text-green-500 text-sm font-bold">verified</span>
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-on-surface-variant font-medium">BPJS Kesehatan</span>
                    <span class="font-mono font-bold text-on-surface flex items-center gap-1">
                        <?= htmlspecialchars($bpjsKes) ?>
                        <span class="material-symbols-outlined text-green-500 text-sm font-bold">verified</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left: Payslips History Table -->
        <div class="lg:col-span-8 bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.02)] overflow-hidden">
            <div class="p-6 border-b border-outline-variant/10 flex justify-between items-center bg-gradient-to-r from-surface-container-lowest to-surface-container-low/30">
                <div>
                    <h2 class="text-lg font-extrabold text-on-surface font-headline">Riwayat Slip Gaji</h2>
                    <p class="text-xs text-on-surface-variant mt-0.5">Daftar slip gaji resmi Anda untuk periode berjalan di tahun 2026.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 border-b border-outline-variant/10">
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Periode</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Gaji Pokok</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Tunjangan</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Potongan</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Gaji Bersih (THP)</th>
                            <th class="p-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/8">
                        <?php foreach ($months as $m): 
                            $totEarn = $baseSalary + $m['tunj_jabatan'] + $m['tunj_transport_makan'] + $m['tunj_komunikasi'] + $m['bonus'] + $m['thr'];
                            $bpjsTkContrib = round($baseSalary * 0.02);
                            $bpjsKesContrib = round($baseSalary * 0.01);
                            $totDed = $bpjsTkContrib + $bpjsKesContrib + $m['pph21'];
                            $netPay = $totEarn - $totDed;
                            $tunjanganTotal = $m['tunj_jabatan'] + $m['tunj_transport_makan'] + $m['tunj_komunikasi'] + $m['bonus'] + $m['thr'];
                        ?>
                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                            <td class="p-4 font-bold text-xs text-on-surface"><?= $m['month_name'] ?></td>
                            <td class="p-4 text-xs font-mono font-medium text-on-surface-variant">Rp <?= number_format($baseSalary, 0, ',', '.') ?></td>
                            <td class="p-4 text-xs font-mono font-medium text-green-700 bg-green-50/40">Rp <?= number_format($tunjanganTotal, 0, ',', '.') ?></td>
                            <td class="p-4 text-xs font-mono font-medium text-red-600 bg-red-50/20">Rp <?= number_format($totDed, 0, ',', '.') ?></td>
                            <td class="p-4 text-xs font-mono font-bold text-primary">Rp <?= number_format($netPay, 0, ',', '.') ?></td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="previewPayslip('<?= $m['code'] ?>')" class="bg-primary/5 hover:bg-primary/10 text-primary p-2 rounded-lg transition-all hover:scale-105 active:scale-95" title="Pratinjau Slip">
                                        <span class="material-symbols-outlined text-sm font-bold">visibility</span>
                                    </button>
                                    <button onclick="printPayslipDirect('<?= $m['code'] ?>')" class="bg-primary/5 hover:bg-primary/10 text-primary p-2 rounded-lg transition-all hover:scale-105 active:scale-95" title="Cetak Slip">
                                        <span class="material-symbols-outlined text-sm font-bold">print</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Tax Documents Section -->
        <div class="lg:col-span-4 bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.02)] p-6 space-y-6">
            <div>
                <h2 class="text-lg font-extrabold text-on-surface font-headline">Dokumen Perpajakan</h2>
                <p class="text-xs text-on-surface-variant mt-0.5">Unduh dan tinjau berkas bukti potong pajak penghasilan tahunan Anda.</p>
            </div>

            <!-- Tax Card 1721-A1 -->
            <div class="bg-gradient-to-br from-indigo-550 to-primary text-white rounded-xl p-5 relative overflow-hidden shadow-md group">
                <div class="absolute -top-12 -right-12 w-28 h-28 bg-white/5 rounded-full blur-lg group-hover:bg-white/10 transition-colors"></div>
                <div class="relative z-10 space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="bg-white/20 text-white font-extrabold text-[9px] tracking-wider px-2 py-0.5 rounded uppercase">RESMI DJP</span>
                            <h4 class="text-md font-bold font-headline mt-1.5 leading-tight">Bukti Potong PPh 21<br>Formulir 1721-A1</h4>
                        </div>
                        <span class="material-symbols-outlined text-3xl opacity-75">assignment_turned_in</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs text-white/80">
                            <span>Tahun Pajak</span>
                            <span class="font-bold text-white">2025</span>
                        </div>
                        <div class="flex justify-between text-xs text-white/80">
                            <span>Status Laporan</span>
                            <span class="font-bold text-green-300 flex items-center gap-1 text-[11px]">
                                <span class="material-symbols-outlined text-xs">verified</span> SELESAI
                            </span>
                        </div>
                    </div>
                    <button onclick="previewTaxA1()" class="w-full bg-white text-primary hover:bg-white/95 font-extrabold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-all shadow-sm hover:scale-[1.02] active:scale-95 duration-200">
                        <span class="material-symbols-outlined text-sm">visibility</span> Pratinjau Form 1721-A1
                    </button>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/10 text-xs text-on-surface-variant leading-relaxed">
                <div class="flex gap-2">
                    <span class="material-symbols-outlined text-primary text-lg flex-shrink-0">info</span>
                    <div>
                        <span class="font-bold text-on-surface">Pelaporan SPT Tahunan:</span> Bukti potong Formulir 1721-A1 merupakan dokumen wajib untuk melakukan pelaporan SPT Tahunan Wajib Pajak Orang Pribadi melalui e-Filing DJP Online.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- DETAILED PAYSLIP PREVIEW MODAL -->
<!-- ============================================== -->
<div id="payslipModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
    <div class="bg-white text-black w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden scale-95 transform transition-transform duration-300 border border-neutral-200" id="payslipModalContainer">
        <!-- Modal Toolbar -->
        <div class="bg-neutral-100 px-6 py-3 border-b border-neutral-200 flex justify-between items-center no-print">
            <span class="text-xs font-bold text-neutral-600 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">print_disabled</span> Mode Pratinjau Dokumen Resmi
            </span>
            <div class="flex items-center gap-2">
                <button onclick="printPayslipFromModal()" class="bg-primary text-white hover:bg-primary/95 font-bold text-xs py-2 px-4 rounded-lg flex items-center gap-1.5 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-sm">print</span> Cetak / Unduh PDF
                </button>
                <button onclick="closePayslipModal()" class="bg-neutral-200 text-neutral-700 hover:bg-neutral-300 font-bold text-xs py-2 px-3 rounded-lg flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-sm">close</span> Tutup
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Content (The Actual Payslip) -->
        <div class="p-8 md:p-12 overflow-y-auto bg-white flex-grow relative" id="payslipPrintArea">
            <!-- Circular Digital Stamp SVG Overlay -->
            <div class="absolute bottom-8 right-8 md:right-20 pointer-events-none select-none z-10">
                <svg class="w-32 h-32 text-blue-800/80 transform rotate-12" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="2.5" stroke-dasharray="3, 1.5" />
                    <circle cx="50" cy="50" r="37" fill="none" stroke="currentColor" stroke-width="1.5" />
                    <path id="stampPath" fill="none" d="M 17 50 A 33 33 0 1 1 83 50" />
                    <text font-size="6.5" font-weight="900" letter-spacing="1.5" fill="currentColor">
                        <textPath href="#stampPath" startOffset="50%" text-anchor="middle">
                            PT SI CARE ENTERPRISE
                        </textPath>
                    </text>
                    <path id="stampPathBottom" fill="none" d="M 83 50 A 33 33 0 0 1 17 50" />
                    <text font-size="6.5" font-weight="900" letter-spacing="1.2" fill="currentColor">
                        <textPath href="#stampPathBottom" startOffset="50%" text-anchor="middle">
                            * HR OPERATIONS *
                        </textPath>
                    </text>
                    <circle cx="50" cy="50" r="23" fill="none" stroke="currentColor" stroke-width="1" />
                    <text x="50" y="47" font-size="7" font-weight="900" text-anchor="middle" fill="currentColor">PAID</text>
                    <text x="50" y="57" font-size="6.5" font-weight="800" text-anchor="middle" fill="currentColor">VERIFIED</text>
                </svg>
            </div>

            <!-- Header Corporate -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b-2 border-neutral-900 pb-6 gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-primary text-white p-2.5 rounded-xl flex items-center justify-center shadow-md">
                        <span class="material-symbols-outlined text-2xl font-bold">local_police</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-neutral-900 tracking-tight leading-none">siCare</h2>
                        <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-widest mt-1 block">PT SI CARE ENTERPRISE</span>
                    </div>
                </div>
                <div class="text-left md:text-right">
                    <h3 class="text-lg font-black text-neutral-900 uppercase tracking-wider">SLIP GAJI KARYAWAN</h3>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5" id="payslipNo">SLIP/PAY/20260525-0033</p>
                </div>
            </div>

            <!-- Employee & General Info Metadata Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 py-6 border-b border-neutral-250 text-xs">
                <div>
                    <span class="text-neutral-500 block mb-1">ID KARYAWAN</span>
                    <span class="font-mono font-bold text-neutral-800" id="payEmpId"><?= htmlspecialchars($employeeId) ?></span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">NAMA KARYAWAN</span>
                    <span class="font-bold text-neutral-800 uppercase" id="payEmpName"><?= htmlspecialchars($namaSesuaiKtp) ?></span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">JABATAN / DIVISI</span>
                    <span class="font-bold text-neutral-800" id="payEmpDept">Senior UI/UX Designer / Product</span>
                </div>
                <div>
                    <span class="text-neutral-500 block mb-1">PERIODE PEMBAYARAN</span>
                    <span class="font-bold text-neutral-800" id="payPeriod">Mei 2026</span>
                </div>
            </div>

            <!-- Financial Details Structure -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-6 items-start">
                <!-- EARNINGS (PENDAPATAN) -->
                <div class="space-y-4">
                    <div class="bg-neutral-100 px-4 py-2 border-l-4 border-green-600">
                        <h4 class="text-xs font-bold text-neutral-850 uppercase tracking-wider">I. PENDAPATAN (EARNINGS)</h4>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Gaji Pokok (Base Salary)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBase">Rp 16.500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Jabatan</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payTunjJabatan">Rp 2.500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Transportasi & Makan</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payTunjTransport">Rp 1.500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Tunjangan Komunikasi</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payTunjKomunikasi">Rp 500.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payBonusRow">
                            <span class="text-neutral-600">Bonus & Insentif Kerja</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBonus">Rp 0</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100" id="payThrRow">
                            <span class="text-neutral-600">Tunjangan Hari Raya (THR)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payThr">Rp 0</span>
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
                            <span class="text-neutral-600">BPJS Ketenagakerjaan (2%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBpjsTk">Rp 330.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">BPJS Kesehatan (1%)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payBpjsKes">Rp 165.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Pajak Penghasilan (PPh 21)</span>
                            <span class="font-mono font-semibold text-neutral-900" id="payPph21">Rp 425.000</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-100">
                            <span class="text-neutral-600">Potongan Lainnya / Keterlambatan</span>
                            <span class="font-mono font-semibold text-neutral-900">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOTAL SUMMARY PANEL -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 pb-6 border-t border-b border-neutral-250">
                <div class="space-y-2">
                    <div class="flex justify-between text-xs font-bold text-neutral-700">
                        <span>TOTAL PENDAPATAN BRUTO</span>
                        <span class="font-mono" id="payGrossTotal">Rp 21.000.000</span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-red-600">
                        <span>TOTAL POTONGAN KARYAWAN</span>
                        <span class="font-mono" id="payDeductionsTotal">Rp 920.000</span>
                    </div>
                </div>
                <div class="bg-neutral-50 p-4 rounded-xl flex flex-col justify-center border border-neutral-200">
                    <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-widest">TAKE HOME PAY (GAJI BERSIH)</span>
                    <span class="text-2xl font-black text-primary font-headline mt-1 tracking-tight" id="payNetTotal">
                        Rp 20.080.000
                    </span>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="pt-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 text-xs text-neutral-700">
                <div>
                    <span class="text-neutral-500 block mb-1">REKENING PAYROLL TUJUAN</span>
                    <span class="font-bold text-neutral-800" id="payBankInfo">BCA - 8012345678</span>
                    <span class="text-neutral-500 block mt-0.5 uppercase" id="payBankHolder">A/N ALEX RIVERA</span>
                </div>
                
                <!-- Signature Space -->
                <div class="flex flex-col items-center text-center">
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
                            <text x="50" y="19" font-size="4" font-family="monospace" text-anchor="middle" fill="#333" id="payBarcodeNo">PAY-20260525-0033</text>
                        </svg>
                    </div>
                    <span class="font-extrabold text-neutral-800 uppercase tracking-tight">Amanda Putri</span>
                    <span class="text-[10px] text-neutral-500 uppercase">HR Operations Director</span>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ============================================== -->
<!-- DETAILED FORMULIR 1721-A1 PREVIEW MODAL -->
<!-- ============================================== -->
<div id="taxModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
    <div class="bg-white text-black w-full max-w-5xl rounded-2xl shadow-2xl flex flex-col max-h-[95vh] overflow-hidden scale-95 transform transition-transform duration-300 border border-neutral-300" id="taxModalContainer">
        <!-- Modal Toolbar -->
        <div class="bg-neutral-100 px-6 py-3 border-b border-neutral-200 flex justify-between items-center no-print">
            <span class="text-xs font-bold text-neutral-600 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">verified</span> Formulir Pajak 1721-A1 Resmi (Tahun Pajak 2025)
            </span>
            <div class="flex items-center gap-2">
                <button onclick="printTaxFromModal()" class="bg-primary text-white hover:bg-primary/95 font-bold text-xs py-2 px-4 rounded-lg flex items-center gap-1.5 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-sm">print</span> Cetak Formulir A1
                </button>
                <button onclick="closeTaxModal()" class="bg-neutral-200 text-neutral-700 hover:bg-neutral-300 font-bold text-xs py-2 px-3 rounded-lg flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-sm">close</span> Tutup
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Content (DJP Form 1721-A1) -->
        <div class="p-8 md:p-12 overflow-y-auto bg-white flex-grow text-[11px]" id="taxPrintArea">
            <!-- Outer Border Box matching actual DJP forms -->
            <div class="border-2 border-neutral-800 p-6 space-y-4">
                <!-- Header Block -->
                <div class="flex items-stretch border border-neutral-800">
                    <div class="w-1/4 border-r border-neutral-800 p-3 flex flex-col items-center justify-center text-center">
                        <span class="material-symbols-outlined text-4xl text-neutral-800">local_police</span>
                        <span class="text-[9px] font-black uppercase mt-1 leading-none">KEMENTERIAN KEUANGAN RI</span>
                        <span class="text-[8px] font-bold text-neutral-500 leading-none">DIREKTORAT JENDERAL PAJAK</span>
                    </div>
                    <div class="w-1/2 border-r border-neutral-800 p-3 flex flex-col items-center justify-center text-center space-y-1">
                        <h4 class="font-black text-sm uppercase leading-tight tracking-tight">BUKTI PEMOTONGAN PAJAK PENGHASILAN PASAL 21 BAGI PEGAWAI TETAP</h4>
                        <p class="text-[9px] text-neutral-500 uppercase leading-none">ATAU PENERIMA PENSIUN ATAU TUNJANGAN HARI TUA/JAMINAN HARI TUA BERKALA</p>
                    </div>
                    <div class="w-1/4 p-3 flex flex-col justify-between items-center text-center">
                        <span class="text-sm font-black tracking-widest border border-neutral-800 px-3 py-1">FORMULIR 1721-A1</span>
                        <div class="text-[9px] text-neutral-550 space-y-0.5 mt-2">
                            <div>MASA PAJAK [ 01 - 12 ]</div>
                            <div>TAHUN PAJAK <strong class="text-neutral-900 text-xs">[ 2025 ]</strong></div>
                        </div>
                    </div>
                </div>

                <div class="text-right font-mono font-bold text-neutral-800 text-[10px]">
                    NOMOR BUKTI POTONG: 1.1.12-25.0000332
                </div>

                <!-- Section I: Identity of Recipient -->
                <div class="space-y-1.5">
                    <div class="bg-neutral-800 text-white font-bold px-3 py-1 text-[10px] tracking-wider uppercase">
                        A. IDENTITAS PENERIMA PENGHASILAN YANG DIPOTONG
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 px-3 py-2 border border-neutral-350">
                        <div class="space-y-1.5">
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">1. NPWP:</span>
                                <span class="w-2/3 font-mono font-bold text-neutral-900"><?= htmlspecialchars($npwpNumber) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">2. NIK (KTP):</span>
                                <span class="w-2/3 font-mono font-bold text-neutral-900"><?= htmlspecialchars($ktpNik) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">3. Nama Lengkap:</span>
                                <span class="w-2/3 font-bold text-neutral-900 uppercase"><?= htmlspecialchars($namaSesuaiKtp) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">4. Alamat KTP:</span>
                                <span class="w-2/3 font-medium text-neutral-900 leading-tight">Jl. Boulevard Gading Serpong No. 12, Tangerang, Banten</span>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">5. Jenis Kelamin:</span>
                                <span class="w-2/3 font-bold text-neutral-800 uppercase"><?= htmlspecialchars($jenisKelamin) ?></span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">6. Status PTKP:</span>
                                <span class="w-2/3 font-bold text-neutral-800">TK/0 (Tanpa Tanggungan)</span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">7. Nama Jabatan:</span>
                                <span class="w-2/3 font-bold text-neutral-800">Senior UI/UX Designer</span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-600">8. Pegawai Asing:</span>
                                <span class="w-2/3 font-bold text-neutral-800">TIDAK</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section II: Calculations List -->
                <div class="space-y-1">
                    <div class="bg-neutral-800 text-white font-bold px-3 py-1 text-[10px] tracking-wider uppercase">
                        B. RINCIAN PENGHASILAN DAN PENGHITUNGAN PPH PASAL 21 (SETAHUN / DISATUKAN)
                    </div>
                    
                    <div class="border border-neutral-350 overflow-hidden font-medium">
                        <!-- Table Headers -->
                        <div class="flex bg-neutral-100 font-bold border-b border-neutral-300 py-1 px-3 text-[10px] text-neutral-700">
                            <div class="w-3/4">URAIAN PENGHITUNGAN</div>
                            <div class="w-1/4 text-right">JUMLAH (RUPIAH)</div>
                        </div>
                        
                        <!-- Rows -->
                        <div class="divide-y divide-neutral-200">
                            <!-- Earnings -->
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">1. Gaji Pokok / Uang Pensiun Berkala</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-900">Rp 198.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">2. Tunjangan PPh 21 (Ditanggung Perusahaan)</div>
                                <div class="w-1/4 text-right font-mono text-neutral-850">Rp 0</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">3. Tunjangan Lainnya, Uang Lembur, dan sejenisnya</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-neutral-900">Rp 54.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">4. Honorarium, Insentif, dan Imbalan Lain sejenis</div>
                                <div class="w-1/4 text-right font-mono text-neutral-850">Rp 0</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">5. Premi Asuransi Kerja yang Dibayar Pemberi Kerja</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-neutral-900">Rp 4.350.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">6. Tantiem, Bonus, Gratifikasi, Jasa Produksi, dan THR</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-900">Rp 16.500.000</div>
                            </div>
                            <div class="flex py-1.5 px-3 bg-neutral-50 border-t border-b border-neutral-350">
                                <div class="w-3/4 font-bold text-neutral-900">7. JUMLAH PENGHASILAN BRUTO (1 s.d. 6)</div>
                                <div class="w-1/4 text-right font-mono font-black text-neutral-950">Rp 272.850.000</div>
                            </div>

                            <!-- Deductions -->
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">8. Biaya Jabatan (5% dari Penghasilan Bruto, Maks Rp 6.000.000)</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-red-700">Rp 6.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">9. Iuran Pensiun atau Iuran JHT/THT (Dibayar Sendiri)</div>
                                <div class="w-1/4 text-right font-mono font-semibold text-red-700">Rp 3.960.000</div>
                            </div>
                            <div class="flex py-1.5 px-3 bg-neutral-55 border-t border-b border-neutral-350">
                                <div class="w-3/4 font-bold text-neutral-900">10. JUMLAH PENGURANGAN (8 + 9)</div>
                                <div class="w-1/4 text-right font-mono font-bold text-red-750">Rp 9.960.000</div>
                            </div>

                            <!-- PPh Calculations -->
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">11. Penghasilan Netto Setahun (7 - 10)</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-900">Rp 262.890.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">12. Penghasilan Tidak Kena Pajak (PTKP) - TK/0</div>
                                <div class="w-1/4 text-right font-mono font-bold text-neutral-850">Rp 54.000.000</div>
                            </div>
                            <div class="flex py-1 px-3">
                                <div class="w-3/4 text-neutral-850 pl-2">13. Penghasilan Kena Pajak (PKP) Setahun (11 - 12)</div>
                                <div class="w-1/4 text-right font-mono font-black text-neutral-900">Rp 208.890.000</div>
                            </div>
                            <div class="flex py-1.5 px-3 bg-neutral-50 border-t border-b border-neutral-350">
                                <div class="w-3/4 font-bold text-primary">14. PPH PASAL 21 TERUTANG / DIPOTONG SETAHUN</div>
                                <div class="w-1/4 text-right font-mono font-black text-primary">Rp 25.333.500</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section III: Identity of Withholder -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 items-end text-[10px]">
                    <div class="space-y-1.5">
                        <div class="bg-neutral-800 text-white font-bold px-3 py-0.5 tracking-wider uppercase">
                            C. IDENTITAS PEMOTONG PAJAK (PERUSAHAAN)
                        </div>
                        <div class="px-3 py-2 border border-neutral-350 space-y-1">
                            <div class="flex">
                                <span class="w-1/3 text-neutral-500">NPWP Pemotong:</span>
                                <span class="w-2/3 font-mono font-bold text-neutral-800">01.234.567.8-012.000</span>
                            </div>
                            <div class="flex">
                                <span class="w-1/3 text-neutral-500">Nama Pemotong:</span>
                                <span class="w-2/3 font-bold text-neutral-800">PT SI CARE ENTERPRISE</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Signature Section -->
                    <div class="flex flex-col items-center text-center relative">
                        <!-- Red Tax Stamp Overlay -->
                        <div class="absolute bottom-4 right-10 pointer-events-none select-none z-10 opacity-75">
                            <svg class="w-24 h-24 text-red-700/80 transform -rotate-12" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="2.5" />
                                <text x="50" y="40" font-size="6" font-weight="900" text-anchor="middle" fill="currentColor" letter-spacing="1">PAJAK RI</text>
                                <text x="50" y="52" font-size="8" font-weight="950" text-anchor="middle" fill="currentColor">LUNAS</text>
                                <text x="50" y="64" font-size="6" font-weight="900" text-anchor="middle" fill="currentColor" letter-spacing="1">DJP - 2025</text>
                                <path d="M 12 50 L 88 50" stroke="currentColor" stroke-width="1" stroke-dasharray="2, 2" />
                            </svg>
                        </div>
                        
                        <span class="text-neutral-500 font-bold block mb-1">TANGERANG, 10 JANUARI 2026</span>
                        <span class="text-[9px] text-neutral-500 block mb-2">TANDA TANGAN & CAP JALUR CEPAT DJP</span>
                        
                        <div class="bg-neutral-50 p-1.5 rounded border border-neutral-200 mb-1">
                            <svg class="h-8 w-36" viewBox="0 0 100 20">
                                <rect x="0" y="0" width="1" height="15" fill="black"/>
                                <rect x="2" y="0" width="3" height="15" fill="black"/>
                                <rect x="6" y="0" width="1" height="15" fill="black"/>
                                <rect x="8" y="0" width="2" height="15" fill="black"/>
                                <rect x="12" y="0" width="4" height="15" fill="black"/>
                                <rect x="17" y="0" width="1" height="15" fill="black"/>
                                <rect x="19" y="0" width="2" height="15" fill="black"/>
                                <rect x="23" y="0" width="3" height="15" fill="black"/>
                                <rect x="27" y="0" width="1" height="15" fill="black"/>
                                <rect x="29" y="0" width="2" height="15" fill="black"/>
                                <rect x="33" y="0" width="1" height="15" fill="black"/>
                                <rect x="35" y="0" width="3" height="15" fill="black"/>
                                <rect x="39" y="0" width="1" height="15" fill="black"/>
                                <rect x="41" y="0" width="4" height="15" fill="black"/>
                                <rect x="46" y="0" width="2" height="15" fill="black"/>
                                <rect x="49" y="0" width="1" height="15" fill="black"/>
                                <rect x="52" y="0" width="3" height="15" fill="black"/>
                                <rect x="56" y="0" width="1" height="15" fill="black"/>
                                <rect x="58" y="0" width="2" height="15" fill="black"/>
                                <rect x="62" y="0" width="4" height="15" fill="black"/>
                                <rect x="67" y="0" width="1" height="15" fill="black"/>
                                <rect x="69" y="0" width="2" height="15" fill="black"/>
                                <rect x="73" y="0" width="3" height="15" fill="black"/>
                                <rect x="77" y="0" width="1" height="15" fill="black"/>
                                <rect x="79" y="0" width="2" height="15" fill="black"/>
                                <rect x="83" y="0" width="1" height="15" fill="black"/>
                                <rect x="85" y="0" width="3" height="15" fill="black"/>
                                <rect x="89" y="0" width="1" height="15" fill="black"/>
                                <rect x="91" y="0" width="4" height="15" fill="black"/>
                                <rect x="96" y="0" width="2" height="15" fill="black"/>
                                <text x="50" y="19" font-size="4" font-family="monospace" text-anchor="middle" fill="#555">TAX-1721A1-2025-0033</text>
                            </svg>
                        </div>
                        <span class="font-extrabold text-neutral-800 uppercase">PT SI CARE ENTERPRISE</span>
                        <span class="text-[9px] text-neutral-500">Pemberi Kerja Pemotong Pajak</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- DEDICATED DOM CONTAINERS FOR PRINT AREA -->
<!-- ============================================== -->
<div id="printArea" class="hidden"></div>

<!-- ============================================== -->
<!-- INTERACTIVE JAVASCRIPT LOGIC -->
<!-- ============================================== -->
<script>
    // Config values matching backend calculations
    const baseSalary = <?= $baseSalary ?>;
    const employeeId = <?= json_encode($employeeId) ?>;
    const employeeName = <?= json_encode($namaSesuaiKtp) ?>;
    const bankName = <?= json_encode($bankName) ?>;
    const bankAccountNumber = <?= json_encode($bankAccountNumber) ?>;
    
    // Payslips detail source mapping
    const payslipsData = {
        '05-2026': {
            no: 'SLIP/PAY/20260525-0033',
            barcode: 'PAY-20260525-0033',
            period: 'Mei 2026',
            tunj_jabatan: 2500000,
            tunj_transport: 1500000,
            tunj_komunikasi: 500000,
            bonus: 0,
            thr: 0,
            pph21: Math.round(baseSalary * 0.025)
        },
        '04-2026': {
            no: 'SLIP/PAY/20260424-0033',
            barcode: 'PAY-20260424-0033',
            period: 'April 2026',
            tunj_jabatan: 2500000,
            tunj_transport: 1500000,
            tunj_komunikasi: 500000,
            bonus: 0,
            thr: baseSalary, // THR Eid
            pph21: Math.round(baseSalary * 0.06)
        },
        '03-2026': {
            no: 'SLIP/PAY/20260325-0033',
            barcode: 'PAY-20260325-0033',
            period: 'Maret 2026',
            tunj_jabatan: 2500000,
            tunj_transport: 1350000,
            tunj_komunikasi: 500000,
            bonus: 500000,
            thr: 0,
            pph21: Math.round(baseSalary * 0.026)
        },
        '02-2026': {
            no: 'SLIP/PAY/20260225-0033',
            barcode: 'PAY-20260225-0033',
            period: 'Februari 2026',
            tunj_jabatan: 2500000,
            tunj_transport: 1450000,
            tunj_komunikasi: 500000,
            bonus: 0,
            thr: 0,
            pph21: Math.round(baseSalary * 0.024)
        },
        '01-2026': {
            no: 'SLIP/PAY/20260125-0033',
            barcode: 'PAY-20260125-0033',
            period: 'Januari 2026',
            tunj_jabatan: 2500000,
            tunj_transport: 1500000,
            tunj_komunikasi: 500000,
            bonus: 1000000,
            thr: 0,
            pph21: Math.round(baseSalary * 0.028)
        }
    };

    window.formatRupiah = function formatRupiah(number) {
        return 'Rp ' + number.toLocaleString('id-ID');
    }

    // Modal Control: Payslip
    window.previewPayslip = function previewPayslip(code) {
        const data = payslipsData[code];
        if (!data) return;

        // Calculations
        const bpjsTk = Math.round(baseSalary * 0.02);
        const bpjsKes = Math.round(baseSalary * 0.01);
        const totalEarnings = baseSalary + data.tunj_jabatan + data.tunj_transport + data.tunj_komunikasi + data.bonus + data.thr;
        const totalDeductions = bpjsTk + bpjsKes + data.pph21;
        const netPay = totalEarnings - totalDeductions;

        // Populating DOM elements
        document.getElementById('payslipNo').innerText = data.no;
        document.getElementById('payPeriod').innerText = data.period;
        document.getElementById('payBase').innerText = formatRupiah(baseSalary);
        document.getElementById('payTunjJabatan').innerText = formatRupiah(data.tunj_jabatan);
        document.getElementById('payTunjTransport').innerText = formatRupiah(data.tunj_transport);
        document.getElementById('payTunjKomunikasi').innerText = formatRupiah(data.tunj_komunikasi);
        
        // Bonus Row
        if (data.bonus > 0) {
            document.getElementById('payBonusRow').style.display = 'flex';
            document.getElementById('payBonus').innerText = formatRupiah(data.bonus);
        } else {
            document.getElementById('payBonusRow').style.display = 'none';
        }

        // THR Row
        if (data.thr > 0) {
            document.getElementById('payThrRow').style.display = 'flex';
            document.getElementById('payThr').innerText = formatRupiah(data.thr);
        } else {
            document.getElementById('payThrRow').style.display = 'none';
        }

        document.getElementById('payBpjsTk').innerText = formatRupiah(bpjsTk);
        document.getElementById('payBpjsKes').innerText = formatRupiah(bpjsKes);
        document.getElementById('payPph21').innerText = formatRupiah(data.pph21);

        document.getElementById('payGrossTotal').innerText = formatRupiah(totalEarnings);
        document.getElementById('payDeductionsTotal').innerText = formatRupiah(totalDeductions);
        document.getElementById('payNetTotal').innerText = formatRupiah(netPay);

        // Bank details
        document.getElementById('payBankInfo').innerText = `${bankName} - ${bankAccountNumber}`;
        document.getElementById('payBankHolder').innerText = `A/N ${employeeName}`;
        
        // Barcode update
        document.getElementById('payBarcodeNo').innerText = data.barcode;

        // Show Modal
        const modal = document.getElementById('payslipModal');
        const container = document.getElementById('payslipModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            container.classList.remove('scale-95');
        }, 50);
    }

    window.closePayslipModal = function closePayslipModal() {
        const modal = document.getElementById('payslipModal');
        const container = document.getElementById('payslipModalContainer');
        modal.classList.add('opacity-0');
        container.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Modal Control: Tax Document A1
    window.previewTaxA1 = function previewTaxA1() {
        const modal = document.getElementById('taxModal');
        const container = document.getElementById('taxModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            container.classList.remove('scale-95');
        }, 50);
    }

    window.closeTaxModal = function closeTaxModal() {
        const modal = document.getElementById('taxModal');
        const container = document.getElementById('taxModalContainer');
        modal.classList.add('opacity-0');
        container.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Printing Operations (Clones target container to dedicated #printArea and triggers system print)
    window.printPayslipDirect = function printPayslipDirect(code) {
        // Build payslip context in modal first
        previewPayslip(code);
        // Print it immediately
        setTimeout(() => {
            printPayslipFromModal();
        }, 200);
    }

    window.printPayslipFromModal = function printPayslipFromModal() {
        const sourceHtml = document.getElementById('payslipPrintArea').innerHTML;
        const printArea = document.getElementById('printArea');
        printArea.innerHTML = sourceHtml;
        window.print();
        printArea.innerHTML = '';
    }

    window.printTaxFromModal = function printTaxFromModal() {
        const sourceHtml = document.getElementById('taxPrintArea').innerHTML;
        const printArea = document.getElementById('printArea');
        printArea.innerHTML = sourceHtml;
        window.print();
        printArea.innerHTML = '';
    }

    // Click outside to close modals
    window.addEventListener('click', function(e) {
        const payModal = document.getElementById('payslipModal');
        const payContainer = document.getElementById('payslipModalContainer');
        if (e.target === payModal) {
            closePayslipModal();
        }

        const taxModal = document.getElementById('taxModal');
        const taxContainer = document.getElementById('taxModalContainer');
        if (e.target === taxModal) {
            closeTaxModal();
        }
    });
</script>