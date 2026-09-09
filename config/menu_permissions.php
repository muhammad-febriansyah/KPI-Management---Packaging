<?php

/**
 * Sidebar menu items that can be granted or revoked per role (excluding
 * Super Admin, who always has full access). Keys match the `key` used in
 * the navigation array in resources/views/components/layouts/app.blade.php
 * and are seeded as `menu.<key>` permission codes by AuthorizationSeeder.
 */
return [
    'dashboard' => 'Dashboard',
    'products' => 'Produk',
    'employees' => 'Karyawan',
    'realizations' => 'Realisasi',
    'deductions' => 'Potongan Gaji',
    'work-reports' => 'Hasil Pekerjaan',
    'reports' => 'Laporan Gaji',
];
