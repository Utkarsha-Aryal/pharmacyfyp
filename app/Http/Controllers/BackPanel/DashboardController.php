<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $expiryLastDate = $today->copy()->addDays(30);
        $purchaseBaseQuery = PurchaseOrder::query();
        $receivedPurchaseQuery = PurchaseOrder::query()->where('status', 'received');

        $lowStockProducts = Product::query()
            ->leftJoin('batches', function ($join) {
                $join->on('products.id', '=', 'batches.product_id')
                    ->where('batches.is_active', true);
            })
            ->where('products.status', 'Y')
            ->groupBy('products.id', 'products.product_name', 'products.name', 'products.reorder_level', 'products.alert_quantity')
            ->selectRaw('products.id, products.product_name, products.name, COALESCE(products.reorder_level, products.alert_quantity, 10) as reorder_level, COALESCE(SUM(batches.quantity_available), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(batches.quantity_available), 0) <= COALESCE(products.reorder_level, products.alert_quantity, 10)')
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

                $batch->expiry_show = $expiryDate->format('Y-m-d');
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

        $totalCategory = Category::where('status', 'Y')->count();
        $totalProducts = Product::where('status', 'Y')->count();
        $totalSuppliers = Supplier::where('status', 'Y')->count();
        $totalUsers = User::count();
        $totalStock = Batch::where('is_active', true)->sum('quantity_available');
        $totalBatches = Batch::where('is_active', true)->count();
        $thisMonthPurchaseValue = (clone $receivedPurchaseQuery)
            ->whereYear('order_date', $today->year)
            ->whereMonth('order_date', $today->month)
            ->sum('total_amount');
        $totalPurchaseValue = (clone $purchaseBaseQuery)->sum('total_amount');
        $lowStockCount = $lowStockProducts->count();
        $expiringSoonCount = $expiryAlerts->count();
        $expiredBatchesCount = Batch::where('is_active', true)->whereDate('expiry_date', '<', $today)->count();
        $pendingPurchaseOrdersCount = PurchaseOrder::where('status', 'pending')->count();

        $purchaseStatusCounts = [
            'pending' => (clone $purchaseBaseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $purchaseBaseQuery)->where('status', 'approved')->count(),
            'received' => (clone $purchaseBaseQuery)->where('status', 'received')->count(),
        ];

        $recentPurchases = PurchaseOrder::with(['supplier'])
            ->latest('order_date')
            ->take(5)
            ->get();

        $purchaseTrend = (clone $receivedPurchaseQuery)
            ->selectRaw("DATE_FORMAT(order_date, '%b %Y') as month_label, SUM(total_amount) as total_amount, MIN(order_date) as month_sort")
            ->groupBy('month_label')
            ->orderByDesc('month_sort')
            ->take(6)
            ->get();

        $purchaseTrend = $purchaseTrend->sortBy('month_sort')->values();

        $stockByCategory = Product::query()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('batches', function ($join) {
                $join->on('products.id', '=', 'batches.product_id')
                    ->where('batches.is_active', true);
            })
            ->where('products.status', 'Y')
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.name, COALESCE(SUM(batches.quantity_available), 0) as stock_qty')
            ->orderByDesc('stock_qty')
            ->take(5)
            ->get();

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

        return view('backend.dashboard.index', [
            'totalCategory' => $totalCategory,
            'totalProducts' => $totalProducts,
            'totalSuppliers' => $totalSuppliers,
            'totalUsers' => $totalUsers,
            'totalStock' => $totalStock,
            'totalBatches' => $totalBatches,
            'thisMonthPurchaseValue' => $thisMonthPurchaseValue,
            'totalPurchaseValue' => $totalPurchaseValue,
            'lowStockCount' => $lowStockCount,
            'expiringSoonCount' => $expiringSoonCount,
            'expiredBatchesCount' => $expiredBatchesCount,
            'pendingPurchaseOrdersCount' => $pendingPurchaseOrdersCount,
            'lowStockProducts' => $lowStockProducts->take(6),
            'expiryAlerts' => $expiryAlerts->take(6),
            'recentPurchases' => $recentPurchases,
            'purchaseStatusCounts' => $purchaseStatusCounts,
            'topSuppliers' => $topSuppliers,
            'overviewChart' => [
                'labels' => ['Category', 'Product', 'Supplier', 'Users', 'Batches'],
                'values' => [$totalCategory, $totalProducts, $totalSuppliers, $totalUsers, $totalBatches],
            ],
            'purchaseTrendChart' => [
                'labels' => $purchaseTrend->pluck('month_label')->values(),
                'values' => $purchaseTrend->pluck('total_amount')->map(fn ($value) => round((float) $value, 2))->values(),
            ],
            'stockCategoryChart' => [
                'labels' => $stockByCategory->pluck('name')->values(),
                'values' => $stockByCategory->pluck('stock_qty')->map(fn ($value) => (int) $value)->values(),
            ],
        ]);
    }
}
