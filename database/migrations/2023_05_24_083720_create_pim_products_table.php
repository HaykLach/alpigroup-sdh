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
        Schema::create('pim_products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pim_products', function (Blueprint $table) {
            $table->foreignUuid('parent_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_products')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });

        Schema::dropIfExists('pim_products');
    }
};
