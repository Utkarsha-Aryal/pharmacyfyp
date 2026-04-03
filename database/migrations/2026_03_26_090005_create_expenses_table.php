<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->string('category');
            $table->unsignedBigInteger('expense_category_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->enum('payment_mode', ['cash', 'bank', 'digital'])->default('cash');
            $table->unsignedBigInteger('payment_mode_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_date', 'expense_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
