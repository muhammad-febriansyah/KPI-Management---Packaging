# Standar Secure Coding & OWASP Top 10
## KPI Management Co-Packing — Laravel 13 Multi-Client

**Versi dokumen:** 1.0 — 7 September 2026  
**Status:** Standar yang diusulkan untuk implementasi; belum merupakan hasil audit source code.  
**Pemilik kontrol:** Tech Lead, Security Reviewer, QA, dan pemilik sistem PT SIM.  
**Cakupan:** Web Laravel 13, Blade, Tailwind CSS, DataTables server-side, SweetAlert, Datepicker, Select2, database, queue, storage, deployment, dan seluruh client.

## 1. Dasar, ruang lingkup, dan tingkat keamanan

BRD asli mendefinisikan keamanan data, pengendalian akses, audit trail, dan infrastruktur sesuai kebijakan PT SIM, tetapi tidak menentukan standar OWASP atau kontrol teknis terperinci. Seluruh ketentuan di bawah merupakan penjabaran dan usulan tambahan, bukan klaim bahwa kontrol sudah diterapkan.

Acuan risiko adalah **OWASP Top 10:2025**. Top 10 merupakan dokumen kesadaran risiko dan tidak cukup sebagai satu-satunya standar coding atau sertifikasi. Gunakan **OWASP ASVS 5.0.0 Level 2 sebagai target verifikasi yang diusulkan** karena aplikasi memproses data karyawan dan penggajian. Level 3 dapat diterapkan pada kontrol berisiko tinggi berdasarkan penilaian keamanan PT SIM. Scope dan pengecualian ASVS harus disetujui, lalu dilacak dengan requirement ID versi yang tepat. Lengkapi dengan OWASP Secure Coding Practices, Cheat Sheet Series, dan pengujian keamanan bisnis.

**Prinsip wajib:** deny by default, least privilege, defense in depth, secure by default, validasi di server, pemisahan data client, fail closed, dan tidak mempercayai input browser. HTTPS, WAF, dan framework bukan pengganti pemeriksaan izin atau keamanan logika bisnis.

## 2. Matriks OWASP Top 10:2025

| Kode | Kategori resmi | Risiko pada Co-Packing | Kontrol dan bukti minimal |
|---|---|---|---|
| A01 | Broken Access Control | IDOR, client A membaca gaji client B, operator mengubah role, SSRF pada integrasi URL | Policy per aksi, tenant scope dan relasi tervalidasi, field-level authorization, pengujian lintas client, egress/URL allowlist bila integrasi ditambahkan. |
| A02 | Security Misconfiguration | Debug aktif, file privat terekspos, sesi tidak aman, endpoint dev terbuka | Konfigurasi production hardened, HTTPS, cookie aman, header keamanan, least privilege, pemisahan environment, pemeriksaan deployment. |
| A03 | Software Supply Chain Failures | Dependency Composer/npm rentan, paket tak tepercaya, CI/release disusupi | Lockfile, audit dependency, review paket, update terjadwal, CI berizin minimal, provenance/artifact integrity, SBOM bila diwajibkan. |
| A04 | Cryptographic Failures | Password plaintext, data gaji bocor, secret di repo, backup tidak terlindungi | Hash password adaptif, TLS, enkripsi data sensitif yang diperlukan, secret manager, key rotation, backup terenkripsi. |
| A05 | Injection | SQL injection di pencarian/sort, XSS di report/DataTables, formula injection pada Excel | Parameter binding, allowlist identifier SQL, encoding sesuai konteks, safe DOM APIs, sanitasi rich text yang disetujui, pengamanan export spreadsheet. |
| A06 | Insecure Design | Manipulasi rate, pembagian borongan salah, finalisasi race condition, bypass approval | Threat modeling, invariants bisnis, server-side calculation, transaction/lock/idempotency, state machine, review desain sebelum coding. |
| A07 | Authentication Failures | Brute force, session fixation, reset password lemah, akun admin diambil alih | Auth standar Laravel, throttling, MFA admin sesuai kebijakan, session regeneration, reset token aman, re-auth aksi kritis, revoke session. |
| A08 | Software or Data Integrity Failures | Import/backup tak tepercaya, payload queue dimanipulasi, histori payroll berubah tanpa otorisasi | Validasi integritas, signed/verified artifacts, proses koreksi terkontrol, snapshot immutable, audit, tidak menjalankan kode/data tak tepercaya. |
| A09 | Security Logging & Alerting Failures | Akses gaji ilegal tidak diketahui, perubahan role tidak terdeteksi | Audit keamanan terstruktur, redaksi data, alert, review dan eskalasi, retensi, akses log terbatas, uji respons insiden. |
| A10 | Mishandling of Exceptional Conditions | Exception membuka debug, kegagalan parsial menggandakan gaji, sistem fail open | Central exception handling, fail closed, rollback, retry aman, timeout, batas resource, error publik generik, pengujian failure path. |

