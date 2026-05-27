<?php
// HR Ops — Pengaturan Presensi & Lokasi
$cfg = [];
$holidays = [];
try {
    $db = \App\Config\Database::getInstance()->getConnection();
    $rows = $db->query("SELECT `key`, `value` FROM global_settings")->fetchAll();
    foreach ($rows as $r) { $cfg[$r['key']] = $r['value']; }
    
    $holidays = $db->query("SELECT * FROM company_holidays ORDER BY holiday_date ASC")->fetchAll();
} catch (Exception $e) { /* table may not exist yet */ }

// Fallback defaults
$cfg = array_merge([
    'office_lat'         => '-6.2297',
    'office_lng'         => '106.8164',
    'office_radius_m'    => '150',
    'home_radius_m'      => '100',
    'work_start_time'    => '08:00',
    'work_end_time'      => '17:00',
    'grace_period_min'   => '10',
    'office_wifi_prefix' => '192.168.10.',
    'wfa_allowed'        => 'true',
    'wfa_days'           => '',
    'weekly_holidays'    => 'Sat,Sun',
], $cfg);

$wfaDaysArr = array_filter(array_map('trim', explode(',', $cfg['wfa_days'])));
$weeklyHolidaysArr = array_filter(array_map('trim', explode(',', $cfg['weekly_holidays'])));
$allDays = ['Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu', 'Sun' => 'Minggu'];
?>

