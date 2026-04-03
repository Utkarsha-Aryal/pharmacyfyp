<?php

namespace Database\Seeders;

use App\Models\DropdownOption;
use Illuminate\Database\Seeder;

class DropdownOptionSeeder extends Seeder
{
    // One shared seeder keeps all UI-managed dropdown values in one place.
    public function run(): void
    {
        $rows = [
            ['alias' => 'product_status', 'name' => 'In Stock', 'data' => null, 'status' => 1],
            ['alias' => 'product_status', 'name' => 'Out of Stock', 'data' => null, 'status' => 1],
            ['alias' => 'product_status', 'name' => 'Discontinued', 'data' => null, 'status' => 1],

            ['alias' => 'formulation', 'name' => 'Tablet', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Capsule', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Syrup', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Injection', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Cream', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Drops', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Powder', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Ointment', 'data' => null, 'status' => 1],
            ['alias' => 'formulation', 'name' => 'Gel', 'data' => null, 'status' => 1],

            ['alias' => 'sales_type', 'name' => 'Retail', 'data' => null, 'status' => 1],
            ['alias' => 'sales_type', 'name' => 'Wholesale', 'data' => null, 'status' => 1],
            ['alias' => 'sales_type', 'name' => 'Credit', 'data' => null, 'status' => 1],

            ['alias' => 'payment_mode', 'name' => 'Cash', 'data' => 'cash', 'status' => 1],
            ['alias' => 'payment_mode', 'name' => 'eSewa', 'data' => 'digital', 'status' => 1],
            ['alias' => 'payment_mode', 'name' => 'Khalti', 'data' => 'digital', 'status' => 1],
            ['alias' => 'payment_mode', 'name' => 'Bank Transfer', 'data' => 'bank', 'status' => 1],
            ['alias' => 'payment_mode', 'name' => 'Cheque', 'data' => 'bank', 'status' => 1],

            ['alias' => 'expense_category', 'name' => 'Salary', 'data' => null, 'status' => 1],
            ['alias' => 'expense_category', 'name' => 'Rent', 'data' => null, 'status' => 1],
            ['alias' => 'expense_category', 'name' => 'Utilities', 'data' => null, 'status' => 1],
            ['alias' => 'expense_category', 'name' => 'Supplies', 'data' => null, 'status' => 1],
            ['alias' => 'expense_category', 'name' => 'Maintenance', 'data' => null, 'status' => 1],
            ['alias' => 'expense_category', 'name' => 'Miscellaneous', 'data' => null, 'status' => 1],
        ];

        foreach ($rows as $row) {
            DropdownOption::query()->updateOrCreate(
                ['alias' => $row['alias'], 'name' => $row['name']],
                ['data' => $row['data'], 'status' => $row['status']]
            );
        }
    }
}
