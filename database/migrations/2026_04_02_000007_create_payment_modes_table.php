<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_modes')) {
            Schema::create('payment_modes', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->enum('type', ['cash', 'bank', 'digital'])->default('cash');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (DB::table('payment_modes')->count() === 0) {
            DB::table('payment_modes')->insert([
                [
                    'name' => 'Cash',
                    'type' => 'cash',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Bank',
                    'type' => 'bank',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_modes');
    }
};
