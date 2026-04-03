<?php

namespace Database\Seeders;

use App\Models\PartyType;
use Illuminate\Database\Seeder;

class PartyTypeSeeder extends Seeder
{
    // Seed the default party types so the forms always have a safe starting point.
    public function run(): void
    {
        foreach ([
            ['name' => 'Customer', 'code' => 'customer'],
            ['name' => 'Institution', 'code' => 'institution'],
        ] as $row) {
            PartyType::query()->updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'is_active' => true]
            );
        }
    }
}
