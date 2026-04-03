<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];

    // Keep the selected master category attached to the expense row.
    public function expenseCategory()
    {
        return $this->belongsTo(DropdownOption::class, 'expense_category_id');
    }

    // Expense payment mode also comes from the same shared dropdown table now.
    public function paymentModeOption()
    {
        return $this->belongsTo(DropdownOption::class, 'payment_mode_id');
    }

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
        return (string) ($this->paymentModeOption?->name ?: ucfirst((string) $this->payment_mode));
    }

    // Let the Blade view keep its badge logic short.
    public function getPaymentBadgeClassAttribute(): string
    {
        $modeGroup = $this->paymentModeOption?->data ?: $this->payment_mode;

        return $modeGroup === 'bank' ? 'report-badge-info' : 'report-badge-warning';
    }

    // Prefer the linked master name, but keep the old text value as a safe fallback.
    public function getExpenseCategoryLabelAttribute(): string
    {
        return $this->expenseCategory?->name ?: ($this->category ?: '-');
    }
}
