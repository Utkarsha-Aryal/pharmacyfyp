<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->date('movement_date');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->enum('movement_type', [
                'purchase_in',
                'sales_out',
                'purchase_return_out',
                'sales_return_in',
                'adjustment_in',
                'adjustment_out',
            ]);
            $table->integer('quantity_in')->default(0);
            $table->integer('quantity_out')->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('destination_type')->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['movement_date', 'movement_type'], 'stock_mv_date_type_idx');
            $table->index(['product_id', 'movement_date'], 'stock_mv_product_date_idx');
            $table->index(['reference_type', 'reference_id'], 'stock_mv_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

