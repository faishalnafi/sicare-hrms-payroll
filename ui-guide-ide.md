# siCare UI/UX Design System & Layout Guidelines

Dokumen ini mendefinisikan standar visual, keselarasan tata letak (layout alignment), kegagalan responsif, dan panduan teknis gaya (*styling guide*) untuk aplikasi **siCare HRMS & Payroll**. Seluruh agen AI dan pengembang wajib mematuhi aturan ini demi menjaga kualitas visual premium yang mutakhir dan bebas bug visual di seluruh halaman.

---

## 1. Aturan Emas Responsivitas & Pencegahan Overflow (Mutlak)

Kesalahan visual paling buruk dalam desain web modern adalah munculnya scroll horizontal pada halaman (*viewport horizontal scroll*) akibat elemen yang terlalu lebar pada layar kecil (mobile).

### 1.1 Larangan Keras Horizontal Scroll Viewport
* **TIDAK BOLEH** ada scrollbar horizontal pada level *window* atau wadah utama halaman di resolusi perangkat apa pun (mulai dari mobile `320px` hingga monitor ultra-wide `2560px`).
* Semua layout utama wajib menggunakan pembatas `max-w-full` dan `overflow-x-hidden` jika diperlukan, namun lebih diutamakan menggunakan struktur kontainer yang elastis dan melipat secara dinamis.

### 1.2 Penanganan & Standardisasi Tabel Data (Responsive & Sorted Tables)
Seluruh tabel wajib dibungkus dengan wadah pelindung *overflow* agar tabel dapat digeser secara lokal tanpa merusak layout luar, serta menggunakan lebar minimum agar kolom tidak terhimpit pada sidebar minimize atau layar kecil.
* **Sintaks & Layout Wajib:**
  ```html
  <div class="overflow-x-auto w-full max-w-full rounded-2xl border border-outline-variant/15">
      <table class="w-full text-left border-collapse table-standardized">
          <thead>
              <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                  <th class="no-col w-12 text-center py-4 px-6 text-[10px] font-extrabold uppercase tracking-wider">No</th>
                  <th onclick="window.sortDomTable(this, 1, 'string')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">
                      <div class="flex items-center gap-1">
                          Nama Kolom
                          <span class="sort-icon-container">
                              <span class="material-symbols-outlined sort-up">arrow_drop_up</span>
                              <span class="material-symbols-outlined sort-down">arrow_drop_down</span>
                          </span>
                      </div>
                  </th>
                  <!-- Kolom lainnya... -->
              </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
              <!-- data row -->
          </tbody>
      </table>
  </div>
  ```
* **Aturan Standardisasi UI Tabel:**
  1. **Lebar Minimum (`min-w-[1100px]`)**: Setiap tabel wajib menggunakan class `.table-standardized` (atau `min-w-[1100px]`) agar kolom tidak memipih/squish dan memicu scrollbar horizontal lokal jika ruang tidak mencukupi.
  2. **Kolom Urutan (`No`)**: Kolom pertama paling kiri wajib berupa kolom nomor urut (`No`) dengan class `.no-col` di `<th>` dan `.no-col-cell` di `<td>`.
  3. **Ikon & Filter Sortir**: Seluruh kolom header (kecuali kolom urutan `No`) wajib dilengkapi ikon panah atas dan bawah (`arrow_drop_up` dan `arrow_drop_down`) di samping teks header.
  4. **Urutan Default & Arah Sortir**: Data secara default harus terurut secara alfabetis (`ASC`) berdasarkan kolom nama (atau kolom tanggal/periode untuk data kronologis), dan mengklik header akan men-toggle pengurutan antara `ASC` dan `DESC`.
  5. **Standardisasi Otomatis**: Framework standardisasi tabel global berjalan di `app.php` secara otomatis menyuntikkan kelas `.table-standardized`, membungkus kontainer responsif, menambahkan kolom urutan `No` dan menyisipkan ikon sortir jika belum dideklarasikan secara manual di HTML.
  6. **Pencegahan Row Gemuk Vertikal (Single-line Cells & Actions)**: Seluruh baris tabel wajib dijaga agar tetap tipis secara vertikal. Teks sel dan tombol aksi pada kolom Aksi/Aksi Kontrol **TIDAK BOLEH** melipat/wrap ke baris baru (wajib menggunakan `white-space: nowrap !important` dan `flex-wrap: nowrap !important` agar tombol aksi tetap berada dalam 1 baris lurus). Jika ruang horizontal tidak memadai, pengguna akan menggeser ke samping menggunakan scrollbar horizontal lokal.
* Tambahkan `whitespace-nowrap` pada kolom-kolom tabel yang berisi teks pendek atau kontrol aksi agar isi kolom tidak pecah/turun ke bawah secara jelek saat dipersempit.

