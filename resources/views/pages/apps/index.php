<?php
$sessName  = $_SESSION['name'] ?? 'User';
$sessRole  = $_SESSION['role'] ?? 'candidate';

$roleLabels = [
    'employee' => 'Portal Karyawan',
    'hr_ops' => 'Portal HR Ops',
    'hiring_manager' => 'Portal Manager',
    'candidate' => 'Portal Kandidat',
    'recruiter' => 'Portal Rekruter',
    'admin' => 'Portal Admin',
    'executive' => 'Portal Eksekutif',
    'superadmin' => 'Portal Superadmin'
];
$portalLabel = $roleLabels[$sessRole] ?? 'Portal Karyawan';

use App\Services\ViewHelper;

[$rolesList, $deptsList, $apps] = ViewHelper::getPortalAppsData($sessRole, $_SESSION['department_id'] ?? null);

// Helper to convert hex to RGB
if (!function_exists('hexToRgb')) {
    function hexToRgb($hex) {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return [$r, $g, $b];
    }
}
?>

<?php if ($sessRole === 'superadmin' || $sessRole === 'admin'): ?>
<div class="space-y-6">
    <!-- Header Banner exactly like Gambar 1 -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-6 border-b border-outline-variant/15">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl font-black text-primary tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-[1.6rem] font-bold">apps</span> Aplikasi Portal
            </h1>
            <p class="text-on-surface-variant font-medium text-xs">Aplikasi yang ditampilkan di halaman depan portal publik.</p>
        </div>
        <div>
            <button onclick="openAppModal()" class="bg-[#000666] hover:bg-[#000666]/95 text-white font-extrabold text-xs py-3 px-6 rounded-xl flex items-center gap-2 transition-all shadow-md shadow-[#000666]/10 cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">add</span> Tambah Aplikasi
            </button>
        </div>
    </div>

    <!-- Tabs switcher for admin previewing/managing -->
    <div class="flex items-center bg-surface-container-low border border-outline-variant/20 rounded-2xl p-1 self-start inline-flex">
        <button onclick="switchAdminTab('manage')" id="tabBtnManage" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all bg-primary text-white shadow-sm">
            <span class="material-symbols-outlined text-sm">apps</span> Kelola Aplikasi
        </button>
        <button onclick="switchAdminTab('preview')" id="tabBtnPreview" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all text-on-surface-variant hover:bg-surface-container-high/50">
            <span class="material-symbols-outlined text-sm">visibility</span> Pratinjau Portal
        </button>
    </div>

    <!-- Tab 1: Manage applications -->
    <div id="tabContentManage" class="tab-content-admin animate-fade-in space-y-6">
        <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full text-left border-collapse table-standardized">
                    <thead>
                        <tr class="bg-surface-container-low/30 border-b border-outline-variant/10 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                            <th class="py-4 px-6 w-16 text-center">Urutan</th>
                            <th class="py-4 px-6 w-20">Logo/Icon</th>
                            <th class="py-4 px-6 w-48">Nama Aplikasi</th>
                            <th class="py-4 px-6">Deskripsi Singkat</th>
                            <th class="py-4 px-6 w-36">Tautan Portal</th>
                            <th class="py-4 px-6 w-36">Tautan Login</th>
                            <th class="py-4 px-6 text-center w-28">Visibilitas</th>
                            <th class="py-4 px-6 text-center w-24">Status</th>
                            <th class="py-4 px-6 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 text-xs font-semibold text-on-surface">
                        <?php if (empty($apps)): ?>
                        <tr>
                            <td colspan="9" class="py-12 px-6 text-center text-on-surface-variant font-medium">
                                <span class="material-symbols-outlined text-3xl text-outline-variant block mb-2">grid_off</span>
                                Belum ada aplikasi yang terdaftar. Klik "+ Tambah Aplikasi" untuk mulai.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($apps as $a): ?>
                            <tr class="hover:bg-surface-container-low/20 transition-colors">
                                <td class="py-4 px-6 text-center text-on-surface-variant font-mono"><?= $a['sort_order'] ?></td>
                                <td class="py-4 px-6">
                                    <?php
                                        $hex = $a['icon_color'] ?: '#3b82f6';
                                        $rgb = hexToRgb($hex);
                                        $bgStyle = "background-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 0.1);";
                                        $borderStyle = "border-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 0.25);";
                                        $textStyle = "color: {$hex};";
                                    ?>
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-sm flex-shrink-0" style="<?= $bgStyle ?> <?= $borderStyle ?> <?= $textStyle ?>">
                                        <?php if (!empty($a['logo_url'])): ?>
                                            <img src="<?= htmlspecialchars($a['logo_url']) ?>" class="w-7 h-7 object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
                                            <span class="material-symbols-outlined text-lg" style="display:none;"><?= htmlspecialchars($a['icon_material'] ?: 'apps') ?></span>
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-lg"><?= htmlspecialchars($a['icon_material'] ?: 'apps') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6 font-bold text-sm text-[#0c145e]"><?= htmlspecialchars($a['name']) ?></td>
                                <td class="py-4 px-6 text-on-surface-variant font-medium max-w-[200px] truncate" title="<?= htmlspecialchars($a['description']) ?>"><?= htmlspecialchars($a['description'] ?: '-') ?></td>
                                <td class="py-4 px-6 truncate max-w-[150px]">
                                    <?= $a['portal_url'] ? '<a href="'.htmlspecialchars($a['portal_url']).'" target="_blank" class="text-primary hover:underline flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">open_in_new</span> Buka Portal</a>' : '<span class="text-neutral-400 font-medium">-</span>' ?>
                                </td>
                                <td class="py-4 px-6 truncate max-w-[150px]">
                                    <?= $a['login_url'] ? '<a href="'.htmlspecialchars($a['login_url']).'" class="text-primary hover:underline flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">login</span> Login</a>' : '<span class="text-neutral-400 font-medium">-</span>' ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if (($a['visibility_type'] ?? 'global') === 'global'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">Global</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 cursor-help" title="Roles: <?= htmlspecialchars($a['allowed_roles'] ?: 'Semua') ?>&#10;Depts: <?= htmlspecialchars($a['allowed_departments'] ?: 'Semua') ?>">Terbatas</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($a['is_active']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">Aktif</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-neutral-50 text-neutral-500 border border-neutral-200">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick='editApp(<?= json_encode($a) ?>)' class="action-btn text-primary hover:text-primary/80 hover:bg-primary/5 p-1.5 rounded-full transition-colors flex items-center justify-center cursor-pointer" title="Edit">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                        <button onclick="deleteApp('<?= $a['id'] ?>', '<?= addslashes($a['name']) ?>')" class="action-btn text-red-600 hover:text-red-700 hover:bg-red-50 p-1.5 rounded-full transition-colors flex items-center justify-center cursor-pointer" title="Hapus">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Preview Portal layout -->
    <div id="tabContentPreview" class="tab-content-admin hidden animate-fade-in space-y-6">
        <?php if (empty(array_filter($apps, fn($ap) => $ap['is_active'] == 1))): ?>
        <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-3xl p-12 text-center min-h-[300px] flex flex-col items-center justify-center">
            <span class="material-symbols-outlined mb-3 text-on-surface-variant opacity-40 font-normal select-none" style="font-size: 52px;">apps</span>
            <h3 class="text-base font-bold text-on-surface font-headline mb-1">Pratinjau Portal Kosong</h3>
            <p class="text-on-surface-variant text-xs leading-relaxed max-w-sm">
                Belum ada aplikasi aktif untuk ditampilkan ke pengguna umum. Aktifkan salah satu aplikasi di tab kelola untuk melihat pratinjaunya.
            </p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($apps as $app): ?>
            <?php if (!$app['is_active']) continue; ?>
            <?php
                $hex = $app['icon_color'] ?: '#3b82f6';
                $rgb = hexToRgb($hex);
                $bgStyle = "background-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 0.1);";
                $borderStyle = "border-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 0.25);";
                $textStyle = "color: {$hex};";
            ?>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 hover:shadow-[0_12px_40px_rgba(0,0,0,0.08)] hover:border-gray-200/50 hover:scale-[1.03] transition-all duration-300 flex flex-col h-full">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border shadow-sm flex-shrink-0 font-bold" style="<?= $bgStyle ?> <?= $borderStyle ?> <?= $textStyle ?>">
                    <?php if (!empty($app['logo_url'])): ?>
                        <img src="<?= htmlspecialchars($app['logo_url']) ?>" class="w-8 h-8 object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
                        <span class="material-symbols-outlined text-[1.4rem]" style="display:none;"><?= htmlspecialchars($app['icon_material'] ?: 'apps') ?></span>
                    <?php else: ?>
                        <span class="material-symbols-outlined text-[1.4rem]"><?= htmlspecialchars($app['icon_material'] ?: 'apps') ?></span>
                    <?php endif; ?>
                </div>
                
                <h3 class="text-base font-headline font-bold text-[#0c145e] mb-2"><?= htmlspecialchars($app['name']) ?></h3>
                <p class="text-[0.8rem] text-gray-500 leading-relaxed mb-6 flex-grow"><?= htmlspecialchars($app['description']) ?></p>
                
                <div class="pt-4 border-t border-gray-50 mt-auto flex flex-col gap-2.5">
                    <?php if (!empty($app['login_url'])): ?>
                    <a href="<?= htmlspecialchars($app['login_url']) ?>" target="_self" class="text-[#0c145e] text-xs font-bold flex items-center gap-1.5 hover:text-blue-700 transition-colors">
                        <span class="material-symbols-outlined text-[1.1rem]">login</span> Login Aplikasi
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($app['portal_url'])): ?>
                    <a href="<?= htmlspecialchars($app['portal_url']) ?>" target="_blank" class="text-[#0c145e] text-xs font-bold flex items-center gap-1.5 hover:text-blue-700 transition-colors">
                        <span class="material-symbols-outlined text-[1.1rem]">open_in_new</span> Buka Portal
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form Add/Edit App exactly like Gambar 2 but with custom picker and visibility settings -->
<div id="appModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-xl mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="appModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <h3 id="appModalTitle" class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_card</span> Tambah Aplikasi
            </h3>
            <button onclick="closeAppModal()" class="p-1.5 rounded-full text-on-surface-variant hover:bg-surface-container-high transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <form id="appForm" onsubmit="saveApp(event)" class="p-6 space-y-4 text-xs font-semibold text-on-surface overflow-y-auto flex-grow">
            <input type="hidden" name="id" id="app_id" value="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Nama Aplikasi <span class="text-red-600">*</span></label>
                    <input type="text" name="name" id="app_name" required placeholder="contoh: Google Classroom" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs" />
                </div>
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Urutan (sort)</label>
                    <input type="number" name="sort_order" id="app_sort_order" value="0" min="0" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs" />
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-on-surface-variant">Deskripsi Singkat</label>
                <textarea name="description" id="app_description" rows="2" placeholder="Deskripsi singkat yang tampil di kartu portal..." class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">URL Logo <span class="text-neutral-400 font-medium">(opsional)</span></label>
                    <input type="text" name="logo_url" id="app_logo_url" placeholder="https://example.com/logo.png" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs" />
                    <p class="text-[9px] text-neutral-400 font-medium mt-0.5">Jika kosong, akan menggunakan icon Material di bawah.</p>
                </div>
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Unggah Logo <span class="text-neutral-400 font-medium">(opsional, maks 1MB)</span></label>
                    <div id="logo_dropzone" class="relative border border-dashed border-outline-variant/30 hover:border-primary/50 rounded-xl p-1 bg-surface-container-low flex flex-col items-center justify-center text-center cursor-pointer transition-all h-[42px] overflow-hidden" onclick="document.getElementById('app_logo_file').click()">
                        <input type="file" name="logo_file" id="app_logo_file" accept="image/png, image/jpeg, image/gif, image/svg+xml, image/webp" class="hidden" onchange="handleFileSelect(this)" />
                        <div id="dropzone_prompt" class="flex items-center gap-1.5 text-on-surface-variant text-[10px] font-bold">
                            <span class="material-symbols-outlined text-sm text-[#000666]">upload_file</span>
                            <span>Choose File atau drag & drop file</span>
                        </div>
                        <div id="dropzone_filename" class="hidden flex items-center gap-1.5 text-primary text-[10px] font-bold w-full px-2 justify-center">
                            <span class="material-symbols-outlined text-sm text-green-600 flex-shrink-0">check_circle</span>
                            <span id="uploaded_file_name_text" class="truncate max-w-[120px]">No file chosen</span>
                            <button type="button" onclick="clearFileSelection(event)" class="text-red-500 hover:text-red-700 ml-1 flex items-center justify-center flex-shrink-0"><span class="material-symbols-outlined text-xs">close</span></button>
                        </div>
                    </div>
                    <p class="text-[9px] text-neutral-400 font-medium mt-0.5">Format: PNG, JPG, JPEG, GIF, SVG, WebP.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Icon Material <span class="text-neutral-400 font-medium">(fallback)</span></label>
                    <input type="text" name="icon_material" id="app_icon_material" value="apps" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs" />
                    <p class="text-[10px] text-neutral-400 font-medium mt-0.5">Lihat: <a href="https://fonts.google.com/icons" target="_blank" class="text-primary hover:underline">Material Icons</a></p>
                </div>
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Warna Icon (Tidak Terbatas)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="app_icon_color_picker" oninput="document.getElementById('app_icon_color').value = this.value" class="w-12 h-10 border border-outline-variant/30 rounded-xl p-0.5 cursor-pointer bg-surface-container-low" value="#3b82f6">
                        <input type="text" name="icon_color" id="app_icon_color" value="#3b82f6" oninput="document.getElementById('app_icon_color_picker').value = this.value" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs" placeholder="#3b82f6" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">URL Portal</label>
                    <input type="text" name="portal_url" id="app_portal_url" placeholder="https://app.example.com" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs" />
                    <p class="text-[10px] text-neutral-400 font-medium mt-0.5">Tombol "Buka Portal" di kartu.</p>
                </div>
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">URL Login</label>
                    <input type="text" name="login_url" id="app_login_url" placeholder="https://app.example.com/login" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs" />
                    <p class="text-[10px] text-neutral-400 font-medium mt-0.5">Tombol "Login Aplikasi" di kartu.</p>
                </div>
            </div>

            <!-- Visibility settings -->
            <div class="space-y-3 pt-2 border-t border-outline-variant/10">
                <div class="space-y-1">
                    <label class="block text-on-surface-variant">Target Visibilitas</label>
                    <select name="visibility_type" id="app_visibility_type" onchange="toggleVisibilityFields()" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs">
                        <option value="global">Global (Semua Karyawan)</option>
                        <option value="restricted">Spesifik (Pilih Departemen/Role)</option>
                    </select>
                </div>

                <div id="visibility_settings_panel" class="hidden space-y-4 border border-outline-variant/10 rounded-2xl p-4 bg-surface-container-low/40">
                    <div class="space-y-2">
                        <label class="block text-on-surface-variant text-[11px]">Batasi Berdasarkan Role</label>
                        <div class="grid grid-cols-2 gap-2 bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/10 max-h-[120px] overflow-y-auto">
                            <?php foreach ($rolesList as $roleItem): ?>
                            <?php
                                $roleCode = $roleItem['code'] ?? '';
                                $roleName = $roleItem['name'] ?? '';
                            ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:text-primary transition-colors text-[10px]">
                                <input type="checkbox" name="allowed_roles[]" value="<?= htmlspecialchars($roleCode) ?>" class="rounded text-primary focus:ring-primary h-3.5 w-3.5" />
                                <span><?= htmlspecialchars($roleName) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[10px] text-neutral-400 font-medium">Jika tidak ada role yang dicentang, semua role otomatis dapat mengakses.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-on-surface-variant text-[11px]">Batasi Berdasarkan Departemen</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/10 max-h-[120px] overflow-y-auto">
                            <?php foreach ($deptsList as $deptItem): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:text-primary transition-colors text-[10px] truncate" title="<?= htmlspecialchars($deptItem['name']) ?>">
                                <input type="checkbox" name="allowed_departments[]" value="<?= htmlspecialchars($deptItem['id']) ?>" class="rounded text-primary focus:ring-primary h-3.5 w-3.5" />
                                <span><?= htmlspecialchars($deptItem['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[10px] text-neutral-400 font-medium">Jika tidak ada departemen yang dicentang, semua departemen otomatis dapat mengakses.</p>
                    </div>
                </div>
            </div>

            <!-- Toggle switch for is_active -->
            <div class="pt-2">
                <label class="flex items-center gap-3 p-3 bg-surface-container-low rounded-xl border border-outline-variant/10 cursor-pointer hover:bg-surface-container-high/40 transition-colors">
                    <input type="checkbox" name="is_active" id="app_is_active" value="1" checked class="sr-only peer" />
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    <span class="text-on-surface font-extrabold text-xs">Aktifkan di portal</span>
                </label>
            </div>

            <!-- Action buttons -->
            <div class="flex justify-end gap-2 pt-4 border-t border-outline-variant/15">
                <button type="button" onclick="closeAppModal()" class="px-5 py-2.5 bg-surface-container-high hover:bg-surface-container-high/80 text-on-surface-variant font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#000666] hover:bg-[#000666]/95 text-white font-bold text-xs rounded-xl shadow transition-all cursor-pointer">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.openAppModal = function() {
    document.getElementById('appModalTitle').innerHTML = '<span class="material-symbols-outlined text-primary">add_card</span> Tambah Aplikasi';
    document.getElementById('appForm').reset();
    document.getElementById('app_id').value = '';
    document.getElementById('app_is_active').checked = true;
    document.getElementById('app_visibility_type').value = 'global';
    document.getElementById('app_icon_color').value = '#3b82f6';
    document.getElementById('app_icon_color_picker').value = '#3b82f6';
    clearFileSelection(null);
    toggleVisibilityFields();
    
    // Clear all visibility checkboxes
    document.querySelectorAll('input[name="allowed_roles[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="allowed_departments[]"]').forEach(cb => cb.checked = false);
    
    const modal = document.getElementById('appModal');
    const container = document.getElementById('appModalContainer');
    modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }, 10);
}

window.closeAppModal = function() {
    const modal = document.getElementById('appModal');
    const container = document.getElementById('appModalContainer');
    modal.classList.remove('opacity-100');
    container.classList.remove('scale-100');
    container.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden', 'opacity-0', 'pointer-events-none');
        modal.classList.remove('flex');
    }, 300);
}

