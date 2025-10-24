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
        Schema::create('pim_product_media', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('pim_media_id')
                ->constrained('pim_media')
                ->cascadeOnDelete();

            $table->foreignUuid('pim_product_id')
                ->constrained('pim_products')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_product_media', function (Blueprint $table) {
            $table->dropForeign('pim_product_media_pim_product_id_foreign');
            $table->dropForeign('pim_product_media_pim_media_id_foreign');
        });

        Schema::dropIfExists('pim_product_media');
    }
};
