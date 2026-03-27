<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SystemStatusMail;
use App\Models\Common;
use App\Models\Setting;
use Illuminate\Mail\MailManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SettingsController extends Controller
{
    // Show all app settings in one place with env fallback for the first setup.
    public function index()
    {
        return view('settings.index', [
            'settings' => [
                'app_name' => setting('app_name', 'Pharmacy Management System'),
                'app_logo' => setting('app_logo'),
                'company_email' => setting('company_email'),
                'company_phone' => setting('company_phone'),
                'company_address' => setting('company_address'),
                'favicon' => setting('favicon'),
                'smtp_host' => setting('smtp_host', env('MAIL_HOST')),
                'smtp_port' => setting('smtp_port', env('MAIL_PORT')),
                'smtp_username' => setting('smtp_username', env('MAIL_USERNAME')),
                'smtp_password' => setting('smtp_password', env('MAIL_PASSWORD')),
                'smtp_encryption' => setting('smtp_encryption', env('MAIL_SCHEME')),
                'mail_from_address' => setting('mail_from_address', env('MAIL_FROM_ADDRESS')),
                'mail_from_name' => setting('mail_from_name', env('MAIL_FROM_NAME')),
                'notification_email' => setting('notification_email', env('MAIL_FROM_ADDRESS')),
                'currency_symbol' => setting('currency_symbol', 'NPR'),
                'low_stock_threshold' => setting('low_stock_threshold', 10),
                'tax_rate' => setting('tax_rate', 13),
            ],
        ]);
    }

    // Save settings and keep the values simple so the admin panel stays easy to maintain.
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg'],
            'company_email' => ['nullable', 'email'],
            'company_phone' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:5000'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'string', 'max:255'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'notification_email' => ['nullable', 'email'],
            'currency_symbol' => ['nullable', 'string', 'max:20'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:1'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($request->hasFile('favicon')) {
            $validated['favicon'] = 'storage/settings/' . Common::uploadFile('settings', $request->file('favicon'));
        }

        if ($request->hasFile('app_logo')) {
            $validated['app_logo'] = 'storage/settings/' . Common::uploadFile('settings', $request->file('app_logo'));
        }

        foreach ($validated as $key => $value) {
            Setting::setValue($key, $value);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }

    // Send one SMTP test mail so admin can confirm the inbox is wired correctly.
    public function testMail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email'],
        ]);

        $recipient = $validated['email']
            ?? setting('notification_email')
            ?? setting('mail_from_address')
            ?? config('mail.from.address');

        if (empty($recipient)) {
            return back()->with('error', 'Please add notification email or mail from address before testing.');
        }

        $smtpHost = setting('smtp_host', env('MAIL_HOST'));
        $smtpPort = setting('smtp_port', env('MAIL_PORT'));
        $smtpUsername = setting('smtp_username', env('MAIL_USERNAME'));
        $smtpPassword = setting('smtp_password', env('MAIL_PASSWORD'));
        $rawSmtpScheme = setting('smtp_encryption', env('MAIL_SCHEME'));
        $smtpScheme = in_array(strtolower((string) $rawSmtpScheme), ['ssl', 'smtps'], true) ? 'smtps' : 'smtp';
        $mailFromAddress = setting('mail_from_address', env('MAIL_FROM_ADDRESS'));
        $mailFromName = setting('mail_from_name', env('MAIL_FROM_NAME', setting('app_name', config('app.name'))));

        if (empty($smtpHost) || empty($smtpPort) || empty($smtpUsername) || empty($smtpPassword)) {
            return back()->with('error', 'SMTP host, port, username and password are required before sending test mail.');
        }

        // Refresh the mailer once with the saved values so the test button checks the real current setup.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $smtpHost,
            'mail.mailers.smtp.port' => $smtpPort,
            'mail.mailers.smtp.username' => $smtpUsername,
            'mail.mailers.smtp.password' => $smtpPassword,
            'mail.mailers.smtp.scheme' => $smtpScheme ?: null,
            'mail.from.address' => $mailFromAddress,
            'mail.from.name' => $mailFromName,
        ]);

        app(MailManager::class)->forgetMailers();

        try {
            Mail::to($recipient)->send(new SystemStatusMail(
                mailSubject: 'SMTP Test Mail',
                title: 'SMTP connection is working',
                intro: 'This is a quick test mail from the pharmacy management system.',
                lines: [
                    'Mail host: ' . ($smtpHost ?: 'Not set'),
                    'Mail port: ' . ($smtpPort ?: 'Not set'),
                    'Generated at: ' . now()->format('M j, Y h:i A'),
                ]
            ));
        } catch (Throwable $throwable) {
            return back()->with('error', 'Test mail failed: ' . $throwable->getMessage());
        }

        return back()->with('success', 'Test mail sent to ' . $recipient . '.');
    }
}
