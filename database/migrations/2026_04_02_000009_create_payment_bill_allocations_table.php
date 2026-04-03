<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_bill_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->unsignedBigInteger('bill_id');
            $table->string('bill_type');
            $table->decimal('allocated_amount', 12, 2);
            $table->timestamps();

            $table->index(['bill_type', 'bill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_bill_allocations');
    }
};
