<?php

use App\Models\Batch;
use App\Models\AccountTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Mail\SystemStatusMail;
use App\Mail\AdminNotificationDigestMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Mail\MailManager;
use Spatie\Permission\Models\Role;

if (!function_exists('settings_cache')) {
    function settings_cache(bool $refresh = false)
    {
        static $settings = null;

        if ($refresh) {
            $settings = null;
        }

        if ($settings !== null) {
            return $settings;
        }

        try {
            if (!Schema::hasTable('settings')) {
                $settings = collect();
                return $settings;
            }

            $settings = Setting::query()->pluck('value', 'key');
        } catch (Throwable $th) {
            $settings = collect();
        }

        return $settings;
    }
}

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        $settings = settings_cache();

        if (!$settings->has($key) || $settings->get($key) === null || $settings->get($key) === '') {
            return $default;
        }

        return $settings->get($key);
    }
}

if (!function_exists('app_favicon_url')) {
    function app_favicon_url()
    {
        $favicon = setting('favicon');
        $faviconPath = ltrim((string) $favicon, '/');

        if (!empty($faviconPath) && file_exists(public_path($faviconPath))) {
            return asset($faviconPath);
        }

        return asset('favicon.ico');
    }
}

if (!function_exists('app_logo_url')) {
    function app_logo_url()
    {
        $logo = setting('app_logo');
        $logoPath = ltrim((string) $logo, '/');

        if (!empty($logoPath) && file_exists(public_path($logoPath))) {
            return asset($logoPath);
        }

        return asset('assets/img/logo/pharmacy.png');
    }
}

if (!function_exists('available_roles')) {
    function available_roles()
    {
        try {
            if (Schema::hasTable('roles')) {
                $roles = Role::query()->orderBy('name')->pluck('name', 'name')->toArray();

                if (!empty($roles)) {
                    return collect($roles)->mapWithKeys(function ($roleName) {
                        return [$roleName => ucfirst($roleName)];
                    })->toArray();
                }
            }
        } catch (Throwable $th) {
            // keep simple fallback for install and first migrate
        }

        return [
            'admin' => 'Admin',
            'staff' => 'Staff',
            'procurement' => 'Procurement',
        ];
    }
}

if (!function_exists('is_admin_user')) {
    function is_admin_user()
    {
        return Auth::check() && Auth::user()->hasRole('admin');
    }
}

if (!function_exists('admin_notification_key')) {
    function admin_notification_key(array $parts)
    {
        return 'notif-' . substr(sha1(implode('|', array_map(fn ($part) => (string) $part, $parts))), 0, 16);
    }
}

