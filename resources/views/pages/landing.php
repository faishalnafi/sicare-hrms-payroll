<!-- Hero Section -->
<section class="relative pt-20 pb-24 px-8 md:px-12 lg:px-24 overflow-hidden">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center gap-16">
        <div class="flex-1 space-y-8 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-tertiary-fixed rounded-full text-on-tertiary-fixed text-xs font-bold tracking-wider">
                <span class="material-symbols-outlined text-sm" data-icon="verified" style="font-variation-settings: 'FILL' 1;">verified</span>
                HRIS & ATS INTEGRATED
            </div>
            <h1 class="text-5xl lg:text-7xl font-headline font-extrabold tracking-tight text-primary leading-[1.1]">
                Portal Karir & Manajemen Karyawan
            </h1>
            <p class="text-xl text-on-surface-variant max-w-xl leading-relaxed">
                Satu pintu menuju seluruh ekosistem HR perusahaan. Akses pendaftaran karir, portal karyawan mandiri (ESS), hingga administrasi operasional dalam satu dasbor terintegrasi yang aman.
            </p>
            <div class="flex flex-wrap gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="px-6 py-3 bg-[#000666] text-white rounded-lg font-bold font-headline hover:bg-[#0c145e] hover:shadow-lg transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">dashboard</span>
                        Ke Dashboard
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
        <div class="flex-1 relative pl-0 md:pl-6 flex items-center justify-center">
            <!-- 3D Floating 3x3 Isometric Blocks Canvas (Outer box removed, pure 3D floating grid) -->
            <div id="sicare-3d-container" class="w-full aspect-square max-w-[540px] relative z-0"></div>
            
            <!-- Identity Chip Overlapping - Floating Animation (Exact design from Gambar 1) -->
            <div class="absolute -bottom-6 left-0 md:left-4 p-5 bg-white rounded-xl shadow-[0_20px_40px_rgba(0,0,0,0.12)] border border-gray-100 flex items-center gap-4 animate-float z-10 w-[280px]">
                <div class="w-12 h-12 bg-[#8efb8b] rounded-full flex items-center justify-center shrink-0 shadow-sm">
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

    // Limit to 12 jobs (3 rows x 4 columns)
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
                <h2 class="text-3xl font-headline font-bold text-[#0c145e]">Daftar Lowongan yang Dibuka</h2>
                <p class="text-gray-500 max-w-2xl text-sm">Temukan posisi yang sesuai dengan keahlian Anda dan bergabunglah dengan tim profesional kami.</p>
            </div>
            <div class="w-full md:w-auto">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[1.2rem]">search</span>
                    <input type="text" id="jobSearch" placeholder="Cari posisi..." class="w-full md:w-72 pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#0c145e] focus:ring-1 focus:ring-[#0c145e] text-xs font-semibold bg-white shadow-2xs">
                </div>
            </div>
        </div>
        
        <!-- Application Grid (Max 4 per row, Max 3 rows = 12 cards) -->
        <div id="jobGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if (empty($publishedJobs)): ?>
                <div class="col-span-full bg-white p-12 rounded-3xl border border-gray-100 text-center space-y-3 shadow-sm">
                    <div class="w-16 h-16 bg-blue-50 text-[#0c145e] rounded-2xl flex items-center justify-center mx-auto border border-blue-100">
                        <span class="material-symbols-outlined text-3xl">work_off</span>
                    </div>
                    <h3 class="text-lg font-headline font-bold text-[#0c145e]">Belum Ada Lowongan Aktif</h3>
                    <p class="text-sm text-gray-500 max-w-md mx-auto">Saat ini belum ada lowongan pekerjaan baru yang dipublikasikan ke portal karir. Silakan cek kembali secara berkala.</p>
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
                                <span class="material-symbols-outlined text-[1.4rem]"><?= htmlspecialchars(!empty($job['icon']) ? $job['icon'] : 'work') ?></span>
                            </div>
                        <?php endif; ?>
                        <h3 class="text-base font-headline font-bold text-[#0c145e] mb-2"><?= htmlspecialchars($job['title'] ?? $job['job_title'] ?? 'Posisi Pekerjaan') ?></h3>
                        <p class="text-[0.8rem] text-gray-500 leading-relaxed mb-6 flex-grow line-clamp-3">
                            <?= htmlspecialchars(($job['job_description'] ?? '') !== '' ? $job['job_description'] : 'Mencari kandidat berbakat untuk bergabung dengan tim kami.') ?>
                        </p>
                        <div class="pt-4 border-t border-gray-50 mt-auto">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="/candidate/dashboard" class="text-[#0c145e] text-xs font-bold flex items-center gap-1 hover:text-blue-700 transition-colors">
                                    <span class="material-symbols-outlined text-[1rem]">open_in_new</span> Lamar Sekarang
                                </a>
                            <?php else: ?>
                                <a href="/signin" class="text-[#0c145e] text-xs font-bold flex items-center gap-1 hover:text-blue-700 transition-colors">
                                    <span class="material-symbols-outlined text-[1rem]">open_in_new</span> Lamar Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- View All Careers Link Button -->
        <div class="text-center pt-4">
            <a href="/careers" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0c145e] hover:bg-[#152187] text-white rounded-2xl font-bold text-xs transition-all shadow-md hover:shadow-lg">
                <span>Lihat Semua Lowongan Karir (<?= $totalJobsCount ?>)</span>
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
                <h2 class="text-3xl md:text-4xl font-headline font-bold text-white">Mulai Karir Anda Bersama Kami</h2>
                <p class="text-indigo-200 text-sm md:text-base leading-relaxed max-w-xl">
                    Buat akun pelamar sekarang untuk mulai mengirimkan lamaran, melacak status rekrutmen secara real-time, dan mendapatkan notifikasi lowongan terbaru.
                </p>
            </div>
            
            <div class="relative z-10 flex flex-col sm:flex-row gap-4 w-full md:w-auto shrink-0">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="px-6 py-3 bg-white text-[#0b1257] rounded-lg font-bold hover:bg-gray-100 transition-all flex items-center justify-center gap-2 shadow-lg text-sm">
                        <span class="material-symbols-outlined text-[1.1rem]">dashboard</span>
                        Ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="/signin" class="px-6 py-3 bg-white text-[#0b1257] rounded-lg font-bold hover:bg-gray-100 transition-all flex items-center justify-center gap-2 shadow-lg text-sm">
                        <span class="material-symbols-outlined text-[1.1rem]">login</span>
                        Masuk Pelamar
                    </a>
                    <a href="/signup" class="px-6 py-3 bg-[#242c75] border border-indigo-400/30 text-white rounded-lg font-bold hover:bg-[#31398c] transition-all flex items-center justify-center gap-2 text-sm shadow-sm">
                        <span class="material-symbols-outlined text-[1.1rem]">person_add</span>
                        Buat Akun Karir
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Three.js Core CDN & Interactive 3D Floating Grid -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="/js/landing-3d.js?v=<?= time() ?>"></script>

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
