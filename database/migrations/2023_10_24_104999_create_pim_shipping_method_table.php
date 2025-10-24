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
        Schema::create('pim_shipping_method', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamps();
        });

        Schema::table('pim_shipping_method', function (Blueprint $table) {
            $table->foreignUuid('delivery_time_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_delivery_time')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_shipping_method');
    }
};
