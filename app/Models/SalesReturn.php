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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // One sales return can hold multiple returned invoice rows.
    public function items()
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_return_id');
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

    // Keep the original selling rate on the return row so history stays accurate.
    public function getEffectiveUnitPriceAttribute(): float
    {
        return round((float) $this->items->avg('unit_price'), 2);
    }

    // Return should keep its own discount snapshot even if invoice data changes later.
    public function getEffectiveDiscountPercentAttribute(): float
    {
        return round((float) $this->items->avg('discount_percent'), 2);
    }

    // Discount amount is stored for the returned quantity, not for the full original sale line.
    public function getEffectiveDiscountAmountAttribute(): float
    {
        return round((float) ($this->discount_amount ?? $this->items->sum('discount_amount')), 2);
    }

    // Net unit rate is the actual discounted sale rate used to derive refund defaults.
    public function getEffectiveNetUnitPriceAttribute(): float
    {
        return round((float) $this->items->avg('net_unit_price'), 2);
    }

    public function getQuantityAttribute(): float
    {
        return round((float) ($this->total_quantity ?? $this->items->sum('quantity')), 2);
    }

    public function getProductSummaryAttribute(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->with('product')->get();
        $names = $items->map(fn ($item) => $item->product?->display_name)->filter()->unique()->values();

        if ($names->isEmpty()) {
            return '-';
        }

        if ($names->count() === 1) {
            return (string) $names->first();
        }

        return $names->first() . ' +' . ($names->count() - 1) . ' more';
    }

    // Keep refund state readable in tables and forms.
    public function getRefundStatusLabelAttribute(): string
    {
        if ($this->refund_status === 'credit' || (float) ($this->pending_credit_amount ?? 0) > 0) {
            return 'Customer Credit';
        }

        return 'Balance Adjusted';
    }

    // Use Bootstrap badge classes instead of custom labels.
    public function getRefundStatusBadgeClassAttribute(): string
    {
        if ($this->refund_status === 'credit' || (float) ($this->pending_credit_amount ?? 0) > 0) {
            return 'bg-warning text-dark';
        }

        return 'bg-info text-dark';
    }
}
