<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockAdjustment extends Model
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

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public static function applyAdjustment(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $batch = Batch::query()->lockForUpdate()->findOrFail($data['batch_id']);
            $quantity = (int) $data['quantity'];
            $type = $data['adjustment_type'];

            // simple stock movement rules so beginner can read the flow quickly
            if (in_array($type, ['subtract', 'expired', 'damaged'], true)) {
                $batch->quantity_available = max(0, (int) $batch->quantity_available - $quantity);
            } else {
                $batch->quantity_available = (int) $batch->quantity_available + $quantity;
                $batch->quantity_received = (int) $batch->quantity_received + $quantity;
            }

            $batch->save();

            return static::create([
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'adjusted_by' => $data['adjusted_by'],
                'created_by' => $data['created_by'] ?? $data['adjusted_by'],
                'updated_by' => $data['updated_by'] ?? $data['adjusted_by'],
                'adjustment_type' => $type,
                'quantity' => $quantity,
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }
}
