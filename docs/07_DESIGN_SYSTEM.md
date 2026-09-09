# Design System - KPI Management Co-Packing

## 1. Design Direction

Target visual:

- Clean.
- Profesional.
- Ringan.
- Tidak ramai.
- Tidak menggunakan gradient dekoratif berlebihan.
- Tidak menggunakan border tebal.
- Tidak menggunakan shadow besar/blur berlebihan.
- Tidak menggunakan card di dalam card tanpa kebutuhan.
- Tidak menggunakan terlalu banyak badge, icon, atau warna.
- Fokus pada keterbacaan data dan kecepatan kerja user.

Primary color: **`#2547F9`**.

## 2. Color Palette

| Token | Warna | Penggunaan |
|---|---|---|
| Primary 600 | `#2547F9` | tombol utama, link aktif, focus |
| Primary 700 | `#1D37D8` | hover tombol utama |
| Primary 100 | `#DCE5FF` | soft selected state |
| Primary 50 | `#F3F6FF` | background soft |
| Text Primary | `#111827` | title, body utama |
| Text Secondary | `#6B7280` | description/helper |
| Border | `#E5E7EB` | border card/input |
| Background | `#F8FAFC` | page background |
| Surface | `#FFFFFF` | card/table/form |
| Success | `#16A34A` | sukses/aktif |
| Warning | `#D97706` | peringatan |
| Danger | `#DC2626` | error/delete/required star |
| Info | `#0284C7` | informasi |

## 3. Typography

Gunakan font sans-serif yang mudah dibaca seperti `Inter` atau font sistem.

- Page Title: 24px, semibold.
- Section Title: 18px, semibold.
- Card Title: 16px, semibold.
- Body: 14px.
- Small/helper: 12-13px.
- Table text: 13-14px.

Hindari terlalu banyak ukuran font berbeda.

## 4. Radius, Border, Shadow

### Card

```text
Border: 1px solid #E5E7EB
Radius: 10-12px
Shadow: sangat ringan, maksimal setara shadow-sm
Padding desktop: 20-24px
Padding mobile/tablet: 16px
```

### Input

```text
Height: 42-44px
Border: 1px solid #D1D5DB
Radius: 8px
Focus: border/focus ring primary #2547F9
```

### Button

```text
Height: 40-42px
Radius: 8px
Padding horizontal: 14-16px
```

Hindari radius 20-30px pada semua elemen karena membuat UI terlihat terlalu "template/AI".

## 5. Layout Global

### Sidebar

- Logo aplikasi/client di atas.
- Menu dikelompokkan berdasarkan fungsi.
- Active menu memakai background `Primary 50` dan text `Primary 600`.
- Jangan semua menu diberi icon berwarna.

### Topbar

- Client Switcher untuk user multi-client.
- User profile menu.
- Optional notification bila memang ada kebutuhan.

### Main Content

Page background `#F8FAFC`.

Urutan wajib setiap page:

1. Breadcrumb.
2. Title.
3. Description.
4. Action button bila ada.
5. Card content.

Contoh:

```text
Master Data / Karyawan

Data Karyawan                                  [+ Tambah Karyawan]
Kelola data karyawan yang terlibat dalam proses co-packing.

┌───────────────────────────────────────────────────────────────┐
│ Filter / Search / DataTable                                  │
└───────────────────────────────────────────────────────────────┘
```

## 6. Standard Breadcrumb, Title, Description

### Dashboard

- Breadcrumb: `Dashboard`
- Title: `Ringkasan Performa`
- Description: `Pantau hasil pekerjaan dan performa co-packing dari client yang sedang aktif.`

### Client

- Breadcrumb: `Pengaturan / Client`
- Title: `Data Client`
- Description: `Kelola client yang menggunakan aplikasi KPI Management Co-Packing.`

### Karyawan

- Breadcrumb: `Master Data / Karyawan`
- Title: `Data Karyawan`
- Description: `Kelola data karyawan yang terlibat dalam proses co-packing.`

