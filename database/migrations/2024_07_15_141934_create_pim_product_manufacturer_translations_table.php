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
        Schema::create('pim_product_manufacturer_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('language_id')
                ->constrained(
                    table: 'pim_languages',
                    indexName: 'pim_manufacturers_translations_id_to_language_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignUuid('manufacturer_id')
                ->constrained(
                    table: 'pim_product_manufacturers',
                    indexName: 'pim_manufacturers_translations_id_to_pim_manufacturers_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name', 255);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_product_manufacturer_translations');
    }
};
