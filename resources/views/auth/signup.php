<style>
    .signature-gradient {
        background: linear-gradient(135deg, #000666 0%, #1a237e 100%);
    }
    .custom-shadow {
        box-shadow: 0 20px 40px rgba(0, 7, 103, 0.06);
    }
    /* Kustomisasi scrollbar untuk form yang panjang */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }
    .custom-scrollbar:hover::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
    }
</style>

<div class="flex-grow flex flex-col lg:flex-row items-center justify-center px-6 py-2 gap-8 lg:gap-12 max-w-7xl mx-auto w-full">
    <!-- Left Column: Editorial Content -->
    <div class="w-full lg:w-5/12 space-y-8 lg:pr-8">
        <div class="space-y-4">
            <h1 class="text-4xl lg:text-5xl font-headline font-extrabold text-primary leading-tight tracking-tighter">Mulai <br/><span class="text-secondary">Karir Cemerlang</span> Anda.</h1>
            <p class="text-on-surface-variant text-base max-w-md leading-relaxed">Bergabunglah dengan tim kami untuk menciptakan inovasi dan memberikan dampak positif. Lengkapi profil Anda secara komprehensif untuk memulai proses rekrutmen.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="p-6 rounded-xl bg-surface-container-low">
                <span class="material-symbols-outlined text-primary mb-3 text-3xl">work_history</span>
                <h3 class="font-headline font-bold text-primary">Proses Transparan</h3>
                <p class="text-sm text-on-surface-variant mt-1">Lacak status lamaran Anda secara real-time dari satu dasbor.</p>
            </div>
            <div class="p-6 rounded-xl bg-surface-container-low">
                <span class="material-symbols-outlined text-primary mb-3 text-3xl">admin_panel_settings</span>
                <h3 class="font-headline font-bold text-primary">Data Aman</h3>
                <p class="text-sm text-on-surface-variant mt-1">Privasi dan dokumen pelamar terjamin kerahasiaannya.</p>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Registration Form -->
    <div class="w-full lg:w-7/12 relative">
        <!-- Background Decorative Element -->
        <div class="absolute -top-12 -right-12 w-64 h-64 signature-gradient opacity-10 rounded-full blur-3xl -z-10"></div>
        
        <div class="bg-surface-container-lowest custom-shadow rounded-xl p-8 lg:p-10 border border-outline-variant/15 max-h-[85vh] overflow-y-auto custom-scrollbar">
            <div class="mb-6">
                <h2 class="text-2xl font-headline font-extrabold text-[#000767] mb-2">Pendaftaran Kandidat</h2>
                <p class="text-on-surface-variant text-sm">Mohon isi data diri Anda dengan lengkap dan unggah dokumen pendukung.</p>
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
            <?php elseif (isset($info) && !empty($info)): ?>
            <!-- Pesan Informasi -->
            <div class="mb-6 bg-[#eff6ff] border border-[#93c5fd] rounded-xl p-4 flex items-start gap-3 shadow-sm transition-all">
                <div class="bg-blue-100 p-1.5 rounded-full shrink-0 flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-600 text-sm">info</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-blue-800 font-headline">Informasi</h4>
                    <p class="mt-1 text-sm text-blue-700 font-medium"><?= htmlspecialchars($info) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <form id="signupForm" class="space-y-6" action="/auth/register" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= \App\Middleware\SecurityMiddleware::getCsrfToken() ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Lengkap -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="nama">Nama Lengkap</label>
                        <div class="relative">
                            <input required name="nama" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="nama" placeholder="Masukkan nama sesuai KTP" type="text"/>
                        </div>
                    </div>
                    
                    <!-- Email Aktif -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="email">Email Aktif</label>
                        <div class="relative">
                            <input required name="email" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="email" placeholder="email@contoh.com" type="email"/>
                        </div>
                    </div>
                    
                    <!-- Nomor Telepon -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="phone">No. Telp / WhatsApp</label>
                        <div class="relative">
                            <input required name="phone" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="phone" placeholder="081234567890" type="tel"/>
                        </div>
                    </div>
                    
                    <!-- Kata Sandi -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="password">Kata Sandi</label>
                        <div class="relative group">
                            <input required name="password" class="w-full bg-surface-container-high border-none rounded-lg pl-4 pr-12 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="password" placeholder="Minimal 8 karakter" type="password"/>
                            <button type="button" id="togglePassword" class="absolute right-3 top-3.5 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                        <!-- Strength Indicators -->
                        <div id="pw-strength-box" class="hidden p-4 bg-surface-container-low rounded-xl border border-outline-variant/15 text-xs space-y-2 mt-2">
                            <p class="font-bold text-on-surface-variant mb-1.5">Kriteria Kata Sandi Kuat:</p>
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
                                <div id="pw-spec" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                    <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Karakter Simbol (@$!%*?&...)
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tautan Profesional -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="portfolio">Tautan Profesional (LinkedIn / Portofolio)</label>
                        <div class="relative">
                            <input required name="portfolio" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="portfolio" placeholder="https://linkedin.com/in/..." type="url"/>
                        </div>
                    </div>
                    
                    <!-- Riwayat Pendidikan -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="education">Riwayat Pendidikan Terakhir</label>
                        <div class="relative">
                            <input required name="education" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="education" placeholder="Contoh: S1 Teknik Informatika, Universitas..." type="text"/>
                        </div>
                    </div>
                    
                    <!-- Ekspektasi Gaji -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="salary">Ekspektasi Gaji (IDR)</label>
                        <div class="relative">
                            <input required name="salary" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="salary" placeholder="Contoh: 8000000" type="number" min="0"/>
                        </div>
                    </div>

                    <!-- Riwayat Pekerjaan (Opsional) -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="experience">Riwayat Pekerjaan (Opsional)</label>
                        <p class="text-xs text-on-surface-variant mb-2 ml-1">Mencakup nama perusahaan sebelumnya, posisi, durasi, dan deskripsi singkat tanggung jawab.</p>
                        <div class="relative">
                            <textarea name="experience" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface placeholder:text-outline/50" id="experience" rows="4" placeholder="Kosongkan jika Anda belum memiliki pengalaman kerja."></textarea>
                        </div>
                    </div>
                    
                    <!-- File Resume / CV -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="resume">File Resume / CV (PDF maks 10MB)</label>
                        <div class="relative">
                            <input required name="resume" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer" id="resume" type="file" accept="application/pdf"/>
                        </div>
                    </div>
                    
                    <!-- File Surat Lamaran / Cover Letter -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1" for="cover_letter">Surat Lamaran / Cover Letter (PDF maks 10MB)</label>
                        <div class="relative">
                            <input required name="cover_letter" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-3 focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer" id="cover_letter" type="file" accept="application/pdf"/>
                        </div>
                    </div>
                </div>
                
                <div class="pt-4 mt-6">
                    <button class="w-full signature-gradient text-on-primary font-headline font-bold py-4 rounded-lg shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-3 group" type="submit">
                        Kirim Pendaftaran 
                        <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </button>
                </div>
            </form>

            <script>
                // Toggle Show/Hide Password
                const passwordInput = document.getElementById('password');
                const togglePasswordBtn = document.getElementById('togglePassword');
                if (passwordInput && togglePasswordBtn) {
                    togglePasswordBtn.addEventListener('click', function() {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        this.querySelector('.material-symbols-outlined').textContent = type === 'password' ? 'visibility' : 'visibility_off';
                    });
                }

                // Password Strength Real-time UX
                const pwStrengthBox = document.getElementById('pw-strength-box');
                const pwLen = document.getElementById('pw-len');
                const pwUpper = document.getElementById('pw-upper');
                const pwLower = document.getElementById('pw-lower');
                const pwNum = document.getElementById('pw-num');
                const pwSpec = document.getElementById('pw-spec');

                let passwordValid = false;

                if (passwordInput) {
                    passwordInput.addEventListener('focus', () => {
                        pwStrengthBox.classList.remove('hidden');
                    });

                    passwordInput.addEventListener('input', function() {
                        const val = this.value;
                        
                        const hasLen = val.length >= 8;
                        const hasUpper = /[A-Z]/.test(val);
                        const hasLower = /[a-z]/.test(val);
                        const hasNum = /[0-9]/.test(val);
                        const hasSpec = /[^A-Za-z0-9]/.test(val);

                        updateCrit(pwLen, hasLen);
                        updateCrit(pwUpper, hasUpper);
                        updateCrit(pwLower, hasLower);
                        updateCrit(pwNum, hasNum);
                        updateCrit(pwSpec, hasSpec);

                        passwordValid = hasLen && hasUpper && hasLower && hasNum && hasSpec;
                    });
                }

                function updateCrit(elem, met) {
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

                    if (!passwordValid) {
                        Swal.fire({
                            title: 'Kata Sandi Kurang Kuat!',
                            text: 'Harap penuhi semua kriteria kata sandi kuat sebelum melanjutkan pendaftaran.',
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
                    const params = new URLSearchParams(formData);
                    
                    fetch('/auth/register', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: params,
                        credentials: 'same-origin'
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire({
                                title: 'Pendaftaran Berhasil!',
                                text: data.message,
                                icon: 'success',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = data.redirect;
                            });
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                            .catch(err => Swal.fire('Error!', err.message || 'Koneksi ke server gagal.', 'error'));
                });
            </script>
            
            <!-- Instruction -->
            <div class="mt-8 p-6 bg-blue-50 rounded-xl border-l-4 border-blue-500">
                <div class="flex gap-4">
                    <span class="material-symbols-outlined text-blue-600" data-icon="info" style="font-variation-settings: 'FILL' 1;">info</span>
                    <div>
                        <p class="text-sm font-bold text-blue-900">Persetujuan & Akses</p>
                        <p class="text-xs text-blue-800 mt-1 leading-relaxed">
                            Dengan mengklik Kirim Pendaftaran, Anda menyatakan bahwa data yang diberikan adalah benar dan mengizinkan tim kami untuk memproses data tersebut guna keperluan rekrutmen.
                            <br><br>
                            Sudah memiliki akun? <a class="text-blue-700 font-bold hover:underline" href="/signin">Masuk di sini</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
