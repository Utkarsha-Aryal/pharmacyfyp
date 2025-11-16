<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;

class Unit extends Model
{
      public function unit()
{
    return $this->hasMany(Product::class, 'unit_id');
}

    
        public static function saveData($post)
        {
            try {
                $dataArray = [
                    'unit_name' => $post['unit_name'],
                    'description' => $post['description'],
                ];

                if (!empty($post['id'])) {
                    $dataArray['updated_at'] = Carbon::now();
                    if (!self::where('id', $post['id'])->update($dataArray)) {
                        throw new Exception("Couldn't update Records", 1);
                    }
                } else {
                    $dataArray['created_at'] = Carbon::now();
                    if (!self::insert($dataArray)) {
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
                $orderby = " created_at " . $sorting . "";

                foreach ($get['columns'] as $key => $value) {
                    $get['columns'][$key]['search']['value'] = trim(strtolower(htmlspecialchars($value['search']['value'], ENT_QUOTES)));
                }

                $cond = " status = 'Y'";

                if (!empty($post['type']) && $post['type'] === "trashed") {
                    $cond = " status = 'N'";
                }

                if ($get['columns'][1]['search']['value'])
                    $cond .= " and lower(unit_name) like '%" . $get['columns'][1]['search']['value'] . "%'";

                $limit = 15;
                $offset = 0;
                if (!empty($get["length"]) && $get["length"]) {
                    $limit = $get['length'];
                    $offset = $get["start"];
                }

                $query = self::selectRaw("(SELECT count(*) FROM units WHERE {$cond}) AS totalrecs, unit_name, description, id, status, created_at")
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
                if (!self::where(['id' => $post['id']])->update($updateArray)) {
                    throw new Exception("Couldn't Restore Data. Please try again", 1);
                }
                return true;
            } catch (Exception $e) {
                throw $e;
            }
        }
}
