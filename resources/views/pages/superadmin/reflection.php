<?php
// Superadmin: Corporate Employee Self-Reflection Reviews (Unrestricted Access)
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

// Fetch all reflections in the company for the selected period (NO ANONYMITY, superadmin can see names of everyone)
$stmtRef = $db->prepare("
    SELECT r.*, u.first_name, u.last_name, u.email, u.employee_id, u.job_title, u.profile_picture, d.name AS department_name,
           COALESCE(latest_mood.mood_rating, 'neutral') AS mood_rating,
           COALESCE(latest_mood.workload_rating, 3) AS workload_rating
    FROM self_reflections r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
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
    WHERE r.period = :period
    ORDER BY r.status DESC, r.updated_at DESC
");
$stmtRef->execute([
    'period' => $selectedPeriod,
    'year' => $year,
    'quarter' => $quarter
]);
$reflections = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

// Fetch personal journals (only available to Employee and Superadmin)
foreach ($reflections as &$ref) {
    $stmtJournals = $db->prepare("SELECT * FROM personal_journals WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmtJournals->execute(['user_id' => $ref['user_id']]);
    $ref['journals'] = $stmtJournals->fetchAll(PDO::FETCH_ASSOC);
}
unset($ref);

// Calculate Aggregates for Superadmin Dashboard
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
    if ($r['status'] !== 'draft') {
        $workloadTotal += $r['workload_rating'];
        $valuesTotal += $r['core_values_rating'];
        $countedForAvg++;
    }
}

$avgWorkload = $countedForAvg > 0 ? round($workloadTotal / $countedForAvg, 1) : 0.0;
$avgValues = $countedForAvg > 0 ? round($valuesTotal / $countedForAvg, 1) : 0.0;

// Quarters selection list
$availablePeriods = [];
$year = date('Y');
for ($y = $year; $y >= $year - 1; $y--) {
    for ($q = 4; $q >= 1; $q--) {
        $availablePeriods[] = "$y-Q$q";
    }
}

function getMoodBadgeSA($mood) {
    $moods = [
        'excellent' => ['label' => 'Sangat Baik', 'icon' => 'sentiment_very_satisfied', 'color' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
        'good'      => ['label' => 'Baik',        'icon' => 'sentiment_satisfied',      'color' => 'text-blue-700 bg-blue-50 border-blue-200'],
        'neutral'   => ['label' => 'Biasa Saja',  'icon' => 'sentiment_neutral',        'color' => 'text-slate-700 bg-slate-50 border-slate-200'],
        'tired'     => ['label' => 'Lelah',       'icon' => 'sentiment_dissatisfied',   'color' => 'text-amber-700 bg-amber-50 border-amber-200'],
        'stressed'  => ['label' => 'Stres / Cemas','icon' => 'sentiment_very_dissatisfied','color' => 'text-red-700 bg-red-50 border-red-200']
    ];
    return $moods[$mood] ?? ['label' => $mood, 'icon' => 'mood', 'color' => 'text-gray-700 bg-gray-50 border-gray-200'];
}
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Refleksi Karyawan (Superadmin)</h1>
            <p class="text-on-surface-variant font-medium text-sm">Akses penuh ke data penilaian diri karyawan, analisis tingkat sentimen/mood korporasi, dan riwayat IDP.</p>
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
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1">Status: <?= $avgWorkload > 3.5 ? 'Tinggi' : ($avgWorkload < 2.5 ? 'Rendah' : 'Ideal') ?></p>
            </div>
        </div>

        <!-- Average Core Values Card -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Implementasi Budaya (Values)</span>
                <span class="material-symbols-outlined text-amber-500 bg-amber-50 p-2 rounded-lg text-sm">stars</span>
            </div>
            <div>
                <h3 class="text-2xl font-black text-amber-600"><?= $avgValues ?> <span class="text-xs font-semibold text-on-surface-variant">/ 5</span></h3>
                <div class="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden mt-2">
                    <div class="bg-amber-500 h-full rounded-full transition-all" style="width: <?= ($avgValues / 5) * 100 ?>%"></div>
                </div>
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1">Implementasi nilai-nilai budaya organisasi</p>
            </div>
        </div>

        <!-- Submission Status Card -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Status Partisipasi</span>
                <span class="material-symbols-outlined text-blue-600 bg-blue-50 p-2 rounded-lg text-sm">assignment_turned_in</span>
            </div>
            <div class="grid grid-cols-3 text-center gap-1">
                <div>
                    <span class="text-[9px] text-on-surface-variant font-bold block">Draf</span>
                    <span class="text-base font-black text-slate-700"><?= $statusCounts['draft'] ?></span>
                </div>
                <div class="border-x border-outline-variant/20">
                    <span class="text-[9px] text-on-surface-variant font-bold block">Masuk</span>
                    <span class="text-base font-black text-amber-600"><?= $statusCounts['submitted'] ?></span>
                </div>
                <div>
                    <span class="text-[9px] text-on-surface-variant font-bold block">Selesai</span>
                    <span class="text-base font-black text-green-700"><?= $statusCounts['completed'] ?></span>
                </div>
            </div>
        </div>

        <!-- Mood / Sentimen Indeks -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Indeks Mood Dominan</span>
                <span class="material-symbols-outlined text-green-600 bg-green-50 p-2 rounded-lg text-sm">mood</span>
            </div>
            <?php 
            arsort($moodCounts);
            $topMood = key($moodCounts);
            $topMoodCount = current($moodCounts);
            $moodLabels = [
                'excellent' => 'Sangat Baik 😄',
                'good' => 'Baik 🙂',
                'neutral' => 'Biasa Saja 😐',
                'tired' => 'Lelah 😴',
                'stressed' => 'Stres / Cemas 😰'
            ];
            ?>
            <div>
                <h3 class="text-lg font-black text-on-surface"><?= $topMoodCount > 0 ? ($moodLabels[$topMood] ?? '-') : 'Belum Ada Data' ?></h3>
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1.5"><?= $topMoodCount ?> Karyawan merasakan ini di periode ini.</p>
            </div>
        </div>
    </div>

    <!-- Search bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-3xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="relative flex-grow max-w-xl">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                <span class="material-symbols-outlined text-lg">search</span>
            </span>
            <input type="text" id="reflectionSearch" oninput="filterReflectionList()" placeholder="Cari nama karyawan, NIK, jabatan, divisi, atau status..." 
                class="w-full pl-10 pr-4 py-2.5 border border-outline-variant/30 rounded-2xl bg-surface-container-low text-xs text-on-surface font-semibold placeholder-on-surface-variant/55 focus:outline-none focus:border-primary transition-all" />
        </div>
    </div>

    <!-- Detailed List Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden animate-fade-in">
        <div class="overflow-x-auto">
            <table class="min-w-[1000px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Karyawan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Jabatan & Divisi</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Mood</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Beban Kerja</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Budaya</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reflectionTableTbody" class="divide-y divide-outline-variant/10">
                    <?php if (empty($reflections)): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-on-surface-variant font-medium text-xs">
                                <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">rate_review</span>
                                <p>Belum ada pengisian refleksi diri untuk periode terpilih.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reflections as $ref): 
                            $mood = getMoodBadgeSA($ref['mood_rating']);
                            $searchStr = strtolower($ref['first_name'] . ' ' . $ref['last_name'] . ' ' . ($ref['employee_id'] ?? '') . ' ' . ($ref['job_title'] ?? '') . ' ' . ($ref['department_name'] ?? '') . ' ' . ($ref['status']));
                        ?>
                            <tr class="reflection-row hover:bg-surface-container-low/30 transition-colors" data-search="<?= htmlspecialchars($searchStr) ?>">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <?php 
                                        $hash = md5(strtolower(trim($ref['email'])));
                                        $avatar = !empty($ref['profile_picture']) ? $ref['profile_picture'] : "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
                                        ?>
                                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border" onerror="window.handleAvatarError(this, '<?= $hash ?>')" />
                                        <div>
                                            <h4 class="font-extrabold text-on-surface whitespace-nowrap"><?= htmlspecialchars($ref['first_name'] . ' ' . $ref['last_name']) ?></h4>
                                            <span class="text-[10px] text-on-surface-variant font-mono"><?= htmlspecialchars($ref['employee_id'] ?: 'NIK N/A') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <p class="font-extrabold text-on-surface text-xs"><?= htmlspecialchars($ref['job_title'] ?: 'Karyawan') ?></p>
                                    <p class="text-[10px] text-on-surface-variant mt-0.5"><?= htmlspecialchars($ref['department_name'] ?? 'Pusat') ?></p>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($ref['status'] === 'draft'): ?>
                                        <span class="text-on-surface-variant/40 text-[10px] font-bold italic">- Draft -</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold border <?= $mood['color'] ?>">
                                            <span class="material-symbols-outlined text-xs"><?= $mood['icon'] ?></span>
                                            <?= $mood['label'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($ref['status'] === 'draft'): ?>
                                        <span class="text-on-surface-variant/40 text-[10px] font-bold italic">- Draft -</span>
                                    <?php else: ?>
                                        <span class="font-bold text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full">
                                            <?= $ref['workload_rating'] ?> / 5
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($ref['status'] === 'draft'): ?>
                                        <span class="text-on-surface-variant/40 text-[10px] font-bold italic">- Draft -</span>
                                    <?php else: ?>
                                        <span class="font-bold text-xs text-amber-500 flex items-center justify-center gap-0.5">
                                            <span class="material-symbols-outlined text-xs font-fill" style="font-variation-settings: 'FILL' 1">star</span>
                                            <span><?= $ref['core_values_rating'] ?></span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6">
                                    <?php if ($ref['status'] === 'draft'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-50 text-slate-600 border border-slate-200">
                                            Draf Karyawan
                                        </span>
                                    <?php elseif ($ref['status'] === 'submitted'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Menunggu Review
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-700 border border-green-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Selesai Ditinjau
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <button onclick="viewReflectionSA(<?= htmlspecialchars(json_encode($ref)) ?>)" class="bg-primary hover:bg-primary/95 text-white font-bold text-[10px] py-1.5 px-3 rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer shadow-sm">
                                        <span class="material-symbols-outlined text-xs">visibility</span>
                                        <span>Lihat Detail</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Reflection Details for Superadmin (Hidden by default) -->
<div id="saReviewModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-3xl mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="saReviewModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary font-bold">policy</span>
                <div>
                    <h3 class="text-base font-extrabold text-on-surface">Detail Refleksi Karyawan (Superadmin)</h3>
                    <p class="text-[10px] text-on-surface-variant font-semibold mt-0.5" id="saReviewEmployeeMeta"></p>
                </div>
            </div>
            <button onclick="closeSaReviewModal()" class="p-1.5 hover:bg-surface-container-high rounded-full transition-colors cursor-pointer flex items-center justify-center text-on-surface-variant">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6 flex-grow">
            <!-- Summary Badges -->
            <div class="grid grid-cols-3 gap-4 bg-surface rounded-2xl p-4 border border-outline-variant/10">
                <div class="text-center">
                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Kondisi Mood</span>
                    <span id="saReviewMoodBadge" class="inline-block mt-1"></span>
                </div>
                <div class="text-center border-x border-outline-variant/20">
                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Beban Kerja</span>
                    <span id="saReviewWorkloadBadge" class="font-extrabold text-xs text-on-surface block mt-1"></span>
                </div>
                <div class="text-center">
                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Penerapan Budaya</span>
                    <span id="saReviewValuesBadge" class="font-extrabold text-xs text-amber-500 block mt-1"></span>
                </div>
            </div>

            <!-- Content tabs -->
            <div class="space-y-4">
                <!-- Section 1: Kinerja -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-primary uppercase border-l-2 border-primary pl-2.5 tracking-wider">1. Kinerja & Hambatan</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Pencapaian Utama</span>
                            <p id="saReviewAchievements" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Tantangan & Hambatan</span>
                            <p id="saReviewChallenges" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Sasaran Periode Depan</span>
                            <p id="saReviewFutureGoals" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Dukungan yang Dibutuhkan</span>
                            <p id="saReviewSupportNeeded" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: IDP -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-xs font-bold text-primary uppercase border-l-2 border-primary pl-2.5 tracking-wider">2. Rencana Karir (IDP)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Aspirasi Karir</span>
                            <p id="saReviewCareerAspirations" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Keterampilan yang Ingin Dikembangkan</span>
                            <p id="saReviewSkillsToDevelop" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                    <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                        <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Rencana Tindakan Konkrit</span>
                        <p id="saReviewActionPlan" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                    </div>
                </div>

                <!-- Section 3: Notes & Personal Journals - FULLY VISIBLE for Superadmin -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-xs font-bold text-primary uppercase border-l-2 border-primary pl-2.5 tracking-wider">3. Catatan Refleksi Bebas & Jurnal (Kerahasiaan Dinonaktifkan bagi Superadmin)</h4>
                    <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10 space-y-4">
                        <div>
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Catatan Bebas Refleksi (Legacy)</span>
                            <p id="saReviewReflectionNotes" class="text-xs text-on-surface font-semibold leading-relaxed whitespace-pre-wrap mt-1"></p>
                        </div>
                        <div class="border-t border-outline-variant/20 pt-3">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block mb-2">Jurnal Belajar Mandiri (Harian/Pribadi)</span>
                            <div id="saReviewJournalsList" class="space-y-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Manager Feedback (if exists) -->
                <div class="space-y-3 pt-2" id="saReviewFeedbackSection">
                    <h4 class="text-xs font-bold text-emerald-700 uppercase border-l-2 border-emerald-600 pl-2.5 tracking-wider">4. Umpan Balik Atasan</h4>
                    <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 p-4 rounded-xl text-xs font-semibold whitespace-pre-wrap" id="saReviewFeedbackText"></div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end bg-surface-container-low/20 flex-shrink-0">
            <button type="button" onclick="closeSaReviewModal()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function changeReflectionPeriod(period) {
        if (window.loadPage) {
            window.loadPage('/superadmin/reflection?period=' + period);
        } else {
            window.location.href = '/superadmin/reflection?period=' + period;
        }
    }

    function filterReflectionList() {
        const query = document.getElementById('reflectionSearch').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.reflection-row');
        rows.forEach(r => {
            const searchVal = r.getAttribute('data-search') || '';
            if (searchVal.includes(query)) {
                r.style.display = 'table-row';
            } else {
                r.style.display = 'none';
            }
        });
    }

    function viewReflectionSA(ref) {
        document.getElementById('saReviewEmployeeMeta').innerText = `${ref.first_name} ${ref.last_name} (${ref.employee_id || 'NIK N/A'}) — Periode ${ref.period}`;
        
        // Setup stats badges
        const moodsMap = {
            'excellent': {label: 'Sangat Baik 😄', color: 'text-emerald-700 bg-emerald-50 border border-emerald-300'},
            'good':      {label: 'Baik 🙂',        color: 'text-blue-700 bg-blue-50 border border-blue-300'},
            'neutral':   {label: 'Biasa Saja 😐',  color: 'text-slate-700 bg-slate-50 border border-slate-300'},
            'tired':     {label: 'Lelah 😴',       color: 'text-amber-700 bg-amber-50 border border-amber-300'},
            'stressed':  {label: 'Stres / Cemas 😰',color: 'text-red-700 bg-red-50 border border-red-300'}
        };
        const mood = moodsMap[ref.mood_rating] || {label: ref.mood_rating || 'Draft', color: 'text-gray-700 bg-gray-50 border'};
        
        const moodBadge = document.getElementById('saReviewMoodBadge');
        moodBadge.innerText = mood.label;
        moodBadge.className = `inline-block px-3 py-1 rounded-full text-[10px] font-black ${mood.color}`;

        const workloadsMap = {
            1: 'Sangat Ringan',
            2: 'Ringan',
            3: 'Seimbang',
            4: 'Padat',
            5: 'Sangat Padat'
        };
        document.getElementById('saReviewWorkloadBadge').innerText = ref.status === 'draft' ? '-' : `${ref.workload_rating} - ${workloadsMap[ref.workload_rating] || ''}`;
        document.getElementById('saReviewValuesBadge').innerText = ref.status === 'draft' ? '-' : `${ref.core_values_rating} / 5`;

        // Content
        document.getElementById('saReviewAchievements').innerText = ref.achievements || '-';
        document.getElementById('saReviewChallenges').innerText = ref.challenges || '-';
        document.getElementById('saReviewFutureGoals').innerText = ref.future_goals || '-';
        document.getElementById('saReviewSupportNeeded').innerText = ref.support_needed || '-';
        document.getElementById('saReviewCareerAspirations').innerText = ref.career_aspirations || '-';
        document.getElementById('saReviewSkillsToDevelop').innerText = ref.skills_to_develop || '-';
        document.getElementById('saReviewActionPlan').innerText = ref.action_plan || '-';
        document.getElementById('saReviewReflectionNotes').innerText = ref.reflection_notes || '-';

        // Helper to escape HTML in JS
        const escapeHtml = (unsafe) => {
            return (unsafe || '')
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        // Render personal journals list
        const journalsList = document.getElementById('saReviewJournalsList');
        journalsList.innerHTML = '';
        if (ref.journals && ref.journals.length > 0) {
            ref.journals.forEach(jr => {
                const item = document.createElement('div');
                item.className = 'bg-surface border border-outline-variant/20 rounded-xl p-3.5 space-y-1.5 shadow-sm';
                item.innerHTML = `
                    <div class="flex justify-between items-center text-[10px] font-extrabold text-on-surface-variant">
                        <span class="text-primary">${escapeHtml(jr.title)}</span>
                        <span class="font-mono">${escapeHtml(jr.created_at)}</span>
                    </div>
                    <p class="text-xs text-on-surface leading-relaxed font-semibold whitespace-pre-wrap">${escapeHtml(jr.notes)}</p>
                `;
                journalsList.appendChild(item);
            });
        } else {
            journalsList.innerHTML = '<p class="text-xs text-on-surface-variant font-medium italic">Tidak ada catatan jurnal pribadi.</p>';
        }

        // Feedback
        const feedbackSection = document.getElementById('saReviewFeedbackSection');
        if (ref.manager_feedback) {
            feedbackSection.classList.remove('hidden');
            document.getElementById('saReviewFeedbackText').innerText = ref.manager_feedback;
        } else {
            feedbackSection.classList.add('hidden');
        }

        // Open modal
        const modal = document.getElementById('saReviewModal');
        const container = document.getElementById('saReviewModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }

    function closeSaReviewModal() {
        const modal = document.getElementById('saReviewModal');
        const container = document.getElementById('saReviewModalContainer');
        modal.classList.add('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
    }

    // Expose to global scope
    window.changeReflectionPeriod = changeReflectionPeriod;
    window.filterReflectionList = filterReflectionList;
    window.viewReflectionSA = viewReflectionSA;
    window.closeSaReviewModal = closeSaReviewModal;
</script>
