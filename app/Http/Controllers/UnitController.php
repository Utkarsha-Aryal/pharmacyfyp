<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use App\Models\Common;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Exception;
use Carbon\Carbon;

class UnitController extends Controller
{
    public function index(){
        return view('unit.index');
    }

    public function save(UnitRequest $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = 'Records saved successfully';
            DB::beginTransaction();
            $result = Unit::saveData($post);
            if (!$result) {
                throw new Exception('Could not save record', 1);
            }
            $savedUnit = !empty($post['id'])
                ? Unit::query()->find($post['id'])
                : Unit::query()->where('unit_name', $post['unit_name'])->latest('id')->first();
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = $this->queryMessage ?? 'Database error';
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
        return response()->json([
            'type' => $type,
            'message' => $message,
            'data' => isset($savedUnit) && $type === 'success'
                ? [
                    'id' => $savedUnit->id,
                    'text' => $savedUnit->unit_name,
                    'type' => $savedUnit->type,
                ]
                : null,
        ]);
    }

    public function list(Request $request)
    {
        try {
            $post = $request->all();
            $data = Unit::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data["totalfilteredrecs"] > 0 ? $data["totalfilteredrecs"] : $data["totalrecs"]);
            $totalrecs = $data["totalrecs"];
            unset($data["totalfilteredrecs"]);
            unset($data["totalrecs"]);
            foreach ($data as $row) {
                $array[$i]["sno"] = $i + 1;
                $array[$i]["unit_name"] = $row->unit_name;
                $array[$i]["type"] = '<span class="badge bg-info-subtle text-info-emphasis">' . e($row->type_label) . '</span>';
                $array[$i]["description"] = $row->description;
                $array[$i]["status"] = $row->status;
                $array[$i]["added_date"] = Carbon::parse($row->created_at)->format('M j, Y');
                $action = '<div class="table-action-group">';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editUnit" title="Edit Unit" data-id="' . $row->id . '" data-unit_name="' . $row->unit_name . '" data-type="' . e($row->type) . '" data-description="' . $row->description . '" data-status="' . $row->status . '"><i class="fa-solid fa-pen-to-square"></i></button>';
                } else if (!empty($post['type']) && $post['type'] == 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-success table-action-btn restoreUnit" title="Restore Unit" data-id="' . $row->id . '"><i class="fa-solid fa-undo"></i></button>';
                }
                $action .= '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn deleteUnit" title="Delete Unit" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
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
            $message = 'Record deleted successfully';
            $post = $request->all();
            $class = new Unit();
            $directory = null;
            DB::beginTransaction();
            $result = Common::deleteSingleData($post, $class, $directory);
            if (!$result) {
                throw new Exception("Couldn't delete record", 1);
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = $this->queryMessage ?? 'Database error';
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
            $message = "Unit restored successfully";
            DB::beginTransaction();
            $result = Unit::restoreData($post);
            if (!$result) {
                throw new Exception("Could not restore Unit. Please try again.", 1);
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = $this->queryMessage ?? 'Database error';
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
        return response()->json(['type' => $type, 'message' => $message]);
    }
}
