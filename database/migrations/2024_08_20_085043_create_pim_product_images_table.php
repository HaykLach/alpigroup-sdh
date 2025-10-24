<?php

use App\Enums\Pim\PimProductImageStatus;
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
        Schema::create('pim_product_images', function (Blueprint $table) {
            $table->uuid('id');

            $table->foreignUuid('product_id')
                ->index()
                ->constrained(
                    table: 'pim_products',
                    indexName: 'pim_product_images_id_to_pim_products_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');

            $table->string('url')
                ->index();

            $table->enum('status', [PimProductImageStatus::PENDING->value, PimProductImageStatus::COMPLETE->value, PimProductImageStatus::FAILED->value])
                ->default(PimProductImageStatus::PENDING->value)
                ->index();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['product_id', 'url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_product_images');
    }
};
