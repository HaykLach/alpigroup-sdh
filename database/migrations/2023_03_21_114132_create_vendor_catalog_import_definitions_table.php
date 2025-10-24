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
        Schema::create('vendor_catalog_import_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('source')->nullable();
            $table->string('protocol')->nullable();

            $table->foreignUuid('vendor_catalog_vendor_id')
                ->constrained(
                    table: 'vendor_catalog_vendors',
                    indexName: 'vendor_catalog_import_definitions_vendor_id_foreign'
                )->cascadeOnDelete();

            $table->json('file')->nullable();
            $table->json('compression')->nullable();
            $table->json('setup')->nullable();
            $table->json('notification')->nullable();
            $table->json('configuration')->nullable();
            $table->json('mappings')->nullable();
            $table->json('columns')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_catalog_import_definitions', function (Blueprint $table) {
            $table->dropForeign('vendor_catalog_import_definitions_vendor_id_foreign');
        });

        Schema::dropIfExists('vendor_catalog_import_definitions');
    }
};
