<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    public static function saveData($post)
    {
        try {
            $dataArray = [
            'supplier_name'    => $post['supplier_name'],
            'contact_person'   => $post['contact_person'],
            'phone_number'     => $post['phone_number'],
            'email'            => $post['email'],
            'pan_number'       => $post['pan_number'],
            'opening_balance'  => $post['opening_balance'],
            'address'          => $post['address'],
            'type'             => $post['type'],
            ];
            if (!empty($post['id'])) {
            $dataArray['updated_at'] = now();
            if (!Supplier::where('id', $post['id'])->update($dataArray)) {
                throw new Exception("Couldn't update Records", 1);
            }
            } else {
            $dataArray['created_at'] = now();
            if (!Supplier::insert($dataArray)) {
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
            $orderby = " id " . $sorting . "";

            foreach ($get['columns'] as $key => $value) {
                $get['columns'][$key]['search']['value'] = trim(strtolower(htmlspecialchars($value['search']['value'], ENT_QUOTES)));
            }

            $cond = " status = 'Y'";

            if (!empty($post['type']) && $post['type'] === "trashed") {
                $cond = " status = 'N'";
            }

            if (!empty($get['columns'][1]['search']['value'])) {
                $cond .= " and lower(supplier_name) like '%" . $get['columns'][1]['search']['value'] . "%'";
            }

            $limit = 15;
            $offset = 0;
            if (!empty($get["length"])) {
                $limit = $get['length'];
                $offset = $get["start"];
            }

            $query = Supplier::selectRaw("(SELECT count(*) FROM suppliers WHERE {$cond}) AS totalrecs, supplier_name, contact_person, phone_number, email, pan_number, opening_balance,created_at, address, type, id")
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
                $ndata = [];
            }

            return $ndata;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
