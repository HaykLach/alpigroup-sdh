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
        Schema::create('pim_order_delivery', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->longText('tracking_codes');
            $table->date('shipping_date_earliest');
            $table->date('shipping_date_latest');
            $table->longText('shipping_costs');
            $table->longText('custom_fields');

            $table->timestamps();
        });

        Schema::table('pim_order_delivery', function (Blueprint $table) {
            $table->foreignUuid('order_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('pim_order_delivery', function (Blueprint $table) {
            $table->foreignUuid('shipping_method_id')
                ->after('order_id')
                ->nullable()
                ->constrained('pim_shipping_method')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_order_delivery');
    }
};
