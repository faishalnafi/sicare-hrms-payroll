<?php
$sessName  = $_SESSION['name'] ?? 'Alex Rivera';
$sessEmail = $_SESSION['email'] ?? 'alex.rivera@example.com';

// Fetch dynamic user data from DB
$db = \App\Config\Database::getInstance()->getConnection();
$userQuery = $db->prepare("
    SELECT u.*, d.name AS department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = :id
");
$userQuery->execute(['id' => $_SESSION['user_id']]);
$dbUser = $userQuery->fetch();

$deptName = !empty($dbUser['department_name']) ? $dbUser['department_name'] : 'Tanpa Departemen';
$jobTitle = !empty($dbUser['job_title']) ? $dbUser['job_title'] : 'Staff';
$employeeId = !empty($dbUser['employee_id']) ? $dbUser['employee_id'] : '-';
?>

<div class="space-y-6 max-w-4xl mx-auto py-4 animate-fade-in">
    <!-- Header Page -->
    <div class="bg-gradient-to-r from-primary to-blue-900 text-white rounded-3xl p-6 md:p-8 shadow-lg relative overflow-hidden">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
            <span class="material-symbols-outlined text-[180px]">contact_page</span>
        </div>
        <div class="relative z-10 space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/10 text-white border border-white/20">
                <span class="material-symbols-outlined text-xs">edit_square</span>
                Pengisian Identitas Baru
            </span>
            <h1 class="font-headline text-3xl md:text-4xl font-extrabold tracking-tight">Lengkapi Profil Administratif Anda</h1>
            <p class="text-xs md:text-sm text-blue-100 max-w-2xl leading-relaxed">
                Super Admin telah mereset data administratif terkunci Anda. Harap isi data di bawah ini secara lengkap dan benar sesuai dokumen hukum resmi Anda (KTP, Buku Tabungan, dll.).
            </p>
        </div>
    </div>

    <!-- Warning / Info Box -->
    <div class="bg-amber-50 border border-amber-200 p-5 rounded-2xl flex gap-4 items-start shadow-sm">
        <span class="material-symbols-outlined text-amber-700 bg-amber-100 p-2 rounded-xl text-xl font-bold flex-shrink-0">warning</span>
        <div>
            <h4 class="text-amber-900 font-extrabold text-sm">Peringatan Penyimpanan Permanen</h4>
            <p class="text-amber-800 text-xs mt-1 leading-relaxed">
                Setelah Anda menekan tombol simpan, data ini akan langsung <strong>terkunci secara permanen</strong> pada sistem siCare. Perubahan data di masa mendatang hanya dapat diajukan dengan mengunggah bukti dokumen resmi melalui menu pengajuan perbaikan data.
            </p>
        </div>
    </div>

    <form id="profileFillForm" onsubmit="submitProfileFill(event)" class="space-y-6">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <!-- Section 1: Data Identitas & Kependudukan -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-sm space-y-6">
            <div class="flex items-center gap-3 border-b border-outline-variant/10 pb-3">
                <span class="material-symbols-outlined text-primary text-xl">demography</span>
                <h3 class="text-base font-extrabold text-on-surface">1. Data Kependudukan & KTP</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label for="nama_sesuai_ktp" class="text-xs font-bold text-on-surface-variant ml-1">Nama Lengkap Sesuai KTP <span class="text-red-500">*</span></label>
                    <div class="relative group">
                        <input type="text" name="nama_sesuai_ktp" id="nama_sesuai_ktp" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface uppercase" placeholder="CONTOH: BUDI SANTOSO" required />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">person</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="ktp_nik" class="text-xs font-bold text-on-surface-variant ml-1">NIK (Nomor Induk Kependudukan) <span class="text-red-500">*</span></label>
                    <div class="relative group">
                        <input type="text" name="ktp_nik" id="ktp_nik" pattern="\d{16}" minlength="16" maxlength="16" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono text-on-surface" placeholder="327501XXXXXXXXXX" required />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">badge</span>
                    </div>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="alamat_ktp" class="text-xs font-bold text-on-surface-variant ml-1">Alamat Lengkap Sesuai KTP <span class="text-red-500">*</span></label>
                    <textarea name="alamat_ktp" id="alamat_ktp" rows="3" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface resize-none" placeholder="Masukkan alamat lengkap sesuai dengan kartu KTP Anda..." required></textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Data Pribadi & Kontak -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-sm space-y-6">
            <div class="flex items-center gap-3 border-b border-outline-variant/10 pb-3">
                <span class="material-symbols-outlined text-primary text-xl">account_box</span>
                <h3 class="text-base font-extrabold text-on-surface">2. Data Pribadi & Kontak</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label for="tanggal_lahir" class="text-xs font-bold text-on-surface-variant ml-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                    <div class="relative group">
                        <input type="text" name="tanggal_lahir" id="tanggal_lahir" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface" placeholder="Contoh: 15 Agustus 1993" required />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">calendar_month</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="jenis_kelamin" class="text-xs font-bold text-on-surface-variant ml-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <select name="jenis_kelamin" id="jenis_kelamin" required class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl pl-4 pr-10 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface appearance-none cursor-pointer">
                            <option value="" disabled selected>Pilih Jenis Kelamin...</option>
                            <option value="Laki-Laki">Laki-Laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-3 pointer-events-none text-on-surface-variant/60 text-lg">expand_more</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="status_pernikahan" class="text-xs font-bold text-on-surface-variant ml-1">Status Pernikahan <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <select name="status_pernikahan" id="status_pernikahan" required class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl pl-4 pr-10 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface appearance-none cursor-pointer">
                            <option value="" disabled selected>Pilih Status...</option>
                            <option value="Belum Menikah">Belum Menikah</option>
                            <option value="Menikah">Menikah</option>
                            <option value="Cerai Hidup">Cerai Hidup</option>
                            <option value="Cerai Mati">Cerai Mati</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-3 pointer-events-none text-on-surface-variant/60 text-lg">expand_more</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="no_telepon" class="text-xs font-bold text-on-surface-variant ml-1">Nomor Telepon Seluler <span class="text-red-500">*</span></label>
                    <div class="relative group">
                        <input type="tel" name="no_telepon" id="no_telepon" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface" placeholder="Contoh: +62 812-3456-7890" required />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">phone_iphone</span>
                    </div>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="alamat_domisili" class="text-xs font-bold text-on-surface-variant ml-1">Alamat Domisili Saat Ini <span class="text-red-500">*</span></label>
                    <textarea name="alamat_domisili" id="alamat_domisili" rows="3" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface resize-none" placeholder="Masukkan alamat tempat tinggal Anda saat ini jika berbeda dengan KTP..." required></textarea>
                </div>
            </div>
        </div>

        <!-- Section 3: Data Rekening & Finansial -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-sm space-y-6">
            <div class="flex items-center gap-3 border-b border-outline-variant/10 pb-3">
                <span class="material-symbols-outlined text-primary text-xl">payments</span>
                <h3 class="text-base font-extrabold text-on-surface">3. Data Rekening & Finansial</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label for="bank_name" class="text-xs font-bold text-on-surface-variant ml-1">Nama Bank Penerima <span class="text-red-500">*</span></label>
                    <div class="relative group">
                        <input type="text" name="bank_name" id="bank_name" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-semibold text-on-surface" placeholder="Contoh: Bank Central Asia (BCA)" required />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">account_balance</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="bank_account_number" class="text-xs font-bold text-on-surface-variant ml-1">Nomor Rekening <span class="text-red-500">*</span></label>
                    <div class="relative group">
                        <input type="text" name="bank_account_number" id="bank_account_number" pattern="\d+" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono text-on-surface" placeholder="Masukkan nomor rekening saja..." required />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">credit_card</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Data Pajak & Asuransi (Optional) -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-sm space-y-6">
            <div class="flex items-center gap-3 border-b border-outline-variant/10 pb-3">
                <span class="material-symbols-outlined text-primary text-xl">receipt_long</span>
                <h3 class="text-base font-extrabold text-on-surface">4. Pajak & Jaminan Sosial (Opsional)</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="space-y-2">
                    <label for="npwp_number" class="text-xs font-bold text-on-surface-variant ml-1">Nomor NPWP</label>
                    <div class="relative group">
                        <input type="text" name="npwp_number" id="npwp_number" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono text-on-surface" placeholder="XX.XXX.XXX.X-XXX.XXX" />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">percent</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="bpjs_tk" class="text-xs font-bold text-on-surface-variant ml-1">BPJS Ketenagakerjaan</label>
                    <div class="relative group">
                        <input type="text" name="bpjs_tk" id="bpjs_tk" pattern="\d*" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono text-on-surface" placeholder="Masukkan nomor BPJS TK..." />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">medical_information</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="bpjs_kes" class="text-xs font-bold text-on-surface-variant ml-1">BPJS Kesehatan</label>
                    <div class="relative group">
                        <input type="text" name="bpjs_kes" id="bpjs_kes" pattern="\d*" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono text-on-surface" placeholder="Masukkan nomor BPJS Kesehatan..." />
                        <span class="material-symbols-outlined absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors text-lg">health_and_safety</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="/dashboard" class="px-6 py-3 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl text-sm font-semibold transition-colors">
                Batal
            </a>
            <button type="submit" class="px-8 py-3 bg-primary hover:bg-primary/95 text-white font-headline font-extrabold text-sm rounded-xl shadow-md shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all duration-250 cursor-pointer">
                Simpan & Kunci Identitas
            </button>
        </div>
    </form>
</div>

<script>
function submitProfileFill(e) {
    e.preventDefault();
    
    if (typeof Swal === 'undefined') {
        // Fallback if Swal not loaded
        if (confirm("Apakah Anda yakin ingin menyimpan data ini secara permanen? Data akan dikunci dan hanya dapat diubah melalui menu pengajuan.")) {
            e.target.submit();
        }
        return;
    }

    Swal.fire({
        title: 'Simpan Permanen?',
        text: 'Apakah Anda yakin semua data yang dimasukkan sudah benar? Data ini akan disimpan secara permanen di sistem dan langsung dikunci kembali. Perubahan di kemudian hari hanya dapat diajukan dengan melampirkan berkas bukti resmi.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#000666',
        cancelButtonColor: '#c6c5d4',
        confirmButtonText: 'Ya, Simpan Permanen',
        cancelButtonText: 'Tinjau Ulang'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Memproses enkripsi dan penyimpanan data identitas baru Anda...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            var form = document.getElementById('profileFillForm');
            var formData = new FormData(form);

            fetch('/employee/profile/save-fill', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        title: 'Berhasil Disimpan!',
                        text: data.message || 'Identitas lengkap Anda berhasil disimpan secara permanen.',
                        icon: 'success',
                        confirmButtonColor: '#000666'
                    }).then(function() {
                        window.location.href = '/employee/profile';
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal Menyimpan',
                        text: data.message || 'Terjadi kesalahan sistem saat menyimpan profil.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(function(err) {
                console.error('Save profile fill error:', err);
                Swal.fire({
                    title: 'Kesalahan Sistem',
                    text: 'Gagal menghubungi server. Silakan coba beberapa saat lagi.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}
window.submitProfileFill = submitProfileFill;


// NPWP Auto Masking: 00.000.000.0-000.000
document.getElementById('npwp_number').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, ''); // strip non-digits
    if (value.length > 15) {
        value = value.substring(0, 15);
    }
    
    let formatted = "";
    if (value.length > 0) {
        formatted += value.substring(0, 2);
    }
    if (value.length > 2) {
        formatted += "." + value.substring(2, 5);
    }
    if (value.length > 5) {
        formatted += "." + value.substring(5, 8);
    }
    if (value.length > 8) {
        formatted += "." + value.substring(8, 9);
    }
    if (value.length > 9) {
        formatted += "-" + value.substring(9, 12);
    }
    if (value.length > 12) {
        formatted += "." + value.substring(12, 15);
    }
    
    e.target.value = formatted;
});
</script>
