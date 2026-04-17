<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\DropdownOption;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function lowStock()
    {
        $lowStockProducts = $this->lowStockBaseQuery()->get();

        return view('report.low-stock', [
            'lowStockCount' => $lowStockProducts->count(),
            'zeroStockCount' => $lowStockProducts->where('current_stock', 0)->count(),
            'safeStockCount' => Product::where('status', 'Y')->count() - $lowStockProducts->count(),
        ]);
    }

    public function lowStockList(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = $this->lowStockBaseQuery();
        $recordsTotal = (clone $query)->get()->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('products.product_name', 'like', '%' . $keyword . '%')
                    ->orWhere('products.name', 'like', '%' . $keyword . '%')
                    ->orWhere('companies.name', 'like', '%' . $keyword . '%');
            });
        }

        $recordsFiltered = (clone $query)->get()->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $items = $query->get();
        $data = [];

        foreach ($items as $index => $item) {
            $currentStock = (int) $item->current_stock;
            $deficit = max(0, (int) $item->reorder_level - $currentStock);
            $statusClass = $currentStock === 0 ? 'bg-danger' : 'bg-warning text-dark';

            $data[] = [
                'sno' => $start + $index + 1,
                'product' => e($item->product_name),
                'company' => e($item->company_name ?? '-'),
                'reorder_level' => (int) $item->reorder_level,
                'current_stock' => '<span class="badge bg-secondary">' . $currentStock . '</span>',
                'deficit' => '<span class="badge bg-light text-dark border">' . $deficit . '</span>',
                'status' => '<span class="badge ' . $statusClass . '">' . e($currentStock === 0 ? 'Out of Stock' : 'Low Stock') . '</span>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Expiry report now supports a proper date range with quick 3 month and 6 month windows.
    public function expiryAlert(Request $request)
    {
        $filters = $this->expiryFilters($request);
        $today = Carbon::today();

        $expiryItems = $this->expiryBaseQuery($filters)->get()
            ->map(function ($batch) use ($today) {
                $expiryDate = Batch::makeExpiryDate($batch->expiry_date);

                if (!$expiryDate) {
                    return null;
                }

                $batch->days_left = $today->diffInDays($expiryDate, false);
                $batch->expiry_state = $expiryDate->lt($today)
                    ? 'expired'
                    : ($batch->days_left <= 90 ? 'critical' : ($batch->days_left <= 180 ? 'warning' : 'safe'));

                return $batch;
            })
            ->filter()
            ->values();

        return view('report.expiry-alert', [
            'expiredCount' => $expiryItems->where('expiry_state', 'expired')->count(),
            'nearCount' => $expiryItems->whereIn('expiry_state', ['critical', 'warning', 'near'])->count(),
            'safeCount' => $expiryItems->where('expiry_state', 'safe')->count(),
            'filters' => $filters,
        ]);
    }

    public function expiryAlertList(Request $request)
    {
        $filters = $this->expiryFilters($request);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $today = Carbon::today();

        $query = $this->expiryBaseQuery($filters);
        $recordsTotal = (clone $query)->count();

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

        $items = $query->get()->map(function (Batch $batch) use ($today) {
            $expiryDate = Batch::makeExpiryDate($batch->expiry_date);
            $batch->days_left = $expiryDate ? $today->diffInDays($expiryDate, false) : 0;
            $batch->expiry_state = !$expiryDate
                ? 'safe'
                : ($expiryDate->lt($today)
                    ? 'expired'
                    : ($batch->days_left <= 90 ? 'critical' : ($batch->days_left <= 180 ? 'warning' : 'safe')));

            return $batch;
        });

        $data = [];

        foreach ($items as $index => $item) {
            $statusClass = match ($item->expiry_state) {
                'expired', 'critical' => 'bg-danger',
                'warning' => 'bg-warning text-dark',
                default => 'bg-info text-dark',
            };

            $data[] = [
                'sno' => $start + $index + 1,
                'product' => e($item->product?->display_name ?? '-'),
                'batch' => '<span class="badge bg-light text-dark border">' . e($item->batch_number ?? '-') . '</span>',
                'supplier' => e($item->supplier?->supplier_name ?? '-'),
                'expiry' => e($item->expiry_show),
                'days_left' => '<span class="badge ' . $statusClass . '">' . e((string) $item->days_left) . '</span>',
                'qty' => '<span class="badge bg-secondary">' . (int) $item->quantity_available . '</span>',
                'location' => e($item->storage_location ?: '-'),
                'status' => '<span class="badge ' . $statusClass . '">' . e(Str::headline((string) $item->expiry_state)) . '</span>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Stream the expiry report as PDF using the same query and filters as the html page.
    public function expiryAlertPrint(Request $request)
    {
        $today = Carbon::today();
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'window' => ['nullable', 'in:3m,6m'],
        ]);

        $dateFrom = !empty($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : $today->copy()->startOfDay();
        $dateTo = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : (($validated['window'] ?? '6m') === '3m'
                ? $today->copy()->addMonths(3)->endOfDay()
                : $today->copy()->addMonths(6)->endOfDay());

        $expiryItems = Batch::query()
            ->with(['product', 'supplier'])
            ->where('is_active', true)
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '>=', $dateFrom->toDateString())
            ->whereDate('expiry_date', '<=', $dateTo->toDateString())
            ->orderBy('expiry_date')
            ->get()
            ->map(function (Batch $batch) use ($today) {
                $batch->days_left = $today->diffInDays($batch->expiry_date, false);
                return $batch;
            });

        return Pdf::loadView('pdf.expiry-alert', [
            'expiryItems' => $expiryItems,
            'company' => pdf_company_context(),
            'logoSrc' => pdf_logo_src(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ])->setPaper('a4', 'portrait')
            ->stream('expiry-alert-report.pdf');
    }

    public function purchaseHistory(Request $request)
    {
        return view('report.purchase-history', [
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'filters' => $request->only(['supplier_id', 'status', 'payment_status', 'date_from', 'date_to']),
        ]);
    }

    public function purchaseHistoryList(Request $request)
    {
        $filters = $request->only(['supplier_id', 'status', 'payment_status', 'date_from', 'date_to']);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 15);

        $query = $this->purchaseHistoryBaseQuery($filters)->orderByDesc('order_date')->orderByDesc('id');
        $recordsTotal = PurchaseOrder::query()->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reference', 'like', '%' . $keyword . '%')
                    ->orWhere('status', 'like', '%' . $keyword . '%')
                    ->orWhere('payment_status', 'like', '%' . $keyword . '%')
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
            $data[] = [
                'sno' => $start + $index + 1,
                'reference' => e($order->reference),
                'supplier' => e($order->supplier?->supplier_name ?? '-'),
                'date' => e($order->order_date_show),
                'status' => '<span class="badge bg-light text-dark border">' . e($order->status_label) . '</span>',
                'payment' => '<span class="badge bg-light text-dark border">' . e($order->payment_label) . '</span>',
                'total' => money_value($order->total_amount),
                'due' => money_value($order->outstanding_amount),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function supplierPerformance()
    {
        return view('report.supplier-performance', [
        ]);
    }

    public function supplierPerformanceList(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = $this->supplierPerformanceBaseQuery();
        $recordsTotal = (clone $query)->get()->count();

        if ($keyword !== '') {
            $query->where('suppliers.supplier_name', 'like', '%' . $keyword . '%');
        }

        $recordsFiltered = (clone $query)->get()->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $suppliers = $query->get();
        $data = [];

        foreach ($suppliers as $index => $supplier) {
            $outstanding = (float) $supplier->outstanding_amount;

            $data[] = [
                'sno' => $start + $index + 1,
                'supplier' => e($supplier->supplier_name),
                'total_orders' => (int) $supplier->total_orders,
                'total_value' => money_value($supplier->total_value),
                'outstanding' => $outstanding > 0
                    ? '<span class="badge bg-danger">' . e(money_value($outstanding)) . '</span>'
                    : '<span class="badge bg-success">Paid</span>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Sales report keeps invoice, payment and party data together for a clean accounting style report.
    public function salesReport(Request $request)
    {
        $filters = $request->only(['customer_id', 'sale_type_id', 'payment_status', 'date_from', 'date_to']);
        $sales = $this->salesReportBaseQuery($filters)->get();

        return view('report.sales', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'saleTypes' => DropdownOption::query()->forAlias('sales_type')->active()->orderBy('name')->get(),
            'filters' => $filters,
            'summary' => [
                'invoice_count' => $sales->count(),
                'total_sales' => round((float) $sales->sum('total_amount'), 2),
                'paid_sales' => round((float) $sales->sum('paid_amount'), 2),
                'due_sales' => round((float) $sales->sum(fn ($sale) => $sale->due_amount), 2),
            ],
        ]);
    }

    public function salesReportList(Request $request)
    {
        $filters = $request->only(['customer_id', 'sale_type_id', 'payment_status', 'date_from', 'date_to']);
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 15);

        $query = $this->salesReportBaseQuery($filters)->orderByDesc('invoice_date')->orderByDesc('id');
        $recordsTotal = SalesInvoice::query()->where('status', 'confirmed')->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reference', 'like', '%' . $keyword . '%')
                    ->orWhere('payment_status', 'like', '%' . $keyword . '%')
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                        $customerQuery->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('saleTypeOption', function (Builder $typeQuery) use ($keyword) {
                        $typeQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $sales = $query->get();
        $data = [];

        foreach ($sales as $index => $sale) {
            $data[] = [
                'sno' => $start + $index + 1,
                'reference' => e($sale->reference),
                'party' => e($sale->customer?->name ?? '-'),
                'date' => e($sale->invoice_date_show),
                'sale_type' => e($sale->sale_type_label),
                'payment' => '<span class="badge bg-light text-dark border">' . e($sale->payment_label) . '</span>',
                'total' => money_value($sale->total_amount),
                'paid' => money_value($sale->paid_amount),
                'due' => money_value($sale->due_amount),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function lowStockBaseQuery(): Builder
    {
        return Product::query()
            ->leftJoin('companies', 'companies.id', '=', 'products.company_id')
            ->leftJoin('batches', function ($join) {
                $join->on('products.id', '=', 'batches.product_id')
                    ->where('batches.is_active', true);
            })
            ->where('products.status', 'Y')
            ->groupBy('products.id', 'products.product_name', 'products.name', 'products.reorder_level', 'products.alert_quantity', 'companies.name')
            ->selectRaw('products.id, products.product_name, products.name, COALESCE(products.reorder_level, products.alert_quantity, 10) as reorder_level, companies.name as company_name, COALESCE(SUM(batches.quantity_available), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(batches.quantity_available), 0) < COALESCE(products.reorder_level, products.alert_quantity, 10)')
            ->orderBy('current_stock');
    }

    private function expiryFilters(Request $request): array
    {
        $today = Carbon::today();
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'window' => ['nullable', 'in:3m,6m'],
        ]);

        $dateFrom = !empty($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : $today->copy()->startOfDay();
        $dateTo = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : (($validated['window'] ?? '6m') === '3m'
                ? $today->copy()->addMonths(3)->endOfDay()
                : $today->copy()->addMonths(6)->endOfDay());

        return [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'window' => $validated['window'] ?? '6m',
        ];
    }

    private function expiryBaseQuery(array $filters): Builder
    {
        return Batch::query()
            ->with(['product', 'supplier'])
            ->where('is_active', true)
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '>=', $filters['date_from'])
            ->whereDate('expiry_date', '<=', $filters['date_to'])
            ->orderBy('expiry_date')
            ->orderBy('id');
    }

    private function purchaseHistoryBaseQuery(array $filters): Builder
    {
        return PurchaseOrder::query()
            ->with('supplier')
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

    private function supplierPerformanceBaseQuery(): Builder
    {
        return PurchaseOrder::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->groupBy('suppliers.id', 'suppliers.supplier_name')
            ->selectRaw("suppliers.id, suppliers.supplier_name, COUNT(purchase_orders.id) as total_orders, SUM(purchase_orders.total_amount) as total_value, SUM(CASE WHEN purchase_orders.payment_status = 'paid' THEN 0 ELSE (purchase_orders.total_amount - purchase_orders.paid_amount) END) as outstanding_amount")
            ->orderByDesc('total_value');
    }

    private function salesReportBaseQuery(array $filters): Builder
    {
        return SalesInvoice::query()
            ->with(['customer', 'paymentMode', 'saleTypeOption'])
            ->where('status', 'confirmed')
            ->when(!empty($filters['customer_id']), function (Builder $builder) use ($filters) {
                $builder->where('customer_id', $filters['customer_id']);
            })
            ->when(!empty($filters['sale_type_id']), function (Builder $builder) use ($filters) {
                $builder->where('sale_type_id', $filters['sale_type_id']);
            })
            ->when(!empty($filters['payment_status']), function (Builder $builder) use ($filters) {
                $builder->where('payment_status', $filters['payment_status']);
            })
            ->when(!empty($filters['date_from']), function (Builder $builder) use ($filters) {
                $builder->whereDate('invoice_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $builder) use ($filters) {
                $builder->whereDate('invoice_date', '<=', $filters['date_to']);
            });
    }
}
