<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\Product;
use App\Models\Unit;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
        Gate::authorize('viewAny', Product::class);
        $query = Product::query()->where('client_id', $client->id())->with(['unit', 'group', 'costCenter'])->orderBy('name');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->addColumn('unit_name', fn (Product $p): string => $p->unit?->name ?? '—')->addColumn('group_name', fn (Product $p): string => $p->group?->name ?? '—')->editColumn('status', fn (Product $p): string => view('components.badge', [
                'variant' => $p->status === 'active' ? 'success' : 'neutral',
                'slot' => $p->status === 'active' ? 'Aktif' : 'Nonaktif',
            ])->render())->addColumn('action', function (Product $p): string {
                $detail = e(json_encode([
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit_name' => $p->unit?->name,
                    'group_name' => $p->group?->name,
                    'cost_center_name' => $p->costCenter?->name,
                    'po_price' => $p->po_price,
                    'old_employee_rate' => $p->old_employee_rate,
                    'new_employee_rate' => $p->new_employee_rate,
                    'estimated_output_per_hour' => $p->estimated_output_per_hour,
                    'status' => $p->status,
                ]));

                return '<button type="button" data-product-detail="'.$detail.'" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#eye"></use></svg>Detail</button> <button type="button" data-product-edit data-url="'.route('products.update', $p).'" data-product="'.e($p->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button> <form method="POST" action="'.route('products.destroy', $p).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form>';
            })->rawColumns(['action', 'status'])->toJson();
        }

        return view('products.index', ['currentClient' => $client->get(), 'user' => $request->user(), 'units' => Unit::query()->where('client_id', $client->id())->where('status', 'active')->get(), 'groups' => Group::query()->where('client_id', $client->id())->where('status', 'active')->get(), 'costCenters' => CostCenter::query()->where('client_id', $client->id())->where('status', 'active')->get()]);
    }

    /**
     * Searchable options for select2, paginated so a large product catalog
     * never has to be dumped into the page as inline <option> tags.
     */
    public function options(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('products', $client->get()), 403);
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

        $paginator = $query->paginate($perPage, ['id', 'sku', 'name', 'unit_id', 'estimated_output_per_hour'], 'page', $page);

        return response()->json([
            'results' => $paginator->getCollection()->map(fn (Product $product): array => [
                'id' => $product->id,
                'text' => "{$product->sku} — {$product->name}",
                'name' => $product->name,
                'unit_name' => $product->unit?->name,
                'estimated_output_per_hour' => $product->estimated_output_per_hour,
            ]),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ]);
    }

    public function store(StoreProductRequest $request, CurrentClientService $client): JsonResponse
    {
        Gate::authorize('create', Product::class);
        Product::query()->create([...$request->validated(), 'client_id' => $client->id()]);

        return response()->json(['message' => 'Produk berhasil ditambahkan.'], 201);
    }

    public function update(UpdateProductRequest $request, Product $product, CurrentClientService $client): JsonResponse
    {
        abort_unless($product->client_id === $client->id(), 404);
        Gate::authorize('update', $product);
        $product->update($request->validated());

        return response()->json(['message' => 'Produk berhasil diperbarui.']);
    }

    public function destroy(Product $product, CurrentClientService $client): JsonResponse
    {
        abort_unless($product->client_id === $client->id(), 404);
        Gate::authorize('delete', $product);
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