**Catatan edisi:** SSRF merupakan bagian A01 pada 2025. Kategori A03 dan A10 berubah dibandingkan edisi 2021. Jangan mencampur nomor kategori dari edisi yang berbeda dalam audit atau tasklist.

## 3. Standar pemrograman tim

### 3.1 Struktur dan kualitas kode

- Gunakan PHP 8.3+ yang kompatibel dengan Laravel 13 dan versi yang disetujui PT SIM; periksa requirement runtime dan support/security update sebelum deployment. Targetkan PHP 8.4 jika lingkungan perusahaan mengizinkan.
- File PHP baru menggunakan `declare(strict_types=1);` bila sesuai, type hints, return types, PSR-12, Laravel Pint, dan penamaan yang konsisten.
- Controller hanya menangani HTTP orchestration. Form Request menangani validasi/otorisasi awal; Policy izin resource; Action/Service logika bisnis; Query Object/Scope query kompleks; Job proses background.
- Jangan menaruh query, perhitungan gaji, atau keputusan izin di Blade/JavaScript. Jangan menggandakan aturan payroll dalam beberapa controller.
- Semua input HTTP dianggap tidak tepercaya, termasuk hidden input, query string, JSON, DataTables parameters, Select2 IDs, dan nilai readonly/disabled.
- Gunakan `$request->validated()` atau `safe()->only()` lalu DTO/allowlist eksplisit. Jangan `Model::create($request->all())`, `update($request->all())`, atau memakai `$guarded = []` pada model sensitif tanpa kontrol yang diaudit.
- `client_id`, `user_id`, `role_id`, `status` final, `gross_amount`, snapshot rate, dan kolom audit ditentukan server sesuai kewenangan, bukan disalin dari payload pengguna.
- Hindari `eval`, dynamic code execution, `unserialize` pada input tak tepercaya, command shell yang dibangun dari string input, dan bypass keamanan framework tanpa justifikasi/review.
- Semua perubahan schema menggunakan migration; jangan mengubah production database secara manual tanpa change record, backup, dan rollback plan.

### 3.2 Review dan repository

- Branch dilindungi; perubahan production melalui Pull Request dan review minimal satu developer lain. Perubahan auth, tenant, payroll, secrets, dan deployment memerlukan reviewer yang memahami kontrol tersebut.
- PR menjelaskan risiko, perubahan izin, migrasi, pengaruh tenant, pengujian, dan rollback. Jangan menambahkan `@csrf` exclusion, `withoutGlobalScopes`, `Gate::before`, raw SQL, atau suppression scanner tanpa alasan yang terdokumentasi.
- Jalankan Pint, static analysis (misalnya Larastan), unit/feature tests, dependency audit, dan secret scanning dalam CI. Versi alat dipin dan ditinjau kompatibilitasnya.
- Tidak boleh commit `.env`, credential, private key, database dump production, file gaji nyata, atau token. Gunakan data sintetis dan `.env.example` tanpa secret.

## 4. Authentication dan session — A07

Gunakan mekanisme autentikasi Laravel yang didukung, bukan hashing/login buatan sendiri. Password disimpan dengan `Hash::make()` memakai algoritma adaptif yang dikonfigurasi; jangan MD5/SHA-256 biasa sebagai password hash. Verifikasi dengan `Hash::check` melalui provider yang sesuai. Password tidak pernah dikirim kembali dalam response atau audit.

- Login memakai pesan generik, throttle per akun dan IP dengan batas yang disetujui; hindari user enumeration dan lockout yang dapat dipakai menyerang akun orang lain.
- Regenerate session setelah login; invalidate session dan regenerate CSRF token pada logout. Gunakan session server-side yang sesuai, HTTPS, `Secure`, `HttpOnly`, dan kebijakan `SameSite` yang aman.
- Tetapkan idle timeout dan absolute timeout sesuai kebijakan perusahaan; re-auth untuk perubahan password, role, client privileges, dan koreksi payroll berisiko tinggi.
- MFA wajib bagi Super Admin dan akun keuangan/administratif berisiko tinggi jika kebijakan dan infrastruktur mendukung; desain enrollment, recovery, dan reset MFA harus diaudit. Jangan mengklaim MFA telah tersedia hanya karena dicantumkan dalam PRD.
- Reset password menggunakan token acak, sekali pakai, masa berlaku pendek, respons generik, dan throttling. Revoke session/token terkait setelah reset atau penonaktifan akun sesuai kebijakan.
- Akun nonaktif, mapping client dicabut, atau role berubah harus segera kehilangan akses efektif; jangan hanya menunggu user login kembali.
- Akun service menggunakan identitas terpisah dan izin minimal; tidak boleh memakai kredensial Super Admin interaktif.

