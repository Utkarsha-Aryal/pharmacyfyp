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

    // Save or update one adjustment and keep the batch quantity in sync in the same transaction.
    public static function saveAdjustment(array $data, ?self $adjustment = null): self
    {
        return DB::transaction(function () use ($data, $adjustment) {
            if ($adjustment) {
                $oldBatch = Batch::query()->lockForUpdate()->findOrFail($adjustment->batch_id);
                static::syncBatchQuantity($oldBatch, $adjustment->adjustment_type, (int) $adjustment->quantity, true);
            }

            $batch = Batch::query()->lockForUpdate()->findOrFail($data['batch_id']);
            $quantity = (int) $data['quantity'];
            $type = $data['adjustment_type'];

            static::syncBatchQuantity($batch, $type, $quantity);

            $adjustment = $adjustment ?: new static();

            $adjustment->fill([
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'adjusted_by' => $data['adjusted_by'],
                'created_by' => $data['created_by'] ?? $data['adjusted_by'],
                'updated_by' => $data['updated_by'] ?? $data['adjusted_by'],
                'adjustment_type' => $type,
                'quantity' => $quantity,
                'reason' => $data['reason'] ?? null,
            ]);

            $adjustment->save();

            return $adjustment;
        });
    }

    // Keep the old method name also working so other pages do not break while we improve this screen.
    public static function applyAdjustment(array $data): self
    {
        return static::saveAdjustment($data);
    }

    // Delete an adjustment and give the quantity effect back to the batch.
    public function deleteWithRollback(): void
    {
        DB::transaction(function () {
            $batch = Batch::query()->lockForUpdate()->findOrFail($this->batch_id);
            static::syncBatchQuantity($batch, $this->adjustment_type, (int) $this->quantity, true);
            $this->delete();
        });
    }

    // This small helper keeps add/subtract logic in one place, so create, edit and delete stay consistent.
    private static function syncBatchQuantity(Batch $batch, string $type, int $quantity, bool $reverse = false): void
    {
        $isRemovalType = in_array($type, ['subtract', 'expired', 'damaged'], true);

        if ($reverse) {
            if ($isRemovalType) {
                $batch->quantity_available = (int) $batch->quantity_available + $quantity;
            } else {
                $batch->quantity_available = max(0, (int) $batch->quantity_available - $quantity);
                $batch->quantity_received = max(0, (int) $batch->quantity_received - $quantity);
            }
        } else {
            if ($isRemovalType) {
                $batch->quantity_available = max(0, (int) $batch->quantity_available - $quantity);
            } else {
                $batch->quantity_available = (int) $batch->quantity_available + $quantity;
                $batch->quantity_received = (int) $batch->quantity_received + $quantity;
            }
        }

        $batch->save();
    }
}
