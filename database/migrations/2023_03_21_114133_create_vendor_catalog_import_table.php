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
        Schema::create('vendor_catalog_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('vendor_catalog_import_definition_id')
                ->constrained(
                    table: 'vendor_catalog_import_definitions',
                    indexName: 'vendor_catalog_imports_import_definition_id_foreign'
                )->cascadeOnDelete();

            $table->string('type')->default('file');
            $table->string('state');

            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('file_hash', 64);
            $table->unsignedBigInteger('size');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_catalog_imports', function (Blueprint $table) {
            $table->dropForeign('vendor_catalog_imports_import_definition_id_foreign');
        });

        Schema::dropIfExists('vendor_catalog_imports');
    }
};
