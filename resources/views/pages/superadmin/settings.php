<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Konfigurasi Global</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Kelola pengaturan tingkat sistem seperti konfigurasi email, kebijakan keamanan, dan batas unggahan berkas.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3.5 py-2 rounded-full border border-primary/10 flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">shield_person</span>
                Otoritas Super Admin
            </span>
        </div>
    </div>

    <!-- Two-Column Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- LEFT COLUMN: Konfigurasi Email (SMTP) -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm flex flex-col">
            <!-- Card Header -->
            <div class="px-6 py-5 border-b border-outline-variant/15">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/5 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-xl">mail</span>
                    </div>
                    <div>
                        <h2 class="font-headline text-lg font-extrabold text-on-surface">Konfigurasi Email</h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">Pengaturan SMTP untuk pengiriman email sistem</p>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-6 space-y-5 flex-1">
                <!-- SMTP Host -->
                <div>
                    <label for="globalSmtpHost" class="block text-xs font-bold text-on-surface-variant mb-2">Host SMTP Server</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">dns</span>
                        <input type="text" id="globalSmtpHost" placeholder="smtp.gmail.com" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>

                <!-- SMTP Port & Encryption -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="globalSmtpPort" class="block text-xs font-bold text-on-surface-variant mb-2">Port SMTP</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">tag</span>
                            <input type="number" id="globalSmtpPort" placeholder="587" min="1" max="65535" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>
                    <div>
                        <label for="globalSmtpEncryption" class="block text-xs font-bold text-on-surface-variant mb-2">Enkripsi</label>
                        <div class="relative">
                            <select id="globalSmtpEncryption" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="none">Tidak Ada (None)</option>
                                <option value="tls" selected>TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                            
                        </div>
                    </div>
                </div>

                <!-- SMTP Username -->
                <div>
                    <label for="globalSmtpUsername" class="block text-xs font-bold text-on-surface-variant mb-2">Username SMTP</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">person</span>
                        <input type="text" id="globalSmtpUsername" placeholder="user@gmail.com" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>

                <!-- SMTP Password -->
                <div>
                    <label for="globalSmtpPassword" class="block text-xs font-bold text-on-surface-variant mb-2">Password SMTP</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">lock</span>
                        <input type="password" id="globalSmtpPassword" placeholder="••••••••••••" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-12 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <button type="button" id="globalToggleSmtpPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- From Address -->
                <div>
                    <label for="globalSmtpFromAddress" class="block text-xs font-bold text-on-surface-variant mb-2">Alamat Pengirim</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">alternate_email</span>
                        <input type="email" id="globalSmtpFromAddress" placeholder="noreply@perusahaan.com" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>

                <!-- From Name -->
                <div>
                    <label for="globalSmtpFromName" class="block text-xs font-bold text-on-surface-variant mb-2">Nama Pengirim</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">badge</span>
                        <input type="text" id="globalSmtpFromName" placeholder="siCare HRIS" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="px-6 py-4 border-t border-outline-variant/15 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 bg-surface-container-low/30">
                <button type="button" onclick="window.sendTestEmail()" class="px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2 border border-outline-variant/20">
                    <span class="material-symbols-outlined text-base">send</span>
                    Kirim Email Percobaan
                </button>
                <button type="button" onclick="window.saveEmailSettings()" class="bg-primary hover:bg-primary/90 text-white font-bold text-sm py-2.5 px-4 rounded-xl transition-colors flex items-center justify-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-base">save</span>
                    Simpan Konfigurasi Email
                </button>
            </div>
        </div>

        <!-- RIGHT COLUMN: Kebijakan Keamanan & Batas Sistem -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm flex flex-col">
            <!-- Card Header -->
            <div class="px-6 py-5 border-b border-outline-variant/15">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/5 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-xl">security</span>
                    </div>
                    <div>
                        <h2 class="font-headline text-lg font-extrabold text-on-surface">Kebijakan Keamanan & Batas Sistem</h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">Batasan unggahan dan aturan validasi kata sandi</p>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-6 space-y-6 flex-1">

                <!-- Section: Batas Unggahan Berkas -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-on-surface-variant text-base">upload_file</span>
                        <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">Batas Unggahan Berkas</h3>
                    </div>
                    <div>
                        <label for="globalMaxUploadSizeMb" class="block text-xs font-bold text-on-surface-variant mb-2">Batas Ukuran Maksimal</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">hard_drive</span>
                            <input type="number" id="globalMaxUploadSizeMb" placeholder="10" min="1" max="1024" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-14 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-extrabold text-on-surface-variant/60 pointer-events-none">MB</span>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-outline-variant/15"></div>

                <!-- Section: Kebijakan Kata Sandi -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-on-surface-variant text-base">password</span>
                        <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">Kebijakan Kata Sandi</h3>
                    </div>

                    <!-- Min Password Length -->
                    <div>
                        <label for="globalMinPasswordLength" class="block text-xs font-bold text-on-surface-variant mb-2">Panjang Minimal Kata Sandi</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">straighten</span>
                            <input type="number" id="globalMinPasswordLength" placeholder="8" min="4" max="128" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>

                    <!-- Toggle: Wajib Huruf Kapital -->
                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant/60 text-lg">title</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Wajib Huruf Kapital</p>
                                <p class="text-xs text-on-surface-variant font-medium">Kata sandi harus mengandung minimal satu huruf besar (A-Z)</p>
                            </div>
                        </div>
                        <label class="global-toggle-switch relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                            <input type="checkbox" id="globalRequireUppercase" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant/40 peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:shadow-sm after:transition-all peer-checked:bg-primary transition-colors"></div>
                        </label>
                    </div>

                    <!-- Toggle: Wajib Mengandung Angka -->
                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant/60 text-lg">123</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Wajib Mengandung Angka</p>
                                <p class="text-xs text-on-surface-variant font-medium">Kata sandi harus mengandung minimal satu angka (0-9)</p>
                            </div>
                        </div>
                        <label class="global-toggle-switch relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                            <input type="checkbox" id="globalRequireNumber" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant/40 peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:shadow-sm after:transition-all peer-checked:bg-primary transition-colors"></div>
                        </label>
                    </div>

                    <!-- Toggle: Wajib Karakter Spesial -->
                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant/60 text-lg">special_character</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Wajib Karakter Spesial</p>
                                <p class="text-xs text-on-surface-variant font-medium">Kata sandi harus mengandung minimal satu simbol (@$!%*?&)</p>
                            </div>
                        </div>
                        <label class="global-toggle-switch relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                            <input type="checkbox" id="globalRequireSpecialChar" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant/40 peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:shadow-sm after:transition-all peer-checked:bg-primary transition-colors"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end bg-surface-container-low/30">
                <button type="button" onclick="window.saveSecuritySettings()" class="bg-primary hover:bg-primary/90 text-white font-bold text-sm py-2.5 px-4 rounded-xl transition-colors flex items-center justify-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-base">save</span>
                    Simpan Kebijakan
                </button>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    // ─── CSRF Token ─────────────────────────────────────────────────────────────
    var csrfToken = <?= json_encode(csrf_token()) ?>;

    // ─── Element References ─────────────────────────────────────────────────────
    function el(id) { return document.getElementById(id); }

    // ─── Setting Key → Element ID Map ───────────────────────────────────────────
    var settingFieldMap = {
        'smtp_host':          'globalSmtpHost',
        'smtp_port':          'globalSmtpPort',
        'smtp_encryption':    'globalSmtpEncryption',
        'smtp_username':      'globalSmtpUsername',
        'smtp_password':      'globalSmtpPassword',
        'smtp_from_address':  'globalSmtpFromAddress',
        'smtp_from_name':     'globalSmtpFromName',
        'max_upload_size_mb': 'globalMaxUploadSizeMb',
        'min_password_length':'globalMinPasswordLength',
        'require_uppercase':  'globalRequireUppercase',
        'require_number':     'globalRequireNumber',
        'require_special_char':'globalRequireSpecialChar'
    };

    // Toggle checkboxes (value stored as 'true'/'false' strings)
    var toggleKeys = ['require_uppercase', 'require_number', 'require_special_char'];

    // ─── Load Global Settings ───────────────────────────────────────────────────
    window.loadGlobalSettings = function() {
        fetch('/superadmin/settings/global')
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function(res) {
                if (!res.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', res.message || 'Gagal memuat konfigurasi global.', 'error');
                    }
                    return;
                }

                var settings = res.data || {};

                Object.keys(settingFieldMap).forEach(function(key) {
                    var element = el(settingFieldMap[key]);
                    if (!element) return;

                    var value = settings[key];
                    if (typeof value === 'undefined' || value === null) return;

                    if (toggleKeys.indexOf(key) !== -1) {
                        element.checked = (String(value) === 'true' || value === true);
                    } else {
                        element.value = value;
                    }
                });
            })
            .catch(function(err) {
                console.error('Global settings load error:', err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal memuat konfigurasi: ' + err.message, 'error');
                }
            });
    };

    // ─── Save Email Settings ────────────────────────────────────────────────────
    window.saveEmailSettings = function() {
        var payload = {
            smtp_host:         (el('globalSmtpHost') ? el('globalSmtpHost').value.trim() : ''),
            smtp_port:         (el('globalSmtpPort') ? el('globalSmtpPort').value : ''),
            smtp_encryption:   (el('globalSmtpEncryption') ? el('globalSmtpEncryption').value : 'tls'),
            smtp_username:     (el('globalSmtpUsername') ? el('globalSmtpUsername').value.trim() : ''),
            smtp_password:     (el('globalSmtpPassword') ? el('globalSmtpPassword').value : ''),
            smtp_from_address: (el('globalSmtpFromAddress') ? el('globalSmtpFromAddress').value.trim() : ''),
            smtp_from_name:    (el('globalSmtpFromName') ? el('globalSmtpFromName').value.trim() : '')
        };

        // Basic validation
        if (!payload.smtp_host) {
            Swal.fire('Validasi Gagal', 'Host SMTP Server wajib diisi.', 'warning');
            return;
        }
        if (!payload.smtp_port || parseInt(payload.smtp_port) < 1) {
            Swal.fire('Validasi Gagal', 'Port SMTP wajib diisi dengan angka yang valid.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Simpan Konfigurasi Email?',
            text: 'Pastikan data SMTP yang dimasukkan sudah benar sebelum menyimpan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            var saveBtn = document.querySelector('[onclick="window.saveEmailSettings()"]');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">autorenew</span> Menyimpan...';
            }

            fetch('/superadmin/settings/global/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        title: 'Tersimpan!',
                        text: 'Konfigurasi email berhasil diperbarui.',
                        icon: 'success',
                        confirmButtonColor: '#000666',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.message || 'Gagal menyimpan konfigurasi email.', 'error');
                }
            })
            .catch(function(err) {
                console.error('Save email settings error:', err);
                Swal.fire('Error', 'Terjadi kesalahan jaringan saat menyimpan.', 'error');
            })
            .finally(function() {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<span class="material-symbols-outlined text-base">save</span> Simpan Konfigurasi Email';
                }
            });
        });
    };

    // ─── Save Security Settings ─────────────────────────────────────────────────
    window.saveSecuritySettings = function() {
        var maxUploadVal = el('globalMaxUploadSizeMb') ? el('globalMaxUploadSizeMb').value : '';
        var minPwLenVal  = el('globalMinPasswordLength') ? el('globalMinPasswordLength').value : '';

        var payload = {
            max_upload_size_mb:  maxUploadVal || '10',
            min_password_length: minPwLenVal || '8',
            require_uppercase:   (el('globalRequireUppercase') ? el('globalRequireUppercase').checked : true) ? 'true' : 'false',
            require_number:      (el('globalRequireNumber') ? el('globalRequireNumber').checked : true) ? 'true' : 'false',
            require_special_char:(el('globalRequireSpecialChar') ? el('globalRequireSpecialChar').checked : true) ? 'true' : 'false'
        };

        // Validation
        if (!maxUploadVal || parseInt(maxUploadVal) < 1) {
            Swal.fire('Validasi Gagal', 'Batas ukuran unggahan harus minimal 1 MB.', 'warning');
            return;
        }
        if (!minPwLenVal || parseInt(minPwLenVal) < 4) {
            Swal.fire('Validasi Gagal', 'Panjang minimal kata sandi harus setidaknya 4 karakter.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Simpan Kebijakan Keamanan?',
            text: 'Perubahan kebijakan akan diterapkan untuk seluruh pengguna sistem.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            var saveBtn = document.querySelector('[onclick="window.saveSecuritySettings()"]');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">autorenew</span> Menyimpan...';
            }

            fetch('/superadmin/settings/global/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        title: 'Tersimpan!',
                        text: 'Kebijakan keamanan dan batas sistem berhasil diperbarui.',
                        icon: 'success',
                        confirmButtonColor: '#000666',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.message || 'Gagal menyimpan kebijakan keamanan.', 'error');
                }
            })
            .catch(function(err) {
                console.error('Save security settings error:', err);
                Swal.fire('Error', 'Terjadi kesalahan jaringan saat menyimpan.', 'error');
            })
            .finally(function() {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<span class="material-symbols-outlined text-base">save</span> Simpan Kebijakan';
                }
            });
        });
    };

    // ─── Send Test Email ────────────────────────────────────────────────────────
    window.sendTestEmail = function() {
        // Validate that SMTP fields are filled before testing
        var host = el('globalSmtpHost') ? el('globalSmtpHost').value.trim() : '';
        var port = el('globalSmtpPort') ? el('globalSmtpPort').value : '';

        if (!host || !port) {
            Swal.fire('Konfigurasi Belum Lengkap', 'Isi minimal Host dan Port SMTP terlebih dahulu sebelum mengirim email percobaan.', 'warning');
            return;
        }

        var testBtn = document.querySelector('[onclick="window.sendTestEmail()"]');
        if (testBtn) {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">autorenew</span> Mengirim...';
        }

        fetch('/superadmin/settings/test-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({})
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                Swal.fire({
                    title: 'Email Terkirim!',
                    html: '<p class="text-sm">Email percobaan berhasil dikirim.</p>' +
                          (data.recipient ? '<p class="text-xs text-on-surface-variant mt-2">Dikirim ke: <strong>' + data.recipient + '</strong></p>' : ''),
                    icon: 'success',
                    confirmButtonColor: '#000666'
                });
            } else {
                Swal.fire({
                    title: 'Pengiriman Gagal',
                    html: '<p class="text-sm mb-2">Email percobaan gagal dikirim.</p>' +
                          (data.message ? '<div class="text-xs bg-red-50 text-red-700 p-3 rounded-xl border border-red-200 mt-2 text-left font-mono">' + data.message + '</div>' : ''),
                    icon: 'error',
                    confirmButtonColor: '#000666'
                });
            }
        })
        .catch(function(err) {
            console.error('Test email error:', err);
            Swal.fire('Error', 'Terjadi kesalahan jaringan saat mengirim email percobaan.', 'error');
        })
        .finally(function() {
            if (testBtn) {
                testBtn.disabled = false;
                testBtn.innerHTML = '<span class="material-symbols-outlined text-base">send</span> Kirim Email Percobaan';
            }
        });
    };

    // ─── Toggle SMTP Password Visibility ────────────────────────────────────────
    var togglePwBtn   = el('globalToggleSmtpPassword');
    var smtpPwInput   = el('globalSmtpPassword');

    if (togglePwBtn && smtpPwInput) {
        togglePwBtn.addEventListener('click', function() {
            var type = smtpPwInput.getAttribute('type') === 'password' ? 'text' : 'password';
            smtpPwInput.setAttribute('type', type);
            var ico = this.querySelector('.material-symbols-outlined');
            if (ico) ico.textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });
    }

    // ─── Initial Load ───────────────────────────────────────────────────────────
    setTimeout(function() {
        window.loadGlobalSettings();
    }, 100);

})();
</script>