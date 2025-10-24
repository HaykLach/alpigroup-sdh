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
        Schema::create('pim_property_group_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('language_id')
                ->constrained(
                    table: 'pim_languages',
                    indexName: 'pim_property_group_translations_id_to_language_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('property_group_id')
                ->constrained(
                    table: 'pim_property_group',
                    indexName: 'pim_property_group_translations_id_to_pim_property_group_id'
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
        Schema::dropIfExists('pim_property_group_translations');
    }
};
