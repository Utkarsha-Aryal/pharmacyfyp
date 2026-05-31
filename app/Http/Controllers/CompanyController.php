<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Common;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\CompanyRequest;

class CompanyController extends Controller
{
    // Show the company page with modal form and server-side list.
    public function index()
    {
        return view('company.index');
    }

    // Save and update company from the same endpoint because the modal form is shared.
    public function save(CompanyRequest $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = 'Company saved successfully';
            DB::beginTransaction();
            $result = Company::saveData($post);
            if (!$result) {
                throw new Exception('Could not save record', 1);
            }
            $savedCompany = !empty($post['id'])
                ? Company::query()->find($post['id'])
                : Company::query()->where('name', $post['name'])->latest('id')->first();
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
        return response()->json([
            'type' => $type,
            'message' => $message,
            'data' => isset($savedCompany) && $type === 'success'
                ? [
                    'id' => $savedCompany->id,
                    'text' => $savedCompany->name,
                ]
                : null,
        ]);
    }

    // Return company rows for DataTable.
    public function list(Request $request)
    {
        try {
            $post = $request->all();
            $data = Company::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data["totalfilteredrecs"] > 0 ? $data["totalfilteredrecs"] : $data["totalrecs"]);
            $totalrecs = $data["totalrecs"];
            unset($data["totalfilteredrecs"]);
            unset($data["totalrecs"]);
            foreach ($data as $row) {
                $array[$i]["sno"] = $i + 1;
                $array[$i]["name"] = $row->name;
                $typeClass = $row->company_type === 'foreign' ? 'bg-warning text-dark' : 'bg-primary';
                $array[$i]["company_type"] = '<span class="badge ' . $typeClass . '">' . e(ucfirst((string) $row->company_type)) . '</span>';
                $array[$i]["default_cc_rate"] = '<span class="badge bg-secondary">' . e(number_format((float) ($row->default_cc_rate ?? 0), 2) . '%') . '</span>';
                $action = '<div class="table-action-group">';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editCompany" title="Edit Company" data-id="' . $row->id . '" data-name="' . e($row->name) . '" data-company_type="' . e($row->company_type) . '" data-default_cc_rate="' . e(number_format((float) ($row->default_cc_rate ?? 0), 2, '.', '')) . '"><i class="fa-solid fa-pen-to-square"></i></button>';
                } else if (!empty($post['type']) && $post['type'] == 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-success table-action-btn restoreCompany" title="Restore Company" data-id="' . $row->id . '"><i class="fa-solid fa-undo"></i></button>';
                }

                $action .= '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn deleteCompany" title="Delete Company" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
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
        return response()->json(array(
            "draw" => (int) $request->input('draw', 0),
            "recordsFiltered" => $filtereddata,
            "recordsTotal" => $totalrecs,
            "data" => $array,
        ));
    }

    // Soft delete or force delete based on the current trash mode.
    public function delete(Request $request)
    {
        try {
            $type = 'success';
            $message = 'Company deleted successfully';
            $post = $request->all();
            DB::beginTransaction();
            $result = Common::deleteDataFileDoesnotExists($post, new Company());
            if (!$result) {
                throw new Exception("Couldn't delete record", 1);
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = 'This compoany is already connected to a product';
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
        return response()->json(['type' => $type, 'message' => $message]);
    }

    // Bring back a deleted company from the trash list.
    public function restore(Request $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = "Company restored successfully";
            DB::beginTransaction();
            $result = Company::restoreData($post);
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
