<?php

namespace App\Models\Scopes;

use App\Services\CurrentClientService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts regular users to the client resolved for the current request; super admins can query across clients.
 *
 * Outside a request the current client is unresolved — console commands, queued jobs,
 * seeders — and the scope stays out of the way rather than silently filtering on a
 * client nobody selected. Such code has to name its client itself.
 *
 * @implements Scope<Model>
 */
class ClientScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->user()?->is_super_admin) {
            return;
        }

        $currentClient = app(CurrentClientService::class);

        if (! $currentClient->isResolved()) {
            return;
        }

        $builder->where($model->qualifyColumn('client_id'), $currentClient->id());
    }
}
