<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function reference()
    {
        return $this->belongsTo(PurchaseReference::class, 'reference_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'reference_id', 'reference_id');
    }

    public static function savePurchaseItems($post)
    {
        try {
            if (!isset($post['items']) || !is_array($post['items'])) {
                throw new Exception('No purchase items found.', 1);
            }

            foreach ($post['items'] as $item) {
                $subtotal = round(((float) $item['quantity']) * ((float) $item['purchase_price']), 2);

                ProductBatch::create([
                    'product_id' => $item['product_id'],
                    'batch_no' => $item['batch_no'] ?? null,
                    'expiry_date' => $item['expiry_date'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $subtotal,
                    'supplier_id' => $post['supplier_id'],
                    'reference_id' => $post['reference_id'],
                ]);

                // keep latest purchase side data on master product
                Product::where('id', $item['product_id'])->update([
                    'product_status' => 'instock',
                    'purchase_price' => $item['purchase_price'],
                    'updated_at' => Carbon::now(),
                ]);
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

            $query = ProductBatch::with(['supplier', 'reference', 'purchase'])
                ->where('status', 'Y')
                ->where('product_id', $post['product_id']);

            if (!empty($get['columns'][1]['search']['value'])) {
                $search = strtolower(trim($get['columns'][1]['search']['value']));
                $query->whereRaw('lower(batch_no) like ?', ['%' . $search . '%']);
            }

            $totalrecs = (clone $query)->count();

            $limit = 15;
            $offset = 0;
            if (!empty($get['length'])) {
                $limit = $get['length'];
                $offset = $get['start'];
            }

            $result = $query
                ->orderByDesc('id')
                ->offset($offset)
                ->limit($limit)
                ->get();

            if ($result->isNotEmpty()) {
                $result['totalrecs'] = $totalrecs;
                $result['totalfilteredrecs'] = $totalrecs;
            } else {
                $result = collect();
                $result['totalrecs'] = 0;
                $result['totalfilteredrecs'] = 0;
            }

            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function makeExpiryDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $value)) {
                return Carbon::createFromFormat('Y-m', $value)->endOfMonth();
            }

            return Carbon::parse($value);
        } catch (Exception $e) {
            return null;
        }
    }
}
