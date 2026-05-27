<?php 
require __DIR__ . '/../../parts/app_sidebar.php'; 
?>
<div class="p-4 sm:p-8 space-y-6 max-w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/10 backdrop-blur-md p-6 rounded-2xl border border-white/20 shadow-xl">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Menu Privilege Mapping</h1>
            <p class="text-gray-300 mt-1 text-sm font-medium">Tautkan menu sistem global ke departemen eksekutif spesifik (C-Level).</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php if(empty($departments)): ?>
            <div class="lg:col-span-2 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 text-center shadow-xl">
                <span class="material-symbols-outlined text-gray-400 text-5xl mb-3 block">domain_disabled</span>
                <h3 class="text-lg font-semibold text-white">Tidak ada entitas eksekutif</h3>
                <p class="text-sm text-gray-400 mt-2">Belum ada departemen yang dikonfigurasi dengan <code class="bg-gray-800 px-1 py-0.5 rounded text-pink-300">is_executive_entity = TRUE</code>. Silakan buat/update departemen C-Level terlebih dahulu.</p>
            </div>
        <?php else: ?>
            <?php foreach ($departments as $dept): ?>
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-6 shadow-xl flex flex-col h-full">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4 mb-4">
                    <div class="p-3 rounded-xl bg-blue-500/20 text-blue-300 border border-blue-500/30">
                        <span class="material-symbols-outlined">shield_person</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white"><?= htmlspecialchars($dept['name']) ?></h2>
                        <span class="text-xs font-mono text-gray-400 uppercase tracking-widest">Executive Entity</span>
                    </div>
                </div>

                <form class="flex-1 flex flex-col space-y-3 mapping-form" data-dept-id="<?= $dept['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= \App\Middleware\SecurityMiddleware::getCsrfToken() ?>">
                    <?php 
                        $assignedMenus = $assignmentMap[$dept['id']] ?? [];
                    ?>
                    <div class="space-y-2 flex-1">
                        <?php foreach ($systemMenus as $menu): ?>
                            <?php $isChecked = in_array($menu['id'], $assignedMenus); ?>
                            <label class="flex items-start gap-3 p-3 rounded-lg border <?= $isChecked ? 'border-blue-500/40 bg-blue-500/10' : 'border-white/5 bg-white/5' ?> hover:bg-white/10 cursor-pointer transition-all">
                                <div class="mt-0.5">
                                    <input type="checkbox" name="menu_ids[]" value="<?= $menu['id'] ?>" <?= $isChecked ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500/50">
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-white text-sm"><?= htmlspecialchars($menu['menu_name']) ?></div>
                                    <div class="text-xs text-gray-400 mt-0.5 leading-relaxed"><?= htmlspecialchars($menu['description']) ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="pt-4 mt-auto">
                        <button type="submit" class="w-full py-2.5 bg-blue-600/90 hover:bg-blue-500 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">save</span>
                            Save Mapping
                        </button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.mapping-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const deptId = this.dataset.deptId;
        const checkedBoxes = this.querySelectorAll('input[name="menu_ids[]"]:checked');
        const formData = new FormData();
        
        formData.append('csrf_token', '<?= \App\Middleware\SecurityMiddleware::getCsrfToken() ?>');
        formData.append('department_id', deptId);
        
        checkedBoxes.forEach(box => {
            formData.append('menu_ids[]', box.value);
        });

        const btn = this.querySelector('button[type="submit"]');
        const origText = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">sync</span> Saving...';
        btn.disabled = true;

        fetch('/superadmin/menus/assign', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            btn.innerHTML = origText;
            btn.disabled = false;
            
            if(res.success) {
                // Update styling of checkboxes locally to reflect change instantly
                const allLabels = this.querySelectorAll('label');
                allLabels.forEach(lbl => {
                    const chk = lbl.querySelector('input');
                    if(chk.checked) {
                        lbl.classList.replace('border-white/5', 'border-blue-500/40');
                        lbl.classList.replace('bg-white/5', 'bg-blue-500/10');
                    } else {
                        lbl.classList.replace('border-blue-500/40', 'border-white/5');
                        lbl.classList.replace('bg-blue-500/10', 'bg-white/5');
                    }
                });

                Swal.fire({
                    title: 'Saved!',
                    text: res.message,
                    icon: 'success',
                    background: '#1a1f2e',
                    color: '#fff',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = origText;
            btn.disabled = false;
            Swal.fire('Error', 'Network error', 'error');
        });
    });
});
</script>
