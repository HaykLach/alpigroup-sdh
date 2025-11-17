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
        Schema::create('sw6_sdk_customer_group_extension', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('sw_customer_group_id')->unique()->nullable();
            $table->timestamps();
        });

        Schema::table('sw6_sdk_customer_group_extension', function (Blueprint $table) {
            $table->foreignUuid('pim_customer_group_id')
                ->after('id')
                ->constrained('pim_customer_group')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sw_customer_group_extension');
    }
};
