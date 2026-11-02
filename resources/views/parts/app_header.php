<?php
use App\Services\ViewHelper;

$sessName  = $_SESSION['name'] ?? 'User';
$sessEmail = $_SESSION['email'] ?? '';
$sessRole  = $_SESSION['role'] ?? 'Employee';
$profilePic = $_SESSION['profile_picture'] ?? null;

$appName      = ViewHelper::getSetting('app_name', 'siCare');
$appLogoIcon  = ViewHelper::getSetting('app_logo_icon', 'local_police');
$appLogoType  = ViewHelper::getSetting('app_logo_type', 'icon');
$appLogoImage = ViewHelper::getSetting('app_logo_image', '');

if (isset($_SESSION['user_id'])) {
    $userProf = ViewHelper::getUserProfile($_SESSION['user_id']);
    if ($userProf && !empty($userProf['profile_picture'])) {
        $profilePic = $userProf['profile_picture'];
    }
}

if (empty($profilePic)) {
    $hash = md5(strtolower(trim($sessEmail)));
    $profilePic = "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
}
?>
<header class="w-full top-0 sticky z-30 bg-[#f8f9fa]/95 backdrop-blur-sm border-b border-outline-variant/20 shadow-sm">
    <div class="flex justify-between items-center px-4 sm:px-6 lg:px-8 py-4 w-full">
        <!-- Left: Hamburger Toggle for Mobile, and Breadcrumb/Page title on Desktop -->
        <div class="flex items-center gap-3">
            <button id="mobileSidebarToggle" class="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <button id="desktopSidebarToggle" class="hidden lg:flex p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors items-center justify-center">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="hidden lg:flex items-center gap-2 text-on-surface-variant select-none">
                <span class="text-xs font-bold text-primary bg-primary/5 px-2.5 py-1 rounded-md uppercase tracking-wider"><?= htmlspecialchars($appName) ?> Portal</span>
                <span class="text-xs text-outline-variant">/</span>
                <span class="text-xs font-semibold text-on-surface-variant">Layanan Karyawan</span>
            </div>
            <!-- Small logo for Mobile -->
            <a href="/dashboard" data-spa class="lg:hidden text-xl font-black text-[#000666] font-headline tracking-tight flex items-center gap-1.5 hover:opacity-90 transition-opacity">
                <?php if ($appLogoType === 'image' && !empty($appLogoImage)): ?>
                    <img src="<?= htmlspecialchars($appLogoImage) ?>" class="h-6 w-auto object-contain flex-shrink-0" alt="Logo" />
                <?php else: ?>
                    <span class="material-symbols-outlined text-primary text-2xl"><?= htmlspecialchars($appLogoIcon) ?></span>
                <?php endif; ?>
                <?= htmlspecialchars($appName) ?>
            </a>
        </div>
        
        <div class="flex items-center gap-4">
            <button class="p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined">notifications</span>
            </button>
            <div class="flex items-center gap-3 pl-4 border-l border-outline-variant/30">
                <div class="text-right hidden sm:block">
                    <p class="text-[10px] font-black text-primary uppercase tracking-wider"><?php echo htmlspecialchars($sessRole); ?></p>
                    <p class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($sessName); ?></p>
                </div>
                <?php $sessEmailHash = md5(strtolower(trim($sessEmail))); ?>
                <img referrerpolicy="no-referrer" alt="User Avatar" class="w-10 h-10 rounded-full border-2 border-primary/20 shadow-sm object-cover bg-white" src="<?php echo htmlspecialchars($profilePic); ?>" onerror="window.handleAvatarError(this, '<?= $sessEmailHash ?>')" />
            </div>
        </div>
    </div>
</header>
