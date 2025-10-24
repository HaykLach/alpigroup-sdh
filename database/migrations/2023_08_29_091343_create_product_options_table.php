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
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')
                ->constrained(
                    table: 'pim_products',
                    indexName: 'product_options_id_to_pim_products_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('option_id')
                ->constrained(
                    table: 'pim_property_group_option',
                    indexName: 'product_options_id_to_pim_property_group_option_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
