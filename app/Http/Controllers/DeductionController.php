<?php

namespace App\Http\Controllers;

use App\Exports\DeductionExport;
use App\Exports\DeductionTemplateExport;
use App\Http\Requests\StoreDeductionPeriodRequest;
use App\Http\Requests\UpdateEmployeeDeductionRequest;
use App\Imports\DeductionImport;
use App\Models\DeductionPeriod;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class DeductionController extends Controller
{
    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('deductions', $client->get()), 403);
        $query = EmployeeDeduction::query()->where('client_id', $client->id())->with(['employee', 'deductionPeriod'])->latest('created_at');
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('created_at', fn (EmployeeDeduction $deduction): string => $deduction->created_at?->format('d/m/Y') ?? '—')
                ->addColumn('periode', fn (EmployeeDeduction $deduction): string => $this->periodLabel($deduction->deductionPeriod))
                ->addColumn('sim_id', fn (EmployeeDeduction $deduction): string => $deduction->employee?->sim_id ?: ($deduction->employee?->employee_no ?? '—'))
                ->addColumn('full_name', fn (EmployeeDeduction $deduction): string => $deduction->employee?->full_name ?? '—')
                ->addColumn('bpjs_health', fn (EmployeeDeduction $deduction): string => $this->formatPercent($deduction->bpjs_health_percent))
                ->addColumn('bpjs_employment', fn (EmployeeDeduction $deduction): string => $this->formatPercent($deduction->bpjs_employment_percent))
                ->addColumn('action', fn (EmployeeDeduction $deduction): string => '<div class="flex justify-end gap-2"><button type="button" data-deduction-row-edit data-url="'.route('deductions.update', $deduction).'" data-deduction-row="'.e($deduction->toJson()).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</button><form method="POST" action="'.route('deductions.destroy', $deduction).'" data-ajax-delete class="inline"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"><svg aria-hidden="true" class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#trash"></use></svg>Hapus</button></form></div>')
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('deductions.index', ['currentClient' => $client->get(), 'user' => $request->user(), 'employees' => Employee::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('full_name')->get()]);
    }

    public function create(Request $request, CurrentClientService $client): View
    {
        abort_unless($request->user()->canAccessMenu('deductions', $client->get()), 403);

        return view('deductions.create', [
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'employees' => Employee::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('full_name')->get(),
        ]);
    }

    /**
     * "1.00%" is harder to scan than "1%". Keeps decimals only when they matter
     * (e.g. "2.5%"), so whole-number rates read as plain "2%" / "1%".
     */
    private function formatPercent(float|string $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2), '0'), '.').'%';
    }

    private function periodLabel(?DeductionPeriod $period): string
    {
        if (! $period) {
            return '—';
        }

        $label = $period->month?->locale('id')->translatedFormat('F Y') ?? '—';

        return $period->week_no ? "{$label} (Minggu {$period->week_no})" : $label;
    }

    public function store(StoreDeductionPeriodRequest $request, CurrentClientService $client): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $employeeIds = $data['employee_ids'] ?? [];
        unset($data['employee_ids']);
        $deductionData = collect($data)->except(['month', 'week_no'])->all();

        $period = DB::transaction(function () use ($data, $deductionData, $employeeIds, $client, $request): DeductionPeriod {
            $period = DeductionPeriod::query()->create(['month' => $data['month'].'-01', 'week_no' => $data['week_no'] ?? null, 'client_id' => $client->id(), 'status' => 'draft', 'created_by' => $request->user()->id]);
            foreach ($employeeIds as $employeeId) {
                $period->deductions()->create([...$deductionData, 'client_id' => $client->id(), 'employee_id' => $employeeId]);
            }

            return $period;
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Potongan gaji berhasil disimpan.', 'id' => $period->id], 201);
        }

        return to_route('deductions.index')->with('status', 'Potongan gaji berhasil disimpan.');
    }

    public function update(UpdateEmployeeDeductionRequest $request, EmployeeDeduction $deduction): JsonResponse
    {
        abort_unless($deduction->client_id === app(CurrentClientService::class)->id(), 404);
        $deduction->update($request->validated());

        return response()->json(['message' => 'Potongan gaji berhasil diperbarui.']);
    }

    public function destroy(EmployeeDeduction $deduction): JsonResponse
    {
        abort_unless($deduction->client_id === app(CurrentClientService::class)->id(), 404);
        $deduction->delete();

        return response()->json(['message' => 'Potongan gaji berhasil dihapus.']);
    }

    public function export(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('deductions', $client->get()), 403);

        return Excel::download(new DeductionExport($client->id()), 'potongan-gaji-'.now()->format('Ymd-His').'.xlsx');
    }

    public function template(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('deductions', $client->get()), 403);
        $exampleEmployee = Employee::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('full_name')->first();

        return Excel::download(new DeductionTemplateExport($exampleEmployee), 'template-potongan-gaji.xlsx');
    }

    public function import(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('deductions', $client->get()), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        $import = new DeductionImport($client->id(), $request->user()->id);
        Excel::import($import, $request->file('file'));

        if ($import->failures !== []) {
            return response()->json([
                'message' => $import->imported > 0
                    ? "{$import->imported} baris berhasil diimpor, ".count($import->failures).' baris gagal. Perbaiki lalu import ulang baris yang gagal.'
                    : 'Import gagal, tidak ada baris yang berhasil disimpan.',
                'failures' => $import->failures,
            ], $import->imported > 0 ? 207 : 422);
        }

        return response()->json(['message' => "{$import->imported} baris potongan gaji berhasil diimpor."]);
    }
}
