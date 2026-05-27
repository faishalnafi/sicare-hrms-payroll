# siCare HRMS & Payroll - Keamanan Data & Proteksi Sistem (Strict Security Policy)

Dokumen ini berisi kebijakan keamanan mutlak, aturan baku pengembangan, dan arsitektur pengamanan data yang **WAJIB** dipatuhi secara ketat oleh seluruh tim pengembang dan agen AI guna mencegah kebocoran data (*data breach*), manipulasi sistem, bypass otorisasi, dan pembajakan kode aplikasi **siCare HRMS & Payroll**.

---

## 1. Perlindungan Data Sensitif & Integritas Autentikasi

### 1.1 Identitas Ganda Terisolasi (Dual Identity Isolation)
* **Acuannya adalah UUID v4 (`CHAR(36)`)**: Seluruh Primary Key (PK) dan Foreign Key (FK) relasional dalam database **WAJIB** menggunakan UUID v4.
* **DILARANG KERAS** menggunakan `AUTO_INCREMENT` bertipe integer numerik pada API publik maupun query URL untuk mencegah eksploitasi enumerasi IDOR (*Insecure Direct Object Reference*).
* **Employee ID (Staff ID / NIK)**: Digunakan murni sebagai penanda visual *human-readable* operasional. Proses otentikasi internal memetakan `employee_id` ke UUID di backend yang terenkripsi.

### 1.2 Enkripsi Data Sensitif Karyawan (Encryption at Rest & Transit)
* **Kategori Data Sangat Rahasia**: NIK KTP, NPWP, Nomor Rekening Bank, Slip Gaji, dan berkas personal wajib dienkripsi di tingkat database menggunakan algoritma enkripsi industri standar (misal: AES-256-GCM).
* **Data Penggajian Terisolasi (Confidential Payroll Track)**: Gaji dan tunjangan tidak boleh diakses oleh `hr_ops` biasa. Dekripsi data ini hanya diizinkan untuk level peran otorisasi khusus (`executive` / Komite Audit / Payroll Manager) melalui kunci dekripsi enkripsi dinamis (*cryptographic key separation*).

---

## 2. Aturan Ketat Unggah Berkas & Proteksi Shell (Anti-Shell Upload)

Kebocoran atau pengeksekusian script shell berbahaya pada server melalui fitur upload adalah ancaman kategori kritikal. Kebijakan berikut bersifat **mutlak**:

### 2.1 Pembatasan Ukuran & Validasi Sisi Server (Strict 10MB Limit)
* **Ukuran Maksimum**: Batas ukuran file maksimal **10MB** untuk seluruh dokumen (CV, KTP, KK, NPWP, Buku Tabungan, Bukti Klaim, Excel). Evaluasi ini harus dilakukan pada baris kode pertama sebelum file diproses lebih jauh.
* **Deteksi Tipe MIME Sebenarnya**: **DILARANG PERCAYA** pada tipe MIME dari client (`$_FILES['file']['type']`) atau ekstensi nama file (misal `.pdf`). Server **WAJIB** membaca *binary signature/magic numbers* berkas secara langsung menggunakan modul PHP `finfo`:
  ```php
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $_FILES['file']['tmp_name']);
  finfo_close($finfo);
  
  $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
  if (!in_array($mimeType, $allowedTypes)) {
      throw new SecurityException("MIME type tidak sah!");
  }
  ```

### 2.2 Isolasi Berkas Privat (Private Storage Directory)
* **DILARANG** meletakkan berkas unggahan di direktori publik (seperti `/public/uploads/`).
* **Penyimpanan Privat**: Simpan file di dalam direktori non-publik (misalnya `/storage/private/documents/`).
* **Otorisasi Unduh (Served via Controller)**: Berkas diakses oleh pengguna melalui URL dinamis (controller) yang memvalidasi otentikasi sesi aktif dan hak akses (RBAC) sebelum memanggil *stream* file dengan fungsi pengiriman aman:
  ```php
  // Logika validasi RBAC
  if ($user->cannot('view', $document)) {
      abort(403, 'Akses Ditolak');
  }
  return response()->file($securePath);
  ```

---

## 3. Pencegahan Injeksi & Serangan Aplikasi Web (OWASP Top 10)

### 3.1 Anti SQL Injection (Prepared Statements Only)
* Seluruh kueri ke database **WAJIB** menggunakan *Prepared Statements* dengan parameterized binding. 
* **TIDAK BOLEH** ada interpolasi variabel langsung di dalam string query (contoh terlarang: `"SELECT * FROM users WHERE id = '$id'"`).

### 3.2 Proteksi Pemalsuan Permintaan Lintas Situs (CSRF Protection)
* Seluruh formulir bermethod `POST`, `PUT`, `PATCH`, dan `DELETE` wajib memiliki token CSRF aktif.
* Setiap endpoint API non-publik wajib divalidasi dengan token CSRF atau mekanisme otentikasi Bearer Token/API-Key yang aman.

### 3.3 Anti Cross-Site Scripting (XSS Sanitization)
* Data dari kandidat/pelamar eksternal diklasifikasikan sebagai data belum tepercaya (*untrusted input*).
* Semua data yang akan ditampilkan ke antarmuka HTML wajib melewati fungsi *escape* HTML (seperti `htmlspecialchars()` atau fungsi pembungkus enkapsulasi template engine dengan filter strict).

---

## 4. Keamanan Akses API & Integrasi Pihak Ketiga

Setiap endpoint API eksternal (`/api/v1/`) yang terhubung ke mesin absensi biometrik atau sistem penggajian luar wajib dilindungi dengan:
1. **X-API-Key Protection**: Token rahasia panjang dengan mekanisme rotasi berkala.
2. **IP Whitelisting**: IP address server pengirim wajib didaftarkan secara manual pada konfigurasi internal (hanya request dari IP tepercaya yang diproses).
3. **Rate Limiting**: Batasi maksimal request per menit dari setiap API-Key untuk mencegah serangan DDoS (*Distributed Denial of Service*).

---

## 5. Audit Log yang Kekal (Immutable Audit Trail)

Semua aksi mutasi data penting (Insert, Update, Delete, Perubahan Hak Akses, Persetujuan Finansial) wajib dicatat pada tabel `audit_logs`.
* **Proteksi Audit Superadmin**: Ketika `superadmin` melakukan pembersihan log untuk perawatan performa server, sistem secara programatik wajib otomatis mengunci aksi dan menyisakan satu log kesaksian tidak terhapus: *"Superadmin [Nama] telah menghapus seluruh log sistem pada [Timestamp]"*.
* **Executive Monitoring**: Rekaman audit log ini dapat dipantau langsung secara real-time oleh peran `executive` (Komite Audit/C-Level) tanpa hak manipulasi log.
