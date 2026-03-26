<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseReference extends Model
{
    protected $guarded = [];

    public function purchase()
    {
        return $this->hasOne(Purchase::class, 'reference_id');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class, 'reference_id');
    }

    public static function makeNewReference()
    {
        $datePart = now()->format('ymd');
        $count = static::whereDate('created_at', now()->toDateString())->count() + 1;

        return static::create([
            'reference_no' => 'PUR-' . $datePart . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT) . '-' . Str::upper(Str::random(2)),
            'used' => 'N',
        ]);
    }
}
