<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $tenantId = tenant('id');

        $stats = [
            'total_products' => Product::query()->count(),
            'total_customers' => Customer::query()->count(),
            'total_sales' => (float) Invoice::query()->sum('total_amount'),
            'total_purchases' => (float) PurchaseOrder::query()->sum('total_amount'),
            'inventory_stock_value' => (float) Product::query()
                ->selectRaw('SUM(current_stock * cost_price) as stock_value')
                ->value('stock_value'),
            'open_invoices' => Invoice::query()->whereIn('status', ['issued', 'partial', 'overdue'])->count(),
        ];

        $recentInvoices = Invoice::query()
            ->orderByDesc('invoice_date')
            ->limit(8)
            ->get();

        $recentCustomers = Customer::query()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $subscription = Subscription::query()
            ->with('plan.features')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        return view('tenant.dashboard', compact('stats', 'subscription', 'recentInvoices', 'recentCustomers'));
    }
}
