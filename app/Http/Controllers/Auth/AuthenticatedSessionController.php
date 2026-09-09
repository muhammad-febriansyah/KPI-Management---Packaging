<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Services\CurrentClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, CurrentClientService $currentClients): RedirectResponse
    {
        $user = $request->authenticate();

        $request->session()->regenerate();

        $defaultClient = $currentClients->availableFor($user)->first();

        if ($defaultClient !== null) {
            $request->session()->put('current_client_id', $defaultClient->getKey());
        } else {
            $request->session()->forget('current_client_id');
        }

        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::query()->create(['user_id' => $user->id, 'action' => 'login', 'auditable_type' => get_class($user), 'auditable_id' => $user->id, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
