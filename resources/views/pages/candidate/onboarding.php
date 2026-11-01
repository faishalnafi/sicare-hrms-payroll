<?php
/**
 * Candidate Portal - Multi-Step Wizard Onboarding (/candidate/onboarding)
 */
use App\Services\ViewHelper;

$user = ViewHelper::getFullUserProfile() ?? [];
$onboardingStatus = $user['onboarding_status'] ?? 'not_started';
$isSubmitted = in_array($onboardingStatus, ['submitted', 'completed']);
?>

<div class="space-y-6 max-w-5xl mx-auto pb-12">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <h1 class="font-headline text-2xl md:text-3xl font-extrabold text-primary tracking-tight">Wizard Onboarding Karyawan</h1>
                <?php if ($onboardingStatus === 'completed'): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200">
                        <span class="material-symbols-outlined text-sm">verified</span> Verified & Active Employee
                    </span>
                <?php elseif ($onboardingStatus === 'submitted'): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-50 text-amber-700 text-xs font-bold rounded-full border border-amber-200">
                        <span class="material-symbols-outlined text-sm">pending_actions</span> Menunggu Verifikasi HR
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full border border-blue-200">
                        <span class="material-symbols-outlined text-sm">edit_document</span> Draf Onboarding
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-on-surface-variant font-medium text-sm">
                Lengkapi seluruh data administratif legal dan unggah berkas pendukung untuk mengaktifkan status karyawan Anda.
            </p>
        </div>
    </div>

    <?php if ($onboardingStatus === 'completed'): ?>
        <!-- Completed State Banner -->
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-8 text-center space-y-4">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-sm">
                <span class="material-symbols-outlined text-3xl">task_alt</span>
            </div>
            <h2 class="text-2xl font-bold text-emerald-950 font-headline">Selamat! Proses Onboarding Selesai</h2>
            <p class="text-emerald-800 text-sm max-w-xl mx-auto leading-relaxed">
                Data administrasi dan berkas legal Anda telah terverifikasi oleh tim HR Operations. Status akun Anda kini aktif sebagai Karyawan Resmi perusahaan.
            </p>
            <div class="pt-2">
                <a href="/employee/profile" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition-all text-sm">
                    <span class="material-symbols-outlined text-base">person</span> Lihat Profil Karyawan
                </a>
            </div>
        </div>
    <?php else: ?>

    <?php if ($onboardingStatus === 'submitted'): ?>
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 flex items-center gap-4">
            <span class="material-symbols-outlined text-amber-600 text-2xl">info</span>
            <div class="text-sm text-amber-900">
                <strong class="font-bold">Berkas Sudah Dikirim:</strong> Anda telah mengirimkan berkas onboarding pada sistem. Anda masih dapat memperbarui data jika terdapat perbaikan sebelum diverifikasi oleh HR.
            </div>
        </div>
    <?php endif; ?>

    <!-- Wizard Stepper Nav -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/15 shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-center" id="wizard-steps-header">
            <button type="button" onclick="goToStep(1)" id="step-btn-1" class="step-btn py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all bg-primary text-white shadow-sm">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs">1</span>
                <span>Kependudukan</span>
            </button>
            <button type="button" onclick="goToStep(2)" id="step-btn-2" class="step-btn py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all bg-surface text-on-surface-variant hover:bg-surface-container">
                <span class="w-6 h-6 rounded-full bg-outline-variant/30 flex items-center justify-center text-xs">2</span>
                <span>Finansial & Bank</span>
            </button>
            <button type="button" onclick="goToStep(3)" id="step-btn-3" class="step-btn py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all bg-surface text-on-surface-variant hover:bg-surface-container">
                <span class="w-6 h-6 rounded-full bg-outline-variant/30 flex items-center justify-center text-xs">3</span>
                <span>Kontak Darurat</span>
            </button>
            <button type="button" onclick="goToStep(4)" id="step-btn-4" class="step-btn py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all bg-surface text-on-surface-variant hover:bg-surface-container">
                <span class="w-6 h-6 rounded-full bg-outline-variant/30 flex items-center justify-center text-xs">4</span>
                <span>Upload Berkas</span>
            </button>
        </div>
    </div>

    <!-- Wizard Form -->
    <form id="onboarding-form" enctype="multipart/form-data" class="bg-surface-container-lowest p-6 md:p-8 rounded-2xl border border-outline-variant/15 shadow-sm space-y-8">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <!-- STEP 1: Data Kependudukan -->
        <div id="step-content-1" class="step-content space-y-6">
            <div class="border-b border-outline-variant/15 pb-4">
                <h3 class="text-lg font-bold text-on-surface font-headline flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">badge</span>
                    Langkah 1: Identitas & Data Kependudukan
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">Pastikan nama dan NIK KTP diisi persis sesuai dengan dokumen resmi negara.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nama Lengkap (Sesuai KTP) <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_sesuai_ktp" value="<?= htmlspecialchars($user['nama_sesuai_ktp'] ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>" required class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">NIK KTP (16 Digit) <span class="text-red-500">*</span></label>
                    <input type="text" name="ktp_nik" maxlength="16" pattern="[0-9]{16}" value="<?= htmlspecialchars($user['ktp_nik'] ?? '') ?>" required placeholder="Contoh: 3171012345670001" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Tanggal Lahir <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($user['tanggal_lahir'] ?? '') ?>" required class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <select name="jenis_kelamin" required class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="Laki-laki" <?= ($user['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= ($user['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Status Pernikahan <span class="text-red-500">*</span></label>
                    <select name="status_pernikahan" required class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="">-- Pilih Status --</option>
                        <option value="Lajang" <?= ($user['status_pernikahan'] ?? '') === 'Lajang' ? 'selected' : '' ?>>Lajang (TK/0)</option>
                        <option value="Menikah" <?= ($user['status_pernikahan'] ?? '') === 'Menikah' ? 'selected' : '' ?>>Menikah</option>
                        <option value="Cerai" <?= ($user['status_pernikahan'] ?? '') === 'Cerai' ? 'selected' : '' ?>>Cerai</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nomor Telepon / WhatsApp <span class="text-red-500">*</span></label>
                    <input type="tel" name="no_telepon" value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>" required placeholder="Contoh: 081234567890" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-2">Alamat Sesuai KTP <span class="text-red-500">*</span></label>
                <textarea name="alamat_ktp" rows="2" required class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary"><?= htmlspecialchars($user['alamat_ktp'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-2">Alamat Domisili Saat Ini <span class="text-red-500">*</span></label>
                <textarea name="alamat_domisili" rows="2" required class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary"><?= htmlspecialchars($user['alamat_domisili'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- STEP 2: Finansial & Bank -->
        <div id="step-content-2" class="step-content space-y-6 hidden">
            <div class="border-b border-outline-variant/15 pb-4">
                <h3 class="text-lg font-bold text-on-surface font-headline flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">account_balance</span>
                    Langkah 2: Informasi Bank & Rekening Payroll
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">Data rekening bank ini akan digunakan oleh sistem Payroll untuk pencairan gaji bulanan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nama Bank Penerima <span class="text-red-500">*</span></label>
                    <select name="bank_name" required class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="">-- Pilih Bank --</option>
                        <option value="BCA" <?= ($user['bank_name'] ?? '') === 'BCA' ? 'selected' : '' ?>>Bank Central Asia (BCA)</option>
                        <option value="Mandiri" <?= ($user['bank_name'] ?? '') === 'Mandiri' ? 'selected' : '' ?>>Bank Mandiri</option>
                        <option value="BNI" <?= ($user['bank_name'] ?? '') === 'BNI' ? 'selected' : '' ?>>Bank Negara Indonesia (BNI)</option>
                        <option value="BRI" <?= ($user['bank_name'] ?? '') === 'BRI' ? 'selected' : '' ?>>Bank Rakyat Indonesia (BRI)</option>
                        <option value="CIMB Niaga" <?= ($user['bank_name'] ?? '') === 'CIMB Niaga' ? 'selected' : '' ?>>CIMB Niaga</option>
                        <option value="Permata" <?= ($user['bank_name'] ?? '') === 'Permata' ? 'selected' : '' ?>>Bank Permata</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nomor Rekening Bank <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_account_number" value="<?= htmlspecialchars($user['bank_account_number'] ?? '') ?>" required placeholder="Contoh: 1234567890" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nomor NPWP (15 / 16 Digit) <span class="text-red-500">*</span></label>
                    <input type="text" name="npwp_number" value="<?= htmlspecialchars($user['npwp_number'] ?? '') ?>" required placeholder="Contoh: 01.234.567.8-901.000" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nomor BPJS Ketenagakerjaan (Jika Ada)</label>
                    <input type="text" name="bpjs_tk" value="<?= htmlspecialchars($user['bpjs_tk'] ?? '') ?>" placeholder="Nomor KPJ BPSTK" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nomor BPJS Kesehatan (Jika Ada)</label>
                    <input type="text" name="bpjs_kes" value="<?= htmlspecialchars($user['bpjs_kes'] ?? '') ?>" placeholder="Nomor Kartu BPJS Kesehatan" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>

        <!-- STEP 3: Kontak Darurat -->
        <div id="step-content-3" class="step-content space-y-6 hidden">
            <div class="border-b border-outline-variant/15 pb-4">
                <h3 class="text-lg font-bold text-on-surface font-headline flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">contact_phone</span>
                    Langkah 3: Kontak Darurat & Penanggung Jawab
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">Kontak yang dapat dihubungi oleh perusahaan saat kondisi darurat operasional.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nama Kontak Darurat <span class="text-red-500">*</span></label>
                    <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>" required placeholder="Nama lengkap wali/pasangan" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Hubungan Keluarga <span class="text-red-500">*</span></label>
                    <input type="text" name="emergency_contact_relation" value="<?= htmlspecialchars($user['emergency_contact_relation'] ?? '') ?>" required placeholder="Misal: Orang Tua, Suami/Istri, Saudara" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Nomor HP Kontak Darurat <span class="text-red-500">*</span></label>
                    <input type="tel" name="emergency_contact_phone" value="<?= htmlspecialchars($user['emergency_contact_phone'] ?? '') ?>" required placeholder="Contoh: 081298765432" class="w-full px-4 py-3 bg-surface border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>

        <!-- STEP 4: Upload Berkas -->
        <div id="step-content-4" class="step-content space-y-6 hidden">
            <div class="border-b border-outline-variant/15 pb-4">
                <h3 class="text-lg font-bold text-on-surface font-headline flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">upload_file</span>
                    Langkah 4: Unggah Berkas Dokumen Legal
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">Format berkas yang diterima: PDF, JPG, PNG (Maksimal 10MB per berkas). Validasi binary finfo aktif.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Foto KTP -->
                <div class="p-4 bg-surface rounded-xl border border-outline-variant/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-base">id_card</span> Foto KTP Asli <span class="text-red-500">*</span>
                        </label>
                        <?php if (!empty($user['ktp_file'])): ?>
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">check_circle</span> Tersimpan
                            </span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="ktp_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDoc(this, 'preview_ktp')" <?= empty($user['ktp_file']) ? 'required' : '' ?> class="block w-full text-xs text-on-surface-variant file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer">
                    <div id="preview_ktp" class="hidden p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span class="material-symbols-outlined text-base text-emerald-600">task</span>
                            <span class="doc-name font-bold truncate"></span>
                        </div>
                        <span class="doc-size text-[10px] font-semibold text-emerald-600 shrink-0"></span>
                    </div>
                </div>

                <!-- Kartu Keluarga -->
                <div class="p-4 bg-surface rounded-xl border border-outline-variant/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-base">family_history</span> Kartu Keluarga (KK) <span class="text-red-500">*</span>
                        </label>
                        <?php if (!empty($user['kk_file'])): ?>
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">check_circle</span> Tersimpan
                            </span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="kk_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDoc(this, 'preview_kk')" <?= empty($user['kk_file']) ? 'required' : '' ?> class="block w-full text-xs text-on-surface-variant file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer">
                    <div id="preview_kk" class="hidden p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span class="material-symbols-outlined text-base text-emerald-600">task</span>
                            <span class="doc-name font-bold truncate"></span>
                        </div>
                        <span class="doc-size text-[10px] font-semibold text-emerald-600 shrink-0"></span>
                    </div>
                </div>

                <!-- Kartu NPWP -->
                <div class="p-4 bg-surface rounded-xl border border-outline-variant/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-base">request_quote</span> Kartu NPWP <span class="text-red-500">*</span>
                        </label>
                        <?php if (!empty($user['npwp_file'])): ?>
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">check_circle</span> Tersimpan
                            </span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="npwp_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDoc(this, 'preview_npwp')" <?= empty($user['npwp_file']) ? 'required' : '' ?> class="block w-full text-xs text-on-surface-variant file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer">
                    <div id="preview_npwp" class="hidden p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span class="material-symbols-outlined text-base text-emerald-600">task</span>
                            <span class="doc-name font-bold truncate"></span>
                        </div>
                        <span class="doc-size text-[10px] font-semibold text-emerald-600 shrink-0"></span>
                    </div>
                </div>

                <!-- Buku Tabungan -->
                <div class="p-4 bg-surface rounded-xl border border-outline-variant/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-base">account_balance_wallet</span> Halaman Depan Buku Tabungan <span class="text-red-500">*</span>
                        </label>
                        <?php if (!empty($user['bank_book_file'])): ?>
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">check_circle</span> Tersimpan
                            </span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="bank_book_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDoc(this, 'preview_bank')" <?= empty($user['bank_book_file']) ? 'required' : '' ?> class="block w-full text-xs text-on-surface-variant file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer">
                    <div id="preview_bank" class="hidden p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span class="material-symbols-outlined text-base text-emerald-600">task</span>
                            <span class="doc-name font-bold truncate"></span>
                        </div>
                        <span class="doc-size text-[10px] font-semibold text-emerald-600 shrink-0"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Nav Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-outline-variant/15">
            <button type="button" id="prev-btn" onclick="prevStep()" class="hidden px-5 py-2.5 bg-surface hover:bg-surface-container text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-base">arrow_back</span> Sebelumnya
            </button>
            <div class="ml-auto flex items-center gap-3">
                <button type="button" id="next-btn" onclick="nextStep()" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2">
                    Lanjut <span class="material-symbols-outlined text-base">arrow_forward</span>
                </button>
                <button type="submit" id="submit-btn" class="hidden px-7 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">send</span> Kirim Berkas Onboarding
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
let currentStep = 1;
const totalSteps = 4;

