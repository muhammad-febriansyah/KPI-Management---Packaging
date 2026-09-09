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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Wipes this client's demo data and rebuilds it from scratch with 10
     * realistic-looking records per module, instead of 20 generically
     * numbered "Dummy N" placeholders.
     */
    public function run(): void
    {
        $client = Client::query()->where('code', env('INITIAL_CLIENT_CODE', 'CLIENT001'))->firstOrFail();
        $admin = User::query()->where('is_super_admin', true)->firstOrFail();
        $employeeRoleId = Role::query()->where('code', 'employee')->value('id');
        $clientRoleId = Role::query()->where('code', 'client')->value('id');

        // Every login user below is a "role" demo account (super-admin / client / employee),
        // matching the 3 roles managed in Settings > User & Hak Akses.
        $employeeUser = User::query()->updateOrCreate(['username' => 'karyawan.demo'], ['name' => 'Karyawan Demo', 'email' => 'karyawan.demo@example.com', 'password' => 'password', 'status' => 'active']);
        $clientUser = User::query()->updateOrCreate(['username' => 'client.demo'], ['name' => 'Client Demo', 'email' => 'client.demo@example.com', 'password' => 'password', 'status' => 'active']);
        $employeeUser->clients()->syncWithoutDetaching([$client->id => ['role_id' => $employeeRoleId, 'status' => 'active', 'is_default' => true]]);
        $clientUser->clients()->syncWithoutDetaching([$client->id => ['role_id' => $clientRoleId, 'status' => 'active', 'is_default' => true]]);

        // A real client company has more than one PIC with portal access.
        foreach ([['Andi Wijaya', 'andi.wijaya'], ['Maya Puspita', 'maya.puspita']] as [$name, $username]) {
            $contact = User::query()->updateOrCreate(['username' => $username], [
                'name' => $name, 'email' => $username.'@example.com', 'password' => 'password', 'status' => 'active',
            ]);
            $contact->clients()->syncWithoutDetaching([$client->id => ['role_id' => $clientRoleId, 'status' => 'active', 'is_default' => true]]);
        }

        $this->wipeExistingDemoData($client->id);

        // 1. Satuan
        $unitNames = ['Pcs', 'Karton', 'Kg', 'Pack', 'Box', 'Lusin', 'Dus', 'Sachet', 'Roll', 'Ball'];
        $units = collect($unitNames)->values()->map(fn (string $name, int $i): Unit => Unit::query()->create([
            'client_id' => $client->id, 'code' => 'UNIT'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'name' => $name, 'status' => 'active',
        ]));

        // 2. Group (lini kerja)
        $groupNames = ['Line Packing A', 'Line Packing B', 'Line Sortir', 'Line Quality Control', 'Line Repack', 'Line Labeling', 'Line Filling', 'Line Wrapping', 'Line Palletizing', 'Line Gudang'];
        $groups = collect($groupNames)->values()->map(fn (string $name, int $i): Group => Group::query()->create([
            'client_id' => $client->id, 'code' => 'GRP'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'name' => $name, 'status' => 'active',
        ]));

        // 3. Shift
        $shiftDefs = [
            ['Shift Pagi', '06:00', '14:00'], ['Shift Siang', '14:00', '22:00'], ['Shift Malam', '22:00', '06:00'],
            ['Shift Pagi Line B', '06:00', '14:00'], ['Shift Siang Line B', '14:00', '22:00'], ['Shift Malam Line B', '22:00', '06:00'],
            ['Shift Pagi Weekend', '07:00', '15:00'], ['Shift Siang Weekend', '15:00', '23:00'],
            ['Shift Lembur', '17:00', '21:00'], ['Shift Fleksibel', '09:00', '17:00'],
        ];
        $shifts = collect($shiftDefs)->values()->map(fn (array $def, int $i): Shift => Shift::query()->create([
            'client_id' => $client->id, 'code' => 'SFT'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'name' => $def[0], 'start_time' => $def[1], 'end_time' => $def[2], 'status' => 'active',
        ]));

        // 4. Cost Center
        $costCenterNames = ['CC Produksi Line A', 'CC Produksi Line B', 'CC Quality Control', 'CC Gudang Bahan Baku', 'CC Gudang Barang Jadi', 'CC Maintenance', 'CC Logistik', 'CC K3 & Safety', 'CC Admin Operasional', 'CC Utility'];
        $costCenters = collect($costCenterNames)->values()->map(fn (string $name, int $i): CostCenter => CostCenter::query()->create([
            'client_id' => $client->id, 'code' => 'CC'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'name' => $name, 'status' => 'active',
        ]));

        // 5. Karyawan — each gets its own login user (role: employee), not just master data.
        // The first keeps the well-known karyawan.demo account; the rest get a real login
        // of their own so Setting > User shows a realistic roster, not one shared demo login.
        $employeeDefs = [
            ['Siti Nurhaliza', 'female', 'siti.nurhaliza'], ['Budi Santoso', 'male', 'budi.santoso'], ['Ahmad Fauzi', 'male', 'ahmad.fauzi'],
            ['Dewi Lestari', 'female', 'dewi.lestari'], ['Rina Marlina', 'female', 'rina.marlina'], ['Agus Setiawan', 'male', 'agus.setiawan'],
            ['Wulan Sari', 'female', 'wulan.sari'], ['Eko Prasetyo', 'male', 'eko.prasetyo'], ['Yuni Astuti', 'female', 'yuni.astuti'],
            ['Doni Kurniawan', 'male', 'doni.kurniawan'],
        ];
        $employees = collect($employeeDefs)->values()->map(function (array $def, int $i) use ($client, $groups, $employeeUser, $employeeRoleId): Employee {
            $loginUser = $i === 0 ? $employeeUser : User::query()->updateOrCreate(['username' => $def[2]], [
                'name' => $def[0], 'email' => $def[2].'@example.com', 'password' => 'password', 'status' => 'active',
            ]);
            $loginUser->clients()->syncWithoutDetaching([$client->id => ['role_id' => $employeeRoleId, 'status' => 'active', 'is_default' => true]]);

            return Employee::query()->create([
                'client_id' => $client->id,
                'user_id' => $loginUser->id,
                'employee_no' => 'EMP'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'full_name' => $def[0],
                'phone' => '0812'.str_pad((string) (30000000 + $i * 1111), 8, '0', STR_PAD_LEFT),
                'join_date' => now()->subMonths(24 - $i * 2)->toDateString(),
                'gender' => $def[1],
                'employee_status' => 'permanent',
                'marital_status' => $i % 3 === 0 ? 'married' : 'single',
                'group_id' => $groups->get($i % $groups->count())->id,
                'rate_category' => $i % 2 ? 'baru' : 'lama',
                'status' => 'active',
            ]);
        });

        // 6. Master Produk
        $productDefs = [
            ['KOP-3IN1-20G', 'Kopi Sachet 3in1 20g', 0, 4000, 25],
            ['TEH-CEL-2G', 'Teh Celup Melati 2g', 0, 3200, 30],
            ['MIE-GRG-85G', 'Mie Instan Goreng 85g', 1, 2800, 20],
            ['SAB-CAI-250ML', 'Sabun Cair 250ml', 2, 8500, 15],
            ['DET-BUB-1KG', 'Deterjen Bubuk 1kg', 2, 12000, 12],
            ['SNK-KRP-50G', 'Snack Keripik Singkong 50g', 3, 5000, 35],
            ['MYK-GRG-1L', 'Minyak Goreng 1L', 2, 16000, 10],
            ['GLA-PSR-1KG', 'Gula Pasir 1kg', 2, 14000, 12],
            ['SUS-BUB-400G', 'Susu Bubuk Coklat 400g', 4, 22000, 18],
            ['SMB-BTL-140ML', 'Sambal Botol 140ml', 2, 6500, 22],
        ];
        $products = collect($productDefs)->values()->map(fn (array $def, int $i): Product => Product::query()->create([
            'client_id' => $client->id,
            'sku' => $def[0],
            'name' => $def[1],
            'unit_id' => $units->get($def[2])->id,
            'group_id' => $groups->get($i % $groups->count())->id,
            'cost_center_id' => $costCenters->get($i % $costCenters->count())->id,
            'po_price' => $def[3],
            'old_employee_rate' => round($def[3] * 0.008, 3),
            'new_employee_rate' => round($def[3] * 0.01, 3),
            'estimated_output_per_hour' => $def[4],
            'status' => 'active',
        ]));

        // 7. Nomor Batch
        $batches = $products->values()->map(fn (Product $product, int $i): Batch => Batch::query()->create([
            'client_id' => $client->id,
            'batch_no' => 'B-'.now()->subDays(10 - $i)->format('Ymd').'-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
            'product_id' => $product->id,
            'start_date' => now()->subDays(10 - $i)->toDateString(),
            'status' => 'active',
        ]));

        // 8. Realisasi Pekerjaan
        $reports = [
            'Realisasi sesuai target harian.',
            'Ada keterlambatan bahan baku dari gudang.',
            'Proses packing lancar tanpa kendala.',
            'Ditemukan sebagian produk cacat kemasan.',
            'Line sempat berhenti untuk maintenance singkat.',
            'Output melebihi target harian.',
            'Kekurangan operator di shift ini.',
            'Kualitas hasil packing baik, sesuai SOP.',
            'Ada revisi jumlah karton di akhir shift.',
            'Realisasi normal, tidak ada catatan khusus.',
        ];
        $outputs = [480, 620, 350, 200, 410, 705, 290, 540, 460, 380];

        foreach (range(0, 9) as $i) {
            $product = $products->get($i);
            $batch = $batches->get($i);
            $unit = $units->firstWhere('id', $product->unit_id);
            $hasEmployee = $i !== 2 && $i !== 7; // leave two realizations unassigned (draft)
            $isSubmitted = in_array($i, [0, 3, 8], true);
            $status = ! $hasEmployee ? 'draft' : ($isSubmitted ? 'submitted' : 'assigned');

            $realization = WorkRealization::query()->create([
                'client_id' => $client->id,
                'work_date' => now()->subDays(10 - $i)->toDateString(),
                'shift_id' => $shifts->get($i % $shifts->count())->id,
                'batch_id' => $batch->id,
                'product_id' => $product->id,
                'sku_snapshot' => $product->sku,
                'product_name_snapshot' => $product->name,
                'unit_name_snapshot' => $unit->name,
                'total_output' => $outputs[$i],
                'start_time' => '08:00',
                'end_time' => '17:00',
                'report' => $isSubmitted ? $reports[$i] : null,
                'is_complaint' => $i === 3,
                'created_by' => $admin->id,
                'status' => $status,
            ]);

            if (! $hasEmployee) {
                continue;
            }

            $employee = $employees->get($i % $employees->count());
            $rate = $employee->rate_category === 'lama' ? $product->old_employee_rate : $product->new_employee_rate;
            DB::table('realization_employees')->insert([
                'client_id' => $client->id,
                'work_realization_id' => $realization->id,
                'employee_id' => $employee->id,
                'rate_category_snapshot' => $employee->rate_category,
                'rate_per_unit_snapshot' => $rate,
                'allocation_output' => $isSubmitted ? $outputs[$i] : null,
                'gross_amount' => $isSubmitted ? (int) round($outputs[$i] * (float) $rate) : 0,
                'created_at' => now(),
            ]);
        }

        // 9. Potongan Gaji
        $period = DeductionPeriod::query()->create([
            'client_id' => $client->id,
            'month' => now()->startOfMonth()->toDateString(),
            'week_no' => null,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        foreach ($employees as $i => $employee) {
            DB::table('employee_deductions')->insert([
                'client_id' => $client->id,
                'deduction_period_id' => $period->id,
                'employee_id' => $employee->id,
                'uniform_amount' => 50000,
                'equipment_amount' => 25000,
                'meal_amount' => 15000 * 20,
                'bpjs_health_percent' => 1.000,
                'bpjs_employment_percent' => 2.000,
                'salary_advance_type' => $i % 4 === 0 ? 'fixed' : null,
                'salary_advance_value' => $i % 4 === 0 ? 200000 : 0,
                'correction_minus' => $i === 5 ? 25000 : 0,
                'correction_plus' => $i === 1 ? 50000 : 0,
                'notes' => $i === 5 ? 'Potongan kasbon bulan lalu.' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 10. Audit log — variasi aksi & pelaku
        $auditDefs = [
            ['create', Product::class, $products->get(0)->id, $admin->id],
            ['update', Product::class, $products->get(1)->id, $admin->id],
            ['create', Employee::class, $employees->get(0)->id, $admin->id],
            ['create', WorkRealization::class, null, $admin->id],
            ['login', User::class, $employeeUser->id, $employeeUser->id],
            ['login', User::class, $clientUser->id, $clientUser->id],
            ['update', Employee::class, $employees->get(3)->id, $admin->id],
            ['create', Batch::class, $batches->get(2)->id, $admin->id],
            ['delete', Product::class, null, $admin->id],
            ['update', DeductionPeriod::class, $period->id, $admin->id],
        ];
        foreach ($auditDefs as $i => [$action, $type, $subjectId, $userId]) {
            // AuditLog has no Eloquent timestamps, and created_at isn't mass-assignable —
            // insert directly so each row gets its own realistic, spread-out timestamp.
            DB::table('audit_logs')->insert([
                'client_id' => $client->id,
                'user_id' => $userId,
                'action' => $action,
                'auditable_type' => $type,
                'auditable_id' => $subjectId,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DemoDataSeeder',
                'created_at' => now()->subHours(10 - $i),
            ]);
        }
    }

    /**
     * Clears this client's previously seeded demo data so re-running this
     * seeder always leaves exactly 10 fresh records per module, instead of
     * piling up duplicates or leftovers from an earlier, larger seed.
     */
    private function wipeExistingDemoData(int $clientId): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'audit_logs', 'realization_employees', 'work_realizations', 'batches',
            'employee_deductions', 'deduction_periods', 'products', 'employees',
            'shifts', 'cost_centers', 'groups', 'units',
        ] as $table) {
            DB::table($table)->where('client_id', $clientId)->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
