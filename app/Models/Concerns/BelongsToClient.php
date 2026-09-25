<?php

namespace App\Models\Concerns;

use App\Models\Client;
use App\Models\Scopes\ClientScope;
use App\Services\CurrentClientService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Marks a model as owned by one client, and keeps that ownership out of the caller's hands:
 * reads are restricted to the active client by ClientScope, and a write made during a request
 * either takes its client_id from the active client or has to match it.
 *
 * Console code — seeders, commands, queued jobs — runs with no active client and keeps setting
 * client_id itself, which is why the checks below start by returning when none is resolved.
 */
trait BelongsToClient
{
    public static function bootBelongsToClient(): void
    {
        static::addGlobalScope(new ClientScope);

        static::creating(function (Model $model): void {
            $currentClient = app(CurrentClientService::class);

            if (! $currentClient->isResolved()) {
                return;
            }

            if ($model->client_id === null) {
                $model->client_id = $currentClient->id();

                return;
            }

            if ((int) $model->client_id !== $currentClient->id()) {
                throw new RuntimeException(sprintf(
                    'Refusing to write a %s for client %d while client %d is active.',
                    $model::class,
                    $model->client_id,
                    $currentClient->id(),
                ));
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
