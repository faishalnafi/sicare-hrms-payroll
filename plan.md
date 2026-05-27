# Rencana Implementasi: Presensi NFC/RFID & QR Code

Fitur presensi saat ini murni menggunakan pelacakan lokasi (GPS/WIFI). Sesuai dengan permintaan Anda, kita akan mengintegrasikan sistem keamanan fisik di mana karyawan yang telah diberikan akses fisik (kartu NFC/RFID atau QR Code) wajib menggunakan metode tersebut, sembari sistem tetap memvalidasi dan merekam lokasi GPS mereka.

## Proposed Changes

### Frontend: `resources/views/pages/employee/attendance.php`

#### [MODIFY] attendance.php
- **Data Fetching:** Memperbarui kueri awal PHP untuk turut menarik data kolom `akses` (boolean) dari tabel `users` untuk karyawan yang sedang *login*.
- **Logika UI Clock-In/Out:**
  - Jika `akses == false`: Alur berjalan seperti biasa, tombol Clock-In/Clock-Out mensyaratkan GPS lalu langsung mengirim POST ke server.
  - Jika `akses == true`: Tombol Clock-In/Clock-Out akan memvalidasi GPS terlebih dahulu, kemudian jika valid, membuka **Modal Pemilihan Metode**.
- **Komponen Modal Scan:**
  - Modal akan menampilkan dua pilihan: **QR Code** atau **NFC/RFID Card**.
  - Jika pengguna memilih **QR Code**: Tampilkan *interface* pemindai kamera (menggunakan library JS seperti html5-qrcode) dengan opsi pendukung untuk mengunggah gambar QR.
  - Jika pengguna memilih **NFC/RFID**: Tampilkan animasi menarik "Silakan Tap Kartu Anda". Fokuskan kursor pada *input field* transparan/tersembunyi. Saat alat pemindai fisik membaca kartu (diikuti otomatis dengan *event Enter*), *script* otomatis mendeteksi, menutup modal, dan melanjutkan pengiriman data (token + GPS).
- **Validasi GPS Mutlak:** Presensi tidak dapat diproses (baik Clock-In maupun Clock-Out) apabila fitur lokasi dinonaktifkan atau GPS gagal dideteksi, menjaga konsistensi dengan logika sistem saat ini.

### Backend: `app/Controllers/AttendanceController.php`

#### [MODIFY] AttendanceController.php
- **Validasi `clockIn()` dan `clockOut()`:**
  - Menarik nilai `akses`, `id_kartu`, dan `id_qrcode` milik karyawan dari tabel `users`.
  - Jika `akses` pada tabel bernilai `TRUE`:
    - Mengambil parameter `$_POST['scanned_token']` yang dikirim dari *frontend*.
    - Mengecek apakah `scanned_token` **sama dengan** nilai `id_kartu` ATAU `id_qrcode` milik karyawan bersangkutan.
    - Jika cocok, sistem lanjut mencatat lokasi GPS dan mencatat presensi (WFO/WFA/WFH).
    - Jika tidak cocok (atau token kosong), *request* akan ditolak dengan respons JSON (contoh: "Autentikasi gagal. Kartu/QR tidak dikenali atau bukan milik Anda").
  - Jika `akses` bernilai `FALSE`:
    - Abaikan pengecekan token, biarkan sistem memproses presensi hanya bermodalkan deteksi GPS seperti perilaku saat ini.

## Verification Plan

### Manual Verification
1. Membuat/Mengatur *user* percobaan:
   - *User* A: `akses = false` -> Tes Clock-In (Harus berhasil dengan GPS biasa).
   - *User* B: `akses = true`, `id_kartu = '123456789'` -> Tes Clock-In tanpa scan (Akan ditolak UI/Sistem). Tes Clock-In dengan memasukkan *token* '123456789' di Modal (Harus berhasil dan mencatat GPS).
2. Mengecek apakah rekam lokasi GPS dan parameter terkait (`clock_in_latitude`, `clock_in_longitude`) tetap masuk ke `employee_attendance` terlepas dari status *scanning* kartu.
