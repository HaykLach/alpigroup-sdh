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
            $table->foreignUuid('customer_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('vat_id')->after('region')->nullable();
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
