<?php
use App\Services\ViewHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$apiKey = ViewHelper::getSetting('system_api_key', 'siCare_API_Secret_Key_2026');
?>

<!-- Banner Identitas Superadmin God Mode -->
<div class="mb-6 bg-gradient-to-r from-primary to-indigo-900 text-white rounded-2xl p-6 shadow-md border border-primary/20 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div class="space-y-1">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-white/10 rounded-lg material-symbols-outlined text-xl">admin_panel_settings</span>
            <span class="text-xs font-bold uppercase tracking-wider text-amber-300">Super Administrator Control Panel</span>
        </div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Pengaturan Sistem & Integrasi Infrastruktur</h2>
        <p class="text-xs text-indigo-100 max-w-2xl leading-relaxed">
            Kelola parameter operasional seluruh cabang perusahaan, batas toleransi presensi, kalender libur nasional, dan kunci integrasi perangkat keras biometrik (M2M).
        </p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="openApiDocsModal()" class="px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
            <span class="material-symbols-outlined text-base">code</span>
            <span>Dokumentasi API</span>
        </button>
    </div>
</div>

<!-- Card Khusus: Integrasi Mesin Presensi Biometrik (API Key Management) -->
<div class="mb-8 bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-sm space-y-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-outline-variant/20 pb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/5 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">fingerprint</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-on-surface">Kunci Integrasi Mesin Presensi Biometrik (REST API M2M)</h3>
                <p class="text-xs text-on-surface-variant">Digunakan oleh mesin absensi fisik (Fingerprint, Face Recognition, atau RFID Gate) untuk mengirim log kehadiran via <code>/api/v1/attendance</code>.</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Endpoint Aktif
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div class="md:col-span-2 space-y-1.5">
            <label class="block text-xs font-bold text-on-surface">X-API-Key Rahasia (Tersimpan di Sistem)</label>
            <div class="relative">
                <input type="password" id="systemApiKeyInput" value="<?= htmlspecialchars($apiKey) ?>" readonly class="w-full pl-4 pr-24 py-2.5 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-mono font-bold text-primary focus:outline-none">
                <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                    <button type="button" onclick="toggleApiKeyVisibility()" class="p-1.5 text-on-surface-variant hover:text-primary transition" title="Tampilkan/Sembunyikan Kunci">
                        <span id="apiKeyEyeIcon" class="material-symbols-outlined text-base">visibility</span>
                    </button>
                    <button type="button" onclick="copyApiKey()" class="p-1.5 text-on-surface-variant hover:text-primary transition" title="Salin Kunci">
                        <span class="material-symbols-outlined text-base">content_copy</span>
                    </button>
                </div>
            </div>
            <p class="text-[11px] text-on-surface-variant">Kirim header <code>X-API-Key: &lt;KUNCI_ANDA&gt;</code> pada setiap request POST JSON ke <code>https://<?= $_SERVER['HTTP_HOST'] ?? 'domain.com' ?>/api/v1/attendance</code>.</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="generateNewApiKey()" class="flex-1 px-3.5 py-2.5 bg-surface-container-highest hover:bg-outline-variant/30 text-on-surface font-bold text-xs rounded-xl border border-outline-variant/40 transition flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-base">refresh</span>
                <span>Generate Key Baru</span>
            </button>
            <button type="button" onclick="saveApiKeyToSettings()" class="px-4 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">save</span>
                <span>Simpan Kunci</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Dokumentasi API Biometrik -->
<div id="apiDocsModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-2xl border border-outline-variant/30">
        <div class="flex justify-between items-center border-b border-outline-variant/30 pb-3">
            <h3 class="text-base font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">terminal</span>
                <span>Spesifikasi Integrasi REST API Mesin Biometrik</span>
            </h3>
            <button onclick="closeApiDocsModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="space-y-3 text-xs text-on-surface max-h-[70vh] overflow-y-auto pr-1">
            <div class="p-3 bg-surface-container-lowest rounded-xl border border-outline-variant/20 font-mono">
                <span class="text-emerald-700 font-bold">POST</span> /api/v1/attendance
            </div>

            <div>
                <span class="font-bold block mb-1">Header Wajib:</span>
                <pre class="bg-gray-900 text-gray-100 p-3 rounded-xl font-mono text-[11px] overflow-x-auto">Content-Type: application/json
