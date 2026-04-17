<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    // Show the general ledger with filters and a more accountant friendly column layout.
    public function ledger(Request $request)
    {
        $query = $this->baseTransactionQuery($request);
        $transactions = (clone $query)->latest('transaction_date')->latest('id')->get();

        return view('finance.ledger', [
            'transactions' => $transactions,
            'filters' => $request->only(['party_type', 'party_keyword', 'account_type', 'entry_type', 'date_from', 'date_to']),
            'summary' => $this->summarizeTransactions($transactions),
            'accountCatalog' => collect($this->accountCatalog())->keyBy('key'),
        ]);
    }

    // Return general ledger rows for the server-side table.
    public function ledgerList(Request $request)
    {
        return $this->transactionListResponse($request, null, true);
    }

    // Show trial balance rows with account code, group and closing balance details.
    // This stays fast because we only ask MySQL for grouped totals, not every raw ledger row.
    public function trialBalance(Request $request)
    {
        $transactionSummary = $this->transactionSummaryByAccount($request);

        $rows = collect($this->accountCatalog())
            ->map(function (array $account) use ($transactionSummary) {
                return $this->makeAccountSummaryRow($account, $transactionSummary->get($account['key'], collect()));
            })
            ->filter(function (array $row) {
                return $row['debit'] > 0 || $row['credit'] > 0;
            })
            ->values();

        return view('finance.trial-balance', [
            'rows' => $rows,
            'rowGroups' => $rows->groupBy('group'),
            'filters' => $request->only(['date_from', 'date_to']),
            'summary' => [
                'debit' => round((float) $rows->sum('debit'), 2),
                'credit' => round((float) $rows->sum('credit'), 2),
                'difference' => round((float) abs($rows->sum('debit') - $rows->sum('credit')), 2),
            ],
        ]);
    }

    // Show a simple chart of accounts tree so accountant users can understand the book structure quickly.
    // We reuse the same grouped finance totals here, so large transaction history still stays manageable.
    public function accountTree(Request $request)
    {
        $transactionSummary = $this->transactionSummaryByAccount($request);

        $groups = collect($this->accountCatalog())
            ->groupBy('group')
            ->map(function (Collection $accounts, string $groupName) use ($transactionSummary) {
                $rows = $accounts->map(function (array $account) use ($transactionSummary) {
                    return $this->makeAccountSummaryRow($account, $transactionSummary->get($account['key'], collect()));
                })->values();

                return [
                    'name' => $groupName,
                    'rows' => $rows,
                    'debit' => round((float) $rows->sum('debit'), 2),
                    'credit' => round((float) $rows->sum('credit'), 2),
                ];
            })
            ->values();

        return view('finance.account-tree', [
            'groups' => $groups,
            'filters' => $request->only(['date_from', 'date_to']),
            'summary' => [
                'accounts' => $groups->sum(fn (array $group) => count($group['rows'])),
                'debit' => round((float) $groups->sum('debit'), 2),
                'credit' => round((float) $groups->sum('credit'), 2),
            ],
        ]);
    }

    // Show the cash book based on cash account transactions only.
    public function cashBook(Request $request)
    {
        $request->merge(['account_type' => 'cash']);
        $query = $this->baseTransactionQuery($request);
        $transactions = (clone $query)->latest('transaction_date')->latest('id')->get();

        return view('finance.cash-book', [
            'transactions' => $transactions,
            'summary' => $this->summarizeTransactions($transactions),
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    // Return cash book rows for the server-side table.
    public function cashBookList(Request $request)
    {
        return $this->transactionListResponse($request, 'cash');
    }

    // Show the bank book based on bank account transactions only.
    public function bankBook(Request $request)
    {
        $request->merge(['account_type' => 'bank']);
        $query = $this->baseTransactionQuery($request);
        $transactions = (clone $query)->latest('transaction_date')->latest('id')->get();

        return view('finance.bank-book', [
            'transactions' => $transactions,
            'summary' => $this->summarizeTransactions($transactions),
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    // Return bank book rows for the server-side table.
    public function bankBookList(Request $request)
    {
        return $this->transactionListResponse($request, 'bank');
    }

    // Day book shows one date-range running balance for daily operation tracking.
    public function dayBook(Request $request)
    {
        $filters = $this->dayBookFilters($request);
        $transactions = $this->dayBookBaseQuery($filters)->get();
        $openingBalance = $this->dayBookOpeningBalance($filters);
        $summary = $this->dayBookSummary($transactions, $openingBalance);

        return view('finance.day-book', [
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    // Return day book rows for the server-side table.
    public function dayBookList(Request $request)
    {
        $filters = $this->dayBookFilters($request);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 15);

        $query = $this->dayBookBaseQuery($filters);
        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query = $this->applyDayBookSearch($query, $keyword);
        }

        $transactions = $query->get();
        $openingBalance = $this->dayBookOpeningBalance($filters);
        $runningBalance = round($openingBalance, 2);

        $transactions->each(function ($transaction) use (&$runningBalance) {
            $delta = $transaction->entry_type === 'debit'
                ? (float) $transaction->amount
                : -(float) $transaction->amount;
            $runningBalance = round($runningBalance + $delta, 2);
            $transaction->running_balance = $runningBalance;
        });

        $recordsFiltered = $transactions->count();
        $rows = $length > -1
            ? $transactions->slice($start, $length)->values()
            : $transactions->values();

        $data = $rows->values()->map(function ($transaction, $index) use ($start) {
            $reference = $transaction->reference_type
                ? $transaction->reference_type . ' #' . $transaction->reference_id
                : '-';
            $entryClass = $transaction->entry_type === 'debit' ? 'bg-success' : 'bg-danger';

            return [
                'sno' => $start + $index + 1,
                'date' => e($transaction->transaction_date_show),
                'reference' => '<span class="badge bg-light text-dark border">' . e($reference) . '</span>',
                'party' => '<div class="text-wrap">' . e($transaction->party_name) . '</div>',
                'account' => '<div class="fw-semibold">' . e($transaction->account_label) . '</div><div class="small text-muted"><span class="badge ' . $entryClass . '">' . e($transaction->entry_label) . '</span></div>',
                'narration' => '<div class="text-wrap small">' . e(Str::limit((string) ($transaction->notes ?: '-'), 100)) . '</div>',
                'debit' => $transaction->entry_type === 'debit' ? money_value($transaction->amount) : '-',
                'credit' => $transaction->entry_type === 'credit' ? money_value($transaction->amount) : '-',
                'running_balance' => '<span class="fw-semibold">' . money_value($transaction->running_balance ?? 0) . '</span>',
            ];
        })->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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
            ->when($request->filled('party_keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->party_keyword);

                $query->where(function ($builder) use ($keyword) {
                    $builder->whereHas('customer', function ($customerQuery) use ($keyword) {
                        $customerQuery->where('name', 'like', '%' . $keyword . '%');
                    })->orWhereHas('supplier', function ($supplierQuery) use ($keyword) {
                        $supplierQuery->where('supplier_name', 'like', '%' . $keyword . '%');
                    });
                });
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

    // Group the raw accounting rows by account type once so trial balance and account tree stay in sync.
    // Date filter is kept here too, so accountant can trim very large history without changing two pages.
    private function transactionSummaryByAccount(?Request $request = null): Collection
    {
        return AccountTransaction::query()
            ->when($request?->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            })
            ->when($request?->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            })
            ->selectRaw('account_type, entry_type, SUM(amount) as total_amount')
            ->groupBy('account_type', 'entry_type')
            ->get()
            ->groupBy('account_type');
    }

    // Build one account row with debit, credit and closing side details.
    private function makeAccountSummaryRow(array $account, Collection $items): array
    {
        $debit = round((float) ($items->firstWhere('entry_type', 'debit')?->total_amount ?? 0), 2);
        $credit = round((float) ($items->firstWhere('entry_type', 'credit')?->total_amount ?? 0), 2);
        $closing = $this->resolveClosingBalance($debit, $credit, $account['nature']);

        return [
            'key' => $account['key'],
            'code' => $account['code'],
            'name' => $account['name'],
            'group' => $account['group'],
            'nature' => strtoupper($account['nature']),
            'debit' => $debit,
            'credit' => $credit,
            'closing_amount' => $closing['amount'],
            'closing_side' => $closing['side'],
        ];
    }

    // Balance side depends on the normal nature of the account, so we keep this logic in one small helper.
    private function resolveClosingBalance(float $debit, float $credit, string $nature): array
    {
        if ($nature === 'debit') {
            $net = round($debit - $credit, 2);

            return [
                'amount' => abs($net),
                'side' => $net >= 0 ? 'Dr' : 'Cr',
            ];
        }

        $net = round($credit - $debit, 2);

        return [
            'amount' => abs($net),
            'side' => $net >= 0 ? 'Cr' : 'Dr',
        ];
    }

    // Keep the chart of accounts readable and stable for finance screens.
    private function accountCatalog(): array
    {
        return [
            ['key' => 'cash', 'code' => '1100', 'name' => 'Cash in Hand', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'bank', 'code' => '1200', 'name' => 'Bank Account', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'receivable', 'code' => '1300', 'name' => 'Accounts Receivable', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'inventory', 'code' => '1400', 'name' => 'Inventory Stock', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'payable', 'code' => '2100', 'name' => 'Accounts Payable', 'group' => 'Liabilities', 'nature' => 'credit'],
            ['key' => 'capital', 'code' => '3100', 'name' => 'Capital', 'group' => 'Equity', 'nature' => 'credit'],
            ['key' => 'income', 'code' => '4100', 'name' => 'Sales Income', 'group' => 'Income', 'nature' => 'credit'],
            ['key' => 'other_income', 'code' => '4200', 'name' => 'Other Income', 'group' => 'Income', 'nature' => 'credit'],
            ['key' => 'expense', 'code' => '5100', 'name' => 'Operating Expense', 'group' => 'Expenses', 'nature' => 'debit'],
            ['key' => 'purchase_return', 'code' => '5200', 'name' => 'Purchase Return / Adjustment', 'group' => 'Expenses', 'nature' => 'debit'],
        ];
    }

    // Sum the useful bits so the top cards stay tiny and easy to scan.
    private function summarizeTransactions(Collection $transactions): array
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

    private function transactionListResponse(Request $request, ?string $forcedAccountType = null, bool $includeAccountGroup = false)
    {
        if ($forcedAccountType) {
            $request->merge(['account_type' => $forcedAccountType]);
        }

        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 15);

        $query = $this->baseTransactionQuery($request)
            ->latest('transaction_date')
            ->latest('id');

        $recordsTotal = AccountTransaction::query()
            ->when($forcedAccountType, function (Builder $builder) use ($forcedAccountType) {
                $builder->where('account_type', $forcedAccountType);
            })
            ->count();

        if ($keyword !== '') {
            $query = $this->applyTransactionSearch($query, $keyword);
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $transactions = $query->get();
        $accountCatalog = collect($this->accountCatalog())->keyBy('key');
        $data = [];

        foreach ($transactions as $index => $transaction) {
            $reference = $transaction->reference_type
                ? $transaction->reference_type . ' #' . $transaction->reference_id
                : '-';
            $account = $accountCatalog->get($transaction->account_type);
            $entryClass = $transaction->entry_type === 'debit' ? 'bg-success' : 'bg-danger';

            if ($includeAccountGroup) {
                $data[] = [
                    'sno' => $start + $index + 1,
                    'date' => e($transaction->transaction_date_show),
                    'reference' => '<span class="badge bg-light text-dark border">' . e($reference) . '</span>',
                    'party' => '<div class="text-wrap">' . e($transaction->party_name) . '</div>',
                    'account' => '<div class="fw-semibold">' . e($account['name'] ?? $transaction->account_label) . '</div>',
                    'group' => e($account['group'] ?? '-'),
                    'narration' => '<div class="text-wrap small">' . e(Str::limit((string) ($transaction->notes ?: '-'), 100)) . '</div>',
                    'debit' => $transaction->entry_type === 'debit' ? money_value($transaction->amount) : '-',
                    'credit' => $transaction->entry_type === 'credit' ? money_value($transaction->amount) : '-',
                    'created_by' => e($transaction->creator?->name ?: '-'),
                ];

                continue;
            }

            $data[] = [
                'sno' => $start + $index + 1,
                'date' => e($transaction->transaction_date_show),
                'reference' => '<span class="badge bg-light text-dark border">' . e($reference) . '</span>',
                'party' => '<div class="text-wrap">' . e($transaction->party_name) . '</div>',
                'entry' => '<span class="badge ' . $entryClass . '">' . e($transaction->entry_label) . '</span>',
                'amount' => money_value($transaction->amount),
                'notes' => '<div class="text-wrap small">' . e(Str::limit((string) ($transaction->notes ?: '-'), 100)) . '</div>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function applyTransactionSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function (Builder $builder) use ($keyword) {
            $builder->where('reference_type', 'like', '%' . $keyword . '%')
                ->orWhere('notes', 'like', '%' . $keyword . '%')
                ->orWhere('account_type', 'like', '%' . $keyword . '%')
                ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                    $customerQuery->where('name', 'like', '%' . $keyword . '%');
                })
                ->orWhereHas('supplier', function (Builder $supplierQuery) use ($keyword) {
                    $supplierQuery->where('supplier_name', 'like', '%' . $keyword . '%');
                })
                ->orWhereHas('creator', function (Builder $userQuery) use ($keyword) {
                    $userQuery->where('name', 'like', '%' . $keyword . '%');
                });
        });
    }

    private function dayBookFilters(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->toDateString());

        return [
            'date_from' => $dateFrom,
            'date_to' => $request->input('date_to', $dateFrom),
            'account_type' => $request->input('account_type'),
        ];
    }

    private function dayBookBaseQuery(array $filters): Builder
    {
        return AccountTransaction::query()
            ->with(['creator', 'customer', 'supplier'])
            ->whereDate('transaction_date', '>=', $filters['date_from'])
            ->whereDate('transaction_date', '<=', $filters['date_to'])
            ->when(!empty($filters['account_type']), function (Builder $builder) use ($filters) {
                $builder->where('account_type', $filters['account_type']);
            })
            ->orderBy('transaction_date')
            ->orderBy('id');
    }

    private function dayBookOpeningBalance(array $filters): float
    {
        return (float) AccountTransaction::query()
            ->when(!empty($filters['account_type']), function (Builder $builder) use ($filters) {
                $builder->where('account_type', $filters['account_type']);
            })
            ->whereDate('transaction_date', '<', $filters['date_from'])
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE -amount END), 0) as opening_balance")
            ->value('opening_balance');
    }

    private function applyDayBookSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function (Builder $builder) use ($keyword) {
            $builder->where('reference_type', 'like', '%' . $keyword . '%')
                ->orWhere('notes', 'like', '%' . $keyword . '%')
                ->orWhere('account_type', 'like', '%' . $keyword . '%')
                ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                    $customerQuery->where('name', 'like', '%' . $keyword . '%');
                })
                ->orWhereHas('supplier', function (Builder $supplierQuery) use ($keyword) {
                    $supplierQuery->where('supplier_name', 'like', '%' . $keyword . '%');
                });
        });
    }

    private function dayBookSummary(Collection $transactions, float $openingBalance): array
    {
        $totalDebit = round((float) $transactions->where('entry_type', 'debit')->sum('amount'), 2);
        $totalCredit = round((float) $transactions->where('entry_type', 'credit')->sum('amount'), 2);

        return [
            'opening_balance' => $openingBalance,
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'closing_balance' => round($openingBalance + $totalDebit - $totalCredit, 2),
        ];
    }
}
