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
        Schema::create('pim_customer_salutation_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('salutation_id')
                ->constrained('pim_customer_salutation')
                ->onDelete('cascade');

            $table->foreignUuid('language_id')
                ->constrained('pim_languages')
                ->onDelete('cascade');

            $table->string('letter_name')->nullable();
            $table->string('display_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_customer_salutation_translations');
    }
};
