<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    // Seed the common defaults once so the admin settings page is not empty after fresh install.
    public function run(): void
    {
        $defaultSettings = [
            'app_name' => 'Pharmacy Management System',
            'company_email' => 'admin@pharmacy.com',
            'mail_from_address' => 'admin@pharmacy.com',
            'mail_from_name' => 'Pharmacy Management System',
            'notification_email' => 'admin@pharmacy.com',
            'smtp_host' => env('MAIL_HOST', 'sandbox.smtp.mailtrap.io'),
            'smtp_port' => env('MAIL_PORT', 2525),
            'smtp_username' => env('MAIL_USERNAME'),
            'smtp_password' => env('MAIL_PASSWORD'),
            'smtp_encryption' => env('MAIL_SCHEME'),
            'currency_symbol' => 'NPR',
            'low_stock_threshold' => 10,
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::setValue($key, $value);
        }
    }
}
