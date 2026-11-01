<!-- Hero Section -->
<section class="relative pt-20 pb-24 px-8 md:px-12 lg:px-24 overflow-hidden">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center gap-16">
        <div class="flex-1 space-y-8 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-tertiary-fixed rounded-full text-on-tertiary-fixed text-xs font-bold tracking-wider">
                <span class="material-symbols-outlined text-sm" data-icon="verified" style="font-variation-settings: 'FILL' 1;">verified</span>
                REKRUTMEN & MANAJEMEN EKSTRAKURIKULER
            </div>
            <h1 class="text-5xl lg:text-7xl font-headline font-extrabold tracking-tight text-primary leading-[1.1]">
                Portal Rekrutmen & Manajemen Anggota
            </h1>
            <p class="text-xl text-on-surface-variant max-w-xl leading-relaxed">
                Satu pintu menuju seluruh ekosistem rekrutmen dan manajemen anggota ekstrakurikuler. Akses pendaftaran calon anggota, 4 divisi operasional, hingga administrasi keanggotaan dalam satu dasbor terintegrasi yang aman.
            </p>
            <div class="flex flex-wrap gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/candidate/dashboard" class="px-6 py-3 bg-[#000666] text-white rounded-lg font-bold font-headline hover:bg-[#0c145e] hover:shadow-lg transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">dashboard</span>
                        Ke Dashboard Kandidat
                    </a>
                <?php else: ?>
                    <a href="/signin" class="px-6 py-3 bg-[#000666] text-white rounded-lg font-bold font-headline hover:bg-[#0c145e] hover:shadow-lg transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">login</span>
                        Masuk / Daftar
                    </a>
                <?php endif; ?>
                <a href="#" class="px-6 py-3 bg-white text-[#000666] rounded-lg font-bold font-headline shadow-sm hover:shadow-md transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-xl">menu_book</span>
                    Panduan Pengguna
                </a>
            </div>
        </div>
        <div class="flex-1 relative pl-0 md:pl-10">
            <div class="w-full aspect-square rounded-[2.5rem] bg-[#0c145e] p-8 flex flex-col justify-between shadow-2xl relative z-0">
                <!-- 3x3 Grid Icon Hub -->
                <div class="grid grid-cols-3 gap-4 flex-grow">
                    <div class="bg-[#243b91] rounded-2xl flex items-center justify-center hover:bg-[#2d49b3] transition-colors cursor-pointer" title="Post Production">
                        <span class="material-symbols-outlined text-white/80 text-4xl">movie_edit</span>
                    </div>
                    <div class="bg-[#3e2f6b] rounded-2xl flex items-center justify-center hover:bg-[#4c3983] transition-colors cursor-pointer" title="Editorial">
                        <span class="material-symbols-outlined text-white/80 text-4xl">edit_note</span>
                    </div>
                    <div class="bg-[#322c7a] rounded-2xl flex items-center justify-center hover:bg-[#3d3694] transition-colors cursor-pointer" title="Broadcast">
                        <span class="material-symbols-outlined text-white/80 text-4xl">mic</span>
                    </div>
                    
                    <div class="bg-[#1f4277] rounded-2xl flex items-center justify-center hover:bg-[#265091] transition-colors cursor-pointer" title="Visual Production">
                        <span class="material-symbols-outlined text-white/80 text-4xl">videocam</span>
                    </div>
                    <div class="bg-[#423d61] rounded-2xl flex items-center justify-center hover:bg-[#524c78] transition-colors cursor-pointer" title="BPH">
                        <span class="material-symbols-outlined text-white/80 text-4xl">groups</span>
                    </div>
                    <div class="bg-[#1e4682] rounded-2xl flex items-center justify-center hover:bg-[#25559c] transition-colors cursor-pointer" title="Evaluasi">
                        <span class="material-symbols-outlined text-white/80 text-4xl">fact_check</span>
                    </div>
                    
                    <div class="bg-[#3c2a6d] rounded-2xl flex items-center justify-center hover:bg-[#4a3485] transition-colors cursor-pointer" title="Wawancara">
                        <span class="material-symbols-outlined text-white/80 text-4xl">co_present</span>
                    </div>
                    <div class="bg-[#1c3f87] rounded-2xl flex items-center justify-center hover:bg-[#234da3] transition-colors cursor-pointer" title="Statistik">
                        <span class="material-symbols-outlined text-white/80 text-4xl">leaderboard</span>
                    </div>
                    <div class="bg-[#433660] rounded-2xl flex items-center justify-center hover:bg-[#544378] transition-colors cursor-pointer" title="Keanggotaan">
                        <span class="material-symbols-outlined text-white/80 text-4xl">verified</span>
                    </div>
                </div>
                
                <!-- Bottom Text inside dark box -->
                <div class="mt-6 pr-4 text-right">
                    <p class="text-[#a0a5db] text-sm font-medium">Akses Semua Layanan Ekstrakurikuler Dalam Satu Portal</p>
                </div>
            </div>
            
            <!-- Identity Chip Overlapping - Floating Animation -->
            <div class="absolute -bottom-8 -left-8 p-5 bg-white rounded-xl shadow-[0_20px_40px_rgba(0,0,0,0.15)] border border-gray-100 flex items-center gap-4 animate-float z-10 w-[280px]">
                <div class="w-12 h-12 bg-[#8efb8b] rounded-full flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-black text-xl" style="font-variation-settings: 'FILL' 1;">local_police</span>
                </div>
                <div class="flex flex-col justify-center">
                    <p class="text-xs text-gray-500 font-medium mb-0.5">Dilindungi oleh</p>
                    <p class="text-sm font-bold text-[#0c145e] leading-none">faishalnafi protect</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features / Modules Hub -->
