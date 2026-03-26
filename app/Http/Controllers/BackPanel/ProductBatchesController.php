<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use Exception;
use Illuminate\Http\Request;

class ProductBatchesController extends Controller
{
    public function index($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        return view('backend.batch.index', [
            'product' => $product,
            'batchCount' => $product->productBatches()->where('status', 'Y')->count(),
            'stockQty' => $product->productBatches()->where('status', 'Y')->sum('quantity'),
        ]);
    }

    public function list(Request $request)
    {
        try {
            $post = $request->all();
            $data = ProductBatch::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data['totalfilteredrecs'] > 0 ? $data['totalfilteredrecs'] : $data['totalrecs']);
            $totalrecs = $data['totalrecs'];
            unset($data['totalfilteredrecs']);
            unset($data['totalrecs']);

            foreach ($data as $row) {
                $array[$i]['sno'] = $i + 1;
                $array[$i]['batch_no'] = $row->batch_no ?: '-';
                $array[$i]['reference_no'] = $row->reference?->reference_no ?? '-';
                $array[$i]['supplier'] = $row->supplier?->supplier_name ?? '-';
                $array[$i]['purchase_date'] = $row->purchase?->purchase_date ?? '-';
                $array[$i]['expiry_date'] = $row->expiry_date ?: '-';
                $array[$i]['quantity'] = $row->quantity;
                $array[$i]['purchase_price'] = number_format((float) $row->purchase_price, 2);
                $array[$i]['subtotal'] = number_format((float) $row->subtotal, 2);
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
}
