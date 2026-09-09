# User Goals - KPI Management Co-Packing

## 1. Super Admin PT SIM

### Goal Utama

Mengelola seluruh client dan memastikan aplikasi aman, terkontrol, serta dapat dipantau dari satu sistem.

### Kebutuhan

- Membuat dan mengaktifkan/nonaktifkan client.
- Membuat user dan memberikan akses ke client tertentu.
- Mengatur role dan permission.
- Berpindah client tanpa login ulang.
- Melihat audit log lintas client.
- Memastikan data antar-client tidak tercampur.

### Indikator Berhasil

- Tidak ada insiden data leakage antar-client.
- Akses user dapat dikontrol tanpa perubahan source code.
- Aktivitas sensitif dapat ditelusuri melalui audit log.

## 2. Admin/CRO

### Goal Utama

Menjaga data master dan administrasi co-packing tetap akurat serta siap digunakan untuk operasional dan laporan.

### Kebutuhan

- Mengelola karyawan.
- Mengelola produk dan master pendukung.
- Mengelola potongan gaji.
- Memeriksa realisasi.
- Mengunduh laporan dengan filter.
- Melakukan pekerjaan dengan cepat walaupun data banyak.

### Indikator Berhasil

- Input data tidak membutuhkan langkah yang membingungkan.
- Search/filter tabel cepat.
- Kesalahan data mudah ditemukan dan diperbaiki sesuai hak akses.

## 3. Supervisor / Atasan

### Goal Utama

Mencatat dan memastikan realisasi pekerjaan harian benar sebelum difinalisasi.

### Kebutuhan

- Memilih tanggal, shift, batch, dan produk dengan cepat.
- Assign beberapa karyawan tanpa reload panjang.
- Melihat rate/produk yang berlaku.
- Menambahkan report atau status komplain.
- Finalisasi data dengan konfirmasi yang jelas.
- Melihat performa tim per shift/periode.

### Indikator Berhasil

- Input realisasi dapat dilakukan dalam waktu singkat.
- Data final memiliki histori yang stabil.
- Kesalahan assignment karyawan berkurang.

## 4. Operator / Karyawan

### Goal Utama

Melakukan input operasional sederhana tanpa harus memahami proses administrasi yang kompleks.

### Kebutuhan

- Tampilan sederhana.
- Hanya melihat menu yang memang diperlukan.
- Pencarian SKU/NIK yang mudah.
- Pesan error menggunakan Bahasa Indonesia yang jelas.

### Indikator Berhasil

- User dapat menyelesaikan input tanpa bantuan teknis.
- Kesalahan validasi dapat langsung dipahami.

## 5. Client Viewer

### Goal Utama

Memantau hasil pekerjaan dan menerima laporan tanpa dapat mengubah data operasional.

### Kebutuhan

- Dashboard ringkas.
- Filter periode yang mudah.
- Laporan gaji sesuai kewenangan.
- Laporan hasil pekerjaan.
- Export data.

### Indikator Berhasil

- Client menemukan laporan yang dibutuhkan maksimal dalam beberapa langkah.
- Tidak ada akses edit yang tidak diperlukan.

## 6. Goal Sistem

- Data tenant terisolasi.
- Load tabel cepat.
- Query tidak menghasilkan N+1.
- Laporan dapat ditelusuri sampai sumber realisasi.
- UI konsisten pada seluruh halaman.
- Semua tindakan penting memiliki feedback sukses/gagal.
- Aksi berisiko selalu memiliki konfirmasi.

## 7. Tujuan Keamanan Tambahan

**Super Admin PT SIM:** Dapat mengelola akses dengan prinsip least privilege, melihat alert keamanan, mencabut akses user secara efektif, dan menelusuri perubahan penting tanpa membuka seluruh data pribadi yang tidak diperlukan. Indikator berhasil: seluruh akses lintas client terotorisasi dan dapat diaudit.

**Admin/CRO:** Dapat mengelola karyawan serta potongan dengan aman tanpa khawatir perubahan master mengubah histori payroll. Indikator berhasil: data sensitif hanya terlihat sesuai izin, transaksi final tidak berubah tanpa prosedur koreksi, dan file/export tidak dapat diakses user lain yang tidak berhak.

**Supervisor/Atasan:** Dapat memastikan realisasi benar dan tidak terproses dua kali ketika koneksi terganggu atau tombol finalisasi ditekan ulang. Indikator berhasil: status, perhitungan, dan audit konsisten saat terjadi error/concurrency.

**Client Viewer:** Dapat mengakses laporan client sendiri secara aman tanpa memperoleh data client lain atau kolom keuangan di luar kewenangannya. Indikator berhasil: filter, pencarian, serta unduhan selalu mengikuti akses aktual.

**Tech Lead/QA/Security Reviewer:** Memiliki standar coding, checklist ASVS, automated tests, proses review, dan bukti keamanan yang dapat ditelusuri sampai release. Indikator berhasil: risiko kritis diprioritaskan, temuan memiliki owner, dan rilis mengikuti security gate yang disepakati.
