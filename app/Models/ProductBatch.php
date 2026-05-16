<?php

namespace App\Models;

use App\Models\Batch;
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
                $product = Product::query()->with('company')->findOrFail($item['product_id']);
                $quantity = (int) ($item['quantity'] ?? 0);
                $freeQuantity = (int) ($item['free_qty'] ?? 0);
                $mrp = round((float) ($item['mrp'] ?? 0), 2);
                $ccRate = round((float) ($item['cc_rate'] ?? $product->effective_cc_rate ?? 0), 2);
                $discountPercent = round((float) ($item['discount_percent'] ?? 0), 2);
                $lineAmount = round($quantity * (float) $item['purchase_price'], 2);
                $discountAmount = round(($lineAmount * $discountPercent) / 100, 2);
                $netAmount = round($lineAmount - $discountAmount, 2);
                $freeGoodsValue = round($freeQuantity * ($mrp * $ccRate / 100), 2);
                $physicalQuantity = $quantity + $freeQuantity;
                $expiryDate = !empty($item['expiry_date'])
                    ? Carbon::parse($item['expiry_date'])->format('Y-m-d')
                    : null;

                ProductBatch::create([
                    'product_id' => $item['product_id'],
                    'batch_no' => $item['batch_no'] ?? null,
                    'expiry_date' => $item['expiry_date'],
                    'quantity' => $physicalQuantity,
                    'free_qty' => $freeQuantity,
                    'mrp' => $mrp,
                    'cc_rate' => $ccRate,
                    'discount_percent' => $discountPercent,
                    'free_goods_value' => $freeGoodsValue,
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $netAmount,
                    'supplier_id' => $post['supplier_id'],
                    'reference_id' => $post['reference_id'],
                ]);

                $inventoryBatchId = null;

                // Direct purchase entry should also feed the main inventory batches table.
                if (!empty($item['batch_no']) && class_exists(Batch::class)) {
                    $inventoryBatch = Batch::query()->firstOrNew([
                        'product_id' => $item['product_id'],
                        'supplier_id' => $post['supplier_id'],
                        'batch_number' => $item['batch_no'],
                    ]);

                    $inventoryBatch->expiry_date = $expiryDate ?? now()->toDateString();
                    $inventoryBatch->purchase_price = $item['purchase_price'];
                    $inventoryBatch->quantity_received = (int) ($inventoryBatch->quantity_received ?? 0) + $physicalQuantity;
                    $inventoryBatch->quantity_available = (int) ($inventoryBatch->quantity_available ?? 0) + $physicalQuantity;
                    $inventoryBatch->is_active = true;
                    $inventoryBatch->save();
                    $inventoryBatchId = $inventoryBatch->id;
                }

                // Purchase item rows make returns, PDF and payment tracking easier later.
                if (!empty($post['purchase_id'])) {
                    PurchaseItem::query()->create([
                        'purchase_id' => $post['purchase_id'],
                        'product_id' => $item['product_id'],
                        'batch_id' => $inventoryBatchId,
                        'batch_no' => $item['batch_no'] ?? null,
                        'expiry_date' => $expiryDate,
                        'quantity' => $quantity,
                        'free_qty' => $freeQuantity,
                        'mrp' => $mrp,
                        'rate' => round((float) $item['purchase_price'], 2),
                        'cc_rate' => $ccRate,
                        'discount_percent' => $discountPercent,
                        'discount_amount' => $discountAmount,
                        'free_goods_value' => $freeGoodsValue,
                        'amount' => $netAmount,
                    ]);
                }

                record_stock_movement([
                    'movement_date' => $post['purchase_date'] ?? now()->toDateString(),
                    'product_id' => $item['product_id'],
                    'batch_id' => $inventoryBatchId,
                    'movement_type' => 'purchase_in',
                    'quantity_in' => $physicalQuantity,
                    'source_type' => 'Supplier',
                    'source_id' => $post['supplier_id'] ?? null,
                    'destination_type' => 'Inventory',
                    'reference_type' => 'Purchase',
                    'reference_id' => $post['purchase_id'] ?? null,
                    'notes' => 'Stock received from purchase bill.',
                ]);

                // keep latest purchase side data on master product
                Product::where('id', $item['product_id'])->update([
                    'product_status' => Product::legacyStatusCode('In Stock'),
                    'product_status_id' => DropdownOption::findIdByAliasAndName('product_status', 'In Stock'),
                    'purchase_price' => $item['purchase_price'],
                    'mrp' => $mrp,
                    'cc_rate' => $ccRate,
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

        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $value)) {
                return Carbon::createFromFormat('Y-m', $value)->endOfMonth();
            }

            return Carbon::parse($value);
        } catch (Exception $e) {
            return null;
        } finally {
            restore_error_handler();
        }
    }
}
