<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class ProductBatch extends Model
{
       public static function saveData($post)
    {
        try {
            $dataArray = [
                'product_id' => $post['product_id'],
                'batch_no' => $post['batch_no'],
                'expiry_date' => $post['expiry_date'],
                'quantity'=> $post['quantity'],
                'purchase_price'=>$post['purchase_price']

            ];
            if (!empty($post['id'])) {
                $dataArray['updated_at'] = Carbon::now();
                if (!ProductBatch::where('id', $post['id'])->update($dataArray)) {
                    throw new Exception("Couldn't update Records", 1);
                }
            } else {
                $dataArray['created_at'] = Carbon::now();
                if (!ProductBatch::insert($dataArray)) {
                    throw new Exception("Couldn't Save Records", 1);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
public static function saveProductBatch($post)
{
    try {
        // Expecting $post['items'] as an array of multiple products
        if (!isset($post['items']) || !is_array($post['items'])) {
            throw new Exception("No product items found", 1);
        }

        foreach ($post['items'] as $item) {
            $dataArray = [
                'product_id'     => $item['product_id'],
                'batch_no'       => $item['batch_no'],
                'expiry_date'    => $item['expiry_date'],
                'quantity'       => $item['quantity'],
                'purchase_price' => $item['purchase_price']
            ];

            if (!empty($item['id'])) {
                // Update existing record
                $dataArray['updated_at'] = Carbon::now();
                if (!ProductBatch::where('id', $item['id'])->update($dataArray)) {
                    throw new Exception("Couldn't update record with ID: " . $item['id'], 1);
                }
            } else {
                // Insert new record
                $dataArray['created_at'] = Carbon::now();
                if (!ProductBatch::insert($dataArray)) {
                    throw new Exception("Couldn't save record for product: " . $item['product_id'], 1);
                }
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

        $cond = " status = 'Y'";
        // intval pervents sql injection
        $cond .= " AND product_id = " . intval($post['product_id']);

        if (!empty($post['type']) && $post['type'] === "trashed") {
            $cond = " status = 'N'";
        }

        if (!empty($get['columns'][1]['search']['value'])) {
            $cond .= " and lower(batch_no) like '%" . strtolower(trim($get['columns'][1]['search']['value'])) . "%'";
        }

        $limit = 15;
        $offset = 0;
        if (!empty($get["length"])) {
            $limit = $get['length'];
            $offset = $get["start"];
        }

        $query = ProductBatch::selectRaw("(SELECT count(*) FROM product_batches WHERE {$cond}) AS totalrecs, product_id, batch_no, expiry_date, quantity, purchase_price, id")
            ->whereRaw($cond);

        if ($limit > -1) {
            $result = $query->offset($offset)->limit($limit)->get();
        } else {
            $result = $query->get();
        }

        if ($result) {
            $ndata = $result;
            $ndata['totalrecs'] = $result[0]->totalrecs ?? 0;
            $ndata['totalfilteredrecs'] = $result[0]->totalrecs ?? 0;
        } else {
            $ndata = [];
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
            if (!ProductBatch::where(['id' => $post['id']])->update($updateArray)) {
                throw new Exception("Couldn't Restore Data. Please try again", 1);
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }


}
