<?php 
require __DIR__ . '/../../parts/app_sidebar.php'; 
?>
<div class="p-4 sm:p-8 space-y-6 max-w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/10 backdrop-blur-md p-6 rounded-2xl border border-white/20 shadow-xl">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Audit Logs & Security Trail</h1>
            <p class="text-gray-300 mt-1 text-sm font-medium">Monitoring aktivitas sistem dan perubahan otorisasi tingkat tinggi.</p>
        </div>
        <?php if (\App\Helpers\AuthHelper::hasRole('superadmin')): ?>
            <button onclick="clearAuditLogs()" class="px-6 py-2.5 bg-red-600/90 hover:bg-red-500 text-white font-semibold rounded-xl transition-all shadow-lg shadow-red-500/30 flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">delete_forever</span>
                Clear All Logs
            </button>
        <?php endif; ?>
    </div>

    <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-6 shadow-2xl overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300 border-collapse">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="py-3 px-4 font-semibold text-white">Timestamp</th>
                    <th class="py-3 px-4 font-semibold text-white">Actor</th>
                    <th class="py-3 px-4 font-semibold text-white">Action</th>
                    <th class="py-3 px-4 font-semibold text-white">Table/Target</th>
                    <th class="py-3 px-4 font-semibold text-white">Description</th>
                    <th class="py-3 px-4 font-semibold text-white text-right">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-400 italic">No audit logs recorded yet.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="py-3 px-4 whitespace-nowrap text-xs"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
                        <td class="py-3 px-4">
                            <span class="block font-medium text-white"><?= htmlspecialchars($log['actor_name'] ?: 'SYSTEM') ?></span>
                            <span class="text-[10px] uppercase px-2 py-0.5 rounded-full bg-white/10 text-gray-300 border border-white/10 mt-1 inline-block">
                                <?= htmlspecialchars($log['role'] ?: 'Automated') ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <?php 
                            $badgeColor = match($log['action']) {
                                'INSERT' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                'UPDATE' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                                'DELETE' => 'bg-orange-500/20 text-orange-300 border-orange-500/30',
                                'TRUNCATE' => 'bg-red-500/20 text-red-300 border-red-500/30',
                                default => 'bg-gray-500/20 text-gray-300 border-gray-500/30'
                            };
                            ?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border <?= $badgeColor ?>">
                                <?= htmlspecialchars($log['action']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4"><code class="text-xs text-purple-300 bg-purple-900/30 px-2 py-1 rounded"><?= htmlspecialchars($log['table_name'] ?? '-') ?></code></td>
                        <td class="py-3 px-4 text-xs leading-relaxed max-w-md truncate hover:whitespace-normal hover:break-words transition-all" title="<?= htmlspecialchars($log['description']) ?>">
                            <?= htmlspecialchars($log['description']) ?>
                        </td>
                        <td class="py-3 px-4 text-right text-xs font-mono text-gray-400"><?= htmlspecialchars($log['ip_address']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function clearAuditLogs() {
    Swal.fire({
        title: 'Clear System Logs?',
        html: '<span class="text-red-400 font-medium">WARNING:</span> This action is irreversible. It will wipe all security logs except for the automated testimony log.',
        icon: 'warning',
        background: '#1a1f2e',
        color: '#fff',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#374151',
        confirmButtonText: 'Yes, Clear Logs'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('csrf_token', '<?= \App\Middleware\SecurityMiddleware::getCsrfToken() ?>');

            fetch('/superadmin/audit/clear', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        title: 'Cleared!',
                        text: res.message,
                        icon: 'success',
                        background: '#1a1f2e',
                        color: '#fff',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: res.message,
                        icon: 'error',
                        background: '#1a1f2e',
                        color: '#fff',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Failed to connect to server', 'error');
            });
        }
    });
}
</script>
