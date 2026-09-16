<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Master records are referenced by composite foreign keys declared restrictOnDelete,
 * so deleting one that is still in use raises an integrity constraint violation. Left
 * alone that reaches the user as a 500 and a raw SQL string; this turns it into a 409
 * carrying a message that says which record is in the way.
 */
trait DeletesRestrictedRecords
{
    /**
     * SQLSTATE class for an integrity constraint violation.
     */
    private const INTEGRITY_CONSTRAINT_VIOLATION = '23000';

    protected function deleteRestricted(Model $model, string $inUseMessage): void
    {
        try {
            $model->delete();
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== self::INTEGRITY_CONSTRAINT_VIOLATION) {
                throw $exception;
            }

            abort(409, $inUseMessage);
        }
    }
}
