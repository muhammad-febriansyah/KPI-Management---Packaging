<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentClientService;
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
            ->select('users.*', 'roles.code as role_code')
            ->join('client_user', function ($join) use ($client): void {
                $join->on('client_user.user_id', '=', 'users.id')
                    ->where('client_user.client_id', $client->id())
                    ->where('client_user.status', 'active');
            })
            ->join('roles', 'roles.id', '=', 'client_user.role_id')
            ->orderBy('users.name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->editColumn('status', fn (User $user): string => view('components.badge', [
                'variant' => $user->status === 'active' ? 'success' : 'neutral',
                'slot' => $user->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('role_name', fn (User $user): string => match ($user->role_code) {
                'employee' => 'Karyawan',
                'client' => 'Client',
                'super-admin' => 'Super Admin',
                default => 'User',
            })->addColumn('action', function (User $user): string {
                $isActive = $user->status === 'active';
                $label = $isActive ? 'Nonaktifkan' : 'Aktifkan';
                $classes = $isActive ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100';
                $icon = $isActive ? 'x-mark' : 'check-circle';

                return '<button type="button" data-user-status-toggle data-url="'.route('settings.access.users.status', $user).'" data-status="'.$user->status.'" data-user-name="'.e($user->name).'" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold '.$classes.'"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#'.$icon.'"></use></svg>'.$label.'</button>';
            })->rawColumns(['status', 'action'])->toJson();
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
        ]);
    }

    public function toggleUserStatus(User $user, CurrentClientService $client): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        abort_unless($user->clients()->whereKey($client->id())->exists(), 404);
        abort_if($user->is_super_admin, 403);

        $status = $user->status === 'active' ? 'inactive' : 'active';

        DB::transaction(function () use ($client, $status, $user): void {
            $user->update(['status' => $status]);
            $user->clients()->updateExistingPivot($client->id(), ['status' => $status]);
        });

        return response()->json([
            'message' => $status === 'active' ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.',
            'status' => $status,
        ]);
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
}