### Produk

- Breadcrumb: `Master Data / Produk`
- Title: `Data Produk`
- Description: `Kelola SKU, harga, satuan, dan estimasi output produk.`

### Group

- Breadcrumb: `Master Data / Group`
- Title: `Data Group`
- Description: `Kelola pengelompokan karyawan dan produk.`

### Satuan

- Breadcrumb: `Master Data / Satuan`
- Title: `Data Satuan`
- Description: `Kelola satuan yang digunakan pada produk dan realisasi pekerjaan.`

### Cost Center

- Breadcrumb: `Master Data / Cost Center`
- Title: `Data Cost Center`
- Description: `Kelola cost center yang digunakan untuk pengelompokan produk.`

### Shift

- Breadcrumb: `Master Data / Shift`
- Title: `Data Shift`
- Description: `Kelola jadwal shift yang digunakan dalam pencatatan realisasi.`

### Batch

- Breadcrumb: `Master Data / Batch`
- Title: `Data Batch`
- Description: `Kelola nomor batch yang digunakan pada proses co-packing.`

### Realisasi Pekerjaan

- Breadcrumb: `Operasional / Realisasi Pekerjaan`
- Title: `Realisasi Pekerjaan`
- Description: `Catat hasil pekerjaan, produk, shift, dan karyawan yang terlibat.`

### Potongan Gaji

- Breadcrumb: `Penggajian / Potongan Gaji`
- Title: `Potongan Gaji`
- Description: `Kelola potongan dan koreksi gaji karyawan berdasarkan periode.`

### Laporan Gaji

- Breadcrumb: `Laporan / Gaji`
- Title: `Laporan Gaji`
- Description: `Lihat rincian gaji kotor, potongan, koreksi, dan gaji bersih karyawan.`

### Laporan Hasil Pekerjaan

- Breadcrumb: `Laporan / Hasil Pekerjaan`
- Title: `Laporan Hasil Pekerjaan`
- Description: `Pantau hasil pekerjaan co-packing berdasarkan periode, produk, dan karyawan.`

### User

- Breadcrumb: `Pengaturan / User & Hak Akses`
- Title: `User & Hak Akses`
- Description: `Kelola akun, client, role, dan hak akses pengguna.`

### Audit Log

- Breadcrumb: `Pengaturan / Audit Log`
- Title: `Audit Log`
- Description: `Lihat riwayat aktivitas dan perubahan data penting di dalam sistem.`

## 7. Card Standard

Gunakan satu main card untuk list/form.

**Header card hanya digunakan bila perlu.** Jangan mengulang title halaman sebagai title card bila tidak memberi informasi baru.

Contoh Tailwind:

```html
<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <!-- content -->
</div>
```

Jangan menggunakan kombinasi border gelap + shadow besar.

## 8. Button

### Primary

- Background `#2547F9`.
- Text putih.
- Hover sedikit lebih gelap.
- Contoh: `Tambah Karyawan`, `Simpan`, `Terapkan Filter`.

### Secondary

- Background putih.
- Border tipis.
- Text slate.
- Contoh: `Batal`, `Reset Filter`.

### Danger

- Gunakan hanya untuk aksi destruktif.
- Contoh: `Hapus`, `Batalkan Realisasi`.

### Icon

Icon dipakai bila membantu scan visual, tetapi tombol utama sebaiknya tetap memiliki teks.

## 9. Form Standard

### Label Required

```text
Nama Lengkap *
```

`*` wajib merah (`#DC2626`).

### Placeholder

Semua input harus memiliki placeholder yang menjelaskan apa yang harus diisi, bukan sekadar mengulang label.

Bagus:

```text
Label: Nama Lengkap *
Placeholder: Masukkan nama lengkap karyawan
```

Kurang baik:

```text
Placeholder: Nama Lengkap
```

### Validation

Pesan Bahasa Indonesia:

- `Nama lengkap wajib diisi.`
- `Format email tidak valid.`
- `Nomor telepon wajib diisi.`
- `Produk wajib dipilih.`
- `Total hasil pekerjaan harus lebih dari 0.`

Error tampil tepat di bawah field.

## 10. Select2 Standard

Gunakan Select2 untuk field relasi/pencarian.

- Height sejajar input 42-44px.
- Search text jelas.
- Placeholder selalu ada.
- Dataset besar menggunakan AJAX server-side.
- Minimum input 1-2 karakter untuk karyawan/produk besar.
- Response maksimal 20-30 item per request.

Contoh placeholder:

- `Pilih group`
- `Cari NIK atau nama karyawan`
- `Cari SKU atau nama produk`
- `Pilih shift`

## 11. Datepicker Standard

- Format tampilan konsisten, contoh `dd/mm/yyyy`.
- Nilai backend tetap dikonversi ke format database `YYYY-MM-DD`.
- Placeholder: `Pilih tanggal` atau `Pilih rentang tanggal`.
- Range date dipakai pada laporan.
- Jangan gunakan browser date field berbeda-beda bila konsistensi UI menjadi prioritas.

## 12. DataTable Server-Side Standard

### Header Area

Kiri:

- Search.
- Filter utama.

Kanan:

- Export.
- Tambah Data.

### Kolom

- Jangan terlalu banyak kolom sekaligus.
- Kolom sekunder dapat dipindahkan ke detail drawer/modal bila terlalu lebar.
- Action berada di kanan.
- Status memakai badge sederhana.

### State

Loading:

`Memuat data...`

Empty:

`Belum ada data untuk ditampilkan.`

Search empty:

`Data yang Anda cari tidak ditemukan.`

### Server-Side

- Search dengan debounce 300-500ms.
- Sorting dibatasi ke kolom yang aman dan relevan.
- Filter dikirim sebagai query parameter.
- Jangan render ribuan row di browser.

## 13. SweetAlert Standard

Gunakan untuk:

- Hapus.
- Finalisasi realisasi.
- Lock/unlock periode.
- Bulk update potongan.
- Aksi sensitif lain.

Contoh finalisasi:

**Judul:** `Finalisasi realisasi?`  
**Deskripsi:** `Data yang sudah difinalisasi tidak dapat diubah tanpa proses koreksi.`  
**Primary:** `Ya, finalisasi`  
**Secondary:** `Batal`

Jangan gunakan alert untuk setiap save biasa bila tidak diperlukan.

## 14. File Upload Modern + Preview

Komponen wajib tersedia untuk field file seperti logo client atau file lain di masa depan.

### Behavior

- Area drag & drop ringan.
- Tombol `Pilih File`.
- Tampilkan nama, ukuran, dan preview.
- Image preview langsung.
- Tombol ganti/hapus file sebelum submit.
- Validation tipe dan ukuran.
- State upload/loading bila asynchronous.

### Visual

- Border dashed tipis.
- Background putih/primary 50 saat drag over.
- Tidak menggunakan ilustrasi besar.

Contoh copy:

```text
Unggah Logo Client
Seret file ke area ini atau pilih file dari perangkat.
PNG, JPG, atau WEBP. Maksimal 2 MB.
```

## 15. Table Action Pattern

Untuk 1-2 action:

```text
Lihat | Ubah
```

Untuk banyak action gunakan dropdown:

```text
•••
- Lihat Detail
- Ubah
- Finalisasi
- Hapus
```

Aksi destruktif dipisahkan secara visual dari action biasa.

## 16. Filter Pattern

Gunakan area filter di atas tabel dalam card yang sama, tidak perlu card baru.

Contoh:

```text
[Rentang Tanggal] [Shift] [Produk] [Status] [Terapkan] [Reset]
```

Pada layar sempit filter boleh wrap ke baris berikutnya.

## 17. Responsive

Prioritas:

