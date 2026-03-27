<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    // Show the adjustment page with the latest stock movement history.
    public function index(Request $request)
    {
        $productId = $request->input('product_id');
        $batchId = $request->input('batch_id');

        $adjustments = StockAdjustment::with(['product', 'batch', 'adjustedBy'])
            ->latest()
            ->take(20)
            ->get();

        return view('inventory.adjustment.index', [
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'batches' => Batch::query()->with('product')->where('is_active', true)->orderByDesc('id')->get(),
            'adjustments' => $adjustments,
            'selectedProductId' => $productId,
            'selectedBatchId' => $batchId,
            'adjustmentTypes' => ['add', 'subtract', 'expired', 'damaged', 'return'],
        ]);
    }

    // Save one stock adjustment and make sure the selected batch really belongs to the selected product.
    // Same method also updates old rows, so we can keep the page in one simple modal flow.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'exists:stock_adjustments,id'],
            'product_id' => ['required', 'exists:products,id'],
            'batch_id' => ['required', 'exists:batches,id'],
            'adjustment_type' => ['required', 'in:add,subtract,expired,damaged,return'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
        ]);

        $batch = Batch::query()->findOrFail($validated['batch_id']);
        $adjustment = !empty($validated['id'])
            ? StockAdjustment::query()->findOrFail($validated['id'])
            : null;

        if ((int) $batch->product_id !== (int) $validated['product_id']) {
            return back()->withInput()->with('error', 'Selected batch does not belong to the chosen product.');
        }

        StockAdjustment::saveAdjustment([
            'product_id' => $validated['product_id'],
            'batch_id' => $batch->id,
            'adjusted_by' => $request->user()->id,
            'created_by' => $adjustment?->created_by ?? $request->user()->id,
            'updated_by' => $request->user()->id,
            'adjustment_type' => $validated['adjustment_type'],
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? null,
        ], $adjustment);

        return back()->with('success', $adjustment ? 'Stock adjustment updated successfully.' : 'Stock adjustment saved successfully.');
    }

    // Delete one adjustment row and reverse its stock effect from the batch.
    public function delete(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->deleteWithRollback();

        return back()->with('success', 'Stock adjustment deleted successfully.');
    }
}
