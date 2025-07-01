<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Common;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\CategoryRequest;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('backend.category.index');
    }

     public function save(CategoryRequest $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = 'Records saved successfully';
            DB::beginTransaction();
            $result = Category::saveData($post);
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

     // Get list
    public function list(Request $request)
    {
        try {
            $post = $request->all();
            $data = Category::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data["totalfilteredrecs"] > 0 ? $data["totalfilteredrecs"] : $data["totalrecs"]);
            $totalrecs = $data["totalrecs"];
            unset($data["totalfilteredrecs"]);
            unset($data["totalrecs"]);
            foreach ($data as $row) {
                $array[$i]["sno"] = $i + 1;
                $array[$i]["name"] = $row->name;
                $array[$i]["keywords"] = $row->keywords;
                $array[$i]["order_number"] = $row->order_number;
                $image = asset('/images/no-image.jpg');
                if (!empty($row->image) && file_exists(public_path('/storage/category/' . $row->image))) {
                    $image = asset('/storage/category') . '/' . $row->image;
                }
                $array[$i]["image"] = '<img src="' . $image . '" height="30px" width="30px" alt="' . ' image"/>';
                $action = '';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    // $action .= ' <a href="javascript:;" class="viewCategory" title="View Data" data-id="' . $row->id . '"><i class="fa-solid fa-eye" style="color: #008f47;"></i></i></a> |';
                    // $action .= '<span style="margin-left: 3px;"></span>';
                    $action .= '<a href="javascript:;" class="editCategory" name="Edit Data" data-id="' . $row->id . '" data-name="' . $row->name . '" data-keywords="' . $row->keywords . '" data-designation="' . $row->designation . '" data-order_number="' . $row->order_number . '" data-image="' . $image . '" data-course="' . $row->student_course . '" data-rating="' . $row->rating . '"><i class="fa-solid fa-pen-to-square text-primary"></i></a> | ';
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
            $array = [];
            $totalrecs = 0;
            $filtereddata = 0;
        } catch (Exception $e) {
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
            $class = new Category();
            $directory = storage_path('app/public/category');
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

     public function restore(Request $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = "Category restored successfully";
            DB::beginTransaction();
            $result = Category::restoreData($post);
            if (!$result) {
                throw new Exception("Could not restore Category. Please try again.", 1);
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
