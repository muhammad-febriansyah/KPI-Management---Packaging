# Technical Architecture & Performance
## Laravel 13 - KPI Management Co-Packing Multi Client

## 1. Arsitektur Aplikasi

```text
Browser
  |
  v
Nginx / Web Server
  |
  v
Laravel 13
  |-- Authentication / Session
  |-- Middleware Current Client
  |-- Policy / Permission
  |-- Form Request Validation
  |-- Controllers
  |-- Services / Actions
  |-- Query Objects / Scopes
  |-- Jobs / Queue
  |
  +---- MySQL/MariaDB
  +---- Cache
  +---- Queue Worker
  +---- File Storage
```

## 2. Struktur Layer yang Direkomendasikan

```text
app/
├── Actions/
│   ├── Realization/
│   └── Payroll/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Policies/
├── Queries/
├── Services/
│   ├── CurrentClientService.php
│   ├── PayrollCalculationService.php
│   └── AuditService.php
└── Jobs/
    └── ExportReportJob.php
```

Business logic berat seperti kalkulasi payroll jangan diletakkan penuh di Controller.

## 3. Multi-Client Isolation

### Context Client Aktif

Setelah login:

1. Ambil daftar client yang boleh diakses user.
2. Tentukan default client.
3. Simpan `current_client_id` di session.
4. Setiap request membaca client aktif melalui service/helper.
5. Middleware memastikan user masih memiliki akses ke client tersebut.

### Larangan

Jangan melakukan:

```php
$clientId = $request->client_id;
Employee::where('client_id', $clientId)->get();
```

Tanpa memverifikasi user memiliki akses terhadap client tersebut.

### Scope

Buat pola konsisten:

```php
Employee::query()
    ->forClient(currentClientId())
    ->where('status', 'active');
```

Untuk operasi berdasarkan ID, selalu kombinasikan dengan client:

```php
$employee = Employee::query()
    ->forClient(currentClientId())
    ->findOrFail($id);
```

Ini membantu mencegah IDOR/cross-tenant access.

## 4. DataTable Server-Side

DataTable harus melakukan:

- Pagination di database.
- Search di database.
- Sorting di database.
- Filter di database.

Contoh query karyawan:

```php
$query = Employee::query()
    ->forClient(currentClientId())
    ->select([
        'id',
        'client_id',
        'employee_no',
        'sim_id',
        'full_name',
        'phone',
        'group_id',
        'status',
    ])
    ->with('group:id,name');
```

Search:

```php
$query->when($search, function ($q) use ($search) {
    $q->where(function ($q) use ($search) {
        $q->where('employee_no', 'like', $search.'%')
          ->orWhere('sim_id', 'like', $search.'%')
          ->orWhere('full_name', 'like', '%'.$search.'%');
    });
});
```

Catatan: pencarian `%keyword%` pada dataset sangat besar dapat membutuhkan strategi full-text/search engine, tetapi untuk skala awal tetap dapat dipakai dengan monitoring.

## 5. Eager Loading

### Wajib

Gunakan `with()` ketika list menampilkan relasi.

```php
Product::query()
    ->forClient(currentClientId())
    ->with([
        'unit:id,name',
        'group:id,name',
        'costCenter:id,name',
    ]);
```

### Hindari N+1

Buruk:

```php
foreach ($products as $product) {
    echo $product->group->name;
}
```

Jika relasi belum di-eager load, ini dapat menambah query per row.

### Select Kolom Relasi

Jangan load seluruh kolom bila hanya butuh `id` dan `name`.

## 6. Indexing Database

### Prinsip

Index dibuat berdasarkan query nyata:

- Tenant: `client_id`.
- Foreign key.
- Date filter.
- Status.
- Search key yang cocok.
- Composite index berdasarkan urutan WHERE yang umum.

### Index Minimum

