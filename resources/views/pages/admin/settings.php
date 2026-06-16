<?php
// HR Ops — Pengaturan Presensi & Lokasi
$isGlobalConfig = (strpos($_SERVER['REQUEST_URI'], '/superadmin/settings') !== false && strpos($_SERVER['REQUEST_URI'], '/superadmin/settings/') === false);
$pageTitle = $isGlobalConfig ? 'Konfigurasi Global' : 'Pengaturan Sistem';
$pageSubtitle = $isGlobalConfig 
    ? 'Konfigurasi parameter sistem global, keamanan, serta kebijakan operasional perusahaan.' 
    : 'Konfigurasi lokasi kantor, jam kerja, toleransi, dan kebijakan WFA/WFC/WFO/WFH.';

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
    'office_lat'               => '-6.2297',
    'office_lng'               => '106.8164',
    'office_radius_m'          => '150',
    'home_radius_m'            => '100',
    'work_start_time'          => '08:00',
    'work_min_start_time'      => '06:00',
    'work_min_start_time_enabled' => 'true',
    'work_min_end_time'        => '15:00',
    'work_min_end_time_enabled'   => 'false',
    'work_end_time'            => '17:00',
    'grace_period_min'         => '10',
    'office_wifi_prefix'       => '192.168.10.',
    'office_wifi_ipv6_prefix'  => '',
    'wfa_allowed'              => 'true',
    'wfa_days'                 => '',
    'weekly_holidays'          => 'Sat,Sun',
    'payroll_tunj_jabatan_pct' => '15',
    'payroll_tunj_jabatan_cap' => '2500000',
    'payroll_tunj_transport'   => '1500000',
    'payroll_tunj_komunikasi'  => '500000',
    'payroll_late_deduction'   => '50000',
    'payroll_bpjs_tk_pct'      => '2',
    'payroll_bpjs_kes_pct'     => '1',
    'payroll_pph21_pct'        => '2.5',
    'app_name'                 => 'siCare',
    'app_company_name'         => 'PT SI CARE ENTERPRISE',
    'app_logo_icon'            => 'local_police',
    'app_logo_type'            => 'icon',
    'app_logo_image'           => '',
    'app_idle_timeout_sec'     => '0',
    'app_idle_countdown_sec'   => '0',
    'google_maps_api_key'      => '',
], $cfg);

$wfaDaysArr = array_filter(array_map('trim', explode(',', $cfg['wfa_days'])));
$weeklyHolidaysArr = array_filter(array_map('trim', explode(',', $cfg['weekly_holidays'])));
$allDays = ['Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu', 'Sun' => 'Minggu'];
?>

<style>
.settings-card { background:#fff; border:1px solid rgba(0,6,102,.07); border-radius:1.25rem; box-shadow:0 2px 16px rgba(0,6,102,.04); }
/* --- Premium Calendar Styles --- */
.calendar-container {
    background: #ffffff;
    border: 1px solid #dde0f0;
    border-radius: 1rem;
    padding: 1.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
}
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.calendar-month-year {
    font-family: 'Outfit', sans-serif;
    font-size: 0.95rem;
    font-weight: 800;
    color: #000666;
}
.calendar-nav-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: #f1f5f9;
    border: 1px solid #dde0f0;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}
.calendar-nav-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.calendar-grid-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    margin-bottom: 0.5rem;
}
.calendar-weekday {
    font-size: 0.65rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.25rem 0;
}
.calendar-grid-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.35rem;
}
.calendar-day {
    position: relative;
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 700;
    color: #334155;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}
.calendar-day.prev-month, .calendar-day.next-month {
    color: #cbd5e1;
    cursor: default;
    pointer-events: none;
}
.calendar-day.current-month:hover {
    background: #f1f5f9;
    color: #000666;
}
.calendar-day.current-month.is-holiday {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.calendar-day.current-month.is-holiday:hover {
    background: #fee2e2;
}
.calendar-day.current-month.is-holiday::after {
    content: '';
    position: absolute;
    bottom: 0.25rem;
    width: 0.25rem;
    height: 0.25rem;
    border-radius: 50%;
    background: #ef4444;
}
.calendar-day.current-month.is-today {
    border: 1.5px solid #000666;
}
.holiday-item-mini {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    padding: 0.65rem 0.85rem;
    border-radius: 0.75rem;
    border: 1px solid #dde0f0;
    transition: transform 0.2s, box-shadow 0.2s;
}
.holiday-item-mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}
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
@media (min-width: 768px) {
    #logo_icon_wrapper, #logo_image_wrapper {
        grid-column: span 2 / span 2 !important;
    }
}
</style>

