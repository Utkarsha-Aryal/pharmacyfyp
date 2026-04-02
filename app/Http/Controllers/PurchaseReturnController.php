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

        $items = PurchaseItem::query()
            ->with(['product', 'batch', 'returns'])
            ->where('purchase_id', $validated['purchase_id'])
            ->get()
            ->map(function (PurchaseItem $item) {
                $returnedQty = (int) $item->returns->sum('return_qty');
                $originalQty = (int) $item->quantity + (int) $item->free_qty;
                $maxReturnable = max(0, $originalQty - $returnedQty);

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
                ];
            })
            ->values();

        return response()->json($items);
    }

    // Save the return and reduce stock from the chosen batch.
    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        $validated = $request->validate([
            'purchase_id' => ['required', 'exists:purchases,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['nullable', 'exists:purchase_items,id'],
            'items.*.batch_id' => ['nullable', 'exists:batches,id'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.return_qty' => ['nullable', 'integer', 'min:0'],
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
                    'items' => 'Every return line needs a valid purchase item and batch.',
                ]);
            }
        }

        $purchaseReturn = DB::transaction(function () use ($validated, $request, $rows) {
            $purchase = Purchase::query()->findOrFail($validated['purchase_id']);

            if ((int) $purchase->supplier_id !== (int) $validated['supplier_id']) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Selected purchase bill does not belong to the chosen supplier.',
                ]);
            }

            $purchaseReturn = PurchaseReturn::query()->create([
                'purchase_id' => $validated['purchase_id'],
                'supplier_id' => $validated['supplier_id'],
                'return_date' => $validated['return_date'],
                'notes' => $validated['notes'] ?? null,
                'returned_by' => $request->user()->id,
            ]);

            foreach ($rows as $row) {
                $purchaseItem = PurchaseItem::query()
                    ->with(['returns', 'batch'])
                    ->where('purchase_id', $validated['purchase_id'])
                    ->findOrFail($row['purchase_item_id']);

                $batch = Batch::query()->lockForUpdate()->findOrFail($row['batch_id']);

                if ((int) $purchaseItem->batch_id !== (int) $batch->id) {
                    throw ValidationException::withMessages([
                        'items' => 'Selected batch does not belong to the chosen purchase line.',
                    ]);
                }

                $alreadyReturned = (int) $purchaseItem->returns->sum('return_qty');
                $maxReturnable = max(0, ((int) $purchaseItem->quantity + (int) $purchaseItem->free_qty) - $alreadyReturned);

                if ((int) $row['return_qty'] > $maxReturnable) {
                    throw ValidationException::withMessages([
                        'items' => 'Return quantity cannot be more than the remaining returnable quantity.',
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

    // Show one return voucher with all returned line items.
    public function show(PurchaseReturn $purchaseReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'superadmin']), 403);

        return view('purchase-return.show', [
            'purchaseReturn' => $purchaseReturn->load(['supplier', 'purchase.reference', 'returnedBy', 'items.product', 'items.batch']),
        ]);
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
