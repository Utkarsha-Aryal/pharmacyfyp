<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\Batch;
use App\Models\Company;
use App\Models\DropdownOption;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\Unit;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    // Show the purchase order list with summary cards and filters.
    public function index(Request $request)
    {
        return view('purchase-order.index', [
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'filters' => $request->only(['supplier_id', 'status', 'payment_status', 'date_from', 'date_to']),
            'summary' => [
                'pending' => PurchaseOrder::query()->where('status', 'pending')->count(),
                'approved' => PurchaseOrder::query()->where('status', 'approved')->count(),
                'received' => PurchaseOrder::query()->where('status', 'received')->count(),
                'this_month' => PurchaseOrder::query()->whereMonth('order_date', now()->month)->whereYear('order_date', now()->year)->sum('total_amount'),
                'all_time' => PurchaseOrder::query()->sum('total_amount'),
            ],
        ]);
    }

    // Return purchase orders for the server-side table.
    public function list(Request $request)
    {
        $filters = $request->only(['supplier_id', 'status', 'payment_status', 'date_from', 'date_to']);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 15);

        $query = PurchaseOrder::query()
            ->with(['supplier'])
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        $recordsTotal = (clone $query)->count();
        $query = $this->applyIndexFilters($query, $filters);

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reference', 'like', '%' . $keyword . '%')
                    ->orWhere('status', 'like', '%' . $keyword . '%')
                    ->orWhere('payment_status', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%')
                    ->orWhereHas('supplier', function (Builder $supplierQuery) use ($keyword) {
                        $supplierQuery->where('supplier_name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $orders = $query->get();
        $data = [];

        foreach ($orders as $index => $order) {
            $statusClass = match ($order->status) {
                'pending' => 'bg-warning text-dark',
                'approved' => 'bg-info text-dark',
                'received' => 'bg-success',
                default => 'bg-secondary',
            };
            $paymentClass = match ($order->payment_status) {
                'paid' => 'bg-success',
                'partial' => 'bg-info text-dark',
                default => 'bg-danger',
            };

            $data[] = [
                'sno' => $start + $index + 1,
                'reference' => '<span class="fw-semibold">' . e($order->reference) . '</span>',
                'supplier' => '<div class="text-wrap">' . e($order->supplier?->supplier_name ?? '-') . '</div>',
                'date' => e($order->order_date_show),
                'items' => '<span class="badge bg-secondary">' . (int) $order->items_count . '</span>',
                'status' => '<span class="badge ' . $statusClass . '">' . e($order->status_label) . '</span>',
                'payment' => '<span class="badge ' . $paymentClass . '">' . e($order->payment_label) . '</span>',
                'total' => money_value($order->total_amount),
                'action' => $this->orderActionHtml($order),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Open the create order screen with supplier and product helpers.
    public function create()
    {
        return view('purchase-order.create', [
            'reference' => PurchaseOrder::makeReference(),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('unit_name')->get(),
            'formulations' => DropdownOption::query()->forAlias('formulation')->active()->orderBy('name')->get(),
            'supplierTypes' => SupplierType::query()->orderBy('name')->get(),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'Received purchase orders cannot be edited. Delete and recreate only if the received stock is still unused.');
        }

        $purchaseOrder->load(['supplier', 'items.product']);

        return view('purchase-order.create', [
            'order' => $purchaseOrder,
            'orderRows' => $this->purchaseOrderFormRows($purchaseOrder),
            'reference' => $purchaseOrder->reference,
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('unit_name')->get(),
            'formulations' => DropdownOption::query()->forAlias('formulation')->active()->orderBy('name')->get(),
            'supplierTypes' => SupplierType::query()->orderBy('name')->get(),
        ]);
    }

    // Search supplier names for select2 on the purchase order form.
    public function supplierOptions(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $suppliers = Supplier::query()
            ->where('status', 'Y')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($builder) use ($keyword) {
                    $builder->where('supplier_name', 'like', '%' . $keyword . '%')
                        ->orWhere('contact_person', 'like', '%' . $keyword . '%')
                        ->orWhere('phone_number', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('supplier_name')
            ->limit(20)
            ->get()
            ->map(fn ($supplier) => [
                'id' => $supplier->id,
                'text' => $supplier->supplier_name . (!empty($supplier->contact_person) ? ' - ' . $supplier->contact_person : ''),
            ])
            ->values();

        return response()->json(['results' => $suppliers]);
    }

    // Search products for select2 so order entry stays fast even with more records.
    public function productOptions(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $products = Product::query()
            ->with('company')
            ->where('status', 'Y')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($builder) use ($keyword) {
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

    // Save one purchase order with item rows and payment snapshot.
    public function store(Request $request)
    {
        $validated = $this->validatePurchaseOrderPayload($request);

        DB::transaction(function () use ($validated, $request) {
            $totals = $this->calculatePurchaseOrderTotals($validated);

            $order = PurchaseOrder::create([
                'reference' => $validated['reference'],
                'supplier_id' => $validated['supplier_id'],
                'ordered_by' => $request->user()->id,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => 'pending',
                'payment_status' => $totals['payment_status'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $totals['total'],
                'paid_amount' => $totals['paid_amount'],
            ]);

            $this->replacePurchaseOrderItems($order, $validated['items']);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'type' => 'success',
                'message' => 'Purchase order saved successfully.',
                'redirect' => route('admin.purchase-orders.index'),
            ]);
        }

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order saved successfully.');
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->with('error', 'Received purchase orders cannot be edited after stock is created.');
        }

        $validated = $this->validatePurchaseOrderPayload($request, $purchaseOrder);

        DB::transaction(function () use ($purchaseOrder, $validated, $request) {
            $purchaseOrder = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            if ($purchaseOrder->status === 'received') {
                throw ValidationException::withMessages([
                    'order' => 'Received purchase orders cannot be edited after stock is created.',
                ]);
            }

            $totals = $this->calculatePurchaseOrderTotals($validated);
            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'updated_by' => $request->user()->id,
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'payment_status' => $totals['payment_status'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $totals['total'],
                'paid_amount' => $totals['paid_amount'],
            ]);

            $this->replacePurchaseOrderItems($purchaseOrder, $validated['items']);
            $this->syncPurchaseOrderAccounts($purchaseOrder->fresh(), $request->user()->id);
        });

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order updated successfully.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        try {
            DB::transaction(function () use ($purchaseOrder) {
                $purchaseOrder = PurchaseOrder::query()->with('items')->lockForUpdate()->findOrFail($purchaseOrder->id);

                if ($purchaseOrder->status === 'received') {
                    $this->rollbackPurchaseOrderReceipt($purchaseOrder);
                }

                AccountTransaction::query()
                    ->where('reference_type', 'PurchaseOrder')
                    ->where('reference_id', $purchaseOrder->id)
                    ->delete();

                StockMovement::query()
                    ->where('reference_type', 'PurchaseOrder')
                    ->where('reference_id', $purchaseOrder->id)
                    ->delete();

                $purchaseOrder->items()->delete();
                $purchaseOrder->delete();
            });

            return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order deleted successfully.');
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first() ?: 'Could not delete purchase order.');
        } catch (\Throwable $throwable) {
            return back()->with('error', $throwable->getMessage() ?: 'Could not delete purchase order.');
        }
    }

    // Show one purchase order with items and current statuses.
    public function show(PurchaseOrder $purchaseOrder)
    {
        return view('purchase-order.show', [
            'order' => $purchaseOrder->load(['supplier', 'orderedBy']),
        ]);
    }

    // Return item rows for one purchase order detail screen.
    public function itemsList(Request $request, PurchaseOrder $purchaseOrder)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = PurchaseOrderItem::query()
            ->with('product')
            ->where('purchase_order_id', $purchaseOrder->id)
            ->orderBy('id');

        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('batch_number', 'like', '%' . $keyword . '%')
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%');
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
                'product' => '<div class="text-wrap fw-semibold">' . e($item->product?->display_name ?? '-') . '</div>',
                'qty_ordered' => (int) $item->quantity_ordered,
                'qty_received' => '<span class="badge ' . ((int) $item->quantity_received > 0 ? 'bg-success' : 'bg-light text-dark border') . '">' . (int) $item->quantity_received . '</span>',
                'unit_price' => money_value($item->unit_price),
                'batch_number' => '<span class="badge bg-light text-dark border">' . e($item->batch_number ?: '-') . '</span>',
                'expiry_date' => e($item->expiry_date ? Carbon::parse($item->expiry_date)->format('M j, Y') : '-'),
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

    // Approve a pending order before goods receiving starts.
    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return back()->with('error', 'Only pending purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase order approved.');
    }

    // Open the goods receive screen only for approved orders.
    public function receive(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'approved') {
            return back()->with('error', 'Only approved purchase orders can be received.');
        }

        return view('purchase-order.receive', [
            'order' => $purchaseOrder->load(['supplier', 'items.product']),
        ]);
    }

    // Receive goods, create live batches and sync finance in one transaction.
    public function receiveStore(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'approved') {
            return back()->with('error', 'Only approved purchase orders can be received.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity_received' => ['required', 'integer', 'min:1'],
            'items.*.batch_number' => ['required', 'string', 'max:255'],
            'items.*.expiry_date' => ['required', 'date'],
            'items.*.manufacturing_date' => ['nullable', 'date'],
            'items.*.storage_location' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated, $request) {
            $purchaseOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            foreach ($purchaseOrder->items as $index => $item) {
                $receivedRow = $validated['items'][$item->id] ?? null;

                if (!$receivedRow) {
                    throw ValidationException::withMessages([
                        'items' => 'Please fill all received item rows.',
                    ]);
                }

                $receivedQty = (int) $receivedRow['quantity_received'];
                $unitPrice = (float) $item->unit_price;
                $subtotal = round($receivedQty * $unitPrice, 2);

                $item->update([
                    'quantity_received' => $receivedQty,
                    'batch_number' => $receivedRow['batch_number'],
                    'expiry_date' => $receivedRow['expiry_date'],
                    'subtotal' => $subtotal,
                ]);

                $batch = Batch::create([
                    'product_id' => $item->product_id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'purchase_order_item_id' => $item->id,
                    'batch_number' => $receivedRow['batch_number'],
                    'manufacturing_date' => $receivedRow['manufacturing_date'] ?? null,
                    'expiry_date' => $receivedRow['expiry_date'],
                    'quantity_received' => $receivedQty,
                    'quantity_available' => $receivedQty,
                    'purchase_price' => $unitPrice,
                    'storage_location' => $receivedRow['storage_location'] ?? null,
                    'is_active' => true,
                ]);

                record_stock_movement([
                    'movement_date' => $purchaseOrder->order_date,
                    'product_id' => $item->product_id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'purchase_in',
                    'quantity_in' => $receivedQty,
                    'source_type' => 'Supplier',
                    'source_id' => $purchaseOrder->supplier_id,
                    'destination_type' => 'Inventory',
                    'reference_type' => 'PurchaseOrder',
                    'reference_id' => $purchaseOrder->id,
                    'notes' => 'Stock received from purchase order.',
                    'created_by' => $request->user()->id,
                ]);
            }

            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

            $this->syncPurchaseOrderAccounts($purchaseOrder, $request->user()->id);
        });

        send_system_notification_mail(
            subject: 'Purchase Order Received',
            title: 'Goods received into inventory',
            intro: 'One purchase order has been fully received and batches were created.',
            lines: [
                'Reference: ' . $purchaseOrder->reference,
                'Supplier: ' . ($purchaseOrder->supplier?->supplier_name ?? '-'),
                'Received at: ' . now()->format('M j, Y h:i A'),
                'Total amount: ' . money_value($purchaseOrder->total_amount),
            ]
        );

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'Goods received and batches created successfully.');
    }

    // Update payment snapshot so payable balance and reports stay correct.
    public function updatePayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $purchaseOrder->update([
            'payment_status' => $validated['payment_status'],
            'paid_amount' => $validated['paid_amount'] ?? 0,
            'updated_by' => $request->user()->id,
        ]);

        $this->syncPurchaseOrderAccounts($purchaseOrder, $request->user()->id);

        return back()->with('success', 'Payment status updated successfully.');
    }

    private function validatePurchaseOrderPayload(Request $request, ?PurchaseOrder $purchaseOrder = null): array
    {
        $referenceRule = $purchaseOrder
            ? ['required', 'string', 'max:255', Rule::unique('purchase_orders', 'reference')->ignore($purchaseOrder->id)]
            : ['required', 'string', 'max:255', 'unique:purchase_orders,reference'];

        return $request->validate([
            'reference' => $referenceRule,
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function calculatePurchaseOrderTotals(array $validated): array
    {
        $total = 0;

        foreach ($validated['items'] as $item) {
            $total += round(((float) $item['quantity_ordered']) * ((float) $item['unit_price']), 2);
        }

        $paidAmount = round((float) ($validated['paid_amount'] ?? 0), 2);

        if ($paidAmount > $total) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Paid amount cannot be greater than order total.',
            ]);
        }

        $paymentStatus = 'unpaid';
        if ($paidAmount > 0 && $paidAmount < $total) {
            $paymentStatus = 'partial';
        } elseif ($paidAmount >= $total && $total > 0) {
            $paymentStatus = 'paid';
        }

        return [
            'total' => round($total, 2),
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
        ];
    }

    private function replacePurchaseOrderItems(PurchaseOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'unit_price' => $item['unit_price'],
                'subtotal' => round(((float) $item['quantity_ordered']) * ((float) $item['unit_price']), 2),
            ]);
        }
    }

    private function rollbackPurchaseOrderReceipt(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('items');

        foreach ($purchaseOrder->items as $item) {
            $batches = Batch::query()
                ->where('purchase_order_item_id', $item->id)
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if (StockMovement::query()
                    ->where('batch_id', $batch->id)
                    ->where(function (Builder $query) use ($purchaseOrder) {
                        $query->where('reference_type', '!=', 'PurchaseOrder')
                            ->orWhere('reference_id', '!=', $purchaseOrder->id)
                            ->orWhereNull('reference_type');
                    })
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'purchase_order' => 'This received order cannot be deleted because this stock already has later inventory history.',
                    ]);
                }

                if ((int) $batch->quantity_available < (int) $batch->quantity_received) {
                    throw ValidationException::withMessages([
                        'purchase_order' => 'This received order cannot be deleted because some stock has already moved out.',
                    ]);
                }

                $batch->delete();
            }
        }
    }

    private function purchaseOrderFormRows(PurchaseOrder $purchaseOrder): array
    {
        return $purchaseOrder->items->map(fn (PurchaseOrderItem $item) => [
            'product_id' => $item->product_id,
            'quantity_ordered' => $item->quantity_ordered,
            'unit_price' => $item->unit_price,
        ])->values()->all();
    }

    private function orderActionHtml(PurchaseOrder $order): string
    {
        $action = '<div class="table-action-group">';
        $action .= '<a href="' . route('admin.purchase-orders.show', $order) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="View Order" aria-label="View Order"><i class="fa-solid fa-eye"></i></a>';

        if ($order->status !== 'received') {
            $action .= '<a href="' . route('admin.purchase-orders.edit', $order) . '" class="btn btn-sm btn-outline-warning table-action-btn" title="Edit Order" aria-label="Edit Order"><i class="fa-solid fa-pen-to-square"></i></a>';
        }

        $action .= '<form action="' . route('admin.purchase-orders.delete', $order) . '" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete purchase order?" data-confirm-text="Received stock will be removed only if it is still unused." data-confirm-button="Yes, delete it">';
        $action .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
        $action .= '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Order" aria-label="Delete Order"><i class="fa-solid fa-trash"></i></button>';
        $action .= '</form></div>';

        return $action;
    }

    // Keep the supplier bill and payment entries in one place so finance pages stay accurate.
    private function syncPurchaseOrderAccounts(PurchaseOrder $purchaseOrder, int $userId): void
    {
        AccountTransaction::query()
            ->where('reference_type', 'PurchaseOrder')
            ->where('reference_id', $purchaseOrder->id)
            ->delete();

        record_account_transaction([
            'transaction_date' => $purchaseOrder->order_date,
            'reference_type' => 'PurchaseOrder',
            'reference_id' => $purchaseOrder->id,
            'party_type' => 'supplier',
            'party_id' => $purchaseOrder->supplier_id,
            'entry_type' => 'debit',
            'account_type' => 'inventory',
            'amount' => $purchaseOrder->total_amount,
            'notes' => 'Inventory received for ' . $purchaseOrder->reference,
            'created_by' => $userId,
        ]);

        record_account_transaction([
            'transaction_date' => $purchaseOrder->order_date,
            'reference_type' => 'PurchaseOrder',
            'reference_id' => $purchaseOrder->id,
            'party_type' => 'supplier',
            'party_id' => $purchaseOrder->supplier_id,
            'entry_type' => 'credit',
            'account_type' => 'payable',
            'amount' => $purchaseOrder->total_amount,
            'notes' => 'Purchase bill for ' . $purchaseOrder->reference,
            'created_by' => $userId,
        ]);

        if ((float) $purchaseOrder->paid_amount > 0) {
            record_account_transaction([
                'transaction_date' => $purchaseOrder->order_date,
                'reference_type' => 'PurchaseOrder',
                'reference_id' => $purchaseOrder->id,
                'party_type' => 'supplier',
                'party_id' => $purchaseOrder->supplier_id,
                'entry_type' => 'debit',
                'account_type' => 'payable',
                'amount' => $purchaseOrder->paid_amount,
                'notes' => 'Payment adjusted for ' . $purchaseOrder->reference,
                'created_by' => $userId,
            ]);

            record_account_transaction([
                'transaction_date' => $purchaseOrder->order_date,
                'reference_type' => 'PurchaseOrder',
                'reference_id' => $purchaseOrder->id,
                'party_type' => 'supplier',
                'party_id' => $purchaseOrder->supplier_id,
                'entry_type' => 'credit',
                'account_type' => 'cash',
                'amount' => $purchaseOrder->paid_amount,
                'notes' => 'Cash payment for ' . $purchaseOrder->reference,
                'created_by' => $userId,
            ]);
        }
    }

    private function applyIndexFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['supplier_id']), function (Builder $builder) use ($filters) {
                $builder->where('supplier_id', $filters['supplier_id']);
            })
            ->when(!empty($filters['status']), function (Builder $builder) use ($filters) {
                $builder->where('status', $filters['status']);
            })
            ->when(!empty($filters['payment_status']), function (Builder $builder) use ($filters) {
                $builder->where('payment_status', $filters['payment_status']);
            })
            ->when(!empty($filters['date_from']), function (Builder $builder) use ($filters) {
                $builder->whereDate('order_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $builder) use ($filters) {
                $builder->whereDate('order_date', '<=', $filters['date_to']);
            });
    }
}
