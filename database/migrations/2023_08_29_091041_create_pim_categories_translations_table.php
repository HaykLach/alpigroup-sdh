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
        Schema::create('pim_categories_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('language_id')
                ->constrained(
                    table: 'pim_languages',
                    indexName: 'pim_categories_translations_id_to_language_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignUuid('category_id')
                ->constrained(
                    table: 'pim_categories',
                    indexName: 'pim_categories_translations_id_to_pim_property_group_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_categories_translations');
    }
};
