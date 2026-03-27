<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->index(['transaction_date', 'account_type'], 'acct_tx_date_account_idx');
            $table->index(['transaction_date', 'entry_type'], 'acct_tx_date_entry_idx');
            $table->index(['party_type', 'party_id'], 'acct_tx_party_idx');
            $table->index(['reference_type', 'reference_id'], 'acct_tx_reference_idx');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->index('invoice_date', 'sales_invoice_date_idx');
            $table->index(['customer_id', 'invoice_date'], 'sales_invoice_customer_date_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_date', 'expense_date_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['order_date', 'status'], 'purchase_order_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_order_date_status_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expense_date_idx');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex('sales_invoice_customer_date_idx');
            $table->dropIndex('sales_invoice_date_idx');
        });

        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropIndex('acct_tx_reference_idx');
            $table->dropIndex('acct_tx_party_idx');
            $table->dropIndex('acct_tx_date_entry_idx');
            $table->dropIndex('acct_tx_date_account_idx');
        });
    }
};
