<?php
/**
 * All Published Career Opportunities Page (/careers & /jobs)
 * Paginated: 4 columns x 5 rows = 20 items per page max.
 */
$perPage = 20; // 4 columns x 5 rows
$page = max(1, (int)($_GET['p'] ?? $_GET['page'] ?? 1));

$db = \App\Config\Database::getInstance()->getConnection();
$searchTerm = trim($_GET['q'] ?? '');

try {
    // Count total records matching search filter
    $countSql = "SELECT COUNT(*) FROM job_requisitions r WHERE r.status = 'published' AND (SELECT COUNT(*) FROM job_applications ja WHERE ja.requisition_id = r.id AND ja.status = 'hired') < r.headcount";
    if (!empty($searchTerm)) {
        $countSql .= " AND (r.title LIKE :search OR r.job_description LIKE :search)";
    }
    $stmtCount = $db->prepare($countSql);
    if (!empty($searchTerm)) {
        $stmtCount->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $totalCount = (int)$stmtCount->fetchColumn();

    $totalPages = max(1, (int)ceil($totalCount / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = max(0, ($page - 1) * $perPage);

    // Query paginated records
    $sql = "SELECT r.* FROM job_requisitions r WHERE r.status = 'published' AND (SELECT COUNT(*) FROM job_applications ja WHERE ja.requisition_id = r.id AND ja.status = 'hired') < r.headcount";
    if (!empty($searchTerm)) {
        $sql .= " AND (r.title LIKE :search OR r.job_description LIKE :search)";
    }
    $sql .= " ORDER BY r.updated_at DESC, r.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    if (!empty($searchTerm)) {
        $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $allJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $allJobs = [];
    $totalCount = 0;
    $totalPages = 1;
}
?>

<section class="bg-[#f4f6fa] min-h-screen py-12 px-8 md:px-12 lg:px-24">
    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Top Navigation / Breadcrumb -->
        <div class="flex items-center justify-between">
            <a href="/" class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-[#0c145e] bg-white px-3.5 py-2 rounded-xl border border-gray-200 transition-all shadow-2xs">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Kembali ke Beranda
            </a>
            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                Total <b class="text-[#0c145e]"><?= $totalCount ?></b> Lowongan Tersedia
            </span>
        </div>

        <!-- Header Title & Search -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
            <div class="space-y-2">
                <h1 class="text-3xl font-headline font-bold text-[#0c145e]">Semua Lowongan Pekerjaan</h1>
                <p class="text-gray-500 max-w-2xl text-sm">Temukan posisi impian Anda dan kembangkan karir profesional bersama kami.</p>
            </div>
            <div class="w-full md:w-80">
                <form method="GET" action="/careers" class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                    <input type="text" name="q" id="careersSearch" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Cari posisi..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:border-[#0c145e] focus:ring-1 focus:ring-[#0c145e] text-xs font-semibold bg-gray-50/50 shadow-2xs">
                </form>
            </div>
        </div>
        
        <!-- Job Requisitions Grid (Max 4 per row, Max 5 rows = 20 per page) -->
        <div id="careersGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if (empty($allJobs)): ?>
                <div class="col-span-full bg-white p-12 rounded-3xl border border-gray-100 text-center space-y-3 shadow-sm">
                    <div class="w-16 h-16 bg-blue-50 text-[#0c145e] rounded-2xl flex items-center justify-center mx-auto border border-blue-100">
                        <span class="material-symbols-outlined text-3xl">search_off</span>
                    </div>
                    <h3 class="text-lg font-headline font-bold text-[#0c145e]">Lowongan Tidak Ditemukan</h3>
                    <p class="text-sm text-gray-500 max-w-md mx-auto">Tidak ada lowongan yang sesuai dengan kata kunci pencarian Anda. Coba gunakan kata kunci lain.</p>
                </div>
            <?php else: ?>
                <?php foreach ($allJobs as $job): ?>
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
                    <div class="job-card-item bg-white p-6 rounded-3xl border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all flex flex-col h-full" data-title="<?= htmlspecialchars(strtolower($job['title'] ?? '')) ?>" data-desc="<?= htmlspecialchars(strtolower($job['job_description'] ?? '')) ?>">
                        <?php if (!empty($job['logo_url'])): ?>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border p-1 bg-white shrink-0 overflow-hidden" style="border-color: <?= $colorVal ?>30;">
                                <img src="<?= htmlspecialchars($job['logo_url']) ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                            </div>
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border shrink-0" style="background-color: <?= $colorVal ?>15; border-color: <?= $colorVal ?>30; color: <?= $colorVal ?>;">
                                <span class="material-symbols-outlined text-[1.4rem]"><?= htmlspecialchars(!empty($job['icon']) ? $job['icon'] : 'work') ?></span>
                            </div>
                        <?php endif; ?>
                        <h3 class="job-title-text text-base font-headline font-bold text-[#0c145e] mb-2"><?= htmlspecialchars($job['title'] ?? $job['job_title'] ?? 'Posisi Pekerjaan') ?></h3>
                        <p class="job-desc-text text-[0.8rem] text-gray-500 leading-relaxed mb-6 flex-grow line-clamp-3">
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

        <!-- Pagination Bar (When total jobs exceed 20) -->
        <?php if ($totalPages > 1): ?>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-gray-200">
                <span class="text-xs text-gray-500 font-semibold">
                    Menampilkan <b class="text-gray-900"><?= min($totalCount, $offset + 1) ?></b> - <b class="text-gray-900"><?= min($totalCount, $offset + count($allJobs)) ?></b> dari <b class="text-gray-900"><?= $totalCount ?></b> lowongan
                </span>

                <div class="flex items-center gap-1.5">
                    <?php if ($page > 1): ?>
                        <a href="/careers?p=<?= $page - 1 ?><?= !empty($searchTerm) ? '&q=' . urlencode($searchTerm) : '' ?>" class="p-2 rounded-xl border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-all font-bold text-xs flex items-center justify-center">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </a>
                    <?php else: ?>
                        <span class="p-2 rounded-xl border border-gray-200 bg-gray-100 text-gray-400 text-xs flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="w-9 h-9 rounded-xl bg-[#0c145e] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <a href="/careers?p=<?= $i ?><?= !empty($searchTerm) ? '&q=' . urlencode($searchTerm) : '' ?>" class="w-9 h-9 rounded-xl border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-all font-bold text-xs flex items-center justify-center">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="/careers?p=<?= $page + 1 ?><?= !empty($searchTerm) ? '&q=' . urlencode($searchTerm) : '' ?>" class="p-2 rounded-xl border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-all font-bold text-xs flex items-center justify-center">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <span class="p-2 rounded-xl border border-gray-200 bg-gray-100 text-gray-400 text-xs flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('careersSearch');
    const cards = document.querySelectorAll('.job-card-item');

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
</script>
