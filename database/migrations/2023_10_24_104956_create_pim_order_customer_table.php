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
        Schema::create('pim_order_customer', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('email');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('title')->nullable();
            $table->longText('vat_ids')->nullable();
            $table->string('company')->nullable();
            $table->longText('custom_fields')->nullable();
            $table->timestamps();
        });

        Schema::table('pim_order_customer', function (Blueprint $table) {
            $table->foreignUuid('customer_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('pim_order_customer', function (Blueprint $table) {
            $table->foreignUuid('order_id')
                ->after('customer_id')
                ->nullable()
                ->constrained('pim_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_order_customer');
    }
};
