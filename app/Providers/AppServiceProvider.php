<?php

namespace App\Providers;

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
        $mailSettings = current_mail_settings();
        $rawSmtpScheme = $mailSettings['encryption'] ?? null;
        $smtpScheme = in_array(strtolower((string) $rawSmtpScheme), ['ssl', 'smtps'], true) ? 'smtps' : 'smtp';

        // Use DB values first, but keep env fallback so a fresh install works before settings are saved.
        if (!empty($mailSettings['app_name'])) {
            config(['app.name' => $mailSettings['app_name']]);
        }

        if (!empty($mailSettings['from_address'])) {
            config(['mail.from.address' => $mailSettings['from_address']]);
        }

        if (!empty($mailSettings['from_name'])) {
            config(['mail.from.name' => $mailSettings['from_name']]);
        }

        // Keep SMTP settings dynamic so admin can change them from settings without editing code.
        if (!empty($mailSettings['host'])) {
            config([
                'mail.default' => $mailSettings['mailer'] ?: 'smtp',
                'mail.mailers.smtp.host' => $mailSettings['host'],
                'mail.mailers.smtp.port' => $mailSettings['port'],
                'mail.mailers.smtp.username' => $mailSettings['username'],
                'mail.mailers.smtp.password' => $mailSettings['password'],
                'mail.mailers.smtp.scheme' => $smtpScheme ?: null,
            ]);
        }
    }
}
