<?php

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
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
        return setting('currency_symbol', 'NPR');
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
