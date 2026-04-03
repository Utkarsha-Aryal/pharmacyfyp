<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PartyType extends Model
{
    protected $guarded = [];

    // One party type can be used by many parties.
    public function customers()
    {
        return $this->hasMany(Customer::class, 'party_type', 'code');
    }

    // Keep the label nice for selects and tables.
    public function getDisplayNameAttribute(): string
    {
        return Str::headline((string) $this->name);
    }

    // Keep the code usable in old rows and dropdown values.
    public function getDisplayCodeAttribute(): string
    {
        return (string) $this->code;
    }
}
