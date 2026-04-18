<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->enum('account_type', ['cash', 'bank', 'receivable', 'payable', 'expense', 'income']);
            $table->enum('party_type', ['customer', 'supplier'])->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 10, 2);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['voucher_id', 'entry_type'], 'voucher_entries_voucher_entry_idx');
            $table->index(['party_type', 'party_id'], 'voucher_entries_party_idx');
            $table->index('account_type', 'voucher_entries_account_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_entries');
    }
};
