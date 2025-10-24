<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_customer', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('pim_quotation_id')
                ->constrained('pim_quotations')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignUuid('pim_customer_id')
                ->constrained('pim_customers')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_customer');
    }
};
