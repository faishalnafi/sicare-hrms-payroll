<?php
use App\Services\ViewHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['q'] ?? '';

$jobOpenings = ViewHelper::getRecruiterJobOpenings($statusFilter, $searchQuery);
$companyName = ViewHelper::getSetting('app_company_name', 'PT SI CARE ENTERPRISE');

// Count summary metrics
$publishedCount = 0;
$closedCount = 0;
$approvedCount = 0;
$totalApplicants = 0;

foreach ($jobOpenings as $job) {
    if ($job['status'] === 'published') $publishedCount++;
    if ($job['status'] === 'closed') $closedCount++;
    if ($job['status'] === 'approved') $approvedCount++;
    $totalApplicants += (int)($job['total_applicants'] ?? 0);
}
?>

<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-outline-variant/30 pb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 bg-primary/10 text-primary rounded-xl material-symbols-outlined text-2xl">work_outline</span>
                <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Manajemen Lowongan Pekerjaan</h1>
            </div>
            <p class="text-on-surface-variant font-medium text-sm mt-1">
                Kelola status lowongan kerja publik, pantau jumlah pelamar real-time, dan kendalikan publikasi di Portal Karir <?= htmlspecialchars($companyName) ?>.
            </p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <a href="/careers" target="_blank" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-surface-container-highest hover:bg-outline-variant/30 text-primary font-bold text-sm rounded-xl transition border border-outline-variant/50">
                <span class="material-symbols-outlined text-lg">open_in_new</span>
                <span>Lihat Portal Karir Publik</span>
            </a>
            <a href="/recruiter/ats" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold text-sm rounded-xl transition shadow-sm">
                <span class="material-symbols-outlined text-lg">view_kanban</span>
                <span>Buka Pipeline ATS</span>
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-emerald-200/60 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block mb-1">Lowongan Aktif</span>
                <span class="text-3xl font-black text-emerald-700"><?= $publishedCount ?></span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5">Tayang di Portal Karir</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">public</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-blue-200/60 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider block mb-1">Total Pelamar Masuk</span>
                <span class="text-3xl font-black text-blue-700"><?= $totalApplicants ?></span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5">Semua posisi aktif</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">group</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-amber-800 uppercase tracking-wider block mb-1">Siap Dipublikasi</span>
                <span class="text-3xl font-black text-amber-700"><?= $approvedCount ?></span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5">Sudah disetujui C-Level</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">verified</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider block mb-1">Lowongan Ditutup</span>
                <span class="text-3xl font-black text-gray-800"><?= $closedCount ?></span>
                <span class="text-[11px] text-on-surface-variant block mt-0.5">Arsip rekrutmen selesai</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gray-50 text-gray-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">lock_clock</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white p-4 rounded-2xl border border-outline-variant/30 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <!-- Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
            <a href="?status=all<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $statusFilter === 'all' ? 'bg-primary text-white shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
                Semua Status (<?= count($jobOpenings) ?>)
            </a>
            <a href="?status=published<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $statusFilter === 'published' ? 'bg-emerald-600 text-white shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
                Aktif / Published (<?= $publishedCount ?>)
            </a>
            <a href="?status=approved<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $statusFilter === 'approved' ? 'bg-amber-600 text-white shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
                Siap Terbit (<?= $approvedCount ?>)
            </a>
            <a href="?status=closed<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $statusFilter === 'closed' ? 'bg-gray-700 text-white shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
                Ditutup (<?= $closedCount ?>)
            </a>
        </div>

        <!-- Search Input -->
        <form method="GET" class="relative w-full md:w-72">
            <?php if (!empty($statusFilter) && $statusFilter !== 'all'): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Cari posisi atau divisi..." class="w-full pl-9 pr-4 py-2 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-primary/20">
            <span class="material-symbols-outlined absolute left-2.5 top-2.5 text-on-surface-variant text-base select-none">search</span>
        </form>
    </div>

    <!-- Job Cards List -->
    <?php if (empty($jobOpenings)): ?>
        <div class="bg-white rounded-2xl border border-outline-variant/30 p-12 text-center space-y-3">
            <span class="material-symbols-outlined text-5xl text-outline-variant">find_in_page</span>
            <h3 class="text-base font-bold text-on-surface">Tidak ada lowongan pekerjaan ditemukan</h3>
            <p class="text-xs text-on-surface-variant max-w-md mx-auto">
                Silakan sesuaikan kata kunci pencarian atau tinjau menu FPTK Requisitions untuk memproses persetujuan pembukaan lowongan baru.
            </p>
            <a href="/recruiter/requisitions" class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary font-bold text-xs rounded-xl hover:bg-primary/20 transition">
                <span class="material-symbols-outlined text-sm">assignment</span>
                <span>Tinjau FPTK Requisitions</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <?php foreach ($jobOpenings as $job): ?>
                <?php
                $isPublished = ($job['status'] === 'published');
                $isClosed = ($job['status'] === 'closed');
                $isApproved = ($job['status'] === 'approved');
                
                $statusBadgeClass = 'bg-gray-100 text-gray-700 border-gray-200';
                $statusLabel = 'Tertutup / Arsip';
                if ($isPublished) {
                    $statusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200/60';
                    $statusLabel = 'Aktif di Portal Karir';
                } elseif ($isApproved) {
                    $statusBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200/60';
                    $statusLabel = 'Siap Dipublikasi';
                }
                
                $empTypeMap = [
                    'full_time' => 'Purna Waktu (Full-Time)',
                    'part_time' => 'Paruh Waktu (Part-Time)',
                    'contract' => 'Kontrak (PKWT)',
                    'remote' => 'Remote / Jarak Jauh',
                    'internship' => 'Magang (Internship)'
                ];
                $empTypeStr = $empTypeMap[$job['employment_type'] ?? 'full_time'] ?? ucwords(str_replace('_', ' ', $job['employment_type']));
                
                $salaryRange = 'Kompetitif';
                if (!empty($job['min_salary']) || !empty($job['max_salary'])) {
                    $salaryRange = 'Rp ' . number_format($job['min_salary'], 0, ',', '.') . ' - Rp ' . number_format($job['max_salary'], 0, ',', '.');
                }
                ?>
                <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between space-y-5">
                    <!-- Top Info -->
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase border <?= $statusBadgeClass ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $isPublished ? 'bg-emerald-500 animate-pulse' : ($isApproved ? 'bg-amber-500' : 'bg-gray-400') ?>"></span>
                                    <?= $statusLabel ?>
                                </span>
                                <span class="text-[11px] font-mono text-on-surface-variant/70 ml-2">#<?= htmlspecialchars($job['req_number']) ?></span>
                            </div>
                            <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-highest px-2.5 py-1 rounded-lg">
                                Kuota: <strong class="text-primary font-bold"><?= (int)$job['headcount'] ?> Staf</strong>
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-on-surface tracking-tight mb-1">
                            <?= htmlspecialchars($job['title']) ?>
                        </h2>
                        
                        <div class="flex flex-wrap items-center gap-y-1 gap-x-3 text-xs text-on-surface-variant mb-4">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-primary">domain</span>
                                <?= htmlspecialchars($job['department_name'] ?? 'Departemen') ?>
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-amber-600">schedule</span>
                                <?= $empTypeStr ?>
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1 font-semibold text-emerald-700">
                                <span class="material-symbols-outlined text-sm">payments</span>
                                <?= $salaryRange ?>
                            </span>
                        </div>

                        <!-- Funnel Stats Box -->
                        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-3 grid grid-cols-4 gap-2 text-center text-xs">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-on-surface-variant/70 block">Total Pelamar</span>
                                <span class="text-sm font-black text-primary"><?= (int)($job['total_applicants'] ?? 0) ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-on-surface-variant/70 block">Screening</span>
                                <span class="text-sm font-black text-amber-600"><?= (int)($job['screening_count'] ?? 0) ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-on-surface-variant/70 block">Wawancara</span>
                                <span class="text-sm font-black text-blue-600"><?= (int)($job['interview_count'] ?? 0) ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-on-surface-variant/70 block">Hired</span>
                                <span class="text-sm font-black text-emerald-600"><?= (int)($job['hired_count'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Buttons -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-outline-variant/20 pt-4">
                        <div class="flex items-center gap-1.5">
                            <button onclick="copyCareerLink('<?= $job['id'] ?>')" class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary/5 rounded-xl border border-outline-variant/40 transition text-xs font-semibold flex items-center gap-1" title="Salin tautan lowongan ini">
                                <span class="material-symbols-outlined text-sm">content_copy</span>
                                <span>Salin Tautan</span>
                            </button>
                            <a href="/recruiter/ats?job_id=<?= $job['id'] ?>" class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary/5 rounded-xl border border-outline-variant/40 transition text-xs font-semibold flex items-center gap-1" title="Buka pelamar di kanban ATS">
                                <span class="material-symbols-outlined text-sm">view_kanban</span>
                                <span>Lihat Pelamar</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <?php if ($isPublished): ?>
                                <button onclick="unpublishJob('<?= $job['id'] ?>', '<?= htmlspecialchars(addslashes($job['title'])) ?>')" class="px-3.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs rounded-xl border border-red-200 transition flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">pause_circle</span>
                                    <span>Tutup Lowongan</span>
                                </button>
                            <?php else: ?>
                                <button onclick="openPublishModal('<?= $job['id'] ?>', '<?= htmlspecialchars(addslashes($job['title'])) ?>', '<?= htmlspecialchars(addslashes($job['job_description'] ?? '')) ?>')" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">publish</span>
                                    <span><?= $isClosed ? 'Buka Ulang Lowongan' : 'Terbitkan Sekarang' ?></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Publikasi Lowongan -->
<div id="publishModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-outline-variant/30">
        <div class="flex justify-between items-center border-b border-outline-variant/30 pb-3">
            <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">rocket_launch</span>
                <span>Publikasikan Lowongan ke Portal Karir</span>
            </h3>
            <button onclick="closePublishModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="publishForm" onsubmit="submitPublishJob(event)" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="requisition_id" id="modal-req-id" value="">

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Judul Tampilan Publik</label>
                <input type="text" name="title" id="modal-title" required class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Deskripsi Singkat untuk Kandidat</label>
                <textarea name="job_description" id="modal-desc" rows="4" class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-normal focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ringkasan tugas utama dan keunggulan posisi..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Ikon Material</label>
                    <input type="text" name="icon" value="badge" class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Contoh: code, work, campaign">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Tema Warna Kartu</label>
                    <select name="card_color" class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="emerald">Hijau (Emerald)</option>
                        <option value="blue">Biru (Corporate)</option>
                        <option value="purple">Ungu (Creative)</option>
                        <option value="amber">Amber (Technical)</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-outline-variant/20">
                <button type="button" onclick="closePublishModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">publish</span>
                    <span>Tayangkan ke Publik</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function copyCareerLink(jobId) {
        const url = window.location.origin + '/careers#job-' + jobId;
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire({
                title: 'Tautan Tersalin!',
                text: 'Tautan lowongan telah disalin ke papan klip: ' + url,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(() => {
            prompt('Salin tautan lowongan ini:', url);
        });
    }

    function openPublishModal(reqId, title, desc) {
        document.getElementById('modal-req-id').value = reqId;
        document.getElementById('modal-title').value = title;
        document.getElementById('modal-desc').value = desc;
        document.getElementById('publishModal').classList.remove('hidden');
    }

    function closePublishModal() {
        document.getElementById('publishModal').classList.add('hidden');
    }

    function submitPublishJob(e) {
        e.preventDefault();
        const form = document.getElementById('publishForm');
        const formData = new FormData(form);
        formData.set('csrf_token', '<?= csrf_token() ?>');

        Swal.showLoading();
        fetch('/recruiter/requisitions/publish', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil Diterbitkan!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                }).then(() => window.location.reload());
            } else {
                Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
            }
        })
        .catch(err => {
            Swal.fire({ title: 'Kesalahan Sistem', text: err.message, icon: 'error' });
        });
    }

    function unpublishJob(reqId, title) {
        Swal.fire({
            title: 'Tutup Lowongan Ini?',
            text: `Lowongan "${title}" tidak akan lagi menerima lamaran baru dari portal karir publik.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d32f2f',
            cancelButtonColor: '#757575',
            confirmButtonText: 'Ya, Tutup Lowongan',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                Swal.showLoading();
                const formData = new FormData();
                formData.append('csrf_token', '<?= csrf_token() ?>');
                formData.append('requisition_id', reqId);

                fetch('/recruiter/requisitions/unpublish', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Lowongan Ditutup!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire({ title: 'Gagal!', text: data.message, icon: 'error', confirmButtonColor: '#000666' });
                    }
                })
                .catch(err => {
                    Swal.fire({ title: 'Kesalahan Sistem', text: err.message, icon: 'error' });
                });
            }
        });
    }
</script>
