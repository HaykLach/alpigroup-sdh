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
        Schema::create('customer_address', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->timestamps();
        });

        Schema::table('customer_address', function (Blueprint $table) {
            $table->foreignUuid('customer_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('customer_address', function (Blueprint $table) {
            $table->foreignUuid('address_id')
                ->after('customer_id')
                ->nullable()
                ->constrained('pim_customer_address')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_address');
    }
};
