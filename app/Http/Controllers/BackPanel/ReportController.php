<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function lowStock()
    {
        $lowStockProducts = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('product_batches', function ($join) {
                $join->on('products.id', '=', 'product_batches.product_id')
                    ->where('product_batches.status', 'Y');
            })
            ->where('products.status', 'Y')
            ->whereNotNull('products.alert_quantity')
            ->groupBy('products.id', 'products.product_name', 'products.alert_quantity', 'categories.name')
            ->selectRaw('products.id, products.product_name, products.alert_quantity, categories.name as category_name, COALESCE(SUM(product_batches.quantity), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(product_batches.quantity), 0) <= products.alert_quantity')
            ->orderBy('current_stock')
            ->get();

        return view('backend.report.low-stock', [
            'lowStockProducts' => $lowStockProducts,
            'lowStockCount' => $lowStockProducts->count(),
            'zeroStockCount' => $lowStockProducts->where('current_stock', 0)->count(),
            'safeStockCount' => Product::where('status', 'Y')->count() - $lowStockProducts->count(),
        ]);
    }

    public function expiryAlert()
    {
        $today = Carbon::today();
        $nearDate = $today->copy()->addDays(60);

        $expiryItems = ProductBatch::with(['product', 'supplier', 'reference', 'purchase'])
            ->where('status', 'Y')
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($batch) use ($today, $nearDate) {
                $expiryDate = ProductBatch::makeExpiryDate($batch->expiry_date);

                if (!$expiryDate) {
                    return null;
                }

                $batch->expiry_show = $expiryDate->format('M Y');
                $batch->days_left = $today->diffInDays($expiryDate, false);
                $batch->expiry_state = $expiryDate->lt($today) ? 'expired' : ($expiryDate->lte($nearDate) ? 'near' : 'safe');

                return $batch;
            })
            ->filter()
            ->values();

        return view('backend.report.expiry-alert', [
            'expiryItems' => $expiryItems,
            'expiredCount' => $expiryItems->where('expiry_state', 'expired')->count(),
            'nearCount' => $expiryItems->where('expiry_state', 'near')->count(),
            'safeCount' => $expiryItems->where('expiry_state', 'safe')->count(),
        ]);
    }
}
