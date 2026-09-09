# User Stories - KPI Management Co-Packing

## Epic A - Authentication & Multi-Client

### US-A01 - Login

**Sebagai** user, **saya ingin** login menggunakan username/email dan password **agar** dapat masuk ke aplikasi sesuai hak akses.

**Acceptance Criteria:**

- Field username/email wajib.
- Field password wajib.
- Password tidak ditampilkan plain text.
- Kredensial salah menampilkan pesan Bahasa Indonesia.
- Session diregenerasi setelah login.
- Login terkena rate limit.

### US-A02 - Pilih Client Aktif

**Sebagai** user yang memiliki beberapa client, **saya ingin** memilih client aktif **agar** data yang saya lihat sesuai client yang sedang dikerjakan.

**Acceptance Criteria:**

- Hanya client yang diberikan ke user yang muncul.
- Pemilihan disimpan di session.
- Semua menu/list/report setelah itu ter-scope client aktif.
- Manipulasi `client_id` melalui request tidak dapat membuka client lain.

### US-A03 - Kelola Client

**Sebagai** Super Admin, **saya ingin** mengelola client **agar** satu aplikasi dapat digunakan banyak client.

**Acceptance Criteria:**

- Kode client unik.
- Logo dapat di-preview sebelum disimpan.
- Client nonaktif tidak dapat dipakai untuk transaksi baru.

## Epic B - Master Data

### US-B01 - Kelola Karyawan

**Sebagai** Admin/CRO, **saya ingin** tambah, lihat, ubah, cari, dan menonaktifkan karyawan **agar** data pekerja selalu akurat.

**Acceptance Criteria:**

- Employee ID 9 digit unik per client.
- Nama, telepon, tanggal masuk, gender, status karyawan, status perkawinan, kategori borongan wajib.
- DataTable server-side.
- Search NIK/SIM ID/nama.
- Filter group dan status.
- Tidak ada data client lain yang tampil.

### US-B02 - Kelola Produk

**Sebagai** Admin/CRO, **saya ingin** mengelola produk dan rate borongan **agar** realisasi dapat dihitung berdasarkan produk yang benar.

**Acceptance Criteria:**

- SKU unik per client.
- Harga PO menggunakan rupiah utuh; rate borongan dapat memakai `DECIMAL(18,3)` bila diperlukan.
- Produk nonaktif tidak muncul untuk transaksi baru.
- DataTable server-side dan dapat difilter.

### US-B03 - Kelola Master Pendukung

**Sebagai** Admin/CRO, **saya ingin** mengelola Group, Satuan, Cost Center, Shift, dan Batch **agar** dropdown menggunakan data yang konsisten.

**Acceptance Criteria:**

- Setiap master ter-scope client.
- Data nonaktif tidak muncul pada form transaksi baru.
- Penghapusan data yang sudah dipakai dicegah atau menggunakan soft delete.

## Epic C - Realisasi Pekerjaan

### US-C01 - Input Realisasi

**Sebagai** Supervisor, **saya ingin** mencatat realisasi pekerjaan **agar** hasil co-packing terdokumentasi.

**Acceptance Criteria:**

- Tanggal, shift, produk, total output, waktu mulai, waktu selesai, dan karyawan tervalidasi.
- Produk dipilih via Select2 AJAX.
- Nama produk/satuan terisi otomatis.
- Waktu selesai tidak boleh tidak logis terhadap waktu mulai sesuai business rule shift.
- Penyimpanan menggunakan transaction.

### US-C02 - Assign Banyak Karyawan

**Sebagai** Supervisor, **saya ingin** memilih beberapa karyawan **agar** semua pekerja pada realisasi tercatat.

**Acceptance Criteria:**

- Search berdasarkan NIK/nama.
- Tidak dapat memilih karyawan yang sama dua kali.
- Hanya karyawan aktif pada client yang sama yang dapat dipilih.
- Rate category dan rate disimpan sebagai snapshot.

### US-C03 - Tandai Komplain

