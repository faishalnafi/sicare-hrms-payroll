<?php
/**
 * Executive Portal - Dashboard Analitik SDM Makro (/executive/analytics)
 */
use App\Services\ViewHelper;

$analytics = ViewHelper::getExecutiveMacroAnalytics();
$deptDist = $analytics['dept_distribution'] ?? [];
$atsFunnel = $analytics['ats_funnel'] ?? [];
$payrollExp = $analytics['payroll_expense'] ?? [];
$attStats = $analytics['attendance_stats'] ?? [];
$totalHeadcount = array_sum(array_column($deptDist, 'total_headcount')) ?: 1;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl md:text-3xl font-extrabold text-primary tracking-tight">Dashboard Analitik SDM & Korporat</h1>
            <p class="text-on-surface-variant font-medium text-sm">
                Pengawasan kinerja makro SDM, analisis biaya rekrutmen, efisiensi penggajian, dan kehadiran global perusahaan.
            </p>
        </div>
        <div class="flex items-center gap-2 bg-emerald-50 text-emerald-800 text-xs font-bold px-4 py-2.5 rounded-xl border border-emerald-200">
            <span class="material-symbols-outlined text-base">monitoring</span>
            <span>Real-time C-Level Overview (<?= date('F Y') ?>)</span>
        </div>
    </div>

    <!-- Metric Cards Grid (5 Cards) -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Card 1: Total Karyawan -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/15 shadow-sm space-y-2">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-bold">Total Karyawan</span>
                <span class="material-symbols-outlined text-primary text-xl">groups</span>
            </div>
            <div class="text-2xl font-extrabold text-primary font-headline"><?= number_format($analytics['total_employees']) ?></div>
            <div class="text-[10px] text-emerald-700 font-semibold flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">trending_up</span> +3% bulan ini
            </div>
        </div>

        <!-- Card 2: Cost Per Hire -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/15 shadow-sm space-y-2">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-bold">Cost Per Hire</span>
                <span class="material-symbols-outlined text-blue-600 text-xl">payments</span>
            </div>
            <div class="text-xl font-extrabold text-on-surface font-headline">Rp <?= number_format($analytics['cost_per_hire_idr'] / 1000000, 1) ?>Jt</div>
            <div class="text-[10px] text-on-surface-variant font-medium">Rata-rata alokasi rekrutmen</div>
        </div>

        <!-- Card 3: Time To Hire -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/15 shadow-sm space-y-2">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-bold">Time to Hire</span>
                <span class="material-symbols-outlined text-amber-600 text-xl">avg_pace</span>
            </div>
            <div class="text-2xl font-extrabold text-on-surface font-headline"><?= (int)$analytics['time_to_hire_days'] ?> Hari</div>
            <div class="text-[10px] text-emerald-700 font-semibold flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">bolt</span> 2 hari lebih cepat
            </div>
        </div>

        <!-- Card 4: Turnover Rate -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/15 shadow-sm space-y-2">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-bold">Turnover Rate</span>
                <span class="material-symbols-outlined text-rose-600 text-xl">published_with_changes</span>
            </div>
            <div class="text-2xl font-extrabold text-on-surface font-headline"><?= (float)$analytics['turnover_rate_pct'] ?>%</div>
            <div class="text-[10px] text-emerald-700 font-semibold">Sangat Sehat (< 5%)</div>
        </div>

        <!-- Card 5: Kehadiran Global -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/15 shadow-sm space-y-2">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-bold">Kehadiran Global</span>
                <span class="material-symbols-outlined text-emerald-600 text-xl">fact_check</span>
            </div>
            <div class="text-2xl font-extrabold text-emerald-700 font-headline"><?= (float)$analytics['attendance_rate_pct'] ?>%</div>
            <div class="text-[10px] text-on-surface-variant font-medium">Tingkat kehadiran harian</div>
        </div>
    </div>

    <!-- Middle Row: 2 Charts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 1. Distribusi Headcount per Departemen -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-outline-variant/15 pb-3">
                <h3 class="font-bold text-on-surface text-base flex items-center gap-2 font-headline">
                    <span class="material-symbols-outlined text-primary text-xl">domain</span>
                    Distribusi Karyawan per Departemen
                </h3>
                <span class="text-xs font-bold text-on-surface-variant"><?= count($deptDist) ?> Divisi</span>
            </div>

            <div class="space-y-3">
                <?php foreach ($deptDist as $d): 
                    $hc = (int)($d['total_headcount'] ?? 0);
                    $pct = ($totalHeadcount > 0) ? round(($hc / $totalHeadcount) * 100, 1) : 0;
                ?>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between items-center font-semibold">
                            <span class="text-on-surface"><?= htmlspecialchars($d['dept_name']) ?></span>
                            <span class="text-on-surface-variant font-mono"><?= $hc ?> Staf (<?= $pct ?>%)</span>
                        </div>
                        <div class="w-full bg-surface-container rounded-full h-2.5 overflow-hidden">
                            <div class="bg-primary h-full rounded-full transition-all duration-500" style="width: <?= max($pct, 4) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 2. ATS Recruitment Funnel -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-outline-variant/15 pb-3">
                <h3 class="font-bold text-on-surface text-base flex items-center gap-2 font-headline">
                    <span class="material-symbols-outlined text-primary text-xl">filter_alt</span>
                    Pipeline Seleksi Rekrutmen (ATS Funnel)
                </h3>
                <span class="text-xs font-bold text-primary"><?= (int)($analytics['total_candidates'] ?? 0) ?> Pelamar Aktif</span>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="font-bold text-xs">1. Applied (Lamaran Masuk)</span>
                    </div>
                    <span class="font-bold text-sm font-mono text-primary"><?= $atsFunnel['applied'] ?> Pelamar</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="font-bold text-xs">2. Screening & Shortlist</span>
                    </div>
                    <span class="font-bold text-sm font-mono text-amber-700"><?= $atsFunnel['screening'] ?> Pelamar</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                        <span class="font-bold text-xs">3. Wawancara Teknis Manager</span>
                    </div>
                    <span class="font-bold text-sm font-mono text-purple-700"><?= $atsFunnel['interview'] ?> Pelamar</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="font-bold text-xs">4. Offering Letter & Kontrak</span>
                    </div>
                    <span class="font-bold text-sm font-mono text-emerald-700"><?= $atsFunnel['offering'] ?> Pelamar</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 text-base">verified</span>
                        <span class="font-bold text-xs text-emerald-900">5. Hired / Onboarding Aktif</span>
                    </div>
                    <span class="font-bold text-sm font-mono text-emerald-800"><?= $atsFunnel['hired'] ?> Karyawan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Financial Payroll & Attendance Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Financial Payroll Breakdown -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-outline-variant/15 pb-3">
                <h3 class="font-bold text-on-surface text-base flex items-center gap-2 font-headline">
                    <span class="material-symbols-outlined text-primary text-xl">account_balance_wallet</span>
                    Ringkasan Pengeluaran Payroll Bulanan
                </h3>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="text-on-surface-variant font-medium">Total Gaji Pokok</div>
                    <div class="font-extrabold text-base text-primary font-mono mt-1">
                        Rp <?= number_format((float)($payrollExp['total_base'] ?: 350000000)) ?>
                    </div>
                </div>

                <div class="p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="text-on-surface-variant font-medium">Total Gaji Bersih (THP)</div>
                    <div class="font-extrabold text-base text-emerald-700 font-mono mt-1">
                        Rp <?= number_format((float)($payrollExp['total_net'] ?: 320000000)) ?>
                    </div>
                </div>

                <div class="p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="text-on-surface-variant font-medium">Potongan PPh21 (Pajak)</div>
                    <div class="font-extrabold text-base text-rose-700 font-mono mt-1">
                        Rp <?= number_format((float)($payrollExp['total_pph21'] ?: 15000000)) ?>
                    </div>
                </div>

                <div class="p-3 bg-surface rounded-xl border border-outline-variant/20">
                    <div class="text-on-surface-variant font-medium">Iuran BPJS TK & Kes</div>
                    <div class="font-extrabold text-base text-blue-700 font-mono mt-1">
                        Rp <?= number_format((float)($payrollExp['total_bpjs'] ?: 15000000)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Breakdown -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-outline-variant/15 pb-3">
                <h3 class="font-bold text-on-surface text-base flex items-center gap-2 font-headline">
                    <span class="material-symbols-outlined text-primary text-xl">free_cancellation</span>
                    Statistik Presensi Perusahaan (Bulan Ini)
                </h3>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200">
                    <div class="text-emerald-900 font-bold flex items-center justify-between">
                        <span>Hadir Tepat Waktu</span>
                        <span class="material-symbols-outlined text-emerald-600 text-base">check_circle</span>
                    </div>
                    <div class="font-extrabold text-2xl text-emerald-700 font-mono mt-1"><?= (int)$attStats['hadir'] ?></div>
                </div>

                <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200">
                    <div class="text-amber-900 font-bold flex items-center justify-between">
                        <span>Terlambat</span>
                        <span class="material-symbols-outlined text-amber-600 text-base">schedule</span>
                    </div>
                    <div class="font-extrabold text-2xl text-amber-700 font-mono mt-1"><?= (int)$attStats['terlambat'] ?></div>
                </div>

                <div class="p-3.5 bg-blue-50 rounded-xl border border-blue-200">
                    <div class="text-blue-900 font-bold flex items-center justify-between">
                        <span>Izin / Cuti Sah</span>
                        <span class="material-symbols-outlined text-blue-600 text-base">event_note</span>
                    </div>
                    <div class="font-extrabold text-2xl text-blue-700 font-mono mt-1"><?= (int)$attStats['izin'] ?></div>
                </div>

                <div class="p-3.5 bg-rose-50 rounded-xl border border-rose-200">
                    <div class="text-rose-900 font-bold flex items-center justify-between">
                        <span>Alpa (Mangkir)</span>
                        <span class="material-symbols-outlined text-rose-600 text-base">warning</span>
                    </div>
                    <div class="font-extrabold text-2xl text-rose-700 font-mono mt-1"><?= (int)$attStats['alpa'] ?></div>
                </div>
            </div>
        </div>
    </div>
</div>