<?php
// HR Operations: Corporate Employee Self-Reflection & IDP Tracker (GDPR & UU PDP Masked)
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

// Fetch all reflections in the company for the selected period
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
    WHERE r.period = :period AND r.status IN ('submitted', 'completed')
    ORDER BY r.status DESC, r.updated_at DESC
");
$stmtRef->execute([
    'period' => $selectedPeriod,
    'year' => $year,
    'quarter' => $quarter
]);
$reflections = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

// Calculate Aggregates for HR Ops Dashboard
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
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Refleksi Karyawan (HR Operations)</h1>
            <p class="text-on-surface-variant font-medium text-sm">Pantau partisipasi, evaluasi rencana pengembangan karir (IDP) untuk L&D, dengan perlindungan privasi data mental health karyawan.</p>
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

    <!-- Privacy Notice Banner -->
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 flex items-start gap-3">
        <span class="material-symbols-outlined text-amber-600 mt-0.5">security</span>
        <div>
            <h4 class="font-extrabold text-sm text-amber-900">Perlindungan Privasi & Kepatuhan UU PDP</h4>
            <p class="text-xs text-amber-800/95 mt-0.5">Sesuai prinsip <strong>Segregation of Duties</strong> dan <strong>Psychological Safety</strong>, detail kondisi mood harian dan catatan bebas/jurnal psikologis karyawan disembunyikan (redacted) bagi HR & Executive, namun data agregat korporat tetap dapat dipantau demi kepentingan L&D dan kesejahteraan organisasi.</p>
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
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1">Status beban kerja organisasi</p>
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
                <p class="text-[9px] text-on-surface-variant font-semibold mt-1">Nilai budaya rata-rata korporasi</p>
            </div>
        </div>

        <!-- Submission Status Card -->
        <div class="stat-card-scale bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Status Partisipasi</span>
                <span class="material-symbols-outlined text-blue-600 bg-blue-50 p-2 rounded-lg text-sm">assignment_turned_in</span>
            </div>
            <div class="grid grid-cols-2 text-center gap-1 pt-1">
                <div class="border-r border-outline-variant/20">
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
            <input type="text" id="reflectionSearch" oninput="filterReflectionList()" placeholder="Cari nama karyawan, NIK, jabatan, atau divisi..." 
                class="w-full pl-10 pr-4 py-2.5 border border-outline-variant/30 rounded-2xl bg-surface-container-low text-xs text-on-surface font-semibold placeholder-on-surface-variant/55 focus:outline-none focus:border-primary transition-all" />
        </div>
    </div>

    <!-- Table List -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden animate-fade-in">
        <div class="overflow-x-auto">
            <table class="min-w-[1000px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Karyawan</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Jabatan & Divisi</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Budaya</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Beban Kerja</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reflectionTableTbody" class="divide-y divide-outline-variant/10">
                    <?php if (empty($reflections)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-on-surface-variant font-medium text-xs">
                                <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">rate_review</span>
                                <p>Belum ada refleksi masuk untuk periode terpilih.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reflections as $ref): 
                            $searchStr = strtolower($ref['first_name'] . ' ' . $ref['last_name'] . ' ' . ($ref['employee_id'] ?? '') . ' ' . ($ref['job_title'] ?? '') . ' ' . ($ref['department_name'] ?? '') . ' ' . $ref['status']);
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
                                <td class="py-4 px-6 text-center font-bold text-xs text-amber-500">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <span class="material-symbols-outlined text-xs font-fill" style="font-variation-settings: 'FILL' 1">star</span>
                                        <span><?= $ref['core_values_rating'] ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="font-bold text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full">
                                        <?= $ref['workload_rating'] ?> / 5
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <?php if ($ref['status'] === 'submitted'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Butuh Review
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-700 border border-green-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Selesai
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <button onclick="viewReflectionHR(<?= htmlspecialchars(json_encode($ref)) ?>)" class="bg-primary hover:bg-primary/95 text-white font-bold text-[10px] py-1.5 px-3 rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer shadow-sm">
                                        <span class="material-symbols-outlined text-xs">visibility</span>
                                        <span>Tinjau IDP & Appraisal</span>
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

<!-- Modal Details for HR (Hidden by default) -->
<div id="hrReviewModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-3xl mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="hrReviewModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary font-bold">shield_person</span>
                <div>
                    <h3 class="text-base font-extrabold text-on-surface">Tinjau IDP & Kinerja Karyawan</h3>
                    <p class="text-[10px] text-on-surface-variant font-semibold mt-0.5" id="hrReviewEmployeeMeta"></p>
                </div>
            </div>
            <button onclick="closeHrReviewModal()" class="p-1.5 hover:bg-surface-container-high rounded-full transition-colors cursor-pointer flex items-center justify-center text-on-surface-variant">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6 flex-grow">
            <!-- Privacy Indicator -->
            <div class="bg-blue-50 border border-blue-150 text-blue-800 p-4 rounded-xl flex items-center gap-2.5 text-xs font-bold">
                <span class="material-symbols-outlined text-blue-600">lock</span>
                <span>Data mood harian dan catatan jurnal psikologis disembunyikan demi menjaga kenyamanan karyawan (UU PDP).</span>
            </div>

            <!-- Content -->
            <div class="space-y-4">
                <!-- Section 1: Kinerja -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-primary uppercase border-l-2 border-primary pl-2.5 tracking-wider">1. Kinerja & Hambatan</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Pencapaian Utama</span>
                            <p id="hrReviewAchievements" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Tantangan & Hambatan</span>
                            <p id="hrReviewChallenges" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Sasaran Periode Depan</span>
                            <p id="hrReviewFutureGoals" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Dukungan yang Dibutuhkan</span>
                            <p id="hrReviewSupportNeeded" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: IDP -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-xs font-bold text-primary uppercase border-l-2 border-primary pl-2.5 tracking-wider">2. Rencana Karir (IDP)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Aspirasi Karir</span>
                            <p id="hrReviewCareerAspirations" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Keterampilan yang Ingin Dikembangkan</span>
                            <p id="hrReviewSkillsToDevelop" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                    <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                        <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Rencana Tindakan Konkrit</span>
                        <p id="hrReviewActionPlan" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                    </div>
                </div>

                <!-- Section 3: Manager Feedback (if exists) -->
                <div class="space-y-3 pt-2" id="hrReviewFeedbackSection">
                    <h4 class="text-xs font-bold text-emerald-700 uppercase border-l-2 border-emerald-600 pl-2.5 tracking-wider">3. Umpan Balik Atasan</h4>
                    <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 p-4 rounded-xl text-xs font-semibold whitespace-pre-wrap" id="hrReviewFeedbackText"></div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end bg-surface-container-low/20 flex-shrink-0">
            <button type="button" onclick="closeHrReviewModal()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function changeReflectionPeriod(period) {
        if (window.loadPage) {
            window.loadPage('/hrops/reflection?period=' + period);
        } else {
            window.location.href = '/hrops/reflection?period=' + period;
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

    function viewReflectionHR(ref) {
        document.getElementById('hrReviewEmployeeMeta').innerText = `${ref.first_name} ${ref.last_name} (${ref.employee_id || 'NIK N/A'}) — Periode ${ref.period}`;
        
        // Content
        document.getElementById('hrReviewAchievements').innerText = ref.achievements || '-';
        document.getElementById('hrReviewChallenges').innerText = ref.challenges || '-';
        document.getElementById('hrReviewFutureGoals').innerText = ref.future_goals || '-';
        document.getElementById('hrReviewSupportNeeded').innerText = ref.support_needed || '-';
        document.getElementById('hrReviewCareerAspirations').innerText = ref.career_aspirations || '-';
        document.getElementById('hrReviewSkillsToDevelop').innerText = ref.skills_to_develop || '-';
        document.getElementById('hrReviewActionPlan').innerText = ref.action_plan || '-';

        // Feedback
        const feedbackSection = document.getElementById('hrReviewFeedbackSection');
        if (ref.manager_feedback) {
            feedbackSection.classList.remove('hidden');
            document.getElementById('hrReviewFeedbackText').innerText = ref.manager_feedback;
        } else {
            feedbackSection.classList.add('hidden');
        }

        // Open modal
        const modal = document.getElementById('hrReviewModal');
        const container = document.getElementById('hrReviewModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }

    function closeHrReviewModal() {
        const modal = document.getElementById('hrReviewModal');
        const container = document.getElementById('hrReviewModalContainer');
        modal.classList.add('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
    }

    // Expose to global scope
    window.changeReflectionPeriod = changeReflectionPeriod;
    window.filterReflectionList = filterReflectionList;
    window.viewReflectionHR = viewReflectionHR;
    window.closeHrReviewModal = closeHrReviewModal;
</script>
