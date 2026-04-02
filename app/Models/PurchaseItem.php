<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $guarded = [];

    // Each row belongs to one purchase bill.
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    // Each row belongs to one product.
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // The batch is linked after stock is created in inventory.
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    // Returns can point back to the original purchase line.
    public function returns()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_item_id');
    }
}
