<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = env('SUPER_ADMIN_PASSWORD', app()->environment('production') ? null : 'password:password');

        if ($password === null || $password === '') {
            throw new \RuntimeException('SUPER_ADMIN_PASSWORD harus diisi sebelum menjalankan seeder.');
        }

        User::query()->updateOrCreate(
            ['username' => env('SUPER_ADMIN_USERNAME', 'superadmin')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Administrator'),
                'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
                'password' => $password,
                'is_super_admin' => true,
                'status' => 'active',
            ],
        );

        if (! app()->environment('production') && Client::query()->doesntExist()) {
            Client::query()->create([
                'code' => env('INITIAL_CLIENT_CODE', 'CLIENT001'),
                'name' => env('INITIAL_CLIENT_NAME', 'PT SIM Group'),
                'timezone' => env('INITIAL_CLIENT_TIMEZONE', 'Asia/Jakarta'),
                'status' => 'active',
            ]);
        }
    }
}
