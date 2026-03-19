<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Database\QueryException;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function __invoke(): View
    {
        try {
            $plans = Plan::query()
                ->with('features')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        } catch (QueryException) {
            $plans = collect();
        }

        return view('marketing.home', compact('plans'));
    }
}
