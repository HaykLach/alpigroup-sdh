<?php

use App\Enums\Pim\PimQuatationListingItemVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_products', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('pim_quotation_id')
                ->constrained('pim_quotations')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->uuidMorphs('product');

            $table->integer('position')->default(0);
            $table->integer('quantity')->default(1);
            $table->float('discount_percentage')->default(0);
            $table->float('price_override')->nullable();
            $table->tinyInteger('visibility')->default(PimQuatationListingItemVisibility::PUBLIC->value);
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_products');
    }
};
