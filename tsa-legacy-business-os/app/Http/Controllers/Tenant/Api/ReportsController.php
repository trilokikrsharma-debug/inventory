<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\GeneratedReport;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function salesAnalytics(Request $request)
    {
        $from = $request->date('from', now()->startOfMonth())?->toDateString();
        $to = $request->date('to', now()->endOfMonth())?->toDateString();

        $rows = Invoice::query()
            ->selectRaw('DATE(invoice_date) as date, SUM(total_amount) as amount')
            ->whereBetween('invoice_date', [$from, $to])
            ->groupBy(DB::raw('DATE(invoice_date)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'series' => $rows,
        ]);
    }

    public function inventoryValuation()
    {
        $valuation = Product::query()
            ->selectRaw('SUM(current_stock * cost_price) as inventory_value')
            ->value('inventory_value');

        $topStock = Product::query()
            ->select(['id', 'name', 'sku', 'current_stock', 'cost_price'])
            ->orderByDesc('current_stock')
            ->limit(10)
            ->get();

        return response()->json([
            'inventory_value' => (float) ($valuation ?? 0),
            'top_stock' => $topStock,
        ]);
    }

    public function exportSnapshot(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'module' => ['required', 'string', 'max:60'],
            'filters' => ['nullable', 'array'],
        ]);

        $report = GeneratedReport::query()->create([
            'name' => $validated['name'],
            'module' => $validated['module'],
            'filters' => $validated['filters'] ?? [],
            'generated_by' => $request->user()?->id,
            'generated_at' => now(),
        ]);

        return response()->json(['data' => $report], 201);
    }
}
