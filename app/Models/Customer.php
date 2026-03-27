<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    // Keep the customer balance easy to read from anywhere in the app.
    public function getBalanceAttribute(): float
    {
        return round((float) $this->current_balance, 2);
    }

    // One party can have many sales invoices.
    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class, 'customer_id');
    }

    // One party can have many return records.
    public function salesReturns()
    {
        return $this->hasManyThrough(
            SalesReturn::class,
            SalesInvoice::class,
            'customer_id',
            'sales_invoice_id',
            'id',
            'id'
        );
    }

    // One party can have many accounting transactions.
    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'party_id')->where('party_type', 'customer');
    }

    // Let the party management table show a short badge label.
    public function getPartyTypeLabelAttribute(): string
    {
        return ucfirst((string) $this->party_type);
    }

    // Keep the party status label short and human friendly.
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
}
