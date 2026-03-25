<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $expiryLastDate = $today->copy()->addDays(30);

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

        $expiringSoon = ProductBatch::with('product')
            ->where('status', 'Y')
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($batch) use ($today, $expiryLastDate) {
                $expiryDate = $this->makeExpiryDate($batch->expiry_date);

                if (!$expiryDate || $expiryDate->lt($today) || $expiryDate->gt($expiryLastDate)) {
                    return null;
                }

                $batch->expiry_show = $expiryDate->format('M Y');
                $batch->days_left = $today->diffInDays($expiryDate);

                return $batch;
            })
            ->filter()
            ->values();

        $totalCategory = Category::where('status', 'Y')->count();
        $totalProducts = Product::where('status', 'Y')->count();
        $totalSuppliers = Supplier::where('status', 'Y')->count();
        $totalUsers = User::count();
        $totalStock = ProductBatch::where('status', 'Y')->sum('quantity');
        $totalBatches = ProductBatch::where('status', 'Y')->count();
        $lowStockCount = $lowStockProducts->count();
        $expiringSoonCount = $expiringSoon->count();

        return view('backend.dashboard.index', [
            'totalCategory' => $totalCategory,
            'totalProducts' => $totalProducts,
            'totalSuppliers' => $totalSuppliers,
            'totalUsers' => $totalUsers,
            'totalStock' => $totalStock,
            'totalBatches' => $totalBatches,
            'lowStockCount' => $lowStockCount,
            'expiringSoonCount' => $expiringSoonCount,
            'lowStockProducts' => $lowStockProducts->take(5),
            'expiringSoon' => $expiringSoon->take(5),
            'overviewChart' => [
                'labels' => ['Category', 'Product', 'Supplier', 'Users', 'Batches'],
                'values' => [$totalCategory, $totalProducts, $totalSuppliers, $totalUsers, $totalBatches],
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
