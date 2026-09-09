<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class GroupController extends Controller
{
    public function index(Request $request, CurrentClientService $currentClient): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $currentClient->get()), 403);
        Gate::authorize('viewAny', Group::class);
        $query = Group::query()->where('client_id', $currentClient->id())->orderBy('name');

        if ($request->expectsJson() || $request->has('draw')) {
            return DataTables::eloquent($query)
                ->addColumn('action', fn (Group $group): string => '<button type="button" data-group-edit data-url="'.route('groups.update', $group).'" data-group="'.e($group->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('groups.destroy', $group).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>')
                ->editColumn('status', fn (Group $group): string => view('components.badge', [
                    'variant' => $group->status === 'active' ? 'success' : 'neutral',
                    'slot' => $group->status === 'active' ? 'Aktif' : 'Nonaktif',
                ])->render())
                ->rawColumns(['action', 'status'])
                ->toJson();
        }

        return view('groups.index', ['currentClient' => $currentClient->get(), 'user' => $request->user()]);
    }

    public function create(Request $request, CurrentClientService $currentClient): View
    {
        abort_unless($request->user()->canAccessMenu('products', $currentClient->get()), 403);
        Gate::authorize('create', Group::class);

        return view('groups.create', ['currentClient' => $currentClient->get(), 'user' => $request->user()]);
    }

    /**
     * Active groups for other forms' selects (e.g. the product form's quick-manage modal),
     * so a newly added group is selectable without a full page reload.
     */
    public function options(Request $request, CurrentClientService $currentClient): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $currentClient->get()), 403);
        Gate::authorize('viewAny', Group::class);

        $groups = Group::query()->where('client_id', $currentClient->id())->where('status', 'active')->orderBy('name')->get();

        return response()->json(['results' => $groups->map(fn (Group $group): array => ['id' => $group->id, 'text' => $group->name])]);
    }

    public function store(StoreGroupRequest $request, CurrentClientService $currentClient): RedirectResponse|JsonResponse
    {
        Group::query()->create([...$request->validated(), 'client_id' => $currentClient->id()]);

        return $request->expectsJson() || $request->ajax() ? response()->json(['message' => 'Group berhasil ditambahkan.']) : to_route('groups.index')->with('status', 'Group berhasil ditambahkan.');
    }

    public function edit(Request $request, Group $group, CurrentClientService $currentClient): View
    {
        abort_unless($group->client_id === $currentClient->id(), 404);
        abort_unless($request->user()->canAccessMenu('products', $currentClient->get()), 403);
        Gate::authorize('update', $group);

        return view('groups.edit', ['group' => $group, 'currentClient' => $currentClient->get(), 'user' => $request->user()]);
    }

    public function update(UpdateGroupRequest $request, Group $group, CurrentClientService $currentClient): RedirectResponse|JsonResponse
    {
        abort_unless($group->client_id === $currentClient->id(), 404);
        Gate::authorize('update', $group);
        $group->update($request->validated());

        return $request->expectsJson() || $request->ajax() ? response()->json(['message' => 'Group berhasil diperbarui.']) : to_route('groups.index')->with('status', 'Group berhasil diperbarui.');
    }

    public function destroy(Request $request, Group $group, CurrentClientService $currentClient): RedirectResponse|JsonResponse
    {
        abort_unless($group->client_id === $currentClient->id(), 404);
        Gate::authorize('delete', $group);
        $group->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Group berhasil dihapus.']);
        }

        return to_route('groups.index')->with('status', 'Group berhasil dihapus.');
    }
}
