<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Product;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /**
     * Global appbar search: looks up employees, products, and static report
     * shortcuts for the current client, capped at a handful of results per
     * group so the dropdown stays scannable.
     */
    public function index(Request $request, CurrentClientService $client): JsonResponse
    {
        $search = trim((string) $request->string('q'));

        if (mb_strlen($search) < 2) {
            return response()->json(['employees' => [], 'products' => [], 'reports' => []]);
        }

        $employees = Gate::allows('viewAny', Employee::class)
            ? Employee::query()
                ->where('client_id', $client->id())
                ->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%"))
                ->orderBy('full_name')
                ->limit(5)
                ->get(['id', 'employee_no', 'full_name'])
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'title' => $employee->full_name,
                    'subtitle' => $employee->employee_no,
                    'url' => route('employees.index', ['q' => $employee->full_name]),
                ])
            : collect();

        $products = Gate::allows('viewAny', Product::class)
            ? Product::query()
                ->where('client_id', $client->id())
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'sku', 'name'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'title' => $product->name,
                    'subtitle' => $product->sku,
                    'url' => route('products.index', ['q' => $product->name]),
                ])
            : collect();

        $reports = collect([
            ['title' => 'Laporan Payroll', 'subtitle' => 'Rekap gaji karyawan', 'url' => route('reports.payroll')],
            ['title' => 'Laporan Realisasi Kerja', 'subtitle' => 'Rekap hasil kerja harian', 'url' => route('reports.work')],
            ['title' => 'Log Audit', 'subtitle' => 'Riwayat aktivitas pengguna', 'url' => route('audit.index')],
        ])->filter(fn (array $report): bool => Str::contains($report['title'], $search, true))->values();

        return response()->json(['employees' => $employees, 'products' => $products, 'reports' => $reports]);
    }
}
