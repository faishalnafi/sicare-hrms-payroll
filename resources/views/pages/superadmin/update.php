<?php
/**
 * Superadmin System Update view page
 */

// Strict authorization check inside the view (defense in depth)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
    echo "Akses ditolak.";
    exit;
}

$db = \App\Config\Database::getInstance()->getConnection();

// 1. Get current database version
$dbVersion = 'Belum Terinstal (Butuh Migrasi)';
$dbCompiledDate = 'N/A';
$dbEdition = 'business';
$dbRepoType = 'monorepo';
try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'changelogs'")->fetch();
    if ($tableCheck) {
        $stmt = $db->query("SELECT version, edition, repo_type, compiled_date FROM changelogs ORDER BY compiled_date DESC, version DESC LIMIT 1");
        $latestDb = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($latestDb) {
            $dbVersion = $latestDb['version'];
            $dbCompiledDate = $latestDb['compiled_date'];
            $dbEdition = $latestDb['edition'] ?? 'business';
            $dbRepoType = $latestDb['repo_type'] ?? 'monorepo';
        }
    }
} catch (Exception $e) {
    // Table might not exist yet
}

// 2. Get latest version from changelog.json
$jsonPath = __DIR__ . '/../../../../changelog.json';
$jsonVersion = 'N/A';
$jsonCompiledDate = 'N/A';
$jsonEdition = 'business';
$jsonRepoType = 'monorepo';
$latestChangelog = null;

if (file_exists($jsonPath)) {
    $jsonContent = file_get_contents($jsonPath);
    $releases = json_decode($jsonContent, true);
    if (is_array($releases) && !empty($releases)) {
        $latestChangelog = $releases[0];
        $jsonVersion = $latestChangelog['version'];
        $jsonCompiledDate = $latestChangelog['compiled_date'];
        $jsonEdition = $latestChangelog['edition'] ?? 'business';
        $jsonRepoType = $latestChangelog['repo_type'] ?? 'monorepo';
    }
}

// 3. Determine update status
$isUpToDate = ($dbVersion === $jsonVersion);
?>

