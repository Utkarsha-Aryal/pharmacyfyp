<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventoryMovementController extends Controller
{
    // Show stock movement history so inventory trace stays clear for audit.
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->applyFilters($this->baseQuery(), $filters);

        $summaryQuery = (clone $query);

        return view('inventory.movement.index', [
            'filters' => $filters,
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
            'companies' => Company::query()->where('status', 'Y')->orderBy('name')->get(),
            'movementTypes' => $this->movementTypes(),
            'summary' => [
                'total_rows' => (clone $summaryQuery)->count(),
                'total_in' => (int) (clone $summaryQuery)->sum('quantity_in'),
                'total_out' => (int) (clone $summaryQuery)->sum('quantity_out'),
                'net' => (int) ((clone $summaryQuery)->sum('quantity_in') - (clone $summaryQuery)->sum('quantity_out')),
            ],
        ]);
    }

    // Return movement rows for server-side DataTables so large history stays fast.
    public function list(Request $request)
    {
        $filters = $this->filters($request);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 15);

        $query = $this->baseQuery()
            ->orderByDesc('movement_date')
            ->orderByDesc('id');

        $recordsTotal = (clone $query)->count();
        $query = $this->applyFilters($query, $filters);

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('movement_type', 'like', '%' . $keyword . '%')
                    ->orWhere('source_type', 'like', '%' . $keyword . '%')
                    ->orWhere('destination_type', 'like', '%' . $keyword . '%')
                    ->orWhere('reference_type', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%')
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%')
                            ->orWhereHas('company', function (Builder $companyQuery) use ($keyword) {
                                $companyQuery->where('name', 'like', '%' . $keyword . '%');
                            });
                    })
                    ->orWhereHas('batch', function (Builder $batchQuery) use ($keyword) {
                        $batchQuery->where('batch_number', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $movements = $query->get();
        $data = [];

        foreach ($movements as $index => $movement) {
            $movementClass = $this->movementBadgeClass($movement->movement_type);
            $productName = $movement->product?->display_name ?? '-';
            $companyName = $movement->product?->company?->name ?? 'No company';
            $batchNumber = $movement->batch?->batch_number ?? '-';
            $reference = $movement->reference_type
                ? $movement->reference_type . ' #' . $movement->reference_id
                : 'No reference';

            $data[] = [
                'sno' => $start + $index + 1,
                'date' => '<div class="fw-semibold">' . e($movement->movement_date_show) . '</div>',
                'product' => '<div class="fw-semibold text-wrap">' . e($productName) . '</div><div class="small text-muted text-wrap">' . e($companyName) . '</div>',
                'batch' => '<span class="badge bg-light text-dark border">' . e($batchNumber) . '</span>',
                'movement' => '<div class="d-flex flex-column gap-1"><span class="badge ' . $movementClass . '">' . e($movement->movement_type_label) . '</span><span class="small text-muted">' . e($movement->creator?->name ?? 'System') . '</span></div>',
                'qty' => '<div class="d-flex flex-column gap-1"><span class="badge bg-success">In ' . (int) $movement->quantity_in . '</span><span class="badge bg-danger">Out ' . (int) $movement->quantity_out . '</span></div>',
                'flow' => '<div class="d-flex flex-column gap-1"><span class="badge bg-light text-dark border">' . e($movement->source_type ?: 'Unknown') . '</span><span class="small text-muted">to</span><span class="badge bg-light text-dark border">' . e($movement->destination_type ?: 'Unknown') . '</span></div>',
                'reference' => '<span class="badge bg-secondary text-wrap">' . e($reference) . '</span>',
                'notes' => '<div class="text-wrap small">' . e(Str::limit((string) ($movement->notes ?: '-'), 110)) . '</div>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'product_id' => $request->input('product_id'),
            'company_id' => $request->input('company_id'),
            'movement_type' => $request->input('movement_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
    }

    private function baseQuery(): Builder
    {
        return StockMovement::query()->with(['product.company', 'batch', 'creator']);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['product_id']), function (Builder $builder) use ($filters) {
                $builder->where('product_id', $filters['product_id']);
            })
            ->when(!empty($filters['company_id']), function (Builder $builder) use ($filters) {
                $builder->whereHas('product', function (Builder $productQuery) use ($filters) {
                    $productQuery->where('company_id', $filters['company_id']);
                });
            })
            ->when(!empty($filters['movement_type']), function (Builder $builder) use ($filters) {
                $builder->where('movement_type', $filters['movement_type']);
            })
            ->when(!empty($filters['date_from']), function (Builder $builder) use ($filters) {
                $builder->whereDate('movement_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $builder) use ($filters) {
                $builder->whereDate('movement_date', '<=', $filters['date_to']);
            });
    }

    private function movementTypes(): array
    {
        return [
            'purchase_in' => 'Purchase In',
            'sales_out' => 'Sales Out',
            'purchase_return_out' => 'Purchase Return Out',
            'sales_return_in' => 'Sales Return In',
            'adjustment_in' => 'Adjustment In',
            'adjustment_out' => 'Adjustment Out',
        ];
    }

    private function movementBadgeClass(string $movementType): string
    {
        return match ($movementType) {
            'purchase_in' => 'bg-primary',
            'sales_out' => 'bg-danger',
            'purchase_return_out' => 'bg-warning text-dark',
            'sales_return_in' => 'bg-info text-dark',
            'adjustment_in' => 'bg-success',
            default => 'bg-dark',
        };
    }
}
