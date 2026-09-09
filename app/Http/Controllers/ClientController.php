<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        // Master Client spans every tenant on the platform — never exposed via a menu
        // checkbox, always super-admin only, regardless of ClientPolicy::viewAny.
        abort_unless($request->user()->is_super_admin, 403);
        Gate::authorize('viewAny', Client::class);
        $query = Client::query()->orderBy('name');

        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('status', fn (Client $client): string => view('components.badge', [
                    'variant' => $client->status === 'active' ? 'success' : 'neutral',
                    'slot' => $client->status === 'active' ? 'Aktif' : 'Nonaktif',
                ])->render())
                ->addColumn('action', fn (Client $client): string => '<button type="button" data-client-edit data-url="'.route('clients.update', $client).'" data-client="'.e($client->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('clients.destroy', $client).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>')
                ->rawColumns(['action', 'status'])
                ->toJson();
        }

        return view('clients.index', ['user' => $request->user()]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        Client::create($request->validated());

        return response()->json(['message' => 'Client berhasil ditambahkan.'], 201);
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);
        $client->update($request->validated());

        return response()->json(['message' => 'Client berhasil diperbarui.']);
    }

    public function destroy(Client $client): JsonResponse
    {
        Gate::authorize('delete', $client);
        $client->delete();

        return response()->json(['message' => 'Client berhasil dihapus.']);
    }
}
