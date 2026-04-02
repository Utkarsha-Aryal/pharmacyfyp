<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'tax_amount')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropColumn('tax_amount');
            });
        }

        if (Schema::hasTable('sales_invoice_items') && Schema::hasColumn('sales_invoice_items', 'tax_percent')) {
            Schema::table('sales_invoice_items', function (Blueprint $table) {
                $table->dropColumn('tax_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_invoices') && !Schema::hasColumn('sales_invoices', 'tax_amount')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('discount_amount');
            });
        }

        if (Schema::hasTable('sales_invoice_items') && !Schema::hasColumn('sales_invoice_items', 'tax_percent')) {
            Schema::table('sales_invoice_items', function (Blueprint $table) {
                $table->decimal('tax_percent', 10, 2)->default(0)->after('discount_percent');
            });
        }
    }
};