window.editApp = function(app) {
    document.getElementById('appModalTitle').innerHTML = '<span class="material-symbols-outlined text-primary">edit</span> Edit Aplikasi';
    document.getElementById('app_id').value = app.id;
    document.getElementById('app_name').value = app.name;
    document.getElementById('app_sort_order').value = app.sort_order;
    document.getElementById('app_description').value = app.description || '';
    document.getElementById('app_logo_url').value = app.logo_url || '';
    clearFileSelection(null);
    document.getElementById('app_icon_material').value = app.icon_material || 'apps';
    document.getElementById('app_icon_color').value = app.icon_color || '#3b82f6';
    document.getElementById('app_icon_color_picker').value = app.icon_color || '#3b82f6';
    document.getElementById('app_portal_url').value = app.portal_url || '';
    document.getElementById('app_login_url').value = app.login_url || '';
    document.getElementById('app_is_active').checked = app.is_active == 1;
    document.getElementById('app_visibility_type').value = app.visibility_type || 'global';
    
    toggleVisibilityFields();

    // Populate roles checkboxes
    const rolesCheckboxes = document.querySelectorAll('input[name="allowed_roles[]"]');
    rolesCheckboxes.forEach(cb => cb.checked = false);
    if (app.allowed_roles) {
        const allowedRoles = app.allowed_roles.split(',');
        rolesCheckboxes.forEach(cb => {
            if (allowedRoles.includes(cb.value)) {
                cb.checked = true;
            }
        });
    }

    // Populate depts checkboxes
    const deptsCheckboxes = document.querySelectorAll('input[name="allowed_departments[]"]');
    deptsCheckboxes.forEach(cb => cb.checked = false);
    if (app.allowed_departments) {
        const allowedDepts = app.allowed_departments.split(',');
        deptsCheckboxes.forEach(cb => {
            if (allowedDepts.includes(cb.value)) {
                cb.checked = true;
            }
        });
    }

    const modal = document.getElementById('appModal');
    const container = document.getElementById('appModalContainer');
    modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }, 10);
}

