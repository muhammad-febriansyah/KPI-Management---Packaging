<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $currentClient = $this->resolveCurrentClient($request, $user);

        return view('profile.edit', [
            'currentClient' => $currentClient,
            'roleCode' => $user->is_super_admin ? 'super-admin' : $user->roleCodeFor($currentClient),
            'employee' => $user->loadMissing('employee')->employee,
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'username', 'email']);
        $employeeData = $request->safe()->only(['sim_id', 'phone', 'marital_status']);

        if ($user->employee) {
            $user->employee->update($employeeData + [
                'full_name' => $data['name'],
                'email' => $data['email'] ?? null,
            ]);
        }

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $data['avatar_path'] = $avatarPath;
        }

        $user->update($data);

        return to_route('profile.edit')->with('status', 'Profil berhasil diperbarui.');
    }

    private function resolveCurrentClient(Request $request, User $user): ?Client
    {
        $clientId = $request->session()->get('current_client_id');

        if (! is_numeric($clientId)) {
            return null;
        }

        if ($user->is_super_admin) {
            return Client::query()->active()->find((int) $clientId);
        }

        return $user->clients()
            ->whereKey((int) $clientId)
            ->where('clients.status', 'active')
            ->wherePivot('status', 'active')
            ->first();
    }
}
