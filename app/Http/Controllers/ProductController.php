<?php

namespace App\Http\Controllers;

use App\Exports\ProductTemplateExport;
use App\Http\Controllers\Concerns\DeletesRestrictedRecords;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Imports\ProductImport;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\Product;
use App\Models\Unit;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    use DeletesRestrictedRecords;

    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('viewAny', Product::class);
        $currentClient = $client->get();
        $availableClientIds = $client->availableFor($request->user())->modelKeys();
        $selectedClientId = $request->integer('client_id');
        $query = Product::query()
            ->when(
                $request->user()->is_super_admin,
                fn ($query) => $query->withoutGlobalScopes()
                    ->whereIn('products.client_id', $availableClientIds)
                    ->when(in_array($selectedClientId, $availableClientIds, true), fn ($query) => $query->where('products.client_id', $selectedClientId)),
                fn ($query) => $query->where('client_id', $client->id()),
            )
            ->with(['client', 'unit', 'group', 'costCenter'])
            ->orderBy('name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->addColumn('client_name', fn (Product $p): string => $p->client?->name ?? '—')->addColumn('unit_name', fn (Product $p): string => $p->unit?->name ?? '—')->addColumn('group_name', fn (Product $p): string => $p->group?->name ?? '—')->editColumn('status', fn (Product $p): string => view('components.badge', [
                'variant' => $p->status === 'active' ? 'success' : 'neutral',
                'slot' => $p->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('action', function (Product $p): string {
                $detail = e(json_encode([
                    'client_code' => $p->client?->code,
                    'client_name' => $p->client?->name,
                    'client_status' => $p->client?->status,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit_name' => $p->unit?->name,
                    'group_name' => $p->group?->name,
                    'cost_center_name' => $p->costCenter?->name,
                    'po_price' => $p->po_price,
                    'employee_rate' => $p->employee_rate,
                    'estimated_output_per_hour' => $p->estimated_output_per_hour,
                    'status' => $p->status,
                ]));
                $edit = e(json_encode([
                    'id' => $p->id,
                    'client_id' => $p->client_id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit_id' => $p->unit_id,
                    'unit_name' => $p->unit?->name,
                    'group_id' => $p->group_id,
                    'group_name' => $p->group?->name,
                    'cost_center_id' => $p->cost_center_id,
                    'cost_center_name' => $p->costCenter?->name,
                    'po_price' => $p->po_price,
                    'employee_rate' => $p->employee_rate,
                    'estimated_output_per_hour' => $p->estimated_output_per_hour,
                    'status' => $p->status,
                ]));

                return '<button type="button" data-product-detail="'.$detail.'" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#eye"></use></svg>Detail</button> <button type="button" data-product-edit data-url="'.route('products.update', $p).'" data-product="'.$edit.'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('products.destroy', $p).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>';
            })->rawColumns(['action', 'status'])->toJson();
        }

        return view('products.index', [
            'currentClient' => $currentClient,
            'user' => $request->user(),
            'availableClients' => $request->user()->is_super_admin ? $client->availableFor($request->user()) : collect(),
            'units' => Unit::query()->withoutGlobalScopes()->where('status', 'active')->orderBy('name')->get(),
            'groups' => Group::query()->withoutGlobalScopes()->where('status', 'active')->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->withoutGlobalScopes()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    /**
     * Searchable options for select2, paginated so a large product catalog
     * never has to be dumped into the page as inline <option> tags.
     */
    public function options(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()) || $request->user()->canAccessMenu('realizations', $client->get()), 403);
        Gate::authorize('viewAny', Product::class);

        $search = trim((string) $request->string('q'));
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 20;

        $query = Product::query()
            ->where('client_id', $client->id())
            ->where('status', 'active')
            ->with('unit')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('sku', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->orderBy('name');

        $paginator = $query->paginate($perPage, ['id', 'sku', 'name', 'unit_id', 'employee_rate', 'estimated_output_per_hour'], 'page', $page);

        return response()->json([
            'results' => $paginator->getCollection()->map(fn (Product $product): array => [
                'id' => $product->id,
                'text' => "{$product->sku} — {$product->name}",
                'name' => $product->name,
                'unit_name' => $product->unit?->name,
                'employee_rate' => $product->employee_rate,
                'estimated_output_per_hour' => $product->estimated_output_per_hour,
            ]),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ]);
    }

    public function template(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);

        $clientModel = $client->get();

        return Excel::download(new ProductTemplateExport(
            $clientModel,
            Unit::query()->where('client_id', $client->id())->active()->orderBy('code')->first(),
            Group::query()->where('client_id', $client->id())->active()->orderBy('code')->first(),
            CostCenter::query()->where('client_id', $client->id())->active()->orderBy('code')->first(),
        ), 'template-master-produk.xlsx');
    }

    public function import(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('create', Product::class);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        $import = new ProductImport($client->id(), $client->availableFor($request->user())->modelKeys());
        Excel::import($import, $request->file('file'));

        if ($import->failures !== []) {
            return response()->json([
                'message' => $import->imported > 0
                    ? "{$import->imported} baris berhasil diimpor, ".count($import->failures).' baris gagal. Perbaiki lalu import ulang baris yang gagal.'
                    : 'Import gagal, tidak ada baris yang berhasil disimpan.',
                'failures' => $import->failures,
            ], $import->imported > 0 ? 207 : 422);
        }

        return response()->json(['message' => "{$import->imported} baris produk berhasil diimpor."]);
    }

    public function store(StoreProductRequest $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('create', Product::class);
        $data = $request->validated();
        $targetClient = Client::query()->active()->findOrFail($data['client_id']);
        $client->runAs($targetClient, fn (): Product => Product::query()->create($data));

        return response()->json(['message' => 'Produk berhasil ditambahkan.'], 201);
    }

    public function update(UpdateProductRequest $request, Product $product, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('update', $product);
        $product->update($request->validated());

        return response()->json(['message' => 'Produk berhasil diperbarui.']);
    }

    public function destroy(Request $request, Product $product, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('delete', $product);
        $this->deleteRestricted($product, 'Produk tidak dapat dihapus karena masih dipakai pada batch atau realisasi kerja.');

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
