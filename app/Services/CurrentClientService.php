<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

class CurrentClientService
{
    private ?Client $client = null;

    public function set(Client $client): void
    {
        $this->client = $client;
    }

    public function clear(): void
    {
        $this->client = null;
    }

    public function isResolved(): bool
    {
        return $this->client !== null;
    }

    public function get(): Client
    {
        return $this->client ?? throw new LogicException('Current client has not been resolved.');
    }

    public function id(): int
    {
        return (int) $this->get()->getKey();
    }

    /**
     * @return Collection<int, Client>
     */
    public function availableFor(User $user): Collection
    {
        if ($user->is_super_admin) {
            return Client::query()
                ->active()
                ->orderBy('name')
                ->get();
        }

        return $user->clients()
            ->where('clients.status', 'active')
            ->wherePivot('status', 'active')
            ->orderByPivot('is_default', 'desc')
            ->orderBy('clients.name')
            ->get();
    }
}
