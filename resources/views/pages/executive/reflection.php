<?php
// Executive / C-Level: Corporate Well-being & Performance Alignment Dashboard (Strictly Aggregate under UU PDP)
$db = \App\Config\Database::getInstance()->getConnection();
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? '';

// Selected period filter
$currentPeriod = date('Y') . '-Q' . ceil(date('n') / 3);
$selectedPeriod = $_GET['period'] ?? $currentPeriod;

// Parse year and quarter for mood pulses filter
$parts = explode('-Q', $selectedPeriod);
$year = isset($parts[0]) ? (int)$parts[0] : (int)date('Y');
$quarter = isset($parts[1]) ? (int)$parts[1] : (int)ceil(date('n') / 3);

// Fetch all reflections in the company for the selected period to calculate global averages
$stmtRef = $db->prepare("
    SELECT r.*, u.department_id,
           COALESCE(latest_mood.mood_rating, 'neutral') AS mood_rating,
           COALESCE(latest_mood.workload_rating, 3) AS workload_rating
    FROM self_reflections r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN (
        SELECT mp1.*
        FROM mood_pulses mp1
        INNER JOIN (
            SELECT user_id, MAX(created_at) as max_created
            FROM mood_pulses
            WHERE YEAR(created_at) = :year AND QUARTER(created_at) = :quarter
            GROUP BY user_id
        ) mp2 ON mp1.user_id = mp2.user_id AND mp1.created_at = mp2.max_created
    ) latest_mood ON r.user_id = latest_mood.user_id
    WHERE r.period = :period AND r.status IN ('submitted', 'completed')
");
$stmtRef->execute([
    'period' => $selectedPeriod,
    'year' => $year,
    'quarter' => $quarter
]);
$reflections = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

// Calculate Global Aggregates
$moodCounts = ['excellent' => 0, 'good' => 0, 'neutral' => 0, 'tired' => 0, 'stressed' => 0];
$statusCounts = ['draft' => 0, 'submitted' => 0, 'completed' => 0];
$workloadTotal = 0;
$valuesTotal = 0;
$countedForAvg = 0;

foreach ($reflections as $r) {
    if (isset($moodCounts[$r['mood_rating']])) {
        $moodCounts[$r['mood_rating']]++;
    }
    if (isset($statusCounts[$r['status']])) {
        $statusCounts[$r['status']]++;
    }
    $workloadTotal += $r['workload_rating'];
    $valuesTotal += $r['core_values_rating'];
    $countedForAvg++;
}

$avgWorkload = $countedForAvg > 0 ? round($workloadTotal / $countedForAvg, 1) : 0.0;
$avgValues = $countedForAvg > 0 ? round($valuesTotal / $countedForAvg, 1) : 0.0;

// Fetch department-level aggregates
$stmtDept = $db->prepare("
    SELECT 
        d.name AS department_name,
        COUNT(r.id) AS total_reflections,
        AVG(COALESCE(latest_mood.workload_rating, 3)) AS avg_workload,
        AVG(r.core_values_rating) AS avg_values,
        -- Calculate percentage of employees with positive mood (excellent or good)
        ROUND(SUM(CASE WHEN COALESCE(latest_mood.mood_rating, 'neutral') IN ('excellent', 'good') THEN 1 ELSE 0 END) / COUNT(r.id) * 100, 1) AS happiness_index
    FROM self_reflections r
    JOIN users u ON r.user_id = u.id
    JOIN departments d ON u.department_id = d.id
    LEFT JOIN (
        SELECT mp1.*
        FROM mood_pulses mp1
        INNER JOIN (
            SELECT user_id, MAX(created_at) as max_created
            FROM mood_pulses
            WHERE YEAR(created_at) = :year AND QUARTER(created_at) = :quarter
            GROUP BY user_id
        ) mp2 ON mp1.user_id = mp2.user_id AND mp1.created_at = mp2.max_created
    ) latest_mood ON r.user_id = latest_mood.user_id
    WHERE r.period = :period AND r.status IN ('submitted', 'completed')
    GROUP BY d.id, d.name
    ORDER BY happiness_index DESC
");
$stmtDept->execute([
    'period' => $selectedPeriod,
    'year' => $year,
    'quarter' => $quarter
]);
$deptStats = $stmtDept->fetchAll(PDO::FETCH_ASSOC);

// Quarters selection list
$availablePeriods = [];
$year = date('Y');
for ($y = $year; $y >= $year - 1; $y--) {
    for ($q = 4; $q >= 1; $q--) {
        $availablePeriods[] = "$y-Q$q";
    }
}
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Analitik Refleksi & Sentimen (Executive)</h1>
            <p class="text-on-surface-variant font-medium text-sm">Dashboard visual sentimen budaya dan beban kerja organisasi per departemen untuk keputusan strategis SDM.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <select onchange="changeReflectionPeriod(this.value)" class="py-2.5 pl-3.5 pr-10 appearance-none text-xs rounded-xl border border-outline-variant/60 bg-surface-container-lowest focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-bold text-on-surface">
                    <?php foreach ($availablePeriods as $ap): ?>
                        <option value="<?= $ap ?>" <?= $ap === $selectedPeriod ? 'selected' : '' ?>><?= $ap ?></option>
                    <?php endforeach; ?>
                </select>
                
            </div>
        </div>
    </div>

    <!-- Executive Privacy Alert Banner -->
    <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-2xl p-4 flex items-start gap-3">
        <span class="material-symbols-outlined text-indigo-600 mt-0.5">policy</span>
        <div>
            <h4 class="font-extrabold text-sm text-indigo-900">Kebijakan Privasi Karyawan & Kepatuhan Keamanan</h4>
            <p class="text-xs text-indigo-800/95 mt-0.5">Menu ini diatur di bawah prinsip **Anonymization & Aggregation**. Jajaran direksi/eksekutif hanya diizinkan memantau data dalam bentuk tren makro departemental guna mendeteksi isu kelelahan kerja (*burnout*) atau masalah dinamika tim tanpa melanggar privasi individu karyawan.</p>
        </div>
    </div>

    <!-- Aggregate Analytics Dashboard Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Average Workload Card -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Beban Kerja Rata-Rata</span>
                <span class="material-symbols-outlined text-primary bg-primary/5 p-2 rounded-lg text-sm">speed</span>
            </div>
            <div>
                <h3 class="text-2xl font-black text-primary"><?= $avgWorkload ?> <span class="text-xs font-semibold text-on-surface-variant">/ 5</span></h3>
                <div class="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden mt-2">
                    <div class="bg-primary h-full rounded-full transition-all" style="width: <?= ($avgWorkload / 5) * 100 ?>%"></div>
                </div>
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1">Status beban kerja korporasi</p>
            </div>
        </div>

        <!-- Average Core Values Card -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Nilai Budaya Organisasi</span>
                <span class="material-symbols-outlined text-amber-500 bg-amber-50 p-2 rounded-lg text-sm">stars</span>
            </div>
            <div>
                <h3 class="text-2xl font-black text-amber-600"><?= $avgValues ?> <span class="text-xs font-semibold text-on-surface-variant">/ 5</span></h3>
                <div class="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden mt-2">
                    <div class="bg-amber-500 h-full rounded-full transition-all" style="width: <?= ($avgValues / 5) * 100 ?>%"></div>
                </div>
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1">Skor implementasi core values</p>
            </div>
        </div>

        <!-- Total Submissions Card -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Partisipasi Aktif</span>
                <span class="material-symbols-outlined text-blue-600 bg-blue-50 p-2 rounded-lg text-sm">group</span>
            </div>
            <div>
                <h3 class="text-2xl font-black text-blue-700"><?= $countedForAvg ?> <span class="text-xs font-semibold text-on-surface-variant">Refleksi</span></h3>
                <p class="text-[9px] text-on-surface-variant font-semibold mt-2">Telah disubmit/selesai ditinjau periode ini</p>
            </div>
        </div>

        <!-- Indeks Kebahagiaan Makro -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Indeks Kepuasan Kerja Global</span>
                <span class="material-symbols-outlined text-green-600 bg-green-50 p-2 rounded-lg text-sm">sentiment_very_satisfied</span>
            </div>
            <?php 
            $happyCount = $moodCounts['excellent'] + $moodCounts['good'];
            $happyPct = $countedForAvg > 0 ? round(($happyCount / $countedForAvg) * 100) : 0;
            ?>
            <div>
                <h3 class="text-2xl font-black text-green-700"><?= $happyPct ?>% <span class="text-xs font-semibold text-on-surface-variant">Karyawan</span></h3>
                <div class="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden mt-2">
                    <div class="bg-green-600 h-full rounded-full transition-all" style="width: <?= $happyPct ?>%"></div>
                </div>
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1">Mengalami emosi positif (Senang/Baik)</p>
            </div>
        </div>
    </div>

    <!-- Mood Sentimen Distribution Bars -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 p-6 shadow-sm space-y-4">
        <h3 class="text-xs font-extrabold text-on-surface uppercase tracking-wider">Distribusi Sentimen Mood Korporasi</h3>
        <div class="space-y-3">
            <?php 
            $moodLabels = [
                'excellent' => ['label' => 'Sangat Baik (😄)', 'color' => 'bg-emerald-600'],
                'good' => ['label' => 'Baik (🙂)', 'color' => 'bg-blue-600'],
                'neutral' => ['label' => 'Biasa Saja (😐)', 'color' => 'bg-slate-500'],
                'tired' => ['label' => 'Lelah (😴)', 'color' => 'bg-amber-600'],
                'stressed' => ['label' => 'Stres / Cemas (😰)', 'color' => 'bg-red-600']
            ];
            foreach ($moodLabels as $key => $mood):
                $count = $moodCounts[$key];
                $pct = $countedForAvg > 0 ? round(($count / $countedForAvg) * 100) : 0;
            ?>
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs font-bold text-on-surface">
                    <span><?= $mood['label'] ?></span>
                    <span class="text-on-surface-variant"><?= $count ?> Staf (<?= $pct ?>%)</span>
                </div>
                <div class="w-full bg-surface-container-low h-2.5 rounded-full overflow-hidden">
                    <div class="<?= $mood['color'] ?> h-full rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Department-level Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden animate-fade-in">
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary font-bold">account_tree</span>
                <h2 class="text-lg font-extrabold text-on-surface">Matriks Analisis Per Departemen</h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Nama Departemen</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Partisipasi (Refleksi)</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Indeks Kepuasan (Mood)</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Beban Kerja Rata-Rata</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Skor Budaya</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Rekomendasi Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10 text-xs font-semibold text-on-surface">
                    <?php if (empty($deptStats)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-on-surface-variant font-medium">
                                <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">dashboard</span>
                                <p>Belum ada data refleksi departemental untuk periode ini.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deptStats as $ds): 
                            $wl = (float)$ds['avg_workload'];
                            $hi = (float)$ds['happiness_index'];
                            
                            // Determine recommendation
                            $recommendation = "✅ Kondisi tim stabil dan selaras.";
                            $recColor = "text-green-700 bg-green-50 border-green-200";
                            if ($wl >= 4.0 && $hi < 50.0) {
                                $recommendation = "🚨 Peringatan: Beban kerja tinggi + kepuasan rendah. Butuh peninjauan headcount.";
                                $recColor = "text-red-700 bg-red-50 border-red-200";
                            } elseif ($wl >= 4.0) {
                                $recommendation = "⚠️ Waspada: Beban kerja tinggi. Monitor sisa cuti & lembur staf.";
                                $recColor = "text-amber-700 bg-amber-50 border-amber-200";
                            } elseif ($hi < 50.0) {
                                $recommendation = "⚠️ Sentimen mood menurun. Evaluasi kepemimpinan & dinamika tim.";
                                $recColor = "text-amber-700 bg-amber-50 border-amber-200";
                            }
                        ?>
                            <tr class="hover:bg-surface-container-low/30 transition-colors">
                                <td class="py-4 px-6 font-extrabold text-primary text-xs">
                                    <?= htmlspecialchars($ds['department_name']) ?>
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-on-surface">
                                    <?= $ds['total_reflections'] ?> Staf
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $hi >= 70.0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($hi < 40.0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-slate-50 text-slate-700 border-slate-200') ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $hi >= 70.0 ? 'bg-emerald-500' : ($hi < 40.0 ? 'bg-red-500' : 'bg-slate-400') ?>"></span>
                                        <?= $ds['happiness_index'] ?>% Bahagia
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="font-bold text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full">
                                        <?= round($wl, 1) ?> / 5
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center text-amber-500 font-bold">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <span class="material-symbols-outlined text-xs font-fill" style="font-variation-settings: 'FILL' 1">star</span>
                                        <span><?= round($ds['avg_values'], 1) ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex px-3 py-1 rounded-xl text-[10px] font-bold border <?= $recColor ?> leading-relaxed whitespace-normal break-words">
                                        <?= $recommendation ?>
                                    </span>
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
    function changeReflectionPeriod(period) {
        if (window.loadPage) {
            window.loadPage('/executive/reflection?period=' + period);
        } else {
            window.location.href = '/executive/reflection?period=' + period;
        }
    }

    // Expose to global scope
    window.changeReflectionPeriod = changeReflectionPeriod;
</script>
