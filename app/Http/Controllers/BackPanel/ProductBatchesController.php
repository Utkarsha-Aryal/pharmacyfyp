<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Common;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;


class ProductBatchesController extends Controller
{

   public function index($slug)
   {
    $product = Product::where('slug',$slug)->first();


    return view('backend.product.batch',['product' =>$product]);
    }

     public function save(Request $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = 'Records saved successfully';
            DB::beginTransaction();
            $result = ProductBatch::saveData($post);
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

     public function list(Request $request)
    {
        try {
            $post = $request->all();
            $data = ProductBatch::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data["totalfilteredrecs"] > 0 ? $data["totalfilteredrecs"] : $data["totalrecs"]);
            $totalrecs = $data["totalrecs"];
            unset($data["totalfilteredrecs"]);
            unset($data["totalrecs"]);
            foreach ($data as $row) {
                $array[$i]["sno"] = $i + 1;
                $array[$i]["batch_no"] = $row->batch_no;
                $array[$i]["expiry_date"] = $row->expiry_date;
                $array[$i]["quantity"] = $row->quantity;
                $array[$i]["purchase_price"] = $row->purchase_price;
                $action = '';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    // $action .= ' <a href="javascript:;" class="viewCategory" title="View Data" data-id="' . $row->id . '"><i class="fa-solid fa-eye" style="color: #008f47;"></i></i></a> |';
                    // $action .= '<span style="margin-left: 3px;"></span>';
                    $action .= '<a href="javascript:;" class="editCategory" name="Edit Data" data-id="' . $row->id . '" data-batch_no="' . $row->batch_no . '" data-expiry_date="' . $row->expiry_date . '" data-quantity="' . $row->quantity . '" data-purchase_price="' . $row->purchase_price . '" ><i class="fa-solid fa-pen-to-square text-primary"></i></a> | ';
                } else if (!empty($post['type']) && $post['type'] == 'trashed') {
                    $action .= '<a href="javascript:;" class="restoreCategory" title="Restore Data" data-id="' . $row->id . '"><i class="fa-solid fa-undo text-success"></i></a> | ';
                }
                // $category_id = $row->id;
                // $categoryCheck = Product::where('category_id', $category_id)->first();
               
                $action .= ' <a href="javascript:;" class="deletecategory" name="Delete Data" data-id="' . $row->id . '"><i class="fa fa-trash text-danger"></i></a>';
                $array[$i]["action"] = $action;
                $i++;
            }
            if (!$filtereddata)
                $filtereddata = 0;
            if (!$totalrecs)
                $totalrecs = 0;
        } catch (QueryException $e) {
            dd($e);
            $array = [];
            $totalrecs = 0;
            $filtereddata = 0;
        } catch (Exception $e) {
            dd($e);

            $array = [];
            $totalrecs = 0;
            $filtereddata = 0;
        }
        return response()->json(array("recordsFiltered" => $filtereddata, "recordsTotal" => $totalrecs, "data" => $array));
    }

     public function delete(Request $request)
    {
        try {
            $type = 'success';
            $message = 'Record deleted successfully';
            $post = $request->all();
            $class = new ProductBatch();
            DB::beginTransaction();
            $result = Common::deleteData($post, $class);
            if (!$result) {
                throw new Exception("Couldn't delete record", 1);
            }
            DB::commit();
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

     public function restore(Request $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = "ProductBatch restored successfully";
            DB::beginTransaction();
            $result = ProductBatch::restoreData($post);
            if (!$result) {
                throw new Exception("Could not restore ProductBatch. Please try again.", 1);
            }
            DB::commit();
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

