<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name')->nullable();
            $table->string('composition')->nullable();
            $table->string('group_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('description')->nullable();
            $table->decimal('previous_price',8,2)->nullable();
            $table->decimal('mrp',8,2)->nullable();
            $table->string('generic_name')->nullable();
            $table->enum('product_status',['instock','stockout'])->default('stockout');
            $table->string('slug')->nullable();
            $table->text('keywords')->nullable();
            $table->integer('order_number')->nullable();
            $table->integer('alert_quantity')->nullable();
            $table->integer('tab_per_quantity')->nullable();
            $table->foreignId('category_id')->constrained('categories', 'id');
            $table->string('image', 255)->nullable();
            $table->decimal('discount',8,2)->nullable();
            $table->decimal('display_price',8,2)->nullable();
            $table->enum('status', ['Y', 'N'])->default('Y');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
