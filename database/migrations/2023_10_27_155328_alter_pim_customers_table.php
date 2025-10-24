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
        Schema::table('pim_customers', function (Blueprint $table) {
            $table->foreignUuid('default_billing_address_id')
                ->after('default_payment_method')
                ->nullable()
                ->constrained('pim_customer_address')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('pim_customers', function (Blueprint $table) {
            $table->foreignUuid('default_shipping_address_id')
                ->after('default_billing_address_id')
                ->nullable()
                ->constrained('pim_customer_address')
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
