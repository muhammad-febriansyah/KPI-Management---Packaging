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
        abort_unless($request->user()->isAdminFor($client->get()), 403);
        Gate::authorize('viewAny', Shift::class);
        $query = $client->scopeVisibleTo(
            Shift::query()->with('client:id,code,name'),
            $request->user(),
            $request->filled('client_id') ? $request->integer('client_id') : null,
        )->orderBy('name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->addColumn('client_name', fn (Shift $shift): string => $shift->client ? $shift->client->code.' — '.$shift->client->name : '—')
                ->editColumn('status', fn (Shift $shift): string => view('components.badge', [
                    'variant' => $shift->status === 'active' ? 'success' : 'neutral',
                    'slot' => $shift->status === 'active' ? 'Aktif' : 'Nonaktif',
                ])->render())
                ->addColumn('action', function (Shift $shift): string {
                    $edit = $shift->toArray();
                    $edit['target_client_id'] = $shift->client_id;

                    return '<button type="button" data-shift-edit data-url="'.route('shifts.update', ['shift' => $shift, 'client_id' => $shift->client_id]).'" data-shift="'.e(json_encode($edit)).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('shifts.destroy', ['shift' => $shift, 'client_id' => $shift->client_id]).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>';
                })
                ->rawColumns(['action', 'status'])
                ->toJson();
        }

        return view('shifts.index', [
            'currentClient' => $client->get(),
            'availableClients' => $request->user()->is_super_admin ? $client->availableFor($request->user()) : collect(),
            'user' => $request->user(),
        ]);
    }

    public function store(StoreShiftRequest $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->isAdminFor($client->get()), 403);
        $data = $request->validated();
        $targetClientId = (int) ($data['target_client_id'] ?? $client->id());
        $targetClient = $client->availableFor($request->user())->firstWhere('id', $targetClientId);
        abort_unless($targetClient !== null, 403);
        unset($data['target_client_id']);
        $shift = $client->runAs($targetClient, fn (): Shift => Shift::query()->create([...$data, 'client_id' => $targetClientId]));

        return response()->json(['message' => 'Shift berhasil ditambahkan untuk '.$targetClient->code.' — '.$targetClient->name.'.', 'data' => $shift], 201);
    }

    public function update(UpdateShiftRequest $request, Shift $shift, CurrentClientService $client): JsonResponse
    {
        abort_unless($shift->client_id === $client->id(), 404);
        abort_unless($request->user()->isAdminFor($client->get()), 403);
        Gate::authorize('update', $shift);
        $data = $request->validated();
        $targetClientId = (int) ($data['target_client_id'] ?? $client->id());
        $targetClient = $client->availableFor($request->user())->firstWhere('id', $targetClientId);
        abort_unless($targetClient !== null, 403);
        unset($data['target_client_id']);
        $client->runAs($targetClient, fn (): bool => $shift->update([...$data, 'client_id' => $targetClientId]));

        return response()->json(['message' => 'Shift berhasil diperbarui.']);
    }

    public function destroy(Request $request, Shift $shift, CurrentClientService $client): JsonResponse
    {
        abort_unless($shift->client_id === $client->id(), 404);
        abort_unless($request->user()->isAdminFor($client->get()), 403);
        Gate::authorize('delete', $shift);
        $this->deleteRestricted($shift, 'Shift tidak dapat dihapus karena masih dipakai pada realisasi kerja.');

        return response()->json(['message' => 'Shift berhasil dihapus.']);
    }
}
