<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantCreated;

class EnsureTenantDatabaseConnection
{
    public function handle(TenantCreated $event): void
    {
        $tenant = $event->tenant;

        $templateConnection = (string) config(
            'tenancy.database.template_tenant_connection',
            config('tenancy.database.central_connection', 'tenant')
        );

        if ($templateConnection === '') {
            $templateConnection = 'tenant';
        }

        $current = (string) ($tenant->getInternal('db_connection') ?? '');

        if ($current === '') {
            $tenant->setInternal('db_connection', $templateConnection);

            if ($tenant->exists) {
                $tenant->saveQuietly();
            }
        }
    }
}