<div class="space-y-6">

    <!-- ── Header ── -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-on-surface-variant font-medium text-sm"><?= htmlspecialchars($pageSubtitle) ?></p>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-on-surface-variant bg-surface-container-lowest border border-outline-variant/20 rounded-xl px-4 py-2.5">
            <span class="material-symbols-outlined text-primary text-sm">update</span>
            Perubahan langsung aktif saat disimpan
        </div>
    </div>

    <form id="settingsForm" enctype="multipart/form-data" onsubmit="saveSettings(event)">
        <input type="hidden" id="work_min_start_time_enabled_hidden" name="work_min_start_time_enabled" value="<?= htmlspecialchars($cfg['work_min_start_time_enabled']) ?>" />
        <input type="hidden" id="work_min_end_time_enabled_hidden" name="work_min_end_time_enabled" value="<?= htmlspecialchars($cfg['work_min_end_time_enabled']) ?>" />
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- ── Col 1–2: Main Settings ── -->
            <div class="xl:col-span-2 space-y-5">

                <!-- === PROFIL APLIKASI & PERUSAHAAN === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-primary text-xl bg-primary/8 p-2 rounded-xl" style="background:rgba(0,6,102,.07)">business</span>
                        <h2 class="settings-section-title">Profil Aplikasi & Perusahaan</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="settings-label" for="app_name">Nama Aplikasi</label>
                            <input type="text" id="app_name" name="app_name"
                                value="<?= htmlspecialchars($cfg['app_name']) ?>"
                                class="settings-input" placeholder="siCare" required />
                            <p class="settings-helper">Nama aplikasi yang tampil di sidebar, tab browser, dan halaman login.</p>
                        </div>
                        <div>
                            <label class="settings-label" for="app_company_name">Nama Perusahaan (PT)</label>
                            <input type="text" id="app_company_name" name="app_company_name"
                                value="<?= htmlspecialchars($cfg['app_company_name']) ?>"
                                class="settings-input" placeholder="PT SI CARE ENTERPRISE" required />
                            <p class="settings-helper">Nama resmi perusahaan pada kop slip gaji dan tanda tangan digital.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="settings-label" for="app_logo_type">Tipe Logo</label>
                            <select id="app_logo_type" name="app_logo_type" class="settings-input" onchange="toggleLogoFields()">
                                <option value="icon" <?= $cfg['app_logo_type'] === 'icon' ? 'selected' : '' ?>>Google Icon</option>
                                <option value="image" <?= $cfg['app_logo_type'] === 'image' ? 'selected' : '' ?>>Unggah Gambar Logo</option>
                            </select>
                            <p class="settings-helper">Pilih jenis logo: menggunakan Google Font Icon atau mengunggah berkas gambar logo custom.</p>
                        </div>
                        <div id="logo_icon_wrapper" class="md:col-span-2 <?= $cfg['app_logo_type'] === 'image' ? 'hidden' : '' ?>">
                            <label class="settings-label" for="app_logo_icon">Logo Aplikasi (Material Icon)</label>
                            <input type="text" id="app_logo_icon" name="app_logo_icon"
                                value="<?= htmlspecialchars($cfg['app_logo_icon']) ?>"
                                class="settings-input" placeholder="local_police" />
                            <p class="settings-helper">Nama icon Google Material Symbols (misal: local_police, shield, business).</p>
                        </div>
                        <div id="logo_image_wrapper" class="md:col-span-2 <?= $cfg['app_logo_type'] === 'icon' ? 'hidden' : '' ?> flex flex-col md:flex-row gap-4 items-start">
                            <div class="flex-grow w-full">
                                <label class="settings-label" for="app_logo_file">Unggah Gambar Logo</label>
                                <input type="file" id="app_logo_file" name="app_logo_file" accept="image/png, image/jpeg, image/gif, image/svg+xml" class="settings-input file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" onchange="previewLogoFile(event)" />
                                <p class="settings-helper">Format berkas: PNG, JPG, GIF, atau SVG. Ukuran maksimal 2MB.</p>
                            </div>
                            <div class="flex flex-col items-center justify-center shrink-0 w-28 h-20 bg-neutral-50 rounded-xl border border-dashed border-neutral-300 relative overflow-hidden group">
                                <p class="text-[9px] font-bold text-neutral-400 absolute top-1">Pratinjau</p>
                                <div id="logo_preview_container" class="mt-4 flex items-center justify-center w-full h-12 px-2">
                                    <?php if (!empty($cfg['app_logo_image'])): ?>
                                        <img id="logo_preview_img" src="<?= htmlspecialchars($cfg['app_logo_image']) ?>" class="max-h-full max-w-full object-contain" />
                                    <?php else: ?>
                                        <span id="logo_preview_placeholder" class="text-neutral-400 text-xs italic">Kosong</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-5 border-t border-dashed border-neutral-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="settings-label" for="app_idle_timeout_sec">Batas Waktu Idle Aplikasi (Detik)</label>
                                <input type="number" min="0" id="app_idle_timeout_sec" name="app_idle_timeout_sec"
                                    value="<?= htmlspecialchars($cfg['app_idle_timeout_sec']) ?>"
                                    class="settings-input" placeholder="0" required />
                                <p class="settings-helper">Durasi tidak ada aktivitas dalam detik. Isi <code>0</code> untuk menonaktifkan deteksi idle (nilai bawaan).</p>
                            </div>
                            <div>
                                <label class="settings-label" for="app_idle_countdown_sec">Hitungan Mundur Logout (Detik)</label>
                                <input type="number" min="0" id="app_idle_countdown_sec" name="app_idle_countdown_sec"
                                    value="<?= htmlspecialchars($cfg['app_idle_countdown_sec'] ?? '0') ?>"
                                    class="settings-input" placeholder="0" required />
                                <p class="settings-helper">Durasi konfirmasi sebelum otomatis logout. Isi <code>0</code> jika tidak ada countdown (logout otomatis ditiadakan).</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-5 border-t border-dashed border-neutral-200">
                        <label class="settings-label" for="google_maps_api_key">Google Maps API Key (Opsional)</label>
                        <input type="text" id="google_maps_api_key" name="google_maps_api_key"
                            value="<?= htmlspecialchars($cfg['google_maps_api_key'] ?? '') ?>"
                            class="settings-input" placeholder="AIzaSy..." />
                        <p class="settings-helper">Masukkan kunci API Google Maps resmi Anda untuk mengaktifkan peta Google Maps & Google Places pencarian lokasi publik interaktif di modal pemilihan koordinat kantor. Jika dibiarkan kosong, sistem akan otomatis menggunakan peta gratis Leaflet (OpenStreetMap).</p>
                    </div>

                </div>

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

                    <!-- WIFI Prefix (IPv4 & IPv6 Tags) -->
                    <div class="mt-5 space-y-4">
                        <!-- IPv4 WiFi Prefixes -->
                        <div>
                            <label class="settings-label">Prefix IP WIFI Kantor (IPv4)</label>
                            
                            <div class="w-full bg-[#f8f9fa] border border-[#dde0f0] rounded-xl p-3 flex flex-wrap gap-2 items-center focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/10 transition-all duration-200" id="ipv4-tags-container">
                                <!-- Tags will be dynamically rendered here -->
                                <input type="text" id="ipv4-tag-input" class="flex-grow min-w-[120px] bg-transparent border-none outline-none focus:ring-0 text-sm font-semibold text-neutral-800 p-0 font-mono" placeholder="Contoh: 192.168.10. (Tekan Enter)" />
                            </div>
                            
                            <!-- Hidden input to hold the actual comma-separated values sent to server -->
                            <input type="hidden" id="office_wifi_prefix" name="office_wifi_prefix" value="<?= htmlspecialchars($cfg['office_wifi_prefix']) ?>" />
                            <p class="settings-helper mt-1.5">Prefix IPv4. Contoh: 192.168.1. atau 10.0.0. (Karyawan otomatis WFO jika IP-nya berawalan ini).</p>
                        </div>

                        <!-- IPv6 WiFi Prefixes -->
                        <div>
                            <label class="settings-label">Prefix IP WIFI Kantor (IPv6)</label>
                            
                            <div class="w-full bg-[#f8f9fa] border border-[#dde0f0] rounded-xl p-3 flex flex-wrap gap-2 items-center focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/10 transition-all duration-200" id="ipv6-tags-container">
                                <!-- Tags will be dynamically rendered here -->
                                <input type="text" id="ipv6-tag-input" class="flex-grow min-w-[120px] bg-transparent border-none outline-none focus:ring-0 text-sm font-semibold text-neutral-800 p-0 font-mono" placeholder="Contoh: fe80:: (Tekan Enter)" />
                            </div>
                            
                            <!-- Hidden input to hold the actual comma-separated values sent to server -->
                            <input type="hidden" id="office_wifi_ipv6_prefix" name="office_wifi_ipv6_prefix" value="<?= htmlspecialchars($cfg['office_wifi_ipv6_prefix'] ?? '') ?>" />
                            <p class="settings-helper mt-1.5">Prefix IPv6. Contoh: 2001:db8: atau fe80:12:: (Tekan Enter untuk menambah tag).</p>
                        </div>
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
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="settings-label !mb-0" for="work_min_start_time">Minimal Jam Masuk</label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="work_min_start_time_enabled" value="true" <?= $cfg['work_min_start_time_enabled'] === 'true' ? 'checked' : '' ?> class="sr-only peer" onchange="toggleMinStart(this.checked)">
                                    <div class="w-9 h-5 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-600"></div>
                                </label>
                            </div>
                            <input type="time" id="work_min_start_time" name="work_min_start_time"
                                value="<?= htmlspecialchars($cfg['work_min_start_time']) ?>"
                                class="settings-input font-mono transition-opacity duration-200" />
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

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="settings-label" for="work_end_time">Jam Pulang Standar</label>
                            <input type="time" id="work_end_time" name="work_end_time"
                                value="<?= htmlspecialchars($cfg['work_end_time']) ?>"
                                class="settings-input font-mono" />
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="settings-label !mb-0" for="work_min_end_time">Minimal Jam Pulang</label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="work_min_end_time_enabled" value="true" <?= $cfg['work_min_end_time_enabled'] === 'true' ? 'checked' : '' ?> class="sr-only peer" onchange="toggleMinEnd(this.checked)">
                                    <div class="w-9 h-5 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-600"></div>
                                </label>
                            </div>
                            <input type="time" id="work_min_end_time" name="work_min_end_time"
                                value="<?= htmlspecialchars($cfg['work_min_end_time']) ?>"
                                class="settings-input font-mono transition-opacity duration-200" />
                        </div>
                        <div>
                            <label class="settings-label" for="checkout_grace_period_min">Toleransi Pulang Lambat</label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="checkout_grace_period_min" name="checkout_grace_period_min"
                                    value="<?= (int)$cfg['checkout_grace_period_min'] ?>"
                                    min="0" max="1440"
                                    class="settings-input" style="padding:.6rem" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">menit</span>
                            </div>
                        </div>
                    </div>
                    <!-- Live preview khusus checkout status -->
                    <div id="checkoutPreview" class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-100 text-xs font-semibold text-amber-800">
                    </div>
                </div>

                <!-- === PENGATURAN KEBIJAKAN PENGGAJIAN === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-emerald-700 text-xl p-2 rounded-xl" style="background:rgba(0,102,50,.07)">payments</span>
                        <h2 class="settings-section-title" style="color:#004d26">Kebijakan & Komponen Penggajian (Payroll)</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="settings-label" for="payroll_tunj_jabatan_pct">Persentase Tunjangan Jabatan</label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="payroll_tunj_jabatan_pct" name="payroll_tunj_jabatan_pct"
                                    value="<?= htmlspecialchars($cfg['payroll_tunj_jabatan_pct']) ?>"
                                    min="0" max="100" step="0.1"
                                    class="settings-input" style="padding:.6rem" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">%</span>
                            </div>
                            <p class="settings-helper mt-1">Persentase tunjangan jabatan dari Gaji Pokok karyawan.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="payroll_tunj_jabatan_cap">Batas Maksimal Tunjangan Jabatan</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="payroll_tunj_jabatan_cap" name="payroll_tunj_jabatan_cap"
                                    value="<?= number_format((float)$cfg['payroll_tunj_jabatan_cap'], 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Batas nominal maksimal bulanan tunjangan jabatan.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="payroll_tunj_transport">Tunjangan Transport & Makan Flat</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="payroll_tunj_transport" name="payroll_tunj_transport"
                                    value="<?= number_format((float)$cfg['payroll_tunj_transport'], 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Nilai tetap tunjangan transport & makan bulanan.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="payroll_tunj_komunikasi">Tunjangan Komunikasi Flat</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="payroll_tunj_komunikasi" name="payroll_tunj_komunikasi"
                                    value="<?= number_format((float)$cfg['payroll_tunj_komunikasi'], 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Nilai tetap tunjangan komunikasi/pulsa bulanan.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="payroll_late_deduction">Denda Keterlambatan Per Hari</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="payroll_late_deduction" name="payroll_late_deduction"
                                    value="<?= number_format((float)$cfg['payroll_late_deduction'], 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Denda potongan per hari jika karyawan terlambat masuk kerja.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="payroll_bpjs_tk_pct">Potongan BPJS Ketenagakerjaan (%)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" min="0" max="100" id="payroll_bpjs_tk_pct" name="payroll_bpjs_tk_pct"
                                    value="<?= htmlspecialchars($cfg['payroll_bpjs_tk_pct']) ?>"
                                    class="settings-input" style="padding:.6rem" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">%</span>
                            </div>
                            <p class="settings-helper mt-1">Persentase potongan BPJS TK Karyawan (default: 2%, set 0 jika tidak ada).</p>
                        </div>

                        <div>
                            <label class="settings-label" for="payroll_bpjs_kes_pct">Potongan BPJS Kesehatan (%)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" min="0" max="100" id="payroll_bpjs_kes_pct" name="payroll_bpjs_kes_pct"
                                    value="<?= htmlspecialchars($cfg['payroll_bpjs_kes_pct']) ?>"
                                    class="settings-input" style="padding:.6rem" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">%</span>
                            </div>
                            <p class="settings-helper mt-1">Persentase potongan BPJS Kesehatan Karyawan (default: 1%, set 0 jika tidak ada).</p>
                        </div>

                        <div>
                            <label class="settings-label" for="payroll_pph21_pct">Pajak Penghasilan PPh 21 (%)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" min="0" max="100" id="payroll_pph21_pct" name="payroll_pph21_pct"
                                    value="<?= htmlspecialchars($cfg['payroll_pph21_pct']) ?>"
                                    class="settings-input" style="padding:.6rem" />
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">%</span>
                            </div>
                            <p class="settings-helper mt-1">Persentase potongan Pajak PPh 21 Karyawan (default: 2.5%, set 0 jika tidak ada).</p>
                        </div>
                    </div>
                </div>

                <!-- === PENGATURAN KLAIM REIMBURSEMENT (DEFAULT) === -->
                <div class="settings-card p-6">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="material-symbols-outlined text-indigo-700 text-xl p-2 rounded-xl" style="background:rgba(79,70,229,.07)">receipt_long</span>
                        <h2 class="settings-section-title" style="color:#1e1b4b">Default Plafon & Kebijakan Reimbursement</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="settings-label" for="reimbursement_limit_medis">Plafon Medis (Default)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="reimbursement_limit_medis" name="reimbursement_limit_medis"
                                    value="<?= number_format((float)($cfg['reimbursement_limit_medis'] ?? 5000000), 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Batas nominal maksimal bulanan klaim kategori Kesehatan & Medis.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="reimbursement_limit_transport">Plafon Transportasi (Default)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="reimbursement_limit_transport" name="reimbursement_limit_transport"
                                    value="<?= number_format((float)($cfg['reimbursement_limit_transport'] ?? 3000000), 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Batas nominal maksimal bulanan klaim kategori Transportasi & Tol.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="reimbursement_limit_operasional">Plafon Operasional (Default)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="reimbursement_limit_operasional" name="reimbursement_limit_operasional"
                                    value="<?= number_format((float)($cfg['reimbursement_limit_operasional'] ?? 4000000), 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Batas nominal maksimal bulanan klaim kategori Alat Kerja & Operasional.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="reimbursement_limit_makan">Plafon Makan & Bisnis (Default)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="reimbursement_limit_makan" name="reimbursement_limit_makan"
                                    value="<?= number_format((float)($cfg['reimbursement_limit_makan'] ?? 2500000), 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Batas nominal maksimal bulanan klaim kategori Makan & Bisnis.</p>
                        </div>

                        <div>
                            <label class="settings-label" for="reimbursement_limit_department_default">Plafon Departemen (Default)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                                <input type="text" id="reimbursement_limit_department_default" name="reimbursement_limit_department_default"
                                    value="<?= number_format((float)($cfg['reimbursement_limit_department_default'] ?? 15000000), 0, ',', '.') ?>"
                                    class="settings-input font-mono" style="padding:.6rem"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');" />
                            </div>
                            <p class="settings-helper mt-1">Default plafon bulanan total per departemen jika tidak di-override pada pengaturan departemen.</p>
                        </div>
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
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <label class="settings-label !mb-0">Tanggal Merah / Libur Nasional</label>
                            <button type="button" onclick="showGoogleHolidaysImporter()" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1 bg-red-50 hover:bg-red-100/70 px-3 py-1.5 rounded-xl transition-all border border-red-100">
                                <span class="material-symbols-outlined text-sm font-bold">sync</span> Ambil dari Google Calendar
                            </button>
                        </div>
                        
                        <!-- Event Calendar Container -->
                        <div class="calendar-container mb-4">
                            <div class="calendar-header">
                                <button type="button" class="calendar-nav-btn" onclick="prevCalendarMonth()">
                                    <span class="material-symbols-outlined text-base">chevron_left</span>
                                </button>
                                <span id="calendarMonthYear" class="calendar-month-year"></span>
                                <button type="button" class="calendar-nav-btn" onclick="nextCalendarMonth()">
                                    <span class="material-symbols-outlined text-base">chevron_right</span>
                                </button>
                            </div>
                            <div class="calendar-grid-weekdays">
                                <div class="calendar-weekday">Sen</div>
                                <div class="calendar-weekday">Sel</div>
                                <div class="calendar-weekday">Rab</div>
                                <div class="calendar-weekday">Kam</div>
                                <div class="calendar-weekday">Jum</div>
                                <div class="calendar-weekday">Sab</div>
                                <div class="calendar-weekday">Min</div>
                            </div>
                            <div id="calendarDaysGrid" class="calendar-grid-days"></div>
                        </div>

                        <!-- Daftar Libur Bulan Ini -->
                        <div class="bg-surface p-3.5 rounded-xl border border-outline-variant/10 mb-4" style="background:#f8f9fa">
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2" id="holidayMonthListTitle">Daftar Libur Bulan Ini</p>
                            <div class="space-y-2 max-h-36 overflow-y-auto pr-1" id="calendarMonthHolidaysList">
                                <!-- Rendered dynamically -->
                            </div>
                        </div>
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
const getRoutePrefix = () => {
    const p = window.location.pathname;
    if (p.startsWith('/hrops')) return '/hrops';
    if (p.startsWith('/superadmin/system-settings')) return '/superadmin/system-settings';
    if (p.startsWith('/superadmin')) return '/superadmin';
    return '/admin';
};
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

