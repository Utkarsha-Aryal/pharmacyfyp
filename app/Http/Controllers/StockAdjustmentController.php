<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    // Show the adjustment page with the latest stock movement history.
    public function index(Request $request)
    {
        $productId = $request->input('product_id');
        $batchId = $request->input('batch_id');

        return view('inventory.adjustment.index', [
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'batches' => Batch::query()->with('product')->where('is_active', true)->orderByDesc('id')->get(),
            'selectedProductId' => $productId,
            'selectedBatchId' => $batchId,
            'adjustmentTypes' => ['add', 'subtract', 'expired', 'damaged', 'return'],
        ]);
    }

    // Return stock adjustments for the server-side table.
    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = StockAdjustment::query()
            ->with(['product', 'batch', 'adjustedBy'])
            ->latest('id');

        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('adjustment_type', 'like', '%' . $keyword . '%')
                    ->orWhere('reason', 'like', '%' . $keyword . '%')
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('batch', function (Builder $batchQuery) use ($keyword) {
                        $batchQuery->where('batch_number', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('adjustedBy', function (Builder $userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $adjustments = $query->get();
        $data = [];

        foreach ($adjustments as $index => $adjustment) {
            $typeClass = in_array($adjustment->adjustment_type, ['add', 'return'], true)
                ? 'bg-success'
                : 'bg-warning text-dark';
            $editButton = '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editAdjustmentBtn"'
                . ' title="Edit Adjustment"'
                . ' data-id="' . $adjustment->id . '"'
                . ' data-product_id="' . $adjustment->product_id . '"'
                . ' data-batch_id="' . $adjustment->batch_id . '"'
                . ' data-adjustment_type="' . e($adjustment->adjustment_type) . '"'
                . ' data-quantity="' . (int) $adjustment->quantity . '"'
                . ' data-reason="' . e((string) $adjustment->reason) . '">'
                . '<i class="fa-solid fa-pen-to-square"></i></button>';
            $deleteForm = '<form action="' . route('admin.inventory.adjustments.delete', $adjustment) . '" method="POST" class="js-confirm-submit"'
                . ' data-confirm-title="Delete this adjustment?"'
                . ' data-confirm-text="This will reverse the stock effect from the selected batch."'
                . ' data-confirm-button="Yes, delete adjustment">'
                . csrf_field()
                . '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Adjustment"><i class="fa-solid fa-trash"></i></button>'
                . '</form>';

            $data[] = [
                'sno' => $start + $index + 1,
                'product' => '<div class="text-wrap fw-semibold">' . e($adjustment->product?->display_name ?? '-') . '</div>',
                'batch' => '<span class="badge bg-light text-dark border">' . e($adjustment->batch?->batch_number ?? '-') . '</span>',
                'type' => '<span class="badge ' . $typeClass . '">' . e(ucfirst($adjustment->adjustment_type)) . '</span>',
                'quantity' => '<span class="badge bg-secondary">' . (int) $adjustment->quantity . '</span>',
                'reason' => '<div class="text-wrap small">' . e($adjustment->reason ?: '-') . '</div>',
                'adjusted_by' => e($adjustment->adjustedBy?->name ?? '-'),
                'date' => e($adjustment->created_at?->format('M j, Y') ?: '-'),
                'action' => '<div class="table-action-group">' . $editButton . $deleteForm . '</div>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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
