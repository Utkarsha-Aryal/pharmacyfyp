<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = [];

    public function entries()
    {
        return $this->hasMany(VoucherEntry::class, 'voucher_id')->orderBy('line_no');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getVoucherDateShowAttribute(): string
    {
        return $this->voucher_date ? Carbon::parse($this->voucher_date)->format('M j, Y') : '-';
    }

    public function getVoucherTypeLabelAttribute(): string
    {
        return match ((string) $this->voucher_type) {
            'contra' => 'Contra Voucher',
            'debit_note' => 'Debit Note',
            'credit_note' => 'Credit Note',
            default => 'Journal Voucher',
        };
    }
}
