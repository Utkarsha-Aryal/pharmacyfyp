<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $guarded = [];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(SalesInvoiceItem::class, 'sales_invoice_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function getEffectiveUnitPriceAttribute(): float
    {
        return round((float) ($this->unit_price ?? $this->invoiceItem?->unit_price ?? 0), 2);
    }

    public function getEffectiveDiscountPercentAttribute(): float
    {
        return round((float) ($this->discount_percent ?? $this->invoiceItem?->discount_percent ?? 0), 2);
    }

    public function getEffectiveDiscountAmountAttribute(): float
    {
        return round((float) ($this->discount_amount ?? 0), 2);
    }

    public function getEffectiveNetUnitPriceAttribute(): float
    {
        return round((float) ($this->net_unit_price ?? $this->unit_price ?? 0), 2);
    }
}