// ── Track current API key to detect changes ──
window.currentGoogleMapsApiKey = <?= json_encode($cfg['google_maps_api_key'] ?? '') ?>;

// ── Open Map for coordinate picking (Google Maps with Leaflet fallback) ──
function openMapPicker() {
    const lat = document.getElementById('office_lat').value.replace(',', '.') || '-6.2297';
    const lng = document.getElementById('office_lng').value.replace(',', '.') || '106.8164';
    const hasGoogleMaps = typeof google !== 'undefined' && typeof google.maps !== 'undefined' && typeof google.maps.places !== 'undefined';
    
    let pickerMap = null;
    let pickerMarker = null;

    Swal.fire({
        title: '📍 Pilih Lokasi Kantor',
        width: '650px',
        html: `
            <div class="text-left space-y-4">
                <p class="text-xs text-gray-500">Cari alamat atau geser pin langsung pada peta untuk menentukan titik koordinat kantor.</p>
                
                <!-- Kotak Pencarian -->
                <div class="flex gap-2">
                    <input id="swalMapSearch" type="text" placeholder="Masukkan nama tempat atau alamat..." class="flex-grow p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500" />
                    <button id="swalMapSearchBtn" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold flex items-center gap-1 hover:bg-blue-700 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-sm font-bold">search</span> Cari
                    </button>
                </div>

                <!-- Container Peta -->
                <div id="swalMapContainer" class="w-full rounded-2xl border border-gray-200 overflow-hidden bg-gray-50 shadow-inner relative" style="height: 320px; min-height: 320px; z-index: 1;"></div>

                <!-- Map Type Selector -->
                <div class="flex items-center gap-1.5">
                    <button id="pickerMapType_roadmap" onclick="switchPickerMapType('roadmap')" type="button"
                        style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid #c6c5d4;background:#fff;color:#454652;transition:all .2s;">
                        <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 0,'wght' 600;">map</span>
                        Jalan
                    </button>
                    <button id="pickerMapType_satellite" onclick="switchPickerMapType('satellite')" type="button"
                        style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid #c6c5d4;background:#fff;color:#454652;transition:all .2s;">
                        <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 0,'wght' 600;">satellite_alt</span>
                        Satelit
                    </button>
                    <button id="pickerMapType_terrain" onclick="switchPickerMapType('terrain')" type="button"
                        style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid #c6c5d4;background:#fff;color:#454652;transition:all .2s;">
                        <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 0,'wght' 600;">terrain</span>
                        Terrain
                    </button>
                </div>

                <!-- Tampilan Koordinat -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Latitude</label>
                        <input id="swalLat" type="number" step="any" value="${lat}" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Longitude</label>
                        <input id="swalLng" type="number" step="any" value="${lng}" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:border-blue-500" />
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#000666',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Terapkan Lokasi',
        cancelButtonText: 'Batal',
        didOpen: () => {
            const swalLatInput = document.getElementById('swalLat');
            const swalLngInput = document.getElementById('swalLng');
            const searchInput = document.getElementById('swalMapSearch');
            const searchBtn = document.getElementById('swalMapSearchBtn');

            // Wait 300ms for Swal transition
            setTimeout(() => {
                const initialLat = parseFloat(lat);
                const initialLng = parseFloat(lng);

                if (hasGoogleMaps) {
                    // Initialize Google Map
                    const gmPickerType = window.currentPickerMapType === 'satellite' ? 'hybrid'
                        : window.currentPickerMapType === 'terrain' ? 'terrain' : 'roadmap';
                    const mapOptions = {
                        center: { lat: initialLat, lng: initialLng },
                        zoom: 16,
                        mapTypeId: gmPickerType,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false
                    };
                    pickerMap = new google.maps.Map(document.getElementById('swalMapContainer'), mapOptions);
                    window._pickerGoogleMapRef = pickerMap;

                    // Sync active button state
                    switchPickerMapType(window.currentPickerMapType || 'roadmap');

                    // Add draggable marker
                    pickerMarker = new google.maps.Marker({
                        position: { lat: initialLat, lng: initialLng },
                        map: pickerMap,
                        draggable: true
                    });

                    // Drag end handler
                    pickerMarker.addListener('dragend', () => {
                        const pos = pickerMarker.getPosition();
                        swalLatInput.value = pos.lat().toFixed(6);
                        swalLngInput.value = pos.lng().toFixed(6);
                    });

                    // Click on map handler
                    pickerMap.addListener('click', (e) => {
                        const coords = e.latLng;
                        pickerMarker.setPosition(coords);
                        swalLatInput.value = coords.lat().toFixed(6);
                        swalLngInput.value = coords.lng().toFixed(6);
                    });

                    // Manual input handler
                    const updateFromManualInputGoogle = () => {
                        const mLat = parseFloat(swalLatInput.value);
                        const mLng = parseFloat(swalLngInput.value);
                        if (!isNaN(mLat) && !isNaN(mLng)) {
                            const newPos = { lat: mLat, lng: mLng };
                            pickerMarker.setPosition(newPos);
                            pickerMap.panTo(newPos);
                        }
                    };
                    swalLatInput.addEventListener('input', updateFromManualInputGoogle);
                    swalLngInput.addEventListener('input', updateFromManualInputGoogle);

                    // Google Places Autocomplete search input integration
                    const autocomplete = new google.maps.places.Autocomplete(searchInput);
                    autocomplete.bindTo('bounds', pickerMap);

                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        if (!place.geometry || !place.geometry.location) {
                            // If place not found, fallback to search query geocoder
                            const geocoder = new google.maps.Geocoder();
                            geocoder.geocode({ address: searchInput.value }, (results, status) => {
                                if (status === 'OK' && results[0]) {
                                    const loc = results[0].geometry.location;
                                    pickerMap.setCenter(loc);
                                    pickerMap.setZoom(16);
                                    pickerMarker.setPosition(loc);
                                    swalLatInput.value = loc.lat().toFixed(6);
                                    swalLngInput.value = loc.lng().toFixed(6);
                                } else {
                                    Swal.showValidationMessage('Alamat tidak ditemukan. Silakan periksa kembali.');
                                    setTimeout(() => Swal.resetValidationMessage(), 3000);
                                }
                            });
                            return;
                        }
                        const loc = place.geometry.location;
                        pickerMap.setCenter(loc);
                        pickerMap.setZoom(16);
                        pickerMarker.setPosition(loc);
                        swalLatInput.value = loc.lat().toFixed(6);
                        swalLngInput.value = loc.lng().toFixed(6);
                    });

                    // For search button click, geocode the address
                    const performGoogleSearch = () => {
                        const query = searchInput.value.trim();
                        if (!query) return;

                        searchBtn.disabled = true;
                        searchBtn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span> Mencari...';

                        const geocoder = new google.maps.Geocoder();
                        geocoder.geocode({ address: query }, (results, status) => {
                            if (status === 'OK' && results[0]) {
                                const loc = results[0].geometry.location;
                                pickerMap.setCenter(loc);
                                pickerMap.setZoom(16);
                                pickerMarker.setPosition(loc);
                                swalLatInput.value = loc.lat().toFixed(6);
                                swalLngInput.value = loc.lng().toFixed(6);
                            } else {
                                Swal.showValidationMessage('Alamat tidak ditemukan. Silakan periksa kembali.');
                                setTimeout(() => Swal.resetValidationMessage(), 3000);
                            }
                            searchBtn.disabled = false;
                            searchBtn.innerHTML = '<span class="material-symbols-outlined text-sm font-bold">search</span> Cari';
                        });
                    };
                    searchBtn.addEventListener('click', performGoogleSearch);
                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            performGoogleSearch();
                        }
                    });
                } else {
                    // Initialize Leaflet Map
                    pickerMap = L.map('swalMapContainer', { zoomControl: true }).setView([initialLat, initialLng], 16);
                    window._pickerLeafletMapRef = pickerMap;
                    window._pickerLeafletTileRef = null;

                    // Tile layer definitions for switcher
                    const pickerTileLayers = {
                        roadmap: L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                            maxZoom: 20,
                            subdomains: ['mt0','mt1','mt2','mt3'],
                            attribution: '&copy; <a href="https://maps.google.com" target="_blank">Google Maps</a>'
                        }),
                        satellite: L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                            maxZoom: 20,
                            subdomains: ['mt0','mt1','mt2','mt3'],
                            attribution: '&copy; <a href="https://maps.google.com" target="_blank">Google Maps</a>'
                        }),
                        terrain: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
                        })
                    };
                    const initPickerType = window.currentPickerMapType || 'roadmap';
                    window._pickerLeafletTileRef = pickerTileLayers[initPickerType] || pickerTileLayers.roadmap;
                    window._pickerLeafletTileRef.addTo(pickerMap);
                    window._pickerTileLayerDefs = pickerTileLayers;

                    // Sync active button state
                    switchPickerMapType(window.currentPickerMapType || 'roadmap');

                    // Draggable marker with custom divIcon
                    pickerMarker = L.marker([initialLat, initialLng], {
                        draggable: true,
                        icon: L.divIcon({
                            className: '',
                            html: `
                                <div class="flex items-center justify-center bg-red-600 text-white rounded-full border-2 border-white shadow-[0_2px_8px_rgba(0,0,0,0.3)]" style="width: 36px; height: 36px;">
                                    <span class="material-symbols-outlined" style="font-size: 20px; font-weight: 600; font-variation-settings: 'FILL' 1;">location_on</span>
                                </div>
                            `,
                            iconSize: [36, 36],
                            iconAnchor: [18, 18]
                        })
                    }).addTo(pickerMap);

                    // Drag end handler
                    pickerMarker.on('dragend', function() {
                        const pos = pickerMarker.getLatLng();
                        swalLatInput.value = pos.lat.toFixed(6);
                        swalLngInput.value = pos.lng.toFixed(6);
                    });

                    // Click on map handler
                    pickerMap.on('click', function(e) {
                        const coords = e.latlng;
                        pickerMarker.setLatLng(coords);
                        swalLatInput.value = coords.lat.toFixed(6);
                        swalLngInput.value = coords.lng.toFixed(6);
                    });

                    // Search function using OpenStreetMap Nominatim API
                    const performSearch = () => {
                        const query = searchInput.value.trim();
                        if (!query) return;

                        searchBtn.disabled = true;
                        searchBtn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span> Mencari...';

                        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    const first = data[0];
                                    const newLat = parseFloat(first.lat);
                                    const newLng = parseFloat(first.lon);

                                    pickerMap.setView([newLat, newLng], 16);
                                    pickerMarker.setLatLng([newLat, newLng]);

                                    swalLatInput.value = newLat.toFixed(6);
                                    swalLngInput.value = newLng.toFixed(6);
                                } else {
                                    Swal.showValidationMessage('Alamat tidak ditemukan. Silakan periksa kembali.');
                                    setTimeout(() => Swal.resetValidationMessage(), 3000);
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                Swal.showValidationMessage('Gagal mencari alamat. Silakan coba lagi.');
                                setTimeout(() => Swal.resetValidationMessage(), 3000);
                            })
                            .finally(() => {
                                searchBtn.disabled = false;
                                searchBtn.innerHTML = '<span class="material-symbols-outlined text-sm font-bold">search</span> Cari';
                            });
                    };

                    searchBtn.addEventListener('click', performSearch);
                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            performSearch();
                        }
                    });

                    // Manual input handler
                    const updateFromManualInput = () => {
                        const mLat = parseFloat(swalLatInput.value);
                        const mLng = parseFloat(swalLngInput.value);
                        if (!isNaN(mLat) && !isNaN(mLng)) {
                            pickerMarker.setLatLng([mLat, mLng]);
                            pickerMap.panTo([mLat, mLng]);
                        }
                    };
                    swalLatInput.addEventListener('input', updateFromManualInput);
                    swalLngInput.addEventListener('input', updateFromManualInput);

                    // Fix Leaflet display issues inside modal container
                    pickerMap.invalidateSize();
                }
            }, 300);
        },
        willClose: () => {
            if (pickerMap && !hasGoogleMaps && typeof pickerMap.remove === 'function') {
                pickerMap.remove();
            }
        },
        preConfirm: () => {
            const latVal = document.getElementById('swalLat').value;
            const lngVal = document.getElementById('swalLng').value;
            if (!latVal || !lngVal) {
                Swal.showValidationMessage('Latitude dan Longitude wajib diisi.');
                return false;
            }
            return { lat: latVal, lng: lngVal };
        }
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('office_lat').value = r.value.lat;
            document.getElementById('office_lng').value = r.value.lng;
            updateMapPreview();
        }
    });
}

// ── Switch map type for coordinate picker ────────────────────
window.currentPickerMapType = window.currentPickerMapType || 'roadmap';
function switchPickerMapType(type) {
    window.currentPickerMapType = type;

    // Update button styles
    ['roadmap','satellite','terrain'].forEach(t => {
        const btn = document.getElementById('pickerMapType_' + t);
        if (!btn) return;
        if (t === type) {
            btn.style.background = '#000666';
            btn.style.color = '#ffffff';
            btn.style.borderColor = '#000666';
            btn.style.boxShadow = '0 2px 8px rgba(0,6,102,0.25)';
        } else {
            btn.style.background = '#ffffff';
            btn.style.color = '#454652';
            btn.style.borderColor = '#c6c5d4';
            btn.style.boxShadow = '';
        }
    });

    // Apply to Google Maps picker
    if (window._pickerGoogleMapRef) {
        const typeMap = { roadmap: 'roadmap', satellite: 'hybrid', terrain: 'terrain' };
        window._pickerGoogleMapRef.setMapTypeId(typeMap[type] || 'roadmap');
    }

    // Apply to Leaflet picker
    if (window._pickerLeafletMapRef && window._pickerTileLayerDefs) {
        if (window._pickerLeafletTileRef) {
            window._pickerLeafletMapRef.removeLayer(window._pickerLeafletTileRef);
        }
        window._pickerLeafletTileRef = window._pickerTileLayerDefs[type] || window._pickerTileLayerDefs.roadmap;
        window._pickerLeafletTileRef.addTo(window._pickerLeafletMapRef);
        window._pickerLeafletTileRef.bringToBack();
    }
}

// ── WFA toggle ───────────────────────────────────────────────
function updateWfaUi() {
    const chk = document.getElementById('wfa_allowed');
    const hidden = document.getElementById('wfa_allowed_hidden');
    const section = document.getElementById('wfaDaysSection');
    const disabledMsg = document.getElementById('wfaDisabledMsg');
    const cfgWfa = document.getElementById('cfg_wfa');
    
    if (chk.checked) {
        const activeDays = Array.from(document.querySelectorAll('.wfa-day-chip.active')).map(d => d.dataset.day);
        if (activeDays.length === 0) {
            const defaultDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
            defaultDays.forEach(day => {
                const chip = document.querySelector(`.wfa-day-chip[data-day="${day}"]`);
                if (chip) {
                    const weeklyChip = document.querySelector(`.weekly-holiday-chip[data-day="${day}"]`);
                    if (!weeklyChip || !weeklyChip.classList.contains('active')) {
                        chip.classList.add('active');
                    }
                }
            });
            const newActiveDays = Array.from(document.querySelectorAll('.wfa-day-chip.active')).map(d => d.dataset.day);
            document.getElementById('wfa_days').value = newActiveDays.join(',');
        }
    }

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

    if (activeDays.length === 0) {
        const chk = document.getElementById('wfa_allowed');
        if (chk && chk.checked) {
            chk.checked = false;
            updateWfaUi();
        }
    }
}

// ── Schedule preview toggles & helper ────────────────────────
function toggleMinStart(enabled) {
    const input = document.getElementById('work_min_start_time');
    if (enabled) {
        input.removeAttribute('disabled');
        input.classList.remove('opacity-50', 'bg-neutral-100');
    } else {
        input.setAttribute('disabled', 'disabled');
        input.classList.add('opacity-50', 'bg-neutral-100');
    }
    document.getElementById('work_min_start_time_enabled_hidden').value = enabled ? 'true' : 'false';
    updateSchedulePreview();
}

function toggleMinEnd(enabled) {
    const input = document.getElementById('work_min_end_time');
    if (enabled) {
        input.removeAttribute('disabled');
        input.classList.remove('opacity-50', 'bg-neutral-100');
    } else {
        input.setAttribute('disabled', 'disabled');
        input.classList.add('opacity-50', 'bg-neutral-100');
    }
    document.getElementById('work_min_end_time_enabled_hidden').value = enabled ? 'true' : 'false';
    updateSchedulePreview();
}

// ── Schedule preview ─────────────────────────────────────────
function updateSchedulePreview() {
    const start = document.getElementById('work_start_time').value;
    const minStart = document.getElementById('work_min_start_time').value;
    const minStartEnabled = document.getElementById('work_min_start_time_enabled').checked;
    const end   = document.getElementById('work_end_time').value;
    const minEnd = document.getElementById('work_min_end_time').value;
    const minEndEnabled = document.getElementById('work_min_end_time_enabled').checked;
    const grace = document.getElementById('grace_period_min').value;
    const coGrace = document.getElementById('checkout_grace_period_min').value;

    if (!start || !end || !minStart || !minEnd) return;
    const [sh, sm] = start.split(':').map(Number);
    const deadline = new Date(0, 0, 0, sh, sm + parseInt(grace || 0));
    const deadlineStr = deadline.toTimeString().substring(0,5);

    const [eh, em] = end.split(':').map(Number);
    const coDeadline = new Date(0, 0, 0, eh, em + parseInt(coGrace || 0));
    const coDeadlineStr = coDeadline.toTimeString().substring(0,5);

    const minStartText = minStartEnabled ? `Minimal Masuk ${minStart}` : 'Minimal Masuk: Dinonaktifkan';
    const minStartDesc = minStartEnabled 
        ? `Karyawan hanya diperbolehkan masuk mulai pukul <strong>${minStart}</strong>. ` 
        : 'Karyawan dapat melakukan presensi masuk kapan saja (tidak dibatasi minimal jam masuk). ';

    document.getElementById('schedulePreview').innerHTML = `
        <div class="flex items-start gap-2">
            <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">info</span>
            <div>
                <strong>Jam Masuk Standar ${start}</strong> &nbsp;·&nbsp; ${minStartText} &nbsp;·&nbsp; Toleransi Masuk ${grace} menit<br>
                ${minStartDesc}Masuk hingga <strong>${deadlineStr}</strong> masih dianggap <span class="text-green-700 font-semibold">Tepat Waktu</span>. Masuk setelah <strong>${deadlineStr}</strong> tercatat sebagai <span class="text-amber-700 font-semibold">Terlambat</span>.
            </div>
        </div>
    `;

    if (document.getElementById('checkoutPreview')) {
        const minEndText = minEndEnabled ? `Minimal Pulang ${minEnd}` : 'Minimal Pulang: Dinonaktifkan';
        const minEndDesc = minEndEnabled 
            ? `Karyawan hanya diperbolehkan melakukan presensi pulang mulai pukul <strong>${minEnd}</strong>. ` 
            : 'Karyawan dapat melakukan presensi pulang kapan saja (tidak dibatasi minimal jam pulang). ';

        document.getElementById('checkoutPreview').innerHTML = `
            <div class="flex items-start gap-2">
                <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">info</span>
                <div>
                    <strong>Jam Pulang Standar ${end}</strong> &nbsp;·&nbsp; ${minEndText} &nbsp;·&nbsp; Toleransi Pulang Lambat ${coGrace} menit<br>
                    ${minEndDesc}Karyawan pulang hingga <strong>${coDeadlineStr}</strong> masih dianggap <span class="text-emerald-700 font-semibold">Tepat Waktu</span>. Pulang setelah <strong>${coDeadlineStr}</strong> tercatat sebagai <span class="text-orange-700 font-semibold">Pulang Terlambat</span>. Jika melewati hari (masuk jam 00:00) dan belum melakukan presensi pulang, maka tercatat sebagai <span class="text-rose-700 font-semibold">Tidak Presensi Pulang</span>.
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
['work_start_time','work_min_start_time','work_min_end_time','work_end_time','grace_period_min','checkout_grace_period_min'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateSchedulePreview);
});

// Run toggle helper initializers
toggleMinStart(document.getElementById('work_min_start_time_enabled').checked);
toggleMinEnd(document.getElementById('work_min_end_time_enabled').checked);
updateSchedulePreview();

// --- Event Calendar Logic ---
const holidayData = <?= json_encode($holidays) ?>;
let calYear = new Date().getFullYear();
let calMonth = new Date().getMonth(); // 0-11

function initCalendar() {
    renderCalendar(calYear, calMonth);
}

function prevCalendarMonth() {
    calMonth--;
    if (calMonth < 0) {
        calMonth = 11;
        calYear--;
    }
    renderCalendar(calYear, calMonth);
}

function nextCalendarMonth() {
    calMonth++;
    if (calMonth > 11) {
        calMonth = 0;
        calYear++;
    }
    renderCalendar(calYear, calMonth);
}

function renderCalendar(year, month) {
    const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    
    // Set Header
    document.getElementById('calendarMonthYear').textContent = monthNames[month] + " " + year;
    
    // Get first day of the month
    const firstDayIndex = new Date(year, month, 1).getDay(); // 0 is Sunday
    let startDay = firstDayIndex === 0 ? 6 : firstDayIndex - 1;
    
    const totalDays = new Date(year, month + 1, 0).getDate();
    const prevTotalDays = new Date(year, month, 0).getDate();
    
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    
    let html = '';
    
    for (let i = startDay - 1; i >= 0; i--) {
        html += `<div class="calendar-day prev-month">${prevTotalDays - i}</div>`;
    }
    
    for (let day = 1; day <= totalDays; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const holiday = holidayData.find(h => h.holiday_date === dateStr);
        
        let classList = "calendar-day current-month";
        let attr = `data-date="${dateStr}"`;
        
        if (holiday) {
            classList += " is-holiday";
            attr += ` data-holiday-id="${holiday.id}" data-holiday-desc="${escapeHtml(holiday.description)}"`;
        }
        
        if (dateStr === todayStr) {
            classList += " is-today";
        }
        
        html += `<div class="${classList}" ${attr} onclick="handleDayClick(this)">
            <span class="day-number">${day}</span>
        </div>`;
    }
    
    const totalGrid = startDay + totalDays;
    const nextDays = totalGrid <= 35 ? 35 - totalGrid : 42 - totalGrid;
    for (let i = 1; i <= nextDays; i++) {
        html += `<div class="calendar-day next-month">${i}</div>`;
    }
    
    document.getElementById('calendarDaysGrid').innerHTML = html;
    
    renderMonthHolidaysList(year, month);
}

function renderMonthHolidaysList(year, month) {
    const listContainer = document.getElementById('calendarMonthHolidaysList');
    const monthStr = String(month + 1).padStart(2, '0');
    const prefix = `${year}-${monthStr}`;
    
    const monthHolidays = holidayData.filter(h => h.holiday_date.startsWith(prefix));
    
    if (monthHolidays.length === 0) {
        listContainer.innerHTML = `<p class="text-[11px] font-semibold text-gray-400 text-center py-3">Tidak ada libur nasional di bulan ini.</p>`;
        return;
    }
    
    let html = '';
    monthHolidays.forEach(h => {
        const d = new Date(h.holiday_date);
        const dayStr = String(d.getDate()).padStart(2, '0');
        const monthsShort = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
        const formattedDate = `${dayStr} ${monthsShort[d.getMonth()]} ${d.getFullYear()}`;
        
        html += `
            <div class="holiday-item-mini">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-extrabold text-gray-800 truncate" title="${escapeHtml(h.description)}">${escapeHtml(h.description)}</p>
                    <p class="text-[9px] font-mono font-medium text-gray-400 mt-0.5">${formattedDate}</p>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                    <button type="button"
                        data-holiday-id="${h.id}"
                        data-holiday-date="${h.holiday_date}"
                        data-holiday-desc="${escapeHtml(h.description)}"
                        onclick="editHoliday(this.dataset.holidayId, this.dataset.holidayDate, this.dataset.holidayDesc)"
                        class="text-blue-500 hover:text-blue-700 p-1 flex items-center justify-center hover:bg-blue-50 rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-sm font-bold">edit</span>
                    </button>
                    <button type="button" onclick="deleteHoliday('${h.id}')" class="text-red-500 hover:text-red-700 p-1 flex items-center justify-center hover:bg-red-50 rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-sm font-bold">delete</span>
                    </button>
                </div>
            </div>
        `;
    });
    
    listContainer.innerHTML = html;
}

function handleDayClick(element) {
    const dateStr = element.dataset.date;
    const isHoliday = element.classList.contains('is-holiday');
    
    if (isHoliday) {
        const holidayDesc = element.dataset.holidayDesc;
        const holidayId = element.dataset.holidayId;
        
        const parts = dateStr.split('-');
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const dateLabel = parseInt(parts[2], 10) + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
        
        Swal.fire({
            title: '🎉 Hari Libur Nasional',
            html: `
                <div class="text-center space-y-2 text-sm mt-2">
                    <p class="font-extrabold text-base text-gray-800">${escapeHtml(holidayDesc)}</p>
                    <p class="font-semibold text-xs text-gray-450 font-mono">${dateLabel}</p>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#000666',
            denyButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Tutup',
            denyButtonText: 'Hapus Libur',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isDenied) {
                deleteHoliday(holidayId);
            }
        });
    } else {
        const parts = dateStr.split('-');
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const dateLabel = parseInt(parts[2], 10) + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
        
        Swal.fire({
            title: '🗓️ Tambah Hari Libur',
            html: `
                <div class="text-left space-y-4 text-sm mt-2">
                    <div>
                        <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Tanggal Libur</label>
                        <input type="text" value="${dateLabel}" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none text-sm font-semibold text-gray-750 font-mono" readonly />
                        <input type="hidden" id="swalHolidayDate" value="${dateStr}" />
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
                if (!desc) { Swal.showValidationMessage('Keterangan libur wajib diisi.'); return false; }
                return { date, desc };
            }
        }).then(result => {
            if (!result.isConfirmed) return;
            submitNewHoliday(result.value.date, result.value.desc);
        });
    }
}

