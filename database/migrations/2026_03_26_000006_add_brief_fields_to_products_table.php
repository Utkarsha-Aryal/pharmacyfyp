<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('products', 'formulation')) {
                $table->enum('formulation', ['tablet', 'capsule', 'syrup', 'injection', 'cream', 'drops', 'other'])
                    ->default('other')
                    ->after('category_id');
            }

            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit')->nullable()->after('formulation');
            }

            if (!Schema::hasColumn('products', 'reorder_level')) {
                $table->integer('reorder_level')->default(10)->after('unit');
            }

            if (!Schema::hasColumn('products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('reorder_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('products', 'reorder_level')) {
                $table->dropColumn('reorder_level');
            }

            if (Schema::hasColumn('products', 'unit')) {
                $table->dropColumn('unit');
            }

            if (Schema::hasColumn('products', 'formulation')) {
                $table->dropColumn('formulation');
            }

            if (Schema::hasColumn('products', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
