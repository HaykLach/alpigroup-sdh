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
        Schema::create('pim_order_address', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('zipcode');
            $table->string('city');
            $table->string('street');
            $table->string('additional_address_line_1')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('region')->nullable();
            $table->string('vat_id')->nullable();

            $table->timestamps();
        });

        Schema::table('pim_order_address', function (Blueprint $table) {
            $table->foreignUuid('order_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('pim_order_address', function (Blueprint $table) {
            $table->foreignUuid('country_id')
                ->after('zipcode')
                ->nullable()
                ->constrained('pim_country')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_order_address');
    }
};