<style>
.settings-card { background:#fff; border:1px solid rgba(0,6,102,.07); border-radius:1.25rem; box-shadow:0 2px 16px rgba(0,6,102,.04); }
.settings-section-title { font-family:'Manrope',sans-serif; font-weight:800; font-size:.875rem; color:#000666; letter-spacing:.02em; }
.settings-input {
    width:100%; padding:.6rem .9rem; font-size:.8rem; font-weight:600; color:#1a1c2d;
    background:#f8f9fa; border:1.5px solid #dde0f0; border-radius:.75rem;
    transition:border-color .2s, box-shadow .2s; outline:none;
}
.settings-input:focus { border-color:#000666; box-shadow:0 0 0 3px rgba(0,6,102,.08); background:#fff; }
.settings-label { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#666; margin-bottom:.45rem; display:block; }
.settings-helper { font-size:.62rem; color:#888; margin-top:.35rem; font-weight:500; }
.map-preview-btn {
    display:inline-flex; align-items:center; gap:.4rem; padding:.5rem 1rem; border-radius:.65rem;
    background:rgba(0,6,102,.06); color:#000666; font-size:.72rem; font-weight:700;
    border:1.5px solid rgba(0,6,102,.12); cursor:pointer; transition:all .2s;
}
.map-preview-btn:hover { background:rgba(0,6,102,.12); }
.toggle-switch { position:relative; display:inline-block; width:3rem; height:1.5rem; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider {
    position:absolute; inset:0; border-radius:99px;
    background:#ccc; transition:.3s; cursor:pointer;
}
.toggle-slider:before {
    content:''; position:absolute; height:1.1rem; width:1.1rem;
    left:.2rem; bottom:.2rem; background:#fff; border-radius:50%; transition:.3s;
}
.toggle-switch input:checked + .toggle-slider { background:#000666; }
.toggle-switch input:checked + .toggle-slider:before { transform:translateX(1.5rem); }
.day-chip {
    display:inline-flex; align-items:center; justify-content:center;
    width:2.8rem; height:2.8rem; border-radius:.6rem; font-size:.7rem; font-weight:800;
    border:1.5px solid #dde0f0; background:#f8f9fa; color:#888; cursor:pointer; transition:all .2s;
}
.day-chip.active { background:#000666; border-color:#000666; color:#fff; box-shadow:0 4px 12px rgba(0,6,102,.25); }
.save-btn {
    background:linear-gradient(135deg,#000666 0%,#1a237e 100%);
    color:#fff; font-weight:800; font-size:.85rem; padding:.85rem 2.5rem;
    border-radius:.875rem; border:none; cursor:pointer;
    box-shadow:0 6px 20px rgba(0,6,102,.3); transition:all .25s;
    display:inline-flex; align-items:center; gap:.5rem;
}
.save-btn:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(0,6,102,.35); }
.coord-display { font-family:'Courier New',monospace; font-size:.78rem; }
</style>

<div class="space-y-6">

    <!-- ── Header ── -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Pengaturan Sistem</h1>
            <p class="text-on-surface-variant font-medium text-sm">Konfigurasi lokasi kantor, jam kerja, toleransi, dan kebijakan WFA/WFC/WFO/WFH.</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-on-surface-variant bg-surface-container-lowest border border-outline-variant/20 rounded-xl px-4 py-2.5">
            <span class="material-symbols-outlined text-primary text-sm">update</span>
            Perubahan langsung aktif saat disimpan
        </div>
    </div>

    <form id="settingsForm" onsubmit="saveSettings(event)">
    <input type="hidden" name="csrf_token" value="<?= \App\Middleware\SecurityMiddleware::getCsrfToken() ?>">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- ── Col 1–2: Main Settings ── -->
            <div class="xl:col-span-2 space-y-5">

                <!-- === LOKASI KANTOR === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-primary text-xl bg-primary/8 p-2 rounded-xl" style="background:rgba(0,6,102,.07)">location_on</span>
                        <h2 class="settings-section-title">Lokasi Kantor Pusat</h2>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="settings-label" for="office_lat">Latitude</label>
                            <input type="text" id="office_lat" name="office_lat"
                                value="<?= htmlspecialchars($cfg['office_lat']) ?>"
                                class="settings-input coord-display" placeholder="-6.2297"
                                onchange="updateMapPreview()" />
                            <p class="settings-helper">Contoh: <code>-6.2297</code> (negatif = selatan)</p>
                        </div>
                        <div>
                            <label class="settings-label" for="office_lng">Longitude</label>
                            <input type="text" id="office_lng" name="office_lng"
                                value="<?= htmlspecialchars($cfg['office_lng']) ?>"
                                class="settings-input coord-display" placeholder="106.8164"
                                onchange="updateMapPreview()" />
                            <p class="settings-helper">Contoh: <code>106.8164</code> (positif = timur)</p>
                        </div>
                    </div>

                    <!-- Mini map / link preview -->
                    <div class="mt-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                        <button type="button" class="map-preview-btn" onclick="openMapPicker()">
                            <span class="material-symbols-outlined text-sm">map</span>
                            Pilih Titik di Google Maps
                        </button>
                        <a id="mapPreviewLink" href="#" target="_blank"
                            class="map-preview-btn text-emerald-700" style="background:rgba(0,150,80,.07);border-color:rgba(0,150,80,.2)">
                            <span class="material-symbols-outlined text-sm text-emerald-600">open_in_new</span>
                            Lihat di Maps
                        </a>
                        <div id="coordDisplay" class="text-xs font-mono text-on-surface-variant/60 bg-surface-container-low px-3 py-1.5 rounded-lg">
                            <?= htmlspecialchars($cfg['office_lat']) ?>, <?= htmlspecialchars($cfg['office_lng']) ?>
                        </div>
                    </div>

                    <!-- Radius WFO -->
                    <div class="mt-5 pt-5 border-t border-outline-variant/15">
                        <label class="settings-label" for="office_radius_m">Radius Deteksi WFO (meter)</label>
                        <div class="flex items-center gap-4">
                            <input type="range" id="radiusSlider" min="1" max="1000" step="1"
                                value="<?= (int)$cfg['office_radius_m'] ?>"
                                class="flex-1 h-2 rounded-full cursor-pointer accent-primary"
                                oninput="syncRadius(this.value)" />
                            <div class="flex items-center gap-1">
                                <input type="number" id="office_radius_m" name="office_radius_m"
                                    value="<?= (int)$cfg['office_radius_m'] ?>"
                                    min="1" max="1000"
                                    class="settings-input w-24 text-center" style="padding:.5rem"
                                    oninput="syncRadius(this.value)" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">meter</span>
                            </div>
                        </div>
                        <p class="settings-helper">Karyawan dalam radius ini dianggap hadir di kantor (WFO). Minimum 1m, maksimum 1000m.</p>
                        <div id="radiusLabel" class="mt-2 text-xs font-bold text-primary">
                            Radius saat ini: <span id="radiusVal"><?= $cfg['office_radius_m'] ?></span>m
                        </div>
                    </div>

                    <!-- Radius WFH -->
                    <div class="mt-5 pt-5 border-t border-outline-variant/15">
                        <label class="settings-label" for="home_radius_m">Radius Deteksi WFH Rumah Karyawan (meter)</label>
                        <div class="flex items-center gap-4">
                            <input type="range" id="homeRadiusSlider" min="1" max="1000" step="1"
                                value="<?= (int)$cfg['home_radius_m'] ?>"
                                class="flex-1 h-2 rounded-full cursor-pointer accent-primary"
                                oninput="syncHomeRadius(this.value)" />
                            <div class="flex items-center gap-1">
                                <input type="number" id="home_radius_m" name="home_radius_m"
                                    value="<?= (int)$cfg['home_radius_m'] ?>"
                                    min="1" max="1000"
                                    class="settings-input w-24 text-center" style="padding:.5rem"
                                    oninput="syncHomeRadius(this.value)" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">meter</span>
                            </div>
                        </div>
                        <p class="settings-helper">Karyawan dalam radius dari koordinat rumah masing-masing dianggap hadir sebagai WFH (Work From Home). Minimum 1m, maksimum 1000m.</p>
                        <div id="homeRadiusLabel" class="mt-2 text-xs font-bold text-primary">
                            Radius WFH saat ini: <span id="homeRadiusVal"><?= $cfg['home_radius_m'] ?></span>m
                        </div>
                    </div>

                    <!-- WIFI Prefix -->
                    <div class="mt-4">
                        <label class="settings-label" for="office_wifi_prefix">Prefix IP WIFI Kantor (auto-deteksi WFO)</label>
                        <input type="text" id="office_wifi_prefix" name="office_wifi_prefix"
                            value="<?= htmlspecialchars($cfg['office_wifi_prefix']) ?>"
                            class="settings-input font-mono" placeholder="192.168.10." />
                        <p class="settings-helper">Karyawan yang terhubung ke WIFI dengan prefix IP ini otomatis terdeteksi sebagai WFO tanpa perlu GPS.</p>
                    </div>
                </div>

                <!-- === JAM KERJA === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-amber-600 text-xl p-2 rounded-xl" style="background:rgba(245,158,11,.07)">schedule</span>
                        <h2 class="settings-section-title" style="color:#92400e">Jam Kerja & Toleransi Masuk</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="settings-label" for="work_start_time">Jam Masuk Standar</label>
                            <input type="time" id="work_start_time" name="work_start_time"
                                value="<?= htmlspecialchars($cfg['work_start_time']) ?>"
                                class="settings-input font-mono" />
                        </div>
                        <div>
                            <label class="settings-label" for="work_end_time">Jam Pulang Standar</label>
                            <input type="time" id="work_end_time" name="work_end_time"
                                value="<?= htmlspecialchars($cfg['work_end_time']) ?>"
                                class="settings-input font-mono" />
                        </div>
                        <div>
                            <label class="settings-label" for="grace_period_min">Toleransi Terlambat Masuk</label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="grace_period_min" name="grace_period_min"
                                    value="<?= (int)$cfg['grace_period_min'] ?>"
                                    min="0" max="1440"
                                    class="settings-input" style="padding:.6rem" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">menit</span>
                            </div>
                        </div>
                    </div>

                    <!-- Live preview -->
                    <div id="schedulePreview" class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-100 text-xs font-semibold text-amber-800">
                    </div>
                </div>

                <!-- === TOLERANSI PULANG LAMBAT === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-orange-600 text-xl p-2 rounded-xl" style="background:rgba(249,115,22,.07)">timer</span>
                        <h2 class="settings-section-title" style="color:#c2410c">Batas Toleransi Pulang Lambat & Lembur</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="settings-label" for="checkout_grace_period_min">Toleransi Pulang Lambat</label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="checkout_grace_period_min" name="checkout_grace_period_min"
                                    value="<?= (int)$cfg['checkout_grace_period_min'] ?>"
                                    min="0" max="1440"
                                    class="settings-input" style="padding:.6rem" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">menit</span>
                            </div>
                            <p class="settings-helper mt-2">Batas toleransi kepulangan karyawan setelah jam pulang standar. Karyawan yang clock-out melewati batas ini akan tercatat sebagai Pulang Terlambat. Jika melewati hari (masuk jam 00:00) dan belum melakukan presensi pulang, maka akan tercatat sebagai Tidak Presensi Pulang.</p>
                        </div>
                    </div>

                    <!-- Live preview khusus checkout status -->
                    <div id="checkoutPreview" class="mt-4 p-4 rounded-xl bg-orange-50/50 border border-orange-100 text-xs font-semibold text-orange-850">
                    </div>
                </div>

            </div>

            <!-- ── Col 3: WFA/WFC Settings ── -->
            <div class="space-y-5">

                <!-- === WFA/WFC/WFH TOGGLE === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-emerald-600 text-xl p-2 rounded-xl" style="background:rgba(16,185,129,.07)">home_work</span>
                        <h2 class="settings-section-title" style="color:#065f46">Work From Anywhere/Cabin/Home (WFA/WFC/WFH)</h2>
                    </div>

                    <!-- Toggle -->
                    <div class="flex items-center justify-between py-3 border-b border-outline-variant/10">
                        <div>
                            <p class="text-sm font-bold text-on-surface">Izinkan WFA/WFC/WFH</p>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">Karyawan di luar radius kantor dapat Clock-In</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="wfa_allowed" value="true"
                                <?= $cfg['wfa_allowed'] === 'true' ? 'checked' : '' ?>
                                onchange="updateWfaUi()" />
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <input type="hidden" name="wfa_allowed" id="wfa_allowed_hidden" value="<?= $cfg['wfa_allowed'] ?>" />

                    <!-- WFA Day Restrictions -->
                    <div id="wfaDaysSection" class="mt-4 <?= $cfg['wfa_allowed'] !== 'true' ? 'opacity-40 pointer-events-none' : '' ?>">
                        <label class="settings-label mb-3">Hari yang Diizinkan WFA/WFC/WFH</label>
                        <p class="text-[11px] text-on-surface-variant mb-3 font-medium">Biarkan semua tidak dipilih = WFA/WFC/WFH boleh setiap hari.</p>
                        <div class="flex gap-2 flex-wrap">
                            <?php foreach ($allDays as $code => $nama): ?>
                            <div class="day-chip wfa-day-chip <?= in_array($code, $wfaDaysArr) ? 'active' : '' ?>"
                                data-day="<?= $code ?>"
                                onclick="toggleDay(this)">
                                <?= substr($nama, 0, 3) ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="wfa_days" name="wfa_days" value="<?= htmlspecialchars($cfg['wfa_days']) ?>" />
                    </div>

                    <!-- WFA Info box -->
                    <div id="wfaDisabledMsg" class="mt-4 <?= $cfg['wfa_allowed'] === 'true' ? 'hidden' : '' ?> p-3 rounded-xl bg-red-50 border border-red-100 text-xs font-semibold text-red-700 flex items-start gap-2">
                        <span class="material-symbols-outlined text-sm mt-0.5 flex-shrink-0">block</span>
                        WFA/WFC/WFH dinonaktifkan. Karyawan yang berada di luar radius kantor akan ditolak saat melakukan Clock-In.
                    </div>
                </div>

                <!-- === HARI LIBUR MINGGUAN & TANGGAL MERAH === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-red-600 text-xl p-2 rounded-xl" style="background:rgba(239,68,68,.07)">calendar_today</span>
                        <h2 class="settings-section-title" style="color:#b91c1c">Hari Libur Perusahaan</h2>
                    </div>

                    <!-- Hari Libur Mingguan (Clickable Chips) -->
                    <div class="mb-5 pb-5 border-b border-outline-variant/10">
                        <label class="settings-label mb-3">Hari Libur Mingguan (Akhir Pekan)</label>
                        <p class="text-[11px] text-on-surface-variant mb-3 font-medium">Karyawan tidak diwajibkan melakukan absen pada hari yang dipilih.</p>
                        <div class="flex gap-2 flex-wrap">
                            <?php foreach ($allDays as $code => $nama): ?>
                            <div class="day-chip weekly-holiday-chip <?= in_array($code, $weeklyHolidaysArr) ? 'active' : '' ?>"
                                data-day="<?= $code ?>"
                                onclick="toggleWeeklyHoliday(this)">
                                <?= substr($nama, 0, 3) ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="weekly_holidays" name="weekly_holidays" value="<?= htmlspecialchars($cfg['weekly_holidays']) ?>" />
                    </div>

                    <!-- Tanggal Merah / Hari Libur Nasional -->
                    <div>
                        <label class="settings-label mb-3">Tanggal Merah / Libur Nasional</label>
                        
                        <!-- List of holidays -->
                        <div class="space-y-2 max-h-48 overflow-y-auto mb-4 pr-1" id="holidaysList">
                            <?php if (empty($holidays)): ?>
                            <p class="text-xs font-semibold text-on-surface-variant/60 text-center py-4 bg-surface rounded-xl">Belum ada hari libur nasional.</p>
                            <?php else: ?>
                            <?php foreach ($holidays as $h): ?>
                            <div class="flex justify-between items-center bg-surface p-3 rounded-xl border border-outline-variant/10">
                                <div>
                                    <p class="text-xs font-extrabold text-on-surface"><?= htmlspecialchars($h['description']) ?></p>
                                    <p class="text-[10px] font-mono text-on-surface-variant font-medium mt-0.5"><?= date('d M Y', strtotime($h['holiday_date'])) ?></p>
                                </div>
                                <button type="button" onclick="deleteHoliday('<?= $h['id'] ?>')" class="text-red-500 hover:text-red-700 p-1 flex items-center justify-center hover:bg-red-50 rounded-lg transition-colors">
                                    <span class="material-symbols-outlined text-sm font-bold">delete</span>
                                </button>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Add holiday button trigger -->
                        <button type="button" onclick="openAddHolidayModal()" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold transition-all border border-red-200">
                            <span class="material-symbols-outlined text-sm font-bold">add</span>
                            Tambah Tanggal Merah
                        </button>
                    </div>
                </div>

                <!-- === STATUS OVERVIEW === -->
                <div class="settings-card p-5">
                    <h3 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-4">Konfigurasi Aktif</h3>
                    <div class="space-y-3 text-xs" id="activeConfigDisplay">
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/10">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1 min-w-0 flex-shrink-0"><span class="material-symbols-outlined text-sm text-primary">location_on</span> Koordinat</span>
                            <span class="font-mono font-bold text-on-surface truncate text-right ml-2" id="cfg_coords" title="<?= $cfg['office_lat'] ?>, <?= $cfg['office_lng'] ?>"><?= is_numeric($cfg['office_lat']) ? round((float)$cfg['office_lat'], 6) : $cfg['office_lat'] ?>, <?= is_numeric($cfg['office_lng']) ? round((float)$cfg['office_lng'], 6) : $cfg['office_lng'] ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/10">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-primary">radar</span> Radius WFO</span>
                            <span class="font-bold text-on-surface" id="cfg_radius"><?= $cfg['office_radius_m'] ?>m</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/10">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-primary">home_pin</span> Radius WFH</span>
                            <span class="font-bold text-on-surface" id="cfg_home_radius"><?= $cfg['home_radius_m'] ?>m</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/10">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-amber-600">schedule</span> Jam Masuk</span>
                            <span class="font-bold font-mono text-on-surface" id="cfg_start"><?= $cfg['work_start_time'] ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/10">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-amber-600">schedule</span> Jam Pulang</span>
                            <span class="font-bold font-mono text-on-surface" id="cfg_end"><?= $cfg['work_end_time'] ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/10">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-orange-500">timer</span> Toleransi Masuk</span>
                            <span class="font-bold text-on-surface" id="cfg_grace"><?= $cfg['grace_period_min'] ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/10">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-orange-500">timer</span> Toleransi Pulang Lambat</span>
                            <span class="font-bold text-on-surface" id="cfg_co_grace"><?= $cfg['checkout_grace_period_min'] ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-emerald-600">home_work</span> WFA/WFC/WFH</span>
                            <span id="cfg_wfa" class="font-bold <?= $cfg['wfa_allowed'] === 'true' ? 'text-emerald-600' : 'text-red-600' ?>">
                                <?= $cfg['wfa_allowed'] === 'true' ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <button type="submit" class="save-btn w-full justify-center" id="saveBtn">
                    <span class="material-symbols-outlined text-lg">save</span>
                    Simpan Pengaturan
                </button>

            </div>
        </div>
    </form>

</div>

<script>
// ── Radius sync ──────────────────────────────────────────────
function syncRadius(v) {
    document.getElementById('radiusSlider').value = v;
    document.getElementById('office_radius_m').value = v;
    document.getElementById('radiusVal').textContent = v;
    document.getElementById('cfg_radius').textContent = v + 'm';
}

// ── Home Radius sync ──────────────────────────────────────────
function syncHomeRadius(v) {
    document.getElementById('homeRadiusSlider').value = v;
    document.getElementById('home_radius_m').value = v;
    document.getElementById('homeRadiusVal').textContent = v;
    document.getElementById('cfg_home_radius').textContent = v + 'm';
}

// ── Map preview link ─────────────────────────────────────────
function updateMapPreview() {
    const latInput = document.getElementById('office_lat');
    const lngInput = document.getElementById('office_lng');
    if (latInput && lngInput) {
        latInput.value = latInput.value.replace(',', '.');
        lngInput.value = lngInput.value.replace(',', '.');
        const lat = latInput.value;
        const lng = lngInput.value;
        const link = `https://www.google.com/maps?q=${lat},${lng}&z=17`;
        document.getElementById('mapPreviewLink').href = link;
        
        let dispLat = lat;
        let dispLng = lng;
        if (!isNaN(parseFloat(lat)) && !isNaN(parseFloat(lng))) {
            dispLat = parseFloat(lat).toFixed(6);
            dispLng = parseFloat(lng).toFixed(6);
        }
        
        document.getElementById('coordDisplay').textContent = `${dispLat}, ${dispLng}`;
        if(document.getElementById('cfg_coords')) {
            document.getElementById('cfg_coords').textContent = `${dispLat}, ${dispLng}`;
            document.getElementById('cfg_coords').title = `${lat}, ${lng}`;
        }
    }
}
updateMapPreview(); // init

// ── Open Google Maps for coordinate picking ──────────────────
function openMapPicker() {
    const lat = document.getElementById('office_lat').value.replace(',', '.') || '-6.2297';
    const lng = document.getElementById('office_lng').value.replace(',', '.') || '106.8164';
    const url = `https://www.google.com/maps?q=${lat},${lng}&z=17`;
    Swal.fire({
        title: '📍 Pilih Koordinat Kantor',
        html: `
            <p class="text-sm text-gray-600 mb-3">Buka Google Maps di bawah, klik kanan pada titik lokasi kantor, lalu salin koordinatnya (baris pertama) dan paste ke form.</p>
            <a href="${url}" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 transition-colors">
                <span class="material-symbols-outlined text-base">open_in_new</span> Buka Google Maps
            </a>
            <div class="mt-4 grid grid-cols-2 gap-3 text-left">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Latitude</label>
                    <input id="swalLat" type="number" step="0.00001" value="${lat}" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Longitude</label>
                    <input id="swalLng" type="number" step="0.00001" value="${lng}" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:border-blue-500" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#000666',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Terapkan Koordinat',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const lat = document.getElementById('swalLat').value;
            const lng = document.getElementById('swalLng').value;
            if (!lat || !lng) { Swal.showValidationMessage('Latitude dan Longitude wajib diisi.'); return false; }
            return { lat, lng };
        }
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('office_lat').value = r.value.lat;
            document.getElementById('office_lng').value = r.value.lng;
            updateMapPreview();
        }
    });
}

// ── WFA toggle ───────────────────────────────────────────────
function updateWfaUi() {
    const chk = document.getElementById('wfa_allowed');
    const hidden = document.getElementById('wfa_allowed_hidden');
    const section = document.getElementById('wfaDaysSection');
    const disabledMsg = document.getElementById('wfaDisabledMsg');
    const cfgWfa = document.getElementById('cfg_wfa');
    hidden.value = chk.checked ? 'true' : 'false';
    section.classList.toggle('opacity-40', !chk.checked);
    section.classList.toggle('pointer-events-none', !chk.checked);
    disabledMsg.classList.toggle('hidden', chk.checked);
    cfgWfa.textContent = chk.checked ? 'Aktif' : 'Nonaktif';
    cfgWfa.className = `font-bold ${chk.checked ? 'text-emerald-600' : 'text-red-600'}`;
}

// ── Day chips ────────────────────────────────────────────────
function toggleDay(el) {
    const day = el.dataset.day;
    // Pengecekan prioritas: apakah hari ini adalah hari libur mingguan?
    const weeklyChip = document.querySelector(`.weekly-holiday-chip[data-day="${day}"]`);
    if (weeklyChip && weeklyChip.classList.contains('active')) {
        Swal.fire({
            title: 'Hari Libur Mingguan',
            text: 'Hari yang dipilih dikonfigurasi sebagai hari libur mingguan perusahaan. WFA/WFC/WFH tidak dapat diaktifkan pada hari libur.',
            icon: 'warning',
            confirmButtonColor: '#ba1a1a'
        });
        return;
    }
    el.classList.toggle('active');
    const activeDays = Array.from(document.querySelectorAll('.wfa-day-chip.active')).map(d => d.dataset.day);
    document.getElementById('wfa_days').value = activeDays.join(',');
}

// ── Schedule preview ─────────────────────────────────────────
function updateSchedulePreview() {
    const start = document.getElementById('work_start_time').value;
    const end   = document.getElementById('work_end_time').value;
    const grace = document.getElementById('grace_period_min').value;
    const coGrace = document.getElementById('checkout_grace_period_min').value;

    if (!start || !end) return;
    const [sh, sm] = start.split(':').map(Number);
    const deadline = new Date(0, 0, 0, sh, sm + parseInt(grace || 0));
    const deadlineStr = deadline.toTimeString().substring(0,5);

    const [eh, em] = end.split(':').map(Number);
    const coDeadline = new Date(0, 0, 0, eh, em + parseInt(coGrace || 0));
    const coDeadlineStr = coDeadline.toTimeString().substring(0,5);

    document.getElementById('schedulePreview').innerHTML = `
        <div class="flex items-start gap-2">
            <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">info</span>
            <div>
                <strong>Jam Masuk Standar ${start}</strong> &nbsp;·&nbsp; Toleransi Masuk ${grace} menit<br>
                Karyawan masuk hingga <strong>${deadlineStr}</strong> masih dianggap <span class="text-green-700 font-semibold">Tepat Waktu</span>. Masuk setelah <strong>${deadlineStr}</strong> tercatat sebagai <span class="text-amber-700 font-semibold">Terlambat</span>.
            </div>
        </div>
    `;

    if (document.getElementById('checkoutPreview')) {
        document.getElementById('checkoutPreview').innerHTML = `
            <div class="flex items-start gap-2">
                <span class="material-symbols-outlined text-orange-600 text-sm mt-0.5">info</span>
                <div>
                    <strong>Jam Pulang Standar ${end}</strong> &nbsp;·&nbsp; Toleransi Pulang Lambat ${coGrace} menit<br>
                    Karyawan pulang hingga <strong>${coDeadlineStr}</strong> masih dianggap <span class="text-emerald-700 font-semibold">Wajar</span>. Pulang setelah <strong>${coDeadlineStr}</strong> tercatat sebagai <span class="text-orange-700 font-semibold">Pulang Terlambat</span>. Jika melewati hari (masuk jam 00:00) dan belum melakukan presensi pulang, maka tercatat sebagai <span class="text-rose-700 font-semibold">Tidak Presensi Pulang</span>.
                </div>
            </div>
        `;
    }

    document.getElementById('cfg_start').textContent = start;
    document.getElementById('cfg_end').textContent = end;
    document.getElementById('cfg_grace').textContent = grace + ' menit';
    if (document.getElementById('cfg_co_grace')) {
        document.getElementById('cfg_co_grace').textContent = coGrace + ' menit';
    }
}

// Init and bind live preview
['work_start_time','work_end_time','grace_period_min','checkout_grace_period_min'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateSchedulePreview);
});
updateSchedulePreview();

// ── Save ─────────────────────────────────────────────────────
function saveSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.innerHTML = '<div style="width:18px;height:18px;border:2.5px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite"></div> Menyimpan...';
    btn.disabled = true;

    // Auto-normalize commas to dots in coordinate inputs
    const latInput = document.getElementById('office_lat');
    const lngInput = document.getElementById('office_lng');
    latInput.value = latInput.value.replace(',', '.');
    lngInput.value = lngInput.value.replace(',', '.');
    updateMapPreview();

    // Sync hidden wfa_allowed field before submit
    const chk = document.getElementById('wfa_allowed');
    document.getElementById('wfa_allowed_hidden').value = chk.checked ? 'true' : 'false';

    const fd = new FormData(document.getElementById('settingsForm'));
    // wfa_allowed checkbox doesn't have name anymore, only hidden field is captured

    fetch('/admin/settings/save', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<span class="material-symbols-outlined text-lg">save</span> Simpan Pengaturan';
        btn.disabled = false;
        if (data.success) {
            Swal.fire({
                title: '✅ Tersimpan!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#000666',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                if (window.loadPage) {
                    window.loadPage(window.location.pathname + window.location.search);
                } else {
                    window.location.reload();
                }
            });
        } else {
            Swal.fire({ title: 'Gagal', text: data.message, icon: 'error', confirmButtonColor: '#ba1a1a' });
        }
    })
    .catch(() => {
        btn.innerHTML = '<span class="material-symbols-outlined text-lg">save</span> Simpan Pengaturan';
        btn.disabled = false;
        Swal.fire({ title: 'Error Koneksi', text: 'Tidak dapat terhubung ke server.', icon: 'error', confirmButtonColor: '#ba1a1a' });
    });
}

// ── Toggle Weekly Holiday ────────────────────────────────────
function toggleWeeklyHoliday(el) {
    el.classList.toggle('active');
    const day = el.dataset.day;
    
    // Jika diaktifkan sebagai libur mingguan, otomatis matikan WFA pada hari tersebut
    if (el.classList.contains('active')) {
        const wfaChip = document.querySelector(`.wfa-day-chip[data-day="${day}"]`);
        if (wfaChip && wfaChip.classList.contains('active')) {
            wfaChip.classList.remove('active');
        }
    }
    
    const activeWeekly = Array.from(document.querySelectorAll('.weekly-holiday-chip.active')).map(d => d.dataset.day);
    document.getElementById('weekly_holidays').value = activeWeekly.join(',');
    
    // Sinkronisasi ulang input WFA days
    const activeWfa = Array.from(document.querySelectorAll('.wfa-day-chip.active')).map(d => d.dataset.day);
    document.getElementById('wfa_days').value = activeWfa.join(',');
}

// ── Open Add Holiday Modal ───────────────────────────────────
function openAddHolidayModal() {
    Swal.fire({
        title: '🗓️ Tambah Hari Libur / Tanggal Merah',
        html: `
            <div class="text-left space-y-4 text-sm mt-2">
                <div>
                    <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Tanggal Libur</label>
                    <input type="date" id="swalHolidayDate" class="w-full p-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm" />
                </div>
                <div>
                    <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Keterangan / Nama Libur</label>
                    <input type="text" id="swalHolidayDesc" placeholder="Contoh: Hari Raya Idul Fitri" class="w-full p-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#b91c1c',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Tambah Libur',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const date = document.getElementById('swalHolidayDate').value;
            const desc = document.getElementById('swalHolidayDesc').value.trim();
            if (!date || !desc) { Swal.showValidationMessage('Tanggal dan Keterangan wajib diisi.'); return false; }
            return { date, desc };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        
        const fd = new FormData();
        fd.append('holiday_date', result.value.date);
        fd.append('description', result.value.desc);
        
        fetch('/admin/holidays/add', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ title: 'Berhasil!', text: data.message, icon: 'success', confirmButtonColor: '#000666' }).then(() => {
                    if (window.loadPage) { window.loadPage(window.location.pathname + window.location.search); }
                    else { window.location.reload(); }
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        });
    });
}

// ── Delete Holiday ───────────────────────────────────────────
function deleteHoliday(id) {
    Swal.fire({
        title: 'Hapus Hari Libur?',
        text: 'Apakah Anda yakin ingin menghapus tanggal merah ini dari agenda perusahaan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ba1a1a',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (!result.isConfirmed) return;
        
        const fd = new FormData();
        fd.append('id', id);
        
        fetch('/admin/holidays/delete', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ title: 'Terhapus!', text: data.message, icon: 'success', confirmButtonColor: '#000666' }).then(() => {
                    if (window.loadPage) { window.loadPage(window.location.pathname + window.location.search); }
                    else { window.location.reload(); }
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        });
    });
}

// Expose functions globally to prevent ReferenceErrors due to IIFE encapsulation in SPA router
window.syncRadius = syncRadius;
window.syncHomeRadius = syncHomeRadius;
window.updateMapPreview = updateMapPreview;
window.openMapPicker = openMapPicker;
window.updateWfaUi = updateWfaUi;
window.toggleDay = toggleDay;
window.toggleWeeklyHoliday = toggleWeeklyHoliday;
window.openAddHolidayModal = openAddHolidayModal;
window.deleteHoliday = deleteHoliday;
window.updateSchedulePreview = updateSchedulePreview;
window.saveSettings = saveSettings;
</script>
