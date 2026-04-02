<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_batches')) {
            Schema::table('product_batches', function (Blueprint $table) {
                if (!Schema::hasColumn('product_batches', 'free_qty')) {
                    $table->integer('free_qty')->default(0)->after('quantity');
                }

                if (!Schema::hasColumn('product_batches', 'mrp')) {
                    $table->decimal('mrp', 10, 2)->default(0)->after('free_qty');
                }

                if (!Schema::hasColumn('product_batches', 'cc_rate')) {
                    $table->decimal('cc_rate', 5, 2)->default(0)->after('mrp');
                }

                if (!Schema::hasColumn('product_batches', 'discount_percent')) {
                    $table->decimal('discount_percent', 5, 2)->default(0)->after('cc_rate');
                }

                if (!Schema::hasColumn('product_batches', 'free_goods_value')) {
                    $table->decimal('free_goods_value', 10, 2)->default(0)->after('discount_percent');
                }
            });
        }

        if (Schema::hasTable('sales_invoice_items')) {
            Schema::table('sales_invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_invoice_items', 'free_qty')) {
                    $table->integer('free_qty')->default(0)->after('quantity');
                }

                if (!Schema::hasColumn('sales_invoice_items', 'mrp')) {
                    $table->decimal('mrp', 10, 2)->default(0)->after('unit_price');
                }

                if (!Schema::hasColumn('sales_invoice_items', 'cc_rate')) {
                    $table->decimal('cc_rate', 5, 2)->default(0)->after('mrp');
                }

                if (!Schema::hasColumn('sales_invoice_items', 'free_goods_value')) {
                    $table->decimal('free_goods_value', 10, 2)->default(0)->after('discount_percent');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_batches')) {
            Schema::table('product_batches', function (Blueprint $table) {
                foreach (['free_qty', 'mrp', 'cc_rate', 'discount_percent', 'free_goods_value'] as $column) {
                    if (Schema::hasColumn('product_batches', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('sales_invoice_items')) {
            Schema::table('sales_invoice_items', function (Blueprint $table) {
                foreach (['free_qty', 'mrp', 'cc_rate', 'free_goods_value'] as $column) {
                    if (Schema::hasColumn('sales_invoice_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
