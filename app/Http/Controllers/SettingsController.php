<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SystemStatusMail;
use App\Models\Common;
use App\Models\PaymentMode;
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
            ],
            'paymentModes' => PaymentMode::query()->orderBy('name')->get(),
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
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,ico'],
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
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'string', 'max:255'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'notification_email' => ['nullable', 'email'],
        ]);

        $recipient = $validated['email']
            ?? setting('notification_email')
            ?? setting('mail_from_address')
            ?? config('mail.from.address');

        if (empty($recipient)) {
            return $this->testMailResponse($request, false, 'Please add notification email or mail from address before testing.');
        }

        $mailSettings = apply_runtime_mail_settings($validated);

        if (empty($mailSettings['host']) || empty($mailSettings['port']) || empty($mailSettings['username']) || empty($mailSettings['password'])) {
            return $this->testMailResponse($request, false, 'SMTP host, port, username and password are required before sending test mail.');
        }

        try {
            Mail::to($recipient)->send(new SystemStatusMail(
                mailSubject: 'SMTP Test Mail',
                title: 'SMTP connection is working',
                intro: 'This is a quick test mail from the pharmacy management system.',
                lines: [
                    'Mail host: ' . ($mailSettings['host'] ?: 'Not set'),
                    'Mail port: ' . ($mailSettings['port'] ?: 'Not set'),
                    'Generated at: ' . now()->format('M j, Y h:i A'),
                ]
            ));
        } catch (Throwable $throwable) {
            return $this->testMailResponse($request, false, 'Test mail failed: ' . $throwable->getMessage());
        }

        return $this->testMailResponse($request, true, 'Test mail sent to ' . $recipient . '.');
    }

    // Keep ajax and normal post response in one place, so settings page can use one test-mail endpoint.
    private function testMailResponse(Request $request, bool $success, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'type' => $success ? 'success' : 'error',
                'message' => $message,
            ], $success ? 200 : 422);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }
}
