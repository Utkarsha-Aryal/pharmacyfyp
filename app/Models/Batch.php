<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    // Direct purchase bill items can also point to this same inventory batch.
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'batch_id');
    }

    // Purchase returns reduce this same batch quantity.
    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'batch_id');
    }

    public static function makeExpiryDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function getExpiryShowAttribute(): ?string
    {
        $expiryDate = self::makeExpiryDate($this->expiry_date);

        return $expiryDate?->format('M j, Y');
    }

    public function getDaysRemainingAttribute(): int
    {
        $expiryDate = self::makeExpiryDate($this->expiry_date);

        if (!$expiryDate) {
            return 0;
        }

        return Carbon::today()->diffInDays($expiryDate, false);
    }

    public function getRowStateAttribute(): string
    {
        $days = $this->days_remaining;

        if ($days < 0) {
            return 'danger';
        }

        if ($days <= 7) {
            return 'danger';
        }

        if ($days <= 15) {
            return 'warning';
        }

        if ($days <= 30) {
            return 'info';
        }

        return 'success';
    }
}
