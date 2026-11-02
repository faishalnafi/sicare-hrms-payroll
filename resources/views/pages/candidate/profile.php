<?php
/**
 * Candidate Portal - Dokumen Pribadi (/candidate/profile)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$db = \App\Config\Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? null;

$userProfile = null;
if ($userId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#000666] via-[#0c145e] to-[#1a237e] rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative z-10 space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-[11px] font-bold text-blue-200 border border-white/15">
                <span class="material-symbols-outlined text-sm">folder_shared</span>
                <span>Manajemen Berkas & Profil Pelamar</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-headline font-extrabold text-white tracking-tight">Dokumen & Data Pribadi</h1>
            <p class="text-xs md:text-sm text-blue-100/80 max-w-2xl font-medium leading-relaxed">
                Kelola data akun yang Anda isikan saat pertama kali melakukan pendaftaran. Data ini akan secara otomatis terisi ketika Anda melamar lowongan kerja di portal kami.
            </p>
        </div>
    </div>

    <!-- Main Profile Form -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 shadow-sm p-6 md:p-8">
        <form id="candidateProfileForm" onsubmit="saveCandidateProfile(event)" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <!-- Section 1: Informasi Kontak & Identitas -->
            <div class="space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant/15">
                    <span class="material-symbols-outlined text-primary text-xl">badge</span>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface">Informasi Identitas & Kontak</h3>
                        <p class="text-[11px] text-on-surface-variant">Data identitas utama pelamar untuk keperluan korespondensi.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-on-surface">Nama Depan <span class="text-red-600">*</span></label>
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($userProfile['first_name'] ?? '') ?>" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs font-bold text-on-surface" />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-on-surface">Nama Belakang</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($userProfile['last_name'] ?? '') ?>" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-on-surface">Email Utama <span class="text-neutral-400 font-normal">(ID Akun)</span></label>
                        <input type="email" readonly value="<?= htmlspecialchars($userProfile['email'] ?? '') ?>" class="w-full p-2.5 border border-outline-variant/20 rounded-xl bg-surface-container-high/40 text-xs font-bold text-on-surface-variant cursor-not-allowed" />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-on-surface">Nomor Telepon / WhatsApp <span class="text-red-600">*</span></label>
                        <input type="text" name="phone" required value="<?= htmlspecialchars($userProfile['no_telepon'] ?? '') ?>" placeholder="contoh: 081234567890" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface" />
                    </div>
                </div>
            </div>

            <!-- Section 2: Kualifikasi & Ekspektasi -->
            <div class="space-y-4 pt-2">
                <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant/15">
                    <span class="material-symbols-outlined text-primary text-xl">school</span>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface">Kualifikasi & Ekspektasi Karir</h3>
                        <p class="text-[11px] text-on-surface-variant">Latar belakang pendidikan, kompensasi yang diharapkan, dan tautan portofolio.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-on-surface">Pendidikan Terakhir</label>
                        <?php $edu = $userProfile['pendidikan_terakhir'] ?? ''; ?>
                        <select name="education" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface font-semibold">
                            <option value="">-- Pilih Pendidikan --</option>
                            <option value="SMA/SMK" <?= $edu === 'SMA/SMK' ? 'selected' : '' ?>>SMA / SMK</option>
                            <option value="D3" <?= $edu === 'D3' ? 'selected' : '' ?>>D3 (Diploma 3)</option>
                            <option value="S1" <?= $edu === 'S1' ? 'selected' : '' ?>>S1 (Sarjana)</option>
                            <option value="S2" <?= $edu === 'S2' ? 'selected' : '' ?>>S2 (Magister)</option>
                            <option value="S3" <?= $edu === 'S3' ? 'selected' : '' ?>>S3 (Doktor)</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-on-surface">Ekspektasi Gaji (Rp)</label>
                        <input type="number" name="salary" value="<?= htmlspecialchars($userProfile['ekspektasi_gaji'] ?? '') ?>" placeholder="5000000" class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface font-semibold" />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-on-surface">Tautan Portofolio / LinkedIn</label>
                        <input type="url" name="portfolio" value="<?= htmlspecialchars($userProfile['tautan_profesional'] ?? '') ?>" placeholder="https://linkedin.com/in/..." class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs text-on-surface" />
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-on-surface">Riwayat & Ringkasan Pengalaman Kerja</label>
                    <textarea name="experience" rows="3" placeholder="Tuliskan pengalaman kerja atau keahlian utama Anda secara singkat..." class="w-full p-2.5 border border-outline-variant/30 rounded-xl bg-surface-container-low focus:outline-none focus:border-primary text-xs resize-none text-on-surface"><?= htmlspecialchars($userProfile['riwayat_pekerjaan'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Section 3: Berkas Dokumen Pelamar (Live PDF Previewer) -->
            <div class="space-y-6 pt-2">
                <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant/15">
                    <span class="material-symbols-outlined text-primary text-xl">folder_shared</span>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface">Manajemen & Live Pratinjau Berkas Dokumen</h3>
                        <p class="text-[11px] text-on-surface-variant">Pratinjau langsung dokumen CV dan Surat Lamaran yang terunggah dalam format PDF.</p>
                    </div>
                </div>

                <!-- CV / Resume Section with Embedded Live PDF Viewer -->
                <div class="space-y-3 p-5 bg-surface-container-low rounded-2xl border border-outline-variant/20 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-outline-variant/15">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg text-primary">picture_as_pdf</span>
                            <div>
                                <h4 class="text-xs font-bold text-on-surface">Curriculum Vitae (CV / Resume)</h4>
                                <?php if (!empty($userProfile['resume_file'])): ?>
                                    <p class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-xs">check_circle</span>
                                        <span><?= htmlspecialchars(basename($userProfile['resume_file'])) ?></span>
                                    </p>
                                <?php else: ?>
                                    <p class="text-[10px] text-amber-700 font-medium mt-0.5">Belum ada berkas CV yang diunggah.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($userProfile['resume_file'])): ?>
                            <div class="flex items-center gap-2">
                                <a href="<?= htmlspecialchars($userProfile['resume_file']) ?>" target="_blank" download class="inline-flex items-center gap-1 text-[11px] font-bold text-primary bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-xl transition-all">
                                    <span class="material-symbols-outlined text-xs">download</span> Unduh PDF
                                </a>
                                <button type="button" onclick="togglePdfPreview('cvPreviewContainer')" class="inline-flex items-center gap-1 text-[11px] font-bold text-on-surface-variant bg-surface hover:bg-surface-container-high px-3 py-1.5 rounded-xl border border-outline-variant/20 transition-all cursor-pointer">
                                    <span class="material-symbols-outlined text-xs">preview</span> Toggle Pratinjau
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($userProfile['resume_file'])): ?>
                        <?php $resumeExt = strtolower(pathinfo($userProfile['resume_file'], PATHINFO_EXTENSION)); ?>
                        <div id="cvPreviewContainer" class="w-full space-y-2 pt-1">
                            <?php if ($resumeExt === 'pdf'): ?>
                                <div class="w-full rounded-xl overflow-hidden shadow-md border border-gray-700 bg-white">
                                    <iframe id="cvPdfIframe" data-src="<?= htmlspecialchars($userProfile['resume_file']) ?>" class="w-full h-[566px] border-0" allow="autoplay" title="Pratinjau CV"></iframe>
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs font-medium flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-amber-600">info</span>
                                        <span>Pratinjau langsung hanya tersedia untuk file format PDF. Berkas Anda berformat <b>.<?= strtoupper($resumeExt) ?></b>.</span>
                                    </div>
                                    <a href="<?= htmlspecialchars($userProfile['resume_file']) ?>" target="_blank" class="px-3 py-1 bg-amber-200 text-amber-950 rounded-lg text-[11px] font-bold">Unduh File</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-1.5 pt-2 border-t border-outline-variant/10">
                        <label class="block text-xs font-semibold text-on-surface">Unggah / Ganti Berkas CV Baru</label>
                        <div id="cvDropzone" 
                             class="relative border-2 border-dashed border-outline-variant/40 hover:border-primary rounded-xl p-4 bg-surface hover:bg-primary/5 transition-all flex flex-col items-center justify-center text-center cursor-pointer group"
                             onclick="document.getElementById('cvFileInput').click()"
                             ondragover="handleDragOver(event, this)"
                             ondragleave="handleDragLeave(event, this)"
                             ondrop="handleFileDrop(event, 'cvFileInput', 'cvFileNameDisplay', this)">
                            <input type="file" name="resume" id="cvFileInput" accept=".pdf,.doc,.docx" class="hidden" onchange="handleFileSelect(this, 'cvFileNameDisplay')" />
                            <span class="material-symbols-outlined text-3xl text-primary/70 group-hover:scale-110 transition-transform mb-1">cloud_upload</span>
                            <p class="text-xs font-bold text-on-surface">Tarik & Lepas file CV di sini, atau <span class="text-primary hover:underline">Pilih Berkas</span></p>
                            <p class="text-[10px] text-on-surface-variant mt-0.5">Format PDF / DOCX (Maksimal 5MB)</p>
                            <div id="cvFileNameDisplay" class="hidden mt-2 p-2 bg-primary/10 border border-primary/20 rounded-lg text-primary text-xs font-bold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">description</span>
                                <span class="file-name-text"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cover Letter Section with Embedded Live PDF Viewer -->
                <div class="space-y-3 p-5 bg-surface-container-low rounded-2xl border border-outline-variant/20 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-outline-variant/15">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg text-primary">draft</span>
                            <div>
                                <h4 class="text-xs font-bold text-on-surface">Surat Lamaran / Cover Letter</h4>
                                <?php if (!empty($userProfile['cover_letter_file'])): ?>
                                    <p class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-xs">check_circle</span>
                                        <span><?= htmlspecialchars(basename($userProfile['cover_letter_file'])) ?></span>
                                    </p>
                                <?php else: ?>
                                    <p class="text-[10px] text-amber-700 font-medium mt-0.5">Belum ada berkas Surat Lamaran yang diunggah.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($userProfile['cover_letter_file'])): ?>
                            <div class="flex items-center gap-2">
                                <a href="<?= htmlspecialchars($userProfile['cover_letter_file']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-primary bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-xl transition-all">
                                    <span class="material-symbols-outlined text-xs">download</span> Unduh PDF
                                </a>
                                <button type="button" onclick="togglePdfPreview('coverPreviewContainer')" class="inline-flex items-center gap-1 text-[11px] font-bold text-on-surface-variant bg-surface hover:bg-surface-container-high px-3 py-1.5 rounded-xl border border-outline-variant/20 transition-all cursor-pointer">
                                    <span class="material-symbols-outlined text-xs">preview</span> Toggle Pratinjau
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($userProfile['cover_letter_file'])): ?>
                        <?php $coverExt = strtolower(pathinfo($userProfile['cover_letter_file'], PATHINFO_EXTENSION)); ?>
                        <div id="coverPreviewContainer" class="w-full space-y-2 pt-1">
                            <?php if ($coverExt === 'pdf'): ?>
                                <div class="w-full rounded-xl overflow-hidden shadow-md border border-gray-700 bg-white">
                                    <iframe id="coverPdfIframe" data-src="<?= htmlspecialchars($userProfile['cover_letter_file']) ?>" class="w-full h-[566px] border-0" allow="autoplay" title="Pratinjau Surat Lamaran"></iframe>
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs font-medium flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-amber-600">info</span>
                                        <span>Pratinjau langsung hanya tersedia untuk file format PDF. Berkas Anda berformat <b>.<?= strtoupper($coverExt) ?></b>.</span>
                                    </div>
                                    <a href="<?= htmlspecialchars($userProfile['cover_letter_file']) ?>" target="_blank" class="px-3 py-1 bg-amber-200 text-amber-950 rounded-lg text-[11px] font-bold">Unduh File</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-1.5 pt-2 border-t border-outline-variant/10">
                        <label class="block text-xs font-semibold text-on-surface">Unggah / Ganti Berkas Surat Lamaran Baru</label>
                        <div id="coverDropzone" 
                             class="relative border-2 border-dashed border-outline-variant/40 hover:border-primary rounded-xl p-4 bg-surface hover:bg-primary/5 transition-all flex flex-col items-center justify-center text-center cursor-pointer group"
                             onclick="document.getElementById('coverFileInput').click()"
                             ondragover="handleDragOver(event, this)"
                             ondragleave="handleDragLeave(event, this)"
                             ondrop="handleFileDrop(event, 'coverFileInput', 'coverFileNameDisplay', this)">
                            <input type="file" name="cover_letter" id="coverFileInput" accept=".pdf,.doc,.docx" class="hidden" onchange="handleFileSelect(this, 'coverFileNameDisplay')" />
                            <span class="material-symbols-outlined text-3xl text-primary/70 group-hover:scale-110 transition-transform mb-1">cloud_upload</span>
                            <p class="text-xs font-bold text-on-surface">Tarik & Lepas file Surat Lamaran di sini, atau <span class="text-primary hover:underline">Pilih Berkas</span></p>
                            <p class="text-[10px] text-on-surface-variant mt-0.5">Format PDF / DOCX (Maksimal 5MB)</p>
                            <div id="coverFileNameDisplay" class="hidden mt-2 p-2 bg-primary/10 border border-primary/20 rounded-lg text-primary text-xs font-bold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">draft</span>
                                <span class="file-name-text"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex items-center justify-end border-t border-outline-variant/15">
                <button type="submit" class="px-6 py-3 bg-[#000666] hover:bg-[#0c145e] text-white font-bold text-xs rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2 cursor-pointer">
                    <span class="material-symbols-outlined text-base">save</span> Simpan Perubahan Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loadBlobIframe(iframeId) {
    const iframe = document.getElementById(iframeId);
    if (iframe && iframe.dataset.src) {
        fetch(iframe.dataset.src)
            .then(r => r.blob())
            .then(blob => {
                iframe.src = URL.createObjectURL(blob);
            })
            .catch(() => {
                iframe.src = iframe.dataset.src;
            });
    }
}

// Load PDF iframe blobs on ready / SPA page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        loadPdfIframe('cvPdfIframe');
        loadPdfIframe('coverPdfIframe');
    });
} else {
    loadPdfIframe('cvPdfIframe');
    loadPdfIframe('coverPdfIframe');
}

function togglePdfPreview(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.classList.toggle('hidden');
    }
}

function handleDragOver(e, dropzone) {
    e.preventDefault();
    e.stopPropagation();
    dropzone.classList.add('border-primary', 'bg-primary/10');
}

function handleDragLeave(e, dropzone) {
    e.preventDefault();
    e.stopPropagation();
    dropzone.classList.remove('border-primary', 'bg-primary/10');
}

function handleFileDrop(e, inputId, displayId, dropzone) {
    e.preventDefault();
    e.stopPropagation();
    dropzone.classList.remove('border-primary', 'bg-primary/10');
    
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        const input = document.getElementById(inputId);
        input.files = e.dataTransfer.files;
        handleFileSelect(input, displayId);
    }
}

function handleFileSelect(input, displayId) {
    const display = document.getElementById(displayId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        display.querySelector('.file-name-text').textContent = 'File Dipilih: ' + file.name;
        display.classList.remove('hidden');
    } else {
        display.classList.add('hidden');
    }
}

function saveCandidateProfile(e) {
    e.preventDefault();
    const form = document.getElementById('candidateProfileForm');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Menyimpan Perubahan...',
        text: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('/candidate/profile/update', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Data Berhasil Diperbarui!',
                text: data.message,
                confirmButtonColor: '#000666'
            }).then(() => {
                if (window.loadPage) window.loadPage('/candidate/profile');
                else window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: data.message,
                confirmButtonColor: '#000666'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Gagal menyambung ke server.',
            confirmButtonColor: '#000666'
        });
    });
}
</script>