X-API-Key: <?= htmlspecialchars($apiKey) ?></pre>
            </div>

            <div>
                <span class="font-bold block mb-1">Format Payload JSON (Batch Records):</span>
                <pre class="bg-gray-900 text-gray-100 p-3 rounded-xl font-mono text-[11px] overflow-x-auto">{
  "records": [
    {
      "employee_id": "EM-2026-0034",
      "attendance_date": "<?= date('Y-m-d') ?>",
      "clock_in": "07:55:00",
      "clock_out": "17:05:00",
      "status": "hadir",
      "work_mode": "WFO"
    }
  ]
}</pre>
            </div>

            <p class="text-on-surface-variant text-[11px]">
                Sistem akan secara otomatis mencocokkan karyawan berdasarkan <code>employee_id</code>, <code>email</code>, atau <code>user_id</code> (UUID).
            </p>
        </div>

        <div class="flex justify-end pt-3 border-t border-outline-variant/20">
            <button type="button" onclick="closeApiDocsModal()" class="px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function toggleApiKeyVisibility() {
        const input = document.getElementById('systemApiKeyInput');
        const icon = document.getElementById('apiKeyEyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerText = 'visibility_off';
        } else {
            input.type = 'password';
            icon.innerText = 'visibility';
        }
    }

    function copyApiKey() {
        const input = document.getElementById('systemApiKeyInput');
        navigator.clipboard.writeText(input.value).then(() => {
            Swal.fire({
                title: 'Tersalin!',
                text: 'Kunci API telah disalin ke clipboard.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }).catch(() => {
            prompt('Salin Kunci API:', input.value);
        });
    }

    function generateNewApiKey() {
        Swal.fire({
            title: 'Generate Kunci API Baru?',
            text: 'Perangkat atau mesin presensi eksternal yang menggunakan kunci lama harus diperbarui dengan kunci baru ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#757575',
            confirmButtonText: 'Ya, Generate',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                // Generate 32-char hex token
                const arr = new Uint8Array(16);
                window.crypto.getRandomValues(arr);
                const newKey = 'siCare_' + Array.from(arr, b => b.toString(16).padStart(2, '0')).join('');
                document.getElementById('systemApiKeyInput').value = newKey;
                document.getElementById('systemApiKeyInput').type = 'text';
                document.getElementById('apiKeyEyeIcon').innerText = 'visibility_off';

                Swal.fire({
                    title: 'Kunci Baru Di-generate!',
                    text: 'Kunci baru telah disiapkan. Klik "Simpan Kunci" untuk menerapkannya ke server.',
                    icon: 'info',
                    confirmButtonColor: '#000666'
                });
            }
        });
    }

    function saveApiKeyToSettings() {
        const newKey = document.getElementById('systemApiKeyInput').value.trim();
        if (!newKey) {
            Swal.fire('Gagal', 'Kunci API tidak boleh kosong.', 'error');
            return;
        }

        Swal.showLoading();
        const formData = new FormData();
        formData.append('csrf_token', '<?= csrf_token() ?>');
        formData.append('system_api_key', newKey);

        fetch('/superadmin/system-settings/settings/save', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil Disimpan!',
                    text: 'Kunci API Mesin Biometrik berhasil diperbarui di database.',
                    icon: 'success',
                    confirmButtonColor: '#000666'
                });
            } else {
                Swal.fire('Gagal', data.message || 'Gagal menyimpan kunci API', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', err.message, 'error');
        });
    }

    function openApiDocsModal() {
        document.getElementById('apiDocsModal').classList.remove('hidden');
    }

    function closeApiDocsModal() {
        document.getElementById('apiDocsModal').classList.add('hidden');
    }
</script>

<!-- Muat Formulir Pengaturan Operasional & Libur Nasional -->
<?php
require __DIR__ . '/../admin/settings.php';
?>
