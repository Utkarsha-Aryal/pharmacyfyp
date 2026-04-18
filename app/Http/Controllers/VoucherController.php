<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    private const VOUCHER_TYPES = [
        'journal' => 'Journal Voucher',
        'contra' => 'Contra Voucher',
        'debit_note' => 'Debit Note',
        'credit_note' => 'Credit Note',
    ];

    private const ACCOUNT_TYPES = [
        'cash' => 'Cash',
        'bank' => 'Bank',
        'receivable' => 'Receivable',
        'payable' => 'Payable',
        'expense' => 'Expense',
        'income' => 'Income',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['voucher_type', 'date_from', 'date_to']);
        $summaryQuery = $this->applyFilters(Voucher::query(), $filters);

        return view('finance.vouchers.index', [
            'filters' => $filters,
            'voucherTypes' => self::VOUCHER_TYPES,
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'amount' => round((float) (clone $summaryQuery)->sum('total_amount'), 2),
                'this_month' => (clone $summaryQuery)
                    ->whereMonth('voucher_date', now()->month)
                    ->whereYear('voucher_date', now()->year)
                    ->count(),
                'journal' => (clone $summaryQuery)->where('voucher_type', 'journal')->count(),
            ],
        ]);
    }

    public function list(Request $request)
    {
        $filters = $request->only(['voucher_type', 'date_from', 'date_to']);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = Voucher::query()
            ->with(['entries.customer', 'entries.supplier', 'creator'])
            ->orderByDesc('voucher_date')
            ->orderByDesc('id');

        $recordsTotal = (clone $query)->count();
        $query = $this->applyFilters($query, $filters);

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('voucher_no', 'like', '%' . $keyword . '%')
                    ->orWhere('voucher_type', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%')
                    ->orWhereHas('entries', function (Builder $entryQuery) use ($keyword) {
                        $entryQuery->where('account_type', 'like', '%' . $keyword . '%')
                            ->orWhere('entry_type', 'like', '%' . $keyword . '%')
                            ->orWhere('notes', 'like', '%' . $keyword . '%')
                            ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                                $customerQuery->where('name', 'like', '%' . $keyword . '%');
                            })
                            ->orWhereHas('supplier', function (Builder $supplierQuery) use ($keyword) {
                                $supplierQuery->where('supplier_name', 'like', '%' . $keyword . '%');
                            });
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $vouchers = $query->get();
        $data = [];

        foreach ($vouchers as $index => $voucher) {
            $partyNames = $voucher->entries
                ->map(fn (VoucherEntry $entry) => $entry->party_name)
                ->filter(fn (string $partyName) => $partyName !== '-')
                ->unique()
                ->take(2)
                ->implode(', ');

            $lineSummary = $voucher->entries
                ->take(2)
                ->map(fn (VoucherEntry $entry) => $entry->account_label . ' (' . ucfirst($entry->entry_type) . ')')
                ->implode(' | ');

            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.finance.vouchers.show', $voucher) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="View Voucher"><i class="fa-solid fa-eye"></i></a>';
            $action .= '<a href="' . route('admin.finance.vouchers.edit', $voucher) . '" class="btn btn-sm btn-outline-warning table-action-btn" title="Edit Voucher"><i class="fa-solid fa-pen-to-square"></i></a>';
            $action .= '<form action="' . route('admin.finance.vouchers.delete', $voucher) . '" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete voucher?" data-confirm-text="This will remove the voucher and its accounting rows." data-confirm-button="Yes, delete it">';
            $action .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            $action .= '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Voucher"><i class="fa-solid fa-trash"></i></button>';
            $action .= '</form></div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'voucher_no' => '<div class="fw-semibold">' . e($voucher->voucher_no) . '</div><div class="small text-muted">' . e($voucher->voucher_type_label) . '</div>',
                'date' => e($voucher->voucher_date_show),
                'party' => '<div class="text-wrap">' . e($partyNames ?: '-') . '</div>',
                'entries' => '<div class="text-wrap small">' . e($lineSummary ?: '-') . '</div><div class="small text-muted">' . e($voucher->entries->count()) . ' line(s)</div>',
                'amount' => '<span class="fw-semibold">' . e(money_value($voucher->total_amount)) . '</span>',
                'created_by' => e($voucher->creator?->name ?? '-'),
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        return view('finance.vouchers.create', $this->formData());
    }

    public function store(Request $request)
    {
        $voucher = $this->persistVoucher($request);

        return redirect()->route('admin.finance.vouchers.show', $voucher)->with('success', 'Voucher saved successfully.');
    }

    public function show(Voucher $voucher)
    {
        return view('finance.vouchers.show', [
            'voucher' => $voucher->load(['entries.customer', 'entries.supplier', 'creator', 'updater']),
        ]);
    }

    public function edit(Voucher $voucher)
    {
        return view('finance.vouchers.edit', $this->formData($voucher->load('entries')));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $voucher = $this->persistVoucher($request, $voucher);

        return redirect()->route('admin.finance.vouchers.show', $voucher)->with('success', 'Voucher updated successfully.');
    }

    public function destroy(Voucher $voucher)
    {
        DB::transaction(function () use ($voucher) {
            $voucher->load('entries');
            $this->syncCustomerBalances($voucher->entries, -1);

            AccountTransaction::query()
                ->where('reference_type', 'Voucher')
                ->where('reference_id', $voucher->id)
                ->delete();

            $voucher->delete();
        });

        return redirect()->route('admin.finance.vouchers.index')->with('success', 'Voucher deleted successfully.');
    }

    private function persistVoucher(Request $request, ?Voucher $existingVoucher = null): Voucher
    {
        $validated = $request->validate([
            'voucher_date' => ['required', 'date'],
            'voucher_type' => ['required', Rule::in(array_keys(self::VOUCHER_TYPES))],
            'notes' => ['nullable', 'string'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_type' => ['required', Rule::in(array_keys(self::ACCOUNT_TYPES))],
            'entries.*.party_type' => ['nullable', Rule::in(['customer', 'supplier'])],
            'entries.*.party_id' => ['nullable', 'integer', 'min:1'],
            'entries.*.entry_type' => ['required', Rule::in(['debit', 'credit'])],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = collect($validated['entries'])
            ->values()
            ->map(function (array $row, int $index) {
                return [
                    'line_no' => $index + 1,
                    'account_type' => $row['account_type'],
                    'party_type' => $row['party_type'] ?? null,
                    'party_id' => !empty($row['party_id']) ? (int) $row['party_id'] : null,
                    'entry_type' => $row['entry_type'],
                    'amount' => round((float) $row['amount'], 2),
                    'notes' => $row['notes'] ?? null,
                ];
            });

        $debitTotal = round((float) $rows->where('entry_type', 'debit')->sum('amount'), 2);
        $creditTotal = round((float) $rows->where('entry_type', 'credit')->sum('amount'), 2);

        if ($debitTotal <= 0 || $creditTotal <= 0) {
            throw ValidationException::withMessages([
                'entries' => 'Voucher must contain at least one debit line and one credit line.',
            ]);
        }

        if ($debitTotal !== $creditTotal) {
            throw ValidationException::withMessages([
                'entries' => 'Debit total and credit total must be equal.',
            ]);
        }

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 1;

            if ($row['account_type'] === 'receivable') {
                if ($row['party_type'] !== 'customer' || !$row['party_id'] || !Customer::query()->whereKey($row['party_id'])->exists()) {
                    throw ValidationException::withMessages([
                        'entries.' . $index . '.party_id' => 'Line ' . $lineNumber . ' needs a valid customer because the account is receivable.',
                    ]);
                }
            }

            if ($row['account_type'] === 'payable') {
                if ($row['party_type'] !== 'supplier' || !$row['party_id'] || !Supplier::query()->whereKey($row['party_id'])->exists()) {
                    throw ValidationException::withMessages([
                        'entries.' . $index . '.party_id' => 'Line ' . $lineNumber . ' needs a valid supplier because the account is payable.',
                    ]);
                }
            }

            if ($row['party_type'] === 'customer' && $row['party_id'] && !Customer::query()->whereKey($row['party_id'])->exists()) {
                throw ValidationException::withMessages([
                    'entries.' . $index . '.party_id' => 'Line ' . $lineNumber . ' has an invalid customer.',
                ]);
            }

            if ($row['party_type'] === 'supplier' && $row['party_id'] && !Supplier::query()->whereKey($row['party_id'])->exists()) {
                throw ValidationException::withMessages([
                    'entries.' . $index . '.party_id' => 'Line ' . $lineNumber . ' has an invalid supplier.',
                ]);
            }

            if (!$row['party_type']) {
                $rows[$index]['party_id'] = null;
            }
        }

        return DB::transaction(function () use ($request, $validated, $rows, $debitTotal, $existingVoucher) {
            if ($existingVoucher) {
                $existingVoucher->load('entries');
                $this->syncCustomerBalances($existingVoucher->entries, -1);

                AccountTransaction::query()
                    ->where('reference_type', 'Voucher')
                    ->where('reference_id', $existingVoucher->id)
                    ->delete();

                VoucherEntry::query()->where('voucher_id', $existingVoucher->id)->delete();

                $existingVoucher->update([
                    'voucher_date' => $validated['voucher_date'],
                    'voucher_type' => $validated['voucher_type'],
                    'total_amount' => $debitTotal,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => $request->user()->id,
                ]);

                $voucher = $existingVoucher->fresh();
            } else {
                $voucher = Voucher::query()->create([
                    'voucher_no' => $this->nextVoucherNumber(),
                    'voucher_date' => $validated['voucher_date'],
                    'voucher_type' => $validated['voucher_type'],
                    'total_amount' => $debitTotal,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }

            foreach ($rows as $row) {
                $entry = VoucherEntry::query()->create([
                    'voucher_id' => $voucher->id,
                    'line_no' => $row['line_no'],
                    'account_type' => $row['account_type'],
                    'party_type' => $row['party_type'],
                    'party_id' => $row['party_id'],
                    'entry_type' => $row['entry_type'],
                    'amount' => $row['amount'],
                    'notes' => $row['notes'],
                ]);

                record_account_transaction([
                    'transaction_date' => $voucher->voucher_date,
                    'reference_type' => 'Voucher',
                    'reference_id' => $voucher->id,
                    'party_type' => $entry->party_type,
                    'party_id' => $entry->party_id,
                    'entry_type' => $entry->entry_type,
                    'account_type' => $entry->account_type,
                    'amount' => $entry->amount,
                    'notes' => $entry->notes ?: ('Voucher ' . $voucher->voucher_no),
                    'created_by' => $request->user()->id,
                ]);
            }

            $voucher->load('entries');
            $this->syncCustomerBalances($voucher->entries, 1);

            return $voucher->fresh(['entries.customer', 'entries.supplier', 'creator', 'updater']);
        });
    }

    private function formData(?Voucher $voucher = null): array
    {
        $entryRows = $voucher
            ? $voucher->entries->map(function (VoucherEntry $entry) {
                return [
                    'account_type' => $entry->account_type,
                    'party_type' => $entry->party_type,
                    'party_id' => $entry->party_id,
                    'entry_type' => $entry->entry_type,
                    'amount' => number_format((float) $entry->amount, 2, '.', ''),
                    'notes' => $entry->notes,
                ];
            })->values()->all()
            : [
                ['account_type' => '', 'party_type' => '', 'party_id' => '', 'entry_type' => 'debit', 'amount' => '', 'notes' => ''],
                ['account_type' => '', 'party_type' => '', 'party_id' => '', 'entry_type' => 'credit', 'amount' => '', 'notes' => ''],
            ];

        return [
            'voucher' => $voucher,
            'voucherTypes' => self::VOUCHER_TYPES,
            'accountTypes' => self::ACCOUNT_TYPES,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'entryRows' => $entryRows,
        ];
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['voucher_type']), function (Builder $builder) use ($filters) {
                $builder->where('voucher_type', $filters['voucher_type']);
            })
            ->when(!empty($filters['date_from']), function (Builder $builder) use ($filters) {
                $builder->whereDate('voucher_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $builder) use ($filters) {
                $builder->whereDate('voucher_date', '<=', $filters['date_to']);
            });
    }

    private function nextVoucherNumber(): string
    {
        $lastId = (int) (Voucher::query()->max('id') ?? 0) + 1;

        return 'VCH-' . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function syncCustomerBalances($entries, int $direction): void
    {
        collect($entries)->each(function ($entry) use ($direction) {
            if ((string) $entry->account_type !== 'receivable' || (string) $entry->party_type !== 'customer' || empty($entry->party_id)) {
                return;
            }

            $customer = Customer::query()->lockForUpdate()->find($entry->party_id);

            if (!$customer) {
                return;
            }

            $entryDirection = (string) $entry->entry_type === 'debit' ? 1 : -1;
            $customer->current_balance = round((float) $customer->current_balance + ($entryDirection * (float) $entry->amount * $direction), 2);
            $customer->save();
        });
    }
}
