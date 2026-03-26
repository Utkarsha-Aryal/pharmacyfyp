<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            'app_name' => 'Pharmacy Management System',
            'company_email' => 'admin@pharmacy.com',
            'mail_from_address' => 'admin@pharmacy.com',
            'mail_from_name' => 'Pharmacy Management System',
            'currency_symbol' => 'NPR',
            'low_stock_threshold' => 10,
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::setValue($key, $value);
        }
    }
}
