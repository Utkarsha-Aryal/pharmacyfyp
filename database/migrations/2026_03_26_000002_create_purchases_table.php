<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers', 'id');
            $table->foreignId('reference_id')->unique()->constrained('purchase_references', 'id');
            $table->string('invoice_no')->nullable();
            $table->date('purchase_date');
            $table->enum('order_status', ['pending', 'approved', 'received'])->default('received');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('remarks')->nullable();
            $table->enum('status', ['Y', 'N'])->default('Y');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
