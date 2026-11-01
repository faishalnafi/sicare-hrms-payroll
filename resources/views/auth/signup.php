<style>
    .signature-gradient {
        background: linear-gradient(135deg, #000666 0%, #1a237e 100%);
    }
    .custom-shadow {
        box-shadow: 0 20px 40px rgba(0, 7, 103, 0.06);
    }
</style>

<div class="flex-grow flex flex-col lg:flex-row items-center justify-center px-6 py-2 gap-8 lg:gap-12 max-w-7xl mx-auto w-full">
    <!-- Left Column: Editorial Content -->
    <div class="w-full lg:w-5/12 space-y-8 lg:pr-8">
        <div class="space-y-4">
            <h1 class="text-4xl lg:text-5xl font-headline font-extrabold text-primary leading-tight tracking-tighter">Pendaftaran <br/><span class="text-secondary">Anggota Ekstrakurikuler</span></h1>
            <p class="text-on-surface-variant text-base max-w-md leading-relaxed">Bergabunglah bersama kami untuk mengembangkan minat dan bakat Anda. Isi formulir pendaftaran di bawah ini untuk memulai proses rekrutmen anggota baru.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="p-6 rounded-xl bg-surface-container-low">
                <span class="material-symbols-outlined text-primary mb-3 text-3xl">groups</span>
                <h3 class="font-headline font-bold text-primary">4 Divisi Operasional</h3>
                <p class="text-sm text-on-surface-variant mt-1">Post Production, Editorial, Broadcast, dan Visual Production.</p>
            </div>
            <div class="p-6 rounded-xl bg-surface-container-low">
                <span class="material-symbols-outlined text-primary mb-3 text-3xl">verified_user</span>
                <h3 class="font-headline font-bold text-primary">Proses Mudah</h3>
                <p class="text-sm text-on-surface-variant mt-1">Daftar akun, lalu pilih divisi minat Anda langsung dari Dashboard Kandidat.</p>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Registration Form -->
    <div class="w-full lg:w-7/12 relative">
        <!-- Background Decorative Element -->
        <div class="absolute -top-12 -right-12 w-64 h-64 signature-gradient opacity-10 rounded-full blur-3xl -z-10"></div>
        
        <div class="bg-surface-container-lowest custom-shadow rounded-xl p-8 lg:p-10 border border-outline-variant/15 max-h-[85vh] overflow-y-auto custom-scrollbar">
            <div class="mb-6">
                <h2 class="text-2xl font-headline font-extrabold text-[#000767] mb-2">Formulir Pendaftaran Akun</h2>
                <p class="text-on-surface-variant text-sm">Lengkapi data diri Anda di bawah ini dengan benar.</p>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($errors) && !empty($errors)): ?>
            <!-- Pesan Error -->
            <div class="mb-6 bg-[#fef2f2] border border-[#f87171] rounded-xl p-4 flex items-start gap-3 shadow-sm transition-all">
                <div class="bg-red-100 p-1.5 rounded-full shrink-0 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-600 text-sm">error</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-red-800 font-headline">Gagal mengirim pendaftaran</h4>
                    <ul class="mt-1 text-sm text-red-700 list-disc list-inside space-y-0.5 font-medium">
                        <?php foreach($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php elseif (isset($error) && !empty($error)): ?>
            <!-- Pesan Error Tunggal -->
            <div class="mb-6 bg-[#fef2f2] border border-[#f87171] rounded-xl p-4 flex items-start gap-3 shadow-sm transition-all">
                <div class="bg-red-100 p-1.5 rounded-full shrink-0 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-600 text-sm">error</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-red-800 font-headline">Gagal mengirim pendaftaran</h4>
                    <p class="mt-1 text-sm text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <form id="signupForm" class="space-y-6" action="/auth/register" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Lengkap -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="nama">Nama Lengkap <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input required name="nama" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="nama" placeholder="Masukkan nama lengkap Anda" type="text"/>
                        </div>
                    </div>
                    
                    <!-- Rombel (Dropdown) -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="rombel">Rombel <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select required name="rombel" id="rombel" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface cursor-pointer">
                                <option value="" disabled selected>-- Pilih Rombel --</option>
                                <?php
                                    $rombelList = [
                                        'X1','X2','X3','X4','X5','X6','X7','X8','X9',
                                        'XI1','XI2','XI3','XI4','XI5','XI6','XI7','XI8','XI9',
                                        'XII1','XII2','XII3','XII4','XII5','XII6','XII7','XII8','XII9'
                                    ];
                                    foreach ($rombelList as $r):
                                ?>
                                    <option value="<?= $r ?>"><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Angkatan Masuk (Dropdown) -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="angkatan_masuk">Angkatan Masuk <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select required name="angkatan_masuk" id="angkatan_masuk" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface cursor-pointer">
                                <option value="" disabled selected>-- Pilih Angkatan --</option>
                                <?php
                                    $startYear = 2019;
                                    $startAngkatan = 29;
                                    for ($i = 0; $i <= 11; $i++):
                                        $year = $startYear + $i;
                                        $angk = $startAngkatan + $i;
                                        $label = "{$year} (Angkatan {$angk})";
                                ?>
                                    <option value="<?= $label ?>"><?= $label ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Bulan Bergabung (Dropdown) -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="bulan_bergabung">Bulan Bergabung <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select required name="bulan_bergabung" id="bulan_bergabung" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface cursor-pointer">
                                <option value="" disabled selected>-- Pilih Bulan --</option>
                                <?php
                                    $bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                    foreach ($bulanList as $b):
                                ?>
                                    <option value="<?= $b ?>"><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Nomor WhatsApp -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="phone">Nomor WhatsApp <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input required name="phone" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="phone" placeholder="628..." type="text" inputmode="numeric"/>
                        </div>
                        <p class="text-[11px] text-on-surface-variant/70 ml-1">Wajib angka saja. Awalan <code>08</code> akan otomatis dikonversi menjadi <code>628</code>.</p>
                    </div>

                    <!-- Gmail / Email Aktif -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="email">Gmail / Email Aktif <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input required name="email" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="email" placeholder="contoh@gmail.com" type="email"/>
                        </div>
                    </div>
                    
                    <!-- Kata Sandi -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="password">Kata Sandi <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <input required name="password" class="w-full bg-surface-container-high border-none rounded-lg pl-4 pr-12 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="password" placeholder="Minimal 8 karakter" type="password"/>
                            <button type="button" id="togglePassword" class="absolute right-3 top-3.5 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Retype Kata Sandi -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="password_confirm">Konfirmasi Kata Sandi <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <input required name="password_confirm" class="w-full bg-surface-container-high border-none rounded-lg pl-4 pr-12 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="password_confirm" placeholder="Ulangi kata sandi" type="password"/>
                            <button type="button" id="togglePasswordConfirm" class="absolute right-3 top-3.5 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Password Strength Indicators -->
                    <div class="md:col-span-2">
                        <div id="pw-strength-box" class="hidden p-4 bg-surface-container-low rounded-xl border border-outline-variant/15 text-xs space-y-2 mt-1">
                            <p class="font-bold text-on-surface-variant mb-1.5">Kriteria Kata Sandi Kuat & Konfirmasi:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-medium">
                                <div id="pw-len" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                    <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Minimal 8 Karakter
                                </div>
                                <div id="pw-upper" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                    <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kapital (A-Z)
                                </div>
                                <div id="pw-lower" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                    <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kecil (a-z)
                                </div>
                                <div id="pw-num" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                    <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Angka (0-9)
                                </div>
                                <div id="pw-match" class="flex items-center gap-1.5 text-red-500 transition-colors sm:col-span-2">
                                    <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Konfirmasi Kata Sandi Cocok
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="pt-4 mt-6">
                    <button class="w-full signature-gradient text-on-primary font-headline font-bold py-4 rounded-lg shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-3 group" type="submit">
                        Daftar Akun Kandidat 
                        <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </button>
                </div>
            </form>

            <script>
                // Format WhatsApp Number (Strictly Digits Only & Auto Convert 08 -> 628)
                const phoneInput = document.getElementById('phone');
                if (phoneInput) {
                    phoneInput.addEventListener('input', function() {
                        // Remove all non-digits
                        let val = this.value.replace(/\D/g, '');
                        // If user types '08...', automatically change '08' to '628'
                        if (val.startsWith('08')) {
                            val = '628' + val.substring(2);
                        }
                        this.value = val;
                    });

                    phoneInput.addEventListener('blur', function() {
                        let val = this.value.replace(/\D/g, '');
                        if (val.startsWith('08')) {
                            val = '628' + val.substring(2);
                        }
                        this.value = val;
                    });
                }

                // Toggle Show/Hide Password
                const passwordInput = document.getElementById('password');
                const passwordConfirmInput = document.getElementById('password_confirm');
                const togglePasswordBtn = document.getElementById('togglePassword');
                const togglePasswordConfirmBtn = document.getElementById('togglePasswordConfirm');

                if (passwordInput && togglePasswordBtn) {
                    togglePasswordBtn.addEventListener('click', function() {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        this.querySelector('.material-symbols-outlined').textContent = type === 'password' ? 'visibility' : 'visibility_off';
                    });
                }

                if (passwordConfirmInput && togglePasswordConfirmBtn) {
                    togglePasswordConfirmBtn.addEventListener('click', function() {
                        const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordConfirmInput.setAttribute('type', type);
                        this.querySelector('.material-symbols-outlined').textContent = type === 'password' ? 'visibility' : 'visibility_off';
                    });
                }

                // Password Strength Real-time UX
                const pwStrengthBox = document.getElementById('pw-strength-box');
                const pwLen = document.getElementById('pw-len');
                const pwUpper = document.getElementById('pw-upper');
                const pwLower = document.getElementById('pw-lower');
                const pwNum = document.getElementById('pw-num');
                const pwMatch = document.getElementById('pw-match');

                let passwordValid = false;

                function checkPasswordStrength() {
                    const val = passwordInput.value;
                    const confirmVal = passwordConfirmInput.value;
                    
                    const hasLen = val.length >= 8;
                    const hasUpper = /[A-Z]/.test(val);
                    const hasLower = /[a-z]/.test(val);
                    const hasNum = /[0-9]/.test(val);
                    const hasMatch = val !== '' && val === confirmVal;

                    updateCrit(pwLen, hasLen);
                    updateCrit(pwUpper, hasUpper);
                    updateCrit(pwLower, hasLower);
                    updateCrit(pwNum, hasNum);
                    updateCrit(pwMatch, hasMatch);

                    passwordValid = hasLen && hasUpper && hasLower && hasNum && hasMatch;
                }

                if (passwordInput && passwordConfirmInput) {
                    passwordInput.addEventListener('focus', () => pwStrengthBox.classList.remove('hidden'));
                    passwordConfirmInput.addEventListener('focus', () => pwStrengthBox.classList.remove('hidden'));

                    passwordInput.addEventListener('input', checkPasswordStrength);
                    passwordConfirmInput.addEventListener('input', checkPasswordStrength);
                }

                function updateCrit(elem, met) {
                    if (!elem) return;
                    if (met) {
                        elem.classList.remove('text-red-500');
                        elem.classList.add('text-green-600');
                        elem.querySelector('.material-symbols-outlined').textContent = 'check_circle';
                    } else {
                        elem.classList.remove('text-green-600');
                        elem.classList.add('text-red-500');
                        elem.querySelector('.material-symbols-outlined').textContent = 'cancel';
                    }
                }

                document.getElementById('signupForm').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const phoneVal = phoneInput.value.trim();
                    if (!phoneVal || !/^\d+$/.test(phoneVal)) {
                        Swal.fire({
                            title: 'Nomor WhatsApp Tidak Valid!',
                            text: 'Nomor WhatsApp wajib diisi angka saja tanpa karakter lain.',
                            icon: 'error',
                            confirmButtonColor: '#000666'
                        });
                        return;
                    }

                    if (!passwordValid) {
                        Swal.fire({
                            title: 'Kata Sandi Kurang Sesuai!',
                            text: 'Harap pastikan kata sandi memenuhi kriteria dan konfirmasi kata sandi cocok.',
                            icon: 'error',
                            confirmButtonColor: '#000666'
                        });
                        return;
                    }
                    
                    const namaFull = document.getElementById('nama').value.trim();
                    const nameParts = namaFull.split(' ');
                    let firstName = namaFull;
                    let lastName = '';
                    
                    if (nameParts.length > 1) {
                        lastName = nameParts.pop();
                        firstName = nameParts.join(' ');
                    }
                    
                    const formData = new FormData(this);
                    formData.append('first_name', firstName);
                    formData.append('last_name', lastName);
                    
                    fetch('/auth/register', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire({
                                title: 'Pendaftaran Berhasil!',
                                text: data.message || 'Akun Anda berhasil dibuat. Mengalihkan ke Dashboard...',
                                icon: 'success',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = data.redirect || '/candidate/dashboard';
                            });
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                    .catch(err => Swal.fire('Error!', 'Koneksi ke server gagal.', 'error'));
                });
            </script>
            
            <!-- Instruction -->
            <div class="mt-8 p-6 bg-blue-50 rounded-xl border-l-4 border-blue-500">
                <div class="flex gap-4">
                    <span class="material-symbols-outlined text-blue-600" data-icon="info" style="font-variation-settings: 'FILL' 1;">info</span>
                    <div>
                        <p class="text-sm font-bold text-blue-900">Alur Setelah Pendaftaran</p>
                        <p class="text-xs text-blue-800 mt-1 leading-relaxed">
                            Setelah pendaftaran akun berhasil, Anda akan langsung masuk ke <b>Dashboard Kandidat</b> untuk memilih divisi pilihan Anda (Post Production, Editorial, Broadcast, atau Visual Production).
                            <br><br>
                            Sudah memiliki akun? <a class="text-blue-700 font-bold hover:underline" href="/signin">Masuk di sini</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
