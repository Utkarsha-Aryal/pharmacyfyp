<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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

        return ['admin' => 'Admin', 'staff' => 'Staff'];
    }
}

if (!function_exists('is_admin_user')) {
    function is_admin_user()
    {
        return Auth::check() && Auth::user()->hasRole('admin');
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
            if (!Schema::hasTable('products') || !Schema::hasTable('product_batches')) {
                return $notifications;
            }

            $today = Carbon::today();
            $nearDate = $today->copy()->addDays(30);

            $lowStockItems = Product::query()
                ->leftJoin('product_batches', function ($join) {
                    $join->on('products.id', '=', 'product_batches.product_id')
                        ->where('product_batches.status', 'Y');
                })
                ->where('products.status', 'Y')
                ->whereNotNull('products.alert_quantity')
                ->groupBy('products.id', 'products.product_name', 'products.alert_quantity')
                ->selectRaw('products.id, products.product_name, COALESCE(SUM(product_batches.quantity), 0) as current_stock, products.alert_quantity')
                ->havingRaw('COALESCE(SUM(product_batches.quantity), 0) <= products.alert_quantity')
                ->orderBy('current_stock')
                ->limit(6)
                ->get();

            foreach ($lowStockItems as $item) {
                $notifications[] = [
                    'title' => 'Low stock alert',
                    'message' => $item->product_name . ' is low. Stock: ' . $item->current_stock . ' / Alert: ' . $item->alert_quantity,
                    'url' => route('admin.report.lowstock'),
                    'color' => 'warning',
                ];
            }

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

            foreach ($expiryItems as $batch) {
                $expiryDate = ProductBatch::makeExpiryDate($batch->expiry_date);
                $isExpired = $expiryDate?->lt($today);

                $notifications[] = [
                    'title' => $isExpired ? 'Expired batch alert' : 'Expiry alert',
                    'message' => ($batch->product?->product_name ?? 'Medicine') . ' batch ' . ($batch->batch_no ?: '-') . ($isExpired ? ' is already expired on ' : ' expires on ') . ($expiryDate?->format('M Y') ?? $batch->expiry_date),
                    'url' => route('admin.report.expiry'),
                    'color' => $isExpired ? 'danger' : 'warning',
                ];
            }
        } catch (Throwable $th) {
            return [];
        }

        return $notifications;
    }
}

if (!function_exists('admin_notification_count')) {
    function admin_notification_count()
    {
        return count(admin_notifications());
    }
}
