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
            return DataTables::eloquent($query)->editColumn('created_at', fn (AuditLog $log): string => $log->created_at?->format('d/m/Y H:i') ?? '—')->addColumn('description', fn (AuditLog $log): string => ucfirst($log->action).' '.class_basename($log->auditable_type))->addColumn('user_name', fn (AuditLog $log): string => $log->user?->name ?? 'System')->toJson();
        }

        return view('audit.index', ['currentClient' => $client->get(), 'user' => $request->user()]);
    }
}