## 5. Authorization dan isolasi multi-client — A01

### 5.1 Context dan izin

Arsitektur tetap **shared database + `client_id`**, dengan akses user melalui `client_user` dan role per client. `CurrentClientService` adalah abstraksi aplikasi yang harus diimplementasikan serta diuji; bukan API bawaan Laravel.

1. Authenticate user dan pastikan akun aktif.
2. Resolve client aktif dari session/context yang sah, bukan nilai bebas dari request.
3. Periksa mapping user-client yang aktif, status client, dan role/permission yang berlaku pada setiap request.
4. Terapkan scope tenant sebelum mengambil resource. Gunakan Policy untuk aksi dan kepemilikan resource.
5. Validasi seluruh ID relasi (karyawan, produk, batch, shift, cost center, periode) milik client yang sama dan status yang diperbolehkan.
6. Untuk operasi lintas client milik Super Admin, gunakan jalur/service eksplisit dengan pemeriksaan izin dan audit. Jangan membuat pengecualian global yang tanpa sengaja melewati tenant scope.
7. Gunakan response 403 atau 404 sesuai kebijakan disclosure; jangan membocorkan keberadaan data client lain melalui pesan kesalahan.

### 5.2 Contoh akses resource Laravel

Contoh ini mengasumsikan `CurrentClientService::id()` dan `EmployeePolicy` telah diimplementasikan. Jangan menyalin contoh tanpa menyesuaikan model, relasi, dan policy proyek.

```php
public function show(int $id, CurrentClientService $context)
{
    $employee = Employee::query()
        ->where('client_id', $context->id())
        ->with('group:id,name')
        ->findOrFail($id);

    Gate::authorize('view', $employee);

    return view('employees.show', compact('employee'));
}
```

Policy wajib memeriksa role pada client aktif dan kepemilikan resource. Middleware saja tidak cukup. Untuk child relation, jangan melakukan `Employee::findOrFail($request->employee_id)` secara global atau menganggap ID yang valid pasti berasal dari client yang benar.

```php
use Illuminate\Validation\Rule;

'employee_id' => [
    'required', 'integer',
    Rule::exists('employees', 'id')->where(fn ($q) =>
        $q->where('client_id', $context->id())
          ->where('status', 'active')
    ),
],
```

Periksa juga bahwa employee yang dipilih benar-benar boleh mengikuti transaksi dan tidak melanggar aturan bisnis. Untuk operasi multi-ID, validasi seluruh anggota array, bukan hanya ID pertama.

### 5.3 Pertahanan lapisan database

- Semua tabel tenant dan detail transaksi penting harus memiliki tenant ownership yang dapat ditegakkan. Review penambahan `client_id` pada child tables dan composite FK agar parent-child tidak bisa berasal dari client berbeda.
- Gunakan unique constraint dan foreign key sesuai aturan bisnis; `UNIQUE(client_id, sku)` dan `UNIQUE(client_id, employee_no)` tetap berlaku.
- Untuk relasi tenant, pertimbangkan `UNIQUE(client_id,id)` pada parent dan FK `(client_id, parent_id)` ke `(client_id,id)`; pastikan urutan, tipe, dan engine mendukungnya. Tetap gunakan PK internal `id` bila dibutuhkan ORM.
- Jangan mengandalkan global scope saja: raw query, relation sync, jobs, import, dan `withoutGlobalScopes()` harus direview. DB constraints melindungi integritas walaupun jalur aplikasi salah.
- Super Admin bukan berarti otomatis bebas mengakses semua field gaji; izin global dan izin data sensitif harus dipisahkan sesuai kebijakan bisnis.

### 5.4 Background job, cache, dan file

- Job menyimpan `client_id` tepercaya, actor_id, jenis aksi, dan resource/filter yang diperlukan. Saat eksekusi, resolve ulang client/context secara eksplisit serta periksa izin atau gunakan service identity terbatas yang disetujui.
- Jangan membaca tenant dari session browser di queue worker. Jangan menyimpan konteks mutable statis yang bocor antar-job pada worker jangka panjang; reset scope/context setelah job.
- Cache key mencakup client, cakupan izin/user bila hasilnya berbeda, filter, serta versi data relevan. Hindari cache global yang mengembalikan laporan gaji client lain.
- Storage memakai namespace tenant dan disk privat untuk dokumen sensitif. Download harus melalui route yang mengautentikasi, mengotorisasi, dan memeriksa kepemilikan file. Signed URL hanya untuk akses terbatasi dengan expiry dan tidak menggantikan kebijakan izin.
- Export yang dibuat harus terikat pada pemilik/request/client/filter. Periksa kembali akses saat job berjalan dan saat file diunduh, termasuk bila user telah dicabut dari client.
- Perubahan client aktif harus mencegah stale response/tab/cache mengirimkan aksi ke tenant yang salah. Tampilkan nama client aktif pada form/transaksi dan konfirmasi aksi kritis.

