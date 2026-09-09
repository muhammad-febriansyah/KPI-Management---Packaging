<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AccessController extends Controller
{
    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        Gate::authorize('viewAny', Role::class);

        $query = User::query()->whereHas('clients', fn ($q) => $q->whereKey($client->id())->where('client_user.status', 'active'))->orderBy('name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->editColumn('status', fn (User $user): string => view('components.badge', [
                'variant' => $user->status === 'active' ? 'success' : 'neutral',
                'slot' => $user->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('role_name', fn (User $user): string => match ($user->roleCodeFor($client->get())) {
                'employee' => 'Karyawan',
                'client' => 'Client',
                'super-admin' => 'Super Admin',
                default => 'User',
            })->rawColumns(['status'])->toJson();
        }

        $roles = Role::query()->where('code', '!=', 'super-admin')->orderBy('name')->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'menus' => $role->permissions()->where('code', 'like', 'menu.%')
                    ->pluck('code')->map(fn (string $code): string => substr($code, 5))->all(),
            ]);

        return view('settings.access', [
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'roles' => $roles,
            'menuOptions' => config('menu_permissions'),
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
