<?php
// Hiring Manager: Team Self-Reflection Reviews
$db = \App\Config\Database::getInstance()->getConnection();
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? '';

// Helper to get descendant department IDs recursively
if (!function_exists('getDescendantsReflection')) {
    function getDescendantsReflection($db, $deptId) {
        $ids = [$deptId];
        $stmt = $db->prepare("SELECT id FROM departments WHERE parent_id = ?");
        $stmt->execute([$deptId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($children as $childId) {
            $ids = array_merge($ids, getDescendantsReflection($db, $childId));
        }
        return $ids;
    }
}

// Get manager's department
$stmt = $db->prepare("SELECT department_id FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$managerDeptId = $stmt->fetchColumn();

$allowedDepts = [];
if (!empty($managerDeptId)) {
    $allowedDepts = getDescendantsReflection($db, $managerDeptId);
}

$hasDepartment = !empty($allowedDepts);
$inClause = $hasDepartment ? implode(',', array_map(fn($id) => $db->quote($id), $allowedDepts)) : "''";

// Selected period filter
$currentPeriod = date('Y') . '-Q' . ceil(date('n') / 3);
$selectedPeriod = $_GET['period'] ?? $currentPeriod;

// Parse year and quarter for mood pulses filter
$parts = explode('-Q', $selectedPeriod);
$year = isset($parts[0]) ? (int)$parts[0] : (int)date('Y');
$quarter = isset($parts[1]) ? (int)$parts[1] : (int)ceil(date('n') / 3);

$reflections = [];
if ($hasDepartment) {
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
        WHERE u.department_id IN ($inClause) AND u.id != :manager_id
          AND r.period = :period AND r.status IN ('submitted', 'completed')
        ORDER BY r.status DESC, r.updated_at DESC
    ");
    $stmtRef->execute([
        'manager_id' => $userId,
        'period' => $selectedPeriod,
        'year' => $year,
        'quarter' => $quarter
    ]);
    $reflections = $stmtRef->fetchAll(PDO::FETCH_ASSOC);
}

// Quarters selection list
$availablePeriods = [];
$year = date('Y');
for ($y = $year; $y >= $year - 1; $y--) {
    for ($q = 4; $q >= 1; $q--) {
        $availablePeriods[] = "$y-Q$q";
    }
}

function getMoodBadge($mood) {
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
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Refleksi Tim</h1>
            <p class="text-on-surface-variant font-medium text-sm">Evaluasi dan berikan umpan balik atas refleksi serta rencana pengembangan karir (IDP) tim Anda.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <select onchange="changeReflectionPeriod(this.value)" class="py-2.5 pl-3.5 pr-10 appearance-none text-xs rounded-xl border border-outline-variant/60 bg-surface-container-lowest focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-bold text-on-surface">
                    <?php foreach ($availablePeriods as $ap): ?>
                        <option value="<?= $ap ?>" <?= $ap === $selectedPeriod ? 'selected' : '' ?>><?= $ap ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant text-sm">arrow_drop_down</span>
            </div>
        </div>
    </div>

    <!-- Reflections List -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary font-bold">rate_review</span>
                <h2 class="text-lg font-extrabold text-on-surface">Daftar Refleksi Masuk</h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1000px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Anggota Tim</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Jabatan / Divisi</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Mood</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Beban Kerja</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-center">Budaya</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-right text-[11px] font-bold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <?php if (empty($reflections)): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-on-surface-variant font-medium text-xs">
                                <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">rate_review</span>
                                <p>Belum ada refleksi masuk untuk periode terpilih.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reflections as $ref): 
                            $mood = getMoodBadge($ref['mood_rating']);
                        ?>
                            <tr class="hover:bg-surface-container-low/30 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <?php 
                                        $hash = md5(strtolower(trim($ref['email'])));
                                        $avatar = !empty($ref['profile_picture']) ? $ref['profile_picture'] : "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
                                        ?>
                                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border" onerror="window.handleAvatarError(this, '<?= $hash ?>')" />
                                        <div>
                                            <h4 class="font-extrabold text-on-surface whitespace-nowrap"><?= htmlspecialchars($ref['first_name'] . ' ' . $ref['last_name']) ?></h4>
                                            <span class="text-[10px] text-on-surface-variant font-mono"><?= htmlspecialchars($ref['employee_id'] ?: 'N/A') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <p class="font-extrabold text-on-surface text-xs"><?= htmlspecialchars($ref['job_title'] ?: 'Karyawan') ?></p>
                                    <p class="text-[10px] text-on-surface-variant mt-0.5"><?= htmlspecialchars($ref['department_name'] ?? 'Pusat') ?></p>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold border <?= $mood['color'] ?>">
                                        <span class="material-symbols-outlined text-xs"><?= $mood['icon'] ?></span>
                                        <?= $mood['label'] ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="font-bold text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full">
                                        <?= $ref['workload_rating'] ?> / 5
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-xs text-amber-500">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <span class="material-symbols-outlined text-xs font-fill" style="font-variation-settings: 'FILL' 1">star</span>
                                        <span><?= $ref['core_values_rating'] ?></span>
                                    </div>
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
                                    <button onclick="viewReflection(<?= htmlspecialchars(json_encode($ref)) ?>)" class="bg-primary hover:bg-primary/95 text-white font-bold text-[10px] py-1.5 px-3 rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer shadow-sm">
                                        <span class="material-symbols-outlined text-xs">visibility</span>
                                        <span>Tinjau Refleksi</span>
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

<!-- Modal Reflection Details & Review (Hidden by default) -->
<div id="reviewModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-3xl mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="reviewModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary font-bold">psychology</span>
                <div>
                    <h3 class="text-base font-extrabold text-on-surface">Tinjau Refleksi Karyawan</h3>
                    <p class="text-[10px] text-on-surface-variant font-semibold mt-0.5" id="reviewEmployeeMeta"></p>
                </div>
            </div>
            <button onclick="closeReviewModal()" class="p-1.5 hover:bg-surface-container-high rounded-full transition-colors cursor-pointer flex items-center justify-center text-on-surface-variant">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6 flex-grow">
            <!-- Summary Badges -->
            <div class="grid grid-cols-3 gap-4 bg-surface rounded-2xl p-4 border border-outline-variant/10">
                <div class="text-center">
                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Kondisi Mood</span>
                    <span id="reviewMoodBadge" class="inline-block mt-1"></span>
                </div>
                <div class="text-center border-x border-outline-variant/20">
                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Beban Kerja</span>
                    <span id="reviewWorkloadBadge" class="font-extrabold text-xs text-on-surface block mt-1"></span>
                </div>
                <div class="text-center">
                    <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Penerapan Budaya</span>
                    <span id="reviewValuesBadge" class="font-extrabold text-xs text-amber-500 block mt-1"></span>
                </div>
            </div>

            <!-- Tab content: Performance & IDP -->
            <div class="space-y-4">
                <!-- Section 1: Kinerja -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-primary uppercase border-l-2 border-primary pl-2.5 tracking-wider">1. Kinerja & Hambatan</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Pencapaian Utama</span>
                            <p id="reviewAchievements" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Tantangan & Hambatan</span>
                            <p id="reviewChallenges" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Sasaran Periode Depan</span>
                            <p id="reviewFutureGoals" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Dukungan yang Dibutuhkan</span>
                            <p id="reviewSupportNeeded" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: IDP -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-xs font-bold text-primary uppercase border-l-2 border-primary pl-2.5 tracking-wider">2. Rencana Karir (IDP)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Aspirasi Karir</span>
                            <p id="reviewCareerAspirations" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                        <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                            <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Keterampilan yang Ingin Dikembangkan</span>
                            <p id="reviewSkillsToDevelop" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                        </div>
                    </div>
                    <div class="bg-surface-container-low/30 p-3.5 rounded-xl border border-outline-variant/10">
                        <span class="text-[9px] uppercase font-bold text-on-surface-variant/75 block">Rencana Tindakan Konkrit</span>
                        <p id="reviewActionPlan" class="text-xs text-on-surface font-semibold leading-relaxed mt-1 whitespace-pre-wrap"></p>
                    </div>
                </div>
            </div>

            <!-- Feedback Form -->
            <form id="feedbackForm" onsubmit="submitFeedbackForm(event)" class="space-y-3 border-t border-outline-variant/15 pt-4">
                <input type="hidden" name="reflection_id" id="feedbackReflectionId" />
                <label for="feedbackContent" class="block text-xs font-bold text-on-surface-variant uppercase">Umpan Balik / Catatan Evaluasi Manajer <span class="text-red-500">*</span></label>
                <textarea name="feedback" id="feedbackContent" rows="3" required placeholder="Tuliskan umpan balik konstruktif, apresiasi pencapaian, serta persetujuan rencana aksi karir staf..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface"></textarea>
            </form>

            <!-- Locked Feedback View (if already completed) -->
            <div id="lockedFeedbackView" class="space-y-2 border-t border-outline-variant/15 pt-4 hidden">
                <span class="block text-xs font-bold text-on-surface-variant uppercase">Umpan Balik Manajer</span>
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 p-4 rounded-xl text-xs font-semibold whitespace-pre-wrap" id="lockedFeedbackText"></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-2 bg-surface-container-low/20 flex-shrink-0">
            <button type="button" onclick="closeReviewModal()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer">
                Batal
            </button>
            <button type="submit" form="feedbackForm" id="submitFeedbackBtn" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-5 rounded-xl transition-all shadow-md shadow-primary/10 cursor-pointer">
                Simpan Umpan Balik
            </button>
        </div>
    </div>
</div>

<script>
    function changeReflectionPeriod(period) {
        if (window.loadPage) {
            window.loadPage('/manager/reflection?period=' + period);
        } else {
            window.location.href = '/manager/reflection?period=' + period;
        }
    }

    // Modal Control
    function viewReflection(ref) {
        document.getElementById('feedbackReflectionId').value = ref.id;
        document.getElementById('reviewEmployeeMeta').innerText = `${ref.first_name} ${ref.last_name} (${ref.employee_id || 'NIK N/A'}) — Periode ${ref.period}`;
        
        // Setup stats badges
        const moodsMap = {
            'excellent': {label: 'Sangat Baik', color: 'text-emerald-700 bg-emerald-50 border border-emerald-300'},
            'good':      {label: 'Baik',        color: 'text-blue-700 bg-blue-50 border border-blue-300'},
            'neutral':   {label: 'Biasa Saja',  color: 'text-slate-700 bg-slate-50 border border-slate-300'},
            'tired':     {label: 'Lelah',       color: 'text-amber-700 bg-amber-50 border border-amber-300'},
            'stressed':  {label: 'Stres / Cemas',color: 'text-red-700 bg-red-50 border border-red-300'}
        };
        const mood = moodsMap[ref.mood_rating] || {label: ref.mood_rating, color: 'text-gray-700 bg-gray-50 border'};
        
        const moodBadge = document.getElementById('reviewMoodBadge');
        moodBadge.innerText = mood.label;
        moodBadge.className = `inline-block px-3 py-1 rounded-full text-[10px] font-black ${mood.color}`;

        const workloadsMap = {
            1: 'Sangat Ringan',
            2: 'Ringan',
            3: 'Seimbang',
            4: 'Padat',
            5: 'Sangat Padat'
        };
        document.getElementById('reviewWorkloadBadge').innerText = `${ref.workload_rating} - ${workloadsMap[ref.workload_rating] || ''}`;
        document.getElementById('reviewValuesBadge').innerText = `${ref.core_values_rating} / 5`;

        // Content
        document.getElementById('reviewAchievements').innerText = ref.achievements || '-';
        document.getElementById('reviewChallenges').innerText = ref.challenges || '-';
        document.getElementById('reviewFutureGoals').innerText = ref.future_goals || '-';
        document.getElementById('reviewSupportNeeded').innerText = ref.support_needed || '-';
        document.getElementById('reviewCareerAspirations').innerText = ref.career_aspirations || '-';
        document.getElementById('reviewSkillsToDevelop').innerText = ref.skills_to_develop || '-';
        document.getElementById('reviewActionPlan').innerText = ref.action_plan || '-';

        // Show/hide feedback input depending on status
        const form = document.getElementById('feedbackForm');
        const lockedView = document.getElementById('lockedFeedbackView');
        const submitBtn = document.getElementById('submitFeedbackBtn');

        if (ref.status === 'completed') {
            form.classList.add('hidden');
            lockedView.classList.remove('hidden');
            document.getElementById('lockedFeedbackText').innerText = ref.manager_feedback || '-';
            submitBtn.classList.add('hidden');
        } else {
            form.classList.remove('hidden');
            lockedView.classList.add('hidden');
            document.getElementById('feedbackContent').value = '';
            submitBtn.classList.remove('hidden');
        }

        // Open modal
        const modal = document.getElementById('reviewModal');
        const container = document.getElementById('reviewModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }

    function closeReviewModal() {
        const modal = document.getElementById('reviewModal');
        const container = document.getElementById('reviewModalContainer');
        modal.classList.add('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
    }

    function submitFeedbackForm(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Kirim Umpan Balik?',
            text: 'Umpan balik evaluasi akan disimpan secara permanen dan status refleksi karyawan diubah menjadi selesai. Apakah Anda yakin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Menyimpan umpan balik penilaian ke sistem siCare.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const form = document.getElementById('feedbackForm');
                const formData = new FormData(form);

                fetch('/manager/reflection/feedback', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil Disimpan!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => {
                            closeReviewModal();
                            if (window.loadPage) {
                                window.loadPage('/manager/reflection?period=<?= $selectedPeriod ?>');
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal Menyimpan',
                            text: data.message || 'Terjadi kesalahan sistem.',
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

    // Expose to global scope
    window.changeReflectionPeriod = changeReflectionPeriod;
    window.viewReflection = viewReflection;
    window.closeReviewModal = closeReviewModal;
    window.submitFeedbackForm = submitFeedbackForm;
</script>
