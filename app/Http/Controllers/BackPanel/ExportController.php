<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;

class ExportController extends Controller
{
    private function downloadExcel(string $filename, $rows)
    {
        $directory = storage_path('app/temp-exports');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . '/' . uniqid('export_', true) . '_' . $filename;

        (new FastExcel($rows))->export($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function categories()
    {
        $rows = Category::query()
            ->where('status', 'Y')
            ->orderBy('order_number')
            ->get()
            ->map(fn ($category) => [
                'Name' => $category->name,
                'Order' => $category->order_number,
                'Added Date' => $category->created_at?->format('Y-m-d'),
            ]);

        return $this->downloadExcel('categories.xlsx', $rows);
    }

    public function units()
    {
        $rows = Unit::query()
            ->where('status', 'Y')
            ->orderBy('unit_name')
            ->get()
            ->map(fn ($unit) => [
                'Unit Name' => $unit->unit_name,
                'Description' => $unit->description,
                'Added Date' => $unit->created_at?->format('Y-m-d'),
            ]);

        return $this->downloadExcel('units.xlsx', $rows);
    }

    public function suppliers()
    {
        $rows = Supplier::query()
            ->where('status', 'Y')
            ->orderBy('supplier_name')
            ->get()
            ->map(fn ($supplier) => [
                'Supplier' => $supplier->supplier_name,
                'Contact Person' => $supplier->contact_person,
                'Phone' => $supplier->phone_number,
                'Email' => $supplier->email,
                'Opening Balance' => $supplier->opening_balance,
                'Type' => ucfirst((string) $supplier->type),
            ]);

        return $this->downloadExcel('suppliers.xlsx', $rows);
    }

    public function products()
    {
        $rows = Product::query()
            ->with(['category', 'productBatches' => fn ($query) => $query->where('status', 'Y')])
            ->where('status', 'Y')
            ->orderBy('product_name')
            ->get()
            ->map(fn ($product) => [
                'Product' => $product->product_name,
                'Category' => $product->category?->name,
                'Generic Name' => $product->generic_name,
                'MRP' => $product->mrp,
                'Purchase Price' => $product->purchase_price,
                'Alert Qty' => $product->alert_quantity,
                'Stock Qty' => $product->productBatches->sum('quantity'),
            ]);

        return $this->downloadExcel('products.xlsx', $rows);
    }

    public function inventoryProducts()
    {
        $rows = Product::query()
            ->with(['category', 'batches' => fn ($query) => $query->where('is_active', true)])
            ->where('status', 'Y')
            ->orderBy('product_name')
            ->get()
            ->map(fn ($product) => [
                'Product' => $product->display_name,
                'Category' => $product->category?->name,
                'Formulation' => $product->formulation,
                'Unit' => $product->unit,
                'Reorder Level' => $product->effective_reorder_level,
                'Current Stock' => $product->batches->sum('quantity_available'),
                'Status' => $product->is_active ? 'Active' : 'Inactive',
            ]);

        return $this->downloadExcel('inventory-products.xlsx', $rows);
    }

    public function inventoryBatches(Request $request)
    {
        $rows = Batch::query()
            ->with(['product', 'supplier'])
            ->when($request->filled('product_id'), function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->supplier_id);
            })
            ->orderBy('expiry_date')
            ->get()
            ->map(fn ($batch) => [
                'Product' => $batch->product?->display_name,
                'Batch No' => $batch->batch_number,
                'Supplier' => $batch->supplier?->supplier_name,
                'Expiry Date' => $batch->expiry_date,
                'Qty Available' => $batch->quantity_available,
                'Storage' => $batch->storage_location,
            ]);

        return $this->downloadExcel('inventory-batches.xlsx', $rows);
    }

    public function purchases(Request $request)
    {
        $rows = Purchase::query()
            ->with(['supplier', 'reference', 'batches'])
            ->where('status', 'Y')
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->filled('order_status'), function ($query) use ($request) {
                $query->where('order_status', $request->order_status);
            })
            ->latest('purchase_date')
            ->get()
            ->map(fn ($purchase) => [
                'Reference' => $purchase->reference?->reference_no,
                'Supplier' => $purchase->supplier?->supplier_name,
                'Invoice No' => $purchase->invoice_no,
                'Purchase Date' => $purchase->purchase_date,
                'Order Status' => ucfirst((string) $purchase->order_status),
                'Payment Status' => ucfirst((string) $purchase->payment_status),
                'Items' => $purchase->batches->count(),
                'Grand Total' => $purchase->grand_total,
                'Paid' => $purchase->paid_amount,
                'Due' => $purchase->due_amount,
            ]);

