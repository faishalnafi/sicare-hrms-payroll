<?php
$db = \App\Config\Database::getInstance()->getConnection();
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
?>
<div class="flex-grow flex items-center justify-center px-6 py-2 relative overflow-hidden">
    <!-- Background Decorative Elements -->
    <div class="absolute top-0 left-0 w-full h-full opacity-5 pointer-events-none">
        <div class="absolute top-[-10%] left-[-5%] w-96 h-96 rounded-full signature-gradient blur-3xl"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-96 h-96 rounded-full signature-gradient blur-3xl"></div>
    </div>
    
    <div class="w-full max-w-[1200px] grid md:grid-cols-2 gap-16 items-center z-10">
        <!-- Left Side: Editorial Content (Asymmetric Layout) -->
        <div class="hidden md:flex flex-col space-y-8 pr-12">
            <div class="inline-flex items-center gap-2 bg-tertiary-fixed text-on-tertiary-fixed-variant px-3 py-1 rounded-full text-xs font-bold tracking-wide uppercase w-max">
                <span class="material-symbols-outlined text-sm" data-icon="shield_lock" style="font-variation-settings: 'FILL' 1;">shield_lock</span> 
                KEAMANAN TINGKAT PERUSAHAAN
            </div>
            <h1 class="font-headline text-6xl font-extrabold text-primary leading-tight tracking-tighter">Akses Masa <br/><span class="text-secondary">Depan Karirmu.</span></h1>
            <p class="text-on-surface-variant text-lg leading-relaxed max-w-md"><?= htmlspecialchars($appName) ?> menyediakan gerbang yang aman dan terpadu untuk mengakses portal HR, melacak lamaran, dan sumber daya perusahaan Anda.</p>
            <div class="flex items-center gap-6 mt-4">
                <div class="flex -space-x-3">
                    <img class="w-10 h-10 rounded-full border-2 border-surface bg-white" alt="Abstract Profile 1" src="https://www.gravatar.com/avatar/12345678901234567890123456789012?d=identicon&f=y"/>
                    <img class="w-10 h-10 rounded-full border-2 border-surface bg-white" alt="Abstract Profile 2" src="https://www.gravatar.com/avatar/abcdefabcdefabcdefabcdefabcdef12?d=identicon&f=y"/>
                    <img class="w-10 h-10 rounded-full border-2 border-surface bg-white" alt="Abstract Profile 3" src="https://www.gravatar.com/avatar/98765432109876543210987654321098?d=identicon&f=y"/>
                </div>
                <p class="text-sm font-medium text-on-surface-variant">Dipercaya oleh 50.000+ karyawan & pelamar.</p>
            </div>
        </div>
        
        <!-- Right Side: Login Card (Central Content Hub) -->
        <div class="flex justify-center md:justify-end">
            <div class="w-full max-w-[480px] bg-surface-container-lowest p-6 md:p-8 rounded-xl shadow-[0_20px_40px_rgba(0,7,103,0.06)] border border-outline-variant/15">
                <div class="mb-6 text-center md:text-left">
                    <span class="font-headline text-2xl font-black text-primary mb-2 block tracking-tight"><?= htmlspecialchars($appName) ?></span>
                    <h2 class="font-headline text-3xl font-bold text-on-surface mt-2">Masuk</h2>
                </div>
                
                <div class="space-y-4">
                    <!-- Alert Messages -->
                    <?php if (isset($error) && !empty($error)): ?>
                    <div class="mb-4 bg-[#fef2f2] border border-[#f87171] rounded-xl p-4 flex items-start gap-3 shadow-sm transition-all">
                        <div class="bg-red-100 p-1.5 rounded-full shrink-0 flex items-center justify-center">
                            <span class="material-symbols-outlined text-red-600 text-sm">error</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-red-800 font-headline">Gagal Masuk</h4>
                            <p class="mt-1 text-sm text-red-700 font-medium">
                                <?php 
                                    if ($error === 'google_auth_failed') {
                                        echo 'Otentikasi dengan Google gagal. Silakan coba kembali.';
                                    } elseif ($error === 'auth_failed') {
                                        echo 'Gagal melakukan otentikasi akun Anda.';
                                    } else {
                                        echo htmlspecialchars($error);
                                    }
                                ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Google Auth Button -->
                    <button onclick="handleGoogleLogin()" class="w-full flex items-center justify-center gap-3 py-2.5 px-4 bg-surface-container-lowest border border-outline-variant/30 rounded-lg text-on-surface-variant font-medium hover:bg-surface-container-low transition-all duration-150">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.27.81-.57z" fill="#FBBC05"></path>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"></path>
                        </svg> 
                        Masuk dengan Google
                    </button>
                    
                    <div class="relative py-4 flex items-center">
                        <div class="flex-grow border-t border-outline-variant opacity-30"></div>
                        <span class="flex-shrink mx-4 text-xs font-bold text-outline uppercase tracking-widest">ATAU MASUK DENGAN ID</span>
                        <div class="flex-grow border-t border-outline-variant opacity-30"></div>
                    </div>
                    
                    <!-- Login Form -->
                    <form id="signinForm" class="space-y-4" action="/auth/login" method="POST">
                        <input type="hidden" name="csrf_token" value="dummy_token_here">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-on-surface-variant ml-1" for="employee_id">Email Karyawan</label>
                            <input class="w-full px-4 py-2.5 bg-surface-container-high border-none rounded-lg focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface" id="email" name="email" placeholder="email@domain.com" type="email" required/>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-on-surface-variant ml-1" for="password">Kata Sandi</label>
                            <div class="relative">
                                <input class="w-full pl-4 pr-12 py-2.5 bg-surface-container-high border-none rounded-lg focus:ring-0 focus:bg-surface-container-lowest focus:border-b-2 focus:border-primary transition-all text-on-surface" id="password" name="password" placeholder="••••••••" type="password" required/>
                                <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors focus:outline-none" onclick="const p = document.getElementById('password'); const i = this.querySelector('span'); if(p.type === 'password'){ p.type = 'text'; i.innerText = 'visibility_off'; } else { p.type = 'password'; i.innerText = 'visibility'; }">
                                    <span class="material-symbols-outlined text-[1.25rem]">visibility</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input class="w-4 h-4 rounded-sm border-outline-variant text-primary focus:ring-primary/20" type="checkbox" id="remember-me" name="remember-me"/>
                                <span class="text-sm text-on-surface-variant group-hover:text-primary transition-colors">Ingat perangkat ini</span>
                            </label>
                            <a class="text-sm font-semibold text-primary hover:underline transition-all" href="#">Lupa kata sandi?</a>
                        </div>
                        <button type="submit" class="w-full signature-gradient text-on-primary py-3 rounded-lg font-bold shadow-lg shadow-primary/10 hover:opacity-90 active:opacity-80 transition-all duration-150 mt-4">
                            Masuk ke Portal
                        </button>
                    </form>

                    <script>
                        function handleGoogleLogin() {
                            const rememberMe = document.getElementById('remember-me').checked;
                            if (rememberMe) {
                                document.cookie = "remember_google=1; path=/; max-age=300; SameSite=Lax";
                            } else {
                                document.cookie = "remember_google=; path=/; max-age=0; SameSite=Lax";
                            }
                            window.location.href = '/auth/google';
                        }

                        document.getElementById('signinForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            const formData = new FormData(this);
                            fetch('/auth/login', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if(data.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
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
                            .catch(err => Swal.fire('Error!', 'Koneksi ke server gagal.', 'error'));
                        });
                    </script>
                    
                    <!-- Instruction for new users -->
                    <div class="mt-5 p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500">
                        <div class="flex gap-4">
                            <span class="material-symbols-outlined text-blue-600" data-icon="info" style="font-variation-settings: 'FILL' 1;">info</span>
                            <div>
                                <p class="text-sm font-bold text-blue-900">Belum punya akun?</p>
                                <p class="text-xs text-blue-800 mt-1 leading-relaxed">
                                    Daftarkan diri Anda untuk melamar pekerjaan atau mengakses portal mandiri. 
                                    <a class="text-blue-700 font-bold hover:underline block mt-1" href="/signup">Daftar Sekarang</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
