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
        Schema::table('pim_order_delivery', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_address_id');
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('pim_order_delivery', function (Blueprint $table) {
            $table->foreignUuid('shipping_address_id')
                ->after('id')
                ->constrained('pim_order_address')
                ->cascadeOnDelete();
        });

        Schema::table('pim_order_delivery', function (Blueprint $table) {
            $table->foreignUuid('order_id')
                ->after('id')
                ->constrained('pim_orders')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
