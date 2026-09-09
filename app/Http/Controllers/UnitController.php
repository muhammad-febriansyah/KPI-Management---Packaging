<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function index(Request $request, CurrentClientService $currentClient): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $currentClient->get()), 403);
        Gate::authorize('viewAny', Unit::class);
        $query = Unit::query()->where('client_id', $currentClient->id())->orderBy('name');

        if ($request->expectsJson() || $request->has('draw')) {
            return DataTables::eloquent($query)
                ->addColumn('action', fn (Unit $unit): string => view('components.table-actions', [
                    'modalEditUrl' => route('units.edit', $unit),
                    'modalUpdateUrl' => route('units.update', $unit),
                    'deleteRoute' => route('units.destroy', $unit),
                ])->render())
                ->editColumn('status', fn (Unit $unit): string => view('components.badge', [
                    'variant' => $unit->status === 'active' ? 'success' : 'neutral',
                    'slot' => $unit->status === 'active' ? 'Aktif' : 'Nonaktif',
                ])->render())
                ->rawColumns(['action', 'status'])
                ->toJson();
        }

        return view('units.index', ['currentClient' => $currentClient->get(), 'user' => $request->user()]);
    }

    /**
     * The create form now lives in a modal on the index page.
     */
    public function create(): RedirectResponse
    {
        return to_route('units.index');
    }

    /**
     * Active units for other forms' selects (e.g. the product form's quick-manage modal),
     * so a newly added unit is selectable without a full page reload.
     */
    public function options(Request $request, CurrentClientService $currentClient): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $currentClient->get()), 403);
        Gate::authorize('viewAny', Unit::class);

        $units = Unit::query()->where('client_id', $currentClient->id())->where('status', 'active')->orderBy('name')->get();

        return response()->json(['results' => $units->map(fn (Unit $unit): array => ['id' => $unit->id, 'text' => $unit->name])]);
    }

    public function store(StoreUnitRequest $request, CurrentClientService $currentClient): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Unit::class);

        Unit::query()->create([...$request->validated(), 'client_id' => $currentClient->id()]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Satuan berhasil ditambahkan.'], 201);
        }

        return to_route('units.index')->with('status', 'Satuan berhasil ditambahkan.');
    }

    /**
     * Returns the unit as JSON so the edit modal can prefill its fields.
     */
    public function edit(Request $request, Unit $unit, CurrentClientService $currentClient): RedirectResponse|JsonResponse
    {
        abort_unless($unit->client_id === $currentClient->id(), 404);
        abort_unless($request->user()->canAccessMenu('products', $currentClient->get()), 403);
        Gate::authorize('update', $unit);

        if (! $request->wantsJson()) {
            return to_route('units.index');
        }

        return response()->json([
            'id' => $unit->getKey(),
            'code' => $unit->code,
            'name' => $unit->name,
            'status' => $unit->status,
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit, CurrentClientService $currentClient): RedirectResponse|JsonResponse
    {
        abort_unless($unit->client_id === $currentClient->id(), 404);
        Gate::authorize('update', $unit);
        $unit->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Satuan berhasil diperbarui.']);
        }

        return to_route('units.index')->with('status', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Request $request, Unit $unit, CurrentClientService $currentClient): RedirectResponse|JsonResponse
    {
        abort_unless($unit->client_id === $currentClient->id(), 404);
        Gate::authorize('delete', $unit);
        $unit->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Satuan berhasil dihapus.']);
        }

        return to_route('units.index')->with('status', 'Satuan berhasil dihapus.');
    }
}
