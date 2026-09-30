<?php

/**
 * Sidebar menu items that can be granted or revoked per role (excluding
 * Super Admin, who always has full access). Keys match the `key` used in
 * the navigation array in resources/views/components/layouts/app.blade.php
 * and are seeded as `menu.<key>` permission codes by AuthorizationSeeder.
 */
return [
    'dashboard' => 'Dashboard',
    'units' => 'Satuan',
    'groups' => 'Group',
    'cost-centers' => 'Cost Center',
    'products' => 'Produk',
    'employees' => 'Karyawan',
    'shifts' => 'Master Shift',
    'realizations' => 'Realisasi',
    'invoices' => 'Invoice Borongan',
    'my-payroll' => 'Gaji Saya',
    'deductions' => 'Potongan Gaji',
    'work-reports' => 'Hasil Pekerjaan',
    'reports' => 'Laporan Gaji',
    'settings' => 'User & Hak Akses',
];
