# Panduan Sistem Manajemen Versi & Changelog siCare

Dokumen ini menjelaskan rancangan tata kelola versi rilis, tata cara kompilasi log pengembangan (*tracker*), dan mekanisme sinkronisasi database untuk platform **siCare**.

---

## 1. Edisi & Arsitektur Fork

Setiap paket rilis membawa 2 atribut jalur (*fork*):

### A. Edisi Aplikasi
| Edisi | Keterangan | Jalur Fork |
|---|---|---|
| **Enterprise** | Edisi bisnis/perusahaan. Tersedia LTS dan STS. | Mono, Multi |
| **Community** | Edisi open-source untuk komunitas. Tersedia LTS dan STS. | Mono (saja) |

### B. Arsitektur Repositori
| Tag | Keterangan |
|---|---|
| **Mono** | Monorepo — seluruh kode dalam satu repositori |
| **Multi** | Multirepo — kode terbagi ke beberapa repositori (hanya Enterprise) |

> **Catatan:** Community Edition hanya tersedia dalam arsitektur Mono.

---

## 2. Jalur Versi Rilis

Sistem ini menggunakan 4 jalur versi:

### A. Versi Stabil (Production)
**Format:** `yy.mm-SUPPORT`
**Contoh:** `26.05-LTS`, `26.11-STS`

| Bulan | Support | Keterangan |
|---|---|---|
| **05** (Mei) | **LTS** (Long Term Support) | Langganan berlangganan, selalu mendapatkan update tahunan |
| **11** (November) | **STS** (Short Term Support) | Pembelian 1x lifetime, blueprint masa mendatang |

- Kedua edisi (Enterprise & Community) memiliki rilis LTS dan STS.
- Setiap versi stabil **wajib memiliki nama alias** (ditentukan developer), seperti penamaan pada Ubuntu/Android.
- **Contoh dengan alias:** `26.05-LTS / Ammonite`
- Saat AI (Antigravity) diperintahkan mencatat ke versi stabil, **wajib menanyakan nama alias** ke developer.

### B. Versi Beta (Public Testing)
**Format:** `X.Y.Z` (SemVer murni, **tanpa** suffix LTS/STS)
**Contoh:** `1.2.0`, `2.0.0`

- Tidak ada pembedaan LTS/STS pada jalur Beta.
- Perhitungan digit otomatis berdasarkan dampak teknis (lihat Section 3).

### C. Versi Pre-Rilis (Continuous Development)
**Format:** `yy.mm.nnnnn`
**Contoh:** `26.06.00009`

- **Hanya berlaku untuk Enterprise Edition.**
- Counter `nnnnn` di-reset setiap bulan **dan** tahun berganti.
- Mencatat setiap update yang ada secara kronologis.

### D. Versi Pra-Production (Environment)
**Format:** `[env]-yy.mm.nnnnn`
**Contoh:** `stg-26.07.00007`

| Prefix | Environment | Keterangan |
|---|---|---|
| `local` | Local Development | Pengembangan lokal developer |
| `tqa` | Test QA | Pengujian Quality Assurance |
| `stg` | Staging | Lingkungan pra-produksi |
| `mtc` | Maintenance | Mode pemeliharaan sistem |

- Format sama dengan Pre-Rilis, ditambah prefix environment.
- **Hanya berlaku untuk Enterprise Edition.**

---

## 3. Aturan Kalkulasi Otomatis SemVer (Jalur Beta)

Saat merangkum 8 tipe pembaruan dari environment bawah ke versi Beta (`X.Y.Z`), sistem Antigravity akan menghitung digit mana yang naik berdasarkan dampak teknis tertinggi:

1. **Naik X (Major):** Jika terdapat pembaruan `Removed` (menghapus fungsi) atau `Changed` (mengubah alur logika utama) yang merusak kompatibilitas sistem/pengguna lama.
2. **Naik Y (Minor):** Jika terdapat pembaruan `Added` (fitur baru), `Deprecated` (persiapan penghapusan), atau `Improved` skala masif yang tidak merusak sistem lama. (Nilai Z di-reset ke 0).
3. **Naik Z (Patch):** Jika hanya berisi `Fixed` (perbaikan bug), `Security` (tambalan keamanan), `Improved` skala kecil, atau `Refactored` (merapikan kode internal).

