<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pim_region', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->string('code')->nullable()->index();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('pim_region_translation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pim_region_id')
                ->constrained('pim_region')
                ->cascadeOnDelete();
            $table->foreignUuid('language_id')
                ->constrained('pim_languages')
                ->cascadeOnDelete();
            $table->string('name');
            $table->unique(['pim_region_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pim_region_translation');
        Schema::dropIfExists('pim_region');
    }
};
