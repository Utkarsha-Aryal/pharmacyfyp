<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $guarded = [];

    // Return belongs to the invoice where the stock originally went out.
    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    // If a return is for one specific line item, this link helps trace it.
    public function invoiceItem()
    {
        return $this->belongsTo(SalesInvoiceItem::class, 'sales_invoice_item_id');
    }

    // Return belongs to one product.
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Return can be connected to the original batch.
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    // The staff member who created the return record.
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Show the return date in a readable format.
    public function getReturnDateShowAttribute(): string
    {
        return $this->return_date ? Carbon::parse($this->return_date)->format('M j, Y') : '-';
    }
}
