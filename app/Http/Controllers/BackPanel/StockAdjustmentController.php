<?php

namespace App\Http\Controllers\BackPanel;

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

        return view('backend.inventory.adjustment.index', [
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'batches' => Batch::query()->with('product')->where('is_active', true)->orderByDesc('id')->get(),
            'adjustments' => $adjustments,
            'selectedProductId' => $productId,
            'selectedBatchId' => $batchId,
        ]);
    }

    // Save one stock adjustment and make sure the selected batch really belongs to the selected product.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'batch_id' => ['required', 'exists:batches,id'],
            'adjustment_type' => ['required', 'in:add,subtract,expired,damaged,return'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
        ]);

        $batch = Batch::query()->findOrFail($validated['batch_id']);

        if ((int) $batch->product_id !== (int) $validated['product_id']) {
            return back()->withInput()->with('error', 'Selected batch does not belong to the chosen product.');
        }

        StockAdjustment::applyAdjustment([
            'product_id' => $validated['product_id'],
            'batch_id' => $batch->id,
            'adjusted_by' => $request->user()->id,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'adjustment_type' => $validated['adjustment_type'],
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Stock adjustment saved successfully.');
    }
}
