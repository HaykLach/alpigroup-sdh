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
        Schema::table('pim_property_group_option', function (Blueprint $table) {
            $table->dropColumn('group_id');
        });

        Schema::table('pim_property_group_option', function (Blueprint $table) {
            $table->foreignUuid('group_id')
                ->constrained(
                    table: 'pim_property_group',
                    indexName: 'pim_property_group_option_id_to_pim_property_group_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('product_options', function (Blueprint $table) {
            $table->dropForeign('product_options_id_to_pim_property_group_option_id');
            $table->dropIndex('product_options_id_to_pim_property_group_option_id');
            $table->dropColumn('option_id');
        });

        Schema::table('product_properties', function (Blueprint $table) {
            $table->dropForeign('product_properties_id_to_pim_property_group_option_id');
            $table->dropIndex('product_properties_id_to_pim_property_group_option_id');
            $table->dropColumn('option_id');
        });

        Schema::table('pim_property_group_option', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        Schema::table('pim_property_group_option_translations', function (Blueprint $table) {
            $table->foreignUuid('property_group_option_id')
                ->constrained(
                    table: 'pim_property_group_option',
                    indexName: 'group_option_translations_id_to_pim_property_group_option_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('product_options', function (Blueprint $table) {
            $table->foreignUuid('option_id')
                ->constrained(
                    table: 'pim_property_group_option',
                    indexName: 'product_options_option_id_to_pim_property_group_option_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('product_properties', function (Blueprint $table) {
            $table->foreignUuid('option_id')
                ->constrained(
                    table: 'pim_property_group_option',
                    indexName: 'product_properties_id_to_pim_property_group_option_id'
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
