<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DeletesRestrictedRecords;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    use DeletesRestrictedRecords;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        Gate::authorize('viewAny', Employee::class);
        $query = Employee::query()->where('client_id', $client->id())->with('group')->orderBy('full_name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->addColumn('group_name', fn (Employee $employee): string => $employee->group?->name ?? '—')->editColumn('status', fn (Employee $employee): string => view('components.badge', [
                'variant' => $employee->status === 'active' ? 'success' : 'neutral',
                'slot' => $employee->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('action', function (Employee $employee): string {
                $detail = e(json_encode([
                    'employee_no' => $employee->employee_no,
                    'sim_id' => $employee->sim_id,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'join_date' => $employee->join_date ? date('d/m/Y', strtotime($employee->join_date)) : null,
                    'gender' => match ($employee->gender) {
                        'male' => 'Laki-laki', 'female' => 'Perempuan', default => null
                    },
                    'employee_status' => match ($employee->employee_status) {
                        'permanent' => 'Tetap', 'contract' => 'Kontrak', 'daily' => 'Harian', default => null
                    },
                    'marital_status' => match ($employee->marital_status) {
                        'single' => 'Belum menikah', 'married' => 'Menikah', 'divorced' => 'Cerai hidup', 'widowed' => 'Cerai mati', default => null
                    },
                    'group_name' => $employee->group?->name,
                    'status' => $employee->status,
                ]));

                return '<button type="button" data-employee-detail="'.$detail.'" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#eye"></use></svg>Detail</button> <button type="button" data-employee-edit data-url="'.route('employees.update', $employee).'" data-employee="'.e($employee->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('employees.destroy', $employee).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>';
            })->rawColumns(['action', 'status'])->toJson();
        }

        return view('employees.index', ['currentClient' => $client->get(), 'user' => $request->user(), 'groups' => Group::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('name')->get()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): never
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        $data = $request->validated();
        $password = filled($data['password'] ?? null) ? $data['password'] : 'password';
        unset($data['password']);
        $employee = DB::transaction(function () use ($client, $data, $password): Employee {
            $employeeNumber = $this->generateEmployeeNumber($client->id());
            $employee = Employee::query()->create($data + [
                'client_id' => $client->id(),
                'employee_no' => $employeeNumber,
                'rate_category' => 'baru',
                'status' => 'active',
            ]);
            $this->syncEmployeeUser($employee, $client->id(), $password);

            return $employee;
        });

        return response()->json(['message' => 'Karyawan berhasil ditambahkan.', 'data' => $employee], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): never
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee, CurrentClientService $client): JsonResponse
    {
        abort_unless($employee->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        Gate::authorize('update', $employee);
        $data = $request->validated();
        $password = $data['password'] ?? null;
        unset($data['password']);
        DB::transaction(function () use ($data, $employee, $password): void {
            $employee->update($data);
            $this->syncEmployeeUser($employee->fresh(), $employee->client_id, $password);
        });

        return response()->json(['message' => 'Karyawan berhasil diperbarui.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Employee $employee, CurrentClientService $client): JsonResponse
    {
        abort_unless($employee->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        Gate::authorize('delete', $employee);
        DB::transaction(function () use ($employee): void {
            if ($employee->user) {
                $employee->user->update(['status' => 'inactive']);
                $employee->user->clients()->updateExistingPivot($employee->client_id, ['status' => 'inactive']);
            }

            $this->deleteRestricted($employee, 'Karyawan tidak dapat dihapus karena masih dipakai pada realisasi kerja atau potongan gaji.');
        });

        return response()->json(['message' => 'Karyawan berhasil dihapus.']);
    }

    private function generateEmployeeNumber(int $clientId): string
    {
        do {
            $employeeNumber = (string) random_int(100000000, 999999999);
        } while (Employee::query()->where('client_id', $clientId)->where('employee_no', $employeeNumber)->exists() || User::query()->where('username', $employeeNumber)->exists());

        return $employeeNumber;
    }

    private function syncEmployeeUser(Employee $employee, int $clientId, ?string $password = null): User
    {
        $user = $employee->user ?? new User;
        $user->fill([
            'name' => $employee->full_name,
            'email' => $employee->email,
            'username' => $user->username ?? $employee->employee_no,
            'status' => 'active',
        ]);

        if (filled($password) || ! $user->exists) {
            $user->password = $password ?: 'password';
        }

        $user->save();
        $roleId = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan'])->getKey();
        $user->clients()->syncWithoutDetaching([$clientId => ['role_id' => $roleId, 'is_default' => true, 'status' => 'active']]);
        $employee->update(['user_id' => $user->getKey()]);

        return $user;
    }
}
