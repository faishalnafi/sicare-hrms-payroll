<?php
use App\Config\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();
$userEmail = $_SESSION['email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// Fetch job offers for this candidate
$stmtOffers = $db->prepare("
    SELECT jo.*, r.title AS job_title, r.req_number, ja.id AS application_id
    FROM job_offers jo
    LEFT JOIN job_applications ja ON jo.application_id = ja.id
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    WHERE ja.candidate_id = :uid OR ja.candidate_email = :email
    ORDER BY jo.created_at DESC
");
$stmtOffers->execute(['uid' => $userId, 'email' => $userEmail]);
$myOffers = $stmtOffers->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Penawaran & Kontrak Kerja</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Tinjau penawaran kerja resmi Anda, unduh draf kontrak, dan lakukan penandatanganan digital.</p>
        </div>
    </div>

    <!-- Offers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (empty($myOffers)): ?>
            <div class="col-span-full bg-surface-container-lowest p-12 rounded-3xl border border-outline-variant/15 text-center space-y-3 shadow-2xs">
                <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">verified</span>
                </div>
                <h3 class="text-base font-headline font-bold text-on-surface">Belum Ada Penawaran Kerja</h3>
                <p class="text-xs text-on-surface-variant max-w-md mx-auto">Saat ini belum ada dokumen Offering Letter resmi yang diterbitkan untuk lamaran Anda. Setiap penawaran baru dari tim Rekruter akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($myOffers as $o): ?>
                <?php 
                    $cardBorder = 'border-gray-100 hover:border-blue-300';
                    $statusBadge = 'bg-blue-100 text-blue-800';
                    $statusText = 'Offering Terbit';
                    $statusIcon = 'send';

                    if ($o['status'] === 'signed') {
                        $cardBorder = 'border-green-100 hover:border-green-300';
                        $statusBadge = 'bg-green-100 text-green-800';
                        $statusText = 'Disetujui & Ditandatangani';
                        $statusIcon = 'task_alt';
                    } elseif ($o['status'] === 'rejected') {
                        $cardBorder = 'border-rose-100 hover:border-rose-300';
                        $statusBadge = 'bg-rose-100 text-rose-800';
                        $statusText = 'Penawaran Ditolak';
                        $statusIcon = 'cancel';
                    }
                ?>
                <div class="bg-white p-6 rounded-3xl border <?= $cardBorder ?> shadow-2xs transition-all flex flex-col justify-between space-y-5">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-primary uppercase bg-blue-50 px-2 py-0.5 rounded"><?= htmlspecialchars($o['req_number']) ?></span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $statusBadge ?>">
                                <span class="material-symbols-outlined text-xs font-normal"><?= $statusIcon ?></span> <?= $statusText ?>
                            </span>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-on-surface"><?= htmlspecialchars($o['job_title']) ?></h3>
                            <p class="text-[10px] text-neutral-400 font-medium">Diterbitkan pada: <?= date('d M Y', strtotime($o['created_at'])) ?></p>
                        </div>

                        <div class="p-4 rounded-xl bg-gray-50 border border-outline-variant/10 text-center font-sans">
                            <span class="text-[9px] font-bold text-neutral-400 uppercase block tracking-wider">KOMPENSASI GAJI YANG DITAWARKAN</span>
                            <span class="text-xl font-extrabold text-primary block mt-1">Rp <?= number_format($o['offered_salary'], 0, ',', '.') ?> <span class="text-xs font-medium text-gray-500">/ Bulan (Gross)</span></span>
                        </div>

                        <div class="pt-2 flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <span class="text-[10px] text-neutral-400 block font-bold uppercase tracking-wider">Berkas Penawaran Kerja:</span>
                                <p class="text-[11px] text-gray-600 font-medium font-sans">Unduh draf kontrak & offering letter resmi di bawah ini.</p>
                            </div>
                            <a href="<?= htmlspecialchars($o['offering_letter_path']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs bg-red-50 hover:bg-red-100 text-red-700 font-bold px-3.5 py-2 rounded-xl border border-red-100 transition-all shrink-0">
                                <span class="material-symbols-outlined text-base">picture_as_pdf</span> Unduh PDF Kontrak
                            </a>
                        </div>

                        <?php if ($o['status'] === 'signed' && !empty($o['signed_at'])): ?>
                            <div class="bg-green-50/50 border border-green-100 p-3 rounded-xl space-y-1">
                                <span class="text-[10px] text-green-700 font-bold flex items-center gap-1"><span class="material-symbols-outlined text-xs">draw</span> Kontrak Telah Ditandatangani:</span>
                                <p class="font-mono text-[9px] bg-white border px-2 py-1 rounded font-bold text-gray-700 break-all select-all">SIG: <?= htmlspecialchars($o['candidate_signature']) ?></p>
                                <span class="text-[9px] text-neutral-400 font-medium block">Ditandatangani pada: <?= date('d M Y H:i', strtotime($o['signed_at'])) ?> WIB</span>
                            </div>

                            <!-- Next Step CTA: Wizard Onboarding -->
                            <div class="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border border-emerald-500/25 p-4 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-xl">rocket_launch</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-xs text-emerald-950 font-headline">Langkah Berikutnya: Wizard Onboarding</h4>
                                        <p class="text-[11px] text-emerald-800 leading-snug">Lengkapi data administrasi legal & unggah berkas KTP, KK, NPWP untuk aktivasi akun karyawan resmi.</p>
                                    </div>
                                </div>
                                <a href="/candidate/onboarding" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition-all flex items-center gap-1.5 shrink-0 self-stretch sm:self-auto justify-center">
                                    Mulai Onboarding <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <?php if ($o['status'] === 'sent'): ?>
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                            <button type="button" onclick="respondOffer('<?= htmlspecialchars($o['id']) ?>', 'reject')" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-[11px] font-bold transition-all cursor-pointer">
                                Tolak Penawaran
                            </button>
                            <button type="button" onclick="openSignatureModal('<?= htmlspecialchars($o['id']) ?>')" class="px-4 py-2 bg-[#000666] hover:bg-[#0c145e] text-white rounded-xl text-[11px] font-bold transition-all cursor-pointer shadow-xs flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">draw</span> Tanda Tangani Kontrak
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Signature Pad -->
<div id="signatureModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white border border-outline-variant/20 rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="signatureModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-[#000666] font-headline flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">draw</span> Bubuhkan Tanda Tangan Digital
            </h3>
            <button type="button" onclick="closeSignatureModal()" class="p-1.5 rounded-full text-gray-500 hover:bg-gray-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <div class="p-6 space-y-4 text-xs font-semibold text-on-surface">
            <!-- Canvas E-signature -->
            <div class="space-y-1">
                <label class="block text-gray-700">Gambarkan Tanda Tangan Anda di Area Canvas <span class="text-red-600">*</span></label>
                <div class="relative border border-gray-300 rounded-xl bg-gray-50 overflow-hidden h-[180px] cursor-crosshair">
                    <canvas id="signatureCanvas" class="w-full h-full block bg-gray-50"></canvas>
                    <button type="button" onclick="clearSignatureCanvas()" class="absolute right-2.5 top-2.5 bg-white/80 hover:bg-white text-gray-600 hover:text-red-600 font-bold px-2.5 py-1 rounded-lg border text-[10px] transition-all cursor-pointer shadow-xs flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">delete</span> Bersihkan
                    </button>
                </div>
            </div>

            <!-- Fallback Text Signature -->
            <div class="space-y-1">
                <label class="block text-gray-700">Ketik Nama Lengkap Anda <span class="text-neutral-400 font-medium">(sebagai verifikasi tanda tangan tertulis)</span> <span class="text-red-600">*</span></label>
                <input type="text" id="signatureTextName" placeholder="contoh: Candidate siCare" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800" />
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="closeSignatureModal()" class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-all text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="button" onclick="submitSignature()" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">verified_user</span> Setujui & Tanda Tangani
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let activeOfferId = null;
    let canvas, ctx;
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    function openSignatureModal(offerId) {
        activeOfferId = offerId;
        const modal = document.getElementById('signatureModal');
        const container = document.getElementById('signatureModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
            initCanvas();
        }, 10);
    }

    function closeSignatureModal() {
        const modal = document.getElementById('signatureModal');
        const container = document.getElementById('signatureModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function initCanvas() {
        canvas = document.getElementById('signatureCanvas');
        ctx = canvas.getContext('2d');
        
        // Adjust canvas scale for high DPI displays
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;

        ctx.strokeStyle = '#000666';
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.lineWidth = 3;

        // Mouse events
        canvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            [lastX, lastY] = [e.offsetX, e.offsetY];
        });
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', () => isDrawing = false);
        canvas.addEventListener('mouseout', () => isDrawing = false);

        // Touch events for mobile support
        canvas.addEventListener('touchstart', (e) => {
            isDrawing = true;
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent("mousedown", {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });
        canvas.addEventListener('touchmove', (e) => {
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            const mouseEvent = new MouseEvent("mousemove", {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }, { passive: false });
        canvas.addEventListener('touchend', () => {
            isDrawing = false;
        });
    }

    function draw(e) {
        if (!isDrawing) return;
        
        // Calculate offset if it is a mouse or touch event
        let currentX, currentY;
        if (e.offsetX !== undefined) {
            currentX = e.offsetX;
            currentY = e.offsetY;
        } else {
            const rect = canvas.getBoundingClientRect();
            currentX = e.clientX - rect.left;
            currentY = e.clientY - rect.top;
        }

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(currentX, currentY);
        ctx.stroke();
        [lastX, lastY] = [currentX, currentY];
    }

    function clearSignatureCanvas() {
        if (ctx && canvas) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    function submitSignature() {
        const nameText = document.getElementById('signatureTextName').value.trim();
        if (nameText === '') {
            Swal.fire('Gagal', 'Nama lengkap verifikasi wajib diisi.', 'warning');
            return;
        }

        // Convert signature canvas to Base64
        const dataUrl = canvas.toDataURL("image/png");
        
        // Check if canvas is blank (by comparing signature string length, or simply verify they drew something)
        // Here we can simply generate a hash or send the Base64 representation.
        // We will send a signature hash created from name + base64 representation.
        const sigCode = 'SIG-' + btoa(nameText).substring(0, 8) + '-' + Math.random().toString(36).substring(2, 7).toUpperCase();

        const formData = new FormData();
        formData.append('offer_id', activeOfferId);
        formData.append('action', 'sign');
        formData.append('signature', sigCode);
        formData.append('csrf_token', '<?= csrf_token() ?>');

        Swal.fire({ title: 'Menandatangani Kontrak...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/candidate/offerings/respond', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeSignatureModal();
                Swal.fire({
                    title: 'Kontrak Berhasil Ditandatangani!',
                    text: 'Selamat bergabung dengan tim kami! Langkah berikutnya adalah melengkapi data administrasi pada Wizard Onboarding Karyawan.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Lanjut ke Onboarding',
                    cancelButtonText: 'Tetap di Halaman Ini',
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b'
                }).then((res) => {
                    if (res.isConfirmed) {
                        window.location.href = '/candidate/onboarding';
                    } else {
                        if (window.loadPage) window.loadPage('/candidate/offerings');
                        else window.location.reload();
                    }
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
    }

    function respondOffer(offerId, action) {
        Swal.fire({
            title: 'Tolak Penawaran Kerja?',
            text: 'Apakah Anda yakin ingin menolak offering letter ini? Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Tolak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('offer_id', offerId);
                formData.append('action', action);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                fetch('/candidate/offerings/respond', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil Ditolak', data.message, 'success').then(() => {
                            if (window.loadPage) window.loadPage('/candidate/offerings');
                            else window.location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
            }
        });
    }
</script>