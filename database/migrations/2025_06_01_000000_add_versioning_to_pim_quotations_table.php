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
        Schema::table('pim_quotations', function (Blueprint $table) {
            $table->uuid('parent_id')
                ->nullable()
                ->after('id')
                ->comment('Reference to the parent quotation (previous version)');

            $table->integer('version')
                ->default(1)
                ->after('quotation_number')
                ->comment('Version number of the quotation');

            // Add foreign key constraint
            $table->foreign('parent_id')
                ->references('id')
                ->on('pim_quotations')
                ->cascadeOnDelete();
        });

        // drop unique index from quotation_number
        Schema::table('pim_quotations', function (Blueprint $table) {
            $table->dropUnique('pim_quotations_quotation_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_quotations', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropColumn('version');
        });
    }
};
