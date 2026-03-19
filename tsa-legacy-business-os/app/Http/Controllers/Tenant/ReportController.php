<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from', now()->startOfMonth())?->toDateString();
        $to = $request->date('to', now()->endOfMonth())?->toDateString();

        $salesRows = Invoice::query()
            ->selectRaw('DATE(invoice_date) as date, SUM(total_amount) as amount')
            ->whereBetween('invoice_date', [$from, $to])
            ->groupBy(DB::raw('DATE(invoice_date)'))
            ->orderBy('date')
            ->get();

        $salesTotal = (float) Invoice::query()
            ->whereBetween('invoice_date', [$from, $to])
            ->sum('total_amount');

        $purchaseTotal = (float) PurchaseOrder::query()
            ->whereBetween('order_date', [$from, $to])
            ->sum('total_amount');

        $inventoryValue = (float) Product::query()
            ->selectRaw('SUM(current_stock * cost_price) as inventory_value')
            ->value('inventory_value');

        $topStock = Product::query()
            ->select(['name', 'sku', 'current_stock', 'cost_price'])
            ->orderByDesc('current_stock')
            ->limit(10)
            ->get();

        $profit = $salesTotal - $purchaseTotal;

        return view('tenant.reports.index', [
            'from' => $from,
            'to' => $to,
            'salesRows' => $salesRows,
            'salesTotal' => $salesTotal,
            'purchaseTotal' => $purchaseTotal,
            'inventoryValue' => $inventoryValue,
            'profit' => $profit,
            'topStock' => $topStock,
        ]);
    }
}
