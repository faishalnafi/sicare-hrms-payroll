<?php
/**
 * universal changelogs view page
 */

$changelogs = [];
$errorMsg = null;
$source = '';
$appName = 'siCare';

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    
    // Fetch dynamic app name
    $stmtName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1");
    if ($stmtName) {
        $dbAppName = $stmtName->fetchColumn();
        if ($dbAppName) {
            $appName = $dbAppName;
        }
    }
    
    // Check if table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'changelogs'")->fetch();
    if ($tableCheck) {
        $stmt = $db->query("SELECT * FROM changelogs ORDER BY compiled_date DESC, created_at DESC");
        $dbData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($dbData)) {
            foreach ($dbData as $row) {
                $changelogs[] = [
                    'version' => $row['version'],
                    'edition' => $row['edition'],
                    'repo_type' => $row['repo_type'] ?? 'monorepo',
                    'compiled_date' => $row['compiled_date'],
                    'applied_date' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
                    'migration_level' => $row['migration_level'],
                    'summary' => json_decode($row['summary'], true) ?: []
                ];
            }
            $source = 'Live Schema Connection';
        }
    }
} catch (Exception $e) {
    // Silent fail, fallback to JSON
}

// Fallback to JSON file if database is empty/inaccessible
if (empty($changelogs)) {
    $jsonPath = __DIR__ . '/../../../changelog.json';
    if (file_exists($jsonPath)) {
        $jsonContent = file_get_contents($jsonPath);
        $rawJson = json_decode($jsonContent, true) ?: [];
        foreach ($rawJson as $rel) {
            $changelogs[] = [
                'version' => $rel['version'],
                'edition' => $rel['edition'],
                'repo_type' => $rel['repo_type'] ?? 'monorepo',
                'compiled_date' => $rel['compiled_date'],
                'applied_date' => $rel['compiled_date'] . ' 00:00:00 (Est)',
                'migration_level' => $rel['migration_level'],
                'summary' => $rel['summary']
            ];
        }
        $source = 'JSON File (Fallback)';
    } else {
        $errorMsg = "Data changelog tidak ditemukan.";
    }
}
?>

