<?php if (isset($showMoodWarning) && $showMoodWarning): ?>
    <!-- Mood Pulse Check Warning Banner -->
    <div class="mb-6 bg-amber-50 border border-amber-250 p-5 rounded-2xl flex gap-4 items-start shadow-sm animate-pulse-subtle">
        <span class="material-symbols-outlined text-amber-600 text-3xl font-bold">sentiment_satisfied</span>
        <div class="flex-grow">
            <h4 class="text-amber-900 font-extrabold text-sm">Pulse Check Mood 2 Mingguan</h4>
            <p class="text-amber-800/90 text-xs mt-1 leading-relaxed font-semibold">
                Anda belum mengisi Pulse Check Mood & Beban Kerja untuk periode ini. Pengisian ini bersifat opsional, namun masukan Anda sangat berharga untuk memantau kenyamanan kerja tim.
            </p>
            <button onclick="openMoodPulseModal()" class="mt-3 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs py-2 px-4 rounded-xl transition-all shadow-sm cursor-pointer inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">edit_note</span>
                <span>Isi Mood (5 Detik)</span>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Section: Read Only Data -->
<section>
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-3xl font-black text-on-surface tracking-tight">Ringkasan Dasbor</h2>
            <p class="text-on-surface-variant mt-1">Selamat datang di portal layanan mandiri karyawan siCare.</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-surface-container-low p-5 rounded-lg border-l-4 border-primary/20">
            <label class="text-[10px] uppercase font-bold tracking-tighter text-on-surface-variant block mb-1">Status Karyawan</label>
            <p class="text-on-surface font-semibold text-green-700 flex items-center gap-2"><span class="material-symbols-outlined text-sm">check_circle</span> Aktif</p>
        </div>
        <div class="bg-surface-container-low p-5 rounded-lg border-l-4 border-primary/20">
            <label class="text-[10px] uppercase font-bold tracking-tighter text-on-surface-variant block mb-1">Tingkat Akses (Role)</label>
            <p class="text-on-surface font-semibold uppercase"><?php echo htmlspecialchars($role ?? 'Candidate'); ?></p>
        </div>
        <div class="bg-surface-container-low p-5 rounded-lg border-l-4 border-primary/20">
            <label class="text-[10px] uppercase font-bold tracking-tighter text-on-surface-variant block mb-1">ID Pengguna Internal</label>
            <p class="text-on-surface font-semibold">Belum Ditentukan</p>
        </div>
        <div class="bg-surface-container-low p-5 rounded-lg border-l-4 border-primary/20">
            <label class="text-[10px] uppercase font-bold tracking-tighter text-on-surface-variant block mb-1">Sisa Cuti Tahunan</label>
            <p class="text-on-surface font-semibold">12 Hari</p>
        </div>
    </div>
</section>

<!-- Security Awareness Card -->
<div class="mt-8 bg-blue-50 border border-blue-100 p-6 rounded-xl flex gap-4 items-start">
    <span class="material-symbols-outlined text-blue-600 mt-0.5">info</span>
    <div>
        <h4 class="text-blue-900 font-bold">Pusat Bantuan</h4>
        <p class="text-blue-800/80 text-sm mt-1 leading-relaxed">Jika Anda membutuhkan perubahan data administratif seperti KTP, NPWP, atau Rekening Bank, silakan masuk ke menu Profil Pribadi dan klik "Ajukan Perbaikan".</p>
    </div>
</div>

