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
        Schema::create('pim_property_group_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('language_id')
                ->constrained(
                    table: 'pim_languages',
                    indexName: 'pim_property_group_option_translations_id_to_language_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name', 255);
            $table->smallInteger('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_property_group_option_translations');
    }
};
