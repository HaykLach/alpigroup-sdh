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
        Schema::table('pim_customer_salutation', function (Blueprint $table) {
            $table->string('letter_name')->after('salutation_key')->nullable();
            $table->string('display_name')->after('letter_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
