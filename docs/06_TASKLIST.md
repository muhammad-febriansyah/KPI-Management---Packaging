# Tasklist - KPI Management Co-Packing

## Cara Membaca Status

- `[x]` pada **Bagian A** = requirement sudah disebut/terdefinisi di BRD.
- `[ ]` pada **Bagian B dst.** = task implementasi yang masih perlu dikerjakan atau diverifikasi.
- Status coding aktual tidak dapat dipastikan tanpa source code/repository.

# A. Requirement yang Sudah Terdefinisi di BRD

- [x] Latar belakang dan tujuan sistem.
- [x] Scope data karyawan.
- [x] Scope pencatatan hasil pekerjaan.
- [x] Scope CRUD/search pengelolaan data.
- [x] Scope monitoring performa.
- [x] Scope reporting + filter/export.
- [x] Scope user management & access control.
- [x] Scope data security & audit trail.
- [x] Scope infrastructure & deployment.
- [x] Flow login.
- [x] Form login username/password.
- [x] Flow master karyawan.
- [x] Field master karyawan.
- [x] Flow master produk.
- [x] Field master produk.
- [x] Flow realisasi pekerjaan.
- [x] Field realisasi pekerjaan.
- [x] Flow assign karyawan.
- [x] Flow potongan gaji.
- [x] Field potongan gaji.
- [x] Struktur Laporan Gaji.
- [x] Struktur Laporan Hasil Pekerjaan.

# B. Foundation - Prioritas Sangat Tinggi

- [ ] Setup project Laravel 13.
- [ ] Setup environment `.env` per environment.
- [ ] Setup Tailwind CSS.
- [ ] Setup layout Blade utama.
- [ ] Setup authentication.
- [ ] Setup middleware auth.
- [ ] Setup master Client.
- [ ] Buat `client_user` mapping.
- [ ] Buat current client context/session.
- [ ] Buat middleware `EnsureClientAccess`.
- [ ] Buat global/query scope tenant yang aman.
- [ ] Buat role & permission.
- [ ] Buat Policy/Gate per modul.
- [ ] Buat audit log dasar.
- [ ] Feature test tenant isolation.

# C. UI Foundation

- [ ] Implement primary color `#2547F9`.
- [ ] Buat reusable page header: breadcrumb + title + description.
- [ ] Buat reusable card wrapper.
- [ ] Buat button variants.
- [ ] Buat input component + label + red required star.
- [ ] Buat validation error component Bahasa Indonesia.
- [ ] Buat Select2 styling sesuai Tailwind.
- [ ] Buat datepicker styling sesuai Tailwind.
- [ ] Buat SweetAlert helper.
- [ ] Buat file upload modern + drag/drop + preview.
- [ ] Buat empty state.
- [ ] Buat skeleton/loading state.
- [ ] Buat status badge.
- [ ] Buat DataTable server-side standard component/helper.

# D. Master Data - Ringan ke Menengah

## D1. Client

- [ ] Migration clients.
- [ ] Model/relationship.
- [ ] Validation/Form Request.
- [ ] DataTable server-side.
- [ ] Form create/edit.
- [ ] Upload logo + preview.
- [ ] Status active/inactive.
- [ ] Policy.
- [ ] Audit log.

## D2. Group

- [ ] Migration + index.
- [ ] CRUD.
- [ ] DataTable server-side.
- [ ] Tenant scope.

## D3. Satuan

- [ ] Migration + index.
- [ ] CRUD.
- [ ] Tenant scope.

## D4. Cost Center

- [ ] Migration + index.
- [ ] CRUD.
- [ ] Tenant scope.

## D5. Shift

- [ ] Migration + index.
- [ ] CRUD.
- [ ] Validasi jam.
- [ ] Tenant scope.

## D6. Batch

- [ ] Migration + index.
- [ ] CRUD.
- [ ] Optional relation produk.
- [ ] Tenant scope.

## D7. Karyawan

- [ ] Migration employees.
- [ ] Index `(client_id, employee_no)` unique.
- [ ] Index group/status.
- [ ] Generate ID 9 digit.
- [ ] CRUD.
- [ ] DataTable server-side.
- [ ] Search NIK/SIM ID/nama.
- [ ] Filter group/status.
- [ ] Tambahkan kategori borongan Lama/Baru.
- [ ] Eager load group dengan select kolom.
- [ ] Policy + audit.

## D8. Produk

- [ ] Migration products.
- [ ] Unique `(client_id, sku)`.
- [ ] `BIGINT UNSIGNED` untuk harga PO dan `DECIMAL(18,3)` untuk rate borongan.
- [ ] CRUD.
- [ ] DataTable server-side.
- [ ] Filter group/cost center/status.
- [ ] Select2 AJAX endpoint untuk form realisasi.
- [ ] Eager load unit/group/costCenter.
- [ ] Policy + audit.

