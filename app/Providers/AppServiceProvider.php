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
        $appName = $settings->get('app_name') ?: env('APP_NAME', 'Pharmacy Management System');
        $mailFromAddress = $settings->get('mail_from_address') ?: env('MAIL_FROM_ADDRESS');
        $mailFromName = $settings->get('mail_from_name') ?: env('MAIL_FROM_NAME', $appName);
        $smtpHost = $settings->get('smtp_host') ?: env('MAIL_HOST');
        $smtpPort = $settings->get('smtp_port') ?: env('MAIL_PORT');
        $smtpUsername = $settings->get('smtp_username') ?: env('MAIL_USERNAME');
        $smtpPassword = $settings->get('smtp_password') ?: env('MAIL_PASSWORD');
        $rawSmtpScheme = $settings->get('smtp_encryption') ?: env('MAIL_SCHEME');
        $smtpScheme = in_array(strtolower((string) $rawSmtpScheme), ['ssl', 'smtps'], true) ? 'smtps' : 'smtp';

        // Use DB values first, but keep env fallback so a fresh install works before settings are saved.
        if (!empty($appName)) {
            config(['app.name' => $appName]);
        }

        if (!empty($mailFromAddress)) {
            config(['mail.from.address' => $mailFromAddress]);
        }

        if (!empty($mailFromName)) {
            config(['mail.from.name' => $mailFromName]);
        }

        // Keep SMTP settings dynamic so admin can change them from settings without editing code.
        if (!empty($smtpHost)) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $smtpHost,
                'mail.mailers.smtp.port' => $smtpPort,
                'mail.mailers.smtp.username' => $smtpUsername,
                'mail.mailers.smtp.password' => $smtpPassword,
                'mail.mailers.smtp.scheme' => $smtpScheme ?: null,
            ]);
        }
    }
}
