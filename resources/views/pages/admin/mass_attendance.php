<?php
/**
 * Presensi Massal (Kiosk Terminal Mode)
 * Strictly restricted to Admin and Super Admin roles.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
$originalRole = $_SESSION['original_role'] ?? '';
$effectiveRole = (!empty($originalRole) && $originalRole === 'superadmin') ? 'superadmin' : $role;

if (!in_array($effectiveRole, ['admin', 'superadmin'])) {
    http_response_code(403);
    require __DIR__ . '/../errors/403.php';
    exit;
}
?>

<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Top Kiosk Header Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-inner">
                <span class="material-symbols-outlined text-3xl font-bold">contactless</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-headline text-2xl font-extrabold text-on-surface tracking-tight">Presensi Massal Terminal</h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        Kiosk Standby
                    </span>
                </div>
                <p class="text-sm font-semibold text-on-surface-variant mt-0.5">Mode Perangkat Tap/Scan Otomatis Karyawan (Khusus Administrator)</p>
            </div>
        </div>

        <!-- Live Clock & GPS Status -->
        <div class="flex items-center gap-6 relative z-10 border-t md:border-t-0 md:border-l border-outline-variant/20 pt-4 md:pt-0 md:pl-6">
            <div class="text-left md:text-right">
                <div id="kioskDigitalClock" class="font-headline text-3xl font-black text-primary tracking-tight font-mono">00:00:00 WIB</div>
                <div id="kioskDigitalDate" class="text-xs font-bold text-on-surface-variant">Memuat Tanggal...</div>
            </div>
            <div class="hidden sm:flex flex-col items-end gap-1">
                <span id="kioskGpsBadge" class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded bg-surface-container text-on-surface-variant border border-outline-variant/30">
                    <span class="material-symbols-outlined text-[14px]">location_on</span>
                    <span id="kioskGpsText">Mendeteksi GPS...</span>
                </span>
                <span class="text-[10px] font-semibold text-on-surface-variant">Lokasi: Kiosk Admin</span>
            </div>
        </div>
    </div>

    <!-- HARDWARE DEVICE CONNECTION STATUS BAR -->
    <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-xl">settings_input_component</span>
            </div>
            <div>
                <h4 class="text-xs font-extrabold text-on-surface">Status Deteksi Perangkat Hardware & Ekstensi</h4>
                <p id="hwStatusSubtitle" class="text-[11px] font-semibold text-on-surface-variant">Terintegrasi Scan-IT to Office, Pembaca USB RFID, dan Kamera Scanner</p>
            </div>
        </div>

        <!-- Dynamic Hardware Badges -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Camera Permission Request Button -->
            <button type="button" onclick="requestBrowserPermissions()" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-primary text-white hover:bg-primary/90 transition-all cursor-pointer shadow-2xs">
                <span class="material-symbols-outlined text-sm">photo_camera</span>
                Izinkan Kamera
            </button>

            <!-- Hardware Detector: Scan-IT to Office Chrome Extension -->
            <span id="badgeScanItToOffice" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-700 border border-amber-500/20" title="Terintegrasi Scan-IT to Office Chrome Extension (Sync Scan dari HP)">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span class="material-symbols-outlined text-sm">phonelink</span>
                Scan-IT to Office: Siap (Sync HP)
            </span>

            <!-- Hardware Detector: USB Scanner / Keyboard Trap -->
            <span id="badgeHwUsb" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20" title="USB RFID / Barcode Scanner Keyboard Trap Aktif">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="material-symbols-outlined text-sm">keyboard</span>
                USB Reader: Siap
            </span>

            <!-- Hardware Detector: WebHID / WebUSB Device Direct Pair -->
            <button type="button" onclick="connectWebHidDevice()" id="btnWebHidConnect" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-surface-container hover:bg-surface-container-high text-on-surface border border-outline-variant/30 transition-all cursor-pointer shadow-2xs">
                <span class="material-symbols-outlined text-sm text-primary">usb</span>
                <span id="txtWebHid">Hubungkan USB RFID (WebHID)</span>
            </button>

            <!-- Hardware Detector: Camera Selector -->
            <div class="relative inline-flex items-center">
                <select id="kioskCameraSelect" onchange="changeKioskCameraDevice(this.value)" class="bg-surface-container border border-outline-variant/30 text-on-surface text-xs font-bold rounded-xl pl-8 pr-7 py-1 appearance-none focus:outline-none focus:border-primary transition-all cursor-pointer">
                    <option value="">Deteksi Kamera...</option>
                </select>
                <span class="material-symbols-outlined absolute left-2.5 text-sm text-primary pointer-events-none">photo_camera</span>
                <span class="material-symbols-outlined absolute right-2 text-sm text-on-surface-variant pointer-events-none">expand_more</span>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Panel: Mode Selector & Scanner / Reader Interface (Col 7) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Mode Switcher Tabs -->
            <div class="bg-surface-container-low p-1.5 rounded-2xl border border-outline-variant/30 flex gap-2 shadow-inner">
                <button type="button" onclick="switchKioskMode('qr')" id="btnKioskModeQr" class="flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 bg-primary text-white shadow-md">
                    <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                    Scan Kode QR
                </button>
                <button type="button" onclick="switchKioskMode('nfc')" id="btnKioskModeNfc" class="flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 bg-transparent text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high">
                    <span class="material-symbols-outlined text-lg">nfc</span>
                    Tap Kartu NFC / RFID
                </button>
            </div>

            <!-- TAB 1: QR CODE CAMERA SCANNER CONTAINER -->
            <div id="kioskQrContainer" class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">videocam</span>
                            Pindai QR Code Karyawan
                        </h3>
                        <p class="text-xs text-on-surface-variant">Arahkan kode QR di depan kamera terminal / pemindai optik / HP Scan-IT</p>
                    </div>
                    <button type="button" onclick="toggleKioskCamera()" class="p-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold border border-outline-variant/30 transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">cameraswitch</span>
                        Ganti Kamera
                    </button>
                </div>

                <!-- Video Viewport -->
                <div class="relative w-full aspect-video bg-black/95 rounded-2xl overflow-hidden shadow-inner flex items-center justify-center border border-outline-variant/20">
                    <video id="kioskQrVideo" class="w-full h-full object-cover" playsinline autoplay muted></video>
                    
                    <!-- Scan Overlay Layer -->
                    <div class="absolute inset-0 border-2 border-primary/40 rounded-2xl pointer-events-none flex items-center justify-center">
                        <!-- Target Box -->
                        <div class="w-48 h-48 sm:w-64 sm:h-64 border-2 border-primary rounded-2xl relative shadow-[0_0_30px_rgba(0,6,102,0.4)]">
                            <!-- Corners -->
                            <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-primary"></div>
                            <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-primary"></div>
                            <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-primary"></div>
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-primary"></div>
                            <!-- Laser Line Animation -->
                            <div class="w-full h-1 bg-gradient-to-r from-transparent via-primary to-transparent absolute top-0 animate-[bounce_2s_infinite]"></div>
                        </div>
                    </div>

                    <!-- Camera Off State & Permission Overlay -->
                    <div id="kioskCameraOffOverlay" class="absolute inset-0 bg-surface-container-lowest/95 backdrop-blur-md hidden flex-col items-center justify-center text-center p-6 space-y-4">
                        <div class="w-14 h-14 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl">photo_camera</span>
                        </div>
                        <div>
                            <h4 class="text-base font-extrabold text-on-surface">Izinkan Akses Kamera Terminal</h4>
                            <p class="text-xs text-on-surface-variant max-w-xs mx-auto mt-1">Browser memerlukan izin Anda untuk menggunakan Kamera Scanner QR / Webcams.</p>
                        </div>
                        <button type="button" onclick="requestBrowserPermissions()" class="px-5 py-3 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base">verified_user</span>
                            Izinkan Kamera di Browser
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 2: NFC / RFID TAP CONTAINER -->
            <div id="kioskNfcContainer" class="hidden bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-8 shadow-sm text-center space-y-6">
                <div class="max-w-md mx-auto space-y-4">
                    <!-- Contactless Icon Graphic -->
                    <div class="relative w-28 h-28 mx-auto flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full bg-primary/10 animate-ping"></div>
                        <div class="absolute inset-2 rounded-full bg-primary/20 animate-pulse"></div>
                        <div class="w-20 h-20 rounded-full bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/30 relative z-10">
                            <span class="material-symbols-outlined text-4xl">contactless</span>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-headline font-extrabold text-xl text-on-surface">Tempelkan Kartu Akses / Scan HP</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Gunakan Card Reader USB / Tap perangkat NFC / Ekstensi Scan-IT to Office</p>
                    </div>

                    <!-- Web NFC HP Permission Button -->
                    <div id="containerNfcMobileBtn" class="space-y-2">
                        <button type="button" onclick="requestMobileNfcPermission()" id="btnActivateNfcMobile" class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-lg animate-pulse">nfc</span>
                            <span id="txtActivateNfcMobile">Aktifkan Sensor NFC HP Ini</span>
                        </button>
                    </div>

                    <!-- Focused Input Listener -->
                    <div class="relative max-w-xs mx-auto">
                        <input type="text" id="kioskNfcInput" autocomplete="off" placeholder="Menunggu scan kartu / HP..." class="w-full bg-surface-container-low border border-outline-variant/40 rounded-xl px-4 py-3 text-center text-sm font-mono font-bold text-primary focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all shadow-inner">
                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-primary text-lg animate-pulse">sensors</span>
                    </div>

                    <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-xs text-amber-800 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">phonelink</span>
                        Terintegrasi dengan Scan-IT to Office! Hasil scan dari aplikasi HP Anda langsung otomatis ditangkap.
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Panel: Session Scan Log Feed (Col 5) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-4">
                    <div>
                        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">history</span>
                            Riwayat Presensi Sesi Ini
                        </h3>
                        <p class="text-xs text-on-surface-variant">Daftar terdeteksi pada terminal</p>
                    </div>
                    <span id="kioskScanCountBadge" class="px-2.5 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                        0 Karyawan
                    </span>
                </div>

                <!-- Live Log Container -->
                <div id="kioskScanFeed" class="space-y-3 max-h-[480px] overflow-y-auto custom-scrollbar pr-1">
                    <!-- Empty State -->
                    <div id="kioskScanFeedEmpty" class="text-center py-12 space-y-2">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">radar</span>
                        <p class="text-xs font-bold text-on-surface-variant">Belum Ada Presensi Terdeteksi</p>
                        <p class="text-[11px] text-on-surface-variant/60">Scan QR, Tap Kartu, atau gunakan Scan-IT to Office HP untuk memulai presensi</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- REAL-TIME SUCCESS / WARNING OVERLAY MODAL -->
<div id="kioskResultOverlay" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-md" onclick="window.closeKioskResultModal()"></div>
    <div id="kioskResultModalContainer" class="bg-surface-container-lowest rounded-3xl w-full max-w-md shadow-2xl overflow-hidden scale-90 transition-transform duration-300 relative z-10 border border-outline-variant/30 p-6 text-center space-y-5">
        
        <!-- Close X Button -->
        <button type="button" onclick="window.closeKioskResultModal()" class="absolute top-4 right-4 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high p-1.5 rounded-full transition-colors flex items-center justify-center" title="Tutup Pop-up (Esc)">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>

        <!-- Header Badge -->
        <div id="kioskResultBadgeHeader" class="py-2.5 px-4 rounded-2xl text-sm font-extrabold flex items-center justify-center gap-2 bg-emerald-500 text-white shadow-md">
            <span id="kioskResultIcon" class="material-symbols-outlined text-xl">check_circle</span>
            <span id="kioskResultTitle">Clock-In Berhasil!</span>
        </div>

        <!-- User Avatar & Details -->
        <div class="space-y-3">
            <img id="kioskResultAvatar" src="" class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-primary/20 shadow-lg" alt="Avatar">
            <div>
                <h3 id="kioskResultName" class="font-headline font-black text-xl text-on-surface tracking-tight">Fullname Karyawan</h3>
                <p id="kioskResultRoleDept" class="text-xs font-bold text-primary mt-0.5">Staff HR · Human Resources</p>
                <p id="kioskResultEmployeeId" class="text-[11px] font-mono font-semibold text-on-surface-variant mt-1">EM-2026-001</p>
            </div>
        </div>

        <!-- Time & Status Box -->
        <div class="p-4 bg-surface-container-low rounded-2xl border border-outline-variant/20 space-y-1">
            <div id="kioskResultMessage" class="text-xs font-semibold text-on-surface">Tepat waktu pada 08:15 WIB</div>
            <div class="text-[10px] text-on-surface-variant">Metode: Kiosk Admin Presensi Massal</div>
        </div>

        <!-- Manual Dismiss Button for Easy Screenshots -->
        <button type="button" onclick="window.closeKioskResultModal()" class="w-full py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30 transition-all flex items-center justify-center gap-2 shadow-2xs">
            <span class="material-symbols-outlined text-base">check</span>
            Tutup & Lanjutkan Pemindaian
        </button>

        <!-- Auto Countdown Timer Progress Bar -->
        <div class="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden">
            <div id="kioskResultProgressBar" class="bg-primary h-full w-full transition-all duration-[4000ms] ease-linear"></div>
        </div>
    </div>
</div>

<script>
(function() {
    var activeMode = 'qr'; // 'qr' or 'nfc'
    var mediaStream = null;
    var isProcessingScan = false;
    var scanHistory = [];
    var currentDeviceId = null;
    var currentFacingMode = 'environment';
    var terminalLat = null;
    var terminalLng = null;
    var dismissTimer = null;

    // Manual Modal Closer
    window.closeKioskResultModal = function() {
        if (dismissTimer) clearTimeout(dismissTimer);
        var modal = document.getElementById('kioskResultOverlay');
        var container = document.getElementById('kioskResultModalContainer');
        if (modal) modal.classList.add('opacity-0', 'pointer-events-none');
        if (container) container.classList.add('scale-90');
        isProcessingScan = false;
        if (activeMode === 'qr') {
            requestAnimationFrame(scanVideoFrame);
        } else {
            focusNfcInput();
        }
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closeKioskResultModal();
        }
    });

    // Explicit Camera Permission Requestor that triggers native Chrome dialog popup!
    window.requestBrowserPermissions = function() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Browser ini tidak mendukung akses kamera. Gunakan Google Chrome / Edge / Safari versi terbaru.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
            stream.getTracks().forEach(function(t) { t.stop(); });
            startKioskCamera();
        }).catch(function(err) {
            alert('Akses Kamera Ditolak atau Dibatasi. Silakan klik ikon gembok / perizinan di sebelah kiri alamat URL browser Anda, lalu izinkan akses Kamera!');
        });
    };

    // Explicit Web NFC Mobile Permission Requestor for Android HP
    window.requestMobileNfcPermission = function() {
        if ('NDEFReader' in window) {
            try {
                var ndef = new NDEFReader();
                ndef.scan().then(function() {
                    var btnTxt = document.getElementById('txtActivateNfcMobile');
                    var btn = document.getElementById('btnActivateNfcMobile');
                    if (btnTxt) btnTxt.textContent = 'Sensor NFC HP Aktif & Siap Tap!';
                    if (btn) btn.className = "w-full py-3 px-4 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 flex items-center justify-center gap-2 pointer-events-none";
                    
                    ndef.addEventListener("reading", function(evt) {
                        if (!isProcessingScan) {
                            var cardId = evt.serialNumber || 'NFC-' + Math.floor(Math.random()*100000);
                            smartProcessScannedText(cardId);
                        }
                    });
                }).catch(function(err) {
                    alert('Gagal mengaktifkan NFC HP: ' + err.message + '. Pastikan fitur NFC di Pengaturan HP Anda sudah dalam posisi ON!');
                });
            } catch(e) {
                alert('Gagal membaca NFC: ' + e.message);
            }
        } else {
            alert('Browser HP ini belum mendukung Web NFC API. Gunakan browser Google Chrome di HP Android!');
        }
    };

    // Hardware Keyboard Wedge Buffer (USB Barcode & RFID Reader & Scan-IT to Office Extension Auto-Trap)
    var keyBuffer = '';
    var lastKeyTime = 0;

    // Web Audio Synthesizer for Touchless Feedback
    function playAudioFeedback(type) {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(800, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } else {
                osc.type = 'square';
                osc.frequency.setValueAtTime(300, ctx.currentTime);
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            }
        } catch (e) {}
    }

    // Digital Clock
    function updateClock() {
        var now = new Date();
        var timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        var dateStr = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        
        var clockEl = document.getElementById('kioskDigitalClock');
        var dateEl = document.getElementById('kioskDigitalDate');
        if (clockEl) clockEl.textContent = timeStr;
        if (dateEl) dateEl.textContent = dateStr;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Get Geolocation Coordinates for Kiosk Terminal
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            terminalLat = pos.coords.latitude;
            terminalLng = pos.coords.longitude;
            var badgeText = document.getElementById('kioskGpsText');
            if (badgeText) badgeText.textContent = terminalLat.toFixed(4) + ', ' + terminalLng.toFixed(4);
        }, function(err) {
            var badgeText = document.getElementById('kioskGpsText');
            if (badgeText) {
                if (err && err.code === err.PERMISSION_DENIED) {
                    badgeText.innerHTML = '<span onclick="window.showLocationPermissionGuide()" class="text-amber-600 font-bold cursor-pointer hover:underline">⚠️ GPS Diblokir - Klik Buka Akses</span>';
                } else {
                    badgeText.textContent = 'GPS Standby (Fixed)';
                }
            }
        });
    }

    // Hardware Detection 1: Enumerate Video Input Devices (External USB Webcams / Scanners)
    function enumerateKioskCameraDevices() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
        
        navigator.mediaDevices.enumerateDevices().then(function(devices) {
            var videoDevices = devices.filter(function(d) { return d.kind === 'videoinput'; });
            var selectEl = document.getElementById('kioskCameraSelect');
            if (!selectEl) return;

            selectEl.innerHTML = '<option value="">Pilih Kamera / Scanner USB...</option>';
            videoDevices.forEach(function(device, idx) {
                var opt = document.createElement('option');
                opt.value = device.deviceId;
                opt.textContent = device.label || ('Kamera ' + (idx + 1));
                selectEl.appendChild(opt);
            });
        }).catch(function() {});
    }

    window.changeKioskCameraDevice = function(deviceId) {
        currentDeviceId = deviceId;
        startKioskCamera();
    };

    // Mode Switcher
    window.switchKioskMode = function(mode) {
        activeMode = mode;
        var btnQr = document.getElementById('btnKioskModeQr');
        var btnNfc = document.getElementById('btnKioskModeNfc');
        var containerQr = document.getElementById('kioskQrContainer');
        var containerNfc = document.getElementById('kioskNfcContainer');

        if (mode === 'qr') {
            btnQr.className = "flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 bg-primary text-white shadow-md";
            btnNfc.className = "flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 bg-transparent text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high";
            containerQr.classList.remove('hidden');
            containerNfc.classList.add('hidden');
            startKioskCamera();
        } else {
            btnNfc.className = "flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 bg-primary text-white shadow-md";
            btnQr.className = "flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 bg-transparent text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high";
            containerNfc.classList.remove('hidden');
            containerQr.classList.add('hidden');
            stopKioskCamera();
            focusNfcInput();
        }
    };

    // Focus Listener for RFID Scanner Field & Scan-IT to Office Target Field
    function focusNfcInput() {
        var inp = document.getElementById('kioskNfcInput');
        if (inp && activeMode === 'nfc') {
            inp.focus();
        }
    }
    document.addEventListener('click', function() {
        if (activeMode === 'nfc') focusNfcInput();
    });

    // Hardware Detection 2: Universal Keyboard Wedge & Extension Trap
    // Traps input from USB RFID, Barcode Scanners, and Scan-IT to Office Chrome Extension
    window.addEventListener('keydown', function(e) {
        var now = Date.now();
        var diff = now - lastKeyTime;
        lastKeyTime = now;

        if (diff > 200) {
            keyBuffer = '';
        }

        if (e.key === 'Enter') {
            if (keyBuffer.length >= 3 && !isProcessingScan) {
                smartProcessScannedText(keyBuffer.trim());
                keyBuffer = '';
                e.preventDefault();
            }
        } else if (e.key.length === 1) {
            keyBuffer += e.key;
        }
    });

    // Explicit Listener for Scan-IT to Office Extension input field updates
    var nfcInput = document.getElementById('kioskNfcInput');
    if (nfcInput) {
        var handleInputChange = function() {
            var val = nfcInput.value.trim();
            if (val && !isProcessingScan) {
                smartProcessScannedText(val);
                nfcInput.value = '';
            }
        };

        nfcInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleInputChange();
            }
        });
        nfcInput.addEventListener('change', handleInputChange);
        nfcInput.addEventListener('input', function() {
            if (nfcInput.value.length >= 3) {
                setTimeout(handleInputChange, 80);
            }
        });
    }

    // Explicit Listener for Scan-IT to Office Extension postMessage Events
    window.addEventListener('message', function(evt) {
        if (evt.data && !isProcessingScan) {
            var payload = evt.data;
            var scannedVal = payload.scannedData || payload.barcode || payload.value || payload.code || payload.text;
            if (typeof payload === 'string' && payload.length >= 3) {
                scannedVal = payload;
            }
            if (scannedVal && String(scannedVal).length >= 3) {
                smartProcessScannedText(String(scannedVal).trim());
            }
        }
    });

    // Custom Event Listener for Scan-IT Extension
    ['scanit', 'scan-it', 'scanit-event'].forEach(function(evtName) {
        document.addEventListener(evtName, function(e) {
            if (e.detail && !isProcessingScan) {
                var val = e.detail.data || e.detail.value || e.detail.barcode;
                if (val) smartProcessScannedText(String(val).trim());
            }
        });
    });

    function extractRawValueFromScan(text) {
        if (!text) return '';
        var rawStr = String(text).trim();

        // 1. Try parsing JSON object / string
        if (rawStr.startsWith('{') && rawStr.endsWith('}')) {
            try {
                var obj = JSON.parse(rawStr);
                if (obj.value) return String(obj.value).trim();
                if (obj.data && obj.data !== 'NFC TAG' && obj.data !== 'QR CODE') return String(obj.data).trim();
                if (obj.code) return String(obj.code).trim();
                if (obj.barcode) return String(obj.barcode).trim();
                if (obj.text) return String(obj.text).trim();
                if (obj.scannedData) return String(obj.scannedData).trim();
            } catch (e) {}
        }

        // 2. Regex fallback for Scan-IT JSON string payload (e.g. {"item":..., "value":"F3:AD:38:E2"})
        var valueMatch = rawStr.match(/"value"\s*:\s*"([^"]+)"/i) || 
                         rawStr.match(/"code"\s*:\s*"([^"]+)"/i) ||
                         rawStr.match(/"barcode"\s*:\s*"([^"]+)"/i) ||
                         rawStr.match(/"text"\s*:\s*"([^"]+)"/i);
        if (valueMatch && valueMatch[1]) {
            return valueMatch[1].trim();
        }

        return rawStr;
    }

    // Smart Detector: Auto-routes scanned string to QR or NFC based on prefix / pattern
    function smartProcessScannedText(text) {
        if (!text) return;
        var cleanVal = extractRawValueFromScan(text);
        if (!cleanVal) return;

        var typeToUse = activeMode;
        if (cleanVal.startsWith('QR-') || cleanVal.includes('http') || cleanVal.includes('://')) {
            typeToUse = 'qr';
        } else if (cleanVal.startsWith('AC-') || cleanVal.startsWith('ID-') || cleanVal.startsWith('CARD-')) {
            typeToUse = 'nfc';
        }
        processScanResult(typeToUse, cleanVal);
    }

    // Hardware Detection 3: WebHID Direct USB Device Connection
    window.connectWebHidDevice = function() {
        if ('hid' in navigator) {
            navigator.hid.requestDevice({ filters: [] }).then(function(devices) {
                if (devices.length > 0) {
                    var device = devices[0];
                    device.open().then(function() {
                        var txt = document.getElementById('txtWebHid');
                        var btn = document.getElementById('btnWebHidConnect');
                        if (txt) txt.textContent = device.productName || 'USB RFID Terhubung';
                        if (btn) btn.className = "inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20";
                        
                        device.addEventListener('inputreport', function(evt) {
                            var data = new Uint8Array(evt.data.buffer);
                            var hexStr = Array.from(data).map(function(b) { return b.toString(16).padStart(2, '0'); }).join('').toUpperCase();
                            if (hexStr && !isProcessingScan) {
                                smartProcessScannedText(hexStr);
                            }
                        });
                    });
                }
            }).catch(function(err) {});
        } else {
            alert('Browser ini belum mendukung WebHID Direct Pairing. Tenang, pemindai USB & Scan-IT to Office HP Anda tetap terdeteksi otomatis!');
        }
    };

    // Camera Management & Frame Decoding Loop
    window.startKioskCamera = function() {
        var video = document.getElementById('kioskQrVideo');
        var overlay = document.getElementById('kioskCameraOffOverlay');
        if (!video) return;

        if (mediaStream) {
            mediaStream.getTracks().forEach(function(t){ t.stop(); });
        }

        var constraints = {
            video: currentDeviceId ? { deviceId: { exact: currentDeviceId } } : { facingMode: currentFacingMode, width: { ideal: 1280 }, height: { ideal: 720 } }
        };

        navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
            mediaStream = stream;
            video.srcObject = stream;
            if (overlay) overlay.classList.add('hidden');
            enumerateKioskCameraDevices();
            requestAnimationFrame(scanVideoFrame);
        }).catch(function(err) {
            if (overlay) overlay.classList.remove('hidden');
        });
    };

    window.stopKioskCamera = function() {
        if (mediaStream) {
            mediaStream.getTracks().forEach(function(t){ t.stop(); });
            mediaStream = null;
        }
    };

    window.toggleKioskCamera = function() {
        currentFacingMode = (currentFacingMode === 'environment') ? 'user' : 'environment';
        currentDeviceId = null;
        startKioskCamera();
    };

    var canvas = document.createElement('canvas');
    var canvasCtx = canvas.getContext('2d');

    function scanVideoFrame() {
        if (activeMode !== 'qr' || isProcessingScan) {
            if (activeMode === 'qr') requestAnimationFrame(scanVideoFrame);
            return;
        }

        var video = document.getElementById('kioskQrVideo');
        if (video && video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            canvasCtx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Engine 1: Native BarcodeDetector API
            if ('BarcodeDetector' in window) {
                var detector = new BarcodeDetector({ formats: ['qr_code'] });
                detector.detect(video).then(function(codes) {
                    if (codes.length > 0 && !isProcessingScan) {
                        smartProcessScannedText(codes[0].rawValue);
                    } else {
                        requestAnimationFrame(scanVideoFrame);
                    }
                }).catch(function() {
                    fallbackJsQrScan(video);
                });
            } else {
                fallbackJsQrScan(video);
            }
        } else {
            requestAnimationFrame(scanVideoFrame);
        }
    }

    function fallbackJsQrScan(video) {
        if (typeof jsQR !== 'undefined') {
            var imageData = canvasCtx.getImageData(0, 0, canvas.width, canvas.height);
            var code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
            if (code && code.data && !isProcessingScan) {
                smartProcessScannedText(code.data);
                return;
            }
        }
        requestAnimationFrame(scanVideoFrame);
    }

    // Process Scanned Data with Backend API
    function processScanResult(type, value) {
        isProcessingScan = true;
        var csrfToken = (function() {
            var el = document.querySelector('meta[name="csrf-token"]') || document.querySelector('input[name="csrf_token"]');
            return el ? (el.content || el.value) : '';
        })();

        var formData = new FormData();
        formData.append('scan_type', type);
        formData.append('scanned_value', value);
        if (terminalLat) formData.append('lat', terminalLat);
        if (terminalLng) formData.append('lng', terminalLng);
        formData.append('csrf_token', csrfToken);

        var jwtToken = (function() {
            var match = document.cookie.match(new RegExp('(^| )jwt_token=([^;]+)'));
            return match ? match[2] : '';
        })();

        fetch('/admin/mass-attendance/process', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-JWT-Token': jwtToken
            },
            body: formData
        })
        .then(function(res) { return res.text(); })
        .then(function(text) {
            var cleanText = text.trim();
            var jsonMatch = cleanText.match(/\{[\s\S]*\}/);
            if (jsonMatch) cleanText = jsonMatch[0];
            var data = JSON.parse(cleanText);

            if (data.success) {
                playAudioFeedback('success');
                showResultOverlay(true, data);
                addScanLog(data);
            } else {
                playAudioFeedback('error');
                showResultOverlay(false, data);
            }
        })
        .catch(function(err) {
            playAudioFeedback('error');
            showResultOverlay(false, { message: 'Terjadi kesalahan jaringan atau server.' });
        });
    }

    // Display Popup Overlay Modal
    function showResultOverlay(isSuccess, data) {
        var modal = document.getElementById('kioskResultOverlay');
        var container = document.getElementById('kioskResultModalContainer');
        var badge = document.getElementById('kioskResultBadgeHeader');
        var icon = document.getElementById('kioskResultIcon');
        var title = document.getElementById('kioskResultTitle');
        var avatar = document.getElementById('kioskResultAvatar');
        var name = document.getElementById('kioskResultName');
        var roleDept = document.getElementById('kioskResultRoleDept');
        var empId = document.getElementById('kioskResultEmployeeId');
        var msg = document.getElementById('kioskResultMessage');
        var progressBar = document.getElementById('kioskResultProgressBar');

        if (dismissTimer) clearTimeout(dismissTimer);

        var durationMs = isSuccess ? 4000 : 10000;

        if (isSuccess && data.user) {
            var isCi = (data.action === 'clock_in');
            badge.className = "py-2.5 px-4 rounded-2xl text-sm font-extrabold flex items-center justify-center gap-2 text-white shadow-md " + (isCi ? "bg-emerald-500" : "bg-blue-600");
            icon.textContent = isCi ? "check_circle" : "logout";
            title.textContent = isCi ? "Clock-In Berhasil!" : "Clock-Out Berhasil!";
            avatar.src = data.user.profile_picture;
            name.textContent = data.user.full_name;
            roleDept.textContent = (data.user.job_title || 'Karyawan') + ' · ' + (data.user.department_name || 'Umum');
            empId.textContent = data.user.employee_id;
            msg.textContent = data.message;
        } else {
            badge.className = "py-2.5 px-4 rounded-2xl text-sm font-extrabold flex items-center justify-center gap-2 text-white shadow-md bg-red-500";
            icon.textContent = "error";
            title.textContent = "Presensi Gagal / Tidak Dikenali";
            avatar.src = "https://www.gravatar.com/avatar/00000000000000000000000000000000?d=identicon";
            name.textContent = "Identitas Tidak Dikenali";
            roleDept.textContent = "Sistem Kiosk Presensi Massal";
            empId.textContent = "ERR-404";
            msg.textContent = data.message || "Gagal memproses presensi.";
        }

        // Show Modal
        if (modal) modal.classList.remove('opacity-0', 'pointer-events-none');
        if (container) container.classList.remove('scale-90');
        if (progressBar) {
            progressBar.style.transitionDuration = durationMs + 'ms';
            progressBar.style.width = '100%';
            setTimeout(function() { progressBar.style.width = '0%'; }, 50);
        }

        // Auto Dismiss with duration
        dismissTimer = setTimeout(function() {
            window.closeKioskResultModal();
        }, durationMs);
    }

    // Add Record to Live Session Scan Log Feed
    function addScanLog(data) {
        if (!data.user) return;
        var feed = document.getElementById('kioskScanFeed');
        var emptyState = document.getElementById('kioskScanFeedEmpty');
        var countBadge = document.getElementById('kioskScanCountBadge');

        if (emptyState) emptyState.remove();

        scanHistory.unshift(data);
        if (countBadge) countBadge.textContent = scanHistory.length + " Karyawan";

        var isCi = (data.action === 'clock_in');
        var badgeBg = isCi ? "bg-emerald-500/10 text-emerald-600 border-emerald-500/20" : "bg-blue-500/10 text-blue-600 border-blue-500/20";
        var actionText = isCi ? "Clock-In" : "Clock-Out";

        var itemHtml = `
            <div class="bg-surface-container-low border border-outline-variant/30 p-3 rounded-2xl flex items-center justify-between gap-3 shadow-2xs transition-all hover:bg-surface-container-high">
                <div class="flex items-center gap-3">
                    <img src="${data.user.profile_picture}" class="w-10 h-10 rounded-full object-cover border border-outline-variant/30" alt="Avatar">
                    <div>
                        <div class="font-bold text-xs text-on-surface">${data.user.full_name}</div>
                        <div class="text-[10px] text-on-surface-variant font-semibold">${data.user.job_title} · ${data.user.department_name}</div>
                    </div>
                </div>
                <div class="text-right space-y-1">
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border ${badgeBg}">
                        <span class="w-1.5 h-1.5 rounded-full ${isCi ? 'bg-emerald-500' : 'bg-blue-500'}"></span>
                        ${actionText} ${data.time}
                    </span>
                    <div class="text-[9px] font-mono text-on-surface-variant">${data.user.employee_id}</div>
                </div>
            </div>
        `;

        if (feed) {
            feed.insertAdjacentHTML('afterbegin', itemHtml);
        }
    }

    // Auto-start QR Camera & Device Enumeration on View Load
    startKioskCamera();
})();
</script>
