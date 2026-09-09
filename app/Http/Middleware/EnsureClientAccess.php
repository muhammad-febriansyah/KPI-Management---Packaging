<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\User;
use App\Services\CurrentClientService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientAccess
{
    public function __construct(private CurrentClientService $currentClient) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $clientId = $request->session()->get('current_client_id');

        if (! $user instanceof User || $user->status !== 'active') {
            $request->session()->forget('current_client_id');

            abort(403, 'Client aktif tidak tersedia. Silakan pilih client yang dapat Anda akses.');
        }

        if (! is_int($clientId) && $user->is_super_admin) {
            $firstClientId = Client::query()->active()->orderBy('name')->value('id');

            if ($firstClientId !== null) {
                $clientId = (int) $firstClientId;
                $request->session()->put('current_client_id', $clientId);
            }
        }

        if (! is_int($clientId)) {
            $request->session()->forget('current_client_id');

            abort(403, 'Client aktif tidak tersedia. Silakan pilih client yang dapat Anda akses.');
        }

        $client = $this->accessibleClient($user, $clientId);

        if (! $client instanceof Client && $user->is_super_admin) {
            $client = Client::query()->active()->orderBy('name')->first();

            if ($client instanceof Client) {
                $request->session()->put('current_client_id', $client->getKey());
            }
        }

        if (! $client instanceof Client) {
            $request->session()->forget('current_client_id');

            abort(403, 'Client aktif tidak tersedia. Silakan pilih client yang dapat Anda akses.');
        }

        $this->currentClient->set($client);
        Context::add('client_id', $client->getKey());

        return $next($request);
    }

    private function accessibleClient(User $user, int $clientId): ?Client
    {
        if ($user->is_super_admin) {
            return Client::query()->active()->find($clientId);
        }

        return $user->clients()
            ->whereKey($clientId)
            ->where('clients.status', 'active')
            ->wherePivot('status', 'active')
            ->first();
    }
}
