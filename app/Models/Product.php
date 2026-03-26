<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use Exception;
use App\Models\Common;
use Illuminate\Support\Str;
use Carbon\Carbon;


class Product extends Model
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class, 'product_id');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class, 'product_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->name ?: $this->product_name ?: 'Untitled product');
    }

    public function getEffectiveReorderLevelAttribute(): int
    {
        if (!is_null($this->reorder_level)) {
            return (int) $this->reorder_level;
        }

        return (int) ($this->alert_quantity ?? 10);
    }

    public function getEffectiveUnitAttribute(): string
    {
        return (string) ($this->unit ?? 'Unit');
    }

    public static function saveData($post)
    {
        try {
            $dataArray = [
                'name' => $post['product_name'],
                'product_name' => $post['product_name'],
                'generic_name' => $post['generic_name'],
                'composition' => $post['composition'],
                'order_number' => $post['order_number'],
                'group_name'=>$post['group_name'],
                'description' => $post['description'],
                'manufacturer'=>$post['manufacturer'], 
                'previous_price' => $post['previous_price'],
                'mrp'=>$post['mrp'],
                'category_id' => $post['category_id'],
                'formulation' => $post['formulation'] ?? 'other',
                'unit' => $post['unit_name'] ?? $post['unit'] ?? null,
                'reorder_level' => $post['reorder_level'] ?? $post['alert_quantity'] ?? 10,
                'alert_quantity' => $post['reorder_level'] ?? $post['alert_quantity'] ?? 10,
                'is_active' => array_key_exists('is_active', $post) ? (bool) $post['is_active'] : true,
                'sale_unit_id'=>$post['unit_sale_id'],
                'purchase_unit_id'=>$post['unit_purchase_id'],
                'product_status' => $post['product_status'] ?? 'stockout',
                'slug' => Str::slug($post['product_name']) . '-' . Str::random(30) . '-' . time() . '-' . Str::slug($post['description']) . '-' . Str::random(30) . '-' . time(),
                'keywords' => $post['keywords'],
                'alert_quantity'=>$post['alert_quantity'],
                'discount'=>$post['discount'],
                'purchase_price'=>$post['purchase_price']

            ];

            if (!empty($post['image'])) {
                $fileName = Common::uploadFile('product', $post['image']);
                if (!$fileName) {
                    return false;
                }
                $dataArray['image'] = $fileName;
            }

            if (!empty($post['id'])) {
                $dataArray['updated_at'] = Carbon::now();
                if (!Product::where('id', $post['id'])->update($dataArray)) {
                    throw new Exception("Couldn't update Product", 1);
                }
                $productId = $post['id'];
            } else {
                $dataArray['created_at'] = Carbon::now();
                $productId = Product::insertGetId($dataArray);
                if (!$productId) {
                    throw new Exception("Couldn't Save Product", 1);
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
            $orderby = " order_number " . $sorting . "";
            if (!empty($get['order'][0]['column']) && $get['order'][0]['column'] == 6) {
                $orderby = " order_number " . $sorting . "";
            }
            foreach ($get['columns'] as $key => $value) {
                $get['columns'][$key]['search']['value'] = trim(strtolower(htmlspecialchars($value['search']['value'], ENT_QUOTES)));
            }

            $cond = " status = 'Y'";

            if (!empty($post['type']) && $post['type'] === "trashed") {
                $cond = " status = 'N'";
            }

            if ($get['columns'][1]['search']['value'])
                $cond .= " and lower(product_name) like '%" . $get['columns'][1]['search']['value'] . "%'";

            if (!empty($post['category_id'])) {
                $cond .= " and category_id = '" . addslashes($post['category_id']) . "'";
            }

            $limit = 15;
            $offset = 0;
            if (!empty($get["length"]) && $get["length"]) {
                $limit = $get['length'];
                $offset = $get["start"];
            }

            $query = Product::with([
                'category',
                'productBatches' => function ($batchQuery) {
                    $batchQuery->where('status', 'Y');
                },
                'batches' => function ($batchQuery) {
                    $batchQuery->where('is_active', true);
                },
            ])
                ->selectRaw("(SELECT COUNT(*) FROM products WHERE {$cond}) 
               AS totalrecs, id, name, product_name, description,mrp,discount,slug, image, category_id,keywords,order_number,generic_name,display_price,manufacturer,formulation,unit,reorder_level,is_active")->whereRaw($cond);
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
            if (!Product::where(['id' => $post['id']])->update($updateArray)) {
                throw new Exception("Couldn't Restore Data. Please try again", 1);
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