window.toggleVisibilityFields = function() {
    const visibilityType = document.getElementById('app_visibility_type').value;
    const panel = document.getElementById('visibility_settings_panel');
    if (visibilityType === 'restricted') {
        panel.classList.remove('hidden');
    } else {
        panel.classList.add('hidden');
    }
}

window.saveApp = function(event) {
    event.preventDefault();
    Swal.showLoading();
    
    const form = document.getElementById('appForm');
    const formData = new FormData(form);
    formData.append('is_active', document.getElementById('app_is_active').checked ? '1' : '0');
    
    const jwtToken = getCookie('jwt_token') || '<?= $_SESSION['jwt_token'] ?? '' ?>';
    
    fetch('/admin/apps/save', {
        method: 'POST',
        headers: {
            'X-JWT-Token': jwtToken
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#000666'
            }).then(() => {
                closeAppModal();
                window.loadPage(window.location.pathname);
            });
        } else {
            Swal.fire({
                title: 'Gagal!',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#000666'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            title: 'Error!',
            text: 'Terjadi kesalahan sistem saat menghubungi server.',
            icon: 'error',
            confirmButtonColor: '#000666'
        });
    });
}

window.deleteApp = function(id, name) {
    Swal.fire({
        title: 'Hapus Aplikasi?',
        text: 'Apakah Anda yakin ingin menghapus aplikasi "' + name + '"? Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#000666',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.showLoading();
            const formData = new FormData();
            formData.append('id', id);
            
            const jwtToken = getCookie('jwt_token') || '<?= $_SESSION['jwt_token'] ?? '' ?>';
            
            fetch('/admin/apps/delete', {
                method: 'POST',
                headers: {
                    'X-JWT-Token': jwtToken
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Dihapus!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#000666'
                    }).then(() => {
                        window.loadPage(window.location.pathname);
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#000666'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan sistem saat menghubungi server.',
                    icon: 'error',
                    confirmButtonColor: '#000666'
                });
            });
        }
    });
}

