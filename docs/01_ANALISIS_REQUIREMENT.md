# Analisis Requirement - KPI Management Co-Packing

## 1. Ringkasan

Aplikasi dibutuhkan sebagai pengganti/alternatif sistem pencatatan co-packing yang saat ini bergantung pada aplikasi dan pengelolaan pihak eksternal. Fokus utamanya adalah pencatatan performa co-packing, pengelolaan master data, potongan gaji, monitoring, reporting, user management, access control, audit trail, serta deployment pada infrastruktur perusahaan.

Requirement tambahan pada dokumen ini adalah **multi-client**, sehingga satu aplikasi dapat digunakan oleh beberapa client dengan data yang terisolasi.

## 2. Modul yang Sudah Terdefinisi pada BRD

Status di bawah adalah **selesai dari sisi requirement BRD**, bukan status coding.

- [x] Login menggunakan username dan password.
- [x] Master Data Karyawan.
- [x] Master Produk.
- [x] Realisasi Pekerjaan.
- [x] Assign karyawan ke realisasi pekerjaan.
- [x] Potongan Gaji.
- [x] Laporan Gaji.
- [x] Laporan Hasil Pekerjaan.
- [x] User Management & Access Control pada level scope.
- [x] Data Security & Audit Trail pada level scope.
- [x] Infrastructure & Deployment pada level scope.
- [x] Monitoring performa pada level scope.

## 3. Role yang Teridentifikasi

Dari use case BRD terdapat tiga kelompok utama:

1. **Admin/CRO**
   - Login.
   - Kelola karyawan.
   - Kelola master produk.
   - Kelola potongan gaji.
   - Unduh laporan.

2. **Atasan/Karyawan**
   - Kelola realisasi pekerjaan sesuai hak akses.

3. **Klien**
   - Melihat/mengunduh laporan sesuai hak akses.

Untuk kebutuhan multi-client, role disempurnakan menjadi:

- **Super Admin PT SIM** - akses lintas client.
- **Admin/CRO** - administrasi satu atau beberapa client.
- **Supervisor/Atasan** - input/review realisasi pekerjaan.
- **Operator/Karyawan** - input terbatas bila diperlukan.
- **Client Viewer** - melihat dashboard dan laporan client miliknya.

## 4. Gap Requirement yang Perlu Dilengkapi

### 4.1 Multi-Client

BRD belum menjelaskan mekanisme multi-client. Dibutuhkan:

- Master Client.
- User dapat memiliki akses ke satu atau beberapa client.
- Client switcher untuk user multi-client.
- Semua data operasional wajib memiliki `client_id`.
- Middleware/policy wajib mencegah akses silang antar-client.
- Semua filter, export, dashboard, dan audit log harus mengikuti client aktif.

### 4.2 Master Pendukung

BRD menggunakan beberapa dropdown tetapi belum mendefinisikan master datanya secara lengkap:

- Group.
- Satuan.
- Cost Center.
- Shift.
- Batch.
- Status produk.
- Status karyawan.

Master tersebut sebaiknya dibuat agar data konsisten dan mudah dikelola.

### 4.3 Penentuan Harga Borongan Karyawan Lama/Baru

Master Produk memiliki harga borongan untuk karyawan lama dan baru, tetapi belum dijelaskan bagaimana sistem menentukan kategori karyawan.

**Rekomendasi:** tambahkan `kategori_borongan` pada karyawan dengan nilai `Lama` atau `Baru`, atau tetapkan business rule otomatis berdasarkan masa kerja. Pilihan final harus disetujui bisnis.

### 4.4 Formula Gaji Kotor

BRD menyebut gaji kotor berasal dari realisasi pekerjaan, tetapi formula detail belum dijelaskan.

Perlu konfirmasi:

- Apakah `Total Karton/Kg x Harga Borongan`?
- Bila satu realisasi memiliki beberapa karyawan, apakah hasil dibagi rata atau ada bobot tertentu?
- Apakah produk dapat memiliki metode perhitungan berbeda berdasarkan satuan?
- Apakah data komplain memengaruhi nilai hasil/gaji?
- Bagaimana koreksi realisasi setelah periode gaji dikunci?

### 4.5 Status dan Finalisasi Data

Agar histori aman, disarankan status:

- Draft.
- Final.
- Dibatalkan.

Data realisasi dan payroll yang sudah final sebaiknya tidak bisa diedit tanpa proses koreksi dan audit trail.

### 4.6 Dashboard/Monitoring

Scope menyebut monitoring performa, tetapi metrik dashboard belum didefinisikan.

Rekomendasi minimal:

- Total realisasi hari ini.
- Total output hari ini/bulan ini.
- Jumlah karyawan aktif.
- Output aktual vs estimasi output/jam.
- Produk dengan realisasi tertinggi.
- Performa per shift.
- Tren output harian.
- Jumlah komplain.

### 4.7 Export Laporan

BRD menyebut export, tetapi format belum ditetapkan.

Rekomendasi:

- Excel/XLSX untuk data operasional.
- PDF untuk laporan siap cetak bila diperlukan.
- Export besar diproses via queue agar request web tetap cepat.

## 5. Data Form Utama

### 5.1 Karyawan

Field BRD:

- ID 9 digit generated.
- SIM ID.
- Nama Lengkap.
- Email Karyawan.
- Nomor Telepon.
- Tanggal Masuk.
- Jenis Kelamin.
- Status Karyawan.
- Status Perkawinan.
- Group.

Tambahan yang direkomendasikan:

- Client.
- Kategori Borongan: Lama/Baru.
- Status aktif/nonaktif.

### 5.2 Produk

