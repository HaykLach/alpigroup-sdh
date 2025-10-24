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
        Schema::create('pim_product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')
                ->constrained(
                    table: 'pim_products',
                    indexName: 'pim_product_categories_id_to_pim_products_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignUuid('category_id')
                ->constrained(
                    table: 'pim_categories',
                    indexName: 'pim_product_categories_id_to_pim_property_group_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_product_categories');
    }
};