window.getCookie = function(name) {
    let value = "; " + document.cookie;
    let parts = value.split("; " + name + "=");
    if (parts.length == 2) return parts.pop().split(";").shift();
}

window.switchAdminTab = function(tab) {
    const manageBtn = document.getElementById('tabBtnManage');
    const previewBtn = document.getElementById('tabBtnPreview');
    const manageContent = document.getElementById('tabContentManage');
    const previewContent = document.getElementById('tabContentPreview');
    
    if (tab === 'manage') {
        manageBtn.classList.add('bg-primary', 'text-white', 'shadow-sm');
        manageBtn.classList.remove('text-on-surface-variant', 'hover:bg-surface-container-high/50');
        previewBtn.classList.remove('bg-primary', 'text-white', 'shadow-sm');
        previewBtn.classList.add('text-on-surface-variant', 'hover:bg-surface-container-high/50');
        
        manageContent.classList.remove('hidden');
        previewContent.classList.add('hidden');
    } else {
        previewBtn.classList.add('bg-primary', 'text-white', 'shadow-sm');
        previewBtn.classList.remove('text-on-surface-variant', 'hover:bg-surface-container-high/50');
        manageBtn.classList.remove('bg-primary', 'text-white', 'shadow-sm');
        manageBtn.classList.add('text-on-surface-variant', 'hover:bg-surface-container-high/50');
        
        previewContent.classList.remove('hidden');
        manageContent.classList.add('hidden');
    }
}

