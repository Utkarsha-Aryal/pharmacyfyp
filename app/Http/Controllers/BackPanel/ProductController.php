<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Models\Common;
use App\Models\Product;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProductController extends Controller
{
     public function index()
    {
        return view('backend.product.index');
    }


public function globalSearch(Request $request)
{
    $query = $request->input('query');

    $products = Product::where('product_name', 'like', '%' . $query . '%')
        ->limit(10)
        ->get();

    $html = '';

    foreach ($products as $product) {
        $imageUrl = asset('storage/' . $product->image); // Adjust path if needed

        $imageUrl = asset('storage/product/' . $product->image);
        $slug = $product->slug;

        $html .= '<a href="' . route('product',$slug) . '" class="list-group-item list-group-item-action d-flex align-items-center">'
            . '<img src="' . $imageUrl . '" alt="' . e($product->product_name) . '" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">'
            . '<span>' . e($product->product_name) . '</span>'
            . '</a>';

    }

    return response()->json(['html' => $html]);
}


   public function form(Request $request)
{
    try {
        $post = $request->all();
        $category = Category::all();
        $prevPost = [];

        if (!empty($post['id'])) {
            $prevPost = Product::find($post['id']);

            if (!$prevPost) {
                throw new \Exception("Couldn't find product details.", 1);
            }
        }

        // Prepare the base data array
        $data = [
            'category' => $category,
            'prevPost' => $prevPost,
        ];

        // Add image HTML based on existence
        if (!empty($prevPost) && $prevPost->image) {
            $data['image'] = '<img src="' . asset('/storage/product/' . $prevPost->image) . '" class="_image" height="160px" width="160px" alt="No image"/>';
        } else {
            $data['image'] = '<img src="' . asset('/no-image.jpg') . '" class="_image" height="160px" width="160px" alt="No image"/>';
        }

        $data['type'] = 'success';
        $data['message'] = 'Successfully got data.';
    } catch (QueryException $e) {
        $data['type'] = 'error';
        $data['message'] = $this->queryMessage;
    } catch (Exception $e) {
        $data['type'] = 'error';
        $data['message'] = $e->getMessage();
    }

    return view('backend.product.form', $data);
}


    public function save(Request $request)
    {
        try{
        $post = $request->all();
        $type = 'success';
        $message = 'Product added sucessfully';
         DB::beginTransaction();
            $result = Product::saveData($post);
            if (!$result) {
                throw new Exception('Could not save record', 1);
            }
            DB::commit();
        }catch(QueryException $e){
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();

        }catch(Exception $e){
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
            $data = Product::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data['totalfilteredrecs'] > 0 ? $data['totalfilteredrecs'] : $data['totalrecs']);
            $totalrecs = $data['totalrecs'];
            unset($data['totalfilteredrecs']);
            unset($data['totalrecs']);
            foreach ($data as $row) {
                $discount = $row->discount;
                $mrp = $row->mrp;
                $discount = $row->discount;
                $price = round($mrp - ($mrp * $discount / 100), 2);

                $array[$i]['sno'] = $i + 1;
                $array[$i]['product_name'] = $row->product_name;
                $array[$i]['category'] = $row->category->name;
                $array[$i]['generic_name'] = $row->generic_name;
                $array[$i]['description'] = $row->description;
                $array[$i]['keywords'] = Str::limit($row->keywords, 25, '...');
                $array[$i]['display_price'] =$price;
                $array[$i]['order_number'] = $row->order_number;
                // $array[$i]['stock_quantity'] = $row->stock_quantity;
                // $array[$i]['sold_qty'] = $row->orderDetails->sum('qty');
                // $array[$i]['available_qty'] = $row->stock_quantity - $row->orderDetails->sum('qty');
                $image = asset('images/no-image.jpg');
                if (!empty($row->image) && file_exists(public_path('/storage/product/' . $row->image))) {
                    $image = asset("storage/product/" . $row->image);
                }
                $array[$i]["image"] = '<img src="' . $image . '" height="30px" width="30px" alt="image"/>';
                $action = '';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    $action .= ' <a href="javascript:;" class="viewProduct" title="View Data" data-id="' . $row->id . '"><i class="fa-solid fa-eye" style="color: #008f47;"></i></i></a>';
                    $action .= '<span style="margin-left: 10px;"></span>';
                    $action .= '|';
                    $action .= '<span style="margin-right: 10px;"></span>';
                    $action .= '<a href="javascript:;" class="editNews" title="Edit Data" data-id="' . $row->id . '" data-name="' . $row->product_name . '" ><i class="fa-solid fa-pen-to-square text-primary"></i></a> |';
                    $action .= '<span style="margin-right: 10px;"></span>';
                    $action .= '<a href="' . route('admin.batch', $row->slug) . '" class="addBatch" title="Add Batch"><i class="fa-solid fa-plus"></i></a> |';
                    // $row->slug

                } else if (!empty($post['type']) && $post['type'] == 'trashed') {
                    $action .= '<a href="javascript:;" class="restoreProduct" title="Restore Data" data-id="' . $row->id . '"><i class="fa-solid fa-undo text-success"></i></a> ';
                    $action .= '<span style="margin-left: 10px;"></span>';
                    $action .= '|';
                    $action .= '<span style="margin-left: 10px;"></span>';
                }
                
                    $action .= ' <a href="javascript:;" class="deleteNews" title="Delete Data" data-id="' . $row->id . '"><i class="fa fa-trash text-danger"></i></a>';

                $array[$i]['action'] = $action;
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
        return response()->json(['recordsFiltered' => $filtereddata, 'recordsTotal' => $totalrecs, 'data' => $array]);
    }


    public function view(Request $request)
    {
         try {
            $post = $request->all();
            $prevPost = Product::where('id', $post['id'])
                ->where('status', 'Y')
                ->first();

            $category = Category::where('id',$prevPost['category_id'])->where('status', 'Y')->first();


            $data = [
                'prevPost' => $prevPost,
                'category'=>$category
            ];

            $data['type'] = 'success';
            $data['message'] = 'Successfully fetched data of portfolio.';
        } catch (QueryException $e) {
            $data['type'] = 'error';
            $data['message'] = $this->queryMessage;
        } catch (Exception $e) {
            $data['type'] = 'error';
            $data['message'] = $e->getMessage();
        }
        return view('backend.product.view', $data);
    }

    public function delete(Request $request)
    {
        try {
            $type = 'success';
            $message = 'Record deleted successfully';
            $post = $request->all();
            $class = new Product();
            $directory = storage_path('app/public/product');
            DB::beginTransaction();
            $result = Common::deleteSingleData($post, $class, $directory);
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

    //function to restore
    public function restore(Request $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = "Product restored successfully";
            DB::beginTransaction();
            $result = Product::restoreData($post);
            if (!$result) {
                throw new Exception("Could not restore Product. Please try again.", 1);
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