## 6. Validasi, SQL injection, XSS dan komponen frontend — A05

### 6.1 Validasi server dan mass assignment

Gunakan Form Request untuk setiap endpoint tulis dan parameter query yang berisiko. Validasi tipe, panjang, enum, tanggal, batas angka, jumlah item array, keunikan per client, status relasi, dan aturan lintas field. Validasi browser hanya meningkatkan UX dan bukan kontrol keamanan. `nullable` tidak berarti nilai boleh bebas. Hindari negative amount kecuali aturan koreksi secara eksplisit mengizinkannya.

```php
public function rules(): array
{
    return [
        'sku' => ['required', 'string', 'max:100'],
        'name' => ['required', 'string', 'max:180'],
        'unit_id' => ['required', 'integer'],
        'estimated_output_per_hour' => ['nullable', 'integer', 'min:0'],
    ];
}
```

Contoh di atas belum lengkap: validasi `unit_id` wajib ditambah constraint client dan status sesuai bagian 5, dan SKU unique per client pada request create/update.

### 6.2 Query database dan DataTables

Gunakan Eloquent/Query Builder dengan binding untuk **nilai**. Jangan menggabungkan input ke raw SQL. Parameter binding tidak bisa mengamankan nama kolom, nama tabel, atau arah sorting; gunakan allowlist server.

```php
$columns = [
    'employee_no' => 'employees.employee_no',
    'full_name' => 'employees.full_name',
    'join_date' => 'employees.join_date',
];

$key = $request->input('sort', 'full_name');
$column = $columns[$key] ?? $columns['full_name'];
$direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

$query->orderBy($column, $direction);
```

Untuk DataTables server-side, mapping `columns`, `order`, dan `search` harus server-controlled. Batasi `length`, `start`, pencarian, rentang tanggal, dan jumlah filter; jangan biarkan `length=-1` mengambil seluruh database tanpa kebijakan. Hitung total dan filtered count pada scope tenant/izin yang sama. Kolom sensitif hanya dipilih bila role berhak, bukan sekadar disembunyikan dari frontend. Batasi sort/search pada kolom yang sah, dan gunakan index/EXPLAIN untuk efisiensi. Pencarian LIKE berawalan `%` tidak otomatis menggunakan index B-tree secara efisien; gunakan strategi search yang sesuai skala.

### 6.3 Output encoding, Blade, dan JavaScript

- Gunakan `{{ $value }}` untuk teks Blade; hindari `{!! $untrusted !!}`. Raw HTML hanya untuk markup yang dibangun oleh kode tepercaya atau konten yang disanitasi dengan kebijakan ketat.
- Jangan memasukkan data pengguna melalui string concatenation ke `<script>`, HTML attribute, URL, CSS, atau event handler. Gunakan JSON encoder resmi dan API DOM yang sesuai konteks.
- DataTables: render field user sebagai text/escaped output. Jika kolom aksi berisi HTML, markup dibangun server dari nilai terkontrol dan diotorisasi; jangan mencampur string HTML tidak tepercaya.
- Select2: gunakan template text yang aman; jangan mengembalikan HTML mentah dari nama produk/karyawan. Review custom `templateResult`, `templateSelection`, dan `escapeMarkup` sebelum mengubah default.
- SweetAlert: gunakan properti `text` untuk pesan yang mengandung input user; `html` hanya untuk markup statis/tepercaya yang telah ditinjau.
- Datepicker: validasi ulang tanggal dan range pada server; format tampilan tidak menjadi sumber kebenaran. Hindari parsing tanggal ambigu dan periksa timezone client.
- Jika rich text diperlukan, gunakan sanitizer allowlist yang terpelihara dan kebijakan yang ketat; jangan membuat sanitizer hanya dengan regex.
- Terapkan Content Security Policy secara bertahap dan hindari inline script/eval bila memungkinkan. Sesuaikan Vite/Blade dan library yang digunakan tanpa melonggarkan seluruh origin.

### 6.4 Export spreadsheet dan dokumen

