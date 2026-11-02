<?php
/**
 * Manager Portal - Review Kandidat & Rubrik Penilaian Wawancara (/manager/candidates)
 */
use App\Services\ViewHelper;

$reviews = ViewHelper::getManagerCandidateReviews();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl md:text-3xl font-extrabold text-primary tracking-tight">Review Kandidat & Scoring Rubric</h1>
            <p class="text-on-surface-variant font-medium text-sm">
                Evaluasi kualifikasi teknis pelamar, isi rubrik penilaian wawancara, dan tetapkan rekomendasi seleksi.
            </p>
        </div>
        <div class="flex items-center gap-2 bg-blue-50 text-blue-800 text-xs font-bold px-4 py-2 rounded-xl border border-blue-200">
            <span class="material-symbols-outlined text-base">rate_review</span>
            <span>Total Evaluasi: <?= count($reviews) ?> Kandidat</span>
        </div>
    </div>

    <!-- Main Candidates List -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-outline-variant/15 flex items-center justify-between">
            <h3 class="font-bold text-on-surface text-base flex items-center gap-2 font-headline">
                <span class="material-symbols-outlined text-primary text-xl">fact_check</span>
                Daftar Pelamar Tahap Wawancara Teknis
            </h3>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="p-12 text-center space-y-3">
                <span class="material-symbols-outlined text-5xl text-outline-variant">person_search</span>
                <h4 class="text-lg font-bold text-on-surface">Belum Ada Antrean Evaluasi</h4>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">
                    Saat ini belum ada jadwal wawancara kandidat yang ditugaskan kepada Anda oleh tim Recruiter.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-surface border-b border-outline-variant/15 text-on-surface-variant font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Kandidat</th>
                            <th class="px-6 py-4">Posisi & Divisi</th>
                            <th class="px-6 py-4">Jadwal Wawancara</th>
                            <th class="px-6 py-4">Skor & Evaluasi</th>
                            <th class="px-6 py-4">Keputusan</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 text-on-surface font-medium">
                        <?php foreach ($reviews as $r): ?>
                            <tr class="hover:bg-surface-container-lowest/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-sm text-primary"><?= htmlspecialchars($r['candidate_name'] ?? 'Kandidat') ?></div>
                                    <div class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($r['candidate_email'] ?? '') ?> • <?= htmlspecialchars($r['candidate_phone'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-on-surface"><?= htmlspecialchars($r['job_title'] ?? 'Lowongan') ?></div>
                                    <div class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($r['department_name'] ?? 'Divisi') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold"><?= date('d M Y', strtotime($r['interview_date'])) ?> • <?= htmlspecialchars($r['interview_time']) ?></div>
                                    <div class="text-[11px] text-on-surface-variant uppercase"><?= htmlspecialchars($r['interview_type'] ?? 'online') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($r['manager_score'])): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-bold rounded text-xs border border-blue-200">
                                                <?= (int)$r['manager_score'] ?> / 100
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant italic text-[11px]">Belum dinilai</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (($r['manager_decision'] ?? 'pending') === 'pass'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-full text-[10px] border border-emerald-200">
                                            <span class="material-symbols-outlined text-xs">check_circle</span> Lulus (Offering)
                                        </span>
                                    <?php elseif (($r['manager_decision'] ?? '') === 'fail'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 text-rose-700 font-bold rounded-full text-[10px] border border-rose-200">
                                            <span class="material-symbols-outlined text-xs">cancel</span> Ditolak
                                        </span>
                                    <?php elseif (($r['manager_decision'] ?? '') === 'hold'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 font-bold rounded-full text-[10px] border border-amber-200">
                                            <span class="material-symbols-outlined text-xs">pause_circle</span> Hold
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-full text-[10px] border border-slate-200">
                                            <span class="material-symbols-outlined text-xs">schedule</span> Menunggu Review
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" onclick='openRubricModal(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="px-3.5 py-2 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-xs shadow-sm transition-all inline-flex items-center gap-1.5 cursor-pointer">
                                        <span class="material-symbols-outlined text-sm">assignment_add</span> Rubrik Penilaian
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Scoring Rubric Wawancara -->
<div id="rubric-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-surface-container-lowest w-full max-w-3xl rounded-2xl shadow-2xl border border-outline-variant/20 max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Modal Header -->
        <div class="p-5 border-b border-outline-variant/15 flex items-center justify-between bg-surface">
            <div>
                <h3 class="font-headline font-extrabold text-lg text-primary" id="rubric-candidate-name">Rubrik Evaluasi Teknis Kandidat</h3>
                <p class="text-xs text-on-surface-variant" id="rubric-job-title">Posisi Wawancara</p>
            </div>
            <button type="button" onclick="closeRubricModal()" class="w-8 h-8 rounded-full bg-surface-container hover:bg-outline-variant/20 flex items-center justify-center text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form id="rubric-form" class="p-6 overflow-y-auto space-y-6 flex-1 text-xs">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="interview_id" id="rubric-interview-id">

            <!-- Profile Summary & Resume Link -->
            <div class="bg-surface p-4 rounded-xl border border-outline-variant/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                <div>
                    <div class="font-bold text-on-surface text-sm" id="rubric-cand-email">candidate@example.com</div>
                    <div class="text-on-surface-variant text-[11px]" id="rubric-cand-phone">08123456789</div>
                </div>
                <div class="flex items-center gap-2" id="rubric-links">
                    <!-- Links populated dynamically -->
                </div>
            </div>

            <!-- Scoring Criteria (4 Kategori Nilai 1 - 5) -->
            <div class="space-y-4">
                <h4 class="font-bold text-on-surface text-xs uppercase tracking-wider border-b border-outline-variant/15 pb-2 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-primary text-base">analytics</span> Kriteria Evaluasi Kompetensi (Skala 1 - 5)
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- 1. Technical Competency -->
                    <div class="p-3.5 bg-surface rounded-xl border border-outline-variant/20 space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="font-bold text-on-surface">1. Kompetensi Teknis <span class="text-red-500">*</span></label>
                            <span class="font-mono text-primary font-bold text-sm" id="val-tech">4/5</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant">Keahlian teknis, arsitektur kode, pemahaman stack.</p>
                        <input type="range" name="score_technical" id="score_technical" min="1" max="5" value="4" oninput="calcTotalScore()" class="w-full accent-primary cursor-pointer">
                    </div>

                    <!-- 2. Problem Solving -->
                    <div class="p-3.5 bg-surface rounded-xl border border-outline-variant/20 space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="font-bold text-on-surface">2. Pemecahan Masalah <span class="text-red-500">*</span></label>
                            <span class="font-mono text-primary font-bold text-sm" id="val-prob">4/5</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant">Daya analisis, logika algoritma, respon kendala.</p>
                        <input type="range" name="score_problem_solving" id="score_problem_solving" min="1" max="5" value="4" oninput="calcTotalScore()" class="w-full accent-primary cursor-pointer">
                    </div>

                    <!-- 3. Communication -->
                    <div class="p-3.5 bg-surface rounded-xl border border-outline-variant/20 space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="font-bold text-on-surface">3. Kemampuan Komunikasi <span class="text-red-500">*</span></label>
                            <span class="font-mono text-primary font-bold text-sm" id="val-comm">4/5</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant">Kejelasan penyampaian ide, kolaborasi tim, keaktifan.</p>
                        <input type="range" name="score_communication" id="score_communication" min="1" max="5" value="4" oninput="calcTotalScore()" class="w-full accent-primary cursor-pointer">
                    </div>

                    <!-- 4. Cultural Fit -->
                    <div class="p-3.5 bg-surface rounded-xl border border-outline-variant/20 space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="font-bold text-on-surface">4. Keselarasan Budaya <span class="text-red-500">*</span></label>
                            <span class="font-mono text-primary font-bold text-sm" id="val-cult">4/5</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant">Integritas, etos kerja, kesesuaian nilai perusahaan.</p>
                        <input type="range" name="score_cultural_fit" id="score_cultural_fit" min="1" max="5" value="4" oninput="calcTotalScore()" class="w-full accent-primary cursor-pointer">
                    </div>
                </div>

                <!-- Total Score Summary Banner -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-blue-950 text-xs">Kalkulasi Skor Keseluruhan</div>
                        <div class="text-[11px] text-blue-800">Dikonversi otomatis ke skala persentase 100%</div>
                    </div>
                    <div class="text-right">
                        <span class="font-extrabold text-2xl text-primary font-headline" id="total-score-display">80%</span>
                        <input type="hidden" name="score" id="form-total-score" value="80">
                    </div>
                </div>
            </div>

            <!-- Catatan Feedback & Rekomendasi Keputusan -->
            <div class="space-y-4">
                <div>
                    <label class="block font-bold text-on-surface mb-1.5">Catatan Umpan Balik Wawancara (Interviewer Feedback) <span class="text-red-500">*</span></label>
                    <textarea name="feedback" id="rubric-feedback" rows="3" required placeholder="Tuliskan catatan rinci kelebihan, kekurangan, dan poin teknis kandidat saat wawancara..." class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/30 rounded-xl text-xs focus:outline-none focus:border-primary"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-on-surface mb-2">Rekomendasi Keputusan Seleksi <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="p-3 bg-surface rounded-xl border border-outline-variant/30 flex items-center gap-2 cursor-pointer hover:border-emerald-500 transition-colors">
                            <input type="radio" name="decision" value="pass" required class="accent-emerald-600">
                            <div>
                                <div class="font-bold text-emerald-800">Lulus (Pass)</div>
                                <div class="text-[10px] text-on-surface-variant">Lanjut ke Offering</div>
                            </div>
                        </label>

                        <label class="p-3 bg-surface rounded-xl border border-outline-variant/30 flex items-center gap-2 cursor-pointer hover:border-amber-500 transition-colors">
                            <input type="radio" name="decision" value="hold" required class="accent-amber-600">
                            <div>
                                <div class="font-bold text-amber-800">Pertimbangkan (Hold)</div>
                                <div class="text-[10px] text-on-surface-variant">Simpan Cadangan</div>
                            </div>
                        </label>

                        <label class="p-3 bg-surface rounded-xl border border-outline-variant/30 flex items-center gap-2 cursor-pointer hover:border-rose-500 transition-colors">
                            <input type="radio" name="decision" value="fail" required class="accent-rose-600">
                            <div>
                                <div class="font-bold text-rose-800">Tolak (Fail)</div>
                                <div class="text-[10px] text-on-surface-variant">Tidak Lulus</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
                <button type="button" onclick="closeRubricModal()" class="px-4 py-2.5 bg-surface hover:bg-surface-container text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30">Batal</button>
                <button type="submit" id="btn-save-rubric" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-base">save</span> Simpan Evaluasi Rubrik
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function calcTotalScore() {
    const tech = parseInt(document.getElementById('score_technical').value) || 3;
    const prob = parseInt(document.getElementById('score_problem_solving').value) || 3;
    const comm = parseInt(document.getElementById('score_communication').value) || 3;
    const cult = parseInt(document.getElementById('score_cultural_fit').value) || 3;

    document.getElementById('val-tech').textContent = `${tech}/5`;
    document.getElementById('val-prob').textContent = `${prob}/5`;
    document.getElementById('val-comm').textContent = `${comm}/5`;
    document.getElementById('val-cult').textContent = `${cult}/5`;

    const avg = (tech + prob + comm + cult) / 4;
    const pct = Math.round(avg * 20);

    document.getElementById('total-score-display').textContent = `${pct}%`;
    document.getElementById('form-total-score').value = pct;
}

function openRubricModal(r) {
    document.getElementById('rubric-interview-id').value = r.id || '';
    document.getElementById('rubric-candidate-name').textContent = r.candidate_name || 'Evaluasi Kandidat';
    document.getElementById('rubric-job-title').textContent = `${r.job_title || 'Lowongan'} • ${r.department_name || 'Divisi'}`;

    document.getElementById('rubric-cand-email').textContent = r.candidate_email || '';
    document.getElementById('rubric-cand-phone').textContent = r.candidate_phone || '';

    const linksContainer = document.getElementById('rubric-links');
    linksContainer.innerHTML = '';
    if (r.resume_path) {
        linksContainer.innerHTML += `<a href="/document/view?file=${encodeURIComponent(r.resume_path)}" target="_blank" class="px-3 py-1.5 bg-primary/10 text-primary font-bold rounded-lg text-xs hover:bg-primary/20 transition-colors">CV / Resume</a>`;
    }
    if (r.portfolio_url) {
        linksContainer.innerHTML += `<a href="${r.portfolio_url}" target="_blank" class="px-3 py-1.5 bg-surface-container text-on-surface font-bold rounded-lg text-xs hover:bg-outline-variant/20 transition-colors">Portofolio</a>`;
    }

    // Set scores if already evaluated
    document.getElementById('score_technical').value = r.score_technical || 4;
    document.getElementById('score_problem_solving').value = r.score_problem_solving || 4;
    document.getElementById('score_communication').value = r.score_communication || 4;
    document.getElementById('score_cultural_fit').value = r.score_cultural_fit || 4;
    calcTotalScore();

    document.getElementById('rubric-feedback').value = r.manager_feedback || '';

    // Set decision radio
    const decision = r.manager_decision || 'pass';
    const radio = document.querySelector(`input[name="decision"][value="${decision}"]`);
    if (radio) radio.checked = true;

    document.getElementById('rubric-modal').classList.remove('hidden');
}

function closeRubricModal() {
    document.getElementById('rubric-modal').classList.add('hidden');
}

document.getElementById('rubric-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const btn = document.getElementById('btn-save-rubric');
    btn.disabled = true;
    btn.innerHTML = `<span class="material-symbols-outlined text-base animate-spin">sync</span> Menyimpan...`;

    fetch('/manager/interviews/evaluate', {
        method: 'POST',
        headers: {
            'X-JWT-Token': window.jwtToken || ''
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">save</span> Simpan Evaluasi Rubrik`;

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Evaluasi Disimpan!',
                text: data.message,
                confirmButtonColor: '#000666'
            }).then(() => {
                closeRubricModal();
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: data.message,
                confirmButtonColor: '#000666'
            });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">save</span> Simpan Evaluasi Rubrik`;
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Terjadi gangguan jaringan saat mengirimkan evaluasi.',
            confirmButtonColor: '#000666'
        });
    });
});
</script>