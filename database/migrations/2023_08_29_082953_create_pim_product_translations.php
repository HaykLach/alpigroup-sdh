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
        Schema::create('pim_product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('language_id')
                ->constrained(
                    table: 'pim_languages',
                    indexName: 'pim_product_translations_id_to_language_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignUuid('product_id')
                ->constrained(
                    table: 'pim_products',
                    indexName: 'pim_product_translations_id_to_pim_products_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description');
            $table->json('custom_fields');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_product_translations');
    }
};
