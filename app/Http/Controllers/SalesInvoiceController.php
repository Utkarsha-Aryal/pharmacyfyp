<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    // Show the sales invoice list page with quick summary cards.
    public function index(Request $request)
    {
        $summaryQuery = SalesInvoice::query();

        return view('sales.index', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['customer_id', 'sale_type', 'status', 'payment_status', 'date_from', 'date_to']),
            'summary' => [
                'this_month' => (clone $summaryQuery)
                    ->whereMonth('invoice_date', now()->month)
                    ->whereYear('invoice_date', now()->year)
                    ->sum('total_amount'),
                'all_time' => (clone $summaryQuery)->sum('total_amount'),
                'receivable' => (clone $summaryQuery)->get()->sum(fn (SalesInvoice $invoice) => $invoice->due_amount),
                'paid' => (clone $summaryQuery)->sum('paid_amount'),
                'pending' => (clone $summaryQuery)->where('status', 'draft')->count(),
                'credit' => (clone $summaryQuery)->where('sale_type', 'credit')->count(),
            ],
        ]);
    }

    // Return invoice rows for the server-side table.
    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = max((int) $request->input('length', 10), 1);

        $query = SalesInvoice::query()
            ->with(['customer', 'paymentMode'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        $recordsTotal = (clone $query)->count();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sale_type')) {
            $query->where('sale_type', $request->sale_type);
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
                ? 'report-badge-danger'
                : ($invoice->status === 'draft' ? 'report-badge-warning' : 'report-badge-success');
            $paymentClass = $invoice->payment_status === 'paid'
                ? 'report-badge-success'
                : ($invoice->payment_status === 'partial' ? 'report-badge-warning' : 'report-badge-danger');

            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.sales.show', $invoice) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="View Invoice" aria-label="View Invoice"><i class="fa-solid fa-eye"></i></a>';
            $action .= '<a href="' . route('admin.sales-invoices.print', $invoice) . '" target="_blank" class="btn btn-sm btn-outline-dark table-action-btn" title="Print / PDF" aria-label="Print / PDF"><i class="fa-solid fa-print"></i></a>';
            $action .= '<a href="' . route('admin.sales.show', $invoice) . '#paymentModal" class="btn btn-sm btn-outline-success table-action-btn" title="Payment" aria-label="Payment"><i class="fa-solid fa-wallet"></i></a>';
            $action .= '<a href="' . route('admin.sales.show', $invoice) . '#returnModal" class="btn btn-sm btn-outline-danger table-action-btn" title="Return" aria-label="Return"><i class="fa-solid fa-rotate-left"></i></a>';
            $action .= '</div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'reference' => e($invoice->reference),
                'customer' => e($invoice->customer?->name ?: '-'),
                'date' => e($invoice->invoice_date_show),
                'sale_type' => '<span class="report-badge report-badge-info">' . e($invoice->sale_type_label) . '</span>',
                'status' => '<span class="report-badge ' . $statusClass . '">' . e($invoice->status_label) . '</span>',
                'payment' => '<span class="report-badge ' . $paymentClass . '">' . e($invoice->payment_label) . '</span>',
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
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'paymentModes' => PaymentMode::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('unit_name')->get(),
            'saleTypes' => [
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
                'credit' => 'Credit',
            ],
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
            ->with('category')
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
                'text' => $product->display_name . ($product->category?->name ? ' - ' . $product->category->name : ''),
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
            ->with(['category', 'batches' => function ($query) {
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
            'cc_rate' => round((float) ($product->cc_rate ?? 0), 2),
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
                Rule::requiredIf(in_array($request->input('sale_type'), ['wholesale', 'credit'], true)),
            ],
            'invoice_date' => ['required', 'date'],
            'sale_type' => ['required', Rule::in(['retail', 'wholesale', 'credit'])],
            'payment_mode_id' => ['required', 'exists:payment_modes,id'],
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
                $paymentMode = PaymentMode::query()->findOrFail($validated['payment_mode_id']);

                $invoice = SalesInvoice::create([
                    'reference' => next_sales_reference(),
                    'customer_id' => $validated['customer_id'] ?? null,
                    'sold_by' => $request->user()->id,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                    'invoice_date' => $validated['invoice_date'],
                    'sale_type' => $validated['sale_type'],
                    'status' => 'confirmed',
                    'payment_status' => 'unpaid',
                    'payment_method' => $paymentMode->name,
                    'payment_mode_id' => $paymentMode->id,
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'total_discount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => (float) ($validated['paid_amount'] ?? 0),
                    'notes' => $validated['notes'] ?? null,
                    'confirmed_at' => now(),
                ]);

                foreach ($validated['items'] as $row) {
                    $product = Product::query()->findOrFail($row['product_id']);
                    $quantity = (float) $row['quantity'];
                    $freeQuantity = (float) ($row['free_qty'] ?? 0);
                    $unitPrice = (float) $row['unit_price'];
                    $mrp = (float) ($row['mrp'] ?? 0);
                    $ccRate = (float) ($row['cc_rate'] ?? $product->cc_rate ?? 0);
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

                    $subtotal += $lineBase;
                    $discountAmount += $lineDiscount;
                    $invoiceTotal += $lineTotal;
                }

                $paidAmount = (float) ($validated['paid_amount'] ?? 0);
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

                $cashAccount = $paymentMode->type === 'bank' ? 'bank' : 'cash';

                if ($paidAmount > 0) {
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
        return view('sales.show', $this->invoiceViewData($salesInvoice));
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
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_mode_id' => ['required', 'exists:payment_modes,id'],
        ]);

        DB::transaction(function () use ($salesInvoice, $validated, $request) {
            $salesInvoice->loadMissing('customer');
            $paymentMode = PaymentMode::query()->findOrFail($validated['payment_mode_id']);
            $oldPaid = (float) $salesInvoice->paid_amount;
            $oldDue = $salesInvoice->due_amount;
            $newPaid = round((float) $validated['paid_amount'], 2);
            $newDue = round((float) $salesInvoice->total_amount - $newPaid, 2);
            $paidDelta = round($newPaid - $oldPaid, 2);

            $salesInvoice->update([
                'payment_status' => $validated['payment_status'],
                'payment_method' => $paymentMode->name,
                'payment_mode_id' => $paymentMode->id,
                'paid_amount' => $newPaid,
                'updated_by' => $request->user()->id,
            ]);

            if ($salesInvoice->customer_id) {
                $customer = Customer::query()->lockForUpdate()->findOrFail($salesInvoice->customer_id);
                $customer->current_balance = round((float) $customer->current_balance + ($newDue - $oldDue), 2);
                $customer->save();
            }

            if ($paidDelta > 0) {
                $cashAccount = $paymentMode->type === 'bank' ? 'bank' : 'cash';

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
        $validated = $request->validate([
            'sales_invoice_item_id' => ['required', 'exists:sales_invoice_items,id'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($salesInvoice, $validated, $request) {
            $invoiceItem = SalesInvoiceItem::query()
                ->with(['product', 'batch'])
                ->where('sales_invoice_id', $salesInvoice->id)
                ->findOrFail($validated['sales_invoice_item_id']);

            $returnQty = (float) $validated['quantity'];
            if ($returnQty > (float) $invoiceItem->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Return quantity cannot be more than sold quantity.',
                ]);
            }

            $refundAmount = isset($validated['refund_amount'])
                ? round((float) $validated['refund_amount'], 2)
                : round($returnQty * (float) $invoiceItem->unit_price, 2);

            if ($invoiceItem->batch) {
                $invoiceItem->batch->quantity_available = round((float) $invoiceItem->batch->quantity_available + $returnQty, 2);
                $invoiceItem->batch->save();
            }

            SalesReturn::create([
                'sales_invoice_id' => $salesInvoice->id,
                'sales_invoice_item_id' => $invoiceItem->id,
                'product_id' => $invoiceItem->product_id,
                'batch_id' => $invoiceItem->batch_id,
                'created_by' => $request->user()->id,
                'return_date' => now()->toDateString(),
                'quantity' => $returnQty,
                'refund_amount' => $refundAmount,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($salesInvoice->customer_id) {
                $customer = Customer::query()->lockForUpdate()->findOrFail($salesInvoice->customer_id);
                $customer->current_balance = max(0, round((float) $customer->current_balance - $refundAmount, 2));
                $customer->save();
            }

            record_account_transaction([
                'transaction_date' => now()->toDateString(),
                'reference_type' => 'SalesReturn',
                'reference_id' => $salesInvoice->id,
                'party_type' => $salesInvoice->customer_id ? 'customer' : null,
                'party_id' => $salesInvoice->customer_id,
                'entry_type' => 'debit',
                'account_type' => 'income',
                'amount' => $refundAmount,
                'notes' => 'Sales return on ' . $salesInvoice->reference,
                'created_by' => $request->user()->id,
            ]);

            record_account_transaction([
                'transaction_date' => now()->toDateString(),
                'reference_type' => 'SalesReturn',
                'reference_id' => $salesInvoice->id,
                'party_type' => $salesInvoice->customer_id ? 'customer' : null,
                'party_id' => $salesInvoice->customer_id,
                'entry_type' => 'credit',
                'account_type' => 'cash',
                'amount' => $refundAmount,
                'notes' => 'Refund paid for ' . $salesInvoice->reference,
                'created_by' => $request->user()->id,
            ]);
        });

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

    // Keep invoice loading in one place so show, print and pdf use the same data.
    private function loadInvoiceRelations(SalesInvoice $salesInvoice): SalesInvoice
    {
        return $salesInvoice->load([
            'customer',
            'paymentMode',
            'soldBy',
            'creator',
            'items.product',
            'items.batch',
            'returns.product',
            'returns.batch',
        ]);
    }

    // Build the common screen data once because invoice screens reuse the same labels and company details.
    private function invoiceViewData(SalesInvoice $salesInvoice): array
    {
        return [
            'invoice' => $this->loadInvoiceRelations($salesInvoice),
            'company' => $this->invoiceCompanyDetails(),
            'paymentModes' => PaymentMode::query()->where('is_active', true)->orderBy('name')->get(),
            'saleTypes' => [
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
                'credit' => 'Credit',
            ],
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
