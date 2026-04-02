<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Common;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Supplier;
use Carbon\Carbon;


class SupplierController extends Controller
{
    public function index(){

        return view('supplier.index');
    }

    public function save(Request $request){
        try {
            $post = $request->validate([
                'id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'supplier_name' => ['required', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'phone_number' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'pan_number' => ['nullable', 'string', 'max:100'],
                'opening_balance' => ['nullable', 'numeric'],
                'address' => ['nullable', 'string'],
                'type' => ['nullable', 'in:cash,credit'],
            ]);
            $type = 'success';
            $message = 'Records saved successfully';
            DB::beginTransaction();
            $result = Supplier::saveData($post);
            if (!$result) {
                throw new Exception('Could not save record', 1);
            }
            $savedSupplier = !empty($post['id'])
                ? Supplier::query()->find($post['id'])
                : Supplier::query()->where('supplier_name', $post['supplier_name'])->latest('id')->first();
            DB::commit();
        } catch (ValidationException $e) {
            $type = 'error';
            $message = collect($e->errors())->flatten()->first() ?: $e->getMessage();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = $this->queryMessage;
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
        return response()->json([
            'type' => $type,
            'message' => $message,
            'data' => isset($savedSupplier) && $type === 'success'
                ? [
                    'id' => $savedSupplier->id,
                    'text' => $savedSupplier->supplier_name,
                ]
                : null,
        ]);

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
