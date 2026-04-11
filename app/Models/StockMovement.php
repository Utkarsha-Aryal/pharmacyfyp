<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMovementDateShowAttribute(): string
    {
        return $this->movement_date ? Carbon::parse($this->movement_date)->format('M j, Y') : '-';
    }

    public function getMovementTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->movement_type));
    }
}