function updateWizardUI() {
    for (let i = 1; i <= totalSteps; i++) {
        const content = document.getElementById(`step-content-${i}`);
        const btn = document.getElementById(`step-btn-${i}`);
        if (!content || !btn) continue;

        if (i === currentStep) {
            content.classList.remove('hidden');
            btn.className = "step-btn py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all bg-primary text-white shadow-sm";
            btn.querySelector('span:first-child').className = "w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs";
        } else {
            content.classList.add('hidden');
            btn.className = "step-btn py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all bg-surface text-on-surface-variant hover:bg-surface-container";
            btn.querySelector('span:first-child').className = "w-6 h-6 rounded-full bg-outline-variant/30 flex items-center justify-center text-xs";
        }
    }

    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const submitBtn = document.getElementById('submit-btn');

    if (currentStep === 1) {
        prevBtn.classList.add('hidden');
    } else {
        prevBtn.classList.remove('hidden');
    }

    if (currentStep === totalSteps) {
        nextBtn.classList.add('hidden');
        submitBtn.classList.remove('hidden');
    } else {
        nextBtn.classList.remove('hidden');
        submitBtn.classList.add('hidden');
    }
}

function goToStep(step) {
    if (step < 1 || step > totalSteps) return;
    currentStep = step;
    updateWizardUI();
}

function nextStep() {
    if (currentStep < totalSteps) {
        currentStep++;
        updateWizardUI();
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateWizardUI();
    }
}

function previewDoc(input, previewId) {
    const box = document.getElementById(previewId);
    if (!box) return;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const nameEl = box.querySelector('.doc-name');
        const sizeEl = box.querySelector('.doc-size');
        if (nameEl) nameEl.textContent = file.name;
        if (sizeEl) {
            const kb = (file.size / 1024).toFixed(1);
            sizeEl.textContent = kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
        }
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}

document.getElementById('onboarding-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span class="material-symbols-outlined text-base animate-spin">sync</span> Memproses...`;

    fetch('/candidate/onboarding/submit', {
        method: 'POST',
        headers: {
            'X-JWT-Token': window.jwtToken || ''
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span class="material-symbols-outlined text-base">send</span> Kirim Berkas Onboarding`;

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dikirim!',
                text: data.message,
                confirmButtonColor: '#000666'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mengirim',
                text: data.message,
                confirmButtonColor: '#000666'
            });
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span class="material-symbols-outlined text-base">send</span> Kirim Berkas Onboarding`;
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Terjadi gangguan jaringan atau respon server tidak valid.',
            confirmButtonColor: '#000666'
        });
    });
});
</script>