<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
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
            ->leftJoin('product_batches', function ($join) {
                $join->on('products.id', '=', 'product_batches.product_id')
                    ->where('product_batches.status', 'Y');
            })
            ->where('products.status', 'Y')
            ->whereNotNull('products.alert_quantity')
            ->groupBy('products.id', 'products.product_name', 'products.alert_quantity', 'categories.name')
            ->selectRaw('products.product_name, products.alert_quantity, categories.name as category_name, COALESCE(SUM(product_batches.quantity), 0) as current_stock')
            ->havingRaw('COALESCE(SUM(product_batches.quantity), 0) <= products.alert_quantity')
            ->orderBy('current_stock')
            ->get()
            ->map(fn ($item) => [
                'Product' => $item->product_name,
                'Category' => $item->category_name,
                'Alert Qty' => $item->alert_quantity,
                'Current Stock' => $item->current_stock,
            ]);

        return $this->downloadExcel('low-stock-report.xlsx', $rows);
    }

    public function expiryAlert()
    {
        $today = Carbon::today();
        $nearDate = $today->copy()->addDays(60);

        $rows = ProductBatch::query()
            ->with(['product', 'supplier', 'reference'])
            ->where('status', 'Y')
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($batch) use ($today, $nearDate) {
                $expiryDate = ProductBatch::makeExpiryDate($batch->expiry_date);

                if (!$expiryDate || $expiryDate->gt($nearDate)) {
                    return null;
                }

                return [
                    'Product' => $batch->product?->product_name,
                    'Batch No' => $batch->batch_no,
                    'Supplier' => $batch->supplier?->supplier_name,
                    'Reference' => $batch->reference?->reference_no,
                    'Expiry Date' => $expiryDate->format('Y-m-d'),
                    'Qty' => $batch->quantity,
                    'Status' => $expiryDate->lt($today) ? 'Expired' : 'Near Expiry',
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
