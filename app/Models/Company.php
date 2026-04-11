<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class, 'company_id');
    }

    public static function saveData($post)
    {
        try {
            $dataArray = [
                'name' => $post['name'],
                'company_type' => $post['company_type'] ?? 'domestic',
                'default_cc_rate' => round((float) ($post['default_cc_rate'] ?? 0), 2),
                'slug' => Str::slug($post['name']) . '-' . Str::random(20) . '-' . time(),
            ];

            if (!empty($post['id'])) {
                $dataArray['updated_at'] = Carbon::now();
                if (!Company::where('id', $post['id'])->update($dataArray)) {
                    throw new Exception("Couldn't update Records", 1);
                }
            } else {
                $dataArray['created_at'] = Carbon::now();
                if (!Company::insert($dataArray)) {
                    throw new Exception("Couldn't Save Records", 1);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function list($post)
    {
        try {
            $get = $post;
            $sorting = !empty($get['order'][0]['dir']) ? $get['order'][0]['dir'] : 'asc';
            $orderby = " name " . $sorting . "";
            if (!empty($get['order'][0]['column']) && (int) $get['order'][0]['column'] === 2) {
                $orderby = " company_type " . $sorting . "";
            }
            foreach ($get['columns'] as $key => $value) {
                $get['columns'][$key]['search']['value'] = trim(strtolower(htmlspecialchars($value['search']['value'], ENT_QUOTES)));
            }

            $cond = " status = 'Y'";

            if (!empty($post['type']) && $post['type'] === "trashed") {
                $cond = " status = 'N'";
            }

            if ($get['columns'][1]['search']['value']) {
                $cond .= " and lower(name) like '%" . $get['columns'][1]['search']['value'] . "%'";
            }

            if (!empty($get['columns'][2]['search']['value'])) {
                $cond .= " and lower(company_type) like '%" . $get['columns'][2]['search']['value'] . "%'";
            }

            $limit = 15;
            $offset = 0;
            if (!empty($get["length"]) && $get["length"]) {
                $limit = $get['length'];
                $offset = $get["start"];
            }

            $query = Company::selectRaw("(SELECT count(*) FROM companies WHERE {$cond}) AS totalrecs, id, name, company_type, default_cc_rate")
                ->whereRaw($cond);

            if ($limit > -1) {
                $result = $query->orderByRaw($orderby)->offset($offset)->limit($limit)->get();
            } else {
                $result = $query->orderByRaw($orderby)->get();
            }
            if ($result) {
                $ndata = $result;
                $ndata['totalrecs'] = @$result[0]->totalrecs ? $result[0]->totalrecs : 0;
                $ndata['totalfilteredrecs'] = @$result[0]->totalrecs ? $result[0]->totalrecs : 0;
            } else {
                $ndata = array();
            }

            return $ndata;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function restoreData($post)
    {
        try {
            $updateArray = [
                'status' => 'Y',
                'updated_at' => Carbon::now(),
            ];
            if (!Company::where(['id' => $post['id']])->update($updateArray)) {
                throw new Exception("Couldn't Restore Data. Please try again", 1);
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
