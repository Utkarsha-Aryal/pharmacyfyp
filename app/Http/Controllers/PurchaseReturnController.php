<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\SupplierType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    // List purchase returns for admin and superadmin users.
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $filters = $request->only(['supplier_id', 'return_mode', 'date_from', 'date_to']);
        $summaryQuery = $this->applyIndexFilters(
            PurchaseReturn::query()->withCount('items'),
            $filters
        );

        return view('purchase-return.index', [
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'filters' => $filters,
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'manual' => (clone $summaryQuery)->whereNull('purchase_id')->count(),
                'items' => (int) (clone $summaryQuery)->get()->sum('items_count'),
                'this_month' => (clone $summaryQuery)
                    ->whereMonth('return_date', now()->month)
                    ->whereYear('return_date', now()->year)
                    ->count(),
            ],
        ]);
    }

    // Return purchase return rows for the server-side table.
    public function list(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $filters = $request->only(['supplier_id', 'return_mode', 'date_from', 'date_to']);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 15);

        $query = PurchaseReturn::query()
            ->with(['supplier', 'purchase.reference', 'returnedBy'])
            ->withCount('items')
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        $recordsTotal = (clone $query)->count();
        $query = $this->applyIndexFilters($query, $filters);

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('notes', 'like', '%' . $keyword . '%')
                    ->orWhereHas('supplier', function (Builder $supplierQuery) use ($keyword) {
                        $supplierQuery->where('supplier_name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('purchase.reference', function (Builder $referenceQuery) use ($keyword) {
                        $referenceQuery->where('reference_no', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('returnedBy', function (Builder $userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', '%' . $keyword . '%');
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
            $modeLabel = $return->purchase_id ? 'By Purchase Bill' : 'By Product & Batch';
            $modeClass = $return->purchase_id ? 'bg-primary' : 'bg-warning text-dark';
            $billLabel = $return->purchase?->reference?->reference_no ?: ($return->purchase_id ? ('PUR-' . $return->purchase_id) : 'Manual entry');
            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.purchase-returns.show', $return) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="View"><i class="fa-solid fa-eye"></i></a>';
            $action .= '<a href="' . route('admin.purchase-returns.edit', $return) . '" class="btn btn-sm btn-outline-warning table-action-btn" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>';
            $action .= '<a href="' . route('admin.purchase-returns.print', $return) . '" target="_blank" class="btn btn-sm btn-outline-dark table-action-btn" title="Print / PDF"><i class="fa-solid fa-print"></i></a>';
            $action .= '<form action="' . route('admin.purchase-returns.delete', $return) . '" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete purchase return?" data-confirm-text="This will restore the stock back to inventory." data-confirm-button="Yes, delete it">';
            $action .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            $action .= '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete"><i class="fa-solid fa-trash"></i></button>';
            $action .= '</form></div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'date' => '<div class="fw-semibold">' . e($return->return_date_show) . '</div>',
                'supplier' => '<div class="fw-semibold text-wrap">' . e($return->supplier?->supplier_name ?? '-') . '</div><div class="small text-muted text-wrap">' . e(Str::limit((string) ($return->notes ?: 'No notes'), 60)) . '</div>',
                'mode' => '<span class="badge ' . $modeClass . '">' . e($modeLabel) . '</span>',
                'purchase_bill' => '<span class="badge bg-light text-dark border">' . e($billLabel) . '</span>',
                'items' => '<span class="badge bg-secondary">' . (int) $return->items_count . ' item(s)</span>',
                'created_by' => e($return->returnedBy?->name ?? '-'),
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

    // Open the return form with supplier selection first.
    public function create()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        return view('purchase-return.create', [
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'supplierTypes' => SupplierType::query()->orderBy('name')->get(),
        ]);
    }

    // Open the edit screen with the existing supplier and return rows already filled.
    public function edit(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $purchaseReturn->load(['supplier', 'purchase.reference', 'items.purchaseItem.returns', 'items.batch', 'items.product']);

        return view('purchase-return.edit', [
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'supplierTypes' => SupplierType::query()->orderBy('name')->get(),
            'purchaseReturn' => $purchaseReturn,
            'itemsRows' => $this->buildEditableRows($purchaseReturn),
            'selectedManualProductId' => !$purchaseReturn->purchase_id && $purchaseReturn->items->pluck('product_id')->unique()->count() === 1
                ? (int) $purchaseReturn->items->pluck('product_id')->unique()->first()
                : null,
        ]);
    }

    // Return supplier purchase bill options for the dependent dropdown.
    public function getPurchases(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
        ]);

        $purchases = Purchase::query()
            ->with('reference')
            ->where('supplier_id', $validated['supplier_id'])
            ->where('status', 'Y')
            ->latest('purchase_date')
            ->get()
            ->map(function (Purchase $purchase) {
                return [
                    'id' => $purchase->id,
                    'text' => ($purchase->reference?->reference_no ?: ('PUR-' . $purchase->id)) . ' | ' . $purchase->purchase_date_show . ' | ' . money_value($purchase->grand_total),
                ];
            })
            ->values();

        return response()->json($purchases);
    }

    // Return purchase line items with current returnable quantity for the selected bill.
    public function getItems(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $validated = $request->validate([
            'purchase_id' => ['required', 'exists:purchases,id'],
        ]);

        $purchase = Purchase::query()->findOrFail($validated['purchase_id']);

        $items = PurchaseItem::query()
            ->with(['product', 'batch', 'returns'])
            ->where('purchase_id', $purchase->id)
            ->get()
            ->map(function (PurchaseItem $item) use ($purchase) {
                $returnedQty = (int) $item->returns->sum('return_qty');
                $originalQty = (int) $item->quantity + (int) $item->free_qty;
                $maxReturnable = max(0, $originalQty - $returnedQty);
                $batchOptions = $this->buildReturnBatchOptions($purchase, $item);
                $selectedBatchId = $this->selectedReturnBatchId($item->batch_id, $batchOptions);
                if (!$selectedBatchId && count($batchOptions) === 1) {
                    $selectedBatchId = (int) $batchOptions[0]['id'];
                }
                $batchBadge = $this->selectedReturnBatchBadge($selectedBatchId, $batchOptions);

                return [
                    'purchase_item_id' => $item->id,
                    'batch_id' => $item->batch_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->display_name ?? '-',
                    'batch_no' => $item->batch?->batch_number ?: ($item->batch_no ?: '-'),
                    'original_qty' => $originalQty,
                    'already_returned' => $returnedQty,
                    'max_returnable' => $maxReturnable,
                    'rate' => round((float) $item->rate, 2),
                    'discount_percent' => round((float) $item->discount_percent, 2),
                    'discount_amount' => 0,
                    'net_rate' => (float) $item->quantity > 0
                        ? round((float) $item->amount / (float) $item->quantity, 2)
                        : round((float) $item->rate, 2),
                    'return_amount' => 0,
                    'batch_options' => $batchOptions,
                    'selected_batch_id' => $selectedBatchId,
                    'batch_badge_class' => $batchBadge['class'],
                    'batch_badge_label' => $batchBadge['label'],
                ];
            })
            ->values();

        return response()->json($items);
    }

    // Return supplier batch rows so returns can be entered even when purchase bill is unknown.
    public function getSupplierBatches(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'exists:products,id'],
        ]);

        $items = Batch::query()
            ->with('product')
            ->where('supplier_id', $validated['supplier_id'])
            ->when(!empty($validated['product_id']), function ($builder) use ($validated) {
                $builder->where('product_id', $validated['product_id']);
            })
            ->where('is_active', true)
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('batch_number')
            ->get()
            ->map(function (Batch $batch) {
                $daysRemaining = (int) $batch->days_remaining;
                $state = $daysRemaining < 0 ? 'expired' : ($daysRemaining <= 30 ? 'warning' : 'valid');
                $badgeClass = $state === 'expired' ? 'bg-danger' : ($state === 'warning' ? 'bg-warning text-dark' : 'bg-success');
                $badgeLabel = $state === 'expired' ? 'Expired batch' : ($state === 'warning' ? 'Expiring soon' : 'Valid batch');

                return [
                    'purchase_item_id' => null,
                    'batch_id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'product_name' => $batch->product?->display_name ?? '-',
                    'batch_no' => $batch->batch_number ?: '-',
                    'original_qty' => (int) $batch->quantity_received,
                    'already_returned' => 0,
                    'max_returnable' => (int) $batch->quantity_available,
                    'rate' => round((float) $batch->purchase_price, 2),
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'net_rate' => round((float) $batch->purchase_price, 2),
                    'return_amount' => 0,
                    'batch_options' => [[
                        'id' => $batch->id,
                        'text' => trim(($batch->batch_number ?: '-') . ' | Exp: ' . ($batch->expiry_show ?: '-') . ' | Qty: ' . (int) $batch->quantity_available),
                        'badge_class' => $badgeClass,
                        'badge_label' => $badgeLabel,
                        'disabled' => false,
                        'quantity_available' => (int) $batch->quantity_available,
                    ]],
                    'selected_batch_id' => (int) $batch->id,
                    'batch_badge_class' => $badgeClass,
                    'batch_badge_label' => $badgeLabel,
                ];
            })
            ->values();

        return response()->json($items);
    }

    // Save the return and reduce stock from the chosen batch.
    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        return $this->persistReturn($request);
    }

    // Update an existing return and rebuild the stock movements from the edited lines.
    public function update(Request $request, PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        return $this->persistReturn($request, $purchaseReturn);
    }

    // Save or update a purchase return inside one transaction so stock rollback stays safe.
    private function persistReturn(Request $request, ?PurchaseReturn $existingReturn = null)
    {
        $validated = $request->validate([
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['nullable', 'exists:purchase_items,id'],
            'items.*.batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.return_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.net_rate' => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Please add at least one return row.',
            'items.min' => 'Please add at least one return row.',
            'items.*.purchase_item_id.exists' => 'Please reload the purchase bill and try again.',
            'items.*.batch_id.integer' => 'Please choose a batch from the dropdown for each return row.',
            'items.*.batch_id.min' => 'Please choose a batch from the dropdown for each return row.',
            'items.*.product_id.exists' => 'Please select a valid product for each return row.',
            'items.*.return_qty.min' => 'Return quantity cannot be less than zero.',
        ]);

        $rows = collect($validated['items'])
            ->filter(function (array $row) {
                return (int) ($row['return_qty'] ?? 0) > 0;
            })
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Please enter return quantity for at least one line item.',
            ]);
        }

        foreach ($rows as $row) {
            if (empty($row['batch_id']) || empty($row['product_id'])) {
                throw ValidationException::withMessages([
                    'items' => 'Every return row needs a product and batch selected from the dropdown.',
                ]);
            }
        }

        $purchaseReturn = DB::transaction(function () use ($validated, $request, $rows, $existingReturn) {
            $purchase = !empty($validated['purchase_id'])
                ? Purchase::query()->findOrFail($validated['purchase_id'])
                : null;

            if ($purchase && (int) $purchase->supplier_id !== (int) $validated['supplier_id']) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Selected purchase bill does not belong to the chosen supplier.',
                ]);
            }

            if ($existingReturn) {
                $existingReturn->load(['purchase.reference', 'items.purchaseItem.returns', 'items.batch']);
                $this->restoreReturnStock($existingReturn);
                PurchaseReturnItem::query()->where('purchase_return_id', $existingReturn->id)->delete();
                $existingReturn->update([
                    'purchase_id' => $validated['purchase_id'] ?? null,
                    'supplier_id' => $validated['supplier_id'],
                    'return_date' => $validated['return_date'],
                    'notes' => $validated['notes'] ?? null,
                    'returned_by' => $request->user()->id,
                ]);
                $purchaseReturn = $existingReturn;
            } else {
                $purchaseReturn = PurchaseReturn::query()->create([
                    'purchase_id' => $validated['purchase_id'] ?? null,
                    'supplier_id' => $validated['supplier_id'],
                    'return_date' => $validated['return_date'],
                    'notes' => $validated['notes'] ?? null,
                    'returned_by' => $request->user()->id,
                ]);
            }

            foreach ($rows as $row) {
                $batch = Batch::query()->lockForUpdate()->findOrFail($row['batch_id']);
                $returnQty = (int) $row['return_qty'];
                $purchaseItem = null;
                $rate = round((float) $batch->purchase_price, 2);
                $discountPercent = round((float) ($row['discount_percent'] ?? 0), 2);
                $discountAmount = round((float) ($row['discount_amount'] ?? 0), 2);
                $netRate = round((float) ($row['net_rate'] ?? $rate), 2);

                if ((int) $batch->supplier_id !== (int) $validated['supplier_id']) {
                    throw ValidationException::withMessages([
                        'items' => 'Selected batch does not belong to the chosen supplier.',
                    ]);
                }

                if ((int) $batch->product_id !== (int) $row['product_id']) {
                    throw ValidationException::withMessages([
                        'items' => 'Selected batch does not belong to the selected product row.',
                    ]);
                }

                if (!empty($validated['purchase_id']) && !empty($row['purchase_item_id'])) {
                    $purchaseItem = PurchaseItem::query()
                        ->with(['returns', 'batch', 'product'])
                        ->where('purchase_id', $validated['purchase_id'])
                        ->findOrFail($row['purchase_item_id']);

                    if ((int) $purchaseItem->product_id !== (int) $row['product_id']) {
                        throw ValidationException::withMessages([
                            'items' => 'Selected purchase row does not match the selected product.',
                        ]);
                    }

                    $alreadyReturned = (int) $purchaseItem->returns->sum('return_qty');
                    $maxReturnable = max(0, ((int) $purchaseItem->quantity + (int) $purchaseItem->free_qty) - $alreadyReturned);

                    if ($returnQty > $maxReturnable) {
                        throw ValidationException::withMessages([
                            'items' => 'Return quantity cannot be more than the remaining returnable quantity.',
                        ]);
                    }

                    $rate = (float) $purchaseItem->rate;
                    $discountPercent = round((float) ($row['discount_percent'] ?? $purchaseItem->discount_percent ?? 0), 2);
                }

                if ((int) $batch->quantity_available < $returnQty) {
                    throw ValidationException::withMessages([
                        'items' => 'Selected batch does not have enough stock available for this return.',
                    ]);
                }

                if ($returnQty <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Return quantity must be greater than zero.',
                    ]);
                }

                if (array_key_exists('net_rate', $row) && $row['net_rate'] !== null && $row['net_rate'] !== '') {
                    $netRate = round((float) $row['net_rate'], 2);
                    $netRate = max(0, min($rate, $netRate));
                    $discountAmount = round(max(0, ($rate - $netRate) * $returnQty), 2);
                    $discountPercent = $rate > 0
                        ? round((($rate - $netRate) / $rate) * 100, 2)
                        : 0;
                } elseif (array_key_exists('discount_amount', $row) && $row['discount_amount'] !== null && $row['discount_amount'] !== '') {
                    $discountAmount = round((float) $row['discount_amount'], 2);
                    $perUnitDiscount = $returnQty > 0 ? round($discountAmount / $returnQty, 4) : 0;
                    $netRate = round(max(0, $rate - $perUnitDiscount), 2);
                    $discountPercent = $rate > 0
                        ? round((($rate - $netRate) / $rate) * 100, 2)
                        : 0;
                    $discountAmount = round(max(0, ($rate - $netRate) * $returnQty), 2);
                } else {
                    $discountPercent = round(max(0, min(100, $discountPercent)), 2);
                    $netRate = round(max(0, $rate - (($rate * $discountPercent) / 100)), 2);
                    $discountAmount = round(max(0, ($rate - $netRate) * $returnQty), 2);
                }

                $returnAmount = round($returnQty * $netRate, 2);

                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $purchaseItem?->id,
                    'batch_id' => $batch->id,
                    'product_id' => (int) $row['product_id'],
                    'return_qty' => $returnQty,
                    'rate' => $rate,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'net_rate' => $netRate,
                    'return_amount' => $returnAmount,
                ]);

                $batch->quantity_available = max(0, (int) $batch->quantity_available - $returnQty);
                $batch->save();

                if ($purchase && $purchaseItem && !empty($purchase->reference_id) && !empty($purchaseItem->batch_no)) {
                    ProductBatch::query()
                        ->where('reference_id', $purchase->reference_id)
                        ->where('product_id', $purchaseItem->product_id)
                        ->where('batch_no', $purchaseItem->batch_no)
                        ->decrement('quantity', $returnQty);
                }

                record_stock_movement([
                    'movement_date' => $validated['return_date'],
                    'product_id' => (int) $row['product_id'],
                    'batch_id' => $batch->id,
                    'movement_type' => 'purchase_return_out',
                    'quantity_out' => $returnQty,
                    'source_type' => 'Inventory',
                    'destination_type' => 'Supplier',
                    'destination_id' => $validated['supplier_id'],
                    'reference_type' => 'PurchaseReturn',
                    'reference_id' => $purchaseReturn->id,
                    'notes' => 'Purchase return stock sent to supplier.',
                    'created_by' => $request->user()->id,
                ]);
            }

            return $purchaseReturn->load(['supplier', 'purchase.reference', 'items.product', 'items.batch']);
        });

        return redirect()->route('admin.purchase-returns.show', $purchaseReturn)->with('success', 'Purchase return saved successfully.');
    }

    // Put the returned quantities back before an update so we can recalculate the new return cleanly.
    private function restoreReturnStock(PurchaseReturn $purchaseReturn): void
    {
        foreach ($purchaseReturn->items as $item) {
            $batch = Batch::query()->lockForUpdate()->find($item->batch_id);

            if ($batch) {
                $batch->quantity_available = (int) $batch->quantity_available + (int) $item->return_qty;
                $batch->save();

                record_stock_movement([
                    'movement_date' => now()->toDateString(),
                    'product_id' => $item->product_id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'adjustment_in',
                    'quantity_in' => (int) $item->return_qty,
                    'source_type' => 'Supplier',
                    'destination_type' => 'Inventory',
                    'reference_type' => 'PurchaseReturn',
                    'reference_id' => $purchaseReturn->id,
                    'notes' => 'Purchase return rollback restored stock.',
                ]);
            }

            if (!empty($purchaseReturn->purchase?->reference_id) && !empty($item->purchaseItem?->batch_no)) {
                ProductBatch::query()
                    ->where('reference_id', $purchaseReturn->purchase?->reference_id)
                    ->where('product_id', $item->product_id)
                    ->where('batch_no', $item->purchaseItem?->batch_no)
                    ->increment('quantity', (int) $item->return_qty);
            }
        }
    }

    // Build the row data for the edit screen so the user can adjust the already entered quantities.
    private function buildEditableRows(PurchaseReturn $purchaseReturn): array
    {
        return $purchaseReturn->items->map(function (PurchaseReturnItem $item) use ($purchaseReturn) {
            $purchaseItem = $item->purchaseItem?->loadMissing(['returns', 'batch', 'product']);
            $isLinkedToPurchase = $purchaseItem && $purchaseReturn->purchase;
            $originalQty = $isLinkedToPurchase
                ? (int) $purchaseItem->quantity + (int) $purchaseItem->free_qty
                : ((int) ($item->batch?->quantity_available ?? 0) + (int) $item->return_qty);
            $otherReturnedQty = $isLinkedToPurchase
                ? ((int) ($purchaseItem?->returns->sum('return_qty') ?? 0) - (int) $item->return_qty)
                : 0;
            $maxReturnable = $isLinkedToPurchase
                ? max(0, $originalQty - $otherReturnedQty)
                : max(0, $originalQty);
            $batchOptions = $isLinkedToPurchase
                ? $this->buildReturnBatchOptions($purchaseReturn->purchase, $purchaseItem)
                : $this->buildSupplierBatchOptions((int) $purchaseReturn->supplier_id, (int) $item->product_id);
            if (!$isLinkedToPurchase && $item->batch && !collect($batchOptions)->contains(fn (array $batchOption) => (int) $batchOption['id'] === (int) $item->batch_id)) {
                $batchOptions[] = [
                    'id' => (int) $item->batch_id,
                    'text' => trim(($item->batch->batch_number ?: '-') . ' | Exp: ' . ($item->batch->expiry_show ?: '-') . ' | Qty: ' . (int) $item->batch->quantity_available),
                    'badge_class' => 'bg-warning text-dark',
                    'badge_label' => 'Previously used batch',
                    'disabled' => false,
                    'quantity_available' => (int) $item->batch->quantity_available,
                ];
            }
            $selectedBatchId = $this->selectedReturnBatchId($item->batch_id, $batchOptions);
            if (!$selectedBatchId && count($batchOptions) === 1) {
                $selectedBatchId = (int) $batchOptions[0]['id'];
            }
            $batchBadge = $this->selectedReturnBatchBadge($selectedBatchId, $batchOptions);

            return [
                'purchase_item_id' => $item->purchase_item_id,
                'batch_id' => $item->batch_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->display_name ?? '-',
                'batch_no' => $item->batch?->batch_number ?: ($purchaseItem?->batch_no ?: '-'),
                'original_qty' => $originalQty,
                'already_returned' => $isLinkedToPurchase ? max(0, $otherReturnedQty) : 0,
                'max_returnable' => $maxReturnable,
                'return_qty' => (int) $item->return_qty,
                'rate' => round((float) $item->rate, 2),
                'discount_percent' => round((float) ($item->discount_percent ?? 0), 2),
                'discount_amount' => round((float) ($item->discount_amount ?? 0), 2),
                'net_rate' => round((float) ($item->net_rate ?? $item->rate ?? 0), 2),
                'return_amount' => round((float) ($item->return_amount ?? 0), 2),
                'batch_options' => $batchOptions,
                'selected_batch_id' => $selectedBatchId,
                'batch_badge_class' => $batchBadge['class'],
                'batch_badge_label' => $batchBadge['label'],
            ];
        })->values()->all();
    }

    // Build batch choices so the return row shows a real dropdown instead of a hidden id.
    private function buildReturnBatchOptions(Purchase $purchase, ?PurchaseItem $purchaseItem): array
    {
        if (!$purchaseItem) {
            return [];
        }

        return Batch::query()
            ->where('supplier_id', $purchase->supplier_id)
            ->where('product_id', $purchaseItem->product_id)
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('batch_number')
            ->get()
            ->map(function (Batch $batch) {
                $quantityAvailable = (int) $batch->quantity_available;
                $daysRemaining = (int) $batch->days_remaining;
                $state = $daysRemaining < 0 ? 'expired' : ($daysRemaining <= 30 ? 'warning' : 'valid');

                return [
                    'id' => $batch->id,
                    'text' => trim(($batch->batch_number ?: '-') . ' | Exp: ' . ($batch->expiry_show ?: '-') . ' | Qty: ' . $quantityAvailable),
                    'badge_class' => $quantityAvailable <= 0 ? 'bg-secondary' : ($state === 'expired' ? 'bg-danger' : ($state === 'warning' ? 'bg-warning text-dark' : 'bg-success')),
                    'badge_label' => $quantityAvailable <= 0 ? 'No stock left' : ($state === 'expired' ? 'Expired batch' : ($state === 'warning' ? 'Expiring soon' : 'Valid batch')),
                    'disabled' => false,
                    'quantity_available' => $quantityAvailable,
                ];
            })
            ->values()
            ->all();
    }

    // Build supplier batch choices for manual returns when purchase bill is unknown.
    private function buildSupplierBatchOptions(int $supplierId, int $productId): array
    {
        if ($supplierId <= 0 || $productId <= 0) {
            return [];
        }

        return Batch::query()
            ->where('supplier_id', $supplierId)
            ->where('product_id', $productId)
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('batch_number')
            ->get()
            ->map(function (Batch $batch) {
                $quantityAvailable = (int) $batch->quantity_available;
                $daysRemaining = (int) $batch->days_remaining;
                $state = $daysRemaining < 0 ? 'expired' : ($daysRemaining <= 30 ? 'warning' : 'valid');

                return [
                    'id' => $batch->id,
                    'text' => trim(($batch->batch_number ?: '-') . ' | Exp: ' . ($batch->expiry_show ?: '-') . ' | Qty: ' . $quantityAvailable),
                    'badge_class' => $state === 'expired' ? 'bg-danger' : ($state === 'warning' ? 'bg-warning text-dark' : 'bg-success'),
                    'badge_label' => $state === 'expired' ? 'Expired batch' : ($state === 'warning' ? 'Expiring soon' : 'Valid batch'),
                    'disabled' => false,
                    'quantity_available' => $quantityAvailable,
                ];
            })
            ->values()
            ->all();
    }

    // Keep the current batch highlighted if it exists, otherwise show a clear warning ribbon.
    private function selectedReturnBatchId(?int $batchId, array $batchOptions): ?int
    {
        if (!$batchId) {
            return null;
        }

        return collect($batchOptions)->contains(fn (array $batch) => (int) $batch['id'] === (int) $batchId) ? (int) $batchId : null;
    }

    // Decide which badge text the row should show while the batch dropdown is being used.
    private function selectedReturnBatchBadge(?int $selectedBatchId, array $batchOptions): array
    {
        if ($selectedBatchId) {
            $batch = collect($batchOptions)->firstWhere('id', $selectedBatchId);

            if ($batch) {
                return [
                    'class' => $batch['badge_class'],
                    'label' => $batch['badge_label'],
                ];
            }
        }

        if (empty($batchOptions)) {
            return [
                'class' => 'bg-danger',
                'label' => 'No valid batch found',
            ];
        }

        return [
            'class' => 'bg-warning text-dark',
            'label' => 'Choose a batch',
        ];
    }

    // Show one return voucher with all returned line items.
    public function show(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        return view('purchase-return.show', [
            'purchaseReturn' => $purchaseReturn->load(['supplier', 'purchase.reference', 'returnedBy', 'items.product', 'items.batch']),
        ]);
    }

    // Remove one purchase return and put the stock back to the same batch rows.
    public function destroy(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        try {
            DB::transaction(function () use ($purchaseReturn) {
                $purchaseReturn->load(['purchase.reference', 'items.purchaseItem.returns', 'items.batch']);
                $this->restoreReturnStock($purchaseReturn);
                PurchaseReturnItem::query()->where('purchase_return_id', $purchaseReturn->id)->delete();
                $purchaseReturn->delete();
            });

            return redirect()->route('admin.purchase-returns.index')->with('success', 'Purchase return deleted successfully.');
        } catch (\Throwable $throwable) {
            return back()->with('error', $throwable->getMessage() ?: 'Could not delete purchase return.');
        }
    }

    // Stream one purchase return voucher as PDF.
    public function print(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $purchaseReturn->load(['supplier', 'purchase.reference', 'returnedBy', 'items.product', 'items.batch']);

        return Pdf::loadView('pdf.purchase-return', [
            'purchaseReturn' => $purchaseReturn,
            'company' => pdf_company_context(),
            'logoSrc' => pdf_logo_src(),
        ])->setPaper('a4', 'portrait')
            ->stream('purchase-return-' . $purchaseReturn->id . '.pdf');
    }

    private function applyIndexFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['supplier_id']), function (Builder $builder) use ($filters) {
                $builder->where('supplier_id', $filters['supplier_id']);
            })
            ->when(($filters['return_mode'] ?? '') === 'bill', function (Builder $builder) {
                $builder->whereNotNull('purchase_id');
            })
            ->when(($filters['return_mode'] ?? '') === 'product', function (Builder $builder) {
                $builder->whereNull('purchase_id');
            })
            ->when(!empty($filters['date_from']), function (Builder $builder) use ($filters) {
                $builder->whereDate('return_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $builder) use ($filters) {
                $builder->whereDate('return_date', '<=', $filters['date_to']);
            });
    }
}
