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
        Schema::create('product_batches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->string('batch_no')->nullable();
    $table->string('expiry_date')->nullable();
    $table->integer('quantity')->default(0);
    $table->decimal('purchase_price', 8, 2)->nullable();
    $table->decimal('subtotal', 12, 2)->nullable(); // ← Fixed
    $table->enum('status', ['Y', 'N'])->default('Y');
    $table->foreignId('supplier_id')->constrained('suppliers','id');
    $table->foreignId('reference_id')->constrained('purchase_references','id');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
