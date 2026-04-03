<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dropdown_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('alias');
            $table->string('name');
            $table->string('data')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique(['alias', 'name']);
            $table->index(['alias', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropdown_options');
    }
};
