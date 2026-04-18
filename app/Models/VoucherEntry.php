<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherEntry extends Model
{
    protected $guarded = [];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'party_id');
    }

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

    public function getAccountLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->account_type));
    }
}