**Sebagai** Supervisor, **saya ingin** menandai realisasi sebagai komplain **agar** data dapat difilter untuk evaluasi.

**Acceptance Criteria:**

- Tersedia checkbox/toggle komplain.
- Report dapat diisi sebagai penjelasan.
- Dashboard/laporan dapat memfilter komplain.

### US-C04 - Finalisasi Realisasi

**Sebagai** Supervisor berizin, **saya ingin** memfinalisasi realisasi **agar** data yang sudah diverifikasi tidak berubah sembarangan.

**Acceptance Criteria:**

- SweetAlert menampilkan konfirmasi.
- Setelah final, edit dibatasi.
- Finalized by/time disimpan.
- Audit log tercatat.

## Epic D - Potongan & Payroll

### US-D01 - Input Potongan

**Sebagai** Admin/CRO, **saya ingin** memasukkan potongan per periode **agar** gaji bersih dapat dihitung.

**Acceptance Criteria:**

- Bulan wajib.
- Minggu opsional.
- Dapat memilih banyak karyawan.
- Nilai uang tidak boleh negatif kecuali field koreksi dengan rule yang jelas.
- Data tersimpan per karyawan.

### US-D02 - Bulk Apply Potongan

**Sebagai** Admin/CRO, **saya ingin** menerapkan nilai potongan yang sama ke beberapa karyawan **agar** input lebih cepat.

**Acceptance Criteria:**

- User memilih karyawan target.
- Preview jumlah target sebelum apply.
- SweetAlert konfirmasi untuk bulk update.
- Audit log menyimpan perubahan.

### US-D03 - Lock Periode

**Sebagai** Admin berizin, **saya ingin** mengunci periode potongan/payroll **agar** data laporan final tidak berubah.

**Acceptance Criteria:**

- Lock membutuhkan konfirmasi.
- Periode locked read-only untuk user biasa.
- Unlock hanya role tertentu dan tercatat audit.

## Epic E - Reporting

### US-E01 - Laporan Gaji

**Sebagai** Admin/Client Viewer, **saya ingin** melihat laporan gaji berdasarkan periode **agar** dapat melakukan monitoring dan evaluasi.

**Acceptance Criteria:**

- Filter bulan tersedia.
- Filter client otomatis mengikuti client aktif.
- Perhitungan gaji kotor/potongan/gaji bersih konsisten.
- Tabel server-side.
- Export mengikuti filter dan permission.

### US-E02 - Laporan Hasil Pekerjaan

**Sebagai** Admin/Client Viewer, **saya ingin** melihat hasil pekerjaan berdasarkan rentang tanggal **agar** dapat mengevaluasi performa co-packing.

**Acceptance Criteria:**

- Filter tanggal, produk, karyawan, shift, group tersedia.
- Search dan pagination server-side.
- Export tidak memuat seluruh dataset ke browser.

## Epic F - Dashboard

### US-F01 - Dashboard KPI

**Sebagai** Supervisor/Admin/Client Viewer, **saya ingin** melihat ringkasan KPI **agar** dapat memahami kondisi operasional dengan cepat.

**Acceptance Criteria:**

- KPI mengikuti client aktif dan filter periode.
- Query agregasi memiliki index yang relevan.
- Grafik tidak memuat data mentah berlebihan.
- Empty state ditampilkan bila belum ada data.

## Epic G - User & Access Control

### US-G01 - Assign Role per Client

**Sebagai** Super Admin, **saya ingin** memberi role user pada client tertentu **agar** hak akses dapat berbeda antar-client.

**Acceptance Criteria:**

- User dapat memiliki role berbeda pada client berbeda.
- Permission diperiksa server-side.
- Menu frontend mengikuti permission tetapi bukan satu-satunya pengamanan.

## Epic H - Audit & Security

### US-H01 - Lihat Audit Log

**Sebagai** user auditor/admin berizin, **saya ingin** melihat audit log **agar** perubahan data dapat ditelusuri.

**Acceptance Criteria:**

- Filter tanggal, user, action, module tersedia.
- Old/new value dapat dilihat secara aman.
- Audit log tidak dapat diedit oleh user biasa.

