<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Supplier;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Keep dashboard data in one method so the overview screen can stay fast and easy to follow.
    public function index()
    {
        $today = Carbon::today();
        $expiryLastDate = $today->copy()->addDays(30);
        $expiryThreeMonthsDate = $today->copy()->addMonths(3);
        $purchaseBaseQuery = PurchaseOrder::query();
        $purchaseBillQuery = Purchase::query()->where('status', 'Y');
        $salesBaseQuery = SalesInvoice::query()->where('status', 'confirmed');

        $lowStockProducts = Product::query()
            ->leftJoin('batches', function ($join) {
                $join->on('products.id', '=', 'batches.product_id')
                    ->where('batches.is_active', true);
            })
            ->where('products.status', 'Y')
            ->groupBy('products.id', 'products.product_name', 'products.name', 'products.reorder_level', 'products.alert_quantity')
            ->selectRaw('products.id, products.product_name, products.name, COALESCE(products.reorder_level, products.alert_quantity, 10) as reorder_level, COALESCE(SUM(batches.quantity_available), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(batches.quantity_available), 0) < COALESCE(products.reorder_level, products.alert_quantity, 10)')
            ->orderBy('current_stock')
            ->get();

        $expiryAlerts = Batch::with('product')
            ->where('is_active', true)
            ->whereDate('expiry_date', '<=', $expiryLastDate)
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($batch) use ($today, $expiryLastDate) {
                $expiryDate = Batch::makeExpiryDate($batch->expiry_date);

                if (!$expiryDate || $expiryDate->gt($expiryLastDate)) {
                    return null;
                }

                $batch->expiry_show = $expiryDate->format('M j, Y');
                $batch->days_left = $today->diffInDays($expiryDate, false);
                $batch->alert_row_class = $batch->days_left < 0
                    ? 'dashboard-expiry-row-critical'
                    : ($batch->days_left <= 7 ? 'dashboard-expiry-row-critical' : ($batch->days_left <= 15 ? 'dashboard-expiry-row-warning' : 'dashboard-expiry-row-caution'));

                return $batch;
            })
            ->filter()
            ->sortBy(function ($batch) {
                return Batch::makeExpiryDate($batch->expiry_date)?->timestamp ?? PHP_INT_MAX;
            })
            ->values();

        $totalProducts = Product::count();
        $totalSuppliers = Supplier::count();
        $totalStock = Batch::where('is_active', true)->sum('quantity_available');
        $monthPurchase = (clone $purchaseBillQuery)
            ->whereYear('purchase_date', $today->year)
            ->whereMonth('purchase_date', $today->month)
            ->sum('grand_total');
        $todaySales = (clone $salesBaseQuery)
            ->whereDate('created_at', $today)
            ->sum('total_amount');
        $monthSales = (clone $salesBaseQuery)
            ->whereYear('created_at', $today->year)
            ->whereMonth('created_at', $today->month)
            ->sum('total_amount');
        $outstandingReceivables = (clone $salesBaseQuery)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total_due')
            ->value('total_due');
        $outstandingPayables = (clone $purchaseBillQuery)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as total_due')
            ->value('total_due');
        $lowStockCount = $lowStockProducts->count();
        $expiringSoonCount = Batch::where('is_active', true)
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $expiryThreeMonthsDate)
            ->count();
        $expiredBatchesCount = Batch::where('is_active', true)->whereDate('expiry_date', '<', $today)->count();
        $pendingPurchaseOrdersCount = PurchaseOrder::where('status', 'pending')->count();

        $purchaseStatusCounts = [
            'pending'   => (clone $purchaseBaseQuery)->where('status', 'pending')->count(),
            'approved'  => (clone $purchaseBaseQuery)->where('status', 'approved')->count(),
            'received'  => (clone $purchaseBaseQuery)->where('status', 'received')->count(),
        ];

        $recentPurchases = PurchaseOrder::with(['supplier'])
            ->latest('order_date')
            ->take(5)
            ->get();

        $recentSales = SalesInvoice::with(['customer'])
            ->where('status', 'confirmed')
            ->latest('invoice_date')
            ->take(5)
            ->get();

        $topSellingProducts = SalesInvoiceItem::query()
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->where('sales_invoices.status', 'confirmed')
            ->whereYear('sales_invoices.invoice_date', $today->year)
            ->whereMonth('sales_invoices.invoice_date', $today->month)
            ->groupBy('products.id', 'products.product_name', 'products.name')
            ->selectRaw('products.id, products.product_name, products.name, SUM(sales_invoice_items.quantity) as total_qty, SUM(sales_invoice_items.subtotal) as total_amount')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $comparisonLabels = [];
        $purchaseComparison = [];
        $salesComparison = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $monthStart = $today->copy()->startOfMonth()->subMonths($offset);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $comparisonLabels[] = $monthStart->format('M Y');
            $purchaseComparison[] = round((float) Purchase::query()
                ->where('status', 'Y')
                ->whereBetween('purchase_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('grand_total'), 2);
            $salesComparison[] = round((float) SalesInvoice::query()
                ->where('status', 'confirmed')
                ->whereBetween('invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('total_amount'), 2);
        }

        $topSuppliers = PurchaseOrder::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->groupBy('suppliers.id', 'suppliers.supplier_name')
            ->selectRaw("
                suppliers.supplier_name,
                COUNT(purchase_orders.id) as total_bill,
                SUM(purchase_orders.total_amount) as total_amount,
                SUM(CASE WHEN purchase_orders.payment_status = 'paid' THEN 0 ELSE (purchase_orders.total_amount - purchase_orders.paid_amount) END) as outstanding_amount
            ")
            ->orderByDesc('total_amount')
            ->take(5)
            ->get();

        return view('dashboard.index', [
            'totalProducts' => $totalProducts,
            'totalSuppliers' => $totalSuppliers,
            'totalStock' => $totalStock,
            'todaySales' => $todaySales,
            'monthSales' => $monthSales,
            'monthPurchase' => $monthPurchase,
            'outstandingReceivables' => $outstandingReceivables,
            'outstandingPayables' => $outstandingPayables,
            'lowStockCount' => $lowStockCount,
            'expiringSoonCount' => $expiringSoonCount,
            'expiredBatchesCount' => $expiredBatchesCount,
            'pendingPurchaseOrdersCount' => $pendingPurchaseOrdersCount,
            'lowStockProducts' => $lowStockProducts->take(6),
            'expiryAlerts' => $expiryAlerts->take(6),
            'recentPurchases' => $recentPurchases,
            'recentSales' => $recentSales,
            'purchaseStatusCounts' => $purchaseStatusCounts,
            'topSuppliers' => $topSuppliers,
            'topSellingProductsChart' => [
                'labels' => $topSellingProducts->map(fn ($item) => $item->name ?: $item->product_name)->values(),
                'values' => $topSellingProducts->pluck('total_qty')->map(fn ($value) => round((float) $value, 2))->values(),
            ],
            'salesPurchaseChart' => [
                'labels' => $comparisonLabels,
                'sales' => $salesComparison,
                'purchase' => $purchaseComparison,
            ],
        ]);
    }
}
