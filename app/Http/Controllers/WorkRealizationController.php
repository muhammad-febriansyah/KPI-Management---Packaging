<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignWorkRealizationRequest;
use App\Http\Requests\StoreWorkRealizationRequest;
use App\Http\Requests\UpdateWorkRealizationBySuperAdminRequest;
use App\Http\Requests\UpdateWorkRealizationRequest;
use App\Models\Batch;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkRealization;
use App\Notifications\RealizationAssigned;
use App\Notifications\RealizationSubmitted;
use App\Services\CurrentClientService;
use App\Services\RichTextSanitizer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class WorkRealizationController extends Controller
{
    private function generateNextBatchNumber(int $clientId, ?string $workDate = null, bool $lock = false): string
    {
        $date = $workDate === null ? now() : Carbon::createFromFormat('Y-m-d', $workDate);
        $prefix = 'B-'.$date->format('Ymd').'-';
        $query = Batch::query()
            ->where('client_id', $clientId)
            ->where('batch_no', 'like', $prefix.'%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastSequence = $query->get(['batch_no'])
            ->map(function (Batch $batch) use ($prefix): ?int {
                $suffix = substr($batch->batch_no, strlen($prefix));

                return preg_match('/^\d{4}$/', $suffix) === 1 ? (int) $suffix : null;
            })
            ->filter(fn (?int $sequence): bool => $sequence !== null)
            ->max();

        if ($lastSequence !== null && $lastSequence >= 9999) {
            throw ValidationException::withMessages(['batch_no' => 'Nomor batch untuk tanggal tersebut sudah mencapai batas maksimal.']);
        }

        return $prefix.str_pad((string) (($lastSequence ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        Gate::authorize('viewAny', WorkRealization::class);
        $query = WorkRealization::query()
            ->select(['id', 'client_id', 'created_by', 'work_date', 'shift_id', 'batch_id', 'product_id', 'sku_snapshot', 'product_name_snapshot', 'total_output'])
            ->where('client_id', $client->id())
            ->with([
                'shift:id,client_id,name',
                'batch:id,client_id,batch_no',
                'product:id,client_id,sku,name,employee_rate',
                'employeeAssignments:id,client_id,work_realization_id,employee_id,rate_per_unit_snapshot,allocation_output',
            ])
            ->withCount('employeeAssignments')
            ->latest('work_date');
        if (in_array($request->user()->roleCodeFor($client->get()), ['leader', 'employee'], true)) {
            $query->where(function ($query) use ($request): void {
                $query->where('created_by', $request->user()->id)
                    ->orWhereHas('employeeAssignments.employee', fn ($employeeQuery) => $employeeQuery->where('user_id', $request->user()->id));
            });
        }
        if ($request->has('draw') || $request->expectsJson()) {
            $canEditResult = in_array($request->user()->roleCodeFor($client->get()), ['leader', 'employee'], true);

            return DataTables::eloquent($query)->editColumn('work_date', fn (WorkRealization $i): string => $i->work_date?->format('d/m/Y') ?? '—')->editColumn('total_output', fn (WorkRealization $i): string => $this->formatQuantity($i->total_output))->addColumn('shift_name', fn (WorkRealization $i): string => $i->shift?->name ?? '—')->addColumn('batch_label', fn (WorkRealization $i): string => $i->batch?->batch_no ?? '—')->addColumn('product_label', fn (WorkRealization $i): string => $i->product_name_snapshot ?? '—')->addColumn('total_price', fn (WorkRealization $i): string => 'Rp '.number_format($this->totalPrice($i), 0, ',', '.'))->addColumn('assignment_count', fn (WorkRealization $i): int => $i->employee_assignments_count)->addColumn('action', function (WorkRealization $i) use ($request, $canEditResult): string {
                $detailAction = '<a href="'.route('realizations.show', $i).'" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#eye"></use></svg>Detail</a>';
                $editAction = $request->user()->is_super_admin
                    ? ' <a href="'.route('realizations.edit', $i).'" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit</a>'
                    : ($canEditResult ? ' <a href="'.route('realizations.edit', $i).'" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700"><svg class="size-4 fill-none stroke-current"><use href="/images/heroicons.svg#pencil"></use></svg>Edit hasil</a>' : '');

                return $detailAction.$editAction;
            })->rawColumns(['action'])->toJson();
        }

        return view('realizations.index', [
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'employees' => in_array($request->user()->roleCodeFor($client->get()), ['admin', 'leader', 'employee'], true)
                ? Employee::query()->select(['id', 'client_id', 'user_id', 'employee_no', 'full_name', 'group_id', 'rate_category'])->where('client_id', $client->id())->where('status', 'active')->whereNotNull('user_id')->with('group:id,client_id,name')->orderBy('full_name')->get()
                : collect(),
        ]);
    }

    public function bulkDestroy(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        abort_unless($request->user()->is_super_admin, 403);

        $validated = $request->validate([
            'realization_ids' => ['required', 'array', 'min:1'],
            'realization_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('work_realizations', 'id')->where(fn ($query) => $query->where('client_id', $client->id())),
            ],
        ]);

        $realizations = WorkRealization::query()
            ->where('client_id', $client->id())
            ->whereIn('id', $validated['realization_ids'])
            ->get(['id', 'result_image_path']);

        DB::transaction(function () use ($realizations): void {
            $realizations->each->delete();
        });

        $realizations->pluck('result_image_path')->filter()->each(fn (string $path): bool => Storage::disk('public')->delete($path));

        return response()->json(['message' => $realizations->count().' realisasi berhasil dihapus.']);
    }

    private function formatQuantity(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    }

    private function totalPrice(WorkRealization $realization): float
    {
        $rate = $realization->employeeAssignments->first()?->rate_per_unit_snapshot
            ?? $realization->product?->employee_rate
            ?? 0;

        return (float) ($realization->total_output ?? 0) * (float) $rate;
    }

    private function rebalanceAssignments(WorkRealization $realization): void
    {
        $assignments = $realization->employeeAssignments()->get()->values();
        $assignmentCount = $assignments->count();

        if ($assignmentCount === 0) {
            return;
        }

        $totalOutput = $realization->total_output === null ? null : (float) $realization->total_output;
        $rate = (float) ($assignments->first()->rate_per_unit_snapshot ?? 0);
        $totalGrossAmount = $totalOutput === null ? 0 : (int) round($totalOutput * $rate);
        $sharedOutput = $totalOutput === null ? null : round($totalOutput / $assignmentCount, 3);
        $sharedGrossAmount = intdiv($totalGrossAmount, $assignmentCount);
        $grossRemainder = $totalGrossAmount % $assignmentCount;

        foreach ($assignments as $index => $assignment) {
            $isLastAssignment = $index === $assignmentCount - 1;
            $assignment->update([
                'allocation_output' => $sharedOutput === null || ! $isLastAssignment
                    ? $sharedOutput
                    : round($totalOutput - ($sharedOutput * ($assignmentCount - 1)), 3),
                'gross_amount' => $sharedGrossAmount + ($isLastAssignment ? $grossRemainder : 0),
            ]);
        }
    }

    public function create(Request $request, CurrentClientService $client): View
    {
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        Gate::authorize('create', WorkRealization::class);

        return view('realizations.create', [
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'defaultBatchNumber' => $this->generateNextBatchNumber($client->id()),
            'employees' => Employee::query()
                ->select(['id', 'client_id', 'user_id', 'employee_no', 'full_name', 'group_id', 'rate_category'])
                ->where('client_id', $client->id())
                ->where('status', 'active')
                ->whereNotNull('user_id')
                ->with('group:id,client_id,name')
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    public function show(Request $request, WorkRealization $realization, CurrentClientService $client): View
    {
        abort_unless($realization->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        abort_unless($request->user()->can('view', $realization), 404);

        return view('realizations.show', ['realization' => $realization->load(['shift', 'batch', 'product', 'employeeAssignments.employee']), 'currentClient' => $client->get(), 'user' => $request->user()]);
    }

    public function edit(Request $request, WorkRealization $realization, CurrentClientService $client): View
    {
        abort_unless($realization->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        $isSuperAdmin = $request->user()->is_super_admin;
        Gate::authorize($isSuperAdmin ? 'adminUpdate' : 'update', $realization);

        return view('realizations.edit', [
            'realization' => $realization->load(['shift', 'batch', 'product.unit', 'employeeAssignments.employee']),
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'isSuperAdmin' => $isSuperAdmin,
            'employees' => $isSuperAdmin
                ? Employee::query()->select(['id', 'client_id', 'user_id', 'employee_no', 'full_name', 'group_id', 'rate_category'])->where('client_id', $client->id())->where('status', 'active')->whereNotNull('user_id')->with('group:id,client_id,name')->orderBy('full_name')->get()
                : collect(),
        ]);
    }

    public function shiftOptions(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        $search = trim((string) $request->string('q'));
        $shifts = Shift::query()->where('client_id', $client->id())->where('status', 'active')->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))->orderBy('name')->limit(30)->get();

        return response()->json(['results' => $shifts->map(fn (Shift $shift): array => ['id' => $shift->id, 'text' => $shift->name])]);
    }

    public function batchOptions(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        $search = trim((string) $request->string('q'));
        $productId = $request->filled('product_id') ? $request->integer('product_id') : null;
        $batches = Batch::query()
            ->with(['product:id,client_id,sku,name,unit_id,employee_rate,estimated_output_per_hour', 'product.unit:id,client_id,name'])
            ->where('client_id', $client->id())
            ->where('status', 'active')
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($search !== '', fn ($query) => $query->where('batch_no', 'like', "%{$search}%"))
            ->orderBy('batch_no')->limit(30)->get();

        return response()->json(['results' => $batches->map(function (Batch $batch): array {
            $product = $batch->product;

            return [
                'id' => $batch->id,
                'text' => $batch->batch_no,
                'product_id' => $batch->product_id,
                'product' => $product ? [
                    'id' => $product->id,
                    'text' => "{$product->sku} — {$product->name}",
                    'name' => $product->name,
                    'unit_name' => $product->unit?->name,
                    'employee_rate' => $product->employee_rate,
                    'estimated_output_per_hour' => $product->estimated_output_per_hour,
                ] : null,
            ];
        })]);
    }

    public function nextBatchNumber(Request $request, CurrentClientService $client): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        $request->validate(['work_date' => ['nullable', 'date']]);

        return response()->json([
            'batch_no' => $this->generateNextBatchNumber($client->id(), $request->input('work_date')),
        ]);
    }

    public function store(StoreWorkRealizationRequest $request, CurrentClientService $client, RichTextSanitizer $sanitizer): JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        Gate::authorize('create', WorkRealization::class);

        $data = $request->validated();
        $data['report'] = $sanitizer->sanitize($data['report'] ?? null);
        if ($request->hasFile('result_image')) {
            $data['result_image_path'] = $request->file('result_image')->store('realizations', 'public');
        }

        unset($data['result_image']);

        $product = filled($data['product_id'] ?? null)
            ? Product::query()->where('client_id', $client->id())->findOrFail($data['product_id'])
            : null;
        $employeeIds = array_values(array_filter($data['employee_ids'] ?? [], fn ($employeeId): bool => filled($employeeId)));
        if ($employeeIds === [] && in_array($request->user()->roleCodeFor($client->get()), ['leader', 'employee'], true)) {
            $employeeIds = [Employee::query()
                ->where('client_id', $client->id())
                ->where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->firstOrFail()
                ->getKey()];
        }
        unset($data['employee_ids']);

        $hasBatchNumber = filled($data['batch_no'] ?? null);
        unset($data['batch_no']);

        DB::transaction(function () use ($data, $hasBatchNumber, $employeeIds, $client, $product, $request): void {
            if ($hasBatchNumber) {
                $batchNumber = $this->generateNextBatchNumber($client->id(), $data['work_date'] ?? null, true);
                $batch = Batch::query()->firstOrCreate(
                    ['client_id' => $client->id(), 'batch_no' => $batchNumber],
                    ['product_id' => $product?->getKey(), 'status' => 'active'],
                );
                $data['batch_id'] = $batch->getKey();
            }

            $realization = WorkRealization::query()->create($data + [
                'client_id' => $client->id(),
                'sku_snapshot' => $product?->sku,
                'product_name_snapshot' => $product?->name,
                'unit_name_snapshot' => $product?->unit?->name,
                'created_by' => $request->user()->id,
                'is_complaint' => false,
            ]);

            foreach ($employeeIds as $employeeId) {
                $employee = Employee::query()->where('client_id', $client->id())->findOrFail($employeeId);
                $rate = $product?->employee_rate ?? 0;
                $realization->employeeAssignments()->create([
                    'client_id' => $client->id(),
                    'employee_id' => $employee->id,
                    'rate_category_snapshot' => $employee->rate_category,
                    'rate_per_unit_snapshot' => $rate,
                    'allocation_output' => $data['total_output'] ?? null,
                    'gross_amount' => ($data['total_output'] ?? null) === null ? 0 : (int) round((float) $data['total_output'] * (float) $rate),
                ]);
                $employee->user?->notify(new RealizationAssigned($realization));
            }
            $this->rebalanceAssignments($realization);
        });

        return response()->json(['message' => 'Realisasi berhasil disimpan.'], 201);
    }

    public function update(UpdateWorkRealizationRequest $request, WorkRealization $realization, CurrentClientService $client, RichTextSanitizer $sanitizer): JsonResponse
    {
        abort_unless($realization->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        Gate::authorize('update', $realization);

        $data = $request->validated();
        $data['report'] = $sanitizer->sanitize($data['report'] ?? null);
        $oldImagePath = $realization->result_image_path;
        $newImagePath = null;

        if ($request->hasFile('result_image')) {
            $newImagePath = $request->file('result_image')->store('realizations', 'public');
            $data['result_image_path'] = $newImagePath;
        }

        unset($data['result_image']);

        DB::transaction(function () use ($data, $realization): void {
            $realization->update($data);
            $this->rebalanceAssignments($realization);
        });

        if ($newImagePath !== null && $oldImagePath !== null) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $notification = new RealizationSubmitted($realization, $request->user()->name);
        User::query()->where('is_super_admin', true)->get()->each->notify($notification);

        return response()->json(['message' => 'Realisasi berhasil dikirim.']);
    }

    public function adminUpdate(UpdateWorkRealizationBySuperAdminRequest $request, WorkRealization $realization, CurrentClientService $client, RichTextSanitizer $sanitizer): JsonResponse
    {
        abort_unless($realization->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        abort_unless($request->user()->is_super_admin, 403);
        Gate::authorize('adminUpdate', $realization);

        $data = $request->validated();
        $data['report'] = $sanitizer->sanitize($data['report'] ?? null);
        $oldImagePath = $realization->result_image_path;
        $newImagePath = null;

        if ($request->hasFile('result_image')) {
            $newImagePath = $request->file('result_image')->store('realizations', 'public');
            $data['result_image_path'] = $newImagePath;
        }

        $product = filled($data['product_id'] ?? null)
            ? Product::query()->where('client_id', $client->id())->with('unit')->findOrFail($data['product_id'])
            : null;
        $employeeIds = array_values(array_filter($data['employee_ids'] ?? [], fn ($employeeId): bool => filled($employeeId)));
        $batchNumber = $data['batch_no'] ?? null;
        unset($data['result_image'], $data['batch_no'], $data['employee_ids']);

        DB::transaction(function () use (&$data, $batchNumber, $employeeIds, $product, $realization, $client): void {
            if (filled($batchNumber)) {
                $batch = $realization->batch;

                if ($batch) {
                    $batch->update(['batch_no' => $batchNumber, 'product_id' => $product?->getKey()]);
                } else {
                    $batch = Batch::query()->create([
                        'client_id' => $client->id(),
                        'batch_no' => $batchNumber,
                        'product_id' => $product?->getKey(),
                        'status' => 'active',
                    ]);
                }

                $data['batch_id'] = $batch->getKey();
            } else {
                $data['batch_id'] = null;
            }

            $data['sku_snapshot'] = $product?->sku;
            $data['product_name_snapshot'] = $product?->name;
            $data['unit_name_snapshot'] = $product?->unit?->name;

            $realization->update($data);

            if ($employeeIds === []) {
                $realization->employeeAssignments()->delete();
            } else {
                $realization->employeeAssignments()->whereNotIn('employee_id', $employeeIds)->delete();
            }
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::query()->where('client_id', $client->id())->findOrFail($employeeId);
                $assignment = $realization->employeeAssignments()->firstOrNew(['employee_id' => $employee->id]);
                $assignment->fill([
                    'client_id' => $client->id(),
                    'rate_category_snapshot' => $employee->rate_category,
                    'rate_per_unit_snapshot' => $product?->employee_rate ?? 0,
                ]);
                $assignment->save();

                if ($assignment->wasRecentlyCreated) {
                    $employee->user?->notify(new RealizationAssigned($realization));
                }
            }

            $this->rebalanceAssignments($realization);
        });

        if ($newImagePath !== null && $oldImagePath !== null) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return response()->json(['message' => 'Data realisasi berhasil diperbarui.']);
    }

    public function assign(AssignWorkRealizationRequest $request, WorkRealization $realization, CurrentClientService $client): JsonResponse
    {
        abort_unless($realization->client_id === $client->id(), 404);
        abort_unless($request->user()->canAccessMenu('realizations', $client->get()), 403);
        Gate::authorize('assign', $realization);

        foreach ($request->validated('employee_ids') as $employeeId) {
            $employee = Employee::query()->where('client_id', $client->id())->findOrFail($employeeId);
            $rate = $realization->product?->employee_rate ?? 0;

            $assignment = $realization->employeeAssignments()->firstOrCreate(
                ['employee_id' => $employee->id],
                [
                    'client_id' => $client->id(),
                    'rate_category_snapshot' => $employee->rate_category,
                    'rate_per_unit_snapshot' => $rate,
                    'allocation_output' => $realization->total_output,
                    'gross_amount' => $realization->total_output === null ? 0 : (int) round((float) $realization->total_output * (float) $rate),
                ],
            );

            if ($assignment->wasRecentlyCreated) {
                $employee->user?->notify(new RealizationAssigned($realization));
            }
        }
        $this->rebalanceAssignments($realization);

        return response()->json(['message' => 'Karyawan berhasil di-assign.']);
    }
}
