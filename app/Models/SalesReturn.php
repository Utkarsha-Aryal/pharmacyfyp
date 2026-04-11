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

    // Keep refund payment mode available for cashier/bank tracing.
    public function paymentMode()
    {
        return $this->belongsTo(DropdownOption::class, 'payment_mode_id');
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
        if (array_key_exists('unit_price', $this->attributes) && $this->attributes['unit_price'] !== null) {
            return round((float) $this->attributes['unit_price'], 2);
        }

        return round((float) ($this->invoiceItem?->unit_price ?? 0), 2);
    }

    // Return should keep its own discount snapshot even if invoice data changes later.
    public function getEffectiveDiscountPercentAttribute(): float
    {
        if (array_key_exists('discount_percent', $this->attributes) && $this->attributes['discount_percent'] !== null) {
            return round((float) $this->attributes['discount_percent'], 2);
        }

        return round((float) ($this->invoiceItem?->discount_percent ?? 0), 2);
    }

    // Discount amount is stored for the returned quantity, not for the full original sale line.
    public function getEffectiveDiscountAmountAttribute(): float
    {
        if (array_key_exists('discount_amount', $this->attributes) && $this->attributes['discount_amount'] !== null) {
            return round((float) $this->attributes['discount_amount'], 2);
        }

        if ($this->invoiceItem && (float) $this->invoiceItem->quantity > 0) {
            $perUnitDiscount = (float) $this->invoiceItem->discount_amount / (float) $this->invoiceItem->quantity;
            return round((float) $this->quantity * $perUnitDiscount, 2);
        }

        return 0.0;
    }

    // Net unit rate is the actual discounted sale rate used to derive refund defaults.
    public function getEffectiveNetUnitPriceAttribute(): float
    {
        if (array_key_exists('net_unit_price', $this->attributes) && $this->attributes['net_unit_price'] !== null) {
            return round((float) $this->attributes['net_unit_price'], 2);
        }

        if ($this->invoiceItem && (float) $this->invoiceItem->quantity > 0) {
            return round((float) $this->invoiceItem->subtotal / (float) $this->invoiceItem->quantity, 2);
        }

        return (float) $this->effective_unit_price;
    }

    // Keep refund state readable in tables and forms.
    public function getRefundStatusLabelAttribute(): string
    {
        if ((float) ($this->pending_credit_amount ?? 0) > 0) {
            return 'Pending Credit';
        }

        if ((float) ($this->cash_refund_amount ?? 0) > 0) {
            return 'Paid';
        }

        return 'Adjusted';
    }

    // Use Bootstrap badge classes instead of custom labels.
    public function getRefundStatusBadgeClassAttribute(): string
    {
        if ((float) ($this->pending_credit_amount ?? 0) > 0) {
            return 'bg-warning text-dark';
        }

        if ((float) ($this->cash_refund_amount ?? 0) > 0) {
            return 'bg-success';
        }

        return 'bg-info text-dark';
    }

    // Show payment mode when an actual payout method was chosen.
    public function getPaymentModeLabelAttribute(): string
    {
        return $this->paymentMode?->name ?: '-';
    }
}
