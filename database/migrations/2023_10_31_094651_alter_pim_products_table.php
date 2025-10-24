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
        Schema::table('pim_products', function (Blueprint $table) {
            $table->longText('description')->change();
        });

        Schema::table('pim_products', function (Blueprint $table) {
            $table->foreignUuid('pim_manufacturer_id')
                ->after('parent_id')
                ->nullable()
                ->constrained('pim_product_manufacturers');
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
