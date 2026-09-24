<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentClientService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AccessController extends Controller
{
    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        Gate::authorize('viewAny', Role::class);

        $query = User::query()
            ->select('users.*', 'roles.code as role_code', 'client_user.status as client_user_status')
            ->with(['employee.group'])
            ->leftJoin('client_user', function ($join) use ($client): void {
                $join->on('client_user.user_id', '=', 'users.id')
                    ->where('client_user.client_id', $client->id());
            })
            ->leftJoin('roles', 'roles.id', '=', 'client_user.role_id')
            ->where(function ($query): void {
                $query->where('users.is_super_admin', true)->orWhereNotNull('client_user.user_id');
            })
            ->orderBy('users.name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('status', function (User $user): string {
                    $status = $user->is_super_admin ? $user->status : ($user->client_user_status ?? $user->status);

                    return view('components.badge', [
                        'variant' => $status === 'active' ? 'success' : 'neutral',
                        'slot' => $status === 'active' ? 'Aktif' : 'Nonaktif',
                    ])->render();
                })->addColumn('role_name', fn (User $user): string => view('components.badge', [
                    'variant' => $this->roleVariant($user),
                    'slot' => $this->roleName($user),
                ])->render())
                ->addColumn('action', function (User $user) use ($client): string {
                    $status = $user->is_super_admin ? $user->status : ($user->client_user_status ?? $user->status);
                    $isActive = $status === 'active';
                    $label = $isActive ? 'Nonaktifkan' : 'Aktifkan';
                    $classes = $isActive ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100';
                    $icon = $isActive ? 'x-mark' : 'check-circle';
                    $buttons = [];

                    if ($user->is_super_admin) {
                        $buttons[] = '<button type="button" data-super-admin-edit data-url="'.route('settings.access.super-admins.update', $user).'" data-super-admin="'.e(json_encode($this->superAdminPayload($user))).'" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button>';
                    } elseif ($user->role_code === 'employee' && $user->employee) {
                        $buttons[] = '<button type="button" data-employee-edit data-url="'.route('employees.update', $user->employee).'" data-employee="'.e($user->employee->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button>';
                    } elseif ($user->role_code === 'client') {
                        $buttons[] = '<button type="button" data-client-edit data-url="'.route('clients.update', $client->get()).'?account_user_id='.$user->getKey().'" data-client="'.e(json_encode($this->clientPayload($user, $client->get()))).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button>';
                    }

                    $buttons[] = '<button type="button" data-user-reset data-url="'.route('settings.access.users.password', $user).'" data-user-name="'.e($user->name).'" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#lock-closed"></use></svg>Reset password</button>';

                    if ($user->getKey() !== request()->user()?->getKey()) {
                        $buttons[] = '<button type="button" data-user-status-toggle data-url="'.route('settings.access.users.status', $user).'" data-status="'.$status.'" data-user-name="'.e($user->name).'" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold '.$classes.'"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#'.$icon.'"></use></svg>'.$label.'</button>';
                        $buttons[] = '<form method="POST" action="'.route('settings.access.users.destroy', $user).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>';
                    }

                    return '<div class="flex flex-wrap justify-end gap-2">'.implode('', $buttons).'</div>';
                })->rawColumns(['status', 'role_name', 'action'])->toJson();
        }

        $roles = Role::query()->where('code', '!=', 'super-admin')->with(['permissions' => fn ($query) => $query->where('code', 'like', 'menu.%')])->orderBy('name')->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'menus' => $role->permissions
                    ->pluck('code')->map(fn (string $code): string => substr($code, 5))->all(),
            ]);

        return view('settings.access', [
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'roles' => $roles,
            'menuOptions' => config('menu_permissions'),
            'groups' => Group::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function employeeOptions(Request $request, CurrentClientService $client): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);

        $search = trim($request->string('q')->toString());
        $page = max(1, $request->integer('page', 1));
        $paginator = Employee::query()
            ->where('client_id', $client->id())
            ->where('status', 'active')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%")
                    ->orWhere('sim_id', 'like', "%{$search}%");
            }))
            ->orderBy('full_name')
            ->paginate(20, ['id', 'employee_no', 'sim_id', 'full_name', 'user_id'], 'page', $page);

        return response()->json([
            'results' => $paginator->getCollection()->map(fn (Employee $employee): array => [
                'id' => $employee->getKey(),
                'text' => $employee->full_name.' — '.$employee->employee_no,
                'name' => $employee->full_name,
                'employee_no' => $employee->employee_no,
                'sim_id' => $employee->sim_id,
                'has_account' => $employee->user_id !== null,
            ]),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ]);
    }

    public function storeEmployeeAccount(Request $request, CurrentClientService $client): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where(fn ($query) => $query
                ->where('client_id', $client->id())
                ->where('status', 'active')
                ->whereNull('user_id'))],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($client, $data): void {
            $employee = Employee::query()
                ->where('client_id', $client->id())
                ->whereNull('user_id')
                ->findOrFail($data['employee_id']);
            $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
            $user = User::query()->create([
                'name' => $employee->full_name,
                'username' => $employee->employee_no,
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active',
            ]);
            $user->clients()->attach($client->id(), [
                'role_id' => $role->getKey(),
                'is_default' => true,
                'status' => 'active',
            ]);
            $employee->update(['email' => $data['email'], 'user_id' => $user->getKey()]);
        });

        return response()->json(['message' => 'Akun Karyawan berhasil ditambahkan.'], 201);
    }

    public function clientOptions(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $search = trim($request->string('q')->toString());
        $page = max(1, $request->integer('page', 1));
        $paginator = Client::query()
            ->active()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(20, ['id', 'code', 'name'], 'page', $page);

        return response()->json([
            'results' => $paginator->getCollection()->map(fn (Client $client): array => [
                'id' => $client->getKey(),
                'text' => $client->code.' — '.$client->name,
                'name' => $client->name,
                'code' => $client->code,
            ]),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ]);
    }

    public function storeClientAccount(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $data = $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'account_name' => ['required', 'string', 'max:150'],
            'login_username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')],
            'login_email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($data): void {
            $client = Client::query()->active()->lockForUpdate()->findOrFail($data['client_id']);
            $role = Role::query()->firstOrCreate(['code' => 'client'], ['name' => 'Client']);
            $user = User::query()->create([
                'name' => $data['account_name'],
                'username' => $data['login_username'],
                'email' => $data['login_email'],
                'password' => $data['password'],
                'status' => 'active',
            ]);
            $user->clients()->attach($client->getKey(), [
                'role_id' => $role->getKey(),
                'is_default' => true,
                'status' => 'active',
            ]);
        });

        return response()->json(['message' => 'Akun Client berhasil ditambahkan.'], 201);
    }

    public function toggleUserStatus(User $user, CurrentClientService $client): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $this->assertUserVisible($user, $client);
        abort_if($user->getKey() === request()->user()?->getKey(), 422, 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');

        $currentStatus = $user->is_super_admin ? $user->status : ($user->clients()->whereKey($client->id())->first()?->pivot?->status ?? $user->status);
        $status = $currentStatus === 'active' ? 'inactive' : 'active';

        if ($user->is_super_admin && $status === 'inactive' && User::query()->where('is_super_admin', true)->where('status', 'active')->count() <= 1) {
            abort(422, 'Minimal satu Super Admin aktif harus dipertahankan.');
        }

        DB::transaction(function () use ($client, $status, $user): void {
            $user->update(['status' => $status]);
            if (! $user->is_super_admin) {
                $user->clients()->updateExistingPivot($client->id(), ['status' => $status]);
            }
        });

        return response()->json([
            'message' => $status === 'active' ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.',
            'status' => $status,
        ]);
    }

    public function storeSuperAdmin(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $data = $request->validate($this->superAdminRules());
        $data['status'] ??= 'active';
        $user = new User;
        $user->fill($data);
        $user->is_super_admin = true;
        $user->save();

        return response()->json(['message' => 'Super Admin berhasil ditambahkan.', 'data' => $user], 201);
    }

    public function updateSuperAdmin(Request $request, User $user): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        abort_unless($user->is_super_admin, 404);
        $data = $request->validate($this->superAdminRules($user));
        $user->update($data);

        return response()->json(['message' => 'Super Admin berhasil diperbarui.']);
    }

    public function resetPassword(Request $request, User $user, CurrentClientService $client): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $this->assertUserVisible($user, $client);
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);
        $user->update(['password' => $data['password']]);

        return response()->json(['message' => 'Password berhasil direset.']);
    }

    public function destroy(User $user, CurrentClientService $client): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $this->assertUserVisible($user, $client);
        abort_if($user->getKey() === request()->user()?->getKey(), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        abort_if($user->is_super_admin && User::query()->where('is_super_admin', true)->count() <= 1, 422, 'Minimal satu Super Admin harus dipertahankan.');

        try {
            DB::transaction(function () use ($user): void {
                $user->clients()->detach();
                $user->delete();
            });
        } catch (QueryException) {
            return response()->json(['message' => 'User tidak dapat dihapus karena masih dipakai pada data lain.'], 422);
        }

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    public function updateRolePermissions(Request $request, Role $role): JsonResponse
    {
        Gate::authorize('update', $role);

        $validated = $request->validate([
            'menus' => ['array'],
            'menus.*' => ['string', Rule::in(array_keys(config('menu_permissions')))],
        ]);

        $permissionIds = Permission::query()
            ->whereIn('code', array_map(fn (string $key): string => 'menu.'.$key, $validated['menus'] ?? []))
            ->pluck('id');

        $role->permissions()->sync($permissionIds);

        return response()->json(['message' => 'Hak akses menu untuk role '.$role->name.' berhasil diperbarui.']);
    }

    private function assertUserVisible(User $user, CurrentClientService $client): void
    {
        abort_unless($user->is_super_admin || $user->clients()->whereKey($client->id())->exists(), 404);
    }

    /** @return array<string, mixed> */
    private function superAdminPayload(User $user): array
    {
        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'status' => $user->status,
        ];
    }

    /** @return array<string, mixed> */
    private function clientPayload(User $user, Client $client): array
    {
        return [
            'account_user_id' => $user->getKey(),
            'code' => $client->code,
            'name' => $client->name,
            'status' => $client->status,
            'account_name' => $user->name,
            'login_username' => $user->username,
            'login_email' => $user->email,
        ];
    }

    private function roleName(User $user): string
    {
        return match ($user->is_super_admin ? 'super-admin' : $user->role_code) {
            'employee' => 'Karyawan',
            'client' => 'Client',
            'super-admin' => 'Super Admin',
            default => 'User',
        };
    }

    private function roleVariant(User $user): string
    {
        return match ($user->is_super_admin ? 'super-admin' : $user->role_code) {
            'super-admin' => 'primary',
            'employee' => 'info',
            'client' => 'success',
            default => 'neutral',
        };
    }

    /** @return array<string, array<int, string|Rule>> */
    private function superAdminRules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user?->getKey())],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->getKey())],
            'status' => [$user ? 'required' : 'sometimes', Rule::in(['active', 'inactive'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => [$user ? 'nullable' : 'required', 'string'],
        ];
    }
}
