<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $response = $next($request);

        if ($user === null || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) || $response->getStatusCode() >= 400) {
            return $response;
        }

        if ($this->hasNewFlash($request, 'errors')) {
            return $response;
        }

        $message = $this->responseMessage($response, $request);

        if ($message === 'ok') {
            $message = null;
        }

        $route = $request->route();
        $routeName = $route?->getName() ?? '';
        $action = $this->action($request, $routeName);
        $description = $message ?: $this->fallbackDescription($action, $routeName);
        $auditable = collect($route?->parameters() ?? [])->first(fn (mixed $value): bool => $value instanceof Model);
        $clientId = $auditable?->getAttribute('client_id');

        if ($clientId === null) {
            $clientId = $request->filled('client_id')
                ? $request->integer('client_id')
                : $request->session()->get('current_client_id');
        }

        // audit_logs.auditable_type is required, so routes without a model (logout, profile,
        // notifications, ...) are recorded against the acting user, as the login entry is.
        $auditableType = $auditable?->getMorphClass() ?? $this->auditableType($routeName);
        $auditableId = $auditable?->getKey();

        if ($auditableType === null) {
            $auditableType = $user->getMorphClass();
            $auditableId = $user->getKey();
        }

        try {
            AuditLog::query()->create([
                'client_id' => $clientId,
                'user_id' => $user->getKey(),
                'action' => $action,
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'new_values' => ['description' => Str::limit(strip_tags($description), 500, '')],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function responseMessage(Response $response, Request $request): ?string
    {
        if ($response instanceof JsonResponse) {
            $message = $response->getData(true)['message'] ?? null;

            return is_string($message) && trim($message) !== '' ? trim($message) : null;
        }

        $message = null;

        foreach (['status', 'success'] as $key) {
            if (! $this->hasNewFlash($request, $key)) {
                continue;
            }

            $message = $request->session()->get($key);

            if (is_string($message) && trim($message) !== '') {
                break;
            }
        }

        return is_string($message) && trim($message) !== '' ? trim($message) : null;
    }

    private function hasNewFlash(Request $request, string $key): bool
    {
        return in_array($key, $request->session()->get('_flash.new', []), true);
    }

    private function action(Request $request, string $routeName): string
    {
        if ($routeName === 'logout') {
            return 'logout';
        }

        if (Str::endsWith($routeName, '.import')) {
            return 'import';
        }

        if ($request->isMethod('DELETE') || Str::endsWith($routeName, '.destroy')) {
            return 'delete';
        }

        if (in_array($request->method(), ['PUT', 'PATCH'], true) || Str::endsWith($routeName, ['.update', '.assign', '.read', '.read-all'])) {
            return 'update';
        }

        return 'create';
    }

    private function fallbackDescription(string $action, string $routeName): string
    {
        $subject = match (Str::before($routeName, '.')) {
            'units' => 'satuan',
            'groups' => 'grup',
            'shifts' => 'shift',
            'cost-centers' => 'cost center',
            'employees' => 'karyawan',
            'products' => 'produk',
            'realizations' => 'realisasi pekerjaan',
            'deductions' => 'potongan gaji',
            'clients' => 'client',
            'settings' => 'pengaturan akses',
            'profile' => 'profil',
            'current-client' => 'client aktif',
            'notifications' => 'notifikasi',
            default => 'data sistem',
        };

        return match ($action) {
            'logout' => 'Keluar dari sistem',
            'import' => "Mengimpor {$subject}",
            'delete' => "Menghapus {$subject}",
            'update' => "Memperbarui {$subject}",
            default => "Membuat {$subject}",
        };
    }

    private function auditableType(string $routeName): ?string
    {
        $model = match (Str::before($routeName, '.')) {
            'units' => Unit::class,
            'groups' => Group::class,
            'shifts' => Shift::class,
            'cost-centers' => CostCenter::class,
            'employees' => Employee::class,
            'products' => Product::class,
            'realizations' => WorkRealization::class,
            'deductions' => Deduction::class,
            'clients' => Client::class,
            'settings' => User::class,
            default => null,
        };

        return $model;
    }
}
