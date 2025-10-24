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
        Schema::table('pim_customer_address', function (Blueprint $table) {
            $table->string('first_name')->after('country_id')->nullable();
            $table->string('last_name')->after('first_name')->nullable();
            $table->longText('custom_fields')->after('vat_id')->nullable();
        });

        Schema::table('pim_customer_address', function (Blueprint $table) {
            $table->foreignUuid('salutation_id')
                ->after('country_id')
                ->nullable()
                ->constrained('pim_customer_salutation')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
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
