<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    // Each payment uses one selected mode.
    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class, 'payment_mode_id');
    }

    // Keep bill allocations grouped under the payment row.
    public function allocations()
    {
        return $this->hasMany(PaymentBillAllocation::class, 'payment_id');
    }

    // Customer relation is used only when party_type is customer.
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    // Supplier relation is used only when party_type is supplier.
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'party_id');
    }

    // Show a friendly payment date on the table and PDF.
    public function getPaymentDateShowAttribute(): string
    {
        return $this->payment_date ? Carbon::parse($this->payment_date)->format('M j, Y') : '-';
    }

    // Keep party name resolution in one place.
    public function getPartyNameAttribute(): string
    {
        if ($this->party_type === 'customer') {
            return $this->customer?->name ?? '-';
        }

        if ($this->party_type === 'supplier') {
            return $this->supplier?->supplier_name ?? '-';
        }

        return '-';
    }
}