function submitNewHoliday(date, desc) {
    const fd = new FormData();
    fd.append('holiday_date', date);
    fd.append('description', desc);
    
    const endpoint = getRoutePrefix() + '/holidays/add';
    
    fetch(endpoint, {
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
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

initCalendar();

// Init logo fields
if (document.getElementById('app_logo_type')) {
    toggleLogoFields();
}

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

    // Sync hidden start/end min time enable fields before submit
    document.getElementById('work_min_start_time_enabled_hidden').value = document.getElementById('work_min_start_time_enabled').checked ? 'true' : 'false';
    document.getElementById('work_min_end_time_enabled_hidden').value = document.getElementById('work_min_end_time_enabled').checked ? 'true' : 'false';

    const fd = new FormData(document.getElementById('settingsForm'));
    // wfa_allowed checkbox doesn't have name anymore, only hidden field is captured

    fetch(getRoutePrefix() + '/settings/save', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<span class="material-symbols-outlined text-lg">save</span> Simpan Pengaturan';
        btn.disabled = false;
        if (data.success) {
            // Detect if Google Maps API Key changed – if so, full reload required
            const newApiKey = (data.settings && data.settings.google_maps_api_key != null) ? data.settings.google_maps_api_key : window.currentGoogleMapsApiKey;
            const apiKeyChanged = newApiKey !== window.currentGoogleMapsApiKey;

            // Show standard SweetAlert2 modal
            Swal.fire({
                title: 'Berhasil',
                text: apiKeyChanged
                    ? data.message + ' Halaman akan dimuat ulang untuk menerapkan perubahan Google Maps API Key...'
                    : data.message,
                icon: 'success',
                timer: apiKeyChanged ? 2000 : 1500,
                timerProgressBar: true,
                confirmButtonColor: '#000666'
            }).then(() => {
                if (apiKeyChanged) {
                    window.location.reload();
                    return;
                }
            });

            // Update parameters in real-time on the current page session without reloading
            if (data.settings) {
                if (window.updateIdleSettings) {
                    window.updateIdleSettings(data.settings.app_idle_timeout_sec, data.settings.app_idle_countdown_sec);
                }
                if (window.updateAppAppearance) {
                    window.updateAppAppearance(
                        data.settings.app_name, 
                        data.settings.app_logo_type, 
                        data.settings.app_logo_icon, 
                        data.settings.app_logo_image
                    );
                }
            }
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

    if (activeWfa.length === 0) {
        const chk = document.getElementById('wfa_allowed');
        if (chk && chk.checked) {
            chk.checked = false;
            updateWfaUi();
        }
    }
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
        
        fetch(getRoutePrefix() + '/holidays/add', {
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
        
        fetch(getRoutePrefix() + '/holidays/delete', {
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

// ── Edit Holiday ─────────────────────────────────────────────
function editHoliday(id, currentDate, currentDesc) {
    Swal.fire({
        title: '✏️ Edit Hari Libur',
        html: `
            <div class="text-left space-y-4 text-sm mt-2">
                <div>
                    <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Tanggal Libur</label>
                    <input type="date" id="swalEditHolidayDate" value="${currentDate}" class="w-full p-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm" />
                </div>
                <div>
                    <label class="block font-bold text-[10px] text-gray-500 mb-1.5 uppercase tracking-wider">Keterangan / Nama Libur</label>
                    <input type="text" id="swalEditHolidayDesc" value="${escapeHtml(currentDesc)}" placeholder="Contoh: Hari Raya Idul Fitri" class="w-full p-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#000666',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Simpan Perubahan',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const date = document.getElementById('swalEditHolidayDate').value;
            const desc = document.getElementById('swalEditHolidayDesc').value.trim();
            if (!date) { Swal.showValidationMessage('Tanggal wajib diisi.'); return false; }
            if (!desc) { Swal.showValidationMessage('Keterangan libur wajib diisi.'); return false; }
            return { date, desc };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('id', id);
        fd.append('holiday_date', result.value.date);
        fd.append('description', result.value.desc);
        fetch(getRoutePrefix() + '/holidays/update', { method: 'POST', body: fd })
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
            })
            .catch(() => Swal.fire('Error', 'Tidak dapat terhubung ke server.', 'error'));
    });
}

// ── Tarik Libur Nasional dari Google Calendar ────────────────
function showGoogleHolidaysImporter() {
    const currentYear = new Date().getFullYear();
    
    Swal.fire({
        title: 'Tarik Hari Libur Nasional & Cuti Bersama',
        text: 'Pilih tahun kalender libur yang ingin ditarik dari Google Calendar:',
        input: 'select',
        inputOptions: {
            [currentYear - 1]: currentYear - 1,
            [currentYear]: currentYear,
            [currentYear + 1]: currentYear + 1
        },
        inputValue: currentYear,
        showCancelButton: true,
        confirmButtonColor: '#000666',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Tampilkan Daftar',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: (year) => {
            return fetch(getRoutePrefix() + '/settings/fetch-google-holidays?year=' + year)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal menghubungi server');
                    return response.json();
                })
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (!result.isConfirmed || !result.value) return;
        
        const data = result.value;
        const holidays = data.holidays;
        if (holidays.length === 0) {
            Swal.fire({
                title: 'Tidak Ada Data',
                text: 'Tidak ada hari libur nasional ditemukan untuk tahun ' + data.year,
                icon: 'info',
                confirmButtonColor: '#000666'
            });
            return;
        }
        
        let checklistHtml = `
            <p class="text-xs text-gray-500 mb-4 text-left">Pilih tanggal merah atau cuti bersama yang ingin disetujui / diterapkan pada kalender libur perusahaan Anda:</p>
            <div class="max-h-[350px] overflow-y-auto border border-gray-150 rounded-xl p-3 text-left space-y-2.5 bg-gray-50/50">
        `;
        
        holidays.forEach((h, index) => {
            const dateObj = new Date(h.date);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dateLabel = dateObj.getDate() + ' ' + months[dateObj.getMonth()] + ' ' + dateObj.getFullYear();
            
            checklistHtml += `
                <label class="flex items-start gap-3 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors border border-transparent hover:border-gray-150">
                    <input type="checkbox" id="g_holiday_${index}" class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500" value="${h.date}" data-desc="${escapeHtml(h.summary)}" checked />
                    <div class="text-xs">
                        <div class="font-extrabold text-gray-800">${escapeHtml(h.summary)}</div>
                        <div class="font-semibold text-gray-450 font-mono mt-0.5">${dateLabel}</div>
                    </div>
                </label>
            `;
        });
        
        checklistHtml += `</div>`;
        
        Swal.fire({
            title: `🗓️ Kalender Libur ${data.year}`,
            html: checklistHtml,
            showCancelButton: true,
            confirmButtonColor: '#b91c1c',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Impor Terpilih',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const selected = [];
                holidays.forEach((h, index) => {
                    const chk = document.getElementById(`g_holiday_${index}`);
                    if (chk && chk.checked) {
                        selected.push({
                            date: chk.value,
                            desc: chk.dataset.desc
                        });
                    }
                });
                if (selected.length === 0) {
                    Swal.showValidationMessage('Pilih minimal satu hari libur untuk diimpor.');
                    return false;
                }
                return selected;
            }
        }).then((importResult) => {
            if (!importResult.isConfirmed || !importResult.value) return;
            
            const selectedHolidays = importResult.value;
            
            Swal.fire({
                title: 'Memproses...',
                text: 'Mengimpor hari libur terpilih...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(getRoutePrefix() + '/settings/import-google-holidays', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ holidays: selectedHolidays })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        title: 'Berhasil',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        timerProgressBar: true,
                        confirmButtonColor: '#000666'
                    }).then(() => {
                        if (window.loadPage) {
                            window.loadPage(window.location.pathname + window.location.search);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Gagal memproses impor.', 'error');
            });
        });
    });
}

function toggleLogoFields() {
    const type = document.getElementById('app_logo_type').value;
    const iconWrapper = document.getElementById('logo_icon_wrapper');
    const imageWrapper = document.getElementById('logo_image_wrapper');
    const iconInput = document.getElementById('app_logo_icon');

    if (type === 'image') {
        iconWrapper.classList.add('hidden');
        imageWrapper.classList.remove('hidden');
        iconInput.removeAttribute('required');
    } else {
        iconWrapper.classList.remove('hidden');
        imageWrapper.classList.add('hidden');
        iconInput.setAttribute('required', 'required');
    }
}

function previewLogoFile(event) {
    const file = event.target.files[0];
    if (!file) return;

    const container = document.getElementById('logo_preview_container');
    container.innerHTML = '';

    const img = document.createElement('img');
    img.className = 'max-h-full max-w-full object-contain';
    img.src = URL.createObjectURL(file);
    container.appendChild(img);
}

class TagInput {
    constructor(containerId, hiddenInputId, textInputId, placeholder) {
        this.container = document.getElementById(containerId);
        this.hiddenInput = document.getElementById(hiddenInputId);
        this.textInput = document.getElementById(textInputId);
        this.placeholder = placeholder;
        this.tags = [];

        if (this.hiddenInput && this.hiddenInput.value) {
            this.tags = this.hiddenInput.value.split(',')
                .map(t => t.trim())
                .filter(t => t.length > 0);
        }

        this.init();
    }

    init() {
        if (!this.container || !this.textInput || !this.hiddenInput) return;

        // Render initial tags
        this.render();

        // Listen for input keydown events (Enter, Comma, Spacebar, Backspace)
        this.textInput.addEventListener('keydown', (e) => {
            // Block space key if input is empty to avoid trailing/leading spaces
            if (e.key === ' ' && this.textInput.value.trim().length === 0) {
                e.preventDefault();
                return;
            }

            const val = this.textInput.value.trim();
            
            if ((e.key === 'Enter' || e.key === ',' || e.key === ' ') && val.length > 0) {
                e.preventDefault();
                // Prevent duplicate tags
                if (!this.tags.includes(val)) {
                    this.tags.push(val);
                    this.updateHiddenInput();
                    this.render();
                }
                this.textInput.value = '';
            } else if (e.key === 'Backspace' && val.length === 0 && this.tags.length > 0) {
                // Delete last tag on backspace when input is empty
                this.tags.pop();
                this.updateHiddenInput();
                this.render();
            }
        });

        // Focus text input when clicking anywhere on the container
        this.container.addEventListener('click', (e) => {
            if (e.target !== this.textInput) {
                this.textInput.focus();
            }
        });
    }

    render() {
        // Clear all tags except the text input
        const pills = this.container.querySelectorAll('.tag-pill');
        pills.forEach(p => p.remove());

        // Insert new tag pills before the text input
        this.tags.forEach((tag, idx) => {
            const pill = document.createElement('div');
            pill.className = 'tag-pill flex items-center gap-1.5 bg-primary/10 border border-primary/20 text-primary rounded-lg px-2.5 py-1 text-xs font-bold font-mono transition-all';
            
            const textSpan = document.createElement('span');
            textSpan.textContent = tag;
            pill.appendChild(textSpan);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'text-primary/70 hover:text-primary font-bold focus:outline-none select-none text-sm leading-none';
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = (e) => {
                e.stopPropagation();
                this.removeTag(idx);
            };
            pill.appendChild(removeBtn);

            this.container.insertBefore(pill, this.textInput);
        });

        // Update placeholder
        if (this.tags.length > 0) {
            this.textInput.placeholder = '';
        } else {
            this.textInput.placeholder = this.placeholder;
        }
    }

    removeTag(index) {
        this.tags.splice(index, 1);
        this.updateHiddenInput();
        this.render();
    }

    updateHiddenInput() {
        this.hiddenInput.value = this.tags.join(',');
    }
}

// Initialize Tag Inputs on load
new TagInput('ipv4-tags-container', 'office_wifi_prefix', 'ipv4-tag-input', 'Contoh: 192.168.10. (Tekan Enter)');
new TagInput('ipv6-tags-container', 'office_wifi_ipv6_prefix', 'ipv6-tag-input', 'Contoh: fe80:: (Tekan Enter)');

// Prevent negative numbers and scientific notation in number inputs
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('keydown', (e) => {
        if (['e', 'E', '-', '+'].includes(e.key)) {
            e.preventDefault();
        }
    });
    input.addEventListener('paste', (e) => {
        const pasteData = e.clipboardData.getData('text');
        if (/[eE\-+]/.test(pasteData)) {
            e.preventDefault();
        }
    });
    input.addEventListener('input', () => {
        if (input.value < 0) {
            input.value = 0;
        }
    });
});

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
window.editHoliday = editHoliday;
window.updateSchedulePreview = updateSchedulePreview;
window.saveSettings = saveSettings;
window.toggleLogoFields = toggleLogoFields;
window.previewLogoFile = previewLogoFile;
window.prevCalendarMonth = prevCalendarMonth;
window.nextCalendarMonth = nextCalendarMonth;
window.handleDayClick = handleDayClick;
window.showGoogleHolidaysImporter = showGoogleHolidaysImporter;
</script>