<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-black text-on-surface tracking-tight flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-4xl">system_update</span>
            Pembaruan Sistem (System Update)
        </h1>
        <p class="text-sm text-on-surface-variant mt-1">Manajemen pembaruan kode, sinkronisasi skema database, dan kontrol sesi global.</p>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- DB Version -->
        <div class="bg-surface-container-lowest border border-outline-variant/15 p-5 rounded-2xl flex items-center gap-4 shadow-sm">
            <div class="p-3 bg-secondary/10 border border-secondary/20 rounded-xl text-secondary">
                <span class="material-symbols-outlined text-2xl">database</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Versi Skema Terinstal</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <h3 class="text-lg font-black text-on-surface"><?= htmlspecialchars($dbVersion) ?></h3>
                    <span class="border rounded-full px-1.5 py-0.2 text-[8px] font-extrabold uppercase bg-neutral-100 text-neutral-800 border-neutral-200"><?= htmlspecialchars(strtoupper($dbRepoType)) ?></span>
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">Edisi: <?= htmlspecialchars(ucfirst($dbEdition)) ?> | Rilis: <?= htmlspecialchars($dbCompiledDate) ?></p>
            </div>
        </div>

        <!-- JSON File Version -->
        <div class="bg-surface-container-lowest border border-outline-variant/15 p-5 rounded-2xl flex items-center gap-4 shadow-sm">
            <div class="p-3 bg-primary/10 border border-primary/20 rounded-xl text-primary">
                <span class="material-symbols-outlined text-2xl">description</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Versi Berkas Unggahan (JSON)</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <h3 class="text-lg font-black text-on-surface"><?= htmlspecialchars($jsonVersion) ?></h3>
                    <span class="border rounded-full px-1.5 py-0.2 text-[8px] font-extrabold uppercase bg-neutral-100 text-neutral-800 border-neutral-200"><?= htmlspecialchars(strtoupper($jsonRepoType)) ?></span>
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">Edisi: <?= htmlspecialchars(ucfirst($jsonEdition)) ?> | Rilis: <?= htmlspecialchars($jsonCompiledDate) ?></p>
            </div>
        </div>
    </div>

    <!-- Update Action Card -->
    <?php if ($isUpToDate): ?>
        <div class="bg-green-50 border border-green-200 rounded-3xl p-8 mb-8 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-green-500 text-white rounded-2xl shadow-md flex-shrink-0">
                    <span class="material-symbols-outlined text-3xl">done_all</span>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold text-green-900">Aplikasi Sudah Terkini</h3>
                    <p class="text-sm text-green-700 mt-1.5 leading-relaxed">
                        Versi database Anda cocok dengan berkas rilis terbaru di server. Tidak ada pembaruan skema atau migrasi yang diperlukan saat ini.
                    </p>
                    <div class="mt-4 inline-flex items-center gap-2 bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">
                        Status: Stabil & Up to Date
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-amber-50 border border-amber-200 rounded-3xl p-8 mb-8 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-amber-500 text-white rounded-2xl shadow-md flex-shrink-0">
                    <span class="material-symbols-outlined text-3xl">warning</span>
                </div>
                <div class="flex-grow">
                    <h3 class="text-xl font-extrabold text-amber-900">Pembaruan Sistem Tersedia!</h3>
                    <p class="text-sm text-amber-800 mt-1.5 leading-relaxed">
                        Terdeteksi perbedaan versi antara database (<strong><?= htmlspecialchars($dbVersion) ?></strong>) dan berkas release JSON terbaru (<strong><?= htmlspecialchars($jsonVersion) ?></strong>). Pembaruan direkomendasikan untuk mencegah inkonsistensi data.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <button id="btnRunUpdate" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm px-6 py-3 rounded-xl transition-all shadow-md shadow-amber-600/10 flex items-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-lg">system_update_alt</span>
                            Perbarui Aplikasi Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Latest Release Details -->
    <?php if ($latestChangelog): ?>
        <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-3xl p-6 shadow-sm">
            <h3 class="text-lg font-black text-on-surface mb-4 pb-2 border-b border-outline-variant/10 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">article</span>
                Catatan Rilis Versi <?= htmlspecialchars($jsonVersion) ?>
            </h3>

            <div class="space-y-6">
                <?php 
                $categories = [
                    'added' => ['title' => 'Added', 'color' => 'bg-green-500/10 text-green-700 border-green-500/20', 'icon' => 'add_circle'],
                    'improved' => ['title' => 'Improved', 'color' => 'bg-blue-500/10 text-blue-700 border-blue-500/20', 'icon' => 'trending_up'],
                    'fixed' => ['title' => 'Fixed', 'color' => 'bg-red-500/10 text-red-700 border-red-500/20', 'icon' => 'bug_report'],
                    'security' => ['title' => 'Security', 'color' => 'bg-purple-500/10 text-purple-700 border-purple-500/20', 'icon' => 'shield'],
                ];

                foreach ($categories as $key => $cat):
                    if (!empty($latestChangelog['summary'][$key])):
                ?>
                    <div>
                        <div class="inline-flex items-center gap-1.5 border rounded-lg px-2.5 py-0.5 text-xs font-bold mb-2 <?= $cat['color'] ?>">
                            <span class="material-symbols-outlined text-[15px]"><?= $cat['icon'] ?></span>
                            <?= $cat['title'] ?>
                        </div>
                        <ul class="list-disc pl-5 space-y-1 text-xs text-on-surface-variant leading-relaxed">
                            <?php foreach ($latestChangelog['summary'][$key] as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    const btnUpdate = document.getElementById('btnRunUpdate');
    if (btnUpdate) {
        btnUpdate.addEventListener('click', function() {
            // Check for fork warnings
            const currentTrack = '<?= preg_match("/^\\d+\\.\\d+\\.\\d+$/", $dbVersion) ? "Beta" : (strpos($dbVersion, "LTS") !== false ? "LTS" : (strpos($dbVersion, "STS") !== false ? "STS" : (preg_match("/^(local|tqa|stg|mtc)-/", $dbVersion) ? "Environment" : (strpos($dbVersion, "Belum") !== false ? "None" : "Pre-release")))) ?>';
            const targetTrack = '<?= preg_match("/^\\d+\\.\\d+\\.\\d+$/", $jsonVersion) ? "Beta" : (strpos($jsonVersion, "LTS") !== false ? "LTS" : (strpos($jsonVersion, "STS") !== false ? "STS" : (preg_match("/^(local|tqa|stg|mtc)-/", $jsonVersion) ? "Environment" : "Pre-release"))) ?>';
            
            const currentEdition = '<?= htmlspecialchars(strtolower($dbEdition)) ?>';
            const targetEdition = '<?= htmlspecialchars(strtolower($jsonEdition)) ?>';
            
            const currentRepo = '<?= htmlspecialchars(strtolower($dbRepoType)) ?>';
            const targetRepo = '<?= htmlspecialchars(strtolower($jsonRepoType)) ?>';
            
            let forkWarnings = [];
            
            // LTS <-> STS is considered same stable pathway, other combinations trigger warning
            const isCurrentStable = (currentTrack === 'LTS' || currentTrack === 'STS');
            const isTargetStable = (targetTrack === 'LTS' || targetTrack === 'STS');
            
            if (currentTrack !== 'None' && ((isCurrentStable && !isTargetStable) || (!isCurrentStable && isTargetStable) || (currentTrack === 'Beta' && targetTrack !== 'Beta') || (currentTrack !== 'Beta' && targetTrack === 'Beta'))) {
                forkWarnings.push(`Jalur Rilis: ${currentTrack} ➔ ${targetTrack}`);
            }
            
            if (currentEdition !== targetEdition) {
                forkWarnings.push(`Edisi: ${currentEdition.toUpperCase()} ➔ ${targetEdition.toUpperCase()}`);
            }
            
            if (currentRepo !== targetRepo) {
                forkWarnings.push(`Arsitektur Repo: ${currentRepo.toUpperCase()} ➔ ${targetRepo.toUpperCase()}`);
            }
            
            const runUpdateExecution = () => {
                // Show progress loader
                Swal.fire({
                    title: 'Sedang Memperbarui...',
                    text: 'Harap tunggu, memproses migrasi database, sinkronisasi changelog, dan mereset sesi aktif...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Trigger AJAX update request
                fetch('/superadmin/update/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                    },
                    body: 'csrf_token=' + encodeURIComponent('<?= csrf_token() ?>')
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Update Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => {
                            window.location.href = '/changelogs';
                        });
                    } else {
                        Swal.fire({
                            title: 'Update Gagal',
                            text: data.message,
                            icon: 'error',
                            confirmButtonColor: '#000666'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Terjadi Kesalahan',
                        text: 'Gagal menghubungi server untuk memproses pembaruan.',
                        icon: 'error',
                        confirmButtonColor: '#000666'
                    });
                });
            };
            
            if (forkWarnings.length > 0) {
                let warningsHtml = '';
                forkWarnings.forEach(w => {
                    warningsHtml += '<li>' + w + '</li>';
                });
                Swal.fire({
                    title: '⚠️ Peringatan Perubahan Jalur (Fork)',
                    html: '<div class="text-left text-sm space-y-2">' +
                             '<p class="font-semibold text-red-600">Sistem mendeteksi Anda memindahkan jalur/fork instalasi platform:</p>' +
                             '<ul class="list-disc pl-5 font-mono text-xs text-gray-700 bg-gray-50 p-2.5 rounded-lg border">' +
                               warningsHtml +
                             '</ul>' +
                             '<p class="mt-2 text-gray-600">Jalur-jalur ini memiliki struktur kode, skema database, dan konfigurasi environment yang berbeda.</p>' +
                             '<p class="font-bold text-gray-800">Apakah Anda yakin ingin mematikan jalur lama dan beralih ke jalur baru ini?</p>' +
                           '</div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d97706',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Ubah Jalur & Update',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        runUpdateExecution();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Konfirmasi Pembaruan Aplikasi',
                    text: 'Aplikasi akan diperbarui ke versi <?= htmlspecialchars($jsonVersion) ?>. Seluruh sesi pengguna lain akan direset (logout) dan database akan dimigrasikan. Apakah Anda yakin?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d97706',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Jalankan Update',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        runUpdateExecution();
                    }
                });
            }
        });
    }
</script>
