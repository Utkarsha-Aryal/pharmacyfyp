<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseReference;



class PurchaseController extends Controller
{
   public function index()
{
    // Generate a unique reference number
    $referenceNo = 'PURCH-' . now()->format('YmdHis') . rand(100, 999);

    // Insert into the purchase_references table
    $reference = new PurchaseReference();
    $reference->reference_no = $referenceNo;
    $reference->used = 'N';
    $reference->save();

    // Pass it to the view if needed
    return view('backend.purchase.index', compact('reference'));
}

    public function list(Request $request){
        $post = $request->all();
    }

    public function addpurchase()
    {
        $supplier = Supplier::all();
        $product = Product::all();
        $data = ['supplier'=>$supplier,
                'product'=>$product 
            ];
        return view('backend.purchase.addpurchase',$data);
    }

    public function save(Request $request)
    {
                try {
            $post = $request->all();
            $type = 'success';
            $message = 'Records saved successfully';
            DB::beginTransaction();
            $result = ProductBatch::saveProductBatch($post);
            if (!$result) {
                throw new Exception('Could not save record', 1);
            }
            DB::commit();
        } catch (ValidationException $e) {
            $type = 'error';
            $message = $e->getMessage();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = $this->queryMessage;
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
        return response()->json(['type' => $type, 'message' => $message]);


    }
}
