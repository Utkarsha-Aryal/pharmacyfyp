<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'orderedBy', 'items.product']);

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

        return view('backend.purchase-order.index', [
            'orders' => $query->latest('order_date')->get(),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'filters' => $request->only(['supplier_id', 'status', 'payment_status', 'date_from', 'date_to']),
            'summary' => [
                'pending' => PurchaseOrder::query()->where('status', 'pending')->count(),
                'approved' => PurchaseOrder::query()->where('status', 'approved')->count(),
                'received' => PurchaseOrder::query()->where('status', 'received')->count(),
                'this_month' => PurchaseOrder::query()->whereMonth('order_date', now()->month)->whereYear('order_date', now()->year)->sum('total_amount'),
                'all_time' => PurchaseOrder::query()->sum('total_amount'),
            ],
        ]);
    }

    public function create()
    {
        return view('backend.purchase-order.create', [
            'reference' => PurchaseOrder::makeReference(),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'products' => Product::query()->where('status', 'Y')->orderBy('product_name')->get(),
        ]);
    }

    public function supplierOptions(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $suppliers = Supplier::query()
            ->where('status', 'Y')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($builder) use ($keyword) {
                    $builder->where('supplier_name', 'like', '%' . $keyword . '%')
                        ->orWhere('contact_person', 'like', '%' . $keyword . '%')
                        ->orWhere('phone_number', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('supplier_name')
            ->limit(20)
            ->get()
            ->map(fn ($supplier) => [
                'id' => $supplier->id,
                'text' => $supplier->supplier_name . (!empty($supplier->contact_person) ? ' - ' . $supplier->contact_person : ''),
            ])
            ->values();

        return response()->json(['results' => $suppliers]);
    }

    public function productOptions(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $products = Product::query()
            ->with('category')
            ->where('status', 'Y')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($builder) use ($keyword) {
                    $builder->where('product_name', 'like', '%' . $keyword . '%')
                        ->orWhere('name', 'like', '%' . $keyword . '%')
                        ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('product_name')
            ->limit(20)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'text' => $product->display_name . ($product->category?->name ? ' - ' . $product->category->name : ''),
            ])
            ->values();

        return response()->json(['results' => $products]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255', 'unique:purchase_orders,reference'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $total = 0;

            foreach ($validated['items'] as $item) {
                $total += round(((float) $item['quantity_ordered']) * ((float) $item['unit_price']), 2);
            }

            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $paymentStatus = 'unpaid';

            if ($paidAmount > 0 && $paidAmount < $total) {
                $paymentStatus = 'partial';
            } elseif ($paidAmount >= $total && $total > 0) {
                $paymentStatus = 'paid';
            }

            $order = PurchaseOrder::create([
                'reference' => $validated['reference'],
                'supplier_id' => $validated['supplier_id'],
                'ordered_by' => $request->user()->id,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => round(((float) $item['quantity_ordered']) * ((float) $item['unit_price']), 2),
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'type' => 'success',
                'message' => 'Purchase order saved successfully.',
                'redirect' => route('admin.purchase-orders.index'),
            ]);
        }

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order saved successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return view('backend.purchase-order.show', [
            'order' => $purchaseOrder->load(['supplier', 'orderedBy', 'items.product']),
        ]);
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return back()->with('error', 'Only pending purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase order approved.');
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'approved') {
            return back()->with('error', 'Only approved purchase orders can be received.');
        }

        return view('backend.purchase-order.receive', [
            'order' => $purchaseOrder->load(['supplier', 'items.product']),
        ]);
    }

    public function receiveStore(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'approved') {
            return back()->with('error', 'Only approved purchase orders can be received.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity_received' => ['required', 'integer', 'min:1'],
            'items.*.batch_number' => ['required', 'string', 'max:255'],
            'items.*.expiry_date' => ['required', 'date'],
            'items.*.manufacturing_date' => ['nullable', 'date'],
            'items.*.storage_location' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated, $request) {
            $purchaseOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            foreach ($purchaseOrder->items as $index => $item) {
                $receivedRow = $validated['items'][$item->id] ?? null;

                if (!$receivedRow) {
                    throw ValidationException::withMessages([
                        'items' => 'Please fill all received item rows.',
                    ]);
                }

                $receivedQty = (int) $receivedRow['quantity_received'];
                $unitPrice = (float) $item->unit_price;
                $subtotal = round($receivedQty * $unitPrice, 2);

                $item->update([
                    'quantity_received' => $receivedQty,
                    'batch_number' => $receivedRow['batch_number'],
                    'expiry_date' => $receivedRow['expiry_date'],
                    'subtotal' => $subtotal,
                ]);

                Batch::create([
                    'product_id' => $item->product_id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'purchase_order_item_id' => $item->id,
                    'batch_number' => $receivedRow['batch_number'],
                    'manufacturing_date' => $receivedRow['manufacturing_date'] ?? null,
                    'expiry_date' => $receivedRow['expiry_date'],
                    'quantity_received' => $receivedQty,
                    'quantity_available' => $receivedQty,
                    'purchase_price' => $unitPrice,
                    'storage_location' => $receivedRow['storage_location'] ?? null,
                    'is_active' => true,
                ]);
            }

            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now(),
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'Goods received and batches created successfully.');
    }

    public function updatePayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $purchaseOrder->update([
            'payment_status' => $validated['payment_status'],
            'paid_amount' => $validated['paid_amount'] ?? 0,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Payment status updated successfully.');
    }
}
