<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Common;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Supplier;
use Carbon\Carbon;


class SupplierController extends Controller
{
    public function index(){

        return view('backend.supplier.index');
    }

    public function save(Request $request){
        try {
            $post = $request->all();
            $type = 'success';
            $message = 'Records saved successfully';
            DB::beginTransaction();
            $result = Supplier::saveData($post);
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
            $data = Supplier::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data["totalfilteredrecs"] > 0 ? $data["totalfilteredrecs"] : $data["totalrecs"]);
            $totalrecs = $data["totalrecs"];
            unset($data["totalfilteredrecs"]);
            unset($data["totalrecs"]);
            foreach ($data as $row) {
                $array[$i]["sno"] = $i + 1;
                $array[$i]["supplier_name"] = $row->supplier_name;
                $array[$i]["contact_person"] = $row->contact_person;
                $array[$i]["phone_number"] = $row->phone_number;
                $array[$i]["email"] = $row->email;
                $array[$i]["pan_number"] = $row->pan_number;
                $array[$i]["opening_balance"] = $row->opening_balance;
                $array[$i]["added_date"] = Carbon::parse($row->created_at)->format('M j, Y');
                $array[$i]["type"] = ucfirst($row->type);

                $action = '<div class="table-action-group">';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editSupplier" title="Edit Supplier" data-id="' . $row->id . '" data-supplier_name="' . $row->supplier_name . '" data-contact_person="' . $row->contact_person . '" data-phone_number="' . $row->phone_number . '" data-email="' . $row->email . '" data-pan_number="' . $row->pan_number . '" data-opening_balance="' . $row->opening_balance . '" data-address="' . $row->address . '" data-type="' . $row->type . '"><i class="fa-solid fa-pen-to-square"></i></button>';
                } else if (!empty($post['type']) && $post['type'] == 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-success table-action-btn restoreSupplier" title="Restore Supplier" data-id="' . $row->id . '"><i class="fa-solid fa-undo"></i></button>';
                }
                $action .= '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn deleteSupplier" title="Delete Supplier" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                $action .= '</div>';
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
            $message = 'Supplier deleted successfully';
            $post = $request->all();
            $class = new Supplier();
            $directory = null;
            DB::beginTransaction();
            $result = Common::deleteSingleData($post, $class, $directory);
            if (!$result) {
                throw new Exception("Couldn't delete supplier", 1);
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = 'Database error';
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
            $message = 'Supplier restored successfully';
            DB::beginTransaction();
            $result = Supplier::restoreData($post);
            if (!$result) {
                throw new Exception("Could not restore supplier. Please try again.", 1);
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = 'Database error';
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }

        return response()->json(['type' => $type, 'message' => $message]);
    }
}
