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
        Schema::create('pim_customer_tax_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->string('tax_handling');
            $table->timestamps();
        });

        Schema::table('pim_customers', function (Blueprint $table) {
            $table->foreignUuid('tax_group_id')
                ->nullable()
                ->after('salutation_id')
                ->constrained('pim_customer_tax_groups')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_customers', function (Blueprint $table) {
            $table->dropForeign(['tax_group_id']);
            $table->dropColumn('tax_group_id');
        });

        Schema::dropIfExists('pim_customer_tax_groups');
    }
};
