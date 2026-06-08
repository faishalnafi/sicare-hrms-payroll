<?php
// Employee ESS Self-Reflection Dashboard
$db = \App\Config\Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? '';

if (empty($userId)) {
    echo "<div class='p-6 text-red-600 font-bold'>Akses Ditolak: Sesi kedaluwarsa. Silakan login kembali.</div>";
    return;
}

// Generate current quarter, e.g. "2026-Q2"
$currentPeriod = date('Y') . '-Q' . ceil(date('n') / 3);
$selectedPeriod = $_GET['period'] ?? $currentPeriod;

// Fetch employee's reflection for the selected period
$stmt = $db->prepare("SELECT * FROM self_reflections WHERE user_id = :user_id AND period = :period");
$stmt->execute(['user_id' => $userId, 'period' => $selectedPeriod]);
$reflection = $stmt->fetch();

// Determine status
$status = $reflection['status'] ?? 'none';
$isLocked = ($status === 'submitted' || $status === 'completed');

// Fetch manager details if feedback exists
$managerName = '-';
if (!empty($reflection['manager_feedback_by'])) {
    $stmtMgr = $db->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
    $stmtMgr->execute(['id' => $reflection['manager_feedback_by']]);
    $mgr = $stmtMgr->fetch();
    if ($mgr) {
        $managerName = $mgr['first_name'] . ' ' . $mgr['last_name'];
    }
}

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
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Refleksi Diri</h1>
            <p class="text-on-surface-variant font-medium text-sm">Evaluasi kinerja mandiri, pantau tingkat well-being, dan rencanakan pengembangan karir Anda.</p>
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

    <!-- Status Alert Banner -->
    <?php if ($status === 'draft'): ?>
        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-2xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-600 mt-0.5">drafts</span>
            <div>
                <h4 class="font-extrabold text-sm text-blue-900">Refleksi Diri Masih Berstatus Draf</h4>
                <p class="text-xs text-blue-800/95 mt-0.5">Refleksi Anda untuk periode <strong><?= htmlspecialchars($selectedPeriod) ?></strong> telah disimpan sebagai draf dan belum dikirim ke atasan. Selesaikan pengisian lalu klik "Kirim Refleksi".</p>
            </div>
        </div>
    <?php elseif ($status === 'submitted'): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-amber-600 mt-0.5 animate-pulse">hourglass_empty</span>
            <div>
                <h4 class="font-extrabold text-sm text-amber-900">Menunggu Review Atasan</h4>
                <p class="text-xs text-amber-800/95 mt-0.5">Refleksi Anda telah dikirim dan terkunci. Atasan langsung Anda akan meninjau dan memberikan umpan balik.</p>
            </div>
        </div>
    <?php elseif ($status === 'completed'): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-emerald-600 mt-0.5">check_circle</span>
            <div>
                <h4 class="font-extrabold text-sm text-emerald-900">Refleksi Selesai & Ditinjau</h4>
                <p class="text-xs text-emerald-800/95 mt-0.5">Atasan Anda telah meninjau refleksi ini dan memberikan masukan di bagian bawah halaman.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-surface-container-high/40 border border-outline-variant/30 text-on-surface-variant rounded-2xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-on-surface-variant mt-0.5">edit_note</span>
            <div>
                <h4 class="font-extrabold text-sm text-on-surface">Mulai Refleksi Baru</h4>
                <p class="text-xs text-on-surface-variant mt-0.5">Silakan isi formulir hibrida di bawah ini untuk periode <strong><?= htmlspecialchars($selectedPeriod) ?></strong>.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Kebijakan Kepatuhan & Transparansi Privasi Data (UU PDP) -->
    <div class="bg-indigo-50/50 border border-indigo-150 text-indigo-900 rounded-2xl p-5 shadow-sm space-y-3">
        <div class="flex items-center gap-2 text-indigo-950 font-bold text-sm">
            <span class="material-symbols-outlined text-indigo-600 font-bold">shield_with_heart</span>
            <span>Transparansi Privasi & Keamanan Data Refleksi (UU PDP)</span>
        </div>
        <p class="text-xs text-indigo-900/90 leading-relaxed font-medium">
            Demi menjaga <strong>Psychological Safety</strong> (keamanan psikologis) dan mematuhi regulasi UU PDP, sistem siCare HRMS menerapkan pembatasan hak akses yang ketat terhadap data refleksi Anda:
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1 text-xs">
            <div class="bg-white/60 border border-indigo-100 rounded-xl p-3.5 space-y-1.5 shadow-sm">
                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase text-indigo-950 tracking-wider">
                    <span class="material-symbols-outlined text-xs text-indigo-600">visibility</span> Kinerja & Karir (IDP)
                </span>
                <p class="text-[10px] text-indigo-900/80 leading-relaxed font-semibold">
                    Dapat dilihat secara <strong>transparan</strong> oleh <strong>Atasan Langsung</strong> (Hiring Manager) dan <strong>Tim HR</strong> guna penilaian kinerja & program pelatihan (L&D).
                </p>
            </div>
            <div class="bg-white/60 border border-indigo-100 rounded-xl p-3.5 space-y-1.5 shadow-sm">
                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase text-amber-600 tracking-wider">
                    <span class="material-symbols-outlined text-xs text-amber-600">query_stats</span> Mood & Beban Kerja
                </span>
                <p class="text-[10px] text-indigo-900/80 leading-relaxed font-semibold">
                    Atasan Langsung melihat <strong>grafik tren</strong> tanpa tulisan sensitif. Tim HR & Executive hanya memantau data secara <strong>agregat & anonim</strong> per departemen.
                </p>
            </div>
            <div class="bg-white/60 border border-indigo-100 rounded-xl p-3.5 space-y-1.5 shadow-sm">
                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase text-red-950 tracking-wider">
                    <span class="material-symbols-outlined text-xs text-red-600">lock</span> Jurnal & Catatan Bebas
                </span>
                <p class="text-[10px] text-indigo-900/80 leading-relaxed font-semibold">
                    Bersifat <strong>Sangat Rahasia</strong>. Sama sekali <strong>TIDAK ditampilkan</strong> ke Atasan Langsung, HR, atau Executive.
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content Container with Tabs -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <!-- Tabs Header -->
        <div class="border-b border-outline-variant/15 bg-surface-container-low/40 flex overflow-x-auto">
            <button type="button" onclick="switchTab('tab-performance')" id="btn-tab-performance" class="tab-btn px-6 py-4 border-b-2 border-primary text-primary font-bold text-xs flex items-center gap-2 whitespace-nowrap focus:outline-none transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm">military_tech</span>
                1. Kinerja & Evaluasi
            </button>
            <button type="button" onclick="switchTab('tab-career')" id="btn-tab-career" class="tab-btn px-6 py-4 border-b-2 border-transparent text-on-surface-variant font-semibold text-xs flex items-center gap-2 whitespace-nowrap focus:outline-none transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm">rocket_launch</span>
                2. Rencana Karir (IDP)
            </button>
            <button type="button" onclick="switchTab('tab-journal')" id="btn-tab-journal" class="tab-btn px-6 py-4 border-b-2 border-transparent text-on-surface-variant font-semibold text-xs flex items-center gap-2 whitespace-nowrap focus:outline-none transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm">book</span>
                3. Jurnal Belajar Mandiri (Privat)
            </button>
        </div>

        <!-- Form Body -->
        <form id="reflectionForm" onsubmit="submitReflectionForm(event)" class="p-6 space-y-6">
            <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>" />
            <input type="hidden" name="status" id="reflectionStatus" value="draft" />

            <!-- Tab 1: Performance -->
            <div id="tab-performance" class="tab-content space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Achievements -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase">Pencapaian Utama (Achievements) <span class="text-red-500">*</span></label>
                        <p class="text-[10px] text-on-surface-variant font-medium">Apa saja kontribusi atau keberhasilan terbesar Anda pada periode ini?</p>
                        <textarea name="achievements" required <?= $isLocked ? 'disabled' : '' ?> rows="4" placeholder="Tuliskan di sini..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface disabled:opacity-75 disabled:bg-surface-container-low"><?= htmlspecialchars($reflection['achievements'] ?? '') ?></textarea>
                    </div>

                    <!-- Challenges -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase">Tantangan & Hambatan (Challenges) <span class="text-red-500">*</span></label>
                        <p class="text-[10px] text-on-surface-variant font-medium">Hambatan apa yang Anda temui dalam pekerjaan, dan bagaimana Anda mengatasinya?</p>
                        <textarea name="challenges" required <?= $isLocked ? 'disabled' : '' ?> rows="4" placeholder="Tuliskan di sini..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface disabled:opacity-75 disabled:bg-surface-container-low"><?= htmlspecialchars($reflection['challenges'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                    <!-- Core Values Rating -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase">Implementasi Budaya Perusahaan (1 - 5) <span class="text-red-500">*</span></label>
                        <p class="text-[10px] text-on-surface-variant font-medium">Seberapa baik Anda mempraktikkan core values perusahaan?</p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <?php $ratingVal = (int)($reflection['core_values_rating'] ?? 5); ?>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" <?= $isLocked ? 'disabled' : '' ?> onclick="setCoreValuesRating(<?= $i ?>)" id="star-<?= $i ?>" class="star-btn p-1 transition-transform hover:scale-110 cursor-pointer disabled:cursor-default">
                                    <span class="material-symbols-outlined text-2xl <?= $i <= $ratingVal ? 'text-amber-500 font-fill' : 'text-outline-variant' ?>" style="<?= $i <= $ratingVal ? "font-variation-settings: 'FILL' 1" : '' ?>">star</span>
                                </button>
                            <?php endfor; ?>
                            <input type="hidden" name="core_values_rating" id="coreValuesRatingInput" value="<?= $ratingVal ?>" />
                        </div>
                    </div>

                    <!-- Future Goals -->
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase">Sasaran Periode Berikutnya (Future Goals) <span class="text-red-500">*</span></label>
                        <p class="text-[10px] text-on-surface-variant font-medium">Apa fokus utama dan target pribadi yang ingin Anda capai di periode mendatang?</p>
                        <textarea name="future_goals" required <?= $isLocked ? 'disabled' : '' ?> rows="3" placeholder="Tuliskan sasaran Anda..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface disabled:opacity-75 disabled:bg-surface-container-low"><?= htmlspecialchars($reflection['future_goals'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Support Needed -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Dukungan yang Dibutuhkan (Support Needed)</label>
                    <p class="text-[10px] text-on-surface-variant font-medium">Bantuan, resource, atau pelatihan apa yang Anda butuhkan dari atasan/tim untuk periode selanjutnya?</p>
                    <textarea name="support_needed" <?= $isLocked ? 'disabled' : '' ?> rows="2" placeholder="Tuliskan di sini (opsional)..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface disabled:opacity-75 disabled:bg-surface-container-low"><?= htmlspecialchars($reflection['support_needed'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Tab 2: Career / IDP -->
            <div id="tab-career" class="tab-content space-y-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Career Aspirations -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase">Aspirasi Karir (Career Goals) <span class="text-red-500">*</span></label>
                        <p class="text-[10px] text-on-surface-variant font-medium">Peran, posisi, atau tanggung jawab baru apa yang ingin Anda capai dalam 2-5 tahun ke depan?</p>
                        <textarea name="career_aspirations" required <?= $isLocked ? 'disabled' : '' ?> rows="4" placeholder="Contoh: Menjadi Senior Software Engineer / Lead Developer..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface disabled:opacity-75 disabled:bg-surface-container-low"><?= htmlspecialchars($reflection['career_aspirations'] ?? '') ?></textarea>
                    </div>

                    <!-- Skills to Develop -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase">Peningkatan Keterampilan (Skills to Develop) <span class="text-red-500">*</span></label>
                        <p class="text-[10px] text-on-surface-variant font-medium">Keterampilan teknis (hard skills) atau interpersonal (soft skills) apa yang ingin Anda asah?</p>
                        <textarea name="skills_to_develop" required <?= $isLocked ? 'disabled' : '' ?> rows="4" placeholder="Contoh: Advanced Redis, System Design, Kepemimpinan Tim..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface disabled:opacity-75 disabled:bg-surface-container-low"><?= htmlspecialchars($reflection['skills_to_develop'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Action Plan -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase">Rencana Tindakan Nyata (Action Plan) <span class="text-red-500">*</span></label>
                    <p class="text-[10px] text-on-surface-variant font-medium">Langkah konkrit apa yang akan Anda ambil (misal: mengikuti kursus, terlibat proyek baru, mentoring)?</p>
                    <textarea name="action_plan" required <?= $isLocked ? 'disabled' : '' ?> rows="3" placeholder="Contoh: Mengikuti sertifikasi AWS Cloud Practitioner, melakukan mentoring 1-on-1 harian..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface disabled:opacity-75 disabled:bg-surface-container-low"><?= htmlspecialchars($reflection['action_plan'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Tab 3: Jurnal Belajar Mandiri (Privat) -->
            <div id="tab-journal" class="tab-content space-y-6 hidden">
                <div class="bg-indigo-50/40 border border-indigo-150 text-indigo-900 rounded-xl p-4 text-xs font-medium leading-relaxed shadow-sm">
                    <span class="material-symbols-outlined text-indigo-600 text-sm align-middle mr-1.5 font-bold">lock</span>
                    Jurnal ini bersifat <strong>sepenuhnya rahasia</strong>. Catatan Anda di sini <strong>tidak akan pernah dibagikan</strong> ke Atasan Langsung (Manager), HR, atau Eksekutif. Ini adalah ruang belajar mandiri dan catatan refleksi pribadi Anda.
                </div>

                <!-- Form to Add Journal Entry -->
                <div id="addJournalContainer" class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-5 space-y-4">
                    <h4 class="text-xs font-bold text-on-surface uppercase">Buat Catatan Jurnal Baru</h4>
                    
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase">Judul Catatan</label>
                        <input type="text" id="journalTitle" placeholder="Contoh: Pembelajaran System Design, Refleksi Konflik..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase">Isi Catatan / Refleksi Pribadi <span class="text-red-500">*</span></label>
                        <textarea id="journalNotes" rows="4" placeholder="Tuliskan proses belajar, tantangan emosional, atau catatan teknis Anda secara bebas di sini..." class="py-2.5 px-3.5 w-full text-xs rounded-xl border border-outline-variant bg-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary font-semibold text-on-surface"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" onclick="submitPersonalJournal()" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2 px-4 rounded-xl transition-all shadow-md cursor-pointer">
                            Simpan Jurnal Pribadi
                        </button>
                    </div>
                </div>

                <!-- Past Journal Entries -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-on-surface-variant uppercase">Riwayat Jurnal Belajar Mandiri</h4>
                    
                    <?php
                    // Fetch personal journals
                    $stmtJournals = $db->prepare("SELECT * FROM personal_journals WHERE user_id = :user_id ORDER BY created_at DESC");
                    $stmtJournals->execute(['user_id' => $userId]);
                    $journals = $stmtJournals->fetchAll();
                    ?>

                    <div id="journalsHistoryList" class="space-y-3.5">
                        <?php if (empty($journals)): ?>
                            <div class="bg-surface p-6 rounded-2xl border border-outline-variant/20 text-center text-on-surface-variant text-xs">
                                <span class="material-symbols-outlined text-3xl text-outline-variant mb-1.5">book</span>
                                <p>Belum ada catatan jurnal pribadi.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($journals as $jr): ?>
                                <div class="bg-surface p-4 rounded-2xl border border-outline-variant/30 shadow-sm space-y-2">
                                    <div class="flex justify-between items-start">
                                        <h5 class="font-extrabold text-on-surface text-xs"><?= htmlspecialchars($jr['title']) ?></h5>
                                        <span class="text-[9px] font-bold text-on-surface-variant font-mono"><?= date('d M Y, H:i', strtotime($jr['created_at'])) ?></span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant leading-relaxed font-medium whitespace-pre-wrap"><?= htmlspecialchars($jr['notes']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons for Form -->
            <?php if (!$isLocked): ?>
                <div class="pt-4 flex items-center justify-end gap-2 border-t border-outline-variant/15 mt-6">
                    <button type="button" onclick="saveAsDraft()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer">
                        Simpan Draf
                    </button>
                    <button type="submit" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2.5 px-5 rounded-xl transition-all shadow-md shadow-primary/10 cursor-pointer">
                        Kirim Refleksi
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Manager Feedback Section (Only visible if completed or feedback exists) -->
    <?php if ($status === 'completed' || !empty($reflection['manager_feedback'])): ?>
        <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600 font-bold">rate_review</span>
                <h3 class="text-lg font-extrabold text-on-surface">Umpan Balik & Masukan Atasan</h3>
            </div>
            <div class="bg-surface-container-low/40 border border-outline-variant/10 rounded-xl p-5 space-y-3">
                <p class="text-xs font-semibold text-on-surface leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($reflection['manager_feedback']) ?></p>
                <div class="flex items-center justify-between border-t border-outline-variant/20 pt-3 text-[10px] font-bold text-on-surface-variant">
                    <span>Peninjau: <strong class="text-primary"><?= htmlspecialchars($managerName) ?></strong></span>
                    <span>Tanggal Ditinjau: <?= date('d M Y, H:i:s', strtotime($reflection['manager_feedback_at'])) ?> WIB</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Tab switching logic
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('border-primary', 'text-primary', 'font-bold');
            b.classList.add('border-transparent', 'text-on-surface-variant', 'font-semibold');
        });

        document.getElementById(tabId).classList.remove('hidden');
        
        const activeBtn = document.getElementById('btn-' + tabId);
        activeBtn.classList.add('border-primary', 'text-primary', 'font-bold');
        activeBtn.classList.remove('border-transparent', 'text-on-surface-variant', 'font-semibold');
    }

    // Star rating handler
    function setCoreValuesRating(rating) {
        document.getElementById('coreValuesRatingInput').value = rating;
        for (let i = 1; i <= 5; i++) {
            const star = document.getElementById('star-' + i);
            const icon = star.querySelector('.material-symbols-outlined');
            if (i <= rating) {
                icon.classList.add('text-amber-500');
                icon.classList.remove('text-outline-variant');
                icon.style.fontVariationSettings = "'FILL' 1";
            } else {
                icon.classList.remove('text-amber-500');
                icon.classList.add('text-outline-variant');
                icon.style.fontVariationSettings = "'FILL' 0";
            }
        }
    }

    // Submit personal journal
    function submitPersonalJournal() {
        const title = document.getElementById('journalTitle').value;
        const notes = document.getElementById('journalNotes').value;

        if (!notes.trim()) {
            Swal.fire({
                title: 'Peringatan',
                text: 'Isi catatan jurnal tidak boleh kosong.',
                icon: 'warning',
                confirmButtonColor: '#ba1a1a'
            });
            return;
        }

        Swal.fire({
            title: 'Menyimpan...',
            text: 'Menyimpan catatan jurnal pribadi Anda.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const formData = new FormData();
        formData.append('title', title);
        formData.append('notes', notes);

        fetch('/employee/reflection/save-journal', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    document.getElementById('journalTitle').value = '';
                    document.getElementById('journalNotes').value = '';
                    if (window.loadPage) {
                        window.loadPage('/employee/reflection?period=<?= htmlspecialchars($selectedPeriod) ?>');
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Gagal',
                    text: data.message || 'Terjadi kesalahan.',
                    icon: 'error',
                    confirmButtonColor: '#ba1a1a'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                title: 'Kesalahan Sistem',
                text: 'Terjadi kesalahan koneksi ke server.',
                icon: 'error',
                confirmButtonColor: '#ba1a1a'
            });
            console.error(err);
        });
    }

    // Period switcher
    function changeReflectionPeriod(period) {
        if (window.loadPage) {
            window.loadPage('/employee/reflection?period=' + period);
        } else {
            window.location.href = '/employee/reflection?period=' + period;
        }
    }

    // Save draft handler
    function saveAsDraft() {
        document.getElementById('reflectionStatus').value = 'draft';
        const form = document.getElementById('reflectionForm');
        
        // Remove 'required' temporarily for draft saving
        const requiredElements = Array.from(form.querySelectorAll('[required]'));
        requiredElements.forEach(el => el.removeAttribute('required'));

        Swal.fire({
            title: 'Menyimpan Draf...',
            text: 'Menyimpan progres pengisian refleksi diri Anda.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const formData = new FormData(form);
        fetch('/employee/reflection/save', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Restore required attributes
            requiredElements.forEach(el => el.setAttribute('required', ''));
            
            if (data.success) {
                Swal.fire({
                    title: 'Draf Disimpan',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => {
                    if (window.loadPage) {
                        window.loadPage('/employee/reflection?period=' + formData.get('period'));
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
            requiredElements.forEach(el => el.setAttribute('required', ''));
            Swal.fire({
                title: 'Kesalahan Koneksi',
                text: 'Koneksi ke server terputus. Silakan coba sesaat lagi.',
                icon: 'error',
                confirmButtonColor: '#ba1a1a'
            });
            console.error(err);
        });
    }

    // Form submit handler (final submit)
    function submitReflectionForm(e) {
        e.preventDefault();
        document.getElementById('reflectionStatus').value = 'submitted';
        
        Swal.fire({
            title: 'Kirim Refleksi Diri?',
            text: 'Setelah dikirim, refleksi Anda akan dikunci dan atasan Anda akan menerima notifikasi untuk memberikan penilaian. Apakah Anda yakin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Kirim!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Mengirim...',
                    text: 'Mengirimkan evaluasi refleksi diri ke atasan Anda.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const form = document.getElementById('reflectionForm');
                const formData = new FormData(form);

                fetch('/employee/reflection/save', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil Dikirim!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => {
                            // Force a full page reload to refresh the sidebar navigation and unlock the menu items
                            window.location.href = '/employee/reflection?period=' + formData.get('period');
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal Mengirim',
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

    // Trigger compliance warning SweetAlert if ?warning=1 query param is set
    (function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('warning') === '1') {
            setTimeout(() => {
                Swal.fire({
                    title: 'Refleksi Kinerja Wajib Diisi!',
                    text: 'Anda wajib menyelesaikan pengisian Refleksi Kinerja & Rencana Karir (IDP) untuk kuartal ini terlebih dahulu sebelum dapat mengakses menu lainnya atau melihat draft slip gaji.',
                    icon: 'warning',
                    confirmButtonColor: '#000666',
                    confirmButtonText: 'Saya Mengerti'
                });
            }, 300);
        }
    })();

    // Expose functions to global SPA scope
    window.switchTab = switchTab;
    window.setCoreValuesRating = setCoreValuesRating;
    window.submitPersonalJournal = submitPersonalJournal;
    window.changeReflectionPeriod = changeReflectionPeriod;
    window.saveAsDraft = saveAsDraft;
    window.submitReflectionForm = submitReflectionForm;
</script>
</script>
