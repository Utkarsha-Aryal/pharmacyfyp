<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('party_type', ['customer', 'supplier'])->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->enum('entry_type', ['debit', 'credit']);
            $table->enum('account_type', ['cash', 'bank', 'receivable', 'payable', 'expense', 'income']);
            $table->decimal('amount', 10, 2);
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_date', 'account_type'], 'acct_tx_date_account_idx');
            $table->index(['transaction_date', 'entry_type'], 'acct_tx_date_entry_idx');
            $table->index(['party_type', 'party_id'], 'acct_tx_party_idx');
            $table->index(['reference_type', 'reference_id'], 'acct_tx_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
