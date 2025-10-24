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
        Schema::table('pim_orders', function (Blueprint $table) {
            $table->dropColumn('billing_address_id');
        });

        Schema::table('pim_orders', function (Blueprint $table) {
            $table->foreignUuid('billing_address_id')
                ->after('currency_id')
                ->nullable()
                ->constrained('pim_order_address')
                ->cascadeOnUpdate()
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
