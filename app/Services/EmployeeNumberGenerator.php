<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeNumberGenerator
{
    public function next(): string
    {
        Client::query()->orderBy('id')->lockForUpdate()->firstOrFail();

        $prefix = 'ID'.now()->format('Ym');
        $lastSequence = DB::table('employees')
            ->where('employee_no', 'like', $prefix.'%')
            ->pluck('employee_no')
            ->merge(User::query()->where('username', 'like', $prefix.'%')->pluck('username'))
            ->map(function (string $number) use ($prefix): int {
                if (preg_match('/^'.preg_quote($prefix, '/').'([0-9]{3,})$/', $number, $matches) !== 1) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max() ?? 0;

        return $prefix.str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }
}
