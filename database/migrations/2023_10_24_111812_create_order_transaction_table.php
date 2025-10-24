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
        Schema::create('pim_order_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('state');
            $table->longText('amount');
            $table->longText('custom_fields')->nullable();
            $table->timestamps();
        });

        Schema::table('pim_order_transaction', function (Blueprint $table) {
            $table->foreignUuid('order_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('pim_order_transaction', function (Blueprint $table) {
            $table->foreignUuid('payment_method_id')
                ->after('order_id')
                ->nullable()
                ->constrained('pim_payment_method')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_transaction');
    }
};
