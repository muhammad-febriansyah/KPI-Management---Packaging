# Product Requirements Document (PRD)
## KPI Management Co-Packing - Multi Client

## 1. Informasi Produk

**Nama sementara:** KPI Management Co-Packing  
**Platform:** Web Application  
**Backend:** Laravel 13  
**Frontend:** Blade + Tailwind CSS  
**Database:** MySQL/MariaDB  
**Mode:** Multi-client / multi-tenant shared database  
**Primary Color:** `#2547F9`

## 2. Latar Belakang

Aplikasi dibangun untuk menyediakan pencatatan dan pengelolaan performa co-packing yang lebih aman, terpusat, mudah dipelihara, dan berada di bawah kendali perusahaan. Sistem baru harus mengurangi ketergantungan kepada aplikasi/pihak eksternal dan mendukung kebutuhan monitoring, reporting, keamanan data, serta pengembangan jangka panjang.

## 3. Tujuan Produk

1. Memusatkan pencatatan karyawan, produk, realisasi pekerjaan, potongan, dan laporan.
2. Menyediakan data performa co-packing yang cepat diakses dan mudah dianalisis.
3. Menjamin isolasi data antar-client.
4. Mencegah akses tidak sah melalui role, permission, policy, dan audit trail.
5. Menyediakan laporan gaji dan hasil pekerjaan yang konsisten.
6. Menjaga performa aplikasi melalui server-side processing, eager loading, indexing, caching, dan queue.
7. Menyediakan UI yang sederhana, clean, konsisten, dan mudah dipahami user non-teknis.

## 4. Non-Goals / Out of Scope Awal

- Mengubah aplikasi existing.
- Mengelola source code/database aplikasi existing.
- Integrasi ke sistem lain yang belum disepakati.
- Migrasi seluruh data historis bila tidak diperlukan.
- Mobile app pada fase awal.

## 5. Persona dan Hak Akses

### 5.1 Super Admin PT SIM

Akses penuh lintas client:

- Kelola client.
- Kelola user lintas client.
- Kelola role/permission.
- Monitoring semua client.
- Audit log global.

### 5.2 Admin/CRO

Akses sesuai client yang diberikan:

- Master karyawan.
- Master produk dan master pendukung.
- Potongan gaji.
- Realisasi pekerjaan.
- Laporan.
- User client sesuai permission.

### 5.3 Supervisor/Atasan

- Input/review/finalisasi realisasi.
- Assign karyawan.
- Melihat KPI dan laporan operasional.

### 5.4 Operator/Karyawan

- Input realisasi bila diberikan permission.
- Melihat data terbatas sesuai kebutuhan bisnis.

### 5.5 Client Viewer

- View dashboard client.
- Filter laporan.
- Download laporan.
- Tidak dapat mengubah master/operasional kecuali diberi permission tambahan.

## 6. Multi-Client Requirement

### 6.1 Konsep

Aplikasi memakai **shared database dengan `client_id`** pada tabel data tenant. Pendekatan ini sederhana, cepat, dan cocok untuk maintenance terpusat.

### 6.2 Aturan

- Super Admin dapat berpindah client.
- User biasa hanya melihat client yang ada pada mapping `client_user`.
- User yang memiliki lebih dari satu client mendapat **Client Switcher** di topbar.
- `client_id` tidak boleh dipercaya dari hidden input frontend; server menentukan client aktif dari session/context.
- Semua query tenant menggunakan scope/middleware.
- Semua export dan dashboard wajib menggunakan client aktif.
- Unique key pada data tenant harus mempertimbangkan `client_id`, contoh `unique(client_id, sku)`.

## 7. Modul Fungsional

### 7.1 Authentication

**Fitur:**

- Login username/email + password.
- Logout.
- Session timeout sesuai kebijakan perusahaan.
- Rate limit login.
- Optional reset password bila dibutuhkan.

**Login UI:**

- Placeholder Username: `Masukkan username atau email`
- Placeholder Password: `Masukkan password`
- Error: `Username atau password tidak sesuai.`

### 7.2 Dashboard

**Tujuan:** memberikan ringkasan performa client aktif.

**KPI minimal:**

- Total realisasi hari ini.
- Total output hari ini.
- Total output bulan berjalan.
- Karyawan aktif.
- Produk aktif.
- Persentase output aktual terhadap estimasi.
- Total komplain.
- Tren output 7/30 hari.
- Output per shift.
- Produk dengan realisasi tertinggi.

**Filter:**

- Rentang tanggal.
- Shift.
- Produk.
- Group.

### 7.3 Master Client

Hanya Super Admin.

**Field:**

- Kode Client *
- Nama Client *
- Logo
- Timezone *
- Status *

**Placeholder:**

