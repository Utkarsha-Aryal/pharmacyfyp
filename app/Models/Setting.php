<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    public static function getValue($key, $default = null)
    {
        $setting = static::query()->where('key', $key)->value('value');

        return $setting !== null && $setting !== '' ? $setting : $default;
    }

    public static function setValue($key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
