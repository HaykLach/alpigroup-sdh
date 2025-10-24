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
        Schema::table('pim_property_group_translations', function (Blueprint $table) {
            $table->dropForeign('pim_property_group_translations_id_to_pim_property_group_id');

            $table->dropIndex('pim_property_group_translations_id_to_pim_property_group_id');
            $table->dropColumn('property_group_id');
        });

        Schema::table('pim_property_group', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        Schema::table('pim_property_group_translations', function (Blueprint $table) {
            $table->foreignUuid('property_group_id')
                ->constrained(
                    table: 'pim_property_group',
                    indexName: 'pim_property_group_translations_id_to_pim_property_group_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
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
