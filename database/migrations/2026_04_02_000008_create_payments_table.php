<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['in', 'out']);
            $table->unsignedBigInteger('party_id');
            $table->string('party_type');
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_mode_id')->constrained('payment_modes')->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['party_type', 'party_id']);
            $table->index(['type', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
