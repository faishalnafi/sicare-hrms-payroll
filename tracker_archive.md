# siCare Development Tracker Archive

This file archives past compiled/published raw logs.

| Date | Type | Description | Original Code/Behavior | Stage | Developer |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 2026-06-11 | Added | Menu Presensi GPS tracking terintegrasi untuk karyawan WFH/WFO | Tidak ada validasi GPS sebelumnya | env | Alex Rivera |
| 2026-06-10 | Fixed | Validasi double clock-in agar tidak menduplikasi baris absensi | Database sempat melemparkan error duplikasi | dev | Budi Santoso |
| 2026-06-09 | Improved | Mempercepat query perhitungan rekap absensi bulanan untuk payroll | Query lambat saat memproses 1000+ baris | tqa | Budi Santoso |
| 2026-06-08 | Added | Integrasi library SweetAlert2 untuk dialog konfirmasi tindakan | Menggunakan window.confirm browser bawaan | env | Amanda Putri |
| 2026-06-07 | Security | Penggunaan finfo_open() pada backend upload berkas klaim | Mempercayai ekstensi berkas mentah dari client | tqa | Rian Hidayat |
| 2026-06-06 | Fixed | Memperbaiki overflow scroll horizontal pada tabel master data di mobile | Tabel terpotong pada lebar layar < 320px | dev | Amanda Putri |
| 2026-06-05 | Added | Fitur self-reflection kuartalan (Q2) untuk pelacakan performa karyawan | Formulir penilaian manual di Excel | env | Alex Rivera |
| 2026-06-04 | Security | Audit log otomatis untuk mencatat semua koreksi data sensitif oleh HR | Perubahan data langsung terupdate tanpa riwayat | tqa | Rian Hidayat |
| 2026-06-03 | Added | Sistem session handler hybrid yang menyimpan state di Redis/DB | Session disimpan di file lokal tunggal | dev | Budi Santoso |
| 2026-06-12 | Added | Fitur ekspor slip gaji ke format PDF terenkripsi kata sandi (tanggal lahir) | Slip gaji diunduh tanpa password PDF | env | Alex Rivera |
| 2026-06-12 | Fixed | Bug pembulatan pecahan rupiah pada penghitungan tunjangan lembur | Terjadi selisih pembulatan desimal kecil | dev | Budi Santoso |
| 2026-06-12 | Improved | Optimasi loading rekap kehadiran karyawan pada dashboard supervisor | Loading lambat saat data di atas 500 baris | tqa | Amanda Putri |
| 2026-06-12 | Security | Validasi payload token JWT OAuth2 untuk integrasi Google Login | Memverifikasi token tanpa validasi payload penuh | dev | Rian Hidayat |
