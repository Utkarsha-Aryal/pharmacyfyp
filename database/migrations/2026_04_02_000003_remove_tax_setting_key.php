<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'tax_rate')->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && !DB::table('settings')->where('key', 'tax_rate')->exists()) {
            DB::table('settings')->insert([
                'key' => 'tax_rate',
                'value' => '13',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
