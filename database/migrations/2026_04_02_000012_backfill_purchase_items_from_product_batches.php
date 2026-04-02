<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_items') || !Schema::hasTable('product_batches') || !Schema::hasTable('purchases')) {
            return;
        }

        $existingPurchaseIds = DB::table('purchase_items')->pluck('purchase_id')->unique()->all();

        DB::table('product_batches')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($existingPurchaseIds) {
                foreach ($rows as $row) {
                    $purchase = DB::table('purchases')
                        ->where('reference_id', $row->reference_id)
                        ->first();

                    if (!$purchase) {
                        continue;
                    }

                    if (in_array($purchase->id, $existingPurchaseIds, true)) {
                        continue;
                    }

                    $inventoryBatch = Schema::hasTable('batches')
                        ? DB::table('batches')
                            ->where('product_id', $row->product_id)
                            ->where('supplier_id', $row->supplier_id)
                            ->where('batch_number', $row->batch_no)
                            ->first()
                        : null;

                    // Old records are mixed. Some expiry values are full dates and some are only year-month.
                    $expiryDate = $this->normalizeExpiryDate($row->expiry_date ?? null);

                    DB::table('purchase_items')->insert([
                        'purchase_id' => $purchase->id,
                        'product_id' => $row->product_id,
                        'batch_id' => $inventoryBatch->id ?? null,
                        'batch_no' => $row->batch_no,
                        'expiry_date' => $expiryDate,
                        'quantity' => max(0, (int) $row->quantity - (int) ($row->free_qty ?? 0)),
                        'free_qty' => (int) ($row->free_qty ?? 0),
                        'mrp' => $row->mrp ?? 0,
                        'rate' => $row->purchase_price ?? 0,
                        'cc_rate' => $row->cc_rate ?? 0,
                        'discount_percent' => $row->discount_percent ?? 0,
                        'discount_amount' => round((((float) $row->purchase_price * max(0, (int) $row->quantity - (int) ($row->free_qty ?? 0))) * (float) ($row->discount_percent ?? 0)) / 100, 2),
                        'free_goods_value' => $row->free_goods_value ?? 0,
                        'amount' => $row->subtotal ?? 0,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Keep historical purchase item rows on rollback because they may be used after the first backfill.
    }

    private function normalizeExpiryDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m', $value)->endOfMonth()->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
};
