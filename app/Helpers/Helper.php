<?php

use App\Models\Batch;
use App\Models\AccountTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Setting;
use App\Mail\SystemStatusMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

if (!function_exists('settings_cache')) {
    function settings_cache()
    {
        static $settings = null;

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

        if (!empty($favicon)) {
            return asset($favicon);
        }

        return asset('favicon.ico');
    }
}

if (!function_exists('app_logo_url')) {
    function app_logo_url()
    {
        $logo = setting('app_logo');

        if (!empty($logo)) {
            return asset($logo);
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

if (!function_exists('default_tax_rate')) {
    function default_tax_rate()
    {
        return (float) setting('tax_rate', 13);
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

if (!function_exists('notification_email_address')) {
    function notification_email_address(): ?string
    {
        return setting('notification_email')
            ?: setting('mail_from_address')
            ?: config('mail.from.address');
    }
}

if (!function_exists('send_system_notification_mail')) {
    function send_system_notification_mail(string $subject, string $title, string $intro, array $lines = [], ?string $recipient = null): bool
    {
        $emailTo = $recipient ?: notification_email_address();

        if (empty($emailTo)) {
            return false;
        }

        try {
            Mail::to($emailTo)->send(new SystemStatusMail(
                mailSubject: $subject,
                title: $title,
                intro: $intro,
                lines: $lines
            ));

            return true;
        } catch (Throwable $th) {
            Log::warning('System notification email could not be sent.', [
                'recipient' => $emailTo,
                'message' => $th->getMessage(),
            ]);

            return false;
        }
    }
}
