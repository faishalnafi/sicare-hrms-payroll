<?php
/**
 * Candidate Portal - Dashboard Lowongan Pekerjaan (/candidate/dashboard & /candidate/jobs)
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$db = \App\Config\Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['name'] ?? 'Kandidat';
$userEmail = $_SESSION['email'] ?? '';

// Fetch all published jobs
$publishedJobs = [];
try {
    $stmt = $db->query("
        SELECT r.*, d.name AS dept_name,
               (SELECT COUNT(*) FROM job_applications ja WHERE ja.requisition_id = r.id AND ja.status = 'hired') AS hired_count
        FROM job_requisitions r
        LEFT JOIN departments d ON r.department_id = d.id
        WHERE r.status = 'published'
          AND (SELECT COUNT(*) FROM job_applications ja WHERE ja.requisition_id = r.id AND ja.status = 'hired') < r.headcount
        ORDER BY r.updated_at DESC, r.created_at DESC
    ");
    $publishedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $publishedJobs = [];
}

// Fetch candidate's submitted applications & candidate profile
$myApplications = [];
$appliedReqIds = [];
$candProfile = null;
if ($userId) {
    try {
        $stmtApp = $db->prepare("SELECT * FROM job_applications WHERE candidate_id = :uid OR candidate_email = :email");
        $stmtApp->execute(['uid' => $userId, 'email' => $userEmail]);
        $myApplications = $stmtApp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($myApplications as $app) {
            $appliedReqIds[] = $app['requisition_id'];
        }
        
        $stmtUser = $db->prepare("SELECT * FROM users WHERE id = :uid");
        $stmtUser->execute(['uid' => $userId]);
        $candProfile = $stmtUser->fetch(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $myApplications = [];
        $appliedReqIds = [];
        $candProfile = null;
    }
}
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#000666] to-[#0c145e] text-white p-6 md:p-8 rounded-3xl shadow-xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="relative z-10 space-y-2 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold text-blue-200 border border-white/10 mb-1">
                <span class="material-symbols-outlined text-xs text-amber-400">star</span> Dashboard Rekrutmen Anggota Ekstrakurikuler
            </div>
            <h1 class="text-2xl md:text-3xl font-headline font-extrabold text-white">Selamat Datang, <?= htmlspecialchars($userName) ?>!</h1>
            <p class="text-blue-100 text-xs md:text-sm leading-relaxed">
                Pilih Divisi Operasional yang diminati di bawah ini untuk mendaftar sebagai Calon Anggota Baru.
            </p>
            
            <!-- Candidate Information Pills -->
            <div class="pt-2 flex flex-wrap items-center gap-2 text-xs">
                <?php if (!empty($candProfile['rombel'])): ?>
                    <span class="px-3 py-1 rounded-lg bg-white/15 backdrop-blur-md text-white font-bold flex items-center gap-1.5 border border-white/20">
                        <span class="material-symbols-outlined text-xs text-amber-300">school</span> Rombel: <?= htmlspecialchars($candProfile['rombel']) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($candProfile['angkatan_masuk'])): ?>
                    <span class="px-3 py-1 rounded-lg bg-white/15 backdrop-blur-md text-white font-bold flex items-center gap-1.5 border border-white/20">
                        <span class="material-symbols-outlined text-xs text-blue-300">calendar_month</span> <?= htmlspecialchars($candProfile['angkatan_masuk']) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($candProfile['bulan_bergabung'])): ?>
                    <span class="px-3 py-1 rounded-lg bg-white/15 backdrop-blur-md text-white font-bold flex items-center gap-1.5 border border-white/20">
                        <span class="material-symbols-outlined text-xs text-emerald-300">event</span> Bergabung: <?= htmlspecialchars($candProfile['bulan_bergabung']) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($candProfile['no_telepon'])): ?>
                    <span class="px-3 py-1 rounded-lg bg-white/15 backdrop-blur-md text-white font-bold flex items-center gap-1.5 border border-white/20">
                        <span class="material-symbols-outlined text-xs text-green-400">chat</span> WA: <?= htmlspecialchars($candProfile['no_telepon']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="relative z-10 flex items-center gap-3 w-full md:w-auto shrink-0">
            <div class="bg-white/10 backdrop-blur-md border border-white/15 p-3.5 rounded-2xl text-center min-w-[120px]">
                <span class="block text-2xl font-extrabold text-white"><?= count($publishedJobs) ?></span>
                <span class="text-[10px] font-bold text-blue-200 uppercase tracking-wider">Divisi Pilihan</span>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/15 p-3.5 rounded-2xl text-center min-w-[120px]">
                <span class="block text-2xl font-extrabold text-amber-300"><?= count($myApplications) ?></span>
                <span class="text-[10px] font-bold text-blue-200 uppercase tracking-wider">Pendaftaran Saya</span>
            </div>
        </div>
    </div>

    <!-- Status Tracking Section if Candidate Has Submitted Applications -->
    <?php if (!empty($myApplications)): ?>
        <div class="bg-surface-container-lowest border border-outline-variant/15 p-6 rounded-3xl shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-headline font-bold text-[#000666] flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-500">assignment_turned_in</span> Status Pendaftaran Divisi Anda
                </h3>
                <span class="text-xs font-semibold text-on-surface-variant"><?= count($myApplications) ?> Pendaftaran</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($myApplications as $app): ?>
                    <?php
                        $statusRaw = strtolower($app['status'] ?? 'submitted');
                        $statusBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                        $statusLabel = 'Seleksi Berkas';
                        $statusIcon = 'folder_open';

                        if (in_array($statusRaw, ['interview', 'interview_scheduled'])) {
                            $statusBadgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                            $statusLabel = 'Wawancara Divisi';
                            $statusIcon = 'record_voice_over';
                        } elseif (in_array($statusRaw, ['hired', 'offered', 'accepted'])) {
                            $statusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            $statusLabel = 'Diterima - Lolos Seleksi';
                            $statusIcon = 'check_circle';
                        } elseif ($statusRaw === 'rejected') {
                            $statusBadgeClass = 'bg-red-50 text-red-700 border-red-200';
                            $statusLabel = 'Belum Lolos Seleksi';
                            $statusIcon = 'cancel';
                        }

                        // Fetch job details
                        $reqStmt = $db->prepare("SELECT title FROM job_requisitions WHERE id = :rid");
                        $reqStmt->execute(['rid' => $app['requisition_id']]);
                        $reqJob = $reqStmt->fetch(PDO::FETCH_ASSOC);
                        $jobTitle = $reqJob['title'] ?? 'Pendaftaran Divisi';
                    ?>
                    <div class="p-4 rounded-2xl border border-outline-variant/15 bg-surface-container-low flex flex-col justify-between gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold border <?= $statusBadgeClass ?> flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm"><?= $statusIcon ?></span> <?= $statusLabel ?>
                                </span>
                                <span class="text-[10px] text-on-surface-variant/70 font-medium"><?= date('d M Y', strtotime($app['created_at'] ?? 'now')) ?></span>
                            </div>
                            <h4 class="text-sm font-bold text-[#0c145e]"><?= htmlspecialchars($jobTitle) ?></h4>
                            <p class="text-xs text-on-surface-variant mt-1">Nama Pendaftar: <b><?= htmlspecialchars($app['candidate_name'] ?? $userName) ?></b></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Bar & Filters -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 p-4 rounded-2xl shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-lg">search</span>
            <input type="text" id="candJobSearch" placeholder="Cari divisi operasional..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant/30 bg-surface-container-low focus:outline-none focus:border-primary text-xs font-semibold text-on-surface" />
        </div>
        <div class="text-xs text-on-surface-variant font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-sm text-primary">groups</span>
            <span>Menampilkan <b><?= count($publishedJobs) ?></b> Divisi Operasional Terbuka</span>
        </div>
    </div>

    <!-- Jobs Grid -->
    <div id="candJobGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php if (empty($publishedJobs)): ?>
            <div class="col-span-full bg-surface-container-lowest p-12 rounded-3xl border border-outline-variant/15 text-center space-y-3 shadow-2xs">
                <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">work_off</span>
                </div>
                <h3 class="text-base font-headline font-bold text-on-surface">Belum Ada Lowongan Aktif</h3>
                <p class="text-xs text-on-surface-variant max-w-md mx-auto">Saat ini belum ada lowongan pekerjaan baru yang dipublikasikan. Silakan cek kembali secara berkala.</p>
            </div>
        <?php else: ?>
            <?php foreach ($publishedJobs as $job): ?>
                <?php
                    $reqId = $job['id'];
                    $hasApplied = in_array($reqId, $appliedReqIds);

                    $colorVal = $job['card_color'] ?? '#3b82f6';
                    $legacyPresetMap = [
                        'blue' => '#3b82f6',
                        'emerald' => '#059669',
                        'amber' => '#d97706',
                        'purple' => '#7c3aed',
                        'rose' => '#e11d48',
                        'indigo' => '#4f46e5'
                    ];
                    if (isset($legacyPresetMap[$colorVal])) {
                        $colorVal = $legacyPresetMap[$colorVal];
                    }
                    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorVal)) {
                        $colorVal = '#3b82f6';
                    }

                    $empTypeText = 'Full Time';
                    if (($job['employment_type'] ?? '') === 'contract') $empTypeText = 'Kontrak';
                    elseif (($job['employment_type'] ?? '') === 'internship') $empTypeText = 'Magang';
                    elseif (($job['employment_type'] ?? '') === 'part_time') $empTypeText = 'Part Time';
                ?>
                <div class="cand-card-item bg-white p-6 rounded-3xl border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all flex flex-col h-full" data-title="<?= htmlspecialchars(strtolower($job['title'] ?? '')) ?>" data-desc="<?= htmlspecialchars(strtolower($job['job_description'] ?? '')) ?>">
                    <?php if (!empty($job['logo_url'])): ?>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border p-1 bg-white shrink-0 overflow-hidden" style="border-color: <?= $colorVal ?>30;">
                            <img src="<?= htmlspecialchars($job['logo_url']) ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                        </div>
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border shrink-0" style="background-color: <?= $colorVal ?>15; border-color: <?= $colorVal ?>30; color: <?= $colorVal ?>;">
                            <span class="material-symbols-outlined text-[1.4rem]"><?= htmlspecialchars(!empty($job['icon']) ? $job['icon'] : 'work') ?></span>
                        </div>
                    <?php endif; ?>

                    <h3 class="text-base font-headline font-bold text-[#0c145e] mb-2"><?= htmlspecialchars($job['title'] ?? $job['job_title'] ?? 'Posisi Pekerjaan') ?></h3>
                    
                    <p class="text-[0.8rem] text-gray-500 leading-relaxed mb-6 flex-grow line-clamp-3">
                        <?= htmlspecialchars(($job['job_description'] ?? '') !== '' ? $job['job_description'] : 'Mencari kandidat berbakat untuk bergabung dengan tim kami.') ?>
                    </p>

                    <div class="pt-4 border-t border-gray-50 mt-auto">
                        <?php if ($hasApplied): ?>
                            <span class="text-emerald-600 text-xs font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[1rem]">check_circle</span> Lamaran Terkirim
                            </span>
                        <?php else: ?>
                            <button type="button" onclick="openFptkDetailModal('<?= $job['id'] ?>')" class="text-[#0c145e] text-xs font-bold flex items-center gap-1 hover:text-blue-700 transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[1rem]">open_in_new</span> Lamar Posisi Ini
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail FPTK (Elegant Typographic Layout) -->
<div id="fptkDetailModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-2xl mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="fptkDetailModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-2xl">assignment_ind</span>
                <div>
                    <h3 class="text-sm font-extrabold text-on-surface font-headline">Detail Permintaan Tenaga Kerja (FPTK)</h3>
                    <p class="text-[10px] text-on-surface-variant font-medium mt-0.5">Informasi spesifikasi dan deskripsi lowongan pekerjaan secara detail.</p>
                </div>
            </div>
            <button type="button" onclick="closeFptkDetailModal()" class="p-1.5 rounded-full text-on-surface-variant hover:bg-surface-container-high transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>

        <!-- Modal Body (Elegant Typographic Layout) -->
        <div class="p-6 space-y-5 text-xs text-on-surface overflow-y-auto flex-grow">
            <div>
                <h4 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-widest">NAMA POSISI / JABATAN YANG DIBUTUHKAN</h4>
                <div id="fptk_title_val" class="text-base font-extrabold text-[#0c145e] mt-1"></div>
            </div>

            <!-- Meta Data Info Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-surface-container-low border border-outline-variant/15">
                <div>
                    <h5 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider">DEPARTEMEN</h5>
                    <p id="fptk_dept_val" class="text-xs font-bold text-[#0c145e] mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-primary">domain</span>
                        <span id="fptk_dept_text"></span>
                    </p>
                </div>
                <div>
                    <h5 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider">TIPE KEPEGAWAIAN</h5>
                    <p id="fptk_employment_type_val" class="text-xs font-bold text-[#0c145e] mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-primary">badge</span>
                        <span id="fptk_employment_type_text"></span>
                    </p>
                </div>
                <div>
                    <h5 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider">JUMLAH KEBUTUHAN</h5>
                    <p id="fptk_headcount_val" class="text-xs font-bold text-[#0c145e] mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-primary">groups</span>
                        <span id="fptk_headcount_text"></span> Orang
                    </p>
                </div>
                <div>
                    <h5 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider">KISARAN GAJI (RP)</h5>
                    <p id="fptk_salary_val" class="text-xs font-bold text-primary mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-primary">payments</span>
                        <span id="fptk_salary_text"></span>
                    </p>
                </div>
            </div>

            <div class="space-y-1.5">
                <h4 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-widest">ALASAN & JUSTIFIKASI PENGAJUAN</h4>
                <div id="fptk_reason_val" class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/10 text-on-surface-variant leading-relaxed font-medium"></div>
            </div>

            <div class="space-y-1.5">
                <h4 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-widest">DESKRIPSI PEKERJAAN</h4>
                <div id="fptk_job_desc_val" class="p-4 bg-surface border border-outline-variant/15 rounded-xl text-on-surface leading-relaxed whitespace-pre-wrap font-medium"></div>
            </div>

            <div class="space-y-1.5">
                <h4 class="text-[9px] font-bold text-on-surface-variant uppercase tracking-widest">KUALIFIKASI & PERSYARATAN</h4>
                <div id="fptk_requirements_val" class="p-4 bg-surface border border-outline-variant/15 rounded-xl text-on-surface leading-relaxed whitespace-pre-wrap font-medium"></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 bg-surface border-t border-outline-variant/15 flex items-center justify-end gap-2 shrink-0">
            <button type="button" onclick="closeFptkDetailModal()" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-container-high transition-all text-xs font-bold cursor-pointer">
                Batal
            </button>
            <button type="button" onclick="proceedToApply()" class="px-5 py-2.5 bg-[#000666] hover:bg-[#0c145e] text-white rounded-xl font-bold text-xs shadow-md hover:shadow-lg transition-all flex items-center gap-1.5 cursor-pointer">
                Lanjutkan Melamar <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Lamar Pekerjaan (Gambar 2) -->
<div id="applyModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="applyModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <div>
                <h3 id="applyModalTitle" class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">send</span> Form Lamaran Pekerjaan
                </h3>
                <p id="applyModalSub" class="text-[10px] text-on-surface-variant font-medium mt-0.5"></p>
            </div>
            <button type="button" onclick="closeApplyModal()" class="p-1.5 rounded-full text-on-surface-variant hover:bg-surface-container-high transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form id="applyForm" onsubmit="submitApplication(event)" class="p-6 space-y-4 text-xs font-semibold text-on-surface overflow-y-auto flex-grow">
            <input type="hidden" name="requisition_id" id="apply_req_id" value="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="space-y-1">
                <label class="block text-on-surface-variant">Nama Lengkap Kandidat <span class="text-red-600">*</span></label>
                <input type="text" name="candidate_name" id="apply_name" required value="<?= htmlspecialchars($candProfile ? ($candProfile['first_name'] . ' ' . ($candProfile['last_name'] ?? '')) : $userName) ?>" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs font-bold text-on-surface" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Email Kontak <span class="text-red-600">*</span></label>
                    <input type="email" name="candidate_email" id="apply_email" required value="<?= htmlspecialchars($candProfile['email'] ?? $userEmail) ?>" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface" />
                </div>
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Nomor Telepon / WhatsApp <span class="text-red-600">*</span></label>
                    <input type="text" name="candidate_phone" id="apply_phone" required value="<?= htmlspecialchars($candProfile['no_telepon'] ?? '') ?>" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface" />
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-on-surface-variant">Tautan Portofolio / LinkedIn <span class="text-neutral-400 font-medium">(opsional)</span></label>
                <input type="url" name="candidate_portfolio" id="apply_portfolio" value="<?= htmlspecialchars($candProfile['tautan_profesional'] ?? '') ?>" placeholder="https://linkedin.com/in/..." class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface" />
            </div>

            <div class="space-y-1">
                <label class="block text-on-surface-variant">Unggah CV / Resume <span class="text-red-600">*</span> <span class="text-neutral-400 font-medium">(PDF/DOCX, maks 5MB)</span></label>
                <div id="resume_dropzone" class="relative border border-dashed border-outline-variant/30 hover:border-primary rounded-xl p-3 bg-surface-container-low flex flex-col items-center justify-center text-center cursor-pointer transition-all" onclick="document.getElementById('apply_resume_file').click()">
                    <input type="file" name="resume_file" id="apply_resume_file" <?= empty($candProfile['resume_file']) ? 'required' : '' ?> accept=".pdf,.doc,.docx" class="hidden" onchange="handleResumeFileSelect(this)" />
                    
                    <?php if (!empty($candProfile['resume_file'])): ?>
                        <div id="resume_prompt" class="hidden flex items-center gap-2 text-on-surface-variant text-xs font-bold">
                            <span class="material-symbols-outlined text-lg text-primary">upload_file</span>
                            <span>Klik untuk memilih file CV/Resume baru</span>
                        </div>
                        <div id="resume_filename" class="flex flex-col items-center gap-1 text-primary text-xs font-bold w-full justify-center">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-green-600">check_circle</span>
                                <span id="resume_file_text" class="truncate max-w-[200px]">Resume Terunggah Saat Mendaftar</span>
                            </div>
                            <span class="text-[9px] text-on-surface-variant font-medium">(Klik di sini jika ingin mengganti CV/Resume)</span>
                        </div>
                    <?php else: ?>
                        <div id="resume_prompt" class="flex items-center gap-2 text-on-surface-variant text-xs font-bold">
                            <span class="material-symbols-outlined text-lg text-primary">upload_file</span>
                            <span>Klik untuk memilih file CV/Resume</span>
                        </div>
                        <div id="resume_filename" class="hidden flex items-center gap-1.5 text-primary text-xs font-bold w-full justify-center">
                            <span class="material-symbols-outlined text-sm text-green-600">check_circle</span>
                            <span id="resume_file_text" class="truncate max-w-[200px]"></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-on-surface-variant">Surat Lamaran / Pesan Singkat <span class="text-neutral-400 font-medium">(opsional)</span></label>
                <textarea name="cover_letter" id="apply_cover_letter" rows="3" placeholder="Tuliskan alasan Anda tertarik melamar posisi ini..." class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs resize-none text-on-surface"></textarea>
                <?php if (!empty($candProfile['cover_letter_file'])): ?>
                    <p class="text-[10px] text-green-600 font-bold flex items-center gap-1.5 mt-1">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        <span>Surat Lamaran saat mendaftar akan dilampirkan otomatis jika kolom ini kosong.</span>
                    </p>
                <?php endif; ?>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-outline-variant/15">
                <button type="button" onclick="closeApplyModal()" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-container-high transition-all text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-[#000666] hover:bg-[#0c145e] text-white rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">send</span> Kirim Lamaran
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const publishedJobs = <?= json_encode($publishedJobs) ?>;
    let selectedJob = null;

    function openFptkDetailModal(jobId) {
        selectedJob = publishedJobs.find(j => j.id === jobId);
        if (!selectedJob) return;

        document.getElementById('fptk_title_val').textContent = selectedJob.title || '';
        document.getElementById('fptk_dept_text').textContent = selectedJob.dept_name || 'Departemen';
        document.getElementById('fptk_headcount_text').textContent = selectedJob.headcount || '1';
        
        let empType = 'Full Time';
        if (selectedJob.employment_type === 'contract') empType = 'Kontrak';
        else if (selectedJob.employment_type === 'internship') empType = 'Magang';
        else if (selectedJob.employment_type === 'part_time') empType = 'Part Time';
        document.getElementById('fptk_employment_type_text').textContent = empType;

        // Format salary min & max
        const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
        const minSal = selectedJob.min_salary ? formatter.format(selectedJob.min_salary) : 'Rp 0';
        const maxSal = selectedJob.max_salary ? formatter.format(selectedJob.max_salary) : 'Rp 0';
        document.getElementById('fptk_salary_text').textContent = minSal + ' - ' + maxSal;

        document.getElementById('fptk_reason_val').textContent = selectedJob.reason || 'Kebutuhan rekrutmen departemen.';
        document.getElementById('fptk_job_desc_val').textContent = selectedJob.job_description || '';
        document.getElementById('fptk_requirements_val').textContent = selectedJob.requirements || '';

        const modal = document.getElementById('fptkDetailModal');
        const container = document.getElementById('fptkDetailModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
    }

    function closeFptkDetailModal() {
        const modal = document.getElementById('fptkDetailModal');
        const container = document.getElementById('fptkDetailModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function proceedToApply() {
        if (!selectedJob) return;
        closeFptkDetailModal();
        setTimeout(() => {
            openApplyModal(selectedJob.id, selectedJob.title, selectedJob.dept_name);
        }, 300);
    }

    function openApplyModal(reqId, jobTitle, deptName) {
        document.getElementById('apply_req_id').value = reqId;
        document.getElementById('applyModalSub').innerText = jobTitle + ' • ' + (deptName || 'Departemen');
        
        const modal = document.getElementById('applyModal');
        const container = document.getElementById('applyModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
    }

    function closeApplyModal() {
        const modal = document.getElementById('applyModal');
        const container = document.getElementById('applyModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function handleResumeFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('resume_file_text').textContent = file.name;
            const prompt = document.getElementById('resume_prompt');
            const filenameDiv = document.getElementById('resume_filename');
            if (prompt) prompt.classList.add('hidden');
            if (filenameDiv) {
                filenameDiv.classList.remove('hidden');
                const helper = filenameDiv.querySelector('span.text-\\[9px\\]');
                if (helper) helper.textContent = '(Klik di sini untuk mengganti file)';
            }
        }
    }

    function submitApplication(e) {
        e.preventDefault();
        const form = document.getElementById('applyForm');
        const formData = new FormData(form);

        Swal.fire({ title: 'Mengirimkan Lamaran...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/candidate/apply', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeApplyModal();
                Swal.fire('Lamaran Terkirim!', data.message, 'success').then(() => {
                    if (window.loadPage) window.loadPage('/candidate/dashboard');
                    else window.location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
    }

    // Live search filter
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('candJobSearch');
        const cards = document.querySelectorAll('.cand-card-item');

        if (searchInput && cards.length > 0) {
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase().trim();
                cards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    const desc = card.getAttribute('data-desc') || '';
                    if (title.includes(term) || desc.includes(term)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });

    window.openFptkDetailModal = openFptkDetailModal;
    window.closeFptkDetailModal = closeFptkDetailModal;
    window.proceedToApply = proceedToApply;
    window.openApplyModal = openApplyModal;
    window.closeApplyModal = closeApplyModal;
    window.handleResumeFileSelect = handleResumeFileSelect;
    window.submitApplication = submitApplication;
</script>
