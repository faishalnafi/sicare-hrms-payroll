<?php
/**
 * Candidate Portal - Portal Katalog Lowongan Pekerjaan (/candidate/jobs)
 */
use App\Services\ViewHelper;

$search = trim($_GET['q'] ?? '');
$deptId = trim($_GET['dept'] ?? '');

$jobs = ViewHelper::getCandidateAvailableJobs($search, $deptId);
$departments = ViewHelper::getDepartments();
$user = ViewHelper::getUserProfile();
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl md:text-3xl font-extrabold text-primary tracking-tight">Katalog Lowongan Pekerjaan</h1>
            <p class="text-on-surface-variant font-medium text-sm">
                Temukan peluang karir terbaik yang sesuai dengan kompetensi dan pengalaman profesional Anda.
            </p>
        </div>
        <div class="flex items-center gap-2 bg-blue-50 text-blue-800 text-xs font-bold px-4 py-2.5 rounded-xl border border-blue-200">
            <span class="material-symbols-outlined text-base">work_history</span>
            <span>Tersedia: <?= count($jobs) ?> Posisi Lowongan</span>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="/candidate/jobs" class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/15 shadow-sm flex flex-col md:flex-row gap-3 items-center">
        <div class="relative flex-1 w-full">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari posisi pekerjaan, kata kunci, atau keahlian..." class="w-full pl-10 pr-4 py-2.5 bg-surface border border-outline-variant/30 rounded-xl text-xs focus:outline-none focus:border-primary">
        </div>

        <div class="w-full md:w-64">
            <select name="dept" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/30 rounded-xl text-xs focus:outline-none focus:border-primary">
                <option value="">-- Semua Departemen --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= htmlspecialchars($d['id']) ?>" <?= $deptId === $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                <span class="material-symbols-outlined text-base">search</span> Cari
            </button>
            <?php if (!empty($search) || !empty($deptId)): ?>
                <a href="/candidate/jobs" class="px-4 py-2.5 bg-surface hover:bg-surface-container text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Job Cards Grid -->
    <?php if (empty($jobs)): ?>
        <div class="bg-surface-container-lowest rounded-2xl p-12 text-center border border-outline-variant/15 shadow-sm space-y-3">
            <span class="material-symbols-outlined text-5xl text-outline-variant">search_off</span>
            <h3 class="text-lg font-bold text-on-surface font-headline">Tidak Ada Lowongan Ditemukan</h3>
            <p class="text-xs text-on-surface-variant max-w-md mx-auto">
                Coba sesuaikan kata kunci pencarian atau pilih kategori departemen yang lain.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($jobs as $j): ?>
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm hover:shadow-md hover:border-primary/30 transition-all flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-md border border-blue-200 inline-block mb-1.5">
                                    <?= htmlspecialchars($j['department_name'] ?? 'Umum') ?>
                                </span>
                                <h3 class="font-headline text-lg font-extrabold text-primary tracking-tight"><?= htmlspecialchars($j['job_title']) ?></h3>
                            </div>
                            <span class="px-2 py-1 bg-surface text-on-surface-variant text-[10px] font-bold rounded border border-outline-variant/20 uppercase">
                                <?= htmlspecialchars($j['employment_type'] ?? 'Full-time') ?>
                            </span>
                        </div>

                        <p class="text-xs text-on-surface-variant line-clamp-3 leading-relaxed">
                            <?= htmlspecialchars($j['description'] ?? $j['requirements'] ?? 'Kualifikasi pekerjaan sesuai deskripsi posisi.') ?>
                        </p>

                        <div class="flex flex-wrap items-center gap-4 text-[11px] text-on-surface-variant font-medium pt-1">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-primary">location_on</span> Jakarta / Hybrid
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-primary">group</span> Kebutuhan: <?= (int)($j['headcount_needed'] ?? 1) ?> Orang
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-primary">person_add</span> <?= (int)($j['total_applicants'] ?? 0) ?> Pelamar
                            </span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-outline-variant/15 flex items-center justify-between">
                        <div class="text-xs">
                            <span class="text-on-surface-variant text-[10px]">Perkiraan Gaji:</span>
                            <div class="font-bold text-emerald-700 font-mono">
                                <?= !empty($j['salary_range_min']) ? 'Rp ' . number_format($j['salary_range_min']/1000000, 1) . 'M - ' . number_format(($j['salary_range_max'] ?? $j['salary_range_min'])/1000000, 1) . 'M' : 'Kompetitif' ?>
                            </div>
                        </div>

                        <button type="button" onclick='openApplyModal(<?= json_encode($j, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-xs shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">send</span> Lamar Posisi Ini
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Quick Apply Lowongan -->
<div id="apply-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl shadow-2xl border border-outline-variant/20 overflow-hidden">
        <div class="p-5 border-b border-outline-variant/15 flex items-center justify-between bg-surface">
            <div>
                <h3 class="font-headline font-extrabold text-base text-primary" id="modal-job-title">Lamar Pekerjaan</h3>
                <p class="text-xs text-on-surface-variant" id="modal-dept-name">Departemen</p>
            </div>
            <button type="button" onclick="closeApplyModal()" class="w-8 h-8 rounded-full bg-surface-container hover:bg-outline-variant/20 flex items-center justify-center text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form id="apply-form" enctype="multipart/form-data" class="p-6 space-y-4 text-xs">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="requisition_id" id="form-req-id">

            <div>
                <label class="block font-bold text-on-surface mb-1">Nama Pelamar <span class="text-red-500">*</span></label>
                <input type="text" name="candidate_name" value="<?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>" required class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/30 rounded-xl focus:outline-none focus:border-primary">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-on-surface mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="candidate_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/30 rounded-xl focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block font-bold text-on-surface mb-1">Nomor WhatsApp <span class="text-red-500">*</span></label>
                    <input type="tel" name="candidate_phone" placeholder="08123456789" required class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/30 rounded-xl focus:outline-none focus:border-primary">
                </div>
            </div>

            <div>
                <label class="block font-bold text-on-surface mb-1">Tautan Portofolio / LinkedIn</label>
                <input type="url" name="candidate_portfolio" placeholder="https://linkedin.com/in/username" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/30 rounded-xl focus:outline-none focus:border-primary">
            </div>

            <div>
                <label class="block font-bold text-on-surface mb-1">Unggah Resume / CV (PDF, Max 10MB) <span class="text-red-500">*</span></label>
                <input type="file" name="resume_file" accept=".pdf" required class="block w-full text-xs text-on-surface-variant file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary/90">
            </div>

            <div>
                <label class="block font-bold text-on-surface mb-1">Surat Pengantar / Catatan Singkat</label>
                <textarea name="cover_letter" rows="2" placeholder="Ceritakan secara singkat alasan Anda tertarik dengan posisi ini..." class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/30 rounded-xl focus:outline-none focus:border-primary"></textarea>
            </div>

            <div class="pt-3 border-t border-outline-variant/15 flex items-center justify-end gap-3">
                <button type="button" onclick="closeApplyModal()" class="px-4 py-2 bg-surface hover:bg-surface-container text-on-surface font-bold rounded-xl border border-outline-variant/30">Batal</button>
                <button type="submit" id="btn-submit-apply" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-base">send</span> Kirim Lamaran
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyModal(job) {
    document.getElementById('form-req-id').value = job.id || '';
    document.getElementById('modal-job-title').textContent = `Lamar: ${job.job_title || 'Posisi'}`;
    document.getElementById('modal-dept-name').textContent = job.department_name || 'Departemen';
    document.getElementById('apply-modal').classList.remove('hidden');
}

function closeApplyModal() {
    document.getElementById('apply-modal').classList.add('hidden');
}

document.getElementById('apply-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.set('csrf_token', '<?= csrf_token() ?>');
    const btn = document.getElementById('btn-submit-apply');
    btn.disabled = true;
    btn.innerHTML = `<span class="material-symbols-outlined text-base animate-spin">sync</span> Mengirim...`;

    fetch('/candidate/apply', {
        method: 'POST',
        headers: {
            'X-JWT-Token': window.jwtToken || ''
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">send</span> Kirim Lamaran`;

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Lamaran Terkirim!',
                text: data.message || 'Lamaran Anda berhasil disimpan ke dalam sistem rekrutmen.',
                confirmButtonColor: '#000666'
            }).then(() => {
                closeApplyModal();
                window.location.href = '/candidate/dashboard';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mengirim',
                text: data.message,
                confirmButtonColor: '#000666'
            });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">send</span> Kirim Lamaran`;
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Terjadi kesalahan jaringan saat mengirimkan lamaran.',
            confirmButtonColor: '#000666'
        });
    });
});
</script>
