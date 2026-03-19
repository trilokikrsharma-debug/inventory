<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'tenants' => Tenant::query()->count(),
            'active_tenants' => Tenant::query()->where('status', 'active')->count(),
            'plans' => Plan::query()->count(),
        ];

        return view('dashboard', compact('metrics'));
    }
}
