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
        Schema::create('pim_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('order_number');
            $table->uuid('billing_address_id')->nullable();
            $table->longText('price');
            $table->dateTime('order_date_time');
            $table->date('order_date');
            $table->double('amount_total');
            $table->double('amount_net');
            $table->string('tax_status');
            $table->longText('shipping_costs');
            $table->double('shipping_total')->nullable();
            $table->longText('custom_fields')->nullable();

            $table->timestamps();
        });

        Schema::table('pim_orders', function (Blueprint $table) {
            $table->foreignUuid('currency_id')
                ->after('order_number')
                ->nullable()
                ->constrained('pim_currency')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_orders');
    }
};