# E. Realisasi Pekerjaan - Menengah ke Berat

- [ ] Migration `work_realizations`.
- [ ] Migration `realization_employees`.
- [ ] Composite index berdasarkan client + tanggal.
- [ ] Form create/edit.
- [ ] Select2 AJAX produk.
- [ ] Select2 AJAX karyawan.
- [ ] Auto-fill nama produk/satuan.
- [ ] Assign multiple karyawan.
- [ ] Prevent duplicate employee assignment.
- [ ] Snapshot SKU/nama produk/satuan.
- [ ] Snapshot kategori/rate karyawan.
- [ ] Tentukan formula pembagian hasil multi-karyawan dengan business owner.
- [ ] Hitung gross amount.
- [ ] Transaction saat simpan.
- [ ] Draft/final/cancel status.
- [ ] SweetAlert finalisasi.
- [ ] Lock edit setelah final.
- [ ] Audit create/update/finalize/cancel.
- [ ] DataTable server-side + filter tanggal/produk/shift/status.
- [ ] Performance test query.

# F. Potongan Gaji - Berat

- [ ] Migration `deduction_periods`.
- [ ] Migration `employee_deductions`.
- [ ] Unique period + employee.
- [ ] Form period bulan/minggu.
- [ ] Pilih banyak karyawan via server-side/AJAX.
- [ ] Input semua komponen potongan.
- [ ] Fixed/persentase DP Gaji.
- [ ] Bulk apply nilai.
- [ ] Preview sebelum bulk update.
- [ ] Draft/locked period.
- [ ] Authorization lock/unlock.
- [ ] Audit perubahan.
- [ ] Validation angka dan persentase.

# G. Payroll & Reporting - Berat

- [ ] Finalisasi business rule gaji kotor.
- [ ] Finalisasi business rule total hari masuk.
- [ ] Finalisasi perhitungan BPJS.
- [ ] Service kalkulasi gaji.
- [ ] Unit test seluruh formula.
- [ ] Laporan Gaji server-side.
- [ ] Laporan Hasil Pekerjaan server-side.
- [ ] Filter laporan.
- [ ] Export XLSX.
- [ ] Queue export data besar.
- [ ] Optional PDF export.
- [ ] Audit export.
- [ ] Pertimbangkan snapshot `payroll_runs` bila data besar.

# H. Dashboard KPI

- [ ] Tentukan KPI final bersama user bisnis.
- [ ] Query total output hari ini/bulan.
- [ ] Query output per shift.
- [ ] Query output per produk.
- [ ] Query komplain.
- [ ] Query actual vs estimated output/hour.
- [ ] Chart tren 7/30 hari.
- [ ] Cache query agregat yang aman.
- [ ] Index query berdasarkan hasil `EXPLAIN`.

# I. User Management & Security

- [ ] CRUD user.
- [ ] Assign user ke client.
- [ ] Assign role per client.
- [ ] Permission view/create/update/delete/export/finalize.
- [ ] Rate limit login.
- [ ] Session security.
- [ ] CSRF validation.
- [ ] Form Request semua endpoint tulis.
- [ ] Escape output Blade.
- [ ] Audit login/logout.
- [ ] Audit perubahan role/user.
- [ ] Security test IDOR/cross-client.

# J. Performance Checklist

- [ ] Semua DataTable menggunakan server-side processing.
- [ ] Tidak menggunakan `Model::all()` untuk dropdown besar.
- [ ] Select2 besar menggunakan AJAX pagination.
- [ ] Eager loading semua relasi list yang diperlukan.
- [ ] Select kolom spesifik pada eager loading.
- [ ] Hilangkan N+1 dari halaman list/report.
- [ ] Tambahkan index seluruh FK.
- [ ] Tambahkan composite index sesuai filter utama.
- [ ] Jalankan `EXPLAIN` query report lambat.
- [ ] Cache master data jarang berubah.
- [ ] Queue export besar.
- [ ] Gunakan chunking/cursor untuk proses background besar.
- [ ] Jangan memuat file besar ke memory sekaligus.

# K. Testing

- [ ] Unit test formula gaji.
- [ ] Feature test login.
- [ ] Feature test authorization.
- [ ] Feature test tenant isolation.
- [ ] Feature test CRUD master.
- [ ] Feature test realisasi.
- [ ] Feature test lock/finalisasi.
- [ ] Feature test report filter.
- [ ] Test export.
- [ ] Test file upload validation.
- [ ] Test query count untuk list kritikal bila diperlukan.

# L. Deployment & Operations

