<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\CurrentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    private const AUDITABLE_LABELS = [
        'Batch' => 'batch pekerjaan',
        'Client' => 'client',
        'CostCenter' => 'cost center',
        'Deduction' => 'potongan gaji',
        'Employee' => 'karyawan',
        'Group' => 'grup karyawan',
        'Product' => 'produk',
        'Role' => 'role akses',
        'Shift' => 'shift',
        'Unit' => 'satuan',
        'User' => 'akun pengguna',
        'WorkRealization' => 'realisasi pekerjaan',
    ];

    public function index(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403);
        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'action' => ['nullable', 'string', 'max:50'],
        ]);
        $query = AuditLog::query()->with('user')->where(fn ($builder) => $builder->where('client_id', $client->id())->orWhereNull('client_id'))->latest('created_at');
        $query->when($validated['date_from'] ?? null, fn ($q, string $dateFrom) => $q->where('created_at', '>=', $dateFrom.' 00:00:00'))
            ->when($validated['date_to'] ?? null, fn ($q, string $dateTo) => $q->where('created_at', '<=', $dateTo.' 23:59:59'))
            ->when(($validated['action'] ?? null) && ($validated['action'] ?? null) !== 'all', fn ($q) => $q->where('action', $validated['action']));
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('created_at', fn (AuditLog $log): string => $log->created_at?->format('d/m/Y H:i') ?? '—')
                ->addColumn('description', fn (AuditLog $log): string => $this->describeActivity($log))
                ->addColumn('user_name', fn (AuditLog $log): string => $log->user?->name ?? 'System')
                ->toJson();
        }

        return view('audit.index', ['currentClient' => $client->get(), 'user' => $request->user()]);
    }

    private function describeActivity(AuditLog $log): string
    {
        if ($log->action === 'login') {
            return 'Masuk ke sistem';
        }

        if ($log->action === 'logout') {
            return 'Keluar dari sistem';
        }

        $action = match ($log->action) {
            'create' => 'Menambahkan',
            'update' => 'Mengubah',
            'delete' => 'Menghapus',
            default => null,
        };

        if ($action === null) {
            return 'Aktivitas sistem';
        }

        $subject = self::AUDITABLE_LABELS[class_basename($log->auditable_type)] ?? 'data sistem';

        return "{$action} {$subject}";
    }
}