- Semua export memeriksa permission, tenant, field yang boleh dilihat, dan filter terotorisasi.
- Data tak tepercaya yang diawali karakter formula spreadsheet (`=`, `+`, `-`, `@`, serta variasi whitespace/control characters) harus ditangani agar tidak dieksekusi sebagai formula. Gunakan cell type string yang eksplisit dan mekanisme proteksi library export yang sesuai; uji saat file dibuka pada aplikasi spreadsheet target.
- Jangan mengekspor data sensitif ke formula, hyperlink, atau HTML aktif tanpa kebutuhan yang disetujui. Sanitasi nama file dan metadata export.
- Export besar menggunakan queue/chunking atau streaming terkontrol; file sementara dan hasilnya disimpan privat, memiliki expiry/retention, dan tidak dibagikan antar-client.
- PDF/HTML report wajib menggunakan encoding yang benar; jangan memuat resource eksternal yang dapat dipengaruhi input tanpa validasi.

## 7. Secure file upload dan SSRF — A01/A05

Komponen UI tetap mendukung drag/drop, preview, ganti/hapus file, serta pesan Bahasa Indonesia. Preview bukan validasi keamanan.

- Batasi tipe file per use case dan ukuran maksimal (contoh logo 2 MB), jumlah file, dimensi gambar, serta total ukuran request.
- Validasi ekstensi allowlist dan MIME/content sebenarnya. Jangan percaya `Content-Type`, nama file, atau ekstensi dari browser saja. Tolak executable/script, polyglot berbahaya, dan format yang tidak dibutuhkan.
- Nama storage dibuat server menggunakan UUID/random. Jangan memakai nama asli sebagai path, jangan izinkan path traversal, dan jangan simpan upload di lokasi yang dapat dieksekusi sebagai PHP.
- Simpan file sensitif di disk privat; gunakan akses download yang memeriksa tenant dan policy. File publik seperti logo tetap harus disanitasi/re-encode bila diperlukan dan disajikan dengan content type yang aman. SVG tidak diizinkan secara default untuk upload logo karena risiko active content.
- Preview browser memakai object URL yang dibersihkan ketika tidak diperlukan; jangan menyisipkan file sebagai HTML tidak tepercaya. Preview PDF atau dokumen harus menggunakan mekanisme viewer aman sesuai kebijakan.
- Jika melakukan antivirus scanning, image re-encoding, atau content disarm, gunakan service terisolasi dan proses karantina sampai file lolos pemeriksaan. Jangan menganggap `mimes` saja cukup untuk semua jenis file.
- File replacement/deletion menggunakan ownership check dan proses yang konsisten dengan DB transaction serta cleanup setelah commit. Jangan menghapus file milik tenant lain atau histori final.
- Jika kelak menerima URL eksternal (import URL, webhook, HTTP client, logo URL), terapkan allowlist domain/protocol, validasi DNS dan IP tujuan, blokir loopback/private/link-local/metadata service yang tidak disetujui, batasi redirect dan response size/time, serta gunakan network egress restriction. Jangan membuat fitur fetch URL arbitrer sebagai pengganti upload tanpa threat model.

## 8. Integritas bisnis, payroll, dan kondisi abnormal — A06/A08/A10

### 8.1 Invariants yang tidak boleh dilanggar

- Nilai rate dan kategori rate diambil dari master yang sah serta disimpan sebagai snapshot. Browser tidak dapat mengubah nilai snapshot langsung.
- Formula pembagian hasil untuk banyak karyawan, satuan, komplain, total hari masuk, dan BPJS menunggu persetujuan business owner; jangan mengasumsikan pembagian rata atau tarif tanpa dasar.
- Simpan nominal rupiah utuh sebagai `BIGINT UNSIGNED`, simpan rate/persentase pecahan sebagai `DECIMAL(18,3)`, dan gunakan decimal arithmetic yang presisi saat menghitung; jangan mengandalkan floating point binary untuk uang. Aturan pembulatan dan penanganan selisih harus disetujui.
- Jumlah alokasi hasil tidak boleh melampaui output yang diotorisasi menurut aturan bisnis. Satu karyawan tidak boleh diduplikasi pada realisasi yang sama. Relasi produk/batch/shift/karyawan harus satu tenant.
- Finalisasi hanya dilakukan dari status yang sah, oleh role yang berizin, setelah validasi ulang seluruh data. Status final dan financial snapshot tidak dapat diubah melalui CRUD biasa.
- Koreksi/reversal melalui workflow terpisah, alasan wajib, jejak actor/waktu, dan approval bila diperlukan. Data histori harus tetap dapat ditelusuri.
- Periode payroll yang telah dikunci tidak dapat dihitung ulang secara diam-diam menggunakan master rate terbaru. Perubahan memerlukan versi/koreksi yang dapat diaudit.

### 8.2 Transaction, concurrency, dan idempotency

