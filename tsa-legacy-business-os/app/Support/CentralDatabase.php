<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final class CentralDatabase
{
    public static function connectionName(): string
    {
        $configured = (string) config('tenancy.database.central_connection', 'central');

        if (app()->environment('testing')) {
            return (string) config('database.default', $configured);
        }

        return $configured;
    }

    public static function connection(): ConnectionInterface
    {
        return DB::connection(self::connectionName());
    }

    public static function table(string $table): string
    {
        return self::connectionName().'.'.$table;
    }
}