window.setupLogoDropzone = function() {
    const dropzone = document.getElementById('logo_dropzone');
    const fileInput = document.getElementById('app_logo_file');
    if (!dropzone || !fileInput) return;

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
            dropzone.classList.add('border-primary', 'bg-primary/5');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
            dropzone.classList.remove('border-primary', 'bg-primary/5');
        }, false);
    });

    // Handle dropped files
    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect(fileInput);
        }
    }, false);
}

window.handleFileSelect = function(input) {
    const prompt = document.getElementById('dropzone_prompt');
    const filenameDiv = document.getElementById('dropzone_filename');
    const filenameText = document.getElementById('uploaded_file_name_text');
    
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        
        // Front-end size validation (max 1MB)
        if (file.size > 1048576) {
            Swal.fire({
                title: 'Ukuran File Terlalu Besar!',
                text: 'Kapasitas file logo maksimal adalah 1MB.',
                icon: 'error',
                confirmButtonColor: '#000666'
            });
            input.value = '';
            clearFileSelection(null);
            return;
        }

        // Front-end type validation
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp'];
        if (!allowedTypes.includes(file.type) && !file.name.endsWith('.svg')) {
            Swal.fire({
                title: 'Format File Tidak Didukung!',
                text: 'Hanya berkas PNG, JPG, JPEG, GIF, SVG, atau WebP yang diperbolehkan.',
                icon: 'error',
                confirmButtonColor: '#000666'
            });
            input.value = '';
            clearFileSelection(null);
            return;
        }

        filenameText.innerText = file.name;
        prompt.classList.add('hidden');
        filenameDiv.classList.remove('hidden');
        filenameDiv.classList.add('flex');
    } else {
        clearFileSelection(null);
    }
}

