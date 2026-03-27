<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryBatchController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'product_id' => $request->input('product_id'),
            'supplier_id' => $request->input('supplier_id'),
            'expiry_status' => $request->input('expiry_status'),
        ];

        $today = Carbon::today();
        $near30 = $today->copy()->addDays(30);

        $query = Batch::with(['product', 'supplier'])->where('is_active', true);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['expiry_status'])) {
            $query->where(function ($builder) use ($filters, $today, $near30) {
                if ($filters['expiry_status'] === 'expired') {
                    $builder->whereDate('expiry_date', '<', $today);
                } elseif ($filters['expiry_status'] === '7d') {
                    $builder->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)]);
                } elseif ($filters['expiry_status'] === '30d') {
                    $builder->whereBetween('expiry_date', [$today, $near30]);
                } elseif ($filters['expiry_status'] === '60d') {
                    $builder->whereBetween('expiry_date', [$today, $today->copy()->addDays(60)]);
                }
            });
        }

        $batches = $query->orderBy('expiry_date')->get();

        return view('inventory.batch.index', [
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'filters' => $filters,
            'batches' => $batches,
            'summary' => [
                'total_batches' => Batch::query()->where('is_active', true)->count(),
                'expired_batches' => Batch::query()->where('is_active', true)->whereDate('expiry_date', '<', $today)->count(),
                'expiring_soon' => Batch::query()->where('is_active', true)->whereBetween('expiry_date', [$today, $near30])->count(),
                'total_stock' => Batch::query()->where('is_active', true)->sum('quantity_available'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:batches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'batch_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('batches', 'batch_number')->ignore($request->input('id')),
            ],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['required', 'date'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            'quantity_available' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:255'],
        ]);

        $batchData = [
            'product_id' => $validated['product_id'],
            'supplier_id' => $validated['supplier_id'],
            'batch_number' => $validated['batch_number'],
            'manufacturing_date' => $validated['manufacturing_date'] ?? null,
            'expiry_date' => $validated['expiry_date'],
            'quantity_received' => $validated['quantity_received'],
            'quantity_available' => $validated['quantity_available'] ?? $validated['quantity_received'],
            'purchase_price' => $validated['purchase_price'],
            'storage_location' => $validated['storage_location'] ?? null,
            'is_active' => true,
        ];

        if (!empty($validated['id'])) {
            $batch = Batch::query()->findOrFail($validated['id']);
            $batch->update($batchData);

            return back()->with('success', 'Batch updated successfully.');
        }

        Batch::create($batchData);

        return back()->with('success', 'Batch added successfully.');
    }

    public function destroy(Batch $batch)
    {
        $batch->update([
            'is_active' => false,
        ]);

        return back()->with('success', 'Batch removed successfully.');
    }
}