<!-- Access Logs Section -->
<div class="mt-8 bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-outline-variant/10 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-primary font-bold">security</span>
            <h3 class="text-base font-extrabold text-on-surface font-headline">Aktivitas Akses Aplikasi</h3>
        </div>
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider bg-surface-container-low px-3 py-1.5 rounded-full">
            <?= count($loginLogs ?? []) ?> Riwayat
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse table-standardized" data-has-custom-pagination="true">
            <thead>
                <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/10">
                    <th class="py-3.5 px-6 text-[10px] font-bold uppercase tracking-wider">Tanggal &amp; Waktu</th>
                    <th class="py-3.5 px-6 text-[10px] font-bold uppercase tracking-wider">Status</th>
                    <th class="py-3.5 px-6 text-[10px] font-bold uppercase tracking-wider">Alamat IP</th>
                    <th class="py-3.5 px-6 text-[10px] font-bold uppercase tracking-wider">Browser &amp; Sistem Operasi</th>
                    <th class="py-3.5 px-6 text-right text-[10px] font-bold uppercase tracking-wider">Lokasi Akses</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/8" id="loginLogsTableBody">
                <?php if (empty($loginLogs)): ?>
                <tr>
                    <td colspan="5" class="py-12 px-6 text-center">
                        <span class="material-symbols-outlined text-4xl text-outline-variant block mb-3">lock_open</span>
                        <p class="text-on-surface-variant font-semibold text-sm">Belum ada riwayat akses tercatat.</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($loginLogs as $log): ?>
                <?php
                    $loginAt = date('d M Y, H:i:s', strtotime($log['login_at']));
                    
                    // Simple User Agent Parser
                    $ua = $log['user_agent'] ?? '';
                    $browser = 'Browser';
                    $os = 'Unknown OS';
                    if (preg_match('/chrome/i', $ua)) {
                        $browser = 'Chrome';
                    } elseif (preg_match('/firefox/i', $ua)) {
                        $browser = 'Firefox';
                    } elseif (preg_match('/safari/i', $ua)) {
                        $browser = 'Safari';
                    } elseif (preg_match('/edge/i', $ua)) {
                        $browser = 'Edge';
                    }
                    
                    if (preg_match('/windows/i', $ua)) {
                        $os = 'Windows';
                    } elseif (preg_match('/mac/i', $ua)) {
                        $os = 'macOS';
                    } elseif (preg_match('/linux/i', $ua)) {
                        $os = 'Linux';
                    } elseif (preg_match('/iphone|ipad/i', $ua)) {
                        $os = 'iOS';
                    } elseif (preg_match('/android/i', $ua)) {
                        $os = 'Android';
                    }
                    
                    $deviceInfo = $browser . ' (' . $os . ')';
                ?>
                <tr class="login-log-row hover:bg-surface-container-low/30 transition-colors">
                    <td class="py-3.5 px-6">
                        <span class="font-bold text-xs text-on-surface"><?= $loginAt ?></span>
                    </td>
                    <td class="py-3.5 px-6">
                        <?php if (($log['status'] ?? '') === 'Login'): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Login
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Akses Aplikasi
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3.5 px-6">
                        <span class="font-mono text-xs font-semibold text-on-surface-variant"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></span>
                    </td>
                    <td class="py-3.5 px-6">
                        <span class="text-xs font-semibold text-on-surface-variant" title="<?= htmlspecialchars($ua) ?>"><?= $deviceInfo ?></span>
                    </td>
                    <td class="py-3.5 px-6 text-right">
                        <?php if ($log['latitude'] && $log['longitude']): ?>
                        <button type="button" 
                                onclick="window.showLeafletMap('Lokasi Akses', {
                                    in_lat: <?= (float)$log['latitude'] ?>,
                                    in_lng: <?= (float)$log['longitude'] ?>,
                                    clock_in: '<?= date('H:i', strtotime($log['login_at'])) ?>',
                                    work_mode: 'Access Log',
                                    office_lat: <?= (float)($cfg['office_lat'] ?? -6.2297) ?>,
                                    office_lng: <?= (float)($cfg['office_lng'] ?? 106.8164) ?>,
                                    office_radius: <?= (int)($cfg['office_radius_m'] ?? 150) ?>
                                })"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary border border-primary/20 rounded-md text-[10px] font-bold hover:bg-primary/20 transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-[12px] font-bold">location_on</span>
                            <span>Lihat Peta</span>
                        </button>
                        <?php else: ?>
                        <span class="text-on-surface-variant/40 text-xs font-medium">Tidak Ada Koordinat</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div id="loginLogsPagination" class="px-6 py-4 border-t border-outline-variant/10 flex items-center justify-between bg-surface-container-low/30 hidden">
        <div id="loginLogsPaginationInfo" class="table-pagination-info text-sm text-on-surface-variant font-medium"></div>
        <div class="flex items-center gap-1.5">
            <!-- First Page -->
            <button id="btnLoginLogsFirstPage" onclick="window.firstLoginLogsPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Pertama">
                <span class="material-symbols-outlined text-sm">first_page</span>
            </button>
            <!-- Prev Page -->
            <button id="btnPrevLoginLogs" onclick="window.prevLoginLogsPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Sebelumnya">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </button>
            
            <!-- Page numbers container -->
            <div id="loginLogsPageNumbers" class="flex items-center gap-1"></div>

            <!-- Next Page -->
            <button id="btnNextLoginLogs" onclick="window.nextLoginLogsPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Berikutnya">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </button>
            <!-- Last Page -->
            <button id="btnLoginLogsLastPage" onclick="window.lastLoginLogsPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Terakhir">
                <span class="material-symbols-outlined text-sm">last_page</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Pulse Check Mood (Hidden by default) -->
