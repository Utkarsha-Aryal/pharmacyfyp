<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $guarded = [];

    // Parent purchase return header.
    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    // Original purchase line.
    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    // Product helps build the print document.
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Batch relation is used for stock rollback and print.
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }
}
