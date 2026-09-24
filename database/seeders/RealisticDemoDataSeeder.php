<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\DeductionPeriod;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RealisticDemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Rebuild one presentation workspace while preserving authorization data.
     */
    public function run(): void
    {
        $admin = User::query()
            ->where('username', env('SUPER_ADMIN_USERNAME', 'superadmin'))
            ->where('is_super_admin', true)
            ->firstOrFail();
        $this->resetPresentationData($admin->getKey());

        $client = Client::query()->create([
            'code' => env('INITIAL_CLIENT_CODE', 'CLIENT001'),
            'name' => env('INITIAL_CLIENT_NAME', 'PT SIMGROUP Co-Packing'),
            'timezone' => env('INITIAL_CLIENT_TIMEZONE', 'Asia/Jakarta'),
            'status' => 'active',
        ]);
        $clientRoleId = (int) Role::query()->where('code', 'client')->value('id');
        $employeeRoleId = (int) Role::query()->where('code', 'employee')->value('id');
        $demoClients = collect([$client]);

        foreach ([
            'PT Zeta Retail Nusantara', 'PT Zeta Logistik Indonesia', 'PT Zeta Consumer Goods',
            'PT Zeta Food Distribution', 'PT Zeta Prima Makmur', 'PT Zeta Karya Sejahtera',
            'PT Zeta Mitra Dagang', 'PT Zeta Sentosa Abadi', 'PT Zeta Solusi Industri',
        ] as $index => $name) {
            $demoClients->push(Client::query()->create([
                'code' => 'CLIENT'.str_pad((string) ($index + 2), 3, '0', STR_PAD_LEFT),
                'name' => $name, 'timezone' => 'Asia/Jakarta', 'status' => 'active',
            ]));
        }

        foreach ($demoClients as $index => $demoClient) {
            $user = User::query()->create([
                'name' => 'Demo Client '.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'username' => 'client.demo.'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'email' => 'client.demo.'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'@example.com',
                'password' => 'password', 'status' => 'active',
            ]);
            $user->clients()->attach($demoClient->id, [
                'role_id' => $clientRoleId, 'is_default' => true, 'status' => 'active',
            ]);

            if ($demoClient->id !== $client->id) {
                $user->clients()->attach($client->id, [
                    'role_id' => $clientRoleId, 'is_default' => false, 'status' => 'active',
                ]);
            }
        }

        $units = collect([
            ['PCS', 'Pcs'], ['KRT', 'Karton'], ['KG', 'Kilogram'], ['BOX', 'Box'], ['SCT', 'Sachet'],
            ['BTL', 'Botol'], ['PKG', 'Paket'], ['ROLL', 'Roll'], ['LTR', 'Liter'], ['BAG', 'Bag'],
        ])->map(fn (array $definition): Unit => Unit::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1], 'status' => 'active',
        ]));

        $groups = collect([
            ['PK-A', 'Line Packing A'], ['PK-B', 'Line Packing B'], ['FL-01', 'Line Filling'],
            ['LB-01', 'Line Labeling'], ['QC-01', 'Quality Control'], ['RP-01', 'Line Repack'],
            ['GD-BB', 'Gudang Bahan Baku'], ['GD-BJ', 'Gudang Barang Jadi'], ['MIX-01', 'Mixing Room'],
            ['RET-01', 'Retur dan Rework'],
        ])->map(fn (array $definition): Group => Group::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1], 'status' => 'active',
        ]));

        $shifts = collect([
            ['PAGI', 'Shift Pagi', '06:00', '14:00'], ['SIANG', 'Shift Siang', '14:00', '22:00'],
            ['MALAM', 'Shift Malam', '22:00', '06:00'], ['FLEKS', 'Shift Fleksibel', '08:00', '17:00'],
            ['PAGI2', 'Shift Pagi Cadangan', '07:00', '15:00'], ['SIANG2', 'Shift Siang Cadangan', '15:00', '23:00'],
            ['MALAM2', 'Shift Malam Cadangan', '23:00', '07:00'], ['QC', 'Shift QC', '08:00', '16:00'],
            ['WH', 'Shift Gudang', '09:00', '17:00'], ['WEEKEND', 'Shift Weekend', '08:00', '16:00'],
        ])->map(fn (array $definition): Shift => Shift::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1],
            'start_time' => $definition[2], 'end_time' => $definition[3], 'status' => 'active',
        ]));

        $costCenters = collect([
            ['PROD-A', 'Produksi Line A'], ['PROD-B', 'Produksi Line B'], ['FILL', 'Filling & Sealing'],
            ['QC', 'Quality Control'], ['WH-RM', 'Gudang Bahan Baku'], ['WH-FG', 'Gudang Barang Jadi'],
            ['LABEL', 'Labeling'], ['REPACK', 'Repacking'], ['MIX', 'Mixing'], ['REWORK', 'Rework'],
        ])->map(fn (array $definition): CostCenter => CostCenter::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1], 'status' => 'active',
        ]));

        $employeeDefinitions = [
            ['Budi Santoso', 'male', 'permanent', 'married', 0, 'lama', '081211110001'],
            ['Siti Nurhaliza', 'female', 'permanent', 'single', 1, 'lama', '081211110002'],
            ['Ahmad Fauzi', 'male', 'contract', 'married', 2, 'baru', '081211110003'],
            ['Dewi Lestari', 'female', 'permanent', 'single', 3, 'baru', '081211110004'],
            ['Rina Marlina', 'female', 'contract', 'married', 4, 'lama', '081211110005'],
            ['Agus Setiawan', 'male', 'permanent', 'single', 5, 'baru', '081211110006'],
            ['Wulan Sari', 'female', 'daily', 'single', 6, 'baru', '081211110007'],
            ['Eko Prasetyo', 'male', 'permanent', 'married', 7, 'lama', '081211110008'],
            ['Yuni Astuti', 'female', 'contract', 'single', 8, 'baru', '081211110009'],
            ['Doni Kurniawan', 'male', 'permanent', 'married', 9, 'lama', '081211110010'],
        ];

        $employees = collect($employeeDefinitions)->map(function (array $definition, int $index) use ($client, $employeeRoleId, $groups): Employee {
            $employeeNumber = 'EMP'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
            $email = strtolower(str_replace(' ', '.', $definition[0])).'@example.com';
            $employee = Employee::query()->create([
                'client_id' => $client->id, 'user_id' => null, 'employee_no' => $employeeNumber,
                'sim_id' => 'SIM-EMP-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'full_name' => $definition[0], 'email' => $email,
                'phone' => $definition[6], 'join_date' => now()->subYears(3)->subMonths($index * 2)->toDateString(),
                'gender' => $definition[1], 'employee_status' => $definition[2], 'marital_status' => $definition[3],
                'group_id' => $groups->get($definition[4])->id, 'rate_category' => $definition[5], 'status' => 'active',
            ]);

            $user = User::query()->create([
                'name' => $employee->full_name, 'username' => $employeeNumber, 'email' => $email,
                'password' => 'password', 'status' => 'active',
            ]);
            $user->clients()->attach($client->id, [
                'role_id' => $employeeRoleId, 'is_default' => true, 'status' => 'active',
            ]);
            $employee->update(['user_id' => $user->getKey()]);

            return $employee;
        });

        $productDefinitions = [
            ['KOP-3IN1-20G', 'Kopi Sachet 3in1 20g', 4, 0, 0, 15000, 850, 800],
            ['TEH-CEL-2G', 'Teh Celup Melati 2g', 3, 3, 3, 15000, 520, 1100],
            ['MIE-GRG-85G', 'Mie Instan Goreng 85g', 0, 1, 1, 17500, 500, 900],
            ['SAB-CAI-250ML', 'Sabun Cair 250ml', 5, 2, 2, 22000, 340, 650],
            ['DET-BUB-1KG', 'Deterjen Bubuk 1kg', 0, 1, 1, 25000, 260, 420],
            ['SNK-KRP-50G', 'Snack Keripik Singkong 50g', 0, 0, 0, 15000, 680, 1000],
            ['MYK-GRG-1L', 'Minyak Goreng 1L', 8, 1, 1, 22000, 300, 500],
            ['GLA-PSR-1KG', 'Gula Pasir 1kg', 2, 6, 4, 18000, 420, 700],
            ['SUS-BUB-400G', 'Susu Bubuk Coklat 400g', 0, 2, 2, 28000, 220, 380],
            ['SMB-BTL-140ML', 'Sambal Botol 140ml', 5, 2, 2, 20000, 390, 600],
        ];

        $products = collect($productDefinitions)->map(fn (array $definition): Product => Product::query()->create([
            'client_id' => $client->id, 'sku' => $definition[0], 'name' => $definition[1],
            'unit_id' => $units->get($definition[2])->id, 'group_id' => $groups->get($definition[3])->id,
            'cost_center_id' => $costCenters->get($definition[4])->id, 'po_price' => $definition[5],
            'employee_rate' => $definition[6], 'estimated_output_per_hour' => $definition[7], 'status' => 'active',
        ]));

        $batches = $products->map(function (Product $product, int $index): Batch {
            $startDate = now()->subDays(20 - $index)->startOfDay();

            return Batch::query()->create([
                'client_id' => $product->client_id,
                'batch_no' => 'B-'.now()->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'product_id' => $product->id, 'start_date' => $startDate->toDateString(),
                'end_date' => $index < 7 ? null : $startDate->copy()->addDays(5)->toDateString(),
                'status' => $index < 7 ? 'active' : 'completed',
            ]);
        });

        $realizationPlan = [
            [1, 0, 0, [0], 520, false, 'Output sesuai target dan hasil packing lolos pemeriksaan awal.'],
            [2, 1, 1, [1, 2], 780, false, 'Produksi berjalan normal pada shift siang.'],
            [3, 2, 2, [3], 430, false, 'Hasil timbang dan sealing sudah diverifikasi QC.'],
            [4, 3, 3, [4, 5], 610, true, 'Dua karton dengan label kurang presisi dipisahkan untuk QC.'],
            [5, 4, 4, [6], 350, false, 'Line berhenti 20 menit untuk pembersihan mesin.'],
            [6, 5, 5, [7, 8], 900, false, 'Output melebihi target harian dengan kualitas stabil.'],
            [7, 6, 6, [9], 275, false, 'Produksi selesai tanpa catatan tambahan.'],
            [8, 7, 7, [0, 1, 2], 660, false, 'Produksi berjalan sesuai SOP dan checklist lengkap.'],
            [9, 8, 8, [3, 4], 410, false, null],
            [10, 9, 9, [5, 6], 300, false, null],
        ];

        $realizations = collect($realizationPlan)->map(function (array $definition) use ($admin, $batches, $client, $employees, $products, $shifts, $units): WorkRealization {
            [$daysAgo, $shiftIndex, $productIndex, $employeeIndexes, $output, $isComplaint, $report] = $definition;
            $product = $products->get($productIndex);
            $batch = $batches->get($productIndex);
            $workDate = now()->subDays($daysAgo)->toDateString();
            $unit = $units->firstWhere('id', $product->unit_id);
            $isFinalized = $report !== null;

            $realization = WorkRealization::query()->create([
                'client_id' => $client->id, 'work_date' => $workDate, 'shift_id' => $shifts->get($shiftIndex)->id,
                'batch_id' => $batch->id, 'product_id' => $product->id, 'sku_snapshot' => $product->sku,
                'product_name_snapshot' => $product->name, 'unit_name_snapshot' => $unit->name,
                'total_output' => $output, 'start_time' => $shifts->get($shiftIndex)->start_time,
                'end_time' => $shifts->get($shiftIndex)->end_time, 'report' => $report,
                'is_complaint' => $isComplaint, 'created_by' => $admin->id,
                'finalized_by' => $isFinalized ? $admin->id : null,
                'finalized_at' => $isFinalized ? now()->subDays($daysAgo)->addHours(10) : null,
            ]);

            foreach ($employeeIndexes as $employeeIndex) {
                $employee = $employees->get($employeeIndex);
                $rate = $product->employee_rate;

                DB::table('realization_employees')->insert([
                    'client_id' => $client->id, 'work_realization_id' => $realization->id, 'employee_id' => $employee->id,
                    'rate_category_snapshot' => $employee->rate_category, 'rate_per_unit_snapshot' => $rate,
                    'allocation_output' => $output,
                    'gross_amount' => $isFinalized ? (int) round($output * (float) $rate) : 0,
                    'created_at' => now(),
                ]);
            }

            return $realization;
        });

        $period = DeductionPeriod::query()->create([
            'client_id' => $client->id, 'month' => now()->startOfMonth()->toDateString(), 'week_no' => null,
            'status' => 'locked', 'created_by' => $admin->id, 'locked_by' => $admin->id, 'locked_at' => now()->subDays(2),
        ]);

        foreach ($employees as $index => $employee) {
            DB::table('employee_deductions')->insert([
                'client_id' => $client->id, 'deduction_period_id' => $period->id, 'employee_id' => $employee->id,
                'uniform_amount' => $index < 3 ? 50000 : 0,
                'equipment_amount' => $index % 4 === 0 ? 25000 : 0,
                'meal_amount' => 150000,
                'bpjs_health_percent' => $index % 3 === 0 ? 1.000 : 1.500,
                'bpjs_employment_percent' => 2.000,
                'salary_advance_type' => match ($index) {
                    0, 2 => 'fixed',
                    1 => 'percentage',
                    default => null,
                },
                'salary_advance_value' => match ($index) {
                    0 => 300000,
                    1 => 10,
                    2 => 500000,
                    default => 0,
                },
                'correction_minus' => $index === 4 ? 25000 : 0,
                'correction_plus' => $index === 5 ? 75000 : 0,
                'notes' => $index === 4 ? 'Penyesuaian selisih kasbon periode sebelumnya.' : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach ($realizations->values() as $index => $realization) {
            DB::table('audit_logs')->insert([
                'client_id' => $client->id, 'user_id' => $admin->id, 'action' => $index % 2 === 0 ? 'create' : 'update',
                'auditable_type' => $index % 2 === 0 ? Product::class : WorkRealization::class,
                'auditable_id' => $index % 2 === 0 ? $products->get($index)->id : $realization->id,
                'ip_address' => '127.0.0.1', 'user_agent' => 'RealisticDemoDataSeeder',
                'created_at' => now()->subHours(10 - $index),
            ]);
        }
    }

    private function resetPresentationData(int $adminId): void
    {
        Schema::withoutForeignKeyConstraints(function () use ($adminId): void {
            foreach ([
                'notifications', 'audit_logs', 'realization_employees', 'work_realizations', 'batches',
                'employee_deductions', 'deduction_periods', 'products', 'employees', 'shifts',
                'cost_centers', 'groups', 'units', 'client_user', 'clients', 'sessions',
            ] as $table) {
                DB::table($table)->delete();
            }

            DB::table('users')->where('id', '!=', $adminId)->delete();
        });

        User::query()->whereKey($adminId)->update(['status' => 'active', 'is_super_admin' => true]);
    }
}
