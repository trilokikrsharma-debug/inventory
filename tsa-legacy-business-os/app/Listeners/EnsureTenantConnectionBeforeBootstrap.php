<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\InitializingTenancy;

class EnsureTenantConnectionBeforeBootstrap
{
    public function handle(InitializingTenancy $event): void
    {
        $tenant = $event->tenancy->tenant;

        if (! $tenant) {
            return;
        }

        $current = (string) ($tenant->getInternal('db_connection') ?? '');

        if ($current !== '') {
            return;
        }

        $template = (string) config(
            'tenancy.database.template_tenant_connection',
            config('tenancy.database.central_connection', 'tenant')
        );

        if ($template === '') {
            $template = 'tenant';
        }

        $tenant->setInternal('db_connection', $template);

        if ($tenant->exists) {
            $tenant->saveQuietly();
        }
    }
}