- [ ] Production `.env` aman.
- [ ] `APP_DEBUG=false`.
- [ ] Cache config/route/view sesuai deployment strategy.
- [ ] Queue worker dengan process manager.
- [ ] Scheduler/cron Laravel.
- [ ] Database backup otomatis.
- [ ] Log rotation.
- [ ] Monitoring error.
- [ ] Health check aplikasi/database/queue.
- [ ] Migration backup/rollback plan.
- [ ] UAT dengan user bisnis.
- [ ] Sign-off business rules payroll.

# M. OWASP Secure Coding — Prioritas dan Status Implementasi

Standar rinci ada pada `09_SECURITY_OWASP_SECURE_CODING.md`. Seluruh item di bawah belum dinyatakan selesai karena source code/repository belum tersedia. Checklist dokumentasi berbeda dari checklist implementasi.

## M0. Foundation Security — P0

- [ ] Tetapkan security owner, klasifikasi data, target ASVS 5.0.0 Level 2, dan pengecualian yang disetujui.
- [ ] Threat model auth, multi-client, payroll, export, file, dan queue.
- [ ] Terapkan Laravel Pint, static analysis, PR review, dan standar coding aman.
- [ ] Terapkan Form Request, DTO/allowlist, dan server-controlled sensitive fields.
- [ ] Implementasi current client context yang fail closed dan diverifikasi tiap request.
- [ ] Implementasi permission per client dan Policy seluruh resource/action.
- [ ] Validasi ownership seluruh FK dan array ID; tenant-safe route binding.
- [ ] Review composite FK/tenant ownership constraints pada child tables.
- [ ] Uji IDOR, cross-client CRUD, AJAX, export, download, serta privilege escalation.
- [ ] Auth/hash Laravel, throttle, session regeneration/invalidation, secure cookies.
- [ ] Tetapkan MFA/re-auth dan kebijakan timeout sesuai hasil persetujuan.
- [ ] SQL binding dan allowlist sorting/filter DataTables.
- [ ] Output encoding aman untuk Blade, DataTables, Select2, SweetAlert.
- [ ] CSRF protection dan review seluruh exemption.
- [ ] Secret management, HTTPS, debug off, least privilege, dan production hardening.
- [ ] Dependency audit, secret scan, lockfile, dan protected CI/CD.
- [ ] Audit minimal yang teredaksi serta backup/rollback plan.

## M1. Operasional, Payroll, File — P1

- [ ] Validasi file konten/MIME/ukuran, private storage, preview aman, download policy.
- [ ] Pengamanan CSV/XLSX formula injection dan field-level export permission.
- [ ] Queue membawa tenant/actor context eksplisit dan tidak bocor antar-job.
- [ ] Cache key mengandung tenant dan scope izin serta invalidation yang benar.
- [ ] Server-side rate calculation dan immutable transaction snapshots.
- [ ] Finalisasi memakai state machine, transaction, lock, dan idempotency.
- [ ] Koreksi/reversal payroll berizin, alasan, approval bila diperlukan, dan audit.
- [ ] Uji double submission, concurrency, retry, duplicate assignment, dan locked period.
- [ ] Batasi ukuran request, pagination, export, dan resource-intensive operations.
- [ ] Implementasi safe error handling, timeout, retry/backoff, dan failed-job handling.
- [ ] Alert privilege changes, cross-tenant attempts, abnormal exports, dan payroll failures.

## M2. Verification & Go-Live — P2

- [ ] Susun ASVS requirement checklist dengan versi dan status/bukti per kontrol.
- [ ] Jalankan unit/feature/security regression tests pada CI.
- [ ] Jalankan DAST pada staging yang diotorisasi dan review keamanan independen sesuai kebijakan.
- [ ] Triage serta remediasi temuan; risk acceptance tertulis untuk pengecualian.
- [ ] Review konfigurasi TLS, CSP, security headers, secret rotation, dan akses infrastruktur.
- [ ] Uji backup restore dan tetapkan RPO/RTO, retensi, serta incident response.
- [ ] Uji alert dan eskalasi insiden.
- [ ] Security sign-off sebelum production go-live.

## M3. Pemeliharaan — P3

- [ ] Audit dependency dan pembaruan patch keamanan secara berkala.
- [ ] Review user/client access dan privilege berkala.
- [ ] Perbarui threat model dan regression tests setiap perubahan fitur penting.
- [ ] Review log/alert, restore test, dan security incident exercise berkala.
- [ ] Tinjau ulang standar OWASP/ASVS sesuai versi yang diadopsi perusahaan.

## M4. Definition of Security Done

Fitur belum selesai apabila belum memiliki validasi dan izin yang relevan, masih memungkinkan cross-tenant access, memercayai nominal/role/status dari frontend, menampilkan input tanpa encoding aman, menyimpan file sensitif secara publik, atau belum memiliki pengujian failure path penting. Bukti testing dan review wajib tersedia; status `[x]` baru boleh diubah setelah pekerjaan nyata diverifikasi.
