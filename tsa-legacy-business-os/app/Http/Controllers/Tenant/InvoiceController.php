<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use App\Models\Tenant\Payment;
use App\Models\Tenant\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        $customerMap = $customers->pluck('name', 'id');

        $paymentSummary = DB::table('invoice_payments')
            ->selectRaw('invoice_id, SUM(amount) as total_paid, MAX(paid_at) as last_payment_at')
            ->groupBy('invoice_id')
            ->get()
            ->keyBy('invoice_id');

        return view('tenant.sales.index', compact('invoices', 'customers', 'products', 'customerMap', 'paymentSummary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $subtotal = (float) $validated['quantity'] * (float) $validated['unit_price'];
            $taxAmount = $subtotal * ((float) ($validated['tax_percent'] ?? 0) / 100);
            $total = $subtotal + $taxAmount;

            $invoice = Invoice::query()->create([
                'invoice_number' => 'INV-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'customer_id' => $validated['customer_id'] ?? null,
                'status' => 'issued',
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'paid_amount' => 0,
                'balance_due' => $total,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'product_id' => $validated['product_id'] ?? null,
                'description' => $validated['description'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'tax_percent' => $validated['tax_percent'] ?? 0,
                'line_total' => $total,
            ]);
        });

        return back()->with('status', 'Invoice created successfully.');
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,card,bank,upi,other'],
            'reference_number' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($validated, $invoice): void {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $validated['amount'];

            if (! in_array($locked->status, ['issued', 'partial', 'overdue'], true)) {
                throw ValidationException::withMessages([
                    'amount' => 'Payments can only be added on issued/partial/overdue invoices.',
                ]);
            }

            if ($amount > (float) $locked->balance_due) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot exceed invoice balance due.',
                ]);
            }

            $invoicePaymentId = DB::table('invoice_payments')->insertGetId([
                'invoice_id' => $locked->id,
                'payment_method' => $validated['payment_method'],
                'amount' => $amount,
                'paid_at' => now(),
                'reference_number' => $validated['reference_number'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Payment::query()->create([
                'invoice_id' => $locked->id,
                'invoice_payment_id' => $invoicePaymentId,
                'payment_method' => $validated['payment_method'],
                'amount' => $amount,
                'paid_at' => now(),
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            $newPaid = (float) $locked->paid_amount + $amount;
            $balance = max(0, (float) $locked->total_amount - $newPaid);

            $locked->update([
                'paid_amount' => $newPaid,
                'balance_due' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'partial',
            ]);
        });

        return back()->with('status', 'Payment recorded successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return back()->with('status', 'Invoice deleted successfully.');
    }
}
