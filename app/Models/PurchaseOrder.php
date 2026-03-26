<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $purchaseOrder) {
            if (empty($purchaseOrder->reference)) {
                $purchaseOrder->reference = static::makeReference();
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public static function makeReference(): string
    {
        $datePart = now()->format('ymd');
        $count = static::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'PUR-' . $datePart . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function getDueAmountAttribute(): float
    {
        return round(((float) $this->total_amount) - (float) ($this->paid_amount ?? 0), 2);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return $this->due_amount;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst((string) $this->status);
    }

    public function getPaymentLabelAttribute(): string
    {
        return ucfirst((string) $this->payment_status);
    }

    public function getOrderDateShowAttribute(): string
    {
        return $this->order_date ? Carbon::parse($this->order_date)->format('Y-m-d') : '-';
    }
}
