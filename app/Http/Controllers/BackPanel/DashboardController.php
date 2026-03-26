<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $expiryLastDate = $today->copy()->addDays(30);
        $purchaseBaseQuery = Purchase::query()->where('status', 'Y');
        $receivedPurchaseQuery = Purchase::query()
            ->where('status', 'Y')
            ->where('order_status', 'received');

        $lowStockProducts = Product::query()
            ->leftJoin('product_batches', function ($join) {
                $join->on('products.id', '=', 'product_batches.product_id')
                    ->where('product_batches.status', 'Y');
            })
            ->where('products.status', 'Y')
            ->whereNotNull('products.alert_quantity')
            ->groupBy('products.id', 'products.product_name', 'products.alert_quantity')
            ->selectRaw('products.id, products.product_name, products.alert_quantity, COALESCE(SUM(product_batches.quantity), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(product_batches.quantity), 0) <= products.alert_quantity')
            ->orderBy('current_stock')
            ->get();

        // change 3: dashboard expiry table now needs row level detail for next 30 days
        $expiryAlerts = ProductBatch::with('product')
            ->where('status', 'Y')
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($batch) use ($today, $expiryLastDate) {
                $expiryDate = $this->makeExpiryDate($batch->expiry_date);

                if (!$expiryDate || $expiryDate->lt($today) || $expiryDate->gt($expiryLastDate)) {
                    return null;
                }

                $batch->expiry_show = $expiryDate->format('Y-m-d');
                $batch->days_left = $today->diffInDays($expiryDate);
                $batch->alert_row_class = $batch->days_left < 7
                    ? 'dashboard-expiry-row-critical'
                    : ($batch->days_left <= 15 ? 'dashboard-expiry-row-warning' : 'dashboard-expiry-row-caution');

                return $batch;
            })
            ->filter()
            ->sortBy(function ($batch) {
                return $this->makeExpiryDate($batch->expiry_date)?->timestamp ?? PHP_INT_MAX;
            })
            ->values();

        $totalCategory = Category::where('status', 'Y')->count();
        $totalProducts = Product::where('status', 'Y')->count();
        $totalSuppliers = Supplier::where('status', 'Y')->count();
        $totalUsers = User::count();
        $totalStock = ProductBatch::where('status', 'Y')->sum('quantity');
        $totalBatches = ProductBatch::where('status', 'Y')->count();
        // change 2: keep monthly purchase in hero, accumulated purchase in kpi card
        $thisMonthPurchaseValue = (clone $receivedPurchaseQuery)
            ->whereYear('purchase_date', $today->year)
            ->whereMonth('purchase_date', $today->month)
            ->sum('grand_total');
        $totalPurchaseValue = (clone $receivedPurchaseQuery)->sum('grand_total');
        $lowStockCount = $lowStockProducts->count();
        $expiringSoonCount = $expiryAlerts->count();

        // change 4: small purchase status summary counts for dashboard and purchase history link
        $purchaseStatusCounts = [
            'pending' => (clone $purchaseBaseQuery)->where('order_status', 'pending')->count(),
            'approved' => (clone $purchaseBaseQuery)->where('order_status', 'approved')->count(),
            'received' => (clone $purchaseBaseQuery)->where('order_status', 'received')->count(),
        ];

        $recentPurchases = Purchase::with(['supplier', 'reference'])
            ->where('status', 'Y')
            ->latest('purchase_date')
            ->take(5)
            ->get();

        $purchaseTrend = (clone $receivedPurchaseQuery)
            ->selectRaw("DATE_FORMAT(purchase_date, '%b %Y') as month_label, SUM(grand_total) as total_amount, MIN(purchase_date) as month_sort")
            ->groupBy('month_label')
            ->orderBy('month_sort')
            ->take(6)
            ->get();

        $stockByCategory = Product::query()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('product_batches', function ($join) {
                $join->on('products.id', '=', 'product_batches.product_id')
                    ->where('product_batches.status', 'Y');
            })
            ->where('products.status', 'Y')
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.name, COALESCE(SUM(product_batches.quantity), 0) as stock_qty')
            ->orderByDesc('stock_qty')
            ->take(5)
            ->get();

        // change 5: supplier summary now includes outstanding payable amount
        $topSuppliers = Purchase::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchases.status', 'Y')
            ->groupBy('suppliers.id', 'suppliers.supplier_name')
            ->selectRaw("
                suppliers.supplier_name,
                COUNT(purchases.id) as total_bill,
                SUM(purchases.grand_total) as total_amount,
                SUM(CASE WHEN purchases.payment_status = 'paid' THEN 0 ELSE (purchases.grand_total - purchases.paid_amount) END) as outstanding_amount
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

    private function makeExpiryDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $value)) {
                return Carbon::createFromFormat('Y-m', $value)->endOfMonth();
            }

            return Carbon::parse($value);
        } catch (\Throwable $th) {
            return null;
        }
    }
}
