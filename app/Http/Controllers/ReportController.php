<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function lowStock()
    {
        $lowStockProducts = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('batches', function ($join) {
                $join->on('products.id', '=', 'batches.product_id')
                    ->where('batches.is_active', true);
            })
            ->where('products.status', 'Y')
            ->groupBy('products.id', 'products.product_name', 'products.name', 'products.reorder_level', 'products.alert_quantity', 'categories.name')
            ->selectRaw('products.id, products.product_name, products.name, COALESCE(products.reorder_level, products.alert_quantity, 10) as reorder_level, categories.name as category_name, COALESCE(SUM(batches.quantity_available), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(batches.quantity_available), 0) < COALESCE(products.reorder_level, products.alert_quantity, 10)')
            ->orderBy('current_stock')
            ->get();

        return view('report.low-stock', [
            'lowStockProducts' => $lowStockProducts,
            'lowStockCount' => $lowStockProducts->count(),
            'zeroStockCount' => $lowStockProducts->where('current_stock', 0)->count(),
            'safeStockCount' => Product::where('status', 'Y')->count() - $lowStockProducts->count(),
        ]);
    }

    // Expiry report now supports a proper date range with quick 3 month and 6 month windows.
    public function expiryAlert(Request $request)
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
            : (
                ($validated['window'] ?? '6m') === '3m'
                    ? $today->copy()->addMonths(3)->endOfDay()
                    : $today->copy()->addMonths(6)->endOfDay()
            );

        $expiryItems = Batch::with(['product', 'supplier'])
            ->where('is_active', true)
            ->whereDate('expiry_date', '>=', $dateFrom->toDateString())
            ->whereDate('expiry_date', '<=', $dateTo->toDateString())
            ->orderBy('expiry_date')
            ->get()
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
            'expiryItems' => $expiryItems,
            'expiredCount' => $expiryItems->where('expiry_state', 'expired')->count(),
            'nearCount' => $expiryItems->whereIn('expiry_state', ['critical', 'warning', 'near'])->count(),
            'safeCount' => $expiryItems->where('expiry_state', 'safe')->count(),
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'window' => $validated['window'] ?? '6m',
            ],
        ]);
    }

    public function purchaseHistory(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'items'])
            ->latest('order_date');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        return view('report.purchase-history', [
            'orders' => $query->get(),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'filters' => $request->only(['supplier_id', 'status', 'payment_status', 'date_from', 'date_to']),
        ]);
    }

    public function supplierPerformance()
    {
        $suppliers = PurchaseOrder::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->groupBy('suppliers.id', 'suppliers.supplier_name')
            ->selectRaw("suppliers.id, suppliers.supplier_name, COUNT(purchase_orders.id) as total_orders, SUM(purchase_orders.total_amount) as total_value, SUM(CASE WHEN purchase_orders.payment_status = 'paid' THEN 0 ELSE (purchase_orders.total_amount - purchase_orders.paid_amount) END) as outstanding_amount")
            ->orderByDesc('total_value')
            ->get();

        return view('report.supplier-performance', [
            'suppliers' => $suppliers,
        ]);
    }
}
