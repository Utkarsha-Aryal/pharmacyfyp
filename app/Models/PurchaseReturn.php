<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $guarded = [];

    // Each return belongs to one supplier bill.
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    // Supplier relation helps show supplier details on list and PDF.
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // User who recorded the return.
    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    // All returned line items live here.
    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }

    // Friendly date for tables and PDF.
    public function getReturnDateShowAttribute(): string
    {
        return $this->return_date ? Carbon::parse($this->return_date)->format('M j, Y') : '-';
    }
}