<?php
$publishedJobs = [];
$totalJobsCount = 0;
try {
    $db = \App\Config\Database::getInstance()->getConnection();
    
    // Count total active published jobs
    $stmtCount = $db->query("
        SELECT COUNT(*) FROM job_requisitions r 
        WHERE r.status = 'published' 
          AND (SELECT COUNT(*) FROM job_applications ja WHERE ja.requisition_id = r.id AND ja.status = 'hired') < r.headcount
    ");
    $totalJobsCount = (int)$stmtCount->fetchColumn();

    // Limit to 12 jobs
    $stmtJobs = $db->query("
        SELECT r.*, d.name AS dept_name 
        FROM job_requisitions r
        LEFT JOIN departments d ON r.department_id = d.id
        WHERE r.status = 'published'
          AND (SELECT COUNT(*) FROM job_applications ja WHERE ja.requisition_id = r.id AND ja.status = 'hired') < r.headcount
        ORDER BY r.updated_at DESC, r.created_at DESC
        LIMIT 12
    ");
    $publishedJobs = $stmtJobs->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $publishedJobs = [];
    $totalJobsCount = 0;
}
?>
<section class="bg-[#f4f6fa] py-16 px-8 md:px-12 lg:px-24">
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div class="space-y-2">
                <h2 class="text-3xl font-headline font-bold text-[#0c145e]">Daftar Divisi Operasional Terbuka</h2>
                <p class="text-gray-500 max-w-2xl text-sm">Temukan divisi operasional yang sesuai dengan minat & bakat Anda, lalu bergabunglah menjadi anggota baru.</p>
            </div>
            <div class="w-full md:w-auto">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[1.2rem]">search</span>
                    <input type="text" id="jobSearch" placeholder="Cari divisi..." class="w-full md:w-72 pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#0c145e] focus:ring-1 focus:ring-[#0c145e] text-xs font-semibold bg-white shadow-2xs">
                </div>
            </div>
        </div>
        
        <!-- Application Grid -->
        <div id="jobGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if (empty($publishedJobs)): ?>
                <div class="col-span-full bg-white p-12 rounded-3xl border border-gray-100 text-center space-y-3 shadow-sm">
                    <div class="w-16 h-16 bg-blue-50 text-[#0c145e] rounded-2xl flex items-center justify-center mx-auto border border-blue-100">
                        <span class="material-symbols-outlined text-3xl">groups</span>
                    </div>
                    <h3 class="text-lg font-headline font-bold text-[#0c145e]">Belum Ada Pendaftaran Divisi Terbuka</h3>
                    <p class="text-sm text-gray-500 max-w-md mx-auto">Saat ini belum ada pendaftaran divisi baru yang dibuka. Silakan cek kembali secara berkala.</p>
                </div>
            <?php else: ?>
                <?php foreach ($publishedJobs as $job): ?>
                    <?php
                        $colorVal = $job['card_color'] ?? '#0c145e';
                        $legacyPresetMap = [
                            'blue' => '#0c145e',
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
                            $colorVal = '#0c145e';
                        }
                    ?>
                    <div class="landing-job-card bg-white p-6 rounded-3xl border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all flex flex-col h-full" data-title="<?= htmlspecialchars(strtolower($job['title'] ?? '')) ?>" data-desc="<?= htmlspecialchars(strtolower($job['job_description'] ?? '')) ?>">
                        <?php if (!empty($job['logo_url'])): ?>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border p-1 bg-white shrink-0 overflow-hidden" style="border-color: <?= $colorVal ?>30;">
                                <img src="<?= htmlspecialchars($job['logo_url']) ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                            </div>
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border shrink-0" style="background-color: <?= $colorVal ?>15; border-color: <?= $colorVal ?>30; color: <?= $colorVal ?>;">
                                <span class="material-symbols-outlined text-[1.4rem]"><?= htmlspecialchars(!empty($job['icon']) ? $job['icon'] : 'groups') ?></span>
                            </div>
                        <?php endif; ?>
                        <h3 class="text-base font-headline font-bold text-[#0c145e] mb-2"><?= htmlspecialchars($job['title'] ?? $job['job_title'] ?? 'Divisi Operasional') ?></h3>
                        <p class="text-[0.8rem] text-gray-500 leading-relaxed mb-6 flex-grow line-clamp-3">
                            <?= htmlspecialchars(($job['job_description'] ?? '') !== '' ? $job['job_description'] : 'Mencari anggota baru berbakat untuk bergabung dengan divisi kami.') ?>
                        </p>
                        <div class="pt-4 border-t border-gray-50 mt-auto">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="/candidate/dashboard" class="text-[#0c145e] text-xs font-bold flex items-center gap-1 hover:text-blue-700 transition-colors">
                                    <span class="material-symbols-outlined text-[1rem]">open_in_new</span> Pilih Divisi Ini
                                </a>
                            <?php else: ?>
                                <a href="/signup" class="text-[#0c145e] text-xs font-bold flex items-center gap-1 hover:text-blue-700 transition-colors">
                                    <span class="material-symbols-outlined text-[1rem]">open_in_new</span> Pilih Divisi Ini
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- View All Link Button -->
        <div class="text-center pt-4">
            <a href="/careers" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0c145e] hover:bg-[#152187] text-white rounded-2xl font-bold text-xs transition-all shadow-md hover:shadow-lg">
                <span>Lihat Semua Pendaftaran Divisi (<?= $totalJobsCount ?>)</span>
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="bg-[#f4f6fa] pb-24 px-8 md:px-12 lg:px-24">
    <div class="max-w-7xl mx-auto">
        <div class="bg-[#0b1257] rounded-[2rem] p-10 md:p-14 flex flex-col md:flex-row items-center justify-between gap-10 shadow-2xl relative overflow-hidden">
            <!-- decorative background circle -->
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 max-w-2xl space-y-3">
                <h2 class="text-3xl md:text-4xl font-headline font-bold text-white">Mulai Perjalanan Ekstrakurikuler Anda</h2>
                <p class="text-indigo-200 text-sm md:text-base leading-relaxed max-w-xl">
                    Buat akun pendaftar sekarang untuk mendaftar divisi, melacak status seleksi secara real-time, dan bergabung sebagai anggota aktif.
                </p>
            </div>
            
            <div class="relative z-10 flex flex-col sm:flex-row gap-4 w-full md:w-auto shrink-0">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/candidate/dashboard" class="px-6 py-3 bg-white text-[#0b1257] rounded-lg font-bold hover:bg-gray-100 transition-all flex items-center justify-center gap-2 shadow-lg text-sm">
                        <span class="material-symbols-outlined text-[1.1rem]">dashboard</span>
                        Ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="/signin" class="px-6 py-3 bg-white text-[#0b1257] rounded-lg font-bold hover:bg-gray-100 transition-all flex items-center justify-center gap-2 shadow-lg text-sm">
                        <span class="material-symbols-outlined text-[1.1rem]">login</span>
                        Masuk Pendaftar
                    </a>
                    <a href="/signup" class="px-6 py-3 bg-[#242c75] border border-indigo-400/30 text-white rounded-lg font-bold hover:bg-[#31398c] transition-all flex items-center justify-center gap-2 text-sm shadow-sm">
                        <span class="material-symbols-outlined text-[1.1rem]">person_add</span>
                        Daftar Akun Anggota
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('jobSearch');
    const jobCards = document.querySelectorAll('.landing-job-card');

    if (searchInput && jobCards.length > 0) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();

            jobCards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const desc = card.getAttribute('data-desc') || '';
                
                if (title.includes(searchTerm) || desc.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Redirect to /careers with query if user presses Enter
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && this.value.trim() !== '') {
                window.location.href = '/careers?q=' + encodeURIComponent(this.value.trim());
            }
        });
    }
});
</script>