```text
employees:
  UNIQUE(client_id, employee_no)
  INDEX(client_id, status)
  INDEX(client_id, group_id, status)

products:
  UNIQUE(client_id, sku)
  INDEX(client_id, status)
  INDEX(client_id, group_id, status)

work_realizations:
  INDEX(client_id, work_date, status)
  INDEX(client_id, product_id, work_date)
  INDEX(client_id, shift_id, work_date)

deduction_periods:
  INDEX(client_id, month, status)

audit_logs:
  INDEX(client_id, created_at)
```

Jangan membuat index semua kolom karena memperlambat write dan memboroskan storage.

## 7. Query Optimization Checklist

- Gunakan `select()` kolom yang diperlukan.
- Gunakan `with()` relasi yang ditampilkan.
- Gunakan `withCount()` daripada loop count manual.
- Hindari `->get()` sebelum filter selesai.
- Gunakan pagination/chunk/cursor sesuai use case.
- Jangan menjalankan query di view Blade.
- Jangan melakukan query di dalam loop.
- Gunakan `exists()` bila hanya butuh pengecekan keberadaan.
- Gunakan `value()` bila hanya butuh satu kolom.
- Gunakan aggregate SQL (`sum`, `count`) daripada kalkulasi seluruh collection di PHP.
- Periksa query lambat dengan `EXPLAIN`.

## 8. Select2 Performance

Untuk produk/karyawan:

- Endpoint AJAX.
- Parameter search.
- Filter client aktif.
- Filter status aktif.
- Limit 20-30 result/request.
- Pagination AJAX.
- Jangan mengirim seluruh daftar karyawan ke HTML awal.

Contoh response:

```json
{
  "results": [
    {"id": 12, "text": "000012345 - Andi Saputra"}
  ],
  "pagination": {"more": false}
}
```

## 9. Cache

Cocok untuk:

- Master satuan.
- Master group.
- Master shift aktif.
- Permission hasil komputasi bila mekanisme permission mendukung.
- KPI agregat yang tidak harus real-time per detik.

Cache key wajib mengandung client:

```text
client:{client_id}:active-shifts
client:{client_id}:dashboard:{date_range_hash}
```

Invalidate cache setelah master terkait berubah.

## 10. Queue

Gunakan queue untuk:

- Export laporan besar.
- Import data besar bila ditambahkan.
- Notifikasi/email bila ditambahkan.
- Kalkulasi snapshot payroll besar.

Web request cukup membuat job dan memberi status progress.

## 11. Transaction

Gunakan `DB::transaction()` untuk operasi multi-tabel:

- Create/update realisasi + assign employee.
- Finalisasi payroll.
- Bulk potongan.
- Perubahan data yang harus atomik.

## 12. Snapshot Historis

Master dapat berubah. Karena itu transaksi menyimpan snapshot penting:

- SKU.
- Nama produk.
- Satuan.
- Rate borongan.
- Kategori rate karyawan.

Tujuannya agar laporan lama tetap sama walaupun master produk/rate berubah.

## 13. Security Baseline

- Laravel CSRF protection.
- Form Request validation.
- Policy/Gate authorization.
- Tenant isolation.
- Rate limit login dan endpoint sensitif.
- Password hash default Laravel.
- Session regenerate setelah login.
- `APP_DEBUG=false` di production.
- Validasi MIME/size file upload.
- Generate nama file server-side.
- Jangan percaya extension file saja.
- Audit aksi sensitif.
- Hindari mass assignment field sensitif tanpa `$fillable`/DTO yang jelas.

## 14. File Upload

Untuk logo/client file:

- Validasi tipe: image yang disetujui.
- Validasi size, contoh maksimal 2 MB untuk logo.
- Random/UUID filename.
- Simpan path, bukan binary di database.
- Preview dilakukan di browser sebelum submit.
- Saat replace file, hapus file lama setelah transaksi berhasil bila aman.

## 15. Report Performance

Laporan gaji berpotensi menjadi query terberat.

### Fase Awal

Gunakan query aggregate ter-index berdasarkan:

- client.
- periode.
- employee.

### Bila Data Membesar

Gunakan snapshot payroll:

```text
payroll_runs
payroll_details
```