### US-H02 - Isolasi Data Client

**Sebagai** pemilik sistem, **saya ingin** memastikan data client tidak dapat diakses client lain **agar** kerahasiaan data terjaga.

**Acceptance Criteria:**

- Feature test mencoba akses ID milik client lain dan harus mendapat 403/404.
- Export dan endpoint AJAX Select2 juga ter-scope client.
- Route model binding tidak melewati pemeriksaan tenant.

## Epic I - Secure Coding dan Security Operations

### US-I01 — Validasi Akses Seluruh Endpoint

**Sebagai** pemilik sistem, **saya ingin** semua operasi memeriksa permission dan client yang benar **agar** data antar-client tidak bocor.

**Acceptance Criteria:** Endpoint CRUD, AJAX, export, download, dan job memeriksa ownership; ID relasi lintas client ditolak; field sensitif tidak diberikan kepada role tanpa izin; test IDOR dan privilege escalation lulus.

### US-I02 — Login dan Session Aman

**Sebagai** pengguna, **saya ingin** akun dan sesi saya terlindungi **agar** orang lain tidak dapat mengambil alih akses.

**Acceptance Criteria:** Login throttle, password hash, session regeneration/invalidation, reset token aman, akun nonaktif ditolak, dan pencabutan akses efektif. MFA/re-auth akun kritis mengikuti kebijakan yang disetujui.

### US-I03 — Input dan Output Aman

**Sebagai** Admin/CRO, **saya ingin** data yang dimasukkan dan ditampilkan diproses aman **agar** input berbahaya tidak mengeksekusi SQL, JavaScript, atau formula spreadsheet.

**Acceptance Criteria:** Form Request/allowlist, SQL binding, safe sort mapping, output encoding Blade/DataTables/Select2/SweetAlert, serta export formula-injection test lulus.

### US-I04 — Upload dan Unduhan Privat

**Sebagai** pengguna berizin, **saya ingin** mengunggah dan mengambil file hanya yang diperbolehkan **agar** file tidak menjadi sarana serangan atau kebocoran.

**Acceptance Criteria:** Validasi MIME/konten/ukuran, nama server-generated, private storage untuk data sensitif, download ownership check, serta preview modern yang aman.

### US-I05 — Integritas Finalisasi Payroll

**Sebagai** Admin keuangan, **saya ingin** perhitungan dan finalisasi tidak dapat dimanipulasi atau digandakan **agar** laporan gaji konsisten dan dapat diaudit.

**Acceptance Criteria:** Rate server-side, snapshot, transaction/locking/idempotency, state machine, koreksi berizin, serta concurrency/retry tests lulus. Formula wajib disetujui bisnis sebelum implementasi final.

### US-I06 — Audit dan Alert Keamanan

**Sebagai** auditor/pemilik sistem, **saya ingin** aktivitas berisiko dicatat dan alert dapat ditindaklanjuti **agar** insiden terdeteksi serta ditelusuri.

**Acceptance Criteria:** Audit terstruktur dan teredaksi, log tidak dapat diubah user biasa, alert dengan owner/eskalasi, dan uji respons insiden tersedia.

### US-I07 — Rilis Melalui Security Gate

**Sebagai** Tech Lead, **saya ingin** setiap perubahan diuji dan direview berdasarkan standar **agar** kerentanan tidak mudah masuk production.

**Acceptance Criteria:** Pint/static analysis/tests, dependency/secret scanning, security review, ASVS checklist, dan approval/risk acceptance terdokumentasi. Tidak ada klaim audit lulus tanpa bukti.

### US-I08 — Penanganan Error Aman

**Sebagai** pengguna, **saya ingin** sistem memberi pesan yang jelas ketika gagal **agar** saya tidak mengulangi transaksi yang sebenarnya sudah diproses.

**Acceptance Criteria:** Error publik tidak membocorkan detail internal; rollback/retry tidak menggandakan gaji; invalid tenant context fail closed; timeout dan failure path diuji.
