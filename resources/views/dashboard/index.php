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
    })();
</script>
