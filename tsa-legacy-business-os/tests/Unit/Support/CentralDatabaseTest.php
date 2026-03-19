<?php

namespace Tests\Unit\Support;

use App\Support\CentralDatabase;
use Tests\TestCase;

class CentralDatabaseTest extends TestCase
{
    public function test_it_uses_default_connection_in_testing_environment(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('tenancy.database.central_connection', 'central');

        $this->assertSame('sqlite', CentralDatabase::connectionName());
        $this->assertSame('sqlite.subscriptions', CentralDatabase::table('subscriptions'));
    }
}
