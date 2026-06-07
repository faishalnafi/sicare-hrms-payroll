<?php
$db = \App\Config\Database::getInstance()->getConnection();
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
$appLogoIcon = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_icon' LIMIT 1")->fetchColumn() ?: 'local_police';
$appLogoType = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_type' LIMIT 1")->fetchColumn() ?: 'icon';
$appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';
?>
<!-- TopNavBar -->
<nav class="w-full top-0 sticky z-50 frosted-nav px-8 py-4 border-b border-white/20">
    <div class="max-w-6xl mx-auto w-full flex justify-between items-center">
        <div class="flex items-center gap-3">
            <?php if ($appLogoType === 'image' && !empty($appLogoImage)): ?>
                <div class="h-8 flex items-center justify-center shrink-0">
                    <img src="<?= htmlspecialchars($appLogoImage) ?>" class="h-8 w-auto object-contain" alt="Logo" />
                </div>
            <?php else: ?>
                <div class="w-8 h-8 bg-[#8efb8b] rounded-full flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-black text-[1rem]" style="font-variation-settings: 'FILL' 1;"><?= htmlspecialchars($appLogoIcon) ?></span>
                </div>
            <?php endif; ?>
            <a href="/" class="text-xl font-extrabold text-[#0c145e] tracking-tight font-headline"><?= htmlspecialchars($appName) ?></a>
        </div>
        
        <div class="flex items-center gap-6">
            <a href="/signin" class="flex items-center gap-2 text-[#0c145e] font-bold hover:text-blue-700 transition-colors text-sm">
                <span class="material-symbols-outlined text-lg">login</span>
                Masuk
            </a>
            <a href="/signup" class="px-6 py-2.5 bg-[#000666] text-white font-bold rounded-lg shadow hover:shadow-lg hover:bg-[#0c145e] transition-all text-sm">
                Daftar
            </a>
        </div>
    </div>
</nav>