window.clearFileSelection = function(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const input = document.getElementById('app_logo_file');
    if (input) input.value = '';
    
    const prompt = document.getElementById('dropzone_prompt');
    const filenameDiv = document.getElementById('dropzone_filename');
    const filenameText = document.getElementById('uploaded_file_name_text');
    
    if (prompt && filenameDiv && filenameText) {
        filenameText.innerText = 'No file chosen';
        filenameDiv.classList.add('hidden');
        filenameDiv.classList.remove('flex');
        prompt.classList.remove('hidden');
    }
}

// Initialize immediately
setupLogoDropzone();
</script>

<?php else: ?>
<!-- Standard view for other roles -->
<?php if (empty($apps)): ?>
<div class="space-y-6 animate-fade-in">
    <!-- Title -->
    <div class="space-y-1">
        <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Aplikasi & Ekosistem Modul</h1>
        <p class="text-on-surface-variant font-medium text-sm">Modul ini masih dalam tahap pengembangan (Placeholder).</p>
    </div>

    <!-- Center Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-3xl p-12 sm:p-16 shadow-sm flex flex-col items-center justify-center text-center min-h-[320px]">
        <span class="material-symbols-outlined mb-4 text-on-surface-variant opacity-40 font-normal select-none" style="font-size: 52px; font-variation-settings: 'FILL' 0, 'wght' 300;">apps</span>
        
        <h3 class="text-xl font-bold text-on-surface font-headline mb-2">Belum Ada Aplikasi</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed max-w-md">
            Mohon maaf, saat ini belum ada aplikasi eksternal atau modul tambahan yang ditambahkan oleh <strong>Administrator Sistem</strong> ataupun <strong>Manajemen Atasan</strong> untuk <?= htmlspecialchars($portalLabel) ?>.
        </p>
    </div>
