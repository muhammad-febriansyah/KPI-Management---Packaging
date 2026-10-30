<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeExport;
use App\Exports\EmployeeTemplateExport;
use App\Http\Controllers\Concerns\DeletesRestrictedRecords;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Imports\EmployeeImport;
use App\Models\Client;
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
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        $currentClient = $client->get();
        $availableClientIds = $client->availableFor($request->user())->modelKeys();
        $selectedClientId = $request->integer('client_id');
        $query = Employee::query()
            ->when(
                $request->user()->is_super_admin,
                fn ($query) => $query->withoutGlobalScopes()
                    ->whereIn('employees.client_id', $availableClientIds)
                    ->when(in_array($selectedClientId, $availableClientIds, true), fn ($query) => $query->where('employees.client_id', $selectedClientId)),
                fn ($query) => $query->where('client_id', $client->id()),
            )
            ->with(['group', 'client'])
            ->orderBy('full_name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->addColumn('client_name', fn (Employee $employee): string => $employee->client?->name ?? '—')->addColumn('group_name', fn (Employee $employee): string => $employee->group?->name ?? '—')->editColumn('status', fn (Employee $employee): string => view('components.badge', [
                'variant' => $employee->status === 'active' ? 'success' : 'neutral',
                'slot' => $employee->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('action', function (Employee $employee): string {
                $employee->setAttribute('group_name', $employee->group?->name);
                $detail = e(json_encode([
                    'employee_no' => $employee->employee_no,
                    'sim_id' => $employee->sim_id,
                    'full_name' => $employee->full_name,
                    'client_name' => $employee->client?->name,
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
                $edit = $employee->toArray();
                $edit['join_date'] = $employee->join_date ? date('Y-m-d', strtotime((string) $employee->join_date)) : null;
                $edit['birth_date'] = $employee->birth_date ? date('Y-m-d', strtotime((string) $employee->birth_date)) : null;

                return '<button type="button" data-employee-detail="'.$detail.'" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#eye"></use></svg>Detail</button> <button type="button" data-employee-edit data-url="'.route('employees.update', $employee).'" data-employee="'.e(json_encode($edit)).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('employees.destroy', $employee).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>';
            })->rawColumns(['action', 'status'])->toJson();
        }

        $groups = Group::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id())
            ->where(function ($query) use ($client): void {
                $query->where('status', 'active')
                    ->orWhereIn('id', Employee::query()
                        ->where('client_id', $client->id())
                        ->whereNotNull('group_id')
                        ->select('group_id'));
            })
            ->orderBy('name')
            ->get();

        return view('employees.index', ['currentClient' => $client->get(), 'user' => $request->user(), 'availableClients' => $request->user()->is_super_admin ? $client->availableFor($request->user()) : collect(), 'groups' => $groups]);
    }

    public function groupOptions(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        Gate::authorize('viewAny', Group::class);
        $targetClientId = $client->id();
        if ($request->user()->is_super_admin && $request->filled('client_id')) {
            $selectedClientId = $request->integer('client_id');
            $allowedClientIds = $client->availableFor($request->user())->modelKeys();
            if (! in_array($selectedClientId, $allowedClientIds, true)) {
                return response()->json(['results' => []]);
            }

            $targetClientId = $selectedClientId;
        }

        $groups = Group::query()->withoutGlobalScopes()->where('client_id', $targetClientId)->where('status', 'active')->orderBy('name')->get();

        return response()->json(['results' => $groups->map(fn (Group $group): array => ['id' => $group->id, 'text' => $group->name])]);
    }

    public function nextSimId(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        $clientId = $request->integer('client_id');
        abort_unless($client->availableFor($request->user())->contains('id', $clientId), 403);

        return response()->json(['sim_id' => $this->generateSimId($clientId)]);
    }

    public function template(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        $group = Group::query()->withoutGlobalScopes()->active()->orderBy('code')->first();

        return Excel::download(new EmployeeTemplateExport($client->get(), $group), 'template-master-karyawan.xlsx');
    }

    public function export(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        Gate::authorize('viewAny', Employee::class);

        return Excel::download(new EmployeeExport($client->id(), $client->get()->code.' — '.$client->get()->name), 'master-karyawan-'.$client->get()->code.'-'.now()->format('Ymd-His').'.xlsx');
    }

    public function import(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        Gate::authorize('create', Employee::class);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        $import = new EmployeeImport($client->id(), $client->availableFor($request->user())->modelKeys());
        Excel::import($import, $request->file('file'));

        if ($import->failures !== []) {
            return response()->json([
                'message' => $import->imported > 0
                    ? "{$import->imported} baris berhasil diimpor, ".count($import->failures).' baris gagal. Perbaiki lalu import ulang baris yang gagal.'
                    : 'Import gagal, tidak ada baris yang berhasil disimpan.',
                'failures' => $import->failures,
            ], $import->imported > 0 ? 207 : 422);
        }

        return response()->json(['message' => "{$import->imported} baris karyawan berhasil diimpor."]);
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
        $targetClientId = $data['client_id'];
        $targetClient = $client->availableFor($request->user())->firstWhere('id', $targetClientId);
        abort_unless($targetClient !== null, 403);
        unset($data['client_id']);
        $password = $data['password'] ?? null;
        unset($data['password']);
        $employee = $client->runAs($targetClient, fn (): Employee => DB::transaction(function () use ($data, $password, $targetClientId): Employee {
            $employeeNumber = $this->generateEmployeeNumber($targetClientId);
            $simId = $this->generateSimId($targetClientId);
            $employee = Employee::query()->create($data + [
                'client_id' => $targetClientId,
                'employee_no' => $employeeNumber,
                'sim_id' => $simId,
                'rate_category' => 'baru',
                'status' => 'active',
            ]);
            $this->syncEmployeeUser($employee, $targetClientId, $password);

            return $employee;
        }));

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
        abort_unless($request->user()->is_super_admin ? $client->availableFor($request->user())->contains('id', $employee->client_id) : $employee->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('employees', $client->get()), 403);
        Gate::authorize('update', $employee);
        $data = $request->validated();
        $oldClientId = $employee->client_id;
        $targetClientId = $request->user()->is_super_admin && filled($data['client_id'] ?? null)
            ? (int) $data['client_id']
            : $oldClientId;
        $targetClient = $client->availableFor($request->user())->firstWhere('id', $targetClientId);
        abort_unless($targetClient !== null, 404);
        unset($data['client_id']);
        $password = $data['password'] ?? null;
        unset($data['password']);
        $client->runAs($targetClient, function () use ($data, $employee, $password, $oldClientId, $targetClientId): void {
            DB::transaction(function () use ($data, $employee, $password, $oldClientId, $targetClientId): void {
                if ($oldClientId !== $targetClientId && $employee->user) {
                    $employee->user->clients()->updateExistingPivot($oldClientId, ['status' => 'inactive', 'is_default' => false]);
                    $employee->user->clients()->updateExistingPivot($targetClientId, ['status' => 'active', 'is_default' => true]);
                }

                $employee->update($data + ['client_id' => $targetClientId]);
                $this->syncEmployeeUser($employee->fresh(), $targetClientId, $password);
            });
        });

        return response()->json(['message' => 'Karyawan berhasil diperbarui.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Employee $employee, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->is_super_admin ? $client->availableFor($request->user())->contains('id', $employee->client_id) : $employee->client_id === $client->id(), 404);
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

    private function generateSimId(int $clientId): string
    {
        Client::query()->whereKey($clientId)->lockForUpdate()->firstOrFail();

        $prefix = 'ID'.now()->format('Ym');
        $lastSequence = Employee::query()
            ->where('client_id', $clientId)
            ->where('sim_id', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('sim_id')
            ->map(function (?string $simId) use ($prefix): int {
                if (preg_match('/^'.preg_quote($prefix, '/').'([0-9]{3,})$/', (string) $simId, $matches) !== 1) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max() ?? 0;

        return $prefix.str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    private function syncEmployeeUser(Employee $employee, int $clientId, ?string $password = null): User
    {
        $user = $employee->user ?? new User;
        $isNewUser = ! $user->exists;
        $userStatus = $isNewUser ? 'active' : $user->status;
        $clientUser = $isNewUser ? null : $user->clients()->whereKey($clientId)->first();
        $clientUserStatus = $clientUser?->pivot?->status ?? $userStatus;
        $user->fill([
            'name' => $employee->full_name,
            'email' => $employee->email,
            'username' => $user->username ?? $employee->employee_no,
            'status' => $userStatus,
        ]);

        if (filled($password) || $isNewUser) {
            $user->password = $password ?: $this->defaultEmployeePassword($employee);
        }

        $user->save();
        $roleId = $clientUser?->pivot?->role_id
            ?? Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan'])->getKey();
        $user->clients()->syncWithoutDetaching([$clientId => ['role_id' => $roleId, 'is_default' => true, 'status' => $clientUserStatus]]);
        $employee->update(['user_id' => $user->getKey()]);

        return $user;
    }

    private function defaultEmployeePassword(Employee $employee): string
    {
        return $employee->birth_date ? date('dmY', strtotime($employee->birth_date)) : 'password';
    }
}