Gunakan `DB::transaction()` untuk operasi multi-tabel dan `lockForUpdate()` pada row yang tepat ketika ada risiko race condition. Periksa status kembali **di dalam** transaction. Tambahkan unique constraint bisnis dan idempotency key untuk aksi yang berpotensi diproses ulang. Batas retry harus aman dan tidak menggandakan gaji/potongan. Jangan melakukan external network call yang lama di dalam DB transaction bila dapat dipisahkan.

```php
DB::transaction(function () use ($id, $context) {
    $realization = WorkRealization::query()
        ->where('client_id', $context->id())
        ->whereKey($id)
        ->lockForUpdate()
        ->firstOrFail();

    Gate::authorize('finalize', $realization);

    if ($realization->status !== 'draft') {
        throw ValidationException::withMessages([
            'status' => 'Realisasi ini tidak dapat difinalisasi.',
        ]);
    }

    // Validasi ulang relasi, perhitungan, dan aturan bisnis yang disetujui.
    // Simpan snapshot dan audit secara atomik melalui Action/Service.
});
```

Contoh sengaja tidak mengarang formula payroll. Implementasi finalisasi harus menyertakan seluruh invariant, audit, dan transisi status yang disetujui.

### 8.3 Exceptional conditions

- Error validation memberikan pesan aman berbahasa Indonesia. Error internal tidak mengirim stack trace, SQL, path server, credential, atau detail tenant lain.
- Kegagalan otorisasi, context tenant, key management, atau dependency kritis harus fail closed; tidak boleh fallback ke client pertama, role admin, atau seluruh data.
- Konfigurasi timeout, retry terbatas, backoff, max attempts, job uniqueness/idempotency, dan dead-letter/failed-job handling harus ditetapkan.
- Penanganan error jangan menelan exception lalu mengembalikan sukses. Gunakan transaksi/rollback dan status yang jelas untuk kegagalan parsial.
- Uji koneksi DB terputus, queue retry, double-click finalisasi, dua user mengedit bersamaan, timeout export, cache unavailable, file upload gagal, dan client dinonaktifkan saat job berjalan.
- Batasi ukuran request, pagination, concurrency, durasi job, memory, dan resource-intensive queries untuk mencegah penyalahgunaan layanan.

## 9. Konfigurasi, kriptografi, dan supply chain — A02/A03/A04

### 9.1 Production hardening

- `APP_ENV=production`, `APP_DEBUG=false`; konfigurasi secret melalui mekanisme aman. Jangan commit production `.env`.
- Gunakan HTTPS modern, redirect HTTP ke HTTPS, HSTS setelah validasi kesiapan domain, dan cookie `Secure`, `HttpOnly`, `SameSite` sesuai alur auth.
- Terapkan header keamanan yang relevan seperti CSP, `X-Content-Type-Options: nosniff`, frame policy, dan referrer policy; uji agar tidak merusak form/Select2/SweetAlert.
- Konfigurasi trusted proxies/hosts secara eksplisit sesuai infrastruktur; jangan mempercayai header proxy dari internet tanpa pembatasan sumber.
- Tutup akses ke `.env`, `.git`, storage privat, log, dump DB, dan direktori selain public web root. Matikan directory listing dan semua endpoint dev/test yang tidak diperlukan.
- DB account aplikasi hanya memiliki privilege runtime minimum. Migration menggunakan akun terpisah bila kebijakan memungkinkan. Service DB/cache/queue tidak diekspos publik tanpa kebutuhan yang sah.
- Pisahkan environment development, staging, production; gunakan data sintetis/anonymized di non-production. Retensi dan penghapusan data mengikuti kebijakan perusahaan.

### 9.2 Password, encryption, dan secret

- Gunakan fasilitas Hash/Crypt Laravel yang didukung. Jangan menyimpan password atau secret dengan enkripsi reversible sebagai pengganti hashing password.
- Klasifikasikan data pribadi dan data keuangan. Terapkan encryption at rest untuk field sensitif yang diperlukan oleh kebijakan/risiko, serta enkripsi backup dan volume/storage sesuai standar perusahaan.
- `APP_KEY` dan encryption keys dijaga terpisah dari source code. Rancang key rotation dan pemulihan data terenkripsi; jangan mengganti key production tanpa rencana karena dapat membuat data/sesi lama tidak terbaca.
- Jangan log password, token, secret, session ID, private key, atau nilai gaji lengkap tanpa alasan/audit yang disetujui. Terapkan masking/redaction dan minimum data pada response.
- Secret CI/deployment memiliki scope minimum dan rotasi; gunakan secret manager yang disetujui. Jangan membagikan credential antar-environment.

### 9.3 Dependency dan CI/CD