</div>
<?php else: ?>
<div class="space-y-8 animate-fade-in">
    <!-- Header Banner -->
    <div class="space-y-1">
        <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Aplikasi & Ekosistem Modul</h1>
        <p class="text-on-surface-variant font-medium text-sm">Temukan dan akses aplikasi eksternal serta modul tambahan yang ditambahkan oleh manajemen.</p>
    </div>

    <!-- Application Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($apps as $app): ?>
        <?php
            $hex = $app['icon_color'] ?: '#3b82f6';
            $rgb = hexToRgb($hex);
            $bgStyle = "background-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 0.1);";
            $borderStyle = "border-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 0.25);";
            $textStyle = "color: {$hex};";
        ?>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 hover:shadow-[0_12px_40px_rgba(0,0,0,0.08)] hover:border-gray-200/50 hover:scale-[1.03] transition-all duration-300 flex flex-col h-full">
            <!-- Icon with rounded box -->
            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 border shadow-sm flex-shrink-0 font-bold" style="<?= $bgStyle ?> <?= $borderStyle ?> <?= $textStyle ?>">
                <?php if (!empty($app['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($app['logo_url']) ?>" class="w-8 h-8 object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
                    <span class="material-symbols-outlined text-[1.4rem]" style="display:none;"><?= htmlspecialchars($app['icon_material'] ?: 'apps') ?></span>
                <?php else: ?>
                    <span class="material-symbols-outlined text-[1.4rem]"><?= htmlspecialchars($app['icon_material'] ?: 'apps') ?></span>
                <?php endif; ?>
            </div>
            
            <h3 class="text-base font-headline font-bold text-[#0c145e] mb-2"><?= htmlspecialchars($app['name']) ?></h3>
            <p class="text-[0.8rem] text-gray-500 leading-relaxed mb-6 flex-grow"><?= htmlspecialchars($app['description']) ?></p>
            
            <!-- Links stacked vertically as requested: login at top (same tab), portal at bottom (new tab) -->
            <div class="pt-4 border-t border-gray-50 mt-auto flex flex-col gap-2.5">
                <?php if (!empty($app['login_url'])): ?>
                <a href="<?= htmlspecialchars($app['login_url']) ?>" target="_self" class="text-[#0c145e] text-xs font-bold flex items-center gap-1.5 hover:text-blue-700 transition-colors">
                    <span class="material-symbols-outlined text-[1.1rem]">login</span> Login Aplikasi
                </a>
                <?php endif; ?>
                <?php if (!empty($app['portal_url'])): ?>
                <a href="<?= htmlspecialchars($app['portal_url']) ?>" target="_blank" class="text-[#0c145e] text-xs font-bold flex items-center gap-1.5 hover:text-blue-700 transition-colors">
                    <span class="material-symbols-outlined text-[1.1rem]">open_in_new</span> Buka Portal
                </a>
                <?php endif; ?>
                <?php if (empty($app['login_url']) && empty($app['portal_url'])): ?>
                <span class="text-neutral-400 text-xs italic flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[1.1rem]">link_off</span> Tidak ada tautan
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
