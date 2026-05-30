<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DropdownOption;
use App\Models\PartyType;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    // Show the sales invoice list page with quick summary cards.
    public function index(Request $request)
    {
        $summaryQuery = SalesInvoice::query();
        $creditSaleTypeId = DropdownOption::findIdByAliasAndName('sales_type', 'Credit');

        return view('sales.index', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'saleTypes' => DropdownOption::query()->forAlias('sales_type')->active()->orderBy('name')->get(),
            'filters' => $request->only(['customer_id', 'sale_type_id', 'status', 'payment_status', 'date_from', 'date_to']),
            'summary' => [
                'this_month' => (clone $summaryQuery)
                    ->whereMonth('invoice_date', now()->month)
                    ->whereYear('invoice_date', now()->year)
                    ->sum('total_amount'),
                'all_time' => (clone $summaryQuery)->sum('total_amount'),
                'receivable' => (clone $summaryQuery)->get()->sum(fn (SalesInvoice $invoice) => $invoice->due_amount),
                'paid' => (clone $summaryQuery)->sum('paid_amount'),
                'pending' => (clone $summaryQuery)->where('status', 'draft')->count(),
                'credit' => $creditSaleTypeId
                    ? (clone $summaryQuery)->where('sale_type_id', $creditSaleTypeId)->count()
                    : (clone $summaryQuery)->where('sale_type', 'credit')->count(),
            ],
        ]);
    }

    // Show the sales return manager with summary cards and filters.
    public function returnsIndex(Request $request)
    {
        $filters = $request->only(['customer_id', 'product_id', 'refund_status', 'date_from', 'date_to']);
        $summaryQuery = $this->applySalesReturnFilters(SalesReturn::query(), $filters);

        return view('sales.returns.index', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'filters' => $filters,
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'refund_total' => round((float) (clone $summaryQuery)->sum('refund_amount'), 2),
                'paid_out_total' => round((float) (clone $summaryQuery)->sum('cash_refund_amount'), 2),
                'pending_credit_total' => round((float) (clone $summaryQuery)->sum('pending_credit_amount'), 2),
            ],
        ]);
    }

    // Return sales return rows for the manager table.
    public function returnsList(Request $request)
    {
        $filters = $request->only(['customer_id', 'product_id', 'refund_status', 'date_from', 'date_to']);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = SalesReturn::query()
            ->with(['invoice.customer', 'product', 'batch', 'creator', 'paymentMode'])
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        $recordsTotal = (clone $query)->count();
        $query = $this->applySalesReturnFilters($query, $filters);

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reason', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%')
                    ->orWhere('refund_status', 'like', '%' . $keyword . '%')
                    ->orWhereHas('invoice', function (Builder $invoiceQuery) use ($keyword) {
                        $invoiceQuery->where('reference', 'like', '%' . $keyword . '%')
                            ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                                $customerQuery->where('name', 'like', '%' . $keyword . '%');
                            });
                    })
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('batch', function (Builder $batchQuery) use ($keyword) {
                        $batchQuery->where('batch_number', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('paymentMode', function (Builder $paymentModeQuery) use ($keyword) {
                        $paymentModeQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $returns = $query->get();
        $data = [];

        foreach ($returns as $index => $return) {
            $invoiceLabel = $return->invoice?->reference ?: ('Invoice #' . $return->sales_invoice_id);
            $customerName = $return->invoice?->customer?->name ?: 'Walk-in Customer';
            $productLabel = $return->product?->display_name ?? '-';
            $batchLabel = $return->batch?->batch_number ?: '-';
            $settlementLabel = $return->cash_refund_amount > 0
                ? ($return->paymentMode?->name ?: 'Paid out')
                : ($return->pending_credit_amount > 0 ? 'Pending customer credit' : 'Adjusted against balance');
            $action = '<div class="table-action-group">';

            $action .= '<a href="' . route('admin.sales.returns.show', $return) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="View Return" aria-label="View Return"><i class="fa-solid fa-eye"></i></a>';
            $action .= '<a href="' . route('admin.sales.returns.edit', $return) . '" class="btn btn-sm btn-outline-warning table-action-btn" title="Edit Return" aria-label="Edit Return"><i class="fa-solid fa-pen-to-square"></i></a>';
            $action .= '<form action="' . route('admin.sales.returns.delete', $return) . '" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete sales return?" data-confirm-text="This will remove the return and take the stock back out of inventory." data-confirm-button="Yes, delete it">';
            $action .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            $action .= '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Return" aria-label="Delete Return"><i class="fa-solid fa-trash"></i></button>';
            $action .= '</form></div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'date' => $return->return_date_show,
                'invoice' => '<div class="fw-semibold">' . e($invoiceLabel) . '</div><div class="small text-muted">Return #' . (int) $return->id . '</div>',
                'customer' => '<div class="text-wrap">' . e($customerName) . '</div>',
                'product' => '<div class="fw-semibold text-wrap">' . e($productLabel) . '</div><div class="small text-muted">Batch: ' . e($batchLabel) . '</div>',
                'qty' => '<span class="badge bg-info text-dark">' . e(number_format((float) $return->quantity, 0)) . '</span>',
                'discount' => '<div class="text-wrap"><span class="badge bg-warning text-dark">' . e(number_format((float) $return->effective_discount_percent, 2)) . '%</span><div class="small text-muted mt-1">' . e(money_value($return->effective_discount_amount)) . '</div></div>',
                'net_rate' => money_value($return->effective_net_unit_price),
                'refund' => '<div class="fw-semibold">' . e(money_value($return->refund_amount)) . '</div><div class="small text-muted">Adj ' . e(money_value($return->receivable_adjusted_amount)) . ' | Cash ' . e(money_value($return->cash_refund_amount)) . ' | Credit ' . e(money_value($return->pending_credit_amount)) . '</div>',
                'settlement' => '<div class="text-wrap"><span class="badge ' . e($return->refund_status_badge_class) . '">' . e($return->refund_status_label) . '</span><div class="small text-muted mt-1">' . e($settlementLabel) . '</div></div>',
                'reason' => '<div class="text-wrap small">' . e(Str::limit((string) ($return->reason ?: $return->notes ?: '-'), 90)) . '</div>',
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

    // Open the dedicated sales return form so staff can work from one clear screen.
    public function returnsCreate(Request $request)
    {
        $invoice = null;

        if ($request->filled('sales_invoice_id')) {
            $invoice = SalesInvoice::query()->findOrFail($request->integer('sales_invoice_id'));
        }

        return view('sales.returns.create', $this->salesReturnFormData(null, $invoice, $request));
    }

    // Save one sales return from the dedicated manager flow.
    public function returnsStore(Request $request)
    {
        $returnMode = $request->input('return_mode') === 'customer_product' ? 'customer_product' : 'invoice';

        $validated = $request->validate([
            'return_mode' => ['nullable', Rule::in(['invoice', 'customer_product'])],
            'sales_invoice_id' => [
                'nullable',
                'exists:sales_invoices,id',
                Rule::requiredIf(fn () => $returnMode === 'invoice'),
            ],
            'customer_id' => [
                'nullable',
                'exists:customers,id',
                Rule::requiredIf(fn () => $returnMode === 'customer_product'),
            ],
            'product_id' => ['nullable', 'exists:products,id'],
            'return_date' => ['required', 'date'],
            'refund_status' => ['required', Rule::in(['pending', 'paid'])],
            'payment_mode_id' => [
                'nullable',
                Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'payment_mode')),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_item_id' => ['required', 'exists:sales_invoice_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.net_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rows = collect($validated['items'])
            ->filter(fn (array $row) => (float) ($row['quantity'] ?? 0) > 0)
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Please add at least one return item with quantity.',
            ]);
        }

        DB::transaction(function () use ($rows, $validated, $request) {
            foreach ($rows as $row) {
                $this->saveSalesReturnValidated([
                    'return_mode' => $validated['return_mode'] ?? 'invoice',
                    'sales_invoice_id' => $validated['sales_invoice_id'] ?? null,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'sales_invoice_item_id' => $row['sales_invoice_item_id'],
                    'return_date' => $validated['return_date'],
                    'quantity' => $row['quantity'],
                    'refund_status' => $validated['refund_status'],
                    'payment_mode_id' => $validated['payment_mode_id'] ?? null,
                    'reason' => $validated['reason'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'unit_price' => $row['unit_price'] ?? null,
                    'discount_percent' => $row['discount_percent'] ?? null,
                    'discount_amount' => $row['discount_amount'] ?? null,
                    'net_unit_price' => $row['net_unit_price'] ?? null,
                    'refund_amount' => $row['refund_amount'] ?? null,
                ], $request);
            }
        });

        return redirect()->route('admin.sales.returns.index')->with('success', 'Sales return saved successfully.');
    }

    // Show one sales return with the same plain detail layout as purchase returns.
    public function returnsShow(SalesReturn $salesReturn)
    {
        $salesReturn->load(['invoice.customer', 'invoiceItem', 'product', 'batch', 'creator', 'paymentMode']);

        return view('sales.returns.show', [
            'salesReturn' => $salesReturn,
        ]);
    }

    // Open the edit screen with the current return already selected.
    public function returnsEdit(SalesReturn $salesReturn)
    {
        $salesReturn->load(['invoice.customer', 'invoiceItem.product', 'invoiceItem.batch', 'product', 'batch']);

        return view('sales.returns.edit', $this->salesReturnFormData($salesReturn, $salesReturn->invoice));
    }

    // Update an existing sales return and rebuild stock/accounting safely.
    public function returnsUpdate(Request $request, SalesReturn $salesReturn)
    {
        if ($request->has('items')) {
            $returnMode = $request->input('return_mode') === 'customer_product' ? 'customer_product' : 'invoice';

            $validated = $request->validate([
                'return_mode' => ['nullable', Rule::in(['invoice', 'customer_product'])],
                'sales_invoice_id' => [
                    'nullable',
                    'exists:sales_invoices,id',
                    Rule::requiredIf(fn () => $returnMode === 'invoice'),
                ],
                'customer_id' => [
                    'nullable',
                    'exists:customers,id',
                    Rule::requiredIf(fn () => $returnMode === 'customer_product'),
                ],
                'product_id' => ['nullable', 'exists:products,id'],
                'return_date' => ['required', 'date'],
                'refund_status' => ['required', Rule::in(['pending', 'paid'])],
                'payment_mode_id' => [
                    'nullable',
                    Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'payment_mode')),
                ],
                'reason' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.sales_invoice_item_id' => ['required', 'exists:sales_invoice_items,id'],
                'items.*.quantity' => ['required', 'numeric', 'min:1'],
                'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
                'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
                'items.*.net_unit_price' => ['nullable', 'numeric', 'min:0'],
                'items.*.refund_amount' => ['nullable', 'numeric', 'min:0'],
            ]);

            $rows = collect($validated['items'])
                ->filter(fn (array $row) => (float) ($row['quantity'] ?? 0) > 0)
                ->values();

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Please add at least one return item with quantity.',
                ]);
            }

            DB::transaction(function () use ($rows, $validated, $request, $salesReturn) {
                $existingRow = $salesReturn;

                foreach ($rows as $row) {
                    $this->saveSalesReturnValidated([
                        'return_mode' => $validated['return_mode'] ?? 'invoice',
                        'sales_invoice_id' => $validated['sales_invoice_id'] ?? null,
                        'customer_id' => $validated['customer_id'] ?? null,
                        'sales_invoice_item_id' => $row['sales_invoice_item_id'],
                        'return_date' => $validated['return_date'],
                        'quantity' => $row['quantity'],
                        'refund_status' => $validated['refund_status'],
                        'payment_mode_id' => $validated['payment_mode_id'] ?? null,
                        'reason' => $validated['reason'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'unit_price' => $row['unit_price'] ?? null,
                        'discount_percent' => $row['discount_percent'] ?? null,
                        'discount_amount' => $row['discount_amount'] ?? null,
                        'net_unit_price' => $row['net_unit_price'] ?? null,
                        'refund_amount' => $row['refund_amount'] ?? null,
                    ], $request, $existingRow);

                    $existingRow = null;
                }
            });

            return redirect()->route('admin.sales.returns.index')->with('success', 'Sales return updated successfully.');
        }

        $this->persistSalesReturn($request, $salesReturn);

        return redirect()->route('admin.sales.returns.index')->with('success', 'Sales return updated successfully.');
    }

    // Delete a sales return only when the returned stock is still available to remove again.
    public function returnsDestroy(SalesReturn $salesReturn)
    {
        try {
            DB::transaction(function () use ($salesReturn) {
                $salesReturn->load(['invoice.customer', 'invoiceItem.batch']);
                $this->rollbackSalesReturnEffects($salesReturn);
                $this->deleteSalesReturnRecords($salesReturn);
                $salesReturn->delete();
            });

            return back()->with('success', 'Sales return deleted successfully.');
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first() ?: 'Could not delete sales return.');
        } catch (\Throwable $throwable) {
            return back()->with('error', $throwable->getMessage() ?: 'Could not delete sales return.');
        }
    }

    // Search invoices for the sales return form.
    public function returnInvoiceOptions(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $invoices = SalesInvoice::query()
            ->with(['customer', 'paymentMode'])
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $query->where(function (Builder $builder) use ($keyword) {
                    $builder->where('reference', 'like', '%' . $keyword . '%')
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                            $customerQuery->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('contact_person', 'like', '%' . $keyword . '%')
                                ->orWhere('phone', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (SalesInvoice $invoice) => $this->formatSalesReturnInvoiceOption($invoice))
            ->values();

        return response()->json(['results' => $invoices]);
    }

    // Search only the returnable items for one selected invoice.
    public function returnItemOptions(Request $request)
    {
        $validated = $request->validate([
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'sales_return_id' => ['nullable', 'exists:sales_returns,id'],
        ]);

        $editingReturn = !empty($validated['sales_return_id'])
            ? SalesReturn::query()->findOrFail($validated['sales_return_id'])
            : null;

        $keyword = trim((string) $request->input('q'));

        if (!empty($validated['sales_invoice_id'])) {
            $invoice = SalesInvoice::query()->findOrFail($validated['sales_invoice_id']);

            if ($editingReturn && (int) $editingReturn->sales_invoice_id !== (int) $invoice->id) {
                $editingReturn = null;
            }

            $options = $this->buildSalesReturnItemOptions($invoice, $editingReturn);
        } elseif (!empty($validated['customer_id'])) {
            $options = $this->buildSalesReturnCustomerItemOptions(
                (int) $validated['customer_id'],
                !empty($validated['product_id']) ? (int) $validated['product_id'] : null,
                $editingReturn
            );
        } else {
            $options = [];
        }

        $items = collect($options)
            ->when($keyword !== '', function ($collection) use ($keyword) {
                return $collection->filter(function (array $row) use ($keyword) {
                    $haystack = implode(' ', [
                        $row['text'] ?? '',
                        $row['product_name'] ?? '',
                        $row['batch_number'] ?? '',
                        $row['invoice_reference'] ?? '',
                    ]);

                    return Str::contains(Str::lower($haystack), Str::lower($keyword));
                })->values();
            })
            ->values();

        return response()->json(['results' => $items]);
    }

    // Return invoice rows for the server-side table.
    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = max((int) $request->input('length', 10), 1);

        $query = SalesInvoice::query()
            ->with(['customer', 'paymentMode', 'saleTypeOption'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        $recordsTotal = (clone $query)->count();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sale_type_id')) {
            $query->where('sale_type_id', $request->sale_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reference', 'like', '%' . $keyword . '%')
                    ->orWhereHas('saleTypeOption', function (Builder $saleTypeQuery) use ($keyword) {
                        $saleTypeQuery->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhere('sale_type', 'like', '%' . $keyword . '%')
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                        $customerQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();
        $invoices = $query->skip($start)->take($length)->get();

        $data = [];

        foreach ($invoices as $index => $invoice) {
            $statusClass = $invoice->status === 'cancelled'
                ? 'bg-danger'
                : ($invoice->status === 'draft' ? 'bg-warning text-dark' : 'bg-success');
            $paymentClass = $invoice->payment_status === 'paid'
                ? 'bg-success'
                : ($invoice->payment_status === 'partial' ? 'bg-warning text-dark' : 'bg-danger');

            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.sales.show', $invoice) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="View Invoice" aria-label="View Invoice"><i class="fa-solid fa-eye"></i></a>';
            $action .= '<a href="' . route('admin.sales-invoices.print', $invoice) . '" target="_blank" class="btn btn-sm btn-outline-dark table-action-btn" title="Print / PDF" aria-label="Print / PDF"><i class="fa-solid fa-print"></i></a>';
            $action .= '<a href="' . route('admin.sales.show', $invoice) . '#paymentModal" class="btn btn-sm btn-outline-success table-action-btn" title="Payment" aria-label="Payment"><i class="fa-solid fa-wallet"></i></a>';
            $action .= '<a href="' . route('admin.sales.returns.create', ['sales_invoice_id' => $invoice->id]) . '" class="btn btn-sm btn-outline-danger table-action-btn" title="Return" aria-label="Return"><i class="fa-solid fa-rotate-left"></i></a>';
            $action .= '</div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'reference' => e($invoice->reference),
                'customer' => e($invoice->customer?->name ?: '-'),
                'date' => e($invoice->invoice_date_show),
                'sale_type' => '<span class="badge bg-info text-dark">' . e($invoice->sale_type_label) . '</span>',
                'status' => '<span class="badge ' . $statusClass . '">' . e($invoice->status_label) . '</span>',
                'payment' => '<span class="badge ' . $paymentClass . '">' . e($invoice->payment_label) . '</span>',
                'total' => money_value($invoice->total_amount),
                'due' => money_value($invoice->due_amount),
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

    // Open the sales billing form.
    public function create()
    {
        return view('sales.create', [
            'reference' => next_sales_reference(),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'partyTypes' => PartyType::query()->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'paymentModes' => DropdownOption::query()->forAlias('payment_mode')->active()->orderBy('name')->get(),
            'formulations' => DropdownOption::query()->forAlias('formulation')->active()->orderBy('name')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('unit_name')->get(),
            'saleTypes' => DropdownOption::query()->forAlias('sales_type')->active()->orderBy('name')->get(),
        ]);
    }

    // Search active customers for select2.
    public function customerOptions(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $customers = Customer::query()
            ->where('is_active', true)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function (Builder $builder) use ($keyword) {
                    $builder->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('contact_person', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'text' => $customer->name . ($customer->contact_person ? ' - ' . $customer->contact_person : ''),
            ])
            ->values();

        return response()->json(['results' => $customers]);
    }

    // Search products for select2 on the billing form.
    public function productOptions(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $products = Product::query()
            ->with('company')
            ->where('status', 'Y')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function (Builder $builder) use ($keyword) {
                    $builder->where('product_name', 'like', '%' . $keyword . '%')
                        ->orWhere('name', 'like', '%' . $keyword . '%')
                        ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('product_name')
            ->limit(20)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'text' => $product->display_name . ($product->company?->name ? ' - ' . $product->company->name : ''),
            ])
            ->values();

        return response()->json(['results' => $products]);
    }

    // Return the current product price and available stock for the row helper.
    public function productInfo(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $product = Product::query()
            ->with(['company', 'batches' => function ($query) {
                $query->where('is_active', true);
            }])
            ->findOrFail($validated['product_id']);

        $activeBatches = $product->batches
            ->sortBy('expiry_date')
            ->values();

        return response()->json([
            'id' => $product->id,
            'name' => $product->display_name,
            'price' => $this->resolveSalePrice($product),
            'mrp' => round((float) ($product->mrp ?? 0), 2),
            'cc_rate' => round((float) ($product->effective_cc_rate ?? 0), 2),
            'stock' => (int) $activeBatches->sum('quantity_available'),
            'batches' => $activeBatches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'text' => $batch->batch_number . ' | ' . human_date($batch->expiry_date),
                    'available' => (int) $batch->quantity_available,
                ];
            })->values(),
        ]);
    }

    // Store one billing invoice with batches, items, balances and accounting rows.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                'exists:customers,id',
                Rule::requiredIf(function () use ($request) {
                    $saleType = DropdownOption::query()
                        ->forAlias('sales_type')
                        ->find($request->input('sale_type_id'));

                    return in_array(strtolower((string) $saleType?->name), ['wholesale', 'credit'], true);
                }),
            ],
            'invoice_date' => ['required', 'date'],
            'sale_type_id' => ['required', Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'sales_type'))],
            'payment_mode_id' => [
                'nullable',
                Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'payment_mode')),
                Rule::requiredIf(fn () => (float) $request->input('paid_amount', 0) > 0),
            ],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.batch_id' => ['required', 'exists:batches,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.free_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.mrp' => ['required', 'numeric', 'min:0'],
            'items.*.cc_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        try {
            $invoice = DB::transaction(function () use ($validated, $request) {
                $subtotal = 0;
                $discountAmount = 0;
                $invoiceTotal = 0;
                $saleType = DropdownOption::query()->forAlias('sales_type')->findOrFail($validated['sale_type_id']);
                $paidAmount = round((float) ($validated['paid_amount'] ?? 0), 2);
                $paymentMode = $paidAmount > 0 && !empty($validated['payment_mode_id'])
                    ? DropdownOption::query()->forAlias('payment_mode')->findOrFail($validated['payment_mode_id'])
                    : null;

                $invoice = SalesInvoice::create([
                    'reference' => next_sales_reference(),
                    'customer_id' => $validated['customer_id'] ?? null,
                    'sold_by' => $request->user()->id,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                    'invoice_date' => $validated['invoice_date'],
                    'sale_type_id' => $saleType->id,
                    'sale_type' => strtolower($saleType->name),
                    'status' => 'confirmed',
                    'payment_status' => 'unpaid',
                    'payment_method' => $paidAmount > 0 ? ($paymentMode?->data ?: 'cash') : 'none',
                    'payment_mode_id' => $paidAmount > 0 ? $paymentMode?->id : null,
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'total_discount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => $paidAmount,
                    'notes' => $validated['notes'] ?? null,
                    'confirmed_at' => now(),
                ]);

                foreach ($validated['items'] as $row) {
                    $product = Product::query()->findOrFail($row['product_id']);
                    $quantity = (float) $row['quantity'];
                    $freeQuantity = (float) ($row['free_qty'] ?? 0);
                    $unitPrice = (float) $row['unit_price'];
                    $mrp = (float) ($row['mrp'] ?? 0);
                    $ccRate = (float) ($row['cc_rate'] ?? $product->effective_cc_rate ?? 0);
                    $discountPercent = (float) ($row['discount_percent'] ?? 0);
                    $stockQuantity = $quantity + $freeQuantity;
                    $batch = !empty($row['batch_id'])
                        ? $this->selectedBatchForSale((int) $product->id, (int) $row['batch_id'], $stockQuantity)
                        : $this->pickBatchForSale((int) $product->id, $stockQuantity);

                    $lineBase = round($quantity * $unitPrice, 2);
                    $lineDiscount = round(($lineBase * $discountPercent) / 100, 2);
                    $lineTotal = round($lineBase - $lineDiscount, 2);
                    $freeGoodsValue = round($freeQuantity * ($mrp * $ccRate / 100), 2);

                    // Free qty also leaves the store, so stock must move for bill qty + free qty together.
                    $batch->quantity_available = max(0, (float) $batch->quantity_available - $stockQuantity);
                    $batch->save();

                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'batch_id' => $batch->id,
                        'quantity' => $quantity,
                        'free_qty' => $freeQuantity,
                        'unit_price' => $unitPrice,
                        'mrp' => $mrp,
                        'cc_rate' => $ccRate,
                        'discount_percent' => $discountPercent,
                        'discount_amount' => $lineDiscount,
                        'free_goods_value' => $freeGoodsValue,
                        'subtotal' => $lineTotal,
                    ]);

                    record_stock_movement([
                        'movement_date' => $validated['invoice_date'],
                        'product_id' => $product->id,
                        'batch_id' => $batch->id,
                        'movement_type' => 'sales_out',
                        'quantity_out' => (int) $stockQuantity,
                        'source_type' => 'Inventory',
                        'destination_type' => 'Customer',
                        'destination_id' => $validated['customer_id'] ?? null,
                        'reference_type' => 'SalesInvoice',
                        'reference_id' => $invoice->id,
                        'notes' => 'Stock issued from sales invoice.',
                        'created_by' => $request->user()->id,
                    ]);

                    $subtotal += $lineBase;
                    $discountAmount += $lineDiscount;
                    $invoiceTotal += $lineTotal;
                }

                if ($paidAmount > round($invoiceTotal, 2)) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Paid amount cannot be greater than invoice total.',
                    ]);
                }

                $paymentStatus = SalesInvoice::resolvePaymentStatus($invoiceTotal, $paidAmount);

                $invoice->update([
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => round($discountAmount, 2),
                    'total_discount' => round($discountAmount, 2),
                    'total_amount' => round($invoiceTotal, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'payment_status' => $paymentStatus,
                    'updated_by' => $request->user()->id,
                ]);

                if ($invoice->customer_id) {
                    $customer = Customer::query()->lockForUpdate()->findOrFail($invoice->customer_id);
                    $customer->current_balance = round((float) $customer->current_balance + $invoice->due_amount, 2);
                    $customer->save();
                }

                if ($paidAmount > 0) {
                    $cashAccount = $paymentMode?->data === 'bank' ? 'bank' : 'cash';

                    record_account_transaction([
                        'transaction_date' => $invoice->invoice_date,
                        'reference_type' => 'SalesInvoice',
                        'reference_id' => $invoice->id,
                        'party_type' => $invoice->customer_id ? 'customer' : null,
                        'party_id' => $invoice->customer_id,
                        'entry_type' => 'debit',
                        'account_type' => $cashAccount,
                        'amount' => $paidAmount,
                        'notes' => 'Sale payment received for ' . $invoice->reference,
                        'created_by' => $request->user()->id,
                    ]);
                }

                if ($invoice->due_amount > 0) {
                    record_account_transaction([
                        'transaction_date' => $invoice->invoice_date,
                        'reference_type' => 'SalesInvoice',
                        'reference_id' => $invoice->id,
                        'party_type' => $invoice->customer_id ? 'customer' : null,
                        'party_id' => $invoice->customer_id,
                        'entry_type' => 'debit',
                        'account_type' => 'receivable',
                        'amount' => $invoice->due_amount,
                        'notes' => 'Sale due amount for ' . $invoice->reference,
                        'created_by' => $request->user()->id,
                    ]);
                }

                record_account_transaction([
                    'transaction_date' => $invoice->invoice_date,
                    'reference_type' => 'SalesInvoice',
                    'reference_id' => $invoice->id,
                    'party_type' => $invoice->customer_id ? 'customer' : null,
                    'party_id' => $invoice->customer_id,
                    'entry_type' => 'credit',
                    'account_type' => 'income',
                    'amount' => $invoice->total_amount,
                    'notes' => 'Sales income for ' . $invoice->reference,
                    'created_by' => $request->user()->id,
                ]);

                return $invoice->load(['customer', 'items.product', 'items.batch']);
            });

            return response()->json([
                'type' => 'success',
                'message' => 'Sales invoice saved successfully.',
                'redirect' => route('admin.sales.show', $invoice),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
            ], 422);
        } catch (QueryException|Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // Show one invoice with items, payment and return history.
    public function show(SalesInvoice $salesInvoice)
    {
        return view('sales.show', $this->invoiceViewData($salesInvoice, false));
    }

    // Return invoice item rows for the detail screen table.
    public function invoiceItemsList(Request $request, SalesInvoice $salesInvoice)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = SalesInvoiceItem::query()
            ->with(['product', 'batch'])
            ->where('sales_invoice_id', $salesInvoice->id)
            ->orderBy('id');

        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('discount_percent', 'like', '%' . $keyword . '%')
                    ->orWhere('cc_rate', 'like', '%' . $keyword . '%')
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('batch', function (Builder $batchQuery) use ($keyword) {
                        $batchQuery->where('batch_number', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $items = $query->get();
        $data = [];

        foreach ($items as $index => $item) {
            $data[] = [
                'sno' => $start + $index + 1,
                'product' => e($item->product?->display_name ?? '-'),
                'batch' => '<span class="badge bg-light text-dark border">' . e($item->batch?->batch_number ?? '-') . '</span>',
                'qty' => '<span class="badge bg-secondary">' . (float) $item->quantity . '</span>',
                'free_qty' => (float) ($item->free_qty ?? 0),
                'mrp' => money_value($item->mrp ?? 0),
                'unit_price' => money_value($item->unit_price),
                'discount_percent' => e(number_format((float) $item->discount_percent, 2)) . '%',
                'cc_rate' => e(number_format((float) ($item->cc_rate ?? 0), 2)) . '%',
                'free_goods_value' => money_value($item->free_goods_value ?? 0),
                'subtotal' => money_value($item->subtotal),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Return sales return history rows for one invoice detail screen.
    public function invoiceReturnsList(Request $request, SalesInvoice $salesInvoice)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = SalesReturn::query()
            ->with(['product', 'batch', 'paymentMode'])
            ->where('sales_invoice_id', $salesInvoice->id)
            ->latest('return_date')
            ->latest('id');

        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reason', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%')
                    ->orWhere('refund_status', 'like', '%' . $keyword . '%')
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('batch', function (Builder $batchQuery) use ($keyword) {
                        $batchQuery->where('batch_number', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('paymentMode', function (Builder $modeQuery) use ($keyword) {
                        $modeQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $returns = $query->get();
        $data = [];

        foreach ($returns as $index => $returnItem) {
            $settlementLabel = $returnItem->cash_refund_amount > 0
                ? ($returnItem->payment_mode_label ?: 'Paid out')
                : ($returnItem->pending_credit_amount > 0 ? 'Pending customer credit' : 'Adjusted against balance');
            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.sales.returns.show', $returnItem) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="View Return"><i class="fa-solid fa-eye"></i></a>';
            $action .= '<a href="' . route('admin.sales.returns.edit', $returnItem) . '" class="btn btn-sm btn-outline-warning table-action-btn" title="Edit Return"><i class="fa-solid fa-pen-to-square"></i></a>';
            $action .= '<form action="' . route('admin.sales.returns.delete', $returnItem) . '" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete sales return?" data-confirm-text="This will remove the return and take the stock back out of inventory." data-confirm-button="Yes, delete it">';
            $action .= csrf_field();
            $action .= '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Return"><i class="fa-solid fa-trash"></i></button>';
            $action .= '</form></div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'date' => e($returnItem->return_date_show),
                'product' => e($returnItem->product?->display_name ?? '-'),
                'batch' => '<span class="badge bg-light text-dark border">' . e($returnItem->batch?->batch_number ?? '-') . '</span>',
                'qty' => '<span class="badge bg-secondary">' . (float) $returnItem->quantity . '</span>',
                'discount_percent' => e(number_format((float) $returnItem->effective_discount_percent, 2)) . '%',
                'discount_amount' => money_value($returnItem->effective_discount_amount),
                'net_rate' => money_value($returnItem->effective_net_unit_price),
                'refund' => '<div>' . e(money_value($returnItem->refund_amount)) . '</div><small class="text-muted d-block">Adj ' . e(money_value($returnItem->receivable_adjusted_amount)) . '</small><small class="text-muted d-block">Cash ' . e(money_value($returnItem->cash_refund_amount)) . ' | Credit ' . e(money_value($returnItem->pending_credit_amount)) . '</small>',
                'settlement' => '<span class="badge ' . e($returnItem->refund_status_badge_class) . '">' . e($returnItem->refund_status_label) . '</span><small class="text-muted d-block mt-1">' . e($settlementLabel) . '</small>',
                'reason' => e($returnItem->reason ?: '-'),
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

    // Open one focused invoice page in a new tab so the user prints only the bill.
    public function printView(SalesInvoice $salesInvoice)
    {
        return $this->printPdf($salesInvoice);
    }

    // Download the invoice as PDF for customer copy or office record.
    public function pdf(SalesInvoice $salesInvoice)
    {
        return $this->printPdf($salesInvoice);
    }

    // Stream the invoice PDF in a new browser tab.
    public function printPdf(SalesInvoice $salesInvoice)
    {
        $invoice = $this->loadInvoiceRelations($salesInvoice);

        return Pdf::loadView('pdf.sales-invoice', [
            'invoice' => $invoice,
            'company' => pdf_company_context(),
            'logoSrc' => pdf_logo_src(),
        ])->setPaper('a4', 'portrait')->stream($invoice->reference . '.pdf');
    }

    // Update payment status and keep customer balance in sync.
    public function updatePayment(Request $request, SalesInvoice $salesInvoice)
    {
        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['unpaid', 'partial', 'paid'])],
            'paid_amount' => ['required', 'numeric', 'min:0', 'max:' . (float) $salesInvoice->total_amount],
            'payment_mode_id' => [
                Rule::requiredIf(fn () => (float) $request->input('paid_amount', 0) > 0),
                'nullable',
                Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'payment_mode')),
            ],
        ]);

        DB::transaction(function () use ($salesInvoice, $validated, $request) {
            $salesInvoice->loadMissing('customer');
            $paymentMode = !empty($validated['payment_mode_id'])
                ? DropdownOption::query()->forAlias('payment_mode')->findOrFail($validated['payment_mode_id'])
                : null;
            $oldPaid = (float) $salesInvoice->paid_amount;
            $oldDue = $salesInvoice->due_amount;
            $newPaid = round((float) $validated['paid_amount'], 2);
            $newDue = round((float) $salesInvoice->total_amount - $newPaid, 2);
            $paidDelta = round($newPaid - $oldPaid, 2);
            $paymentStatus = SalesInvoice::resolvePaymentStatus((float) $salesInvoice->total_amount, $newPaid);

            $salesInvoice->update([
                'payment_status' => $paymentStatus,
                'payment_method' => $newPaid > 0 ? ($paymentMode?->data ?: 'cash') : 'none',
                'payment_mode_id' => $newPaid > 0 ? $paymentMode?->id : null,
                'paid_amount' => $newPaid,
                'updated_by' => $request->user()->id,
            ]);

            if ($salesInvoice->customer_id) {
                $customer = Customer::query()->lockForUpdate()->findOrFail($salesInvoice->customer_id);
                $customer->current_balance = round((float) $customer->current_balance + ($newDue - $oldDue), 2);
                $customer->save();
            }

            if ($paidDelta > 0 && $paymentMode) {
                $cashAccount = $paymentMode->data === 'cash' ? 'cash' : 'bank';

                record_account_transaction([
                    'transaction_date' => $salesInvoice->invoice_date,
                    'reference_type' => 'SalesInvoice',
                    'reference_id' => $salesInvoice->id,
                    'party_type' => $salesInvoice->customer_id ? 'customer' : null,
                    'party_id' => $salesInvoice->customer_id,
                    'entry_type' => 'debit',
                    'account_type' => $cashAccount,
                    'amount' => $paidDelta,
                    'notes' => 'Payment updated for ' . $salesInvoice->reference,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('success', 'Sales payment updated successfully.');
    }

    // Save one return row and give the stock back to the batch.
    public function returnStore(Request $request, SalesInvoice $salesInvoice)
    {
        $request->merge([
            'sales_invoice_id' => $salesInvoice->id,
            'return_date' => $request->input('return_date', now()->toDateString()),
            'refund_status' => $request->input('refund_status', 'paid'),
            'payment_mode_id' => $request->input('payment_mode_id', $salesInvoice->payment_mode_id),
        ]);

        $this->persistSalesReturn($request);

        return back()->with('success', 'Sales return saved successfully.');
    }

    // Pick one active batch in expiry order and keep the stock rule simple.
    private function pickBatchForSale(int $productId, float $quantity): Batch
    {
        $batches = Batch::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('expiry_date')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ((float) $batch->quantity_available >= $quantity) {
                return $batch;
            }
        }

        throw ValidationException::withMessages([
            'items' => 'Not enough stock available for one of the selected products.',
        ]);
    }

    // If user selects a batch manually, keep the same stock rule but respect that chosen batch.
    private function selectedBatchForSale(int $productId, int $batchId, float $quantity): Batch
    {
        $batch = Batch::query()
            ->where('product_id', $productId)
            ->where('id', $batchId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (!$batch) {
            throw ValidationException::withMessages([
                'items' => 'Selected batch does not belong to the chosen product.',
            ]);
        }

        if ((float) $batch->quantity_available < $quantity) {
            throw ValidationException::withMessages([
                'items' => 'Selected batch does not have enough stock.',
            ]);
        }

        return $batch;
    }

    // Keep the sale price easy to read from the catalog record.
    private function resolveSalePrice(Product $product): float
    {
        if (!empty($product->display_price)) {
            return round((float) $product->display_price, 2);
        }

        if (!empty($product->mrp)) {
            return round((float) $product->mrp, 2);
        }

        if (!empty($product->purchase_price)) {
            return round((float) $product->purchase_price, 2);
        }

        return 0.00;
    }

    // Save or update a sales return and keep stock plus accounting in sync.
    private function persistSalesReturn(Request $request, ?SalesReturn $existingReturn = null): SalesReturn
    {
        $validated = $request->validate([
            'sales_invoice_id' => ['required', 'exists:sales_invoices,id'],
            'sales_invoice_item_id' => ['required', 'exists:sales_invoice_items,id'],
            'return_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'refund_status' => ['required', Rule::in(['pending', 'paid'])],
            'payment_mode_id' => [
                'nullable',
                Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'payment_mode')),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'net_unit_price' => ['nullable', 'numeric', 'min:0'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($validated, $request, $existingReturn) {
            return $this->saveSalesReturnValidated($validated, $request, $existingReturn);
        });
    }

    private function saveSalesReturnValidated(array $validated, Request $request, ?SalesReturn $existingReturn = null): SalesReturn
    {
            if ($existingReturn) {
                $existingReturn->load(['invoice.customer', 'invoiceItem.batch']);
                $this->rollbackSalesReturnEffects($existingReturn);
                $this->deleteSalesReturnRecords($existingReturn);
            }

            $invoiceItem = SalesInvoiceItem::query()
                ->with(['invoice.customer', 'product', 'batch'])
                ->findOrFail($validated['sales_invoice_item_id']);
            $salesInvoice = $invoiceItem->invoice;

            if (!empty($validated['sales_invoice_id']) && (int) $validated['sales_invoice_id'] !== (int) $salesInvoice->id) {
                throw ValidationException::withMessages([
                    'sales_invoice_id' => 'Selected return item does not belong to the selected invoice.',
                ]);
            }

            if (!empty($validated['customer_id']) && (int) $validated['customer_id'] !== (int) $salesInvoice->customer_id) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Selected return item does not belong to the selected customer.',
                ]);
            }

            $returnQty = round((float) $validated['quantity'], 2);
            $alreadyReturnedQty = (float) SalesReturn::query()
                ->where('sales_invoice_item_id', $invoiceItem->id)
                ->when($existingReturn, function (Builder $query) use ($existingReturn) {
                    $query->where('id', '!=', $existingReturn->id);
                })
                ->sum('quantity');
            $maxReturnableQty = max(0, round((float) $invoiceItem->quantity - $alreadyReturnedQty, 2));

            if ($returnQty > $maxReturnableQty) {
                throw ValidationException::withMessages([
                    'quantity' => 'Return quantity cannot be more than the remaining returnable quantity.',
                ]);
            }

            $defaultDiscountedUnitRate = (float) $invoiceItem->quantity > 0
                ? round((float) $invoiceItem->subtotal / (float) $invoiceItem->quantity, 2)
                : round((float) $invoiceItem->unit_price, 2);
            $defaultPerUnitDiscount = (float) $invoiceItem->quantity > 0
                ? round((float) $invoiceItem->discount_amount / (float) $invoiceItem->quantity, 4)
                : 0;
            $unitPrice = round((float) ($validated['unit_price'] ?? $invoiceItem->unit_price ?? 0), 2);
            $discountPercent = round((float) ($validated['discount_percent'] ?? $invoiceItem->discount_percent ?? 0), 2);
            $discountAmount = round((float) ($validated['discount_amount'] ?? 0), 2);
            $netUnitPrice = round((float) ($validated['net_unit_price'] ?? $defaultDiscountedUnitRate), 2);

            if (array_key_exists('net_unit_price', $validated) && $validated['net_unit_price'] !== null && $validated['net_unit_price'] !== '') {
                $netUnitPrice = round(max(0, min($unitPrice, (float) $validated['net_unit_price'])), 2);
                $discountAmount = round(max(0, ($unitPrice - $netUnitPrice) * $returnQty), 2);
                $discountPercent = $unitPrice > 0
                    ? round((($unitPrice - $netUnitPrice) / $unitPrice) * 100, 2)
                    : 0;
            } elseif (array_key_exists('discount_amount', $validated) && $validated['discount_amount'] !== null && $validated['discount_amount'] !== '') {
                $discountAmount = round((float) $validated['discount_amount'], 2);
                $perUnitDiscount = $returnQty > 0 ? round($discountAmount / $returnQty, 4) : 0;
                $netUnitPrice = round(max(0, $unitPrice - $perUnitDiscount), 2);
                $discountPercent = $unitPrice > 0
                    ? round((($unitPrice - $netUnitPrice) / $unitPrice) * 100, 2)
                    : 0;
                $discountAmount = round(max(0, ($unitPrice - $netUnitPrice) * $returnQty), 2);
            } elseif (array_key_exists('discount_percent', $validated) && $validated['discount_percent'] !== null && $validated['discount_percent'] !== '') {
                $discountPercent = round(max(0, min(100, (float) $validated['discount_percent'])), 2);
                $netUnitPrice = round(max(0, $unitPrice - (($unitPrice * $discountPercent) / 100)), 2);
                $discountAmount = round(max(0, ($unitPrice - $netUnitPrice) * $returnQty), 2);
            } else {
                $netUnitPrice = $defaultDiscountedUnitRate;
                $discountAmount = round($returnQty * $defaultPerUnitDiscount, 2);
                $discountPercent = round((float) ($invoiceItem->discount_percent ?? 0), 2);
            }

            $refundAmount = isset($validated['refund_amount']) && $validated['refund_amount'] !== null && $validated['refund_amount'] !== ''
                ? round((float) $validated['refund_amount'], 2)
                : round($returnQty * $netUnitPrice, 2);
            $paymentMode = !empty($validated['payment_mode_id'])
                ? DropdownOption::query()->forAlias('payment_mode')->findOrFail($validated['payment_mode_id'])
                : null;

            if ($invoiceItem->batch) {
                $lockedBatch = Batch::query()->lockForUpdate()->findOrFail($invoiceItem->batch_id);
                $lockedBatch->quantity_available = round((float) $lockedBatch->quantity_available + $returnQty, 2);
                $lockedBatch->save();
            }

            if (!$salesInvoice->customer_id && $validated['refund_status'] === 'pending') {
                throw ValidationException::withMessages([
                    'refund_status' => 'Pending refund needs a customer-linked invoice so the credit can be tracked.',
                ]);
            }

            $customer = $salesInvoice->customer_id
                ? Customer::query()->lockForUpdate()->findOrFail($salesInvoice->customer_id)
                : null;
            $settlement = $this->buildSalesReturnSettlement($customer, $refundAmount, (string) $validated['refund_status']);

            if ($settlement['cash_refund_amount'] > 0 && !$paymentMode) {
                throw ValidationException::withMessages([
                    'payment_mode_id' => 'Please choose a payment mode for the cash or bank refund.',
                ]);
            }

            $payload = [
                'return_mode' => ($validated['return_mode'] ?? 'invoice') === 'customer_product' ? 'customer_product' : 'invoice',
                'sales_invoice_id' => $salesInvoice->id,
                'sales_invoice_item_id' => $invoiceItem->id,
                'product_id' => $invoiceItem->product_id,
                'batch_id' => $invoiceItem->batch_id,
                'created_by' => $existingReturn?->created_by ?: $request->user()->id,
                'return_date' => $validated['return_date'],
                'quantity' => $returnQty,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'net_unit_price' => $netUnitPrice,
                'refund_amount' => $refundAmount,
                'refund_status' => $validated['refund_status'],
                'payment_mode_id' => $validated['refund_status'] === 'paid' ? ($paymentMode?->id) : null,
                'receivable_adjusted_amount' => $settlement['receivable_adjusted_amount'],
                'cash_refund_amount' => $settlement['cash_refund_amount'],
                'pending_credit_amount' => $settlement['pending_credit_amount'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];

            if ($existingReturn) {
                $existingReturn->update($payload);
                $salesReturn = $existingReturn->fresh();
            } else {
                $salesReturn = SalesReturn::query()->create($payload);
            }

            record_stock_movement([
                'movement_date' => $validated['return_date'],
                'product_id' => $invoiceItem->product_id,
                'batch_id' => $invoiceItem->batch_id,
                'movement_type' => 'sales_return_in',
                'quantity_in' => (int) $returnQty,
                'source_type' => 'Customer',
                'source_id' => $salesInvoice->customer_id,
                'destination_type' => 'Inventory',
                'reference_type' => 'SalesReturn',
                'reference_id' => $salesReturn->id,
                'notes' => 'Stock returned from sales invoice.',
                'created_by' => $request->user()->id,
            ]);

            if ($customer && $settlement['balance_impact_amount'] > 0) {
                $customer->current_balance = round((float) $customer->current_balance - $settlement['balance_impact_amount'], 2);
                $customer->save();
            }

            record_account_transaction([
                'transaction_date' => $validated['return_date'],
                'reference_type' => 'SalesReturn',
                'reference_id' => $salesReturn->id,
                'party_type' => $salesInvoice->customer_id ? 'customer' : null,
                'party_id' => $salesInvoice->customer_id,
                'entry_type' => 'debit',
                'account_type' => 'income',
                'amount' => $refundAmount,
                'notes' => 'Sales return on ' . $salesInvoice->reference,
                'created_by' => $request->user()->id,
            ]);

            if ($settlement['receivable_adjusted_amount'] > 0) {
                record_account_transaction([
                    'transaction_date' => $validated['return_date'],
                    'reference_type' => 'SalesReturn',
                    'reference_id' => $salesReturn->id,
                    'party_type' => $salesInvoice->customer_id ? 'customer' : null,
                    'party_id' => $salesInvoice->customer_id,
                    'entry_type' => 'credit',
                    'account_type' => 'receivable',
                    'amount' => $settlement['receivable_adjusted_amount'],
                    'notes' => 'Sales return adjusted against customer due for ' . $salesInvoice->reference,
                    'created_by' => $request->user()->id,
                ]);
            }

            if ($settlement['pending_credit_amount'] > 0) {
                record_account_transaction([
                    'transaction_date' => $validated['return_date'],
                    'reference_type' => 'SalesReturn',
                    'reference_id' => $salesReturn->id,
                    'party_type' => $salesInvoice->customer_id ? 'customer' : null,
                    'party_id' => $salesInvoice->customer_id,
                    'entry_type' => 'credit',
                    'account_type' => 'payable',
                    'amount' => $settlement['pending_credit_amount'],
                    'notes' => 'Pending customer refund credit for ' . $salesInvoice->reference,
                    'created_by' => $request->user()->id,
                ]);
            }

            if ($settlement['cash_refund_amount'] > 0) {
                $cashAccount = $paymentMode?->data === 'bank' ? 'bank' : 'cash';

                record_account_transaction([
                    'transaction_date' => $validated['return_date'],
                    'reference_type' => 'SalesReturn',
                    'reference_id' => $salesReturn->id,
                    'party_type' => $salesInvoice->customer_id ? 'customer' : null,
                    'party_id' => $salesInvoice->customer_id,
                    'entry_type' => 'credit',
                    'account_type' => $cashAccount,
                    'amount' => $settlement['cash_refund_amount'],
                    'notes' => 'Refund paid for ' . $salesInvoice->reference,
                    'created_by' => $request->user()->id,
                ]);
            }

            return $salesReturn->load(['invoice.customer', 'invoiceItem.product', 'invoiceItem.batch', 'product', 'batch', 'paymentMode']);
    }

    // Split a return into balance adjustment, pending credit, and actual cash or bank payout.
    private function buildSalesReturnSettlement(?Customer $customer, float $refundAmount, string $refundStatus): array
    {
        $customerBalance = round((float) ($customer?->current_balance ?? 0), 2);
        $receivableAdjustedAmount = round(max(0, min($customerBalance, $refundAmount)), 2);
        $remainingRefund = round(max(0, $refundAmount - $receivableAdjustedAmount), 2);
        $cashRefundAmount = $refundStatus === 'paid' ? $remainingRefund : 0.0;
        $pendingCreditAmount = $refundStatus === 'pending' ? $remainingRefund : 0.0;

        return [
            'receivable_adjusted_amount' => $receivableAdjustedAmount,
            'cash_refund_amount' => round($cashRefundAmount, 2),
            'pending_credit_amount' => round($pendingCreditAmount, 2),
            'balance_impact_amount' => round($receivableAdjustedAmount + $pendingCreditAmount, 2),
        ];
    }

    // Reverse stock and balance impact before an edit or delete.
    private function rollbackSalesReturnEffects(SalesReturn $salesReturn): void
    {
        $salesReturn->loadMissing(['invoice.customer', 'invoiceItem.batch']);

        if ($salesReturn->batch_id) {
            $batch = Batch::query()->lockForUpdate()->find($salesReturn->batch_id);

            if (!$batch) {
                throw ValidationException::withMessages([
                    'sales_return' => 'The batch linked to this sales return no longer exists.',
                ]);
            }

            if ((float) $batch->quantity_available < (float) $salesReturn->quantity) {
                throw ValidationException::withMessages([
                    'sales_return' => 'This sales return cannot be changed because the returned stock has already been used from inventory.',
                ]);
            }

            $batch->quantity_available = round((float) $batch->quantity_available - (float) $salesReturn->quantity, 2);
            $batch->save();
        }

        if ($salesReturn->invoice?->customer_id) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($salesReturn->invoice->customer_id);
            $balanceImpactAmount = round((float) $salesReturn->receivable_adjusted_amount + (float) $salesReturn->pending_credit_amount, 2);
            $customer->current_balance = round((float) $customer->current_balance + $balanceImpactAmount, 2);
            $customer->save();
        }
    }

    // Remove old movement and accounting rows so edited returns do not duplicate history.
    private function deleteSalesReturnRecords(SalesReturn $salesReturn): void
    {
        StockMovement::query()
            ->where('reference_type', 'SalesReturn')
            ->where('reference_id', $salesReturn->id)
            ->delete();

        AccountTransaction::query()
            ->where('reference_type', 'SalesReturn')
            ->where('reference_id', $salesReturn->id)
            ->delete();
    }

    private function applySalesReturnFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['customer_id']), function (Builder $builder) use ($filters) {
                $builder->whereHas('invoice', function (Builder $invoiceQuery) use ($filters) {
                    $invoiceQuery->where('customer_id', $filters['customer_id']);
                });
            })
            ->when(!empty($filters['product_id']), function (Builder $builder) use ($filters) {
                $builder->where('product_id', $filters['product_id']);
            })
            ->when(!empty($filters['refund_status']), function (Builder $builder) use ($filters) {
                $builder->where('refund_status', $filters['refund_status']);
            })
            ->when(!empty($filters['date_from']), function (Builder $builder) use ($filters) {
                $builder->whereDate('return_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $builder) use ($filters) {
                $builder->whereDate('return_date', '<=', $filters['date_to']);
            });
    }

    // Keep invoice loading in one place so show, print and pdf use the same data.
    private function loadInvoiceRelations(SalesInvoice $salesInvoice, bool $includeLineData = true): SalesInvoice
    {
        $relations = [
            'customer',
            'paymentMode',
            'soldBy',
            'creator',
        ];

        if ($includeLineData) {
            $relations = array_merge($relations, [
                'items.product',
                'items.batch',
                'returns.product',
                'returns.batch',
                'returns.invoiceItem',
                'returns.paymentMode',
            ]);
        }

        return $salesInvoice->load($relations);
    }

    // Build the common screen data once because invoice screens reuse the same labels and company details.
    private function invoiceViewData(SalesInvoice $salesInvoice, bool $includeLineData = true): array
    {
        return [
            'invoice' => $this->loadInvoiceRelations($salesInvoice, $includeLineData),
            'company' => $this->invoiceCompanyDetails(),
            'paymentModes' => DropdownOption::query()->forAlias('payment_mode')->active()->orderBy('name')->get(),
            'saleTypes' => DropdownOption::query()->forAlias('sales_type')->active()->orderBy('name')->pluck('name', 'id'),
        ];
    }

    // Prepare the dedicated sales return form with optional preselected invoice and item.
    private function salesReturnFormData(?SalesReturn $salesReturn = null, ?SalesInvoice $prefillInvoice = null, ?Request $request = null): array
    {
        $request = $request ?: request();
        $storedReturnMode = $salesReturn?->return_mode ?: 'invoice';
        $selectedReturnMode = old('return_mode', $request->input('return_mode', $storedReturnMode));
        $selectedReturnMode = $selectedReturnMode === 'customer_product' ? 'customer_product' : 'invoice';
        $selectedInvoiceId = old('sales_invoice_id', $prefillInvoice?->id ?: $request->input('sales_invoice_id'));
        $selectedInvoice = null;

        if ($selectedInvoiceId) {
            $selectedInvoice = $this->loadInvoiceRelations(
                SalesInvoice::query()->findOrFail($selectedInvoiceId)
            );
        }

        $selectedInvoiceOption = $selectedInvoice ? $this->formatSalesReturnInvoiceOption($selectedInvoice) : null;
        $selectedItemId = old('sales_invoice_item_id', $salesReturn?->sales_invoice_item_id ?: $request->input('sales_invoice_item_id'));
        $selectedItemOption = null;
        $salesReturnItemOptions = $selectedInvoice ? $this->buildSalesReturnItemOptions($selectedInvoice, $salesReturn) : [];

        if ($selectedInvoice && $selectedItemId) {
            $selectedItemOption = collect($salesReturnItemOptions)
                ->firstWhere('id', (int) $selectedItemId);
        }

        return [
            'salesReturn' => $salesReturn,
            'selectedInvoice' => $selectedInvoice,
            'selectedInvoiceOption' => $selectedInvoiceOption,
            'selectedItemOption' => $selectedItemOption,
            'selectedReturnMode' => $selectedReturnMode,
            'salesReturnItemOptions' => $salesReturnItemOptions,
            'paymentModes' => DropdownOption::query()->forAlias('payment_mode')->active()->orderBy('name')->get(),
        ];
    }

    // Format one invoice for the select2 invoice picker.
    private function formatSalesReturnInvoiceOption(SalesInvoice $invoice): array
    {
        $customerName = $invoice->customer?->name ?: 'Walk-in Customer';

        return [
            'id' => $invoice->id,
            'text' => $invoice->reference . ' | ' . $customerName . ' | ' . $invoice->invoice_date_show,
            'reference' => $invoice->reference,
            'customer_name' => $customerName,
            'invoice_date' => $invoice->invoice_date_show,
            'total_amount' => round((float) $invoice->total_amount, 2),
            'payment_mode_id' => $invoice->payment_mode_id,
            'payment_mode_name' => $invoice->paymentMode?->name,
        ];
    }

    // Build the invoice item options with remaining quantity and saved discount context.
    private function buildSalesReturnItemOptions(SalesInvoice $invoice, ?SalesReturn $editingReturn = null): array
    {
        $invoice->loadMissing(['customer', 'items.product', 'items.batch']);

        return $invoice->items
            ->map(fn (SalesInvoiceItem $item) => $this->formatSalesReturnItemOption($item, $item->invoice ?: $invoice, $editingReturn))
            ->filter(function (array $row) use ($editingReturn) {
                return $row['remaining_qty'] > 0
                    || ($editingReturn && (int) $editingReturn->sales_invoice_item_id === (int) $row['id']);
            })
            ->values()
            ->all();
    }

    // Let staff find returnable rows by customer/product when the invoice number is unknown.
    private function buildSalesReturnCustomerItemOptions(int $customerId, ?int $productId = null, ?SalesReturn $editingReturn = null): array
    {
        return SalesInvoiceItem::query()
            ->with(['invoice.customer', 'product', 'batch'])
            ->whereHas('invoice', function (Builder $query) use ($customerId) {
                $query->where('customer_id', $customerId)
                    ->where('status', 'confirmed');
            })
            ->when($productId, function (Builder $query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (SalesInvoiceItem $item) => $this->formatSalesReturnItemOption($item, $item->invoice, $editingReturn, true))
            ->filter(function (array $row) use ($editingReturn) {
                return $row['remaining_qty'] > 0
                    || ($editingReturn && (int) $editingReturn->sales_invoice_item_id === (int) $row['id']);
            })
            ->values()
            ->all();
    }

    private function formatSalesReturnItemOption(SalesInvoiceItem $item, SalesInvoice $invoice, ?SalesReturn $editingReturn = null, bool $includeInvoiceInText = false): array
    {
        $alreadyReturnedQty = (float) SalesReturn::query()
            ->where('sales_invoice_item_id', $item->id)
            ->when($editingReturn, function (Builder $query) use ($editingReturn) {
                $query->where('id', '!=', $editingReturn->id);
            })
            ->sum('quantity');
        $remainingQty = max(0, round((float) $item->quantity - $alreadyReturnedQty, 2));
        $netRate = (float) $item->quantity > 0
            ? round((float) $item->subtotal / (float) $item->quantity, 2)
            : round((float) $item->unit_price, 2);
        $perUnitDiscount = (float) $item->quantity > 0
            ? round((float) $item->discount_amount / (float) $item->quantity, 4)
            : 0;
        $isSelectedItem = $editingReturn && (int) $editingReturn->sales_invoice_item_id === (int) $item->id;

        if ($isSelectedItem) {
            $remainingQty = round($remainingQty + (float) $editingReturn->quantity, 2);
        }

        $productName = $item->product?->display_name ?? '-';
        $batchNumber = $item->batch?->batch_number ?: '-';
        $prefix = $includeInvoiceInText ? ($invoice->reference . ' | ') : '';

        return [
            'id' => $item->id,
            'sales_invoice_id' => $invoice->id,
            'text' => $prefix . $productName . ' | Batch ' . $batchNumber . ' | Remaining ' . number_format($remainingQty, 0),
            'product_name' => $productName,
            'batch_number' => $batchNumber,
            'remaining_qty' => $remainingQty,
            'discount_percent' => round((float) $item->discount_percent, 2),
            'unit_price' => round((float) $item->unit_price, 2),
            'net_rate' => $netRate,
            'per_unit_discount' => round($perUnitDiscount, 2),
            'original_pricing_note' => 'Original invoice ' . $invoice->reference . ': ' . number_format((float) $item->discount_percent, 2) . '% | Disc/unit ' . money_value((float) round($perUnitDiscount, 2)) . ' | Net ' . money_value($netRate),
            'invoice_reference' => $invoice->reference,
            'customer_name' => $invoice->customer?->name ?: 'Walk-in Customer',
            'invoice_date' => $invoice->invoice_date_show,
        ];
    }

    // Read basic company details from settings so invoice copy still looks proper with fallback values.
    private function invoiceCompanyDetails(): array
    {
        return [
            'name' => setting('app_name', 'Pharmacy Management System'),
            'email' => setting('company_email', 'info@pharmacy.com'),
            'phone' => setting('company_phone', ''),
            'address' => trim(strip_tags((string) setting('company_address', ''))),
        ];
    }
}
