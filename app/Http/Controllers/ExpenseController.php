<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\DropdownOption;
use App\Models\Expense;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    // Show the expense tracking page with a small summary at the top.
    public function index()
    {
        $query = Expense::query();
        $cashModeIds = DropdownOption::query()
            ->forAlias('payment_mode')
            ->where('data', 'cash')
            ->pluck('id');
        $bankModeIds = DropdownOption::query()
            ->forAlias('payment_mode')
            ->where('data', 'bank')
            ->pluck('id');

        return view('expense.index', [
            'expenseCategories' => DropdownOption::query()->forAlias('expense_category')->active()->orderBy('name')->get(),
            'paymentModes' => DropdownOption::query()->forAlias('payment_mode')->active()->orderBy('name')->get(),
            'summary' => [
                'this_month' => (clone $query)
                    ->whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->sum('amount'),
                'cash' => (clone $query)
                    ->where(function ($builder) use ($cashModeIds) {
                        $builder->when($cashModeIds->isNotEmpty(), function ($innerBuilder) use ($cashModeIds) {
                            $innerBuilder->whereIn('payment_mode_id', $cashModeIds);
                        }, function ($innerBuilder) {
                            $innerBuilder->where('payment_mode', 'cash');
                        });
                    })
                    ->sum('amount'),
                'bank' => (clone $query)
                    ->where(function ($builder) use ($bankModeIds) {
                        $builder->when($bankModeIds->isNotEmpty(), function ($innerBuilder) use ($bankModeIds) {
                            $innerBuilder->whereIn('payment_mode_id', $bankModeIds);
                        }, function ($innerBuilder) {
                            $innerBuilder->where('payment_mode', 'bank');
                        });
                    })
                    ->sum('amount'),
                'total' => (clone $query)->sum('amount'),
            ],
        ]);
    }

    // Return expense rows for the server-side table.
    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = max((int) $request->input('length', 10), 1);

        $query = Expense::query()->with(['creator', 'expenseCategory', 'paymentModeOption'])->latest('expense_date')->latest('id');
        $recordsTotal = (clone $query)->count();

        if ($request->filled('payment_mode_id')) {
            $query->where('payment_mode_id', $request->payment_mode_id);
        }

        if ($request->filled('expense_category_id')) {
            $category = DropdownOption::query()->forAlias('expense_category')->find($request->expense_category_id);

            if ($category) {
                $query->where(function ($builder) use ($category) {
                    $builder->where('expense_category_id', $category->id)
                        ->orWhere('category', $category->name);
                });
            }
        }

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('category', 'like', '%' . $keyword . '%')
                    ->orWhereHas('expenseCategory', function ($expenseCategoryQuery) use ($keyword) {
                        $expenseCategoryQuery->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhere('vendor_name', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%');
            });
        }

        $recordsFiltered = (clone $query)->count();
        $expenses = $query->skip($start)->take($length)->get();

        $data = [];

        foreach ($expenses as $index => $expense) {
            $paymentClass = ($expense->paymentModeOption?->data ?: $expense->payment_mode) === 'bank'
                ? 'report-badge-info'
                : 'report-badge-warning';

            $action = '<div class="table-action-group">';
            $action .= '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editExpense" title="Edit Expense" aria-label="Edit Expense" data-id="' . $expense->id . '" data-expense-date="' . e($expense->expense_date) . '" data-expense-category-id="' . e($expense->expense_category_id) . '" data-expense-category-name="' . e($expense->expense_category_label) . '" data-vendor-name="' . e($expense->vendor_name) . '" data-payment-mode-id="' . e($expense->payment_mode_id) . '" data-amount="' . e($expense->amount) . '" data-notes="' . e($expense->notes) . '"><i class="fa-solid fa-pen-to-square"></i></button>';
            $action .= '<form action="' . route('admin.expenses.delete', $expense) . '" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete this expense?" data-confirm-text="This will remove the expense record from the list." data-confirm-button="Yes, delete expense">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Expense" aria-label="Delete Expense"><i class="fa-solid fa-trash"></i></button></form>';
            $action .= '</div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'date' => e($expense->expense_date_show),
                'category' => e($expense->expense_category_label),
                'vendor' => e($expense->vendor_name ?: '-'),
                'payment_mode' => '<span class="report-badge ' . $paymentClass . '">' . e($expense->payment_mode_label) . '</span>',
                'amount' => money_value($expense->amount),
                'notes' => e($expense->notes ?: '-'),
                'created_by' => e($expense->creator?->name ?: '-'),
                'action' => $action,
                'expense_category_id' => $expense->expense_category_id,
                'expense_category_name' => e($expense->expense_category_label),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Save one expense row and keep accounting entries in sync.
    public function save(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['nullable', 'integer', 'exists:expenses,id'],
                'expense_date' => ['required', 'date'],
                'expense_category_id' => ['nullable', 'integer', Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'expense_category'))],
                'category' => ['nullable', 'string', 'max:255'],
                'vendor_name' => ['nullable', 'string', 'max:255'],
                'payment_mode_id' => ['required', Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'payment_mode'))],
                'amount' => ['required', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
            ], [
                'expense_category_id.required' => 'Please select an expense category.',
                'expense_category_id.exists' => 'Please select a valid expense category.',
                'payment_mode_id.required' => 'Please select a payment mode.',
            ]);

            $expense = DB::transaction(function () use ($validated, $request) {
                $expenseCategory = null;

                if (!empty($validated['expense_category_id'])) {
                    $expenseCategory = DropdownOption::query()->forAlias('expense_category')->lockForUpdate()->find($validated['expense_category_id']);
                } elseif (!empty($validated['category'])) {
                    $expenseCategory = DropdownOption::query()->forAlias('expense_category')->lockForUpdate()->where('name', $validated['category'])->first();
                }

                if (!$expenseCategory) {
                    throw ValidationException::withMessages([
                        'expense_category_id' => 'Please select an expense category.',
                    ]);
                }

                $paymentMode = DropdownOption::query()
                    ->forAlias('payment_mode')
                    ->lockForUpdate()
                    ->findOrFail($validated['payment_mode_id']);

                $expense = !empty($validated['id'])
                    ? Expense::query()->lockForUpdate()->findOrFail($validated['id'])
                    : new Expense();

                if ($expense->exists) {
                    AccountTransaction::query()
                        ->where('reference_type', 'Expense')
                        ->where('reference_id', $expense->id)
                        ->delete();
                }

                $expense->fill([
                    'expense_date' => $validated['expense_date'],
                    'expense_category_id' => $expenseCategory->id,
                    'category' => $expenseCategory->name,
                    'vendor_name' => $validated['vendor_name'] ?? null,
                    'payment_mode_id' => $paymentMode->id,
                    'payment_mode' => $paymentMode->data ?: strtolower($paymentMode->name),
                    'amount' => $validated['amount'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $expense->exists ? $expense->created_by : $request->user()->id,
                ]);

                $expense->save();

                record_account_transaction([
                    'transaction_date' => $expense->expense_date,
                    'reference_type' => 'Expense',
                    'reference_id' => $expense->id,
                    'entry_type' => 'debit',
                    'account_type' => 'expense',
                    'amount' => $expense->amount,
                    'notes' => 'Expense posted under ' . $expense->category,
                    'created_by' => $request->user()->id,
                ]);

                record_account_transaction([
                    'transaction_date' => $expense->expense_date,
                    'reference_type' => 'Expense',
                    'reference_id' => $expense->id,
                    'entry_type' => 'credit',
                    'account_type' => ($paymentMode->data === 'cash') ? 'cash' : 'bank',
                    'amount' => $expense->amount,
                    'notes' => 'Expense payment by ' . $paymentMode->name,
                    'created_by' => $request->user()->id,
                ]);

                return $expense;
            });

            return response()->json([
                'type' => 'success',
                'message' => empty($validated['id']) ? 'Expense added successfully.' : 'Expense updated successfully.',
            ]);
        } catch (QueryException|Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // Delete one expense and remove its linked accounting entries too.
    public function delete(Expense $expense)
    {
        DB::transaction(function () use ($expense) {
            AccountTransaction::query()
                ->where('reference_type', 'Expense')
                ->where('reference_id', $expense->id)
                ->delete();

            $expense->delete();
        });

        return back()->with('success', 'Expense deleted successfully.');
    }
}
