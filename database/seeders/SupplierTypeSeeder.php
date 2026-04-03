<?php

namespace Database\Seeders;

use App\Models\SupplierType;
use Illuminate\Database\Seeder;

class SupplierTypeSeeder extends Seeder
{
    // Seed the default supplier types so the forms always have a safe starting point.
    public function run(): void
    {
        foreach ([
            ['name' => 'Credit', 'code' => 'credit'],
            ['name' => 'Debit', 'code' => 'debit'],
        ] as $row) {
            SupplierType::query()->updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'is_active' => true]
            );
        }
    }
}
