<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    protected $guarded = [];

    // One payment mode can be used by many payment rows.
    public function payments()
    {
        return $this->hasMany(Payment::class, 'payment_mode_id');
    }
}
