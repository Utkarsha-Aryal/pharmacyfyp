<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    // List purchase returns for admin and superadmin users.
    public function index()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        return view('purchase-return.index', [
            'returns' => PurchaseReturn::query()
                ->with(['supplier', 'purchase.reference', 'returnedBy'])
                ->latest('return_date')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    // Open the return form with supplier selection first.
    public function create()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        return view('purchase-return.create', [
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
        ]);
    }

    // Open the edit screen with the existing supplier and return rows already filled.
    public function edit(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $purchaseReturn->load(['supplier', 'purchase.reference', 'items.purchaseItem.returns', 'items.batch', 'items.product']);

        return view('purchase-return.edit', [
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'purchaseReturn' => $purchaseReturn,
            'itemsRows' => $this->buildEditableRows($purchaseReturn),
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
                    'batch_options' => $batchOptions,
                    'selected_batch_id' => $selectedBatchId,
                    'batch_badge_class' => $batchBadge['class'],
                    'batch_badge_label' => $batchBadge['label'],
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
            'purchase_id' => ['required', 'exists:purchases,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['nullable', 'exists:purchase_items,id'],
            'items.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.return_qty' => ['nullable', 'integer', 'min:0'],
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
            if (empty($row['purchase_item_id']) || empty($row['batch_id']) || empty($row['product_id'])) {
                throw ValidationException::withMessages([
                    'items' => 'Every return row needs a product, purchase item and batch selected from the dropdown.',
                ]);
            }
        }

        $purchaseReturn = DB::transaction(function () use ($validated, $request, $rows, $existingReturn) {
            $purchase = Purchase::query()->findOrFail($validated['purchase_id']);

            if ((int) $purchase->supplier_id !== (int) $validated['supplier_id']) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Selected purchase bill does not belong to the chosen supplier.',
                ]);
            }

            if ($existingReturn) {
                $existingReturn->load(['purchase.reference', 'items.purchaseItem.returns', 'items.batch']);
                $this->restoreReturnStock($existingReturn);
                PurchaseReturnItem::query()->where('purchase_return_id', $existingReturn->id)->delete();
                $existingReturn->update([
                    'purchase_id' => $validated['purchase_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'return_date' => $validated['return_date'],
                    'notes' => $validated['notes'] ?? null,
                    'returned_by' => $request->user()->id,
                ]);
                $purchaseReturn = $existingReturn;
            } else {
                $purchaseReturn = PurchaseReturn::query()->create([
                    'purchase_id' => $validated['purchase_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'return_date' => $validated['return_date'],
                    'notes' => $validated['notes'] ?? null,
                    'returned_by' => $request->user()->id,
                ]);
            }

            foreach ($rows as $row) {
                $purchaseItem = PurchaseItem::query()
                    ->with(['returns', 'batch'])
                    ->where('purchase_id', $validated['purchase_id'])
                    ->findOrFail($row['purchase_item_id']);

                $batch = Batch::query()->lockForUpdate()->findOrFail($row['batch_id']);

                $allowedBatchIds = Batch::query()
                    ->where('supplier_id', $purchase->supplier_id)
                    ->where('product_id', $purchaseItem->product_id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (! in_array((int) $batch->id, $allowedBatchIds, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Please choose a valid batch from the dropdown for ' . ($purchaseItem->product?->display_name ?? 'this row') . '.',
                    ]);
                }

                $alreadyReturned = (int) $purchaseItem->returns->sum('return_qty');
                $maxReturnable = max(0, ((int) $purchaseItem->quantity + (int) $purchaseItem->free_qty) - $alreadyReturned);

                if ((int) $row['return_qty'] > $maxReturnable) {
                    throw ValidationException::withMessages([
                        'items' => 'Return quantity cannot be more than the remaining returnable quantity.',
                    ]);
                }

                if ((int) $batch->quantity_available < (int) $row['return_qty']) {
                    throw ValidationException::withMessages([
                        'items' => 'Selected batch does not have enough stock available for this return.',
                    ]);
                }

                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'batch_id' => $batch->id,
                    'product_id' => $purchaseItem->product_id,
                    'return_qty' => (int) $row['return_qty'],
                    'rate' => (float) $purchaseItem->rate,
                ]);

                $batch->quantity_available = max(0, (int) $batch->quantity_available - (int) $row['return_qty']);
                $batch->save();

                ProductBatch::query()
                    ->where('reference_id', $purchase->reference_id)
                    ->where('product_id', $purchaseItem->product_id)
                    ->where('batch_no', $purchaseItem->batch_no)
                    ->decrement('quantity', (int) $row['return_qty']);
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
            }

            ProductBatch::query()
                ->where('reference_id', $purchaseReturn->purchase?->reference_id)
                ->where('product_id', $item->product_id)
                ->where('batch_no', $item->purchaseItem?->batch_no)
                ->increment('quantity', (int) $item->return_qty);
        }
    }

    // Build the row data for the edit screen so the user can adjust the already entered quantities.
    private function buildEditableRows(PurchaseReturn $purchaseReturn): array
    {
        return $purchaseReturn->items->map(function (PurchaseReturnItem $item) use ($purchaseReturn) {
            $purchaseItem = $item->purchaseItem?->loadMissing(['returns', 'batch', 'product']);
            $originalQty = (int) $purchaseItem?->quantity + (int) $purchaseItem?->free_qty;
            $otherReturnedQty = (int) ($purchaseItem?->returns->sum('return_qty') ?? 0) - (int) $item->return_qty;
            $maxReturnable = max(0, $originalQty - $otherReturnedQty);
            $batchOptions = $this->buildReturnBatchOptions($purchaseReturn->purchase, $purchaseItem);
            $selectedBatchId = $this->selectedReturnBatchId($item->batch_id, $batchOptions);
            $batchBadge = $this->selectedReturnBatchBadge($selectedBatchId, $batchOptions);

            return [
                'purchase_item_id' => $item->purchase_item_id,
                'batch_id' => $item->batch_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->display_name ?? '-',
                'batch_no' => $item->batch?->batch_number ?: ($purchaseItem?->batch_no ?: '-'),
                'original_qty' => $originalQty,
                'already_returned' => max(0, $otherReturnedQty),
                'max_returnable' => $maxReturnable,
                'return_qty' => (int) $item->return_qty,
                'rate' => round((float) $item->rate, 2),
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
}
