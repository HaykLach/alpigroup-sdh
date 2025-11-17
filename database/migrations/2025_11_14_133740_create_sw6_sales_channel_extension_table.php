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
        Schema::create('sw6_sdk_sales_channel_extension', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('sw_sales_channel_id')->nullable();
            $table->timestamps();
        });

        Schema::table('sw6_sdk_sales_channel_extension', function (Blueprint $table) {
            $table->foreignUuid('pim_sales_channel_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_sales_channels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sw6_sdk_sales_channel_extension');
    }
};