- Commit `composer.lock` dan lockfile frontend; gunakan instalasi deterministik (`composer install`, `npm ci` bila npm digunakan), bukan dependency update tak terkendali saat deploy.
- Audit Composer/npm dan periksa advisories pada setiap PR/release serta secara berkala. Tetapkan SLA remediasi berbasis severity dan exploitability dengan persetujuan PT SIM.
- Pin versi dependency yang kompatibel dan masih mendapat dukungan; review reputasi, maintainer, lisensi, serta izin paket baru. Batasi script install/build dari sumber tak tepercaya.
- CI menggunakan permissions minimum, protected secrets, review perubahan workflow, dan artifact provenance/signing bila infrastruktur mendukung. Deploy hanya artifact hasil pipeline tepercaya.
- Hasil scan tidak boleh dinyatakan “aman 100%”. Temuan High/Critical yang relevan harus diperbaiki sebelum rilis atau memperoleh risk acceptance tertulis, time-bound, dan mitigasi yang dapat diuji.
- Simpan daftar dependency/SBOM sesuai kebutuhan audit dan kebijakan perusahaan; jangan menambahkan service eksternal tanpa persetujuan data/security.

## 10. Audit, alerting, dan operasi — A09

Audit operasional yang sudah dirancang perlu dipisahkan secara logis dari log keamanan. Catat peristiwa penting: login gagal/berhasil, logout, lockout, reset/MFA, perubahan role/client access, akses ditolak, perubahan data sensitif, finalisasi/koreksi payroll, ekspor gaji, unduhan file privat, dan perubahan konfigurasi penting.

### Data log yang disarankan

`event_id`, timestamp UTC, request/correlation ID, actor_id, client_id, action, resource type/id, outcome, reason code, IP, user agent yang dibatasi, dan metadata minimum yang diperlukan. Jangan menyimpan seluruh request body atau old/new JSON tanpa redaksi; gunakan allowlist field audit. NIK, data gaji, password, token, dan informasi pribadi harus dibatasi sesuai klasifikasi data.

- Audit log bersifat append-only secara aplikasi; user biasa tidak boleh mengubah/menghapusnya. Akses auditor dibatasi dan dicatat.
- Gunakan penyimpanan/forwarding log yang sesuai kebijakan dengan perlindungan integritas, retensi, backup, dan pemisahan akses. Perubahan administrator terhadap log harus dapat diketahui.
- Buat alert untuk percobaan akses lintas client, lonjakan login gagal, perubahan privilege, export gaji tidak lazim, kegagalan job payroll, dan perubahan keamanan yang kritis.
- Tetapkan pemilik alert, kanal eskalasi, tingkat severity, waktu respons, dan prosedur incident response. Lakukan uji alert dan simulasi insiden.
- Backup database dan file dengan jadwal, retensi, enkripsi, pembatasan akses, pemantauan kegagalan, dan restore test berkala. RPO/RTO harus disepakati bisnis, bukan diasumsikan.

## 11. Pengujian keamanan dan gate rilis

### 11.1 Pengujian wajib

| Area | Skenario minimum | Bukti |
|---|---|---|
| Tenant isolation | User A mencoba membaca, mengubah, menghapus, mencari, dan export data B melalui ID/query/body/route | Feature tests 403/404, tidak ada data B. |
| Relation ownership | Assign employee/product/batch dari client lain dan manipulasi array IDs | Validation/authorization gagal; tidak ada perubahan DB. |
| Privilege escalation | Operator memanggil endpoint admin, mengirim `role_id`, `is_super_admin`, `client_id`, status final dan nominal gaji | Field ditolak/diabaikan aman; aksi tidak sah gagal. |
| SQL injection | Search/filter/sort DataTables dan Select2 dengan input berbahaya | Query binding/allowlist, tidak ada SQL execution tak sah. |
| XSS | Nama produk/karyawan/report mengandung payload HTML/JS pada Blade, DataTables, Select2, SweetAlert | Tampil sebagai teks atau disanitasi aman, script tidak dieksekusi. |
| Authentication | Brute force, session fixation, logout, reset token expired/replay, akun nonaktif, pencabutan role | Tidak ada bypass dan sesi invalid sesuai kebijakan. |
| CSRF | Request state-changing tanpa token sah; pengecualian route ditinjau | Ditolak; tidak ada pengecualian luas tanpa alasan. |
| Upload | MIME palsu, ekstensi ganda, SVG/script, ukuran berlebih, path traversal, download client lain | Ditolak atau disajikan aman; file privat tidak bocor. |
| Spreadsheet | Input berawalan formula dan variasi kontrol whitespace pada XLSX/CSV | Cell diperlakukan sebagai data, bukan formula berbahaya. |
| Payroll integrity | Manipulasi rate, dua finalisasi bersamaan, retry job, update setelah locked | Tidak ada duplikasi/ubah histori; audit dan idempotency benar. |
| Queue/cache | Context tenant berbeda dalam worker yang sama, akses user dicabut, stale cache, export expired | Tidak ada cross-tenant leakage atau unauthorized download. |
| Error/failure | DB timeout, queue failure, invalid context, missing secret, backup/restore | Fail closed, rollback/retry aman, error publik tidak membocorkan detail. |

