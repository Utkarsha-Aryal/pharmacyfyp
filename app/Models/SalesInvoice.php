<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        // Generate a friendly invoice code so the sales screen stays quick to use.
        static::creating(function (SalesInvoice $invoice) {
            if (empty($invoice->reference)) {
                $invoice->reference = static::makeReference();
            }
        });
    }

    // Each sale belongs to one customer.
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // The staff member who opened or confirmed the invoice.
    public function soldBy()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    // Direct invoice payments can use the shared payment mode master too.
    public function paymentMode()
    {
        return $this->belongsTo(DropdownOption::class, 'payment_mode_id');
    }

    // Sale type is now also driven from the shared dropdown options table.
    public function saleTypeOption()
    {
        return $this->belongsTo(DropdownOption::class, 'sale_type_id');
    }

    // Keep a direct link to the creator for audit style tracing.
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Item rows for the invoice.
    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id');
    }

    // Return rows linked back to this invoice.
    public function returns()
    {
        return $this->hasMany(SalesReturn::class, 'sales_invoice_id');
    }

    // Payment allocations help Payment In link money against one or more invoices.
    public function paymentAllocations()
    {
        return $this->hasMany(PaymentBillAllocation::class, 'bill_id')->where('bill_type', 'sales_invoice');
    }

    // Build a unique invoice reference using the current date.
    public static function makeReference(): string
    {
        $datePart = now()->format('ymd');
        $count = static::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'INV-' . $datePart . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    // Show the invoice date in a friendlier format.
    public function getInvoiceDateShowAttribute(): string
    {
        return $this->invoice_date ? Carbon::parse($this->invoice_date)->format('M j, Y') : '-';
    }

    // Convert the status code into a simple label.
    public function getStatusLabelAttribute(): string
    {
        return ucfirst((string) $this->status);
    }

    // Convert the payment code into a simple label.
    public function getPaymentLabelAttribute(): string
    {
        return ucfirst((string) $this->payment_status);
    }

    // Turn the sale type into a readable label for the invoice table.
    public function getSaleTypeLabelAttribute(): string
    {
        return (string) ($this->saleTypeOption?->name ?: ucfirst((string) $this->sale_type));
    }

    // Keep the payment method label simple for the show page.
    public function getPaymentMethodLabelAttribute(): string
    {
        if ($this->relationLoaded('paymentMode') && $this->paymentMode) {
            return $this->paymentMode->name;
        }

        if (empty($this->payment_method) || $this->payment_method === 'none') {
            return 'Not collected';
        }

        return ucfirst((string) $this->payment_method);
    }

    // This helps the view show a colored badge without repeating logic.
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'report-badge-warning',
            'cancelled' => 'report-badge-danger',
            default => 'report-badge-success',
        };
    }

    // This helps the view show a colored badge without repeating logic.
    public function getPaymentBadgeClassAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'report-badge-success',
            'partial' => 'report-badge-warning',
            default => 'report-badge-danger',
        };
    }

    // Show how much is still unpaid.
    public function getDueAmountAttribute(): float
    {
        return round(((float) $this->total_amount) - (float) ($this->paid_amount ?? 0), 2);
    }

    // Receivable summary also uses the same unpaid figure.
    public function getOutstandingAmountAttribute(): float
    {
        return $this->due_amount;
    }

    // Keep the payment status logic in one place so controllers stay simple.
    public static function resolvePaymentStatus(float $grandTotal, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $grandTotal) {
            return 'paid';
        }

        return 'partial';
    }
}
