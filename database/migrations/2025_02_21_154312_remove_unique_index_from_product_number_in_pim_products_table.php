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
        Schema::table('pim_products', function (Blueprint $table) {
            $table->dropUnique('pim_products_product_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_products', function (Blueprint $table) {
            $table->unique('product_number', 'pim_products_product_number_unique');
        });
    }
};
