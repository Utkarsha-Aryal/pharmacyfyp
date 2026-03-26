<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\Common;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('backend.settings.index', [
            'settings' => [
                'app_name' => setting('app_name', 'Pharmacy Management System'),
                'app_logo' => setting('app_logo'),
                'company_email' => setting('company_email'),
                'company_phone' => setting('company_phone'),
                'company_address' => setting('company_address'),
                'favicon' => setting('favicon'),
                'smtp_host' => setting('smtp_host'),
                'smtp_port' => setting('smtp_port'),
                'smtp_username' => setting('smtp_username'),
                'smtp_password' => setting('smtp_password'),
                'smtp_encryption' => setting('smtp_encryption'),
                'mail_from_address' => setting('mail_from_address'),
                'mail_from_name' => setting('mail_from_name'),
                'notification_email' => setting('notification_email'),
                'currency_symbol' => setting('currency_symbol', 'NPR'),
                'low_stock_threshold' => setting('low_stock_threshold', 10),
            ],
        ]);
    }

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
}