<div class="p-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-on-surface tracking-tight flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-4xl">history</span>
                Riwayat Rilis & Changelog
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">Catatan pembaruan sistem dan pengembangan platform <?= htmlspecialchars($appName) ?>.</p>
        </div>
        <?php if (!empty($source)): ?>
            <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 rounded-full px-3 py-1 text-xs font-bold text-primary self-start md:self-center">
                <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                <?= $source ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <?php if ($errorMsg): ?>
        <div class="bg-error/10 border border-error/20 text-error p-4 rounded-xl flex items-center gap-3">
            <span class="material-symbols-outlined text-2xl">error</span>
            <span class="text-sm font-semibold"><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php elseif (empty($changelogs)): ?>
        <div class="bg-surface-container border border-outline-variant/20 p-8 rounded-2xl text-center">
            <span class="material-symbols-outlined text-5xl text-on-surface-variant/50">history_toggle_off</span>
            <h3 class="text-lg font-bold text-on-surface mt-4">Changelog Belum Tersedia</h3>
            <p class="text-sm text-on-surface-variant mt-1">Belum ada catatan rilis yang dipublikasikan pada sistem.</p>
        </div>
    <?php else: ?>
        <div class="relative pl-6 md:pl-8 border-l border-outline-variant/30 space-y-12 ml-4">
            <?php foreach ($changelogs as $index => $rel): 
                $isBeta = preg_match('/^\d+\.\d+\.\d+-/', $rel['version']) === 1;
                $isLTS = strpos($rel['version'], 'LTS') !== false;
                $isSTS = strpos($rel['version'], 'STS') !== false;

                // Color themes based on track
                if ($isBeta) {
                    $trackBadge = 'bg-amber-100 text-amber-800 border-amber-200';
                    $trackLabel = $isLTS ? 'Beta LTS' : 'Beta STS';
                    $iconColor = 'text-amber-700 bg-amber-50 border-amber-200';
                } elseif ($isLTS) {
                    $trackBadge = 'bg-green-100 text-green-800 border-green-200';
                    $trackLabel = 'LTS (Long Term Support)';
                    $iconColor = 'text-green-700 bg-green-50 border-green-200';
                } elseif ($isSTS) {
                    $trackBadge = 'bg-blue-100 text-blue-800 border-blue-200';
                    $trackLabel = 'STS (Short Term Support)';
                    $iconColor = 'text-blue-700 bg-blue-50 border-blue-200';
                } else {
                    $trackBadge = 'bg-purple-100 text-purple-800 border-purple-200';
                    $trackLabel = 'Pre-Release';
                    $iconColor = 'text-purple-700 bg-purple-50 border-purple-200';
                }
            ?>
                <!-- Timeline Node -->
                <div class="relative">
                    <!-- Marker Dot -->
                    <span class="absolute -left-[39px] md:-left-[47px] top-1.5 flex items-center justify-center w-8 h-8 rounded-full border bg-surface-container-lowest <?= $iconColor ?> shadow-sm z-10">
                        <span class="material-symbols-outlined text-base font-bold"><?= $isBeta ? 'construction' : 'verified' ?></span>
                    </span>

                    <!-- Version Card -->
                    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <!-- Top Banner / Meta -->
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-4 border-b border-outline-variant/10 w-full">
                            <div class="flex items-center gap-3">
                                <h3 class="text-xl font-black text-on-surface tracking-tight">Version <?= htmlspecialchars($rel['version']) ?></h3>
                                <div class="flex gap-1.5 flex-wrap">
                                    <span class="border rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase <?= $trackBadge ?>"><?= htmlspecialchars(ucfirst($rel['edition'])) ?></span>
                                    <?php if (!$isBeta): ?>
                                    <span class="border rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase bg-neutral-100 text-neutral-800 border-neutral-200"><?= htmlspecialchars(strtoupper($rel['repo_type'] ?? 'monorepo')) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 text-xs font-semibold text-on-surface-variant flex-wrap">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">event</span>
                                    Rilis: <?= htmlspecialchars($rel['compiled_date']) ?>
                                </span>
                                <span class="text-outline-variant/40">|</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">update</span>
                                    Update: <?= htmlspecialchars($rel['applied_date']) ?>
                                </span>
                                <span class="text-outline-variant/40">|</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">swap_horiz</span>
                                    <?= htmlspecialchars($rel['migration_level']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Description & Detailed Logs -->
                        <div class="space-y-6">
                            <?php 
                            $categories = [
                                'added' => ['title' => 'Added', 'color' => 'bg-green-500/10 text-green-700 border-green-500/20', 'icon' => 'add_circle'],
                                'improved' => ['title' => 'Improved', 'color' => 'bg-blue-500/10 text-blue-700 border-blue-500/20', 'icon' => 'trending_up'],
                                'fixed' => ['title' => 'Fixed', 'color' => 'bg-red-500/10 text-red-700 border-red-500/20', 'icon' => 'bug_report'],
                                'security' => ['title' => 'Security', 'color' => 'bg-purple-500/10 text-purple-700 border-purple-500/20', 'icon' => 'shield'],
                                'deprecated' => ['title' => 'Deprecated', 'color' => 'bg-orange-500/10 text-orange-700 border-orange-500/20', 'icon' => 'warning'],
                                'removed' => ['title' => 'Removed', 'color' => 'bg-neutral-500/10 text-neutral-700 border-neutral-500/20', 'icon' => 'delete'],
                                'changed' => ['title' => 'Changed', 'color' => 'bg-indigo-500/10 text-indigo-700 border-indigo-500/20', 'icon' => 'edit_note'],
                                'refactored' => ['title' => 'Refactored', 'color' => 'bg-teal-500/10 text-teal-700 border-teal-500/20', 'icon' => 'code']
                            ];

                            $hasSummaryContent = false;
                            foreach ($categories as $key => $cat):
                                if (!empty($rel['summary'][$key])):
                                    $hasSummaryContent = true;
                            ?>
                                <div>
                                    <div class="inline-flex items-center gap-1.5 border rounded-lg px-2.5 py-0.5 text-xs font-bold mb-2 <?= $cat['color'] ?>">
                                        <span class="material-symbols-outlined text-[15px]"><?= $cat['icon'] ?></span>
                                        <?= $cat['title'] ?>
                                    </div>
                                    <ul class="list-disc pl-5 space-y-1 text-xs text-on-surface-variant leading-relaxed">
                                        <?php foreach ($rel['summary'][$key] as $item): ?>
                                            <li><?= htmlspecialchars($item) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php 
                                endif; 
                            endforeach; 

                            if (!$hasSummaryContent):
                            ?>
                                <p class="text-xs text-on-surface-variant italic">Tidak ada rincian pembaruan yang terdaftar.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