<div id="moodPulseModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col" id="moodPulseModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary font-bold">sentiment_satisfied</span>
                <h3 class="text-base font-extrabold text-on-surface">Pulse Check Mood 2 Mingguan</h3>
            </div>
            <button onclick="closeMoodPulseModal()" class="p-1.5 hover:bg-surface-container-high rounded-full transition-colors cursor-pointer text-on-surface-variant flex items-center justify-center">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="moodPulseForm" onsubmit="submitMoodPulseForm(event)" class="p-6 space-y-5">
            <!-- Mood Picker -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase">Bagaimana Mood Anda hari ini? <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-5 gap-2">
                    <?php 
                    $moodsListDashboard = [
                        'stressed'  => ['label' => 'Stres',  'icon' => 'sentiment_very_dissatisfied', 'color' => 'text-red-650 bg-red-50 border-red-300'],
                        'tired'     => ['label' => 'Lelah',  'icon' => 'sentiment_dissatisfied',   'color' => 'text-amber-650 bg-amber-50 border-amber-300'],
                        'neutral'   => ['label' => 'Biasa',  'icon' => 'sentiment_neutral',        'color' => 'text-slate-650 bg-slate-50 border-slate-300'],
                        'good'      => ['label' => 'Baik',   'icon' => 'sentiment_satisfied',      'color' => 'text-blue-650 bg-blue-50 border-blue-300'],
                        'excellent' => ['label' => 'Sangat', 'icon' => 'sentiment_very_satisfied', 'color' => 'text-emerald-605 bg-emerald-50 border-emerald-300']
                    ];
                    foreach ($moodsListDashboard as $k => $v):
                    ?>
                        <button type="button" onclick="setDashboardMood('<?= $k ?>')" id="db-mood-btn-<?= $k ?>" class="db-mood-option p-3.5 rounded-xl border text-center flex flex-col items-center gap-1 cursor-pointer transition-all duration-200 border-outline-variant/60 bg-surface text-on-surface-variant hover:bg-surface-container-low">
                            <span class="material-symbols-outlined text-2xl"><?= $v['icon'] ?></span>
                            <span class="text-[9px] font-bold"><?= $v['label'] ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="mood_rating" id="dbMoodRatingInput" value="" />
            </div>

            <!-- Workload slider -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase">Tingkat Beban Kerja <span class="text-red-500">*</span></label>
                <div class="bg-surface rounded-xl p-4 border border-outline-variant/50 space-y-3">
                    <div class="flex justify-between items-center text-[10px] font-bold text-on-surface">
                        <span>Beban Kerja</span>
                        <span class="text-primary bg-primary/10 px-2.5 py-0.5 rounded-full" id="dbWorkloadValueLabel">Tingkat 3 - Seimbang</span>
                    </div>
                    <input type="range" name="workload_rating" id="dbWorkloadRange" min="1" max="5" value="3" oninput="updateDbWorkloadLabel(this.value)" class="w-full h-1.5 bg-surface-container-high rounded-lg appearance-none cursor-pointer accent-primary" />
                    <div class="flex justify-between text-[9px] font-bold text-on-surface-variant/70">
                        <span>Ringan</span>
                        <span>Seimbang</span>
                        <span>Padat</span>
                    </div>
                </div>
            </div>
            
            <div class="pt-3 border-t border-outline-variant/15 flex items-center justify-end gap-2 bg-surface-container-low/20 -mx-6 -mb-6 p-4">
                <button type="button" onclick="closeMoodPulseModal()" class="bg-surface-container-high hover:bg-surface-container-high/85 text-on-surface font-bold text-xs py-2 px-4 rounded-xl transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2 px-5 rounded-xl transition-all shadow-md shadow-primary/10 cursor-pointer">
                    Simpan Mood
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const moodsMap = {
            'stressed':  {color: 'text-red-600 bg-red-50 border-red-300 ring-2 ring-primary/20 scale-105 shadow-sm font-bold'},
            'tired':     {color: 'text-amber-600 bg-amber-50 border-amber-300 ring-2 ring-primary/20 scale-105 shadow-sm font-bold'},
            'neutral':   {color: 'text-slate-600 bg-slate-50 border-slate-300 ring-2 ring-primary/20 scale-105 shadow-sm font-bold'},
            'good':      {color: 'text-blue-600 bg-blue-50 border-blue-300 ring-2 ring-primary/20 scale-105 shadow-sm font-bold'},
            'excellent': {color: 'text-emerald-600 bg-emerald-50 border-emerald-300 ring-2 ring-primary/20 scale-105 shadow-sm font-bold'}
        };

        window.openMoodPulseModal = function() {
            const modal = document.getElementById('moodPulseModal');
            const container = document.getElementById('moodPulseModalContainer');
            modal.classList.remove('hidden', 'pointer-events-none', 'opacity-0');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
            setDashboardMood('');
            updateDbWorkloadLabel(3);
        };

        window.closeMoodPulseModal = function() {
            const modal = document.getElementById('moodPulseModal');
            const container = document.getElementById('moodPulseModalContainer');
            modal.classList.add('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-100');
            container.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        };

        window.setDashboardMood = function(mood) {
            document.getElementById('dbMoodRatingInput').value = mood;
            
            document.querySelectorAll('.db-mood-option').forEach(btn => {
                btn.className = 'db-mood-option p-3.5 rounded-xl border text-center flex flex-col items-center gap-1 cursor-pointer transition-all duration-200 border-outline-variant/60 bg-surface text-on-surface-variant hover:bg-surface-container-low';
            });
            
            if (mood && moodsMap[mood]) {
                const activeBtn = document.getElementById('db-mood-btn-' + mood);
                if (activeBtn) {
                    activeBtn.className = 'db-mood-option p-3.5 rounded-xl border text-center flex flex-col items-center gap-1 cursor-pointer transition-all duration-200 ' + moodsMap[mood].color;
                }
            }
        };

        window.updateDbWorkloadLabel = function(val) {
            const labels = {
                1: 'Tingkat 1 - Sangat Ringan',
                2: 'Tingkat 2 - Ringan',
                3: 'Tingkat 3 - Seimbang',
                4: 'Tingkat 4 - Padat',
                5: 'Tingkat 5 - Sangat Padat'
            };
            document.getElementById('dbWorkloadValueLabel').innerText = labels[val] || '';
            document.getElementById('dbWorkloadRange').value = val;
        };

        window.submitMoodPulseForm = function(e) {
            e.preventDefault();
            const mood = document.getElementById('dbMoodRatingInput').value;
            const workload = document.getElementById('dbWorkloadRange').value;

            if (!mood) {
                Swal.fire('Error', 'Silakan pilih mood Anda terlebih dahulu.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('mood_rating', mood);
            formData.append('workload_rating', workload);

            fetch('/employee/reflection/save-mood', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Sukses',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#000666'
                    }).then(() => {
                        closeMoodPulseModal();
                        if (window.loadPage) {
                            window.loadPage('/<?= $roleFolder ?>/dashboard');
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Gagal menyimpan mood', 'error');
            });
        };

        // Auto-record access location on application load/refresh
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])):
        ?>
        if (!window.appAccessLogged) {
            window.appAccessLogged = true;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const fd = new FormData();
                    fd.append('lat', pos.coords.latitude);
                    fd.append('lng', pos.coords.longitude);
                    fetch('/auth/record-login-location', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Access location recorded successfully.');
                        }
                    })
                    .catch(err => console.error('Error recording access location:', err));
                }, err => {
                    console.warn('Geolocation failed for access log:', err);
                    const fd = new FormData();
                    fd.append('lat', '');
                    fd.append('lng', '');
                    fetch('/auth/record-login-location', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    });
                }, { timeout: 10000, enableHighAccuracy: true });
            } else {
                const fd = new FormData();
                fd.append('lat', '');
                fd.append('lng', '');
                fetch('/auth/record-login-location', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
            }
        }
        <?php endif; ?>

        // ── Login Logs Pagination Logic ──────────────────────────────────
        (function() {
            const rows = Array.from(document.querySelectorAll('.login-log-row'));
            const rowsPerPage = 10;
            let currentPage = 1;
            let totalPages = Math.ceil(rows.length / rowsPerPage) || 1;
            
            function renderPagination() {
                if (totalPages <= 1) {
                    document.getElementById('loginLogsPagination').classList.add('hidden');
                    rows.forEach(row => { row.style.display = ''; });
                    return;
                }
                
                document.getElementById('loginLogsPagination').classList.remove('hidden');
                
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                
                rows.forEach((row, index) => {
                    if (index >= start && index < end) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Info text
                const infoEl = document.getElementById('loginLogsPaginationInfo');
                if (infoEl) {
                    const startShow = rows.length === 0 ? 0 : start + 1;
                    const endShow = Math.min(end, rows.length);
                    infoEl.textContent = 'Menampilkan data ' + startShow + ' sampai ' + endShow + ' dari ' + rows.length;
                }

                // Disabled states
                const firstBtn = document.getElementById('btnLoginLogsFirstPage');
                const prevBtn = document.getElementById('btnPrevLoginLogs');
                const nextBtn = document.getElementById('btnNextLoginLogs');
                const lastBtn = document.getElementById('btnLoginLogsLastPage');

                if (firstBtn) firstBtn.disabled = currentPage === 1;
                if (prevBtn) prevBtn.disabled = currentPage === 1;
                if (nextBtn) nextBtn.disabled = currentPage === totalPages;
                if (lastBtn) lastBtn.disabled = currentPage === totalPages;

                // Page numbers (max 3 centered)
                const pageNumbersContainer = document.getElementById('loginLogsPageNumbers');
                if (pageNumbersContainer) {
                    pageNumbersContainer.innerHTML = '';

                    let startPage = 1;
                    let endPage = totalPages;

                    if (totalPages > 3) {
                        if (currentPage === 1) {
                            startPage = 1;
                            endPage = 3;
                        } else if (currentPage === totalPages) {
                            startPage = totalPages - 2;
                            endPage = totalPages;
                        } else {
                            startPage = currentPage - 1;
                            endPage = currentPage + 1;
                        }
                    }

                    for (let p = startPage; p <= endPage; p++) {
                        (function(pageNum) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'w-8 h-8 flex items-center justify-center rounded-full text-xs font-semibold transition-all border ';
                            if (pageNum === currentPage) {
                                btn.className += 'bg-primary text-white border-primary shadow-sm';
                            } else {
                                btn.className += 'hover:bg-surface-container-high text-on-surface border-transparent';
                            }
                            btn.textContent = pageNum;
                            btn.onclick = function() {
                                currentPage = pageNum;
                                renderPagination();
                            };
                            pageNumbersContainer.appendChild(btn);
                        })(p);
                    }
                }
            }
            
            window.firstLoginLogsPage = function() {
                if (currentPage > 1) {
                    currentPage = 1;
                    renderPagination();
                }
            };

            window.prevLoginLogsPage = function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderPagination();
                }
            };

            window.nextLoginLogsPage = function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPagination();
                }
            };

            window.lastLoginLogsPage = function() {
                if (currentPage < totalPages) {
                    currentPage = totalPages;
                    renderPagination();
                }
            };

            renderPagination();
        })();
    })();
</script>
