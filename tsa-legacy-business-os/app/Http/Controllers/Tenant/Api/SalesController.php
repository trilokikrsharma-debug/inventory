<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use App\Services\Saas\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->orderByDesc('invoice_date')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($invoices);
    }

    public function storeInvoice(Request $request, PlanLimitService $planLimitService)
    {
        $tenantId = (string) tenant('id');
        $planLimitService->enforce($tenantId, 'max_invoices', Invoice::query()->count(), 'invoices');

        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoice = DB::transaction(function () use ($validated, $request) {
            $subtotal = 0.0;
            $taxAmount = 0.0;

            $invoice = Invoice::query()->create([
                'invoice_number' => 'INV-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'customer_id' => $validated['customer_id'] ?? null,
                'status' => 'issued',
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($validated['items'] as $item) {
                $lineSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $lineTax = $lineSubtotal * ((float) ($item['tax_percent'] ?? 0) / 100);
                $lineTotal = $lineSubtotal + $lineTax;

                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_percent' => $item['tax_percent'] ?? 0,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
            }

            $total = $subtotal + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'balance_due' => $total,
            ]);

            return $invoice->fresh();
        });

        return response()->json(['data' => $invoice], 201);
    }

    public function receivePayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,card,bank,upi,other'],
            'reference_number' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($validated, $invoice): void {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $validated['amount'];

            if (! in_array($invoice->status, ['issued', 'partial', 'overdue'], true)) {
                throw ValidationException::withMessages([
                    'amount' => 'Payments can only be recorded for issued, partial, or overdue invoices.',
                ]);
            }

            if ($amount > (float) $invoice->balance_due) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot exceed current balance due.',
                ]);
            }

            DB::table('invoice_payments')->insert([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'paid_at' => now(),
                'reference_number' => $validated['reference_number'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $newPaid = (float) $invoice->paid_amount + $amount;
            $balance = max(0, (float) $invoice->total_amount - $newPaid);

            $invoice->update([
                'paid_amount' => $newPaid,
                'balance_due' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'partial',
            ]);
        });

        return response()->json(['message' => 'Payment recorded successfully.']);
    }
}
