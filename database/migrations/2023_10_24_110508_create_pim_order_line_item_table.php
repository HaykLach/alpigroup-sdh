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
        Schema::create('pim_order_line_item', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('identifier');
            $table->string('label');
            $table->mediumText('description')->nullable();
            $table->integer('quantity');
            $table->double('unit_price')->nullable();
            $table->double('total_price')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('price_definition')->nullable();
            $table->string('type')->nullable();

            $table->timestamps();
        });

        Schema::table('pim_order_line_item', function (Blueprint $table) {
            $table->foreignUuid('order_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_orders')
                ->cascadeOnDelete();
        });

        Schema::table('pim_order_line_item', function (Blueprint $table) {
            $table->foreignUuid('parent_id')
                ->after('order_id')
                ->nullable()
                ->constrained('pim_order_line_item')
                ->cascadeOnDelete();
        });

        Schema::table('pim_order_line_item', function (Blueprint $table) {
            $table->foreignUuid('product_id')
                ->after('identifier')
                ->nullable()
                ->constrained('pim_products')
                ->cascadeOnDelete();
        });

        Schema::table('pim_order_line_item', function (Blueprint $table) {
            $table->foreignUuid('promotion_id')
                ->after('product_id')
                ->nullable()
                ->constrained('pim_promotion')
                ->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_order_line_item');
    }
};
