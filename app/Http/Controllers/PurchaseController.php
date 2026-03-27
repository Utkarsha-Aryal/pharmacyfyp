<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseReference;
use App\Models\Supplier;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $selectedSupplier = $request->input('supplier_id');
        $selectedOrderStatus = $request->input('order_status');

        $purchaseQuery = Purchase::query()->where('status', 'Y');

        if (!empty($selectedSupplier)) {
            $purchaseQuery->where('supplier_id', $selectedSupplier);
        }

        if (!empty($selectedOrderStatus)) {
            $purchaseQuery->where('order_status', $selectedOrderStatus);
        }

        return view('purchase.index', [
            'suppliers' => Supplier::where('status', 'Y')->orderBy('supplier_name')->get(),
            'selectedSupplier' => $selectedSupplier,
            'selectedOrderStatus' => $selectedOrderStatus,
            'purchaseCount' => (clone $purchaseQuery)->count(),
            'purchaseTotal' => (clone $purchaseQuery)->sum('grand_total'),
            'paidTotal' => (clone $purchaseQuery)->sum('paid_amount'),
            'dueTotal' => (clone $purchaseQuery)->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as due_total')->value('due_total'),
        ]);
    }

    public function list(Request $request)
    {
        try {
            $post = $request->all();
            $data = Purchase::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data['totalfilteredrecs'] > 0 ? $data['totalfilteredrecs'] : $data['totalrecs']);
            $totalrecs = $data['totalrecs'];
            unset($data['totalfilteredrecs']);
            unset($data['totalrecs']);

            foreach ($data as $row) {
                $array[$i]['sno'] = $i + 1;
                $array[$i]['reference_no'] = $row->reference?->reference_no ?? '-';
                $array[$i]['invoice_no'] = $row->invoice_no ?: '-';
                $array[$i]['supplier'] = $row->supplier?->supplier_name ?? '-';
                $array[$i]['items_count'] = $row->batches_count;
                $array[$i]['g_total'] = number_format((float) $row->grand_total, 2);
                $array[$i]['paid'] = number_format((float) $row->paid_amount, 2);
                $array[$i]['due'] = number_format($row->due_amount, 2);
                $array[$i]['order_status'] = $this->statusBadgeHtml($row->order_status);
                $array[$i]['added_date'] = $row->purchase_date_show;
                $array[$i]['action'] = '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn viewPurchaseBillBtn" title="View Bill Summary" data-reference="' . e($row->reference?->reference_no ?? '-') . '" data-invoice="' . e($row->invoice_no ?: '-') . '" data-supplier="' . e($row->supplier?->supplier_name ?? '-') . '" data-items="' . e($row->batches_count) . '" data-total="' . e(number_format((float) $row->grand_total, 2)) . '" data-paid="' . e(number_format((float) $row->paid_amount, 2)) . '" data-due="' . e(number_format($row->due_amount, 2)) . '" data-status="' . e($row->order_status_label) . '" data-date="' . e($row->purchase_date_show) . '" data-remarks="' . e($row->remarks ?: '-') . '"><i class="fa-solid fa-eye"></i></button>';
                $i++;
            }

            return response()->json([
                'recordsFiltered' => $filtereddata ?: 0,
                'recordsTotal' => $totalrecs ?: 0,
                'data' => $array,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'recordsFiltered' => 0,
                'recordsTotal' => 0,
                'data' => [],
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function addpurchase()
    {
        return view('purchase.create', [
            'supplier' => Supplier::where('status', 'Y')->orderBy('supplier_name')->get(),
            'product' => Product::where('status', 'Y')->orderBy('product_name')->get(),
            'reference' => PurchaseReference::makeNewReference(),
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
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'text' => $supplier->supplier_name . (!empty($supplier->contact_person) ? ' - ' . $supplier->contact_person : ''),
                ];
            })
            ->values();

        return response()->json([
            'results' => $suppliers,
        ]);
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
                        ->orWhere('generic_name', 'like', '%' . $keyword . '%')
                        ->orWhere('keywords', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('product_name')
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'text' => $product->product_name . ($product->category?->name ? ' - ' . $product->category->name : ''),
                ];
            })
            ->values();

        return response()->json([
            'results' => $products,
        ]);
    }

    public function save(Request $request)
    {
        try {
            $post = $request->validate([
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'reference_id' => ['required', 'exists:purchase_references,id'],
                'invoice_no' => ['nullable', 'string', 'max:255'],
                'purchase_date' => ['required', 'date'],
                'paid_amount' => ['nullable', 'numeric', 'min:0'],
                'remarks' => ['nullable', 'string'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'exists:products,id'],
                'items.*.batch_no' => ['nullable', 'string', 'max:255'],
                'items.*.expiry_date' => ['required', 'string', 'max:20'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            ]);

            DB::beginTransaction();

            $reference = PurchaseReference::where('id', $post['reference_id'])->lockForUpdate()->first();
            if (!$reference || $reference->used === 'Y') {
                throw new Exception('This purchase reference is already used. Please reload the page.', 1);
            }

            $grandTotal = 0;
            foreach ($post['items'] as $item) {
                $grandTotal += ((float) $item['quantity']) * ((float) $item['purchase_price']);
            }

            $purchase = Purchase::create([
                'supplier_id' => $post['supplier_id'],
                'reference_id' => $post['reference_id'],
                'invoice_no' => $post['invoice_no'] ?? null,
                'purchase_date' => $post['purchase_date'],
                'order_status' => 'received',
                'grand_total' => $grandTotal,
                'paid_amount' => $post['paid_amount'] ?? 0,
                'payment_status' => Purchase::resolvePaymentStatus($grandTotal, (float) ($post['paid_amount'] ?? 0)),
                'remarks' => $post['remarks'] ?? null,
            ]);

            $result = ProductBatch::savePurchaseItems($post);
            if (!$result) {
                throw new Exception('Could not save purchase items.', 1);
            }

            $reference->used = 'Y';
            $reference->save();

            DB::commit();

            return response()->json([
                'type' => 'success',
                'message' => 'Purchase saved successfully.',
                'redirect' => route('admin.purchase'),
                'purchase_id' => $purchase->id,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'type' => 'error',
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json([
                'type' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'type' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function statusBadgeHtml(?string $status): string
    {
        $class = match ($status) {
            'pending' => 'report-badge-warning',
            'approved' => 'report-badge-info',
            default => 'report-badge-success',
        };

        $label = match ($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            default => 'Received',
        };

        return '<span class="report-badge ' . $class . '">' . $label . '</span>';
    }
}
