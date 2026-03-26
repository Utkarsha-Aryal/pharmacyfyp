<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    protected $guarded = [];

    // The staff member who created the accounting entry.
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // If this entry belongs to a customer party, keep the relation easy to load.
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    // If this entry belongs to a supplier party, keep the relation easy to load.
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'party_id');
    }

    // Show the transaction date in a friendly format.
    public function getTransactionDateShowAttribute(): string
    {
        return $this->transaction_date ? Carbon::parse($this->transaction_date)->format('M j, Y') : '-';
    }

    // Show party name in one place so the finance tables stay small.
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

    // Turn debit and credit into a readable tag for the ledger page.
    public function getEntryLabelAttribute(): string
    {
        return ucfirst((string) $this->entry_type);
    }

    // Turn the account type into a simple title case label.
    public function getAccountLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->account_type));
    }
}
