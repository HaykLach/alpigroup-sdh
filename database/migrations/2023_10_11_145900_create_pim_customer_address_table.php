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
        Schema::create('pim_customer_address', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('zipcode');
            $table->string('country');
            $table->string('city');
            $table->string('street');
            $table->string('additional_address_line_1')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('region')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_customer_address');
    }
};
