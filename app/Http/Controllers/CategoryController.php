<?php

namespace App\Http\Controllers;

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
    // Show the category page with modal form and server-side list.
    public function index()
    {
        return view('category.index');
    }

    // Save and update category from the same endpoint because the modal form is shared.
    public function save(CategoryRequest $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = 'Company saved successfully';
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

    // Return category rows for DataTable.
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
                if (!empty($row->image) && file_exists(public_path('storage/category/' . $row->image))) {
                    $image = asset('/storage/category') . '/' . $row->image;
                }
                $array[$i]["image"] = '<img src="' . $image . '" height="30px" width="30px" alt="' . ' image"/>';
                $action = '<div class="table-action-group">';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editCategory" title="Edit Company" data-id="' . $row->id . '" data-name="' . $row->name . '" data-order_number="' . $row->order_number . '" data-image="' . $image . '"><i class="fa-solid fa-pen-to-square"></i></button>';
                } else if (!empty($post['type']) && $post['type'] == 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-success table-action-btn restoreCategory" title="Restore Company" data-id="' . $row->id . '"><i class="fa-solid fa-undo"></i></button>';
                }

                $action .= '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn deletecategory" title="Delete Company" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                $action .= '</div>';
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

    // Soft delete or force delete based on the current trash mode.
    public function delete(Request $request)
    {
        try {
            $type = 'success';
            $message = 'Company deleted successfully';
            $post = $request->all();
            $class = new Category();
            $directory = public_path('storage/category');
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

    // Bring back a deleted category from the trash list.
    public function restore(Request $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = "Company restored successfully";
            DB::beginTransaction();
            $result = Category::restoreData($post);
            if (!$result) {
                throw new Exception("Could not restore Company. Please try again.", 1);
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
