<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_invoices') && !Schema::hasColumn('sales_invoices', 'payment_mode_id')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->foreignId('payment_mode_id')->nullable()->after('payment_method')->constrained('payment_modes')->nullOnDelete();
            });
        }

        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'payment_mode_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignId('payment_mode_id')->nullable()->after('payment_status')->constrained('payment_modes')->nullOnDelete();
            });
        }

        if (Schema::hasTable('payment_modes')) {
            $cashId = DB::table('payment_modes')->whereRaw('LOWER(name) = ?', ['cash'])->value('id');
            $bankId = DB::table('payment_modes')->whereRaw('LOWER(name) = ?', ['bank'])->value('id');

            if ($cashId && Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'payment_method')) {
                $bankFallback = $bankId ?: $cashId;
                DB::table('sales_invoices')
                    ->whereNull('payment_mode_id')
                    ->update([
                        'payment_mode_id' => DB::raw("CASE WHEN LOWER(COALESCE(payment_method, 'cash')) = 'bank' THEN {$bankFallback} ELSE {$cashId} END"),
                    ]);
            }

            if ($cashId && Schema::hasTable('purchases')) {
                DB::table('purchases')
                    ->whereNull('payment_mode_id')
                    ->where('paid_amount', '>', 0)
                    ->update(['payment_mode_id' => $cashId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'payment_mode_id')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('payment_mode_id');
            });
        }

        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'payment_mode_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropConstrainedForeignId('payment_mode_id');
            });
        }
    }
};
