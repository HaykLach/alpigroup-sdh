<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pim_cache_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('class')->index();
            $table->string('provider')->index();
            $table->string('from_lang')->index();
            $table->string('to_lang')->index();
            $table->text('input');
            $table->text('translation');
            $table->boolean('successful')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_cache_translations');
    }
};