- Kode: `Contoh: CLIENT001`
- Nama: `Masukkan nama client`
- Timezone: `Pilih timezone`

### 7.4 Master Karyawan

**Field:**

- ID Karyawan * - generated 9 digit.
- SIM ID.
- Nama Lengkap *.
- Email Karyawan.
- Nomor Telepon *.
- Tanggal Masuk *.
- Jenis Kelamin *.
- Status Karyawan *.
- Status Perkawinan *.
- Group.
- Kategori Borongan * - Lama/Baru, tambahan untuk menentukan rate.
- Status Aktif *.

**Placeholder contoh:**

- SIM ID: `Masukkan SIM ID jika tersedia`
- Nama Lengkap: `Masukkan nama lengkap karyawan`
- Email: `contoh@perusahaan.com`
- Nomor Telepon: `Contoh: 081234567890`
- Tanggal Masuk: `Pilih tanggal masuk`
- Jenis Kelamin: `Pilih jenis kelamin`
- Status Karyawan: `Pilih status karyawan`
- Status Perkawinan: `Pilih status perkawinan`
- Group: `Pilih group`
- Kategori Borongan: `Pilih kategori borongan`

### 7.5 Master Group

**Field:** Kode, Nama Group *, Status *.

Placeholder:

- `Masukkan nama group`

### 7.6 Master Satuan

**Field:** Kode, Nama Satuan *, Status *.

Contoh satuan: Karton, Kg.

### 7.7 Master Cost Center

**Field:** Kode Cost Center *, Nama Cost Center *, Status *.

### 7.8 Master Shift

**Field:**

- Kode Shift *
- Nama Shift *
- Jam Mulai *
- Jam Selesai *
- Status *

### 7.9 Master Batch

**Field:**

- Nomor Batch *
- Produk opsional
- Tanggal Mulai
- Tanggal Selesai
- Status *

### 7.10 Master Produk

**Field:**

- SKU *
- Nama Produk *
- Satuan *
- Group
- Cost Center
- Harga PO
- Harga Borongan Karyawan Lama
- Harga Borongan Karyawan Baru
- Estimasi Output/Jam
- Status Produk *

**Tipe angka:** nominal rupiah utuh memakai `BIGINT UNSIGNED`; rate borongan yang dapat memiliki pecahan memakai `DECIMAL(18,3)`.

**Placeholder:**

- SKU: `Masukkan kode SKU`
- Nama Produk: `Masukkan nama produk`
- Satuan: `Pilih satuan`
- Group: `Pilih group`
- Cost Center: `Pilih cost center`
- Harga PO: `Contoh: 12500`
- Harga Borongan Lama: `Masukkan harga borongan karyawan lama`
- Harga Borongan Baru: `Masukkan harga borongan karyawan baru`
- Estimasi Output/Jam: `Masukkan estimasi output per jam`

### 7.11 Realisasi Pekerjaan

**Field:**

- Tanggal Borongan *
- Shift *
- Nomor Batch *
- SKU / Produk *
- Nama Produk otomatis
- Total Karton/Kg *
- Waktu Mulai *
- Waktu Selesai *
- Report / Deskripsi
- Data Borongan Komplenan
- Daftar Karyawan *
- Ingat NIK Karyawan sebagai convenience UI
- Status Draft/Final

**Behavior:**

- Select2 produk melakukan AJAX search berdasarkan SKU/nama.
- Setelah produk dipilih, nama produk dan satuan tampil otomatis.
- Harga borongan yang digunakan harus disimpan sebagai **snapshot** pada detail realisasi agar perubahan harga produk di masa depan tidak mengubah histori.
- Assign karyawan menggunakan Select2 AJAX agar tetap cepat walaupun data karyawan besar.
- Save menggunakan transaction.
- Finalisasi membutuhkan SweetAlert confirmation.

**Placeholder:**

- Tanggal: `Pilih tanggal borongan`
- Shift: `Pilih shift`
- Batch: `Pilih nomor batch`
- Produk: `Cari SKU atau nama produk`
- Total: `Masukkan total hasil pekerjaan`
- Waktu Mulai: `Pilih waktu mulai`
- Waktu Selesai: `Pilih waktu selesai`
- Report: `Tambahkan catatan pekerjaan jika diperlukan`
- Karyawan: `Cari NIK atau nama karyawan`

### 7.12 Potongan Gaji

**Filter periode:** Bulan * dan Minggu opsional.

**Field per karyawan:**

- Potongan Seragam.
- Potongan Perlengkapan Kerja.
- Potongan Uang Makan.
- BPJS Kesehatan (%).
- BPJS Ketenagakerjaan (%).
- DP Gaji tipe Fixed/Persentase.
- Nilai DP Gaji.
- Koreksi Pengurangan.
- Koreksi Penambahan.
- Catatan.

**Behavior:**