- Nama SKU.
- Nama Produk.
- Satuan.
- Group.
- Cost Center.
- Harga PO disimpan sebagai `BIGINT UNSIGNED` dalam satuan rupiah utuh.
- Harga Borongan Karyawan Lama decimal 3 angka.
- Harga Borongan Karyawan Baru decimal 3 angka.
- Estimasi Output/Jam.
- Status Produk.

### 5.3 Realisasi Pekerjaan

- Tanggal Borongan.
- Shift.
- Nomor Batch.
- SKU.
- Nama Produk otomatis dari SKU.
- Total Karton/Kg.
- Waktu Pengerjaan 1.
- Waktu Pengerjaan 2.
- Report.
- Data Borongan Komplenan.
- Ingat NIK Karyawan.
- Daftar karyawan yang di-assign.

### 5.4 Potongan Gaji

- Bulan.
- Minggu.
- Potongan Seragam.
- Potongan Perlengkapan Kerja.
- Potongan Uang Makan.
- BPJS Kesehatan (%).
- BPJS Ketenagakerjaan (%).
- DP Gaji: fixed/persentase.
- Nilai DP Gaji.
- Koreksi Pengurangan.
- Koreksi Penambahan.
- Karyawan yang dipilih.

### 5.5 Laporan Gaji

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

### 5.6 Laporan Hasil Pekerjaan

- Tanggal.
- SIM ID.
- Nama Lengkap.
- Produk.
- Realisasi.
- Deskripsi.

## 6. Prioritas Pengembangan

### P0 - Foundation

- Authentication.
- Multi-client isolation.
- Role & permission.
- Master client.
- Audit log dasar.

### P1 - Master Data

- Karyawan.
- Group.
- Satuan.
- Cost Center.
- Shift.
- Batch.
- Produk.

### P2 - Operasional

- Realisasi pekerjaan.
- Assign karyawan.
- Validasi harga borongan.
- Perhitungan hasil dan gaji kotor.

### P3 - Payroll & Report

- Potongan gaji.
- Perhitungan gaji bersih.
- Laporan gaji.
- Laporan hasil pekerjaan.
- Export.

### P4 - Monitoring, Security, Performance

- Dashboard KPI.
- Audit log lengkap.
- Indexing dan query optimization.
- Cache master data.
- Queue export.
- Backup dan monitoring.

## 7. Definition of Done Umum

Sebuah fitur dianggap selesai jika:

- Validasi backend tersedia.
- Authorization/policy tersedia.
- Tenant/client isolation teruji.
- UI mengikuti design system.
- Breadcrumb, title, description, dan card tersedia.
- Semua required field memakai `*` merah.
- Semua input memiliki placeholder.
- DataTable menggunakan server-side processing.
- Tidak terdapat N+1 query.
- Index database relevan tersedia.
- Pesan sukses/gagal menggunakan Bahasa Indonesia.
- Aksi hapus/perubahan berisiko menggunakan SweetAlert.
- Audit log tercatat untuk aksi penting.
- Test minimal untuk happy path, validation, permission, dan tenant isolation tersedia.

## 8. Analisis Tambahan Keamanan OWASP

### Dasar dan status

BRD menetapkan Data Security & Audit Trail serta kebutuhan infrastruktur sesuai kebijakan PT SIM, tetapi belum memuat daftar OWASP, ASVS, kontrol teknis, standar secure coding, atau bukti pengujian. Standar tambahan disusun pada `09_SECURITY_OWASP_SECURE_CODING.md` dengan acuan OWASP Top 10:2025 dan target usulan ASVS 5.0.0 Level 2. Target final dan pengecualian harus disetujui PT SIM.

### Gap keamanan prioritas

| Prioritas | Gap | Keputusan yang direkomendasikan |
|---|---|---|
| P0 | Tenant isolation belum memiliki standar berlapis | Wajib context terverifikasi, Policy, validasi relasi, constraint DB, dan pengujian cross-client untuk seluruh endpoint. |
| P0 | Auth, session, dan privilege belum rinci | Throttling, session aman, MFA admin sesuai kebijakan, re-auth aksi kritis, dan pencabutan akses efektif. |
| P0 | Secure coding belum menjadi gate PR | Pint, static analysis, review keamanan, input allowlist, SQL binding, output encoding, serta secret/dependency scanning. |
| P0 | Data gaji dan data pribadi perlu klasifikasi | Field-level permission, minimisasi data, encryption/key management, storage privat, dan redaksi log. |
| P1 | Integritas borongan dan payroll | Server-side calculation, snapshot rate, transaksi/lock/idempotency, state machine, koreksi berizin, serta audit. |
| P1 | Export, upload, queue, dan cache | Tenant ownership, private files, safe spreadsheet output, validasi file, context job eksplisit, dan batas resource. |
| P2 | Operasional keamanan | Alerting, incident response, backup/restore test, vulnerability remediation, dan ASVS verification. |

**Perubahan urutan prioritas:** Security tidak boleh menunggu seluruh modul selesai. Kontrol P0 harus menjadi foundation sebelum data operasional nyata diproses. Audit lanjutan, monitoring, dan pengetesan independen dilakukan berkelanjutan sampai sign-off go-live.

### Hal yang perlu disetujui perusahaan

Pemilik data dan klasifikasi data, tingkat ASVS, kebijakan MFA/session, retensi audit/backup, RPO/RTO, enkripsi field yang diperlukan, daftar role yang boleh melihat gaji, approval koreksi payroll, SLA penanganan kerentanan, dan prosedur incident response. Tidak satu pun keputusan tersebut dianggap sudah disetujui hanya karena dimasukkan ke dokumen.