Saat periode dikunci, hitung sekali dan simpan hasil. Laporan kemudian membaca tabel summary, bukan menghitung seluruh histori setiap request.

## 16. Observability

Minimal production:

- Application error log.
- Slow query monitoring.
- Queue failed jobs.
- Disk usage.
- Database backup status.
- Health endpoint aplikasi.

## 17. Deployment Checklist

```text
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan strategi deployment tim kompatibel dengan route caching dan tidak ada closure route yang menghambat.

Queue worker dan scheduler harus berjalan terpisah dari web request.

## 18. Performance Target Praktis

Target berikut adalah target internal yang dapat disesuaikan setelah load test:

- Login: < 1.5 detik.
- DataTable umum: < 1 detik untuk query normal.
- Select2 AJAX: < 500-800 ms.
- Dashboard: < 2 detik, cache bila agregasi berat.
- Export besar: asynchronous, tidak menahan HTTP request panjang.

## 19. Definition of Performance Done

Fitur list/report belum dianggap selesai bila:

- masih N+1,
- belum tenant scoped,
- belum ada index untuk filter utama,
- masih memuat seluruh dataset ke browser,
- atau export besar masih dilakukan synchronous tanpa pertimbangan ukuran data.

## 20. Standar Teknis Keamanan OWASP

Seluruh ketentuan keamanan pada dokumen ini wajib mengacu pada `09_SECURITY_OWASP_SECURE_CODING.md`. Gunakan OWASP Top 10:2025 dan target usulan ASVS 5.0.0 Level 2. Security baseline pada Bagian 13 adalah daftar awal, bukan checklist yang lengkap atau bukti implementasi.

### Perubahan arsitektur yang wajib dipertimbangkan

- Tambahkan security review pada setiap PR; gunakan Pint, static analysis, dependency/secret scanning, dan feature/security tests.
- Controller memakai Form Request dan DTO/allowlist; Action/Service menghitung nominal dan transisi status. Client context, actor, dan permission harus eksplisit.
- Seluruh query termasuk raw query, Eloquent relation, DataTables counts, export, dan AJAX wajib tenant scoped. Validasi FK satu tenant; review composite FK dan tenant ownership pada child tables.
- Queue tidak mengandalkan session HTTP. Job membawa context tepercaya dan menguji ulang izin/service identity; worker jangka panjang tidak boleh menyimpan context tenant sebelumnya.
- Cache memakai client dan scope permission bila relevan. File dan export menggunakan private storage, ownership metadata, expiry, serta otorisasi saat download.
- Payroll memakai decimal arithmetic saat menghitung rate, menyimpan nominal rupiah final sebagai integer, snapshot, transaksi/locking, idempotency, serta koreksi/finalisasi berizin. Formula dan aturan pembulatan harus disetujui bisnis sebelum digunakan.
- SQL values menggunakan binding; nama kolom sorting menggunakan allowlist. Input user di Blade/DataTables/Select2/SweetAlert di-encode sesuai konteks; export spreadsheet aman dari formula injection.
- Production memakai hardened configuration, HTTPS, least privilege, secret management, audit redaction, monitoring/alerting, dan backup restore test.

### Catatan terhadap contoh query sebelumnya

`forClient(currentClientId())` adalah pola konseptual yang harus diimplementasikan. Jangan menganggap helper tersebut bawaan Laravel. Scope pada parent tidak otomatis menjamin seluruh relasi, raw SQL, child mutation, dan background job aman. Pastikan seluruh jalur terkait memeriksa tenant dan izin, serta gunakan constraint DB sebagai lapisan tambahan.

### Definition of Done tambahan

Performance test dan security test harus berjalan bersama. Optimasi yang melewati policy/tenant scope, men-cache data gaji lintas user tanpa izin, atau mengirim seluruh dataset sensitif ke browser tidak diperbolehkan walaupun lebih cepat. Fitur dinyatakan selesai setelah pengujian keamanan relevan, regression test, review, serta bukti ASVS tersedia sesuai gate yang disepakati.
