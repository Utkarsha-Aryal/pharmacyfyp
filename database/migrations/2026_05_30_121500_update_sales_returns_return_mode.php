<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_returns') || Schema::hasColumn('sales_returns', 'return_mode')) {
            return;
        }

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->enum('return_mode', ['invoice', 'customer_product'])->default('invoice')->after('id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_returns') || !Schema::hasColumn('sales_returns', 'return_mode')) {
            return;
        }

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropColumn('return_mode');
        });
    }
};
