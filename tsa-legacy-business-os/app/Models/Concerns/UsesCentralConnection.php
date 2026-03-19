<?php

namespace App\Models\Concerns;

use App\Support\CentralDatabase;

trait UsesCentralConnection
{
    public function getConnectionName(): ?string
    {
        return CentralDatabase::connectionName();
    }
}