### 8 Tipe Pembaruan
| Tipe | Keterangan | Dampak SemVer |
|---|---|---|
| `Added` | Fitur baru | Minor (Y) |
| `Improved` | Peningkatan fitur yang sudah ada | Minor (Y) / Patch (Z) |
| `Fixed` | Perbaikan bug | Patch (Z) |
| `Security` | Tambalan keamanan | Patch (Z) |
| `Deprecated` | Persiapan penghapusan fitur | Minor (Y) |
| `Removed` | Penghapusan fitur | Major (X) |
| `Changed` | Perubahan alur logika utama | Major (X) |
| `Refactored` | Merapikan kode internal | Patch (Z) |

---

## 4. Mekanisme Pencatatan & Kompilasi

### A. File Pencatatan
| File | Fungsi | Perilaku |
|---|---|---|
| `tracker.md` | Catatan perubahan aktif (tabel Markdown) | Di-reset setelah kompilasi |
| `tracker.txt` | Log permanen seluruh perubahan dari awal | **Tidak pernah dihapus** (append-only) |
| `tracker_archive.md` | Arsip entri tracker.md setelah kompilasi | Append-only |
| `changelog.json` | Data rilis terstruktur untuk database | Hanya berisi versi yang disepakati |
| `changelog.md` | Dokumen riwayat rilis publik | Prepend versi terbaru |

### B. Perintah CLI Kompiler
```bash
# Pre-Rilis Enterprise (counter otomatis)
php changelog_compiler.php --type PRERELEASE --edition Enterprise --repo mono --yes

# Pra-Production Staging Enterprise
php changelog_compiler.php --type STG --edition Enterprise --repo mono --yes

# Beta (SemVer otomatis)
php changelog_compiler.php --type BETA --edition Enterprise --repo mono --yes

# Stabil LTS Enterprise Mono (wajib --alias)
php changelog_compiler.php --type LTS --edition Enterprise --repo mono --alias "Ammonite" --yes

# Stabil STS Community Mono
php changelog_compiler.php --type STS --edition Community --repo mono --alias "Basalt" --yes
```

### C. Output Kompilasi
Kompiler akan:
1. Membaca entri dari `tracker.md`
2. Mengelompokkan berdasarkan 8 tipe pembaruan
3. Menyimpan ke `changelog.json` dan prepend ke `changelog.md`
4. Append ke `tracker.txt` (permanen) dan `tracker_archive.md`
5. Reset `tracker.md` untuk siklus berikutnya

---

## 5. Mekanisme Pembaruan Sistem & Validasi Fork

### A. Penggunaan Istilah Korporat
* **Live Schema Connection:** Menggantikan istilah "Database Connection" pada halaman riwayat rilis.
* **Versi Skema Terinstal:** Menggantikan istilah "Versi Database Saat Ini".

### B. Pelacakan Tanggal Ganda
* **Tanggal Rilis Versi (`compiled_date`):** Tanggal kompilasi berkas rilis kode.
* **Tanggal Update Aplikasi (`created_at`):** Tanggal ketika skema database dimigrasikan di server.

### C. Deteksi & Peringatan Jalur (Fork Mismatch Warning)
Saat Superadmin menekan tombol "Perbarui Aplikasi Sekarang", sistem memvalidasi kesesuaian jalur. Jika terdeteksi perpindahan jalur, popup Pemberitahuan Kritis (SweetAlert2) akan menghalangi konfirmasi biasa:
1. **Perpindahan Jalur Rilis:** Berubah antara Stabil ↔ Beta / Pre-release.
2. **Perubahan Edisi:** Berubah antara Enterprise ↔ Community.
3. **Perubahan Arsitektur Repo:** Berubah antara Mono ↔ Multi.

### D. Validasi Constraint
| Aturan | Deskripsi |
|---|---|
| Community + Multi | ❌ Ditolak — Community hanya Mono |
| Community + Pre-Rilis | ❌ Ditolak — Pre-Rilis hanya Enterprise |
| Community + Pra-Production | ❌ Ditolak — Pra-Production hanya Enterprise |
| Stabil tanpa Alias | ❌ Ditolak — Versi stabil wajib punya nama alias |