if (!function_exists('admin_notifications')) {
    function admin_notifications()
    {
        static $notifications = false;

        if ($notifications !== false) {
            return $notifications;
        }

        $notifications = [];

        try {
            if (!Schema::hasTable('products')) {
                return $notifications;
            }

            $today = Carbon::today();
            $nearDate = $today->copy()->addDays(30);

            $batchTable = Schema::hasTable('batches') ? 'batches' : 'product_batches';
            $batchQuantityColumn = $batchTable === 'batches' ? 'quantity_available' : 'quantity';

            $lowStockItems = Product::query()
                ->leftJoin($batchTable, function ($join) use ($batchTable) {
                    $join->on('products.id', '=', $batchTable . '.product_id');
                    if ($batchTable === 'product_batches') {
                        $join->where($batchTable . '.status', 'Y');
                    }
                    if ($batchTable === 'batches') {
                        $join->where($batchTable . '.is_active', true);
                    }
                })
                ->where('products.status', 'Y')
                ->groupBy('products.id', 'products.product_name', 'products.alert_quantity', 'products.reorder_level')
                ->selectRaw('products.id, products.product_name, COALESCE(products.reorder_level, products.alert_quantity, 10) as reorder_level, COALESCE(SUM(' . $batchTable . '.' . $batchQuantityColumn . '), 0) as current_stock')
                ->havingRaw('COALESCE(SUM(' . $batchTable . '.' . $batchQuantityColumn . '), 0) <= COALESCE(products.reorder_level, products.alert_quantity, 10)')
                ->orderBy('current_stock')
                ->limit(6)
                ->get();

            foreach ($lowStockItems as $item) {
                $notifications[] = [
                    'id' => admin_notification_key(['low-stock', $item->id]),
                    'title' => 'Low stock alert',
                    'message' => $item->product_name . ' is low. Stock: ' . $item->current_stock . ' / Alert: ' . $item->reorder_level,
                    'url' => route('admin.report.lowstock'),
                    'color' => 'warning',
                ];
            }

            if (Schema::hasTable('batches')) {
                $expiryItems = Batch::query()
                    ->with('product')
                    ->where('is_active', true)
                    ->whereDate('expiry_date', '<=', $nearDate)
                    ->orderBy('expiry_date')
                    ->take(6)
                    ->get();
            } else {
                $expiryItems = ProductBatch::query()
                    ->with('product')
                    ->where('status', 'Y')
                    ->get()
                    ->filter(function ($batch) use ($today, $nearDate) {
                        $expiryDate = ProductBatch::makeExpiryDate($batch->expiry_date);

                        return $expiryDate && $expiryDate->lte($nearDate);
                    })
                    ->sortBy('expiry_date')
                    ->take(6);
            }

            foreach ($expiryItems as $batch) {
                $expiryDate = Schema::hasTable('batches')
                    ? Batch::makeExpiryDate($batch->expiry_date)
                    : ProductBatch::makeExpiryDate($batch->expiry_date);
                $isExpired = $expiryDate?->lt($today);

                $notifications[] = [
                    'id' => admin_notification_key(['expiry', $batch->id]),
                    'title' => $isExpired ? 'Expired batch alert' : 'Expiry alert',
                    'message' => ($batch->product?->display_name ?? $batch->product?->product_name ?? 'Medicine') . ' batch ' . ($batch->batch_number ?? $batch->batch_no ?: '-') . ($isExpired ? ' is already expired on ' : ' expires on ') . ($expiryDate?->format('M Y') ?? $batch->expiry_date),
                    'url' => route('admin.report.expiry'),
                    'color' => $isExpired ? 'danger' : 'warning',
                ];
            }

            if (Schema::hasTable('purchase_orders')) {
                $pendingOrders = PurchaseOrder::query()->where('status', 'pending')->count();

                if ($pendingOrders > 0) {
                    $notifications[] = [
                        'id' => admin_notification_key(['pending-orders']),
                        'title' => 'Pending purchase order',
                        'message' => $pendingOrders . ' purchase order(s) still need action.',
                        'url' => route('admin.purchase-orders.index'),
                        'color' => 'warning',
                    ];
                }
            }
        } catch (Throwable $th) {
            return [];
        }

        return $notifications;
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol()
    {
        return trim((string) setting('currency_symbol', 'NPR'));
    }
}

if (!function_exists('low_stock_threshold')) {
    function low_stock_threshold()
    {
        return (int) setting('low_stock_threshold', 10);
    }
}

if (!function_exists('admin_notification_count')) {
    function admin_notification_count()
    {
        return count(admin_notifications());
    }
}

if (!function_exists('human_date')) {
    function human_date($value, $default = '-')
    {
        if (empty($value)) {
            return $default;
        }

        try {
            return Carbon::parse($value)->format('M j, Y');
        } catch (Throwable $th) {
            return $default;
        }
    }
}

if (!function_exists('money_value')) {
    function money_value($value, $default = '0.00')
    {
        $formattedValue = $value === null || $value === ''
            ? (string) $default
            : number_format((float) $value, 2);

        $symbol = currency_symbol();

        return $symbol !== '' ? $symbol . ' ' . $formattedValue : $formattedValue;
    }
}

if (!function_exists('pdf_company_context')) {
    function pdf_company_context(): array
    {
        return [
            'company_name' => setting('app_name', 'Pharmacy Management System'),
            'company_address' => trim(strip_tags((string) setting('company_address', ''))),
            'company_phone' => setting('company_phone', ''),
            'company_email' => setting('company_email', ''),
            'company_logo' => setting('app_logo'),
            'footer_text' => setting('print_footer_text', 'Thank you for your business'),
        ];
    }
}

if (!function_exists('pdf_logo_src')) {
    function pdf_logo_src(): ?string
    {
        $company = pdf_company_context();
        $logoPath = $company['company_logo'] ?? null;

        if (empty($logoPath)) {
            return null;
        }

        $fullPath = public_path(str_starts_with($logoPath, 'storage/') ? $logoPath : ltrim($logoPath, '/'));

        if (!is_file($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: 'image/png';
        $binary = file_get_contents($fullPath);

        if ($binary === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }
}

if (!function_exists('next_sales_reference')) {
    function next_sales_reference(): string
    {
        try {
            $datePart = now()->format('ymd');
            $count = SalesInvoice::query()->whereDate('created_at', now()->toDateString())->count() + 1;

            return 'INV-' . $datePart . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $th) {
            return 'INV-' . now()->format('ymd') . '-0001';
        }
    }
}

if (!function_exists('record_account_transaction')) {
    function record_account_transaction(array $payload)
    {
        if (!Schema::hasTable('account_transactions')) {
            return null;
        }

        return AccountTransaction::create([
            'transaction_date' => $payload['transaction_date'] ?? now()->toDateString(),
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id' => $payload['reference_id'] ?? null,
            'party_type' => $payload['party_type'] ?? null,
            'party_id' => $payload['party_id'] ?? null,
            'entry_type' => $payload['entry_type'],
            'account_type' => $payload['account_type'],
            'amount' => $payload['amount'],
            'notes' => $payload['notes'] ?? null,
            'created_by' => $payload['created_by'] ?? auth()->id(),
        ]);
    }
}

if (!function_exists('record_stock_movement')) {
    function record_stock_movement(array $payload)
    {
        if (!Schema::hasTable('stock_movements')) {
            return null;
        }

        return StockMovement::create([
            'movement_date' => $payload['movement_date'] ?? now()->toDateString(),
            'product_id' => $payload['product_id'],
            'batch_id' => $payload['batch_id'] ?? null,
            'movement_type' => $payload['movement_type'],
            'quantity_in' => (int) ($payload['quantity_in'] ?? 0),
            'quantity_out' => (int) ($payload['quantity_out'] ?? 0),
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'destination_type' => $payload['destination_type'] ?? null,
            'destination_id' => $payload['destination_id'] ?? null,
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id' => $payload['reference_id'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'created_by' => $payload['created_by'] ?? auth()->id(),
        ]);
    }
}

if (!function_exists('notification_email_address')) {
    function notification_email_address(): ?string
    {
        return notification_email_recipients()[0] ?? null;
    }
}

if (!function_exists('notification_email_recipients')) {
    function notification_email_recipients(): array
    {
        $mailSettings = current_mail_settings();
        $recipients = [];

        if (!empty($mailSettings['notification_email'])) {
            $recipients[] = trim((string) $mailSettings['notification_email']);
        }

        if (!empty($mailSettings['from_address'])) {
            $recipients[] = trim((string) $mailSettings['from_address']);
        }

        // Staff, procurement and admin users can also receive the same alert digest when email is needed.
        if (Schema::hasTable('users') && Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $roleEmails = User::query()
                ->where('is_active', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'staff', 'procurement']);
                })
                ->pluck('email')
                ->map(fn ($email) => trim((string) $email))
                ->filter()
                ->toArray();

            $recipients = array_merge($recipients, $roleEmails);
        }

        return array_values(array_unique(array_filter($recipients)));
    }
}

if (!function_exists('current_mail_settings')) {
    function current_mail_settings(array $overrides = []): array
    {
        $value = function (string $settingKey, ?string $envKey = null, mixed $default = null) use ($overrides) {
            if (array_key_exists($settingKey, $overrides) && trim((string) $overrides[$settingKey]) !== '') {
                return $overrides[$settingKey];
            }

            $settingValue = settings_cache()->get($settingKey);

            if ($settingValue !== null && trim((string) $settingValue) !== '') {
                return $settingValue;
            }

            if ($envKey !== null) {
                $envValue = env($envKey);

                if ($envValue !== null && trim((string) $envValue) !== '') {
                    return $envValue;
                }
            }

            return $default;
        };

        $hasDatabaseSmtp = collect(['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'])
            ->contains(function ($key) use ($overrides) {
                if (array_key_exists($key, $overrides) && trim((string) $overrides[$key]) !== '') {
                    return true;
                }

                $settingValue = settings_cache()->get($key);

                return $settingValue !== null && trim((string) $settingValue) !== '';
            });

        $appName = $value('app_name', 'APP_NAME', 'Pharmacy Management System');
        $mailer = $value('mail_mailer', null);
        $host = $value('smtp_host', 'MAIL_HOST');

        return [
            'mailer' => $mailer ?: ($hasDatabaseSmtp || $host ? 'smtp' : env('MAIL_MAILER', 'smtp')),
            'host' => $host,
            'port' => $value('smtp_port', 'MAIL_PORT'),
            'username' => $value('smtp_username', 'MAIL_USERNAME'),
            'password' => $value('smtp_password', 'MAIL_PASSWORD'),
            'encryption' => $value('smtp_encryption', 'MAIL_SCHEME'),
            'from_address' => $value('mail_from_address', 'MAIL_FROM_ADDRESS'),
            'from_name' => $value('mail_from_name', 'MAIL_FROM_NAME', $appName),
            'notification_email' => $value('notification_email', 'MAIL_FROM_ADDRESS'),
            'app_name' => $appName,
        ];
    }
}

if (!function_exists('missing_smtp_mail_settings')) {
    function missing_smtp_mail_settings(array $mailSettings): array
    {
        $mailer = strtolower((string) ($mailSettings['mailer'] ?? 'smtp'));

        if (!in_array($mailer, ['smtp', 'smtps'], true)) {
            return ['SMTP mailer'];
        }

        $requiredFields = [
            'host' => 'SMTP host',
            'port' => 'SMTP port',
            'username' => 'SMTP username',
            'password' => 'SMTP password',
            'from_address' => 'mail from address',
        ];

        return collect($requiredFields)
            ->filter(fn ($label, $key) => trim((string) ($mailSettings[$key] ?? '')) === '')
            ->values()
            ->all();
    }
}

if (!function_exists('apply_runtime_mail_settings')) {
    function apply_runtime_mail_settings(array $overrides = []): array
    {
        $mailSettings = current_mail_settings($overrides);
        $rawScheme = strtolower((string) ($mailSettings['encryption'] ?? ''));
        $smtpScheme = in_array($rawScheme, ['ssl', 'smtps'], true) ? 'smtps' : 'smtp';

        // This helper keeps controller, command and alert mails all on the same SMTP config.
        config([
            'app.name' => $mailSettings['app_name'] ?: config('app.name'),
            'mail.default' => $mailSettings['mailer'] ?: 'smtp',
            'mail.mailers.smtp.host' => $mailSettings['host'],
            'mail.mailers.smtp.port' => $mailSettings['port'],
            'mail.mailers.smtp.username' => $mailSettings['username'],
            'mail.mailers.smtp.password' => $mailSettings['password'],
            'mail.mailers.smtp.scheme' => $smtpScheme ?: null,
            'mail.from.address' => $mailSettings['from_address'],
            'mail.from.name' => $mailSettings['from_name'],
        ]);

        app(MailManager::class)->forgetMailers();

        return $mailSettings;
    }
}

if (!function_exists('send_system_notification_mail')) {
    function send_system_notification_mail(string $subject, string $title, string $intro, array $lines = [], string|array|null $recipient = null): bool
    {
        $recipients = $recipient
            ? (array) $recipient
            : notification_email_recipients();
        $recipients = array_values(array_unique(array_filter(array_map(fn ($email) => trim((string) $email), $recipients))));

        if (empty($recipients)) {
            return false;
        }

        try {
            apply_runtime_mail_settings();

            $allRecipients = $recipients;
            $primaryRecipient = array_shift($recipients);
            $mail = Mail::to($primaryRecipient);

            if (!empty($recipients)) {
                $mail->bcc($recipients);
            }

            $mail->send(new SystemStatusMail(
                mailSubject: $subject,
                title: $title,
                intro: $intro,
                lines: $lines
            ));

            return true;
        } catch (Throwable $th) {
            Log::warning('System notification email could not be sent.', [
                'recipient' => $allRecipients ?? $recipients,
                'message' => $th->getMessage(),
            ]);

            return false;
        }
    }
}

if (!function_exists('send_admin_notification_digest')) {
    function send_admin_notification_digest(string|array|null $recipient = null): bool
    {
        $recipients = $recipient
            ? (array) $recipient
            : notification_email_recipients();
        $notifications = admin_notifications();

        $recipients = array_values(array_unique(array_filter(array_map(fn ($email) => trim((string) $email), $recipients))));

        if (empty($recipients) || empty($notifications)) {
            return false;
        }

        try {
            apply_runtime_mail_settings();

            $allRecipients = $recipients;
            $primaryRecipient = array_shift($recipients);
            $mail = Mail::to($primaryRecipient);

            if (!empty($recipients)) {
                $mail->bcc($recipients);
            }

            $mail->send(new AdminNotificationDigestMail($notifications));

            return true;
        } catch (Throwable $th) {
            Log::warning('Admin notification digest could not be sent.', [
                'recipient' => $allRecipients ?? $recipients,
                'message' => $th->getMessage(),
            ]);

            return false;
        }
    }
}
