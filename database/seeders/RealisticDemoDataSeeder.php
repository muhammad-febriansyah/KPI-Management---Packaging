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
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = Client::query()->where('code', env('INITIAL_CLIENT_CODE', 'CLIENT001'))->firstOrFail();
        $client->update(['name' => env('INITIAL_CLIENT_NAME', 'PT SIMGROUP Co-Packing')]);

        $admin = User::query()->where('is_super_admin', true)->firstOrFail();
        $employeeRoleId = (int) Role::query()->where('code', 'employee')->value('id');
        $clientRoleId = (int) Role::query()->where('code', 'client')->value('id');

        $this->wipeExistingDemoData($client->id);
        $this->removeLegacyDemoAccounts();

        $clientUsers = collect([
            ['Andi Wijaya', 'andi.wijaya', 'andi.wijaya@example.com'],
            ['Maya Puspita', 'maya.puspita', 'maya.puspita@example.com'],
        ])->map(fn (array $definition): User => $this->createPortalUser(
            client: $client,
            roleId: $clientRoleId,
            username: $definition[1],
            name: $definition[0],
            email: $definition[2],
        ));

        $units = collect([
            ['PCS', 'Pcs'], ['KRT', 'Karton'], ['KG', 'Kg'], ['BOX', 'Box'], ['SCT', 'Sachet'],
        ])->map(fn (array $definition): Unit => Unit::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1], 'status' => 'active',
        ]));

        $groups = collect([
            ['PK-A', 'Line Packing A'], ['PK-B', 'Line Packing B'], ['FL-01', 'Line Filling'],
            ['LB-01', 'Line Labeling'], ['QC-01', 'Quality Control'], ['RP-01', 'Line Repack'],
            ['GD-BB', 'Gudang Bahan Baku'], ['GD-BJ', 'Gudang Barang Jadi'],
        ])->map(fn (array $definition): Group => Group::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1], 'status' => 'active',
        ]));

        $shifts = collect([
            ['PAGI', 'Shift Pagi', '06:00', '14:00'], ['SIANG', 'Shift Siang', '14:00', '22:00'],
            ['MALAM', 'Shift Malam', '22:00', '06:00'], ['FLEKS', 'Shift Fleksibel', '08:00', '17:00'],
        ])->map(fn (array $definition): Shift => Shift::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1],
            'start_time' => $definition[2], 'end_time' => $definition[3], 'status' => 'active',
        ]));

        $costCenters = collect([
            ['PROD-A', 'Produksi Line A'], ['PROD-B', 'Produksi Line B'], ['FILL', 'Filling & Sealing'],
            ['QC', 'Quality Control'], ['WH-RM', 'Gudang Bahan Baku'], ['WH-FG', 'Gudang Barang Jadi'],
        ])->map(fn (array $definition): CostCenter => CostCenter::query()->create([
            'client_id' => $client->id, 'code' => $definition[0], 'name' => $definition[1], 'status' => 'active',
        ]));

        $employeeDefinitions = [
            ['Budi Santoso', 'male', 'permanent', 'married', 0, 'lama', 'budi.santoso', '081211110001', 'active'],
            ['Siti Nurhaliza', 'female', 'permanent', 'single', 0, 'lama', 'siti.nurhaliza', '081211110002', 'active'],
            ['Ahmad Fauzi', 'male', 'contract', 'married', 1, 'baru', 'ahmad.fauzi', '081211110003', 'active'],
            ['Dewi Lestari', 'female', 'permanent', 'single', 1, 'baru', 'dewi.lestari', '081211110004', 'active'],
            ['Rina Marlina', 'female', 'contract', 'married', 2, 'lama', 'rina.marlina', '081211110005', 'active'],
            ['Agus Setiawan', 'male', 'permanent', 'single', 2, 'baru', 'agus.setiawan', '081211110006', 'active'],
            ['Wulan Sari', 'female', 'daily', 'single', 3, 'baru', 'wulan.sari', '081211110007', 'active'],
            ['Eko Prasetyo', 'male', 'permanent', 'married', 4, 'lama', 'eko.prasetyo', '081211110008', 'active'],
            ['Yuni Astuti', 'female', 'contract', 'single', 5, 'baru', 'yuni.astuti', '081211110009', 'active'],
            ['Doni Kurniawan', 'male', 'permanent', 'married', 0, 'lama', 'doni.kurniawan', '081211110010', 'active'],
            ['Lestari Wulandari', 'female', 'daily', 'single', 1, 'baru', 'lestari.wulandari', '081211110011', 'active'],
            ['Fajar Ramadhan', 'male', 'contract', 'single', 6, 'baru', 'fajar.ramadhan', '081211110012', 'inactive'],
        ];

        $employees = collect($employeeDefinitions)->values()->map(function (array $definition, int $index) use ($client, $employeeRoleId, $groups): Employee {
            $status = $definition[8];
            $loginUser = $this->createPortalUser(
                client: $client, roleId: $employeeRoleId, username: $definition[6], name: $definition[0],
                email: $definition[6].'@example.com', status: $status,
            );

            return Employee::query()->create([
                'client_id' => $client->id, 'user_id' => $loginUser->id,
                'employee_no' => 'EMP'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                'sim_id' => 'SIM-EMP-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'full_name' => $definition[0], 'email' => $definition[6].'@example.com', 'phone' => $definition[7],
                'join_date' => now()->subYears(3)->subMonths($index * 2)->toDateString(),
                'gender' => $definition[1], 'employee_status' => $definition[2], 'marital_status' => $definition[3],
                'group_id' => $groups->get($definition[4])->id, 'rate_category' => $definition[5], 'status' => $status,
            ]);
        });

        $productDefinitions = [
            ['KOP-3IN1-20G', 'Kopi Sachet 3in1 20g', 4, 0, 0, 420, 12000, 15000, 850],
            ['TEH-CEL-2G', 'Teh Celup Melati 2g', 3, 0, 0, 7000, 12500, 15000, 520],
            ['MIE-GRG-85G', 'Mie Instan Goreng 85g', 0, 1, 1, 2800, 14000, 17500, 500],
            ['SAB-CAI-250ML', 'Sabun Cair 250ml', 0, 2, 2, 8500, 18000, 22000, 340],
            ['DET-BUB-1KG', 'Deterjen Bubuk 1kg', 0, 1, 1, 12500, 20000, 25000, 260],
            ['SNK-KRP-50G', 'Snack Keripik Singkong 50g', 0, 0, 0, 3500, 12000, 15000, 680],
            ['MYK-GRG-1L', 'Minyak Goreng 1L', 0, 1, 1, 17500, 18000, 22000, 300],
            ['GLA-PSR-1KG', 'Gula Pasir 1kg', 2, 6, 4, 15000, 15000, 18000, 420],
            ['SUS-BUB-400G', 'Susu Bubuk Coklat 400g', 0, 2, 2, 23500, 22000, 28000, 220],
            ['SMB-BTL-140ML', 'Sambal Botol 140ml', 0, 2, 2, 6800, 16000, 20000, 390],
            ['SAUS-TIR-135', 'Saus Tiram 135ml', 0, 2, 2, 10500, 15000, 19000, 380],
            ['KOP-HIT-100G', 'Kopi Hitam Bubuk 100g', 0, 5, 0, 6500, 13000, 16000, 460],
        ];

        $products = collect($productDefinitions)->values()->map(fn (array $definition): Product => Product::query()->create([
            'client_id' => $client->id, 'sku' => $definition[0], 'name' => $definition[1],
            'unit_id' => $units->get($definition[2])->id, 'group_id' => $groups->get($definition[3])->id,
            'cost_center_id' => $costCenters->get($definition[4])->id, 'po_price' => $definition[5],
            'old_employee_rate' => $definition[6], 'new_employee_rate' => $definition[7],
            'estimated_output_per_hour' => $definition[8], 'status' => 'active',
        ]));

        $batches = $products->values()->map(function (Product $product, int $index): Batch {
            $startDate = now()->subDays(14 - $index)->startOfDay();

            return Batch::query()->create([
                'client_id' => $product->client_id,
                'batch_no' => 'B-'.now()->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'product_id' => $product->id, 'start_date' => $startDate->toDateString(),
                'end_date' => $index < 3 ? null : $startDate->copy()->addDays(7)->toDateString(),
                'status' => $index < 3 ? 'active' : 'completed',
            ]);
        });

        $realizationPlan = [
            [0, 0, 0, [0, 1], 720, 'submitted', false, 'Output sesuai target dan hasil packing lolos pemeriksaan awal.'],
            [1, 1, 2, [2], 480, 'submitted', false, 'Produksi berjalan normal pada shift siang.'],
            [2, 2, 5, [3, 4], 640, 'submitted', false, 'Ada penyesuaian bahan baku sebelum proses dimulai.'],
            [3, 3, 3, [5], 280, 'submitted', true, 'Ditemukan dua karton dengan label kurang presisi, sudah dipisahkan untuk QC.'],
            [4, 0, 4, [6], 245, 'submitted', false, 'Line berhenti 20 menit untuk pembersihan mesin.'],
            [5, 1, 6, [7, 8], 710, 'submitted', false, 'Output melebihi target harian dengan kualitas stabil.'],
            [6, 2, 7, [9], 390, 'assigned', false, null],
            [7, 3, 8, [10], 205, 'submitted', false, 'Bahan kemasan datang terlambat, namun target akhir tercapai.'],
            [8, 0, 9, [0, 2], 360, 'submitted', false, 'Realisasi sesuai rencana produksi.'],
            [9, 1, 10, [3], 420, 'assigned', false, null],
            [10, 2, 11, [4, 5], 235, 'submitted', false, 'Hasil timbang dan sealing sudah diverifikasi QC.'],
            [11, 3, 0, [6], 310, 'submitted', false, 'Tidak ada kendala pada proses packing.'],
            [12, 0, 1, [7, 8], 510, 'submitted', false, 'Produksi berjalan sesuai SOP.'],
            [13, 1, 2, [9], 570, 'submitted', false, 'Ada tambahan output dari sisa bahan produksi.'],
            [14, 2, 3, [10], 330, 'draft', false, null],
            [15, 3, 4, [1], 190, 'submitted', false, 'Batch selesai dan dipindahkan ke gudang barang jadi.'],
            [16, 0, 5, [0, 6], 760, 'submitted', false, 'Realisasi melebihi estimasi output per jam.'],
            [17, 1, 6, [2], 455, 'submitted', false, 'Produksi selesai tanpa catatan tambahan.'],
        ];

        $realizations = collect($realizationPlan)->map(function (array $definition) use ($admin, $batches, $client, $employees, $products, $shifts, $units): WorkRealization {
            [$daysAgo, $shiftIndex, $productIndex, $employeeIndexes, $output, $status, $isComplaint, $report] = $definition;
            $product = $products->get($productIndex);
            $batch = $batches->get($productIndex);
            $workDate = now()->subDays($daysAgo)->toDateString();
            $unit = $units->firstWhere('id', $product->unit_id);

            $realization = WorkRealization::query()->create([
                'client_id' => $client->id, 'work_date' => $workDate, 'shift_id' => $shifts->get($shiftIndex)->id,
                'batch_id' => $batch->id, 'product_id' => $product->id, 'sku_snapshot' => $product->sku,
                'product_name_snapshot' => $product->name, 'unit_name_snapshot' => $unit->name,
                'total_output' => $output, 'start_time' => $shifts->get($shiftIndex)->start_time,
                'end_time' => $shifts->get($shiftIndex)->end_time, 'report' => $report,
                'is_complaint' => $isComplaint, 'status' => $status, 'created_by' => $admin->id,
            ]);

            foreach ($employeeIndexes as $employeeIndex) {
                $employee = $employees->get($employeeIndex);
                $rate = $employee->rate_category === 'lama' ? $product->old_employee_rate : $product->new_employee_rate;
                $hasResult = in_array($status, ['submitted', 'finalized'], true);

                DB::table('realization_employees')->insert([
                    'client_id' => $client->id, 'work_realization_id' => $realization->id, 'employee_id' => $employee->id,
                    'rate_category_snapshot' => $employee->rate_category, 'rate_per_unit_snapshot' => $rate,
                    'allocation_output' => $hasResult ? $output : null,
                    'gross_amount' => $hasResult ? (int) round($output * (float) $rate) : 0,
                    'created_at' => now(),
                ]);
            }

            return $realization;
        });

        $activeEmployees = $employees->where('status', 'active')->values();
        foreach ([[1, 'locked', now()->subDays(4)], [2, 'draft', null]] as [$weekNo, $status, $lockedAt]) {
            $period = DeductionPeriod::query()->create([
                'client_id' => $client->id, 'month' => now()->startOfMonth()->toDateString(), 'week_no' => $weekNo,
                'status' => $status, 'created_by' => $admin->id, 'locked_by' => $lockedAt ? $admin->id : null,
                'locked_at' => $lockedAt,
            ]);

            foreach ($activeEmployees as $index => $employee) {
                DB::table('employee_deductions')->insert([
                    'client_id' => $client->id, 'deduction_period_id' => $period->id, 'employee_id' => $employee->id,
                    'uniform_amount' => $weekNo === 1 && $index < 4 ? 50000 : 0,
                    'equipment_amount' => $weekNo === 1 && $index % 3 === 0 ? 25000 : 0,
                    'meal_amount' => 150000, 'bpjs_health_percent' => $weekNo === 1 ? 1.000 : 0,
                    'bpjs_employment_percent' => $weekNo === 1 ? 2.000 : 0,
                    'salary_advance_type' => $weekNo === 1 && $index === 0 ? 'fixed' : ($weekNo === 2 && $index === 1 ? 'percentage' : null),
                    'salary_advance_value' => $weekNo === 1 && $index === 0 ? 300000 : ($weekNo === 2 && $index === 1 ? 10 : 0),
                    'correction_minus' => $weekNo === 1 && $index === 4 ? 25000 : 0,
                    'correction_plus' => $weekNo === 2 && $index === 2 ? 75000 : 0,
                    'notes' => $weekNo === 1 && $index === 4 ? 'Penyesuaian selisih kasbon periode sebelumnya.' : null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $auditDefinitions = [
            ['create', Product::class, $products->get(0)->id, $admin->id],
            ['update', Product::class, $products->get(4)->id, $admin->id],
            ['create', Employee::class, $employees->get(0)->id, $admin->id],
            ['create', WorkRealization::class, $realizations->get(0)->id, $admin->id],
            ['login', User::class, $employees->get(0)->user_id, $employees->get(0)->user_id],
            ['login', User::class, $clientUsers->first()->id, $clientUsers->first()->id],
            ['update', WorkRealization::class, $realizations->get(3)->id, $admin->id],
            ['create', Batch::class, $batches->get(2)->id, $admin->id],
            ['update', DeductionPeriod::class, 1, $admin->id],
            ['update', Employee::class, $employees->get(5)->id, $admin->id],
        ];

        foreach ($auditDefinitions as $index => [$action, $type, $subjectId, $userId]) {
            DB::table('audit_logs')->insert([
                'client_id' => $client->id, 'user_id' => $userId, 'action' => $action,
                'auditable_type' => $type, 'auditable_id' => $subjectId, 'ip_address' => '127.0.0.1',
                'user_agent' => 'RealisticDemoDataSeeder', 'created_at' => now()->subHours(10 - $index),
            ]);
        }
    }

    private function createPortalUser(Client $client, int $roleId, string $username, string $name, string $email, string $status = 'active'): User
    {
        $user = User::query()->updateOrCreate(
            ['username' => $username],
            ['name' => $name, 'email' => $email, 'password' => 'password', 'status' => $status],
        );

        $user->clients()->syncWithoutDetaching([
            $client->id => ['role_id' => $roleId, 'status' => $status, 'is_default' => true],
        ]);

        return $user;
    }

    private function removeLegacyDemoAccounts(): void
    {
        User::query()->whereIn('username', ['karyawan.demo', 'client.demo'])->get()->each(function (User $user): void {
            $user->clients()->detach();
            $user->delete();
        });
    }

    private function wipeExistingDemoData(int $clientId): void
    {
        Schema::withoutForeignKeyConstraints(function () use ($clientId): void {
            foreach ([
                'audit_logs', 'realization_employees', 'work_realizations', 'batches',
                'employee_deductions', 'deduction_periods', 'products', 'employees',
                'shifts', 'cost_centers', 'groups', 'units',
            ] as $table) {
                DB::table($table)->where('client_id', $clientId)->delete();
            }
        });
    }
}
