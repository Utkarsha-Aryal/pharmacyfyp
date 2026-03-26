<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];

    // The staff member who saved this expense.
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Show the expense date in a friendly format.
    public function getExpenseDateShowAttribute(): string
    {
        return $this->expense_date ? Carbon::parse($this->expense_date)->format('M j, Y') : '-';
    }

    // Turn the payment mode into a neat label for the table.
    public function getPaymentModeLabelAttribute(): string
    {
        return ucfirst((string) $this->payment_mode);
    }

    // Let the Blade view keep its badge logic short.
    public function getPaymentBadgeClassAttribute(): string
    {
        return $this->payment_mode === 'bank' ? 'report-badge-info' : 'report-badge-warning';
    }
}
