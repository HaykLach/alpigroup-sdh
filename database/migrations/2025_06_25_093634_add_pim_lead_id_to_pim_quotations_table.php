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
            $table->uuid('pim_lead_id')->nullable();
            $table->foreign('pim_lead_id')
                ->references('id')
                ->on('pim_leads')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_quotations', function (Blueprint $table) {
            $table->dropForeign(['pim_lead_id']);
            $table->dropColumn('pim_lead_id');
        });
    }
};
