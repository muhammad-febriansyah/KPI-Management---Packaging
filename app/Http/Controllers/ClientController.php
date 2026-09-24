<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{
    public function options(Request $request, CurrentClientService $currentClient): JsonResponse
    {
        abort_unless($request->user()?->status === 'active', 403);

        $search = trim($request->string('q')->toString());
        $page = max(1, $request->integer('page', 1));
        $query = Client::query()->active();

        if (! $request->user()->is_super_admin) {
            $query->whereIn('clients.id', $request->user()->clients()
                ->where('clients.status', 'active')
                ->wherePivot('status', 'active')
                ->select('clients.id'));
        }

        $paginator = $query
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20, ['id', 'code', 'name'], 'page', $page);

        return response()->json([
            'results' => $paginator->getCollection()->map(fn (Client $client): array => [
                'id' => $client->getKey(),
                'text' => "{$client->code} — {$client->name}",
                'name' => $client->name,
                'code' => $client->code,
            ]),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ]);
    }

    public function index(Request $request): View|JsonResponse
    {
        // Master Client spans every tenant on the platform — never exposed via a menu
        // checkbox, always super-admin only, regardless of ClientPolicy::viewAny.
        abort_unless($request->user()->is_super_admin, 403);
        Gate::authorize('viewAny', Client::class);
        $clientRoleId = Role::query()->where('code', 'client')->value('id');
        $query = Client::query()->orderBy('name');

        if ($clientRoleId) {
            $query->with(['users' => fn ($query) => $query
                ->wherePivot('role_id', $clientRoleId)
                ->orderByPivot('is_default', 'desc')
                ->orderBy('users.id')]);
        }

        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('status', fn (Client $client): string => view('components.badge', [
                    'variant' => $client->status === 'active' ? 'success' : 'neutral',
                    'slot' => $client->status === 'active' ? 'Aktif' : 'Nonaktif',
                ])->render())
                ->addColumn('account_name', fn (Client $client): ?string => $client->users->first()?->name)
                ->addColumn('login_username', fn (Client $client): ?string => $client->users->first()?->username)
                ->addColumn('login_email', fn (Client $client): ?string => $client->users->first()?->email)
                ->addColumn('action', function (Client $client): string {
                    $account = $client->users->first();
                    $client->setAttribute('account_name', $account?->name);
                    $client->setAttribute('login_username', $account?->username);
                    $client->setAttribute('login_email', $account?->email);
                    $clientData = e($client->toJson());

                    return '<button type="button" data-client-detail="'.$clientData.'" class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#eye"></use></svg>Detail</button> <button type="button" data-client-edit data-url="'.route('clients.update', $client).'" data-client="'.$clientData.'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('clients.destroy', $client).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>';
                })
                ->rawColumns(['action', 'status'])
                ->toJson();
        }

        return view('clients.index', ['user' => $request->user()]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        $client = DB::transaction(function () use ($data): Client {
            $client = Client::create(Arr::only($data, ['code', 'name', 'status']));
            $clientRole = Role::query()->firstOrCreate(['code' => 'client'], ['name' => 'Client']);
            $account = User::create([
                'name' => $data['account_name'],
                'username' => $data['login_username'],
                'email' => $data['login_email'],
                'password' => $data['password'],
                'status' => $data['status'],
            ]);
            $account->clients()->attach($client, [
                'role_id' => $clientRole->getKey(),
                'is_default' => true,
                'status' => $data['status'],
            ]);

            return $client;
        });

        return response()->json(['message' => 'Client berhasil ditambahkan.', 'data' => $client], 201);
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);
        $data = $request->validated();

        DB::transaction(function () use ($client, $data): void {
            $client->update(Arr::only($data, ['code', 'name', 'status']));
            $clientRole = Role::query()->firstOrCreate(['code' => 'client'], ['name' => 'Client']);
            $account = $client->users()
                ->wherePivot('role_id', $clientRole->getKey())
                ->orderByPivot('is_default', 'desc')
                ->first() ?? new User;
            $account->fill([
                'name' => $data['account_name'],
                'username' => $data['login_username'],
                'email' => $data['login_email'],
                'status' => $data['status'],
            ]);

            if (filled($data['password'] ?? null)) {
                $account->password = $data['password'];
            }

            $account->save();
            $account->clients()->syncWithoutDetaching([$client->getKey() => [
                'role_id' => $clientRole->getKey(),
                'is_default' => true,
                'status' => $data['status'],
            ]]);
        });

        return response()->json(['message' => 'Client berhasil diperbarui.']);
    }

    public function destroy(Client $client): JsonResponse
    {
        Gate::authorize('delete', $client);
        $client->delete();

        return response()->json(['message' => 'Client berhasil dihapus.']);
    }
}