- User dapat memilih beberapa karyawan.
- Bulk apply nilai diperbolehkan untuk mempercepat input.
- Nilai potongan rupiah utuh disimpan sebagai `BIGINT UNSIGNED`.
- Nilai DP bertipe persentase tetap memakai `DECIMAL(18,3)`.
- Persentase dibatasi sesuai rule bisnis.
- Periode final/locked tidak bisa diedit tanpa proses unlock berizin.

### 7.13 Laporan Gaji

**Filter:**

- Client aktif.
- Bulan *.
- Minggu opsional.
- Group.
- Status karyawan.

**Kolom:**

- NIK.
- Nama Lengkap.
- Jenis Kelamin.
- Total Hari Masuk.
- Gaji Kotor.
- BPJS Ketenagakerjaan.
- Seragam.
- Perlengkapan Kerja.
- Uang Makan.
- DP Gaji.
- Koreksi Pengurangan.
- Koreksi Penambahan.
- Gaji Bersih.

**Export:** XLSX, optional PDF.

### 7.14 Laporan Hasil Pekerjaan

**Filter:**

- Rentang tanggal *.
- Karyawan.
- Produk.
- Shift.
- Group.
- Status komplain.

**Kolom minimal:**

- Tanggal.
- SIM ID/NIK.
- Nama Lengkap.
- Produk.
- Realisasi.
- Deskripsi.

### 7.15 User Management & Access Control

**Fitur:**

- CRUD user.
- Assign client.
- Assign role per client.
- Aktif/nonaktif user.
- Reset password oleh admin yang berizin.
- Permission granular untuk view/create/update/delete/export/finalize.

### 7.16 Audit Trail

Catat minimal:

- Login/logout.
- Create/update/delete master.
- Create/update/finalize/cancel realisasi.
- Perubahan potongan.
- Export laporan.
- Perubahan user/role.
- Perubahan client aktif untuk aktivitas sensitif bila diperlukan.

Audit menyimpan:

- User.
- Client.
- Action.
- Entity.
- Record ID.
- Old value JSON.
- New value JSON.
- IP address.
- User agent.
- Timestamp.

## 8. Business Rules Penting

1. Data antar-client tidak boleh tercampur.
2. SKU unik per client.
3. Employee ID unik per client.
4. Data final tidak dapat hard-delete.
5. Harga/rate pada transaksi disimpan sebagai snapshot.
6. Perubahan master tidak mengubah histori transaksi lama.
7. Perhitungan gaji harus dapat ditelusuri dari realisasi dan potongan.
8. Semua perubahan sensitif dicatat pada audit log.
9. Export mengikuti filter aktif dan hak akses user.
10. Semua tanggal disimpan konsisten; timezone client digunakan pada presentasi.

## 9. Formula Gaji - Draft

Formula final wajib dikonfirmasi bisnis.

```text
Gaji Kotor = akumulasi nilai borongan dari realisasi pekerjaan
Total Potongan = Seragam + Perlengkapan + Uang Makan + BPJS + DP Gaji + Koreksi Pengurangan
Gaji Bersih = Gaji Kotor - Total Potongan + Koreksi Penambahan
```

Untuk setiap realisasi, sistem perlu mengetahui aturan pembagian nilai borongan saat lebih dari satu karyawan di-assign. Jangan implementasikan asumsi pembagian rata tanpa persetujuan bisnis.

## 10. Non-Functional Requirements

### Performance

- Target list standar: respons API/DataTable ideal < 1 detik pada kondisi normal.
- Pagination server-side.
- Eager loading untuk relasi yang ditampilkan.
- Index pada FK dan filter utama.
- Select2 AJAX untuk dataset besar.
- Cache master data yang jarang berubah.
- Queue untuk export besar.

### Security

- CSRF protection.
- Validation server-side.
- Authorization via Policy/Gate.
- Rate limiting.
- Password hash standar Laravel.
- Session regeneration setelah login.
- Audit trail.
- Tenant isolation test.
- Tidak menampilkan stack trace di production.

### Maintainability

- Service/Action layer untuk business logic kompleks.
- Form Request untuk validation.
- Policy untuk authorization.
- Query scope/repository/query object untuk list kompleks.
- Tests untuk perhitungan payroll dan tenant isolation.

## 11. UI/UX Requirements

Setiap halaman wajib:

1. Breadcrumb.
2. Title.
3. Description pendek dalam Bahasa Indonesia.
4. Content card dengan border tipis dan shadow sangat ringan.
5. Action button yang jelas.
6. Form dengan label, placeholder, helper/error text.
7. Tanda `*` merah pada field required.
8. Konfirmasi SweetAlert untuk aksi destruktif/finalisasi.

Contoh header halaman:

```text
Breadcrumb: Master Data / Karyawan
Title: Data Karyawan
Description: Kelola data karyawan yang terlibat dalam proses co-packing.
```

## 12. DataTable Standard

- Server-side processing.
- Search dengan debounce 300-500 ms.
- Default page size 10/25.
- Filter di area terpisah di atas tabel.
- Sorting hanya pada kolom yang di-index/relevan.
- Action menggunakan menu ringkas atau tombol icon + teks sesuai ruang.
- Empty state: `Belum ada data untuk ditampilkan.`
- Loading state jelas.
- Export tidak memuat seluruh data ke browser.

## 13. Acceptance Criteria Global

- Tidak ada data client A yang muncul saat client B aktif.
- Semua fitur mematuhi permission.
- Validation message Bahasa Indonesia.
- Server-side DataTable tidak melakukan query N+1.
- Query utama memiliki index yang sesuai.
- Form tetap usable pada desktop 1366px dan tablet landscape.
- Button dan status memiliki state loading/disabled.
- Delete/finalize memiliki confirmation.
- Audit log tercatat untuk aksi sensitif.
- Unit test/feature test kritikal lulus.

## 14. Security Requirements — OWASP Top 10:2025

Dokumen normatif: `09_SECURITY_OWASP_SECURE_CODING.md`. Standar ini merupakan perluasan dari security baseline sebelumnya; seluruh kontrol berlaku pada web, AJAX, export, queue, storage, dan operasi lintas client. OWASP ASVS 5.0.0 Level 2 menjadi target usulan yang harus disetujui pemilik sistem.

### SEC-01 — Access Control dan Multi-Client

Setiap request harus memverifikasi user aktif, client aktif, mapping serta role/permission. Semua akses resource menggunakan tenant scope dan Policy. Foreign key input harus berasal dari tenant yang sama. Super Admin lintas client wajib memakai jalur eksplisit dan audit. DB constraints, jobs, cache, file, DataTable, Select2, dan export juga wajib menjaga isolasi. Akses lintas tenant ditolak 403/404 tanpa disclosure.

### SEC-02 — Authentication dan Session

Gunakan auth/hash Laravel yang didukung, throttling, session regeneration, secure cookies, logout invalidation, reset password aman, pencabutan akses, dan re-auth aksi sensitif. MFA untuk akun berisiko tinggi mengikuti kebijakan yang disepakati. Tidak ada password/token di log atau response.

### SEC-03 — Secure Coding dan Input/Output

Form Request dan DTO/allowlist untuk input; server menentukan `client_id`, role, status final, rate, dan nominal terhitung. SQL values memakai binding, sort identifiers memakai allowlist. Blade/JS/DataTables/Select2/SweetAlert melakukan encoding sesuai konteks. Export harus aman dari formula injection. Upload divalidasi konten/ukuran/tipe, disimpan dengan nama server dan private access untuk file sensitif.

### SEC-04 — Integritas Bisnis dan Payroll

Perhitungan hanya di server menggunakan rumus yang disetujui. Snapshot rate, transaksi atomik, state machine, concurrency control, idempotency, dan koreksi/finalisasi berizin wajib diterapkan. Tidak boleh ada perubahan diam-diam pada histori locked/final. Semua perubahan keuangan sensitif dapat ditelusuri.

### SEC-05 — Konfigurasi, Kriptografi, dan Supply Chain

Production hardened dengan HTTPS, debug nonaktif, least privilege, secret management, encryption sesuai klasifikasi, backup terenkripsi, lockfile, dependency audit, dan protected CI/CD. Tidak boleh ada credential/data production dalam repository atau environment non-production tanpa perlindungan dan persetujuan.

### SEC-06 — Logging, Alerting, dan Failure Handling

Audit keamanan terstruktur, redaksi data pribadi/secret, alert untuk kejadian kritis, retensi, incident response, serta backup/restore test. Exception tidak membuka detail internal atau melakukan fail-open. Retry dan kegagalan parsial tidak boleh menggandakan hasil/gaji.

### Acceptance Criteria Keamanan Global

- Seluruh endpoint kritis memiliki security test untuk authentication, authorization, tenant scope, dan validasi.
- Uji IDOR, privilege escalation, SQL injection, XSS, CSRF, file upload, dan ekspor spreadsheet relevan lulus.
- Uji concurrency, retry, dan finalisasi payroll tidak menghasilkan duplikasi atau perubahan histori tanpa izin.
- Tidak ada High/Critical vulnerability relevan yang terbuka tanpa risk acceptance tertulis dari pihak berwenang.
- ASVS verification checklist dan bukti security review tersedia sebelum sign-off production.
- Seluruh kontrol dinyatakan selesai hanya setelah implementasi dan bukti pengujian tersedia; dokumen ini bukan pernyataan aplikasi telah aman atau tersertifikasi.
