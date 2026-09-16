<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DeletesRestrictedRecords;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Models\Shift;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ShiftController extends Controller
{
    use DeletesRestrictedRecords;

    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403);
        Gate::authorize('viewAny', Shift::class);
        $query = Shift::query()->where('client_id', $client->id())->orderBy('name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->editColumn('status', fn (Shift $shift): string => view('components.badge', [
                'variant' => $shift->status === 'active' ? 'success' : 'neutral',
                'slot' => $shift->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('action', fn (Shift $shift): string => '<button type="button" data-shift-edit data-url="'.route('shifts.update', $shift).'" data-shift="'.e($shift->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('shifts.destroy', $shift).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>')->rawColumns(['action', 'status'])->toJson();
        }

        return view('shifts.index', ['currentClient' => $client->get(), 'user' => $request->user()]);
    }

    public function store(StoreShiftRequest $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403);
        $shift = Shift::query()->create([...$request->validated(), 'client_id' => $client->id()]);

        return response()->json(['message' => 'Shift berhasil ditambahkan.', 'data' => $shift], 201);
    }

    public function update(UpdateShiftRequest $request, Shift $shift, CurrentClientService $client): JsonResponse
    {
        abort_unless($shift->client_id === $client->id(), 404);
        abort_unless($request->user()->is_super_admin, 403);
        Gate::authorize('update', $shift);
        $shift->update($request->validated());

        return response()->json(['message' => 'Shift berhasil diperbarui.']);
    }

    public function destroy(Request $request, Shift $shift, CurrentClientService $client): JsonResponse
    {
        abort_unless($shift->client_id === $client->id(), 404);
        abort_unless($request->user()->is_super_admin, 403);
        Gate::authorize('delete', $shift);
        $this->deleteRestricted($shift, 'Shift tidak dapat dihapus karena masih dipakai pada realisasi kerja.');

        return response()->json(['message' => 'Shift berhasil dihapus.']);
    }
}