### 11.2 Pipeline minimum

```text
PR / Commit
  -> Pint / formatting
  -> Static analysis
  -> Unit + feature tests
  -> Dependency audit + secret scan
  -> Security-sensitive code review
  -> Build artifact tepercaya
  -> Staging + DAST terotorisasi
  -> UAT / security sign-off
  -> Production deployment + smoke test
```

DAST dan penetration test hanya dilakukan pada environment/aset yang diotorisasi. Gunakan test data sintetis dan jangan menjalankan pemindaian destruktif pada production tanpa izin formal. Setiap temuan memiliki owner, severity, deadline, bukti perbaikan, dan retest. Target ASVS Level 2 harus dibuktikan dengan checklist requirement yang berlaku, bukan sekadar menyatakan “sudah mengikuti OWASP”.

### 11.3 Definition of Security Done

Sebuah fitur baru boleh dinyatakan selesai jika validasi, authentication/authorization, tenant scope, relation ownership, least privilege, output encoding, audit, dan failure behavior yang relevan telah direview dan diuji. Test kritis lulus; tidak ada kerentanan High/Critical terbuka tanpa risk acceptance yang sah; dependency/secret checks berjalan; performance dan keamanan tidak saling dikorbankan; dokumentasi serta bukti review tersimpan. Release production memerlukan sign-off dari pihak yang ditetapkan PT SIM.

## 12. Prioritas implementasi

**P0 — Sebelum data nyata:** secure auth/session, permission per client, tenant context, scoped queries/relations, database ownership constraints, server-side validation, safe output/SQL, secrets dan production hardening, audit minimal, test isolasi tenant, serta rollback/backup. Semua fitur CRUD harus dibangun di atas foundation ini.

**P1 — Sebelum operasional/payroll:** integrity invariants, rate snapshot, transaction/lock/idempotency, finalisasi dan koreksi berizin, export/file security, logging/alerting, serta test failure paths dan concurrency.

**P2 — Sebelum go-live lengkap:** ASVS checklist, automated security gates, DAST/review independen yang disetujui, restore test, incident response, monitoring, dependency remediation, dan security sign-off.

**P3 — Peningkatan berkelanjutan:** threat-model review pada fitur baru, security regression tests, hardening tambahan, exercise insiden, peninjauan akses, dan pembaruan standar berdasarkan versi terbaru yang diadopsi perusahaan.

## 13. Referensi resmi

- [OWASP Top 10:2025](https://owasp.org/Top10/2025/0x00_2025-Introduction/)
- [OWASP — Establishing a Modern Application Security Program](https://owasp.org/Top10/2025/0x03_2025-Establishing_a_Modern_Application_Security_Program/)
- [OWASP Application Security Verification Standard (ASVS)](https://owasp.org/www-project-application-security-verification-standard/)
- [OWASP Secure Coding Practices](https://owasp.org/www-project-secure-coding-practices-quick-reference-guide/)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)
- [OWASP Web Security Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- [Laravel 13 — Authentication](https://laravel.com/docs/13.x/authentication)
- [Laravel 13 — Authorization](https://laravel.com/docs/13.x/authorization)
- [Laravel 13 — Validation](https://laravel.com/docs/13.x/validation)
- [Laravel 13 — Database Query Builder](https://laravel.com/docs/13.x/queries)
- [Laravel 13 — CSRF Protection](https://laravel.com/docs/13.x/csrf)
- [Laravel 13 — Hashing](https://laravel.com/docs/13.x/hashing)
- [Laravel 13 — Encryption](https://laravel.com/docs/13.x/encryption)
- [Laravel 13 — File Storage](https://laravel.com/docs/13.x/filesystem)
- [Laravel 13 — Queues](https://laravel.com/docs/13.x/queues)
- [Laravel 13 — Deployment](https://laravel.com/docs/13.x/deployment)

## 14. Status implementasi

Dokumen ini telah menyusun standar yang harus diterapkan. Tidak ada source code/repository yang diberikan untuk menguji aplikasi. Karena itu seluruh kontrol teknis tetap berstatus **Belum dikerjakan / Perlu diverifikasi** sampai bukti implementasi dan pengujian tersedia. Dokumen ini tidak menyatakan sertifikasi, audit lulus, atau keamanan aplikasi terjamin sepenuhnya.
