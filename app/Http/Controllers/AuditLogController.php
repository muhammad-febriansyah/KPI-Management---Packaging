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
        $query = AuditLog::query()->with('user')->where(fn ($builder) => $builder->where('client_id', $client->id())->orWhereNull('client_id'))->latest('created_at');
        $query->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')))
            ->when($request->filled('action') && $request->string('action')->toString() !== 'all', fn ($q) => $q->where('action', $request->string('action')));
        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)->editColumn('created_at', fn (AuditLog $log): string => $log->created_at?->format('d/m/Y H:i') ?? '—')->addColumn('description', fn (AuditLog $log): string => ucfirst($log->action).' '.class_basename($log->auditable_type))->addColumn('user_name', fn (AuditLog $log): string => $log->user?->name ?? 'System')->toJson();
        }

        return view('audit.index', ['currentClient' => $client->get(), 'user' => $request->user()]);
    }
}
