<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    // Keep PDF download in one helper so finance reports use the same paper settings.
    private function downloadPdf(string $filename, string $view, array $data, string $paper = 'a4', string $orientation = 'portrait')
    {
        return Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->download($filename);
    }

    // Use one shared PDF table so list exports keep the same clean structure.
    private function downloadTablePdf(string $filename, string $title, string $subtitle, array $columns, Collection|array $rows, string $paper = 'a4', string $orientation = 'landscape')
    {
        return $this->downloadPdf(
            $filename,
            'backend.export.pdf.table',
            [
                'title' => $title,
                'subtitle' => $subtitle,
                'columns' => $columns,
                'rows' => collect($rows)->values(),
                'generatedAt' => now()->format('M j, Y h:i A'),
            ],
            $paper,
            $orientation
        );
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
                'Added Date' => $category->created_at?->format('M j, Y'),
            ]);

        return $this->downloadExcel('categories.xlsx', $rows);
    }

    // Export category list as PDF for sharing and print.
    public function categoriesPdf()
    {
        $rows = Category::query()
            ->where('status', 'Y')
            ->orderBy('order_number')
            ->get()
            ->map(fn ($category) => [
                'Name' => $category->name,
                'Display Order' => $category->order_number,
                'Added Date' => $category->created_at?->format('M j, Y'),
            ]);

        return $this->downloadTablePdf('categories.pdf', 'Category List', 'Sorted category master list', ['Name', 'Display Order', 'Added Date'], $rows);
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
                'Added Date' => $unit->created_at?->format('M j, Y'),
            ]);

        return $this->downloadExcel('units.xlsx', $rows);
    }

    // Export unit list as PDF.
    public function unitsPdf()
    {
        $rows = Unit::query()
            ->where('status', 'Y')
            ->orderBy('unit_name')
            ->get()
            ->map(fn ($unit) => [
                'Unit Name' => $unit->unit_name,
                'Description' => $unit->description ?: '-',
                'Added Date' => $unit->created_at?->format('M j, Y'),
            ]);

        return $this->downloadTablePdf('units.pdf', 'Unit List', 'Measurement and packaging units', ['Unit Name', 'Description', 'Added Date'], $rows);
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

    // Export supplier list as PDF.
    public function suppliersPdf()
    {
        $rows = Supplier::query()
            ->where('status', 'Y')
            ->orderBy('supplier_name')
            ->get()
            ->map(fn ($supplier) => [
                'Supplier' => $supplier->supplier_name,
                'Contact Person' => $supplier->contact_person ?: '-',
                'Phone' => $supplier->phone_number ?: '-',
                'Email' => $supplier->email ?: '-',
                'Current Balance' => number_format((float) $supplier->opening_balance, 2),
                'Type' => ucfirst((string) $supplier->type),
            ]);

        return $this->downloadTablePdf('suppliers.pdf', 'Supplier List', 'Supplier contact and balance summary', ['Supplier', 'Contact Person', 'Phone', 'Email', 'Current Balance', 'Type'], $rows);
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

    // Export product master as PDF.
    public function productsPdf()
    {
        $rows = Product::query()
            ->with(['category', 'productBatches' => fn ($query) => $query->where('status', 'Y')])
            ->where('status', 'Y')
            ->orderBy('product_name')
            ->get()
            ->map(fn ($product) => [
                'Product' => $product->product_name,
                'Category' => $product->category?->name ?: '-',
                'Generic Name' => $product->generic_name ?: '-',
                'MRP' => number_format((float) $product->mrp, 2),
                'Alert Qty' => (int) $product->alert_quantity,
                'Stock Qty' => $product->productBatches->sum('quantity'),
            ]);

        return $this->downloadTablePdf('products.pdf', 'Product List', 'Product master with stock overview', ['Product', 'Category', 'Generic Name', 'MRP', 'Alert Qty', 'Stock Qty'], $rows);
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

    // Export inventory product stock view as PDF.
    public function inventoryProductsPdf()
    {
        $rows = Product::query()
            ->with(['category', 'batches' => fn ($query) => $query->where('is_active', true)])
            ->where('status', 'Y')
            ->orderBy('product_name')
            ->get()
            ->map(fn ($product) => [
                'Product' => $product->display_name,
                'Category' => $product->category?->name ?: '-',
                'Formulation' => $product->formulation ?: '-',
                'Unit' => $product->unit ?: '-',
                'Reorder Level' => $product->effective_reorder_level,
                'Current Stock' => $product->batches->sum('quantity_available'),
                'Status' => $product->is_active ? 'Active' : 'Inactive',
            ]);

        return $this->downloadTablePdf('inventory-products.pdf', 'Inventory Product List', 'Live stock overview by product', ['Product', 'Category', 'Formulation', 'Unit', 'Reorder Level', 'Current Stock', 'Status'], $rows);
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

    // Export inventory batches as PDF.
    public function inventoryBatchesPdf(Request $request)
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
                'Product' => $batch->product?->display_name ?: '-',
                'Batch No' => $batch->batch_number,
                'Supplier' => $batch->supplier?->supplier_name ?: '-',
                'Expiry Date' => Batch::makeExpiryDate($batch->expiry_date)?->format('M j, Y') ?: '-',
                'Qty Available' => $batch->quantity_available,
                'Storage' => $batch->storage_location ?: '-',
            ]);

        return $this->downloadTablePdf('inventory-batches.pdf', 'Inventory Batch List', 'Batch-wise stock and expiry view', ['Product', 'Batch No', 'Supplier', 'Expiry Date', 'Qty Available', 'Storage'], $rows);
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

    // Export purchase history as PDF.
    public function purchaseHistoryPdf(Request $request)
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
                'Supplier' => $order->supplier?->supplier_name ?: '-',
                'Date' => $order->order_date_show,
                'Status' => $order->status_label,
                'Payment' => $order->payment_label,
                'Items' => $order->items->count(),
                'Total' => number_format((float) $order->total_amount, 2),
                'Due' => number_format((float) $order->outstanding_amount, 2),
            ]);

        return $this->downloadTablePdf('purchase-history.pdf', 'Purchase History Report', 'Purchase order report with filters applied', ['Reference', 'Supplier', 'Date', 'Status', 'Payment', 'Items', 'Total', 'Due'], $rows);
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

    // Export supplier performance as PDF.
    public function supplierPerformancePdf()
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
                'Total Value' => number_format((float) $supplier->total_value, 2),
                'Outstanding' => number_format((float) $supplier->outstanding_amount, 2),
            ]);

        return $this->downloadTablePdf('supplier-performance.pdf', 'Supplier Performance Report', 'Supplier order value and outstanding summary', ['Supplier', 'Total Orders', 'Total Value', 'Outstanding'], $rows);
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
                'Added Date' => $user->created_at?->format('M j, Y'),
            ]);

        return $this->downloadExcel('users.xlsx', $rows);
    }

    // Export user list as PDF.
    public function usersPdf()
    {
        $rows = User::query()
            ->with('roles')
            ->latest('id')
            ->get()
            ->map(fn ($user) => [
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->getRoleNames()->implode(', '),
                'Added Date' => $user->created_at?->format('M j, Y'),
            ]);

        return $this->downloadTablePdf('users.pdf', 'User List', 'System users with assigned roles', ['Name', 'Email', 'Role', 'Added Date'], $rows);
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

    // Export low stock report as PDF.
    public function lowStockPdf()
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
                'Category' => $item->category_name ?: '-',
                'Reorder Level' => $item->reorder_level,
                'Current Stock' => $item->current_stock,
                'Deficit' => max(0, (int) $item->reorder_level - (int) $item->current_stock),
            ]);

        return $this->downloadTablePdf('low-stock-report.pdf', 'Low Stock Report', 'Products below or equal to reorder level', ['Product', 'Category', 'Reorder Level', 'Current Stock', 'Deficit'], $rows);
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
                    'Expiry Date' => $expiryDate->format('M j, Y'),
                    'Days Left' => $today->diffInDays($expiryDate, false),
                    'Qty' => $batch->quantity_available,
                    'Location' => $batch->storage_location,
                ];
            })
            ->filter()
            ->values();

        return $this->downloadExcel('expiry-alert-report.xlsx', $rows);
    }

    // Export expiry alert report as PDF.
    public function expiryAlertPdf()
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
                    'Product' => $batch->product?->display_name ?: '-',
                    'Batch No' => $batch->batch_number,
                    'Supplier' => $batch->supplier?->supplier_name ?: '-',
                    'Expiry Date' => $expiryDate->format('M j, Y'),
                    'Days Left' => $today->diffInDays($expiryDate, false),
                    'Qty' => $batch->quantity_available,
                    'Location' => $batch->storage_location ?: '-',
                ];
            })
            ->filter()
            ->values();

        return $this->downloadTablePdf('expiry-alert-report.pdf', 'Expiry Alert Report', 'Batch-wise expiry tracking report', ['Product', 'Batch No', 'Supplier', 'Expiry Date', 'Days Left', 'Qty', 'Location'], $rows);
    }

    // Export the customer and institution master list.
    public function customers()
    {
        $rows = Customer::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($customer) => [
                'Name' => $customer->name,
                'Type' => ucfirst((string) $customer->party_type),
                'Contact Person' => $customer->contact_person,
                'Phone' => $customer->phone,
                'Email' => $customer->email,
                'Credit Limit' => $customer->credit_limit,
                'Balance' => $customer->current_balance,
                'Status' => $customer->is_active ? 'Active' : 'Inactive',
            ]);

        return $this->downloadExcel('customers.xlsx', $rows);
    }

    // Export customer and institution list as PDF.
    public function customersPdf()
    {
        $rows = Customer::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($customer) => [
                'Name' => $customer->name,
                'Type' => ucfirst((string) $customer->party_type),
                'Contact Person' => $customer->contact_person ?: '-',
                'Phone' => $customer->phone ?: '-',
                'Email' => $customer->email ?: '-',
                'Credit Limit' => number_format((float) $customer->credit_limit, 2),
                'Balance' => number_format((float) $customer->current_balance, 2),
                'Status' => $customer->is_active ? 'Active' : 'Inactive',
            ]);

        return $this->downloadTablePdf('customers.pdf', 'Party Management List', 'Customer and institution master records', ['Name', 'Type', 'Contact Person', 'Phone', 'Email', 'Credit Limit', 'Balance', 'Status'], $rows);
    }

    // Export the sales invoice list with payment status and due amount.
    public function salesInvoices(Request $request)
    {
        $rows = SalesInvoice::query()
            ->with('customer')
            ->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('customer_id', $request->customer_id);
            })
            ->when($request->filled('sale_type'), function ($query) use ($request) {
                $query->where('sale_type', $request->sale_type);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('payment_status'), function ($query) use ($request) {
                $query->where('payment_status', $request->payment_status);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('invoice_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('invoice_date', '<=', $request->date_to);
            })
            ->latest('invoice_date')
            ->get()
            ->map(fn ($invoice) => [
                'Reference' => $invoice->reference,
                'Party' => $invoice->customer?->name,
                'Date' => $invoice->invoice_date_show,
                'Sale Type' => $invoice->sale_type_label,
                'Status' => $invoice->status_label,
                'Payment' => $invoice->payment_label,
                'Total' => $invoice->total_amount,
                'Paid' => $invoice->paid_amount,
                'Due' => $invoice->due_amount,
            ]);

        return $this->downloadExcel('sales-invoices.xlsx', $rows);
    }

    // Export the expense tracking list.
    public function expenses()
    {
        $rows = Expense::query()
            ->latest('expense_date')
            ->get()
            ->map(fn ($expense) => [
                'Date' => $expense->expense_date_show,
                'Category' => $expense->category,
                'Vendor' => $expense->vendor_name,
                'Payment Mode' => ucfirst((string) $expense->payment_mode),
                'Amount' => $expense->amount,
                'Notes' => $expense->notes,
            ]);

        return $this->downloadExcel('expenses.xlsx', $rows);
    }

    // Export the general ledger rows.
    public function ledger(Request $request)
    {
        $rows = AccountTransaction::query()
            ->with(['customer', 'supplier', 'creator'])
            ->when($request->filled('party_type'), function ($query) use ($request) {
                $query->where('party_type', $request->party_type);
            })
            ->when($request->filled('account_type'), function ($query) use ($request) {
                $query->where('account_type', $request->account_type);
            })
            ->when($request->filled('entry_type'), function ($query) use ($request) {
                $query->where('entry_type', $request->entry_type);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            })
            ->latest('transaction_date')
            ->get()
            ->map(fn ($transaction) => [
                'Date' => $transaction->transaction_date_show,
                'Reference' => $transaction->reference_type ? $transaction->reference_type . ' #' . $transaction->reference_id : '-',
                'Party' => $transaction->party_name,
                'Account' => $transaction->account_label,
                'Entry' => $transaction->entry_label,
                'Amount' => $transaction->amount,
                'Notes' => $transaction->notes,
            ]);

        return $this->downloadExcel('ledger.xlsx', $rows);
    }

    // Export the general ledger as a clean PDF for accountant use and printing.
    public function ledgerPdf(Request $request)
    {
        $transactions = $this->financeTransactionQuery($request)->latest('transaction_date')->latest('id')->get();
        $filters = $request->only(['party_type', 'account_type', 'entry_type', 'date_from', 'date_to']);
        $accountCatalog = collect($this->accountCatalog())->keyBy('key');

        return $this->downloadPdf(
            'ledger.pdf',
            'backend.finance.pdf.ledger',
            [
                'transactions' => $transactions,
                'filters' => $filters,
                'summary' => $this->summarizeTransactions($transactions),
                'accountCatalog' => $accountCatalog,
            ],
            'a4',
            'landscape'
        );
    }

    // Export the trial balance summary table.
    public function trialBalance()
    {
        $rows = AccountTransaction::query()
            ->selectRaw('account_type, entry_type, SUM(amount) as total_amount')
            ->groupBy('account_type', 'entry_type')
            ->get()
            ->groupBy('account_type')
            ->map(function ($items, $accountType) {
                $debit = (float) ($items->firstWhere('entry_type', 'debit')?->total_amount ?? 0);
                $credit = (float) ($items->firstWhere('entry_type', 'credit')?->total_amount ?? 0);

                return [
                    'Account' => ucwords(str_replace('_', ' ', $accountType)),
                    'Debit' => $debit,
                    'Credit' => $credit,
                    'Difference' => round($debit - $credit, 2),
                ];
            })
            ->values();

        return $this->downloadExcel('trial-balance.xlsx', $rows);
    }

    // Export the same trial balance rows as a finance friendly PDF.
    public function trialBalancePdf()
    {
        $transactionSummary = $this->transactionSummaryByAccount();

        $rows = collect($this->accountCatalog())
            ->map(function (array $account) use ($transactionSummary) {
                return $this->makeAccountSummaryRow($account, $transactionSummary->get($account['key'], collect()));
            })
            ->filter(function (array $row) {
                return $row['debit'] > 0 || $row['credit'] > 0;
            })
            ->values();

        $summary = [
            'debit' => round((float) $rows->sum('debit'), 2),
            'credit' => round((float) $rows->sum('credit'), 2),
            'difference' => round((float) abs($rows->sum('debit') - $rows->sum('credit')), 2),
        ];

        return $this->downloadPdf(
            'trial-balance.pdf',
            'backend.finance.pdf.trial-balance',
            [
                'rowGroups' => $rows->groupBy('group'),
                'summary' => $summary,
            ],
            'a4',
            'landscape'
        );
    }

    // Export the cash book rows.
    public function cashBook(Request $request)
    {
        $rows = AccountTransaction::query()
            ->with(['customer', 'supplier', 'creator'])
            ->where('account_type', 'cash')
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            })
            ->latest('transaction_date')
            ->get()
            ->map(fn ($transaction) => [
                'Date' => $transaction->transaction_date_show,
                'Reference' => $transaction->reference_type ? $transaction->reference_type . ' #' . $transaction->reference_id : '-',
                'Party' => $transaction->party_name,
                'Entry' => $transaction->entry_label,
                'Amount' => $transaction->amount,
                'Notes' => $transaction->notes,
            ]);

        return $this->downloadExcel('cash-book.xlsx', $rows);
    }

    // Export cash book rows with separate debit and credit columns for print and filing.
    public function cashBookPdf(Request $request)
    {
        $transactions = $this->financeTransactionQuery($request)
            ->where('account_type', 'cash')
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return $this->downloadPdf(
            'cash-book.pdf',
            'backend.finance.pdf.transaction-book',
            [
                'title' => 'Cash Book',
                'subtitle' => 'Cash inflow and outflow report',
                'transactions' => $transactions,
                'summary' => $this->summarizeTransactions($transactions),
            ],
            'a4',
            'landscape'
        );
    }

    // Export the bank book rows.
    public function bankBook(Request $request)
    {
        $rows = AccountTransaction::query()
            ->with(['customer', 'supplier', 'creator'])
            ->where('account_type', 'bank')
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            })
            ->latest('transaction_date')
            ->get()
            ->map(fn ($transaction) => [
                'Date' => $transaction->transaction_date_show,
                'Reference' => $transaction->reference_type ? $transaction->reference_type . ' #' . $transaction->reference_id : '-',
                'Party' => $transaction->party_name,
                'Entry' => $transaction->entry_label,
                'Amount' => $transaction->amount,
                'Notes' => $transaction->notes,
            ]);

        return $this->downloadExcel('bank-book.xlsx', $rows);
    }

    // Export bank book rows with separate debit and credit columns for finance review.
    public function bankBookPdf(Request $request)
    {
        $transactions = $this->financeTransactionQuery($request)
            ->where('account_type', 'bank')
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return $this->downloadPdf(
            'bank-book.pdf',
            'backend.finance.pdf.transaction-book',
            [
                'title' => 'Bank Book',
                'subtitle' => 'Bank inflow and outflow report',
                'transactions' => $transactions,
                'summary' => $this->summarizeTransactions($transactions),
            ],
            'a4',
            'landscape'
        );
    }

    // Export the GST report rows.
    public function gstReport(Request $request)
    {
        $rows = SalesInvoice::query()
            ->with('customer')
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('invoice_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('invoice_date', '<=', $request->date_to);
            })
            ->latest('invoice_date')
            ->get()
            ->map(fn ($invoice) => [
                'Invoice' => $invoice->reference,
                'Party' => $invoice->customer?->name,
                'Date' => $invoice->invoice_date_show,
                'Taxable Sales' => $invoice->subtotal,
                'Tax' => $invoice->tax_amount,
                'Total' => $invoice->total_amount,
                'Payment' => $invoice->payment_label,
            ]);

        return $this->downloadExcel('gst-report.xlsx', $rows);
    }

    // Export GST report as a tax summary PDF.
    public function gstReportPdf(Request $request)
    {
        $invoices = SalesInvoice::query()
            ->with('customer')
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('invoice_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('invoice_date', '<=', $request->date_to);
            })
            ->latest('invoice_date')
            ->latest('id')
            ->get();

        return $this->downloadPdf(
            'gst-report.pdf',
            'backend.finance.pdf.gst-report',
            [
                'invoices' => $invoices,
                'filters' => $request->only(['date_from', 'date_to']),
                'summary' => [
                    'taxable_sales' => $invoices->sum('subtotal'),
                    'tax_amount' => $invoices->sum('tax_amount'),
                    'total_sales' => $invoices->sum('total_amount'),
                ],
            ]
        );
    }

    // Export the account tree so finance users can share the chart of accounts as a PDF.
    public function accountTreePdf()
    {
        $transactionSummary = $this->transactionSummaryByAccount();

        $groups = collect($this->accountCatalog())
            ->groupBy('group')
            ->map(function (Collection $accounts, string $groupName) use ($transactionSummary) {
                $rows = $accounts->map(function (array $account) use ($transactionSummary) {
                    return $this->makeAccountSummaryRow($account, $transactionSummary->get($account['key'], collect()));
                })->values();

                return [
                    'name' => $groupName,
                    'rows' => $rows,
                    'debit' => round((float) $rows->sum('debit'), 2),
                    'credit' => round((float) $rows->sum('credit'), 2),
                ];
            })
            ->values();

        return $this->downloadPdf(
            'account-tree.pdf',
            'backend.finance.pdf.account-tree',
            [
                'groups' => $groups,
                'summary' => [
                    'accounts' => $groups->sum(fn (array $group) => count($group['rows'])),
                    'debit' => round((float) $groups->sum('debit'), 2),
                    'credit' => round((float) $groups->sum('credit'), 2),
                ],
            ]
        );
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
                'Purchase Date' => $batch->purchase?->purchase_date_show ?? '-',
                'Expiry' => Batch::makeExpiryDate($batch->expiry_date)?->format('M j, Y') ?? '-',
                'Qty' => $batch->quantity,
                'Price' => $batch->purchase_price,
                'Subtotal' => $batch->subtotal,
            ]);

        return $this->downloadExcel('product-batch-history.xlsx', $rows);
    }

    // Keep finance report filters in one place so excel and pdf stay matched.
    private function financeTransactionQuery(Request $request)
    {
        return AccountTransaction::query()
            ->with(['customer', 'supplier', 'creator'])
            ->when($request->filled('party_type'), function ($query) use ($request) {
                $query->where('party_type', $request->party_type);
            })
            ->when($request->filled('account_type'), function ($query) use ($request) {
                $query->where('account_type', $request->account_type);
            })
            ->when($request->filled('entry_type'), function ($query) use ($request) {
                $query->where('entry_type', $request->entry_type);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            });
    }

    // Small summary cards and pdf headers use the same totals.
    private function summarizeTransactions(Collection $transactions): array
    {
        return [
            'debit' => round((float) $transactions->where('entry_type', 'debit')->sum('amount'), 2),
            'credit' => round((float) $transactions->where('entry_type', 'credit')->sum('amount'), 2),
            'cash' => round((float) $transactions->where('account_type', 'cash')->sum('amount'), 2),
            'bank' => round((float) $transactions->where('account_type', 'bank')->sum('amount'), 2),
            'receivable' => round((float) $transactions->where('account_type', 'receivable')->sum('amount'), 2),
            'payable' => round((float) $transactions->where('account_type', 'payable')->sum('amount'), 2),
        ];
    }

    // Group finance rows by account type once so trial balance and account tree match each other.
    private function transactionSummaryByAccount(): Collection
    {
        return AccountTransaction::query()
            ->selectRaw('account_type, entry_type, SUM(amount) as total_amount')
            ->groupBy('account_type', 'entry_type')
            ->get()
            ->groupBy('account_type');
    }

    // Build one clean finance row with closing balance information.
    private function makeAccountSummaryRow(array $account, Collection $items): array
    {
        $debit = round((float) ($items->firstWhere('entry_type', 'debit')?->total_amount ?? 0), 2);
        $credit = round((float) ($items->firstWhere('entry_type', 'credit')?->total_amount ?? 0), 2);
        $closing = $this->resolveClosingBalance($debit, $credit, $account['nature']);

        return [
            'key' => $account['key'],
            'code' => $account['code'],
            'name' => $account['name'],
            'group' => $account['group'],
            'nature' => strtoupper($account['nature']),
            'debit' => $debit,
            'credit' => $credit,
            'closing_amount' => $closing['amount'],
            'closing_side' => $closing['side'],
        ];
    }

    // Closing side changes based on the normal account nature, so we keep that logic here.
    private function resolveClosingBalance(float $debit, float $credit, string $nature): array
    {
        if ($nature === 'debit') {
            $net = round($debit - $credit, 2);

            return [
                'amount' => abs($net),
                'side' => $net >= 0 ? 'Dr' : 'Cr',
            ];
        }

        $net = round($credit - $debit, 2);

        return [
            'amount' => abs($net),
            'side' => $net >= 0 ? 'Cr' : 'Dr',
        ];
    }

    // Keep finance account names stable across pages and exports.
    private function accountCatalog(): array
    {
        return [
            ['key' => 'cash', 'code' => '1100', 'name' => 'Cash in Hand', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'bank', 'code' => '1200', 'name' => 'Bank Account', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'receivable', 'code' => '1300', 'name' => 'Accounts Receivable', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'inventory', 'code' => '1400', 'name' => 'Inventory Stock', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'tax_receivable', 'code' => '1500', 'name' => 'Tax Receivable', 'group' => 'Assets', 'nature' => 'debit'],
            ['key' => 'payable', 'code' => '2100', 'name' => 'Accounts Payable', 'group' => 'Liabilities', 'nature' => 'credit'],
            ['key' => 'tax_payable', 'code' => '2200', 'name' => 'Tax Payable', 'group' => 'Liabilities', 'nature' => 'credit'],
            ['key' => 'capital', 'code' => '3100', 'name' => 'Capital', 'group' => 'Equity', 'nature' => 'credit'],
            ['key' => 'income', 'code' => '4100', 'name' => 'Sales Income', 'group' => 'Income', 'nature' => 'credit'],
            ['key' => 'other_income', 'code' => '4200', 'name' => 'Other Income', 'group' => 'Income', 'nature' => 'credit'],
            ['key' => 'expense', 'code' => '5100', 'name' => 'Operating Expense', 'group' => 'Expenses', 'nature' => 'debit'],
            ['key' => 'purchase_return', 'code' => '5200', 'name' => 'Purchase Return / Adjustment', 'group' => 'Expenses', 'nature' => 'debit'],
        ];
    }
}
