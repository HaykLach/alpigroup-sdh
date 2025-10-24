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
            $table->integer('quotation_template_number')
                ->unique()
                ->nullable()
                ->after('quotation_number');
        });
    }

    public function down()
    {
        Schema::table('pim_quotations', function (Blueprint $table) {
            $table->dropColumn('quotation_template_number');
        });
    }
};
