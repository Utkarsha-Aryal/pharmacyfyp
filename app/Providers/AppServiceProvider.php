<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $settings = Setting::query()->pluck('value', 'key');

        if ($settings->has('app_name')) {
            config(['app.name' => $settings->get('app_name')]);
        }

        if ($settings->has('mail_from_address')) {
            config(['mail.from.address' => $settings->get('mail_from_address')]);
        }

        if ($settings->has('mail_from_name')) {
            config(['mail.from.name' => $settings->get('mail_from_name')]);
        }

        // use smtp config only when admin has filled it from settings page
        if ($settings->has('smtp_host') && !empty($settings->get('smtp_host'))) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $settings->get('smtp_host'),
                'mail.mailers.smtp.port' => $settings->get('smtp_port'),
                'mail.mailers.smtp.username' => $settings->get('smtp_username'),
                'mail.mailers.smtp.password' => $settings->get('smtp_password'),
                'mail.mailers.smtp.encryption' => $settings->get('smtp_encryption'),
            ]);
        }
    }
}
