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
            $table->string('indentifier', 255)->nullable()->unique();
            $table->string('product_number', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('stock')->nullable();
            $table->json('prices')->nullable();
            $table->boolean('active')->nullable();
            $table->json('custom_fields')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_products', function (Blueprint $table) {
            $table->dropColumn([
                'product_number',
                'name',
                'description',
                'stock',
                'prices',
                'active',
                'custom_fields',
            ]);
        });
    }
};
