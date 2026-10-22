<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
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
     * Run a tenant-owned write against another client without changing the
     * request's selected client for the rest of the page.
     */
    public function runAs(Client $client, Closure $callback): mixed
    {
        $previousClient = $this->client;
        $this->client = $client;

        try {
            return $callback();
        } finally {
            $this->client = $previousClient;
        }
    }

    /**
     * Scope list queries to one client for regular users, or all accessible clients for super admins.
     * A selected client is an optional list filter and never changes the session.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeVisibleTo(Builder $query, User $user, ?int $selectedClientId = null): Builder
    {
        if (! $user->is_super_admin) {
            return $query->where($query->getModel()->qualifyColumn('client_id'), $this->id());
        }

        $availableClientIds = Client::query()->pluck('id')->all();

        if ($selectedClientId !== null) {
            return $query->whereIn($query->getModel()->qualifyColumn('client_id'), $availableClientIds)
                ->where($query->getModel()->qualifyColumn('client_id'), $selectedClientId);
        }

        return $query->whereIn($query->getModel()->qualifyColumn('client_id'), $availableClientIds);
    }

    /**
     * @return Collection<int, Client>
     */
    public function availableFor(User $user): Collection
    {
        if ($user->is_super_admin) {
            return Client::query()
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