        return $this->downloadExcel('purchases.xlsx', $rows);
    }

    public function purchaseOrders(Request $request)
    {
        $rows = PurchaseOrder::query()
            ->with(['supplier', 'items'])
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('payment_status'), function ($query) use ($request) {
                $query->where('payment_status', $request->payment_status);
            })
            ->latest('order_date')
            ->get()
            ->map(fn ($order) => [
                'Reference' => $order->reference,
                'Supplier' => $order->supplier?->supplier_name,
                'Date' => $order->order_date_show,
                'Status' => $order->status_label,
                'Payment' => $order->payment_label,
                'Items' => $order->items->count(),
                'Total' => $order->total_amount,
                'Paid' => $order->paid_amount,
                'Due' => $order->outstanding_amount,
            ]);

        return $this->downloadExcel('purchase-orders.xlsx', $rows);
    }

    public function purchaseHistory(Request $request)
    {
        $rows = PurchaseOrder::query()
            ->with(['supplier', 'items'])
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('payment_status'), function ($query) use ($request) {
                $query->where('payment_status', $request->payment_status);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('order_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('order_date', '<=', $request->date_to);
            })
            ->latest('order_date')
            ->get()
            ->map(fn ($order) => [
                'Reference' => $order->reference,
                'Supplier' => $order->supplier?->supplier_name,
                'Date' => $order->order_date_show,
                'Status' => $order->status_label,
                'Payment' => $order->payment_label,
                'Items' => $order->items->count(),
                'Total' => $order->total_amount,
                'Due' => $order->outstanding_amount,
            ]);

        return $this->downloadExcel('purchase-history.xlsx', $rows);
    }

    public function supplierPerformance()
    {
        $rows = PurchaseOrder::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->groupBy('suppliers.id', 'suppliers.supplier_name')
            ->selectRaw("suppliers.supplier_name, COUNT(purchase_orders.id) as total_orders, SUM(purchase_orders.total_amount) as total_value, SUM(CASE WHEN purchase_orders.payment_status = 'paid' THEN 0 ELSE (purchase_orders.total_amount - purchase_orders.paid_amount) END) as outstanding_amount")
            ->orderByDesc('total_value')
            ->get()
            ->map(fn ($supplier) => [
                'Supplier' => $supplier->supplier_name,
                'Total Orders' => $supplier->total_orders,
                'Total Value' => $supplier->total_value,
                'Outstanding' => $supplier->outstanding_amount,
            ]);

        return $this->downloadExcel('supplier-performance.xlsx', $rows);
    }

    public function purchaseSupplierSummary()
    {
        $rows = Purchase::query()
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
            ->get()
            ->map(fn ($supplier) => [
                'Supplier' => $supplier->supplier_name,
                'Bills' => $supplier->total_bill,
                'Total Amount' => $supplier->total_amount,
                'Outstanding' => $supplier->outstanding_amount,
            ]);

        return $this->downloadExcel('top-suppliers-summary.xlsx', $rows);
    }

    public function users()
    {
        $rows = User::query()
            ->with('roles')
            ->latest('id')
            ->get()
            ->map(fn ($user) => [
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->getRoleNames()->implode(', '),
                'Added Date' => $user->created_at?->format('Y-m-d'),
            ]);

        return $this->downloadExcel('users.xlsx', $rows);
    }

    public function lowStock()
    {
        $rows = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('batches', function ($join) {
                $join->on('products.id', '=', 'batches.product_id')
                    ->where('batches.is_active', true);
            })
            ->where('products.status', 'Y')
            ->groupBy('products.id', 'products.product_name', 'products.reorder_level', 'products.alert_quantity', 'categories.name')
            ->selectRaw('products.product_name, categories.name as category_name, COALESCE(products.reorder_level, products.alert_quantity, 10) as reorder_level, COALESCE(SUM(batches.quantity_available), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(batches.quantity_available), 0) <= COALESCE(products.reorder_level, products.alert_quantity, 10)')
            ->orderBy('current_stock')
            ->get()
            ->map(fn ($item) => [
                'Product' => $item->product_name,
                'Category' => $item->category_name,
                'Reorder Level' => $item->reorder_level,
                'Current Stock' => $item->current_stock,
                'Deficit' => max(0, (int) $item->reorder_level - (int) $item->current_stock),
            ]);

        return $this->downloadExcel('low-stock-report.xlsx', $rows);
    }

    public function expiryAlert()
    {
        $today = Carbon::today();
        $nearDate = $today->copy()->addDays(60);

        $rows = Batch::query()
            ->with(['product', 'supplier'])
            ->where('is_active', true)
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($batch) use ($today, $nearDate) {
                $expiryDate = Batch::makeExpiryDate($batch->expiry_date);

                if (!$expiryDate || $expiryDate->gt($nearDate)) {
                    return null;
                }

                return [
                    'Product' => $batch->product?->display_name,
                    'Batch No' => $batch->batch_number,
                    'Supplier' => $batch->supplier?->supplier_name,
                    'Expiry Date' => $expiryDate->format('Y-m-d'),
                    'Days Left' => $today->diffInDays($expiryDate, false),
                    'Qty' => $batch->quantity_available,
                    'Location' => $batch->storage_location,
                ];
            })
            ->filter()
            ->values();

        return $this->downloadExcel('expiry-alert-report.xlsx', $rows);
    }

    public function batches(string $slug)
    {
        $product = Product::query()->where('slug', $slug)->firstOrFail();

        $rows = ProductBatch::query()
            ->with(['supplier', 'reference', 'purchase'])
            ->where('status', 'Y')
            ->where('product_id', $product->id)
            ->latest('id')
            ->get()
            ->map(fn ($batch) => [
                'Product' => $product->product_name,
                'Batch No' => $batch->batch_no,
                'Reference' => $batch->reference?->reference_no,
                'Supplier' => $batch->supplier?->supplier_name,
                'Purchase Date' => $batch->purchase?->purchase_date,
                'Expiry' => $batch->expiry_date,
                'Qty' => $batch->quantity,
                'Price' => $batch->purchase_price,
                'Subtotal' => $batch->subtotal,
            ]);

        return $this->downloadExcel('product-batch-history.xlsx', $rows);
    }
}
