<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $guarded = [];

    // Item belongs to a sale invoice.
    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    // Item belongs to a product.
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Item can be linked to one stock batch.
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }
}
