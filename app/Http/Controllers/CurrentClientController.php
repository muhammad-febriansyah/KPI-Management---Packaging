<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCurrentClientRequest;
use App\Models\Client;
use App\Services\CurrentClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CurrentClientController extends Controller
{
    public function update(
        UpdateCurrentClientRequest $request,
        CurrentClientService $currentClient,
    ): RedirectResponse {
        $client = Client::query()
            ->active()
            ->findOrFail($request->integer('client_id'));

        Gate::authorize('select', $client);

        $request->session()->put('current_client_id', $client->getKey());
        $currentClient->set($client);

        return back()->with('success', 'Client aktif berhasil diubah.');
    }
}
