<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\Expense;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    // Show the general ledger with filters and a simple summary.
    public function ledger(Request $request)
    {
        $query = $this->baseTransactionQuery($request);
        $transactions = (clone $query)->latest('transaction_date')->latest('id')->get();

        return view('backend.finance.ledger', [
            'transactions' => $transactions,
            'filters' => $request->only(['party_type', 'account_type', 'entry_type', 'date_from', 'date_to']),
            'summary' => $this->summarizeTransactions($transactions),
        ]);
    }

    // Show a trial balance style view from the saved accounting rows.
    public function trialBalance()
    {
        $rows = AccountTransaction::query()
            ->selectRaw('account_type, entry_type, SUM(amount) as total_amount')
            ->groupBy('account_type', 'entry_type')
            ->get()
            ->groupBy('account_type')
            ->map(function ($items, $accountType) {
                $debit = (float) ($items->firstWhere('entry_type', 'debit')?->total_amount ?? 0);
                $credit = (float) ($items->firstWhere('entry_type', 'credit')?->total_amount ?? 0);

                return [
                    'account_type' => $accountType,
                    'debit' => $debit,
                    'credit' => $credit,
                    'difference' => round($debit - $credit, 2),
                ];
            })
            ->values();

        return view('backend.finance.trial-balance', [
            'rows' => $rows,
            'summary' => [
                'debit' => $rows->sum('debit'),
                'credit' => $rows->sum('credit'),
            ],
        ]);
    }

    // Show the cash book based on cash account transactions only.
    public function cashBook(Request $request)
    {
        $request->merge(['account_type' => 'cash']);
        $query = $this->baseTransactionQuery($request);
        $transactions = (clone $query)->latest('transaction_date')->latest('id')->get();

        return view('backend.finance.cash-book', [
            'transactions' => $transactions,
            'summary' => $this->summarizeTransactions($transactions),
        ]);
    }

    // Show the bank book based on bank account transactions only.
    public function bankBook(Request $request)
    {
        $request->merge(['account_type' => 'bank']);
        $query = $this->baseTransactionQuery($request);
        $transactions = (clone $query)->latest('transaction_date')->latest('id')->get();

        return view('backend.finance.bank-book', [
            'transactions' => $transactions,
            'summary' => $this->summarizeTransactions($transactions),
        ]);
    }

    // Show a GST style tax summary from the sales invoices.
    public function gstReport(Request $request)
    {
        $query = SalesInvoice::query()->with('customer');

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $invoices = $query->latest('invoice_date')->latest('id')->get();

        return view('backend.finance.gst-report', [
            'invoices' => $invoices,
            'filters' => $request->only(['date_from', 'date_to']),
            'summary' => [
                'taxable_sales' => $invoices->sum('subtotal'),
                'tax_amount' => $invoices->sum('tax_amount'),
                'total_sales' => $invoices->sum('total_amount'),
            ],
        ]);
    }

    // Keep the ledger query in one place so the report pages stay short.
    private function baseTransactionQuery(Request $request)
    {
        return AccountTransaction::query()
            ->with(['creator', 'customer', 'supplier'])
            ->when($request->filled('party_type'), function ($query) use ($request) {
                $query->where('party_type', $request->party_type);
            })
            ->when($request->filled('account_type'), function ($query) use ($request) {
                $query->where('account_type', $request->account_type);
            })
            ->when($request->filled('entry_type'), function ($query) use ($request) {
                $query->where('entry_type', $request->entry_type);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            });
    }

    // Sum the useful bits so the top cards stay tiny and easy to scan.
    private function summarizeTransactions($transactions): array
    {
        return [
            'debit' => round((float) $transactions->where('entry_type', 'debit')->sum('amount'), 2),
            'credit' => round((float) $transactions->where('entry_type', 'credit')->sum('amount'), 2),
            'cash' => round((float) $transactions->where('account_type', 'cash')->sum('amount'), 2),
            'bank' => round((float) $transactions->where('account_type', 'bank')->sum('amount'), 2),
            'receivable' => round((float) $transactions->where('account_type', 'receivable')->sum('amount'), 2),
            'payable' => round((float) $transactions->where('account_type', 'payable')->sum('amount'), 2),
        ];
    }
}