1. Desktop 1366px+.
2. Laptop 1280px.
3. Tablet landscape.

Tabel besar dapat scroll horizontal, tetapi filter/action utama tetap mudah dijangkau.

## 18. Anti "AI Slop" Checklist

- Jangan pakai gradient pada card utama.
- Jangan pakai icon besar pada setiap card statistik.
- Jangan pakai border radius berlebihan.
- Jangan gunakan 3-4 lapis card nested.
- Jangan pakai shadow tebal.
- Jangan pakai glow.
- Jangan gunakan terlalu banyak warna status.
- Jangan terlalu banyak teks marketing pada aplikasi internal.
- Gunakan whitespace yang cukup, bukan elemen dekoratif.
- Gunakan bahasa operasional yang langsung dipahami user.

## 19. Keamanan UI dan Aksesibilitas

Standar visual sebelumnya tetap berlaku. Keamanan tidak boleh membuat UI ramai, menambah dekorasi berlebihan, atau mengandalkan warna sebagai satu-satunya penanda.

- Setiap halaman baru tetap memiliki breadcrumb, title, description Bahasa Indonesia, dan satu main card dengan border/shadow tipis. Form memiliki placeholder, required `*` merah, helper/error text, serta loading/disabled state.
- Pesan login/reset tidak membocorkan apakah akun terdaftar. Contoh: `Username atau password tidak sesuai.` atau `Jika akun terdaftar, petunjuk akan dikirimkan.`
- Halaman akses ditolak menggunakan pesan: `Anda tidak memiliki akses untuk melihat halaman ini.` Jangan menampilkan detail client/resource yang tidak boleh diketahui user.
- Client Switcher hanya menampilkan client yang berhak; nama client aktif terlihat jelas saat mengisi realisasi, potongan, finalisasi, dan export. Perpindahan client membatalkan pilihan/filter/form stale yang berisiko.
- Kontrol permission harus ditegakkan server-side. Menu/tombol yang disembunyikan bukan mekanisme keamanan.
- Nominal gaji, NIK, dan data pribadi ditampilkan secukupnya sesuai permission; gunakan masking bila relevan. Jangan menampilkan data sensitif pada toast, URL, atau console.
- Semua DataTables, Select2, dan SweetAlert menggunakan text rendering/escaping untuk data user. Raw HTML hanya untuk komponen yang tepercaya dan telah direview. Jangan gunakan `html` SweetAlert untuk pesan dari input user.
- File upload modern tetap memiliki preview; file yang gagal validasi tidak boleh dipublikasikan. Untuk dokumen privat, preview dan download memerlukan otorisasi. Tampilkan error tipe/ukuran yang mudah dipahami.
- SweetAlert konfirmasi aksi kritis mencantumkan tindakan, client aktif, dampak, dan tombol yang jelas. Konfirmasi bukan pengganti otorisasi server maupun transaksi atomik.
- Form security settings, user access, dan audit log wajib memakai komponen yang sama; jangan membuat desain khusus yang berbeda tanpa alasan UX.

### Contoh copy keamanan

| Kondisi | Teks |
|---|---|
| Session berakhir | `Sesi Anda telah berakhir. Silakan masuk kembali.` |
| Tidak memiliki izin | `Anda tidak memiliki akses untuk melakukan tindakan ini.` |
| Client tidak tersedia | `Client yang dipilih tidak tersedia. Silakan pilih client lain yang dapat Anda akses.` |
| Data telah berubah | `Data telah diperbarui oleh pengguna lain. Muat ulang untuk melihat perubahan terbaru.` |
| Periode terkunci | `Periode ini sudah dikunci. Hubungi admin yang berwenang jika diperlukan koreksi.` |
| Upload ditolak | `File tidak dapat diunggah. Periksa jenis dan ukuran file, lalu coba lagi.` |
| Export diproses | `Laporan sedang disiapkan. Anda dapat mengunduhnya setelah proses selesai.` |
