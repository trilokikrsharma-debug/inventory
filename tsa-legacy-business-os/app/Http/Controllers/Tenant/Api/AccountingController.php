<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\LedgerEntry;
use App\Models\Tenant\TaxReport;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function summary()
    {
        $income = LedgerEntry::query()->where('credit', '>', 0)->sum('credit');
        $expense = LedgerEntry::query()->where('debit', '>', 0)->sum('debit');

        return response()->json([
            'profit_and_loss' => [
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
            ],
            'accounts_count' => ChartOfAccount::query()->count(),
            'tax_reports' => TaxReport::query()->latest()->limit(12)->get(),
        ]);
    }

    public function storeLedgerEntry(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
            'entry_date' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:255'],
            'reference_type' => ['nullable', 'string', 'max:120'],
            'reference_id' => ['nullable', 'integer'],
        ]);

        $entry = LedgerEntry::query()->create($validated + [
            'created_by' => $request->user()?->id,
            'debit' => $validated['debit'] ?? 0,
            'credit' => $validated['credit'] ?? 0,
        ]);

        return response()->json(['data' => $entry], 201);
    }
}
