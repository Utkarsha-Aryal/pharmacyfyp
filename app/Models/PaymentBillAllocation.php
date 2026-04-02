<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentBillAllocation extends Model
{
    protected $guarded = [];

    // One allocation belongs to one payment voucher.
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
