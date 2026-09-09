<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCostCenterRequest;
use App\Http\Requests\UpdateCostCenterRequest;
use App\Models\CostCenter;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CostCenterController extends Controller
{
    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('viewAny', CostCenter::class);
        $query = CostCenter::query()->where('client_id', $client->id())->orderBy('name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->editColumn('status', fn (CostCenter $item): string => view('components.badge', [
                'variant' => $item->status === 'active' ? 'success' : 'neutral',
                'slot' => $item->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('action', fn (CostCenter $item): string => '<button type="button" data-cost-center-edit data-url="'.route('cost-centers.update', $item).'" data-cost-center="'.e($item->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('cost-centers.destroy', $item).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>')->rawColumns(['action', 'status'])->toJson();
        }

        return view('cost-centers.index', ['currentClient' => $client->get(), 'user' => $request->user()]);
    }

    public function create(Request $request, CurrentClientService $client): View
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('viewAny', CostCenter::class);

        return view('cost-centers.create', ['currentClient' => $client->get(), 'user' => $request->user()]);
    }

    /**
     * Active cost centers for other forms' selects (e.g. the product form's quick-manage
     * modal), so a newly added cost center is selectable without a full page reload.
     */
    public function options(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('viewAny', CostCenter::class);

        $costCenters = CostCenter::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('name')->get();

        return response()->json(['results' => $costCenters->map(fn (CostCenter $item): array => ['id' => $item->id, 'text' => $item->name])]);
    }

    public function store(StoreCostCenterRequest $request, CurrentClientService $client): RedirectResponse|JsonResponse
    {
        CostCenter::query()->create([...$request->validated(), 'client_id' => $client->id()]);

        return $request->expectsJson() || $request->ajax() ? response()->json(['message' => 'Cost center berhasil ditambahkan.']) : to_route('cost-centers.index')->with('status', 'Cost center berhasil ditambahkan.');
    }

    public function edit(Request $request, CostCenter $costCenter, CurrentClientService $client): View
    {
        abort_unless($costCenter->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('update', $costCenter);

        return view('cost-centers.edit', ['costCenter' => $costCenter, 'currentClient' => $client->get(), 'user' => $request->user()]);
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter, CurrentClientService $client): RedirectResponse|JsonResponse
    {
        abort_unless($costCenter->client_id === $client->id(), 404);
        Gate::authorize('update', $costCenter);
        $costCenter->update($request->validated());

        return $request->expectsJson() || $request->ajax() ? response()->json(['message' => 'Cost center berhasil diperbarui.']) : to_route('cost-centers.index')->with('status', 'Cost center berhasil diperbarui.');
    }

    public function destroy(Request $request, CostCenter $costCenter, CurrentClientService $client): JsonResponse|RedirectResponse
    {
        abort_unless($costCenter->client_id === $client->id(), 404);
        Gate::authorize('delete', $costCenter);
        $costCenter->delete();

        return $request->expectsJson() || $request->ajax() ? response()->json(['message' => 'Cost center berhasil dihapus.']) : to_route('cost-centers.index');
    }
}
