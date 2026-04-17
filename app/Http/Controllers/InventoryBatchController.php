<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        return view('inventory.batch.index', [
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'filters' => $filters,
            'summary' => [
                'total_batches' => Batch::query()->where('is_active', true)->count(),
                'expired_batches' => Batch::query()->where('is_active', true)->whereDate('expiry_date', '<', $today)->count(),
                'expiring_soon' => Batch::query()->where('is_active', true)->whereBetween('expiry_date', [$today, $near30])->count(),
                'total_stock' => Batch::query()->where('is_active', true)->sum('quantity_available'),
            ],
        ]);
    }

    public function list(Request $request)
    {
        $filters = [
            'product_id' => $request->input('product_id'),
            'supplier_id' => $request->input('supplier_id'),
            'expiry_status' => $request->input('expiry_status'),
        ];
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = $this->applyFilters(
            Batch::query()->with(['product', 'supplier'])->where('is_active', true),
            $filters
        )->orderBy('expiry_date')->orderBy('id');

        $recordsTotal = Batch::query()->where('is_active', true)->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('batch_number', 'like', '%' . $keyword . '%')
                    ->orWhere('storage_location', 'like', '%' . $keyword . '%')
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('supplier', function (Builder $supplierQuery) use ($keyword) {
                        $supplierQuery->where('supplier_name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $batches = $query->get();
        $data = [];

        foreach ($batches as $index => $batch) {
            $statusClass = match ($batch->row_state) {
                'danger' => 'bg-danger',
                'warning' => 'bg-warning text-dark',
                'info' => 'bg-info text-dark',
                default => 'bg-success',
            };
            $daysLabel = $batch->days_remaining < 0
                ? abs((int) $batch->days_remaining) . ' day(s) overdue'
                : (int) $batch->days_remaining . ' day(s)';

            $editButton = '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editBatch" title="Edit Batch"'
                . ' data-id="' . $batch->id . '"'
                . ' data-product-id="' . $batch->product_id . '"'
                . ' data-supplier-id="' . $batch->supplier_id . '"'
                . ' data-batch-number="' . e($batch->batch_number) . '"'
                . ' data-manufacturing-date="' . e((string) $batch->manufacturing_date) . '"'
                . ' data-expiry-date="' . e((string) $batch->expiry_date) . '"'
                . ' data-quantity-received="' . (int) $batch->quantity_received . '"'
                . ' data-quantity-available="' . (int) $batch->quantity_available . '"'
                . ' data-purchase-price="' . e((string) $batch->purchase_price) . '"'
                . ' data-storage-location="' . e((string) $batch->storage_location) . '">'
                . '<i class="fa-solid fa-pen-to-square"></i></button>';

            $deleteForm = '<form action="' . route('admin.inventory.batches.delete', $batch) . '" method="POST" class="d-inline js-confirm-submit"'
                . ' data-confirm-title="Delete this batch?"'
                . ' data-confirm-text="This batch will be hidden from the active list."'
                . ' data-confirm-button="Yes, delete batch">'
                . csrf_field()
                . '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Batch" aria-label="Delete Batch"><i class="fa-solid fa-trash"></i></button>'
                . '</form>';

            $data[] = [
                'sno' => $start + $index + 1,
                'product' => '<div class="text-wrap fw-semibold">' . e($batch->product?->display_name ?? '-') . '</div>',
                'batch_number' => '<span class="badge bg-light text-dark border">' . e($batch->batch_number) . '</span>',
                'supplier' => '<div class="text-wrap">' . e($batch->supplier?->supplier_name ?? '-') . '</div>',
                'expiry_date' => e($batch->expiry_show),
                'days_remaining' => '<span class="badge ' . $statusClass . '">' . e($daysLabel) . '</span>',
                'quantity' => '<span class="badge bg-secondary">' . (int) $batch->quantity_available . '</span>',
                'storage' => e($batch->storage_location ?: '-'),
                'status' => '<span class="badge ' . $statusClass . '">' . e(Str::headline($batch->row_state)) . '</span>',
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
            $oldAvailable = (int) $batch->quantity_available;
            $batch->update($batchData);

            $diff = (int) $batch->quantity_available - $oldAvailable;
            if ($diff !== 0) {
                record_stock_movement([
                    'movement_date' => now()->toDateString(),
                    'product_id' => $batch->product_id,
                    'batch_id' => $batch->id,
                    'movement_type' => $diff > 0 ? 'adjustment_in' : 'adjustment_out',
                    'quantity_in' => $diff > 0 ? abs($diff) : 0,
                    'quantity_out' => $diff < 0 ? abs($diff) : 0,
                    'source_type' => $diff > 0 ? 'Batch Update' : 'Inventory',
                    'destination_type' => $diff > 0 ? 'Inventory' : 'Batch Update',
                    'reference_type' => 'Batch',
                    'reference_id' => $batch->id,
                    'notes' => 'Manual batch quantity update from inventory batch screen.',
                    'created_by' => $request->user()->id,
                ]);
            }

            return back()->with('success', 'Batch updated successfully.');
        }

        $batch = Batch::create($batchData);
        if ((int) $batch->quantity_available > 0) {
            record_stock_movement([
                'movement_date' => now()->toDateString(),
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'movement_type' => 'purchase_in',
                'quantity_in' => (int) $batch->quantity_available,
                'source_type' => 'Supplier',
                'source_id' => $batch->supplier_id,
                'destination_type' => 'Inventory',
                'reference_type' => 'Batch',
                'reference_id' => $batch->id,
                'notes' => 'Manual batch entry added to inventory.',
                'created_by' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Batch added successfully.');
    }

    public function destroy(Batch $batch)
    {
        $batch->update([
            'is_active' => false,
        ]);

        return back()->with('success', 'Batch removed successfully.');
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $today = Carbon::today();
        $near30 = $today->copy()->addDays(30);

        return $query
            ->when(!empty($filters['product_id']), function (Builder $builder) use ($filters) {
                $builder->where('product_id', $filters['product_id']);
            })
            ->when(!empty($filters['supplier_id']), function (Builder $builder) use ($filters) {
                $builder->where('supplier_id', $filters['supplier_id']);
            })
            ->when(!empty($filters['expiry_status']), function (Builder $builder) use ($filters, $today, $near30) {
                $builder->where(function (Builder $statusQuery) use ($filters, $today, $near30) {
                    if ($filters['expiry_status'] === 'expired') {
                        $statusQuery->whereDate('expiry_date', '<', $today);
                    } elseif ($filters['expiry_status'] === '7d') {
                        $statusQuery->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)]);
                    } elseif ($filters['expiry_status'] === '30d') {
                        $statusQuery->whereBetween('expiry_date', [$today, $near30]);
                    } elseif ($filters['expiry_status'] === '60d') {
                        $statusQuery->whereBetween('expiry_date', [$today, $today->copy()->addDays(60)]);
                    }
                });
            });
    }
}
