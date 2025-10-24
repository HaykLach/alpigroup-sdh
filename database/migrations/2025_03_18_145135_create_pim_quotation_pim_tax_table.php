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
            $table->foreignUuid('pim_tax_id')
                ->after('total_cost')
                ->nullable()
                ->constrained('pim_tax')
                ->onDelete('set null');

            $table->float('total_cost_with_tax')
                ->after('pim_tax_id')
                ->default(0.00)
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_quotations', function (Blueprint $table) {
            $table->dropForeign(['pim_tax_id']);
            $table->dropColumn('pim_tax_id');
            $table->dropColumn('total_cost_with_tax');
        });
    }
};
