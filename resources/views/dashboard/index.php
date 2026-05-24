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
