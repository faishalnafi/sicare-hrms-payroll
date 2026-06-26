<?php
$sessName  = $_SESSION['name'] ?? 'User';
$sessEmail = $_SESSION['email'] ?? '';
$sessRole  = $_SESSION['role'] ?? 'candidate';
$profilePic = $_SESSION['profile_picture'] ?? null;

$db = \App\Config\Database::getInstance()->getConnection();
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
$appLogoIcon = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_icon' LIMIT 1")->fetchColumn() ?: 'local_police';
$appLogoType = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_type' LIMIT 1")->fetchColumn() ?: 'icon';
$appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';
if (isset($_SESSION['user_id'])) {
    $userQuery = $db->prepare("SELECT profile_picture FROM users WHERE id = :id");
    $userQuery->execute(['id' => $_SESSION['user_id']]);
    $dbUser = $userQuery->fetch();
    if ($dbUser && !empty($dbUser['profile_picture'])) {
        $profilePic = $dbUser['profile_picture'];
    }
}

if (empty($profilePic)) {
    $hash = md5(strtolower(trim($sessEmail)));
    $profilePic = "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
}

$isReflectionIncomplete = false;
if ($sessRole === 'employee') {
    $currentPeriod = date('Y') . '-Q' . ceil(date('n') / 3);
    $userId = $_SESSION['user_id'] ?? '';
    if (!empty($userId)) {
        $stmtRefCheck = $db->prepare("SELECT COUNT(*) FROM self_reflections WHERE user_id = :user_id AND period = :period AND status IN ('submitted', 'completed')");
        $stmtRefCheck->execute(['user_id' => $userId, 'period' => $currentPeriod]);
        $isReflectionIncomplete = ($stmtRefCheck->fetchColumn() == 0);
    }
}


$roleFolder = $sessRole;
if ($sessRole === 'hiring_manager') $roleFolder = 'manager';
if ($sessRole === 'hr_ops') $roleFolder = 'hrops';

$sessRoleId = $_SESSION['role_id'] ?? null;
$sessDeptId = $_SESSION['department_id'] ?? null;
$userId     = $_SESSION['user_id'] ?? '';

if (empty($sessRoleId) && !empty($userId)) {
    try {
        $stmtUser = $db->prepare("SELECT role_id, department_id FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $userId]);
        $dbUser = $stmtUser->fetch();
        if ($dbUser) {
            $sessRoleId = $dbUser['role_id'];
            $sessDeptId = $dbUser['department_id'];
            $_SESSION['role_id'] = $sessRoleId;
            $_SESSION['department_id'] = $sessDeptId;
        }
    } catch (\Exception $e) {
        // Fail-safe
    }
}

$cache = \App\Config\SimpleCache::getInstance();
$cacheKey = 'sidebar_menus_' . md5(($sessRoleId ?? '') . '_' . ($sessDeptId ?? 'null'));
$menus = $cache->get($cacheKey);

if ($menus === null) {
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT sm.title, sm.icon, sm.url_route, sm.sort_order 
            FROM sys_menus sm
            INNER JOIN menu_permissions mp ON sm.id = mp.menu_id
            WHERE mp.role_id = :role_id 
              AND (mp.department_id = :dept_id OR mp.department_id IS NULL)
            ORDER BY sm.sort_order ASC, sm.title ASC
        ");
        $stmt->execute([
            'role_id' => $sessRoleId,
            'dept_id' => $sessDeptId
        ]);
        $dbMenus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $menus = array_map(function($m) {
            $link = $m['url_route'];
            if (!str_starts_with($link, '/')) {
                $link = '/' . $link;
            }
            return [
                'title' => $m['title'],
                'icon' => $m['icon'] ?? 'widgets',
                'link' => $link
            ];
        }, $dbMenus);

        $cache->set($cacheKey, $menus, 3600);
    } catch (\Exception $e) {
        $menus = [];
    }
}