### 1.3 Penggunaan Ukuran Piksel Keras (Hardcoded Widths)
* **DILARANG** menggunakan lebar absolut berbasis piksel seperti `width: 500px;` atau kelas Tailwind `w-[400px]` pada kontainer utama.
* **SOLUSI:** Gunakan pendekatan fluid-responsive dengan gabungan lebar persentase dan batas maksimum:
  * **Benar:** `w-full max-w-[400px]` atau `w-full md:w-1/2 lg:w-1/3`
  * **Salah:** `w-[400px]` (ini akan pecah di layar ponsel berukuran `320px` - `375px`)

---

## 2. Palet Warna & Desain Premium

siCare menggunakan kombinasi gaya **Deep Premium Navy/Indigo** dengan **Glassmorphism modern** dan aksen **Amber/Gold** untuk memancarkan aura profesional, mutakhir, dan tepercaya.

### 2.1 Skema Warna Master
* **Background Utama (Sidebar & Hero):** Gradient linear malam yang elegan:
  `linear-gradient(135deg, #000666 0%, #1a237e 50%, #0d47a1 100%)`
* **Warna Aksen Utama (Action):** Amber/Oranye Hangat untuk memicu ketertarikan visual:
  * Primer: `#ff6f00` (Amber 900)
  * Hover/Terang: `#ffa726` (Amber 400)
* **Status Badges & Alur Persetujuan:**
  * **Approved/Success:** Hijau Emerald (`#00c853` / `bg-emerald-50 text-emerald-700`)
  * **Pending/Review:** Kuning Amber (`#ff6f00` / `bg-amber-50 text-amber-800 border-amber-200`)
  * **Rejected/Danger:** Merah Crimson (`#ba1a1a` / `bg-red-50 text-red-700 border-red-200`)

### 2.2 Efek Glassmorphic Modern
Gunakan efek kaca transparan (*glassmorphism*) untuk kontainer di atas latar belakang malam agar memantulkan visual yang premium:
```css
background: rgba(255, 255, 255, 0.1);
backdrop-filter: blur(8px);
-webkit-backdrop-filter: blur(8px);
border: 1px solid rgba(255, 255, 255, 0.15);
```

---

## 3. Tipografi & Penyelarasan Piksel (Pixel-Perfect Alignment)

### 3.1 Font Utama
* Wajib menggunakan Google Fonts **Outfit** atau **Inter** dengan pemuatan fallback sistem (`'Outfit', 'Inter', 'Manrope', sans-serif`).
* Untuk angka jam digital/moneter, wajib menggunakan font bergaya Monospace atau Outfit dengan bobot tebal (`font-mono` atau `font-black`) agar karakter tidak bergeser saat nilai berubah.

### 3.2 Penyelarasan Vertikal & Horizontal Komponen
* Pastikan elemen bertumpuk vertikal memiliki jarak yang konsisten (gunakan utilitas gap seperti `gap-4` atau `space-y-4`).
* Kartu yang bersebelahan harus memiliki tinggi yang setara. Gunakan `flex flex-col h-full` atau layout grid `grid items-stretch` agar tidak ada kartu yang lebih pendek di sebelah kartu yang panjang.
* Tombol aksi yang sejajar (seperti tombol `Clock-In` dan `Clock-Out`) wajib diatur dengan kelas `flex-1` di dalam flex container agar **lebar kedua tombol sama persis (simetris)** di resolusi apa pun.

---

## 4. Efek Interaksi & UX Polish (Subtle Micro-Animations)

Setiap elemen interaktif wajib merespons hover pengguna dengan perubahan yang halus (tidak kaku):
* **Transisi Halus:** Tambahkan `transition: all 0.2s ease` atau `transition-all duration-200` pada seluruh link, tombol, dan baris tabel.
* **Hover Translate:** Naikkan sedikit elemen ke atas saat di-hover untuk memberi kesan melayang yang premium:
  `hover:-translate-y-0.5 hover:shadow-md`
* **Indikator Berdenyut (Pulse Ring):** Untuk elemen dinamis / *live* (seperti lampu status kehadiran aktif), gunakan animasi denyut lambat:
  ```css
  @keyframes attPulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(0, 200, 83, 0.4); }
      50% { box-shadow: 0 0 0 12px rgba(0, 200, 83, 0); }
  }
  ```

---

## 5. Kepatuhan Privasi Data (UU PDP) & Riwayat Pengajuan
* Data sensitif seperti alasan penolakan perbaikan data atau berkas pribadi wajib disimpan di database selama **minimal 30 hari** sebelum dibersihkan.
* Tampilkan alasan penolakan secara transparan dan lengkap di UI tanpa pemotongan teks (*no ellipsis/truncation*), berikan ruang menyamping atau ke bawah yang cukup agar mudah dibaca oleh karyawan bersangkutan.
* Selalu pertahankan kotak status pengajuan (badge `Pending`, `Disetujui`, atau `Ditolak`) agar karyawan dapat memantau dengan jelas status pengajuan mereka.