// Filter out 'Pedoman Penomoran' menu if user is not admin or superadmin
if (isset($menus) && is_array($menus)) {
    $menus = array_filter($menus, function($m) use ($sessRole) {
        if (isset($m['title']) && strtolower($m['title']) === 'pedoman penomoran') {
            return in_array($sessRole, ['admin', 'superadmin']);
        }
        return true;
    });
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentUri = '/' . trim($requestUri, '/');
?>
<style>
/* Custom thin scrollbar with fade-in effect on hover */
#appSidebar nav::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}
#appSidebar nav::-webkit-scrollbar-track {
    background: transparent;
}
#appSidebar nav::-webkit-scrollbar-thumb {
    background: transparent;
    border-radius: 999px;
    transition: background 0.25s ease-in-out;
}
#appSidebar nav:hover::-webkit-scrollbar-thumb {
    background: rgba(0, 6, 102, 0.15);
}
#appSidebar nav::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 6, 102, 0.35);
}

/* Firefox Support */
#appSidebar nav {
    scrollbar-width: none;
}
#appSidebar nav:hover {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 6, 102, 0.15) transparent;
}
</style>
<aside id="appSidebar" class="fixed top-0 left-0 h-screen lg:top-4 lg:h-[calc(100vh-2rem)] w-72 lg:w-20 lg:hover:w-72 xl:w-72 bg-surface-container-lowest border-r lg:border border-outline-variant/15 lg:rounded-2xl flex flex-col justify-between py-6 px-4 lg:px-2 lg:hover:px-4 xl:px-4 z-40 -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out shadow-[4px_4px_30px_rgba(0,6,102,0.05)] group">
    <!-- Brand Logo, Desktop Toggle and Mobile Close Button -->
    <!-- Brand Logo, Desktop Toggle and Mobile Close Button -->
    <div class="brand-logo-container flex items-center justify-between px-3 lg:px-2 lg:group-hover:px-3 xl:px-3 mb-6 flex-shrink-0">
        <a href="/<?= $roleFolder ?>/dashboard" data-spa class="brand-logo-link text-2xl font-black text-primary font-headline tracking-tight flex items-center gap-2.5 hover:opacity-90 transition-opacity">
            <?php if ($appLogoType === 'image' && !empty($appLogoImage)): ?>
                <img src="<?= htmlspecialchars($appLogoImage) ?>" class="brand-logo-icon h-9 w-auto object-contain flex-shrink-0" alt="Logo" />
            <?php else: ?>
                <span class="brand-logo-icon material-symbols-outlined text-primary text-3xl font-bold flex-shrink-0"><?= htmlspecialchars($appLogoIcon) ?></span>
            <?php endif; ?>
            <span class="brand-text bg-gradient-to-r from-primary to-blue-800 bg-clip-text text-transparent whitespace-nowrap"><?= htmlspecialchars($appName) ?></span>
        </a>
        <!-- Desktop Sidebar Toggle (Gemini Style) -->
        <button id="desktopSidebarToggle" class="hidden lg:flex p-1.5 text-on-surface-variant hover:bg-surface-container-high rounded-lg transition-all items-center justify-center cursor-pointer select-none">
            <span class="material-symbols-outlined text-lg">side_navigation</span>
        </button>
        <!-- Close Button (Mobile Only, using matching Gemini style icon) -->
        <button id="mobileSidebarClose" class="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined">side_navigation</span>
        </button>
    </div>

    <!-- Scrollable Navigation Menu -->
    <nav class="flex-grow overflow-y-auto space-y-1.5 px-1 pr-2 -mr-2 scrollbar-thin scrollbar-thumb-surface-container-high scrollbar-track-transparent">
        <?php foreach($menus as $menu): 
            $cleanUri = rtrim($currentUri, '/');
            $cleanLink = rtrim($menu['link'], '/');
            $isActive = ($cleanUri === $cleanLink || str_ends_with($cleanUri, $cleanLink));
            
            // Check if menu is locked
            $isDisabled = ($isReflectionIncomplete && $menu['link'] !== '/employee/reflection');
            
            if ($isDisabled) {
                $activeClass = 'text-gray-400 bg-gray-100/50 cursor-not-allowed opacity-50 select-none';
                $linkHref = 'javascript:void(0);';
                $onclickAttr = 'onclick="Swal.fire({title: \'Refleksi Wajib Diisi\', text: \'Anda wajib menyelesaikan pengisian Refleksi Kinerja & Rencana Karir (IDP) terlebih dahulu sebelum dapat mengakses menu lainnya.\', icon: \'warning\', confirmButtonColor: \'#000666\'});"';
            } else {
                $activeClass = $isActive 
                    ? 'bg-primary/10 text-primary font-bold shadow-[0_4px_12px_rgba(0,6,102,0.02)]' 
                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-all duration-200';
                $linkHref = htmlspecialchars($menu['link']);
                $onclickAttr = '';
            }
        ?>
        <a href="<?php echo $linkHref; ?>" <?= $onclickAttr ?> <?php echo !$isDisabled ? 'data-spa' : ''; ?> class="block" data-tooltip="<?php echo htmlspecialchars($menu['title']); ?>">
            <div class="rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 group cursor-pointer transition-all duration-200 <?php echo $activeClass; ?>">
                <span class="material-symbols-outlined flex-shrink-0 transition-colors <?php echo $isActive ? 'text-primary' : 'text-on-surface-variant group-hover:text-primary'; ?>"><?php echo htmlspecialchars($menu['icon']); ?></span>
                <span class="text-sm font-medium flex-grow whitespace-nowrap transition-colors <?php echo $isActive ? 'text-primary' : 'text-on-surface-variant group-hover:text-primary'; ?>"><?php echo htmlspecialchars($menu['title']); ?></span>
                <span class="material-symbols-outlined text-sm flex-shrink-0 ml-auto <?php echo $isActive ? 'text-primary opacity-100' : 'text-primary/50 opacity-0 group-hover:opacity-100'; ?>">chevron_right</span>
            </div>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer: Profile and Sign Out -->
    <div class="border-t border-outline-variant/15 pt-4 mt-4 flex-shrink-0 space-y-3">

        <!-- Profile Widget -->
        <?php
        $profileFolder = $sessRole;
        if ($sessRole === 'hiring_manager') $profileFolder = 'manager';
        if ($sessRole === 'hr_ops') $profileFolder = 'hrops';
        ?>
        <a href="/<?= $profileFolder ?>/profile" data-spa class="block" data-tooltip="Profil: <?= htmlspecialchars($sessName) ?>">
            <div class="profile-widget-container flex items-center gap-3 p-2 rounded-xl hover:bg-surface-container-low transition-all duration-200 group/profile cursor-pointer">
                <div class="relative flex-shrink-0">
                    <?php $sessEmailHash = md5(strtolower(trim($sessEmail))); ?>
                    <img referrerpolicy="no-referrer" src="<?= htmlspecialchars($profilePic) ?>" onerror="window.handleAvatarError(this, '<?= $sessEmailHash ?>')" alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-outline-variant/20 shadow-sm" />
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-surface-container-lowest rounded-full"></span>
                </div>
                <div class="profile-details flex-grow min-w-0 whitespace-nowrap">
                    <h4 class="text-xs font-extrabold text-on-surface truncate"><?= htmlspecialchars($sessName) ?></h4>
                    <?php 
                    $roleLabel = str_replace('_', ' ', $sessRole);
                    if ($sessRole === 'employee') $roleLabel = 'Karyawan Aktif';
                    ?>
                    <p class="text-[9px] font-bold text-primary uppercase tracking-wider mt-0.5"><?= htmlspecialchars($roleLabel) ?></p>
                </div>
            </div>
        </a>

        <!-- Logout Button -->
        <button onclick="confirmAction('Keluar dari sistem?', 'Anda akan diarahkan ke halaman utama.', '/auth/logout', 'Ya, Keluar')" class="w-full text-left" data-tooltip="Keluar">
            <div class="rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 cursor-pointer bg-red-50 hover:bg-red-100/80 transition-all duration-200 border border-red-100">
                <span class="material-symbols-outlined text-red-600 flex-shrink-0">logout</span>
                <span class="text-sm font-bold text-red-600 whitespace-nowrap">Keluar</span>
                <span class="material-symbols-outlined text-sm text-red-400 ml-auto">chevron_right</span>
            </div>
        </button>
    </div>
</aside>


