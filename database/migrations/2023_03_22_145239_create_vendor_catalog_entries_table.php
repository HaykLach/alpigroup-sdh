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
        Schema::create('vendor_catalog_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('vendor_catalog_vendor_id')
                ->constrained('vendor_catalog_vendors')
                ->cascadeOnDelete();

            $table->foreignUuid('vendor_catalog_import_record_id')
                ->constrained('vendor_catalog_import_records')
                ->cascadeOnDelete();

            $table->string('gtin')->nullable();
            $table->string('number')->nullable();
            $table->string('name')->nullable();
            $table->integer('stock')->default(0)->nullable();
            $table->string('price')->nullable();
            $table->string('currency')->default('euro')->nullable();

            $table->json('data');

            $table->softDeletes();

            $table->timestamps();
        });

        Schema::table('vendor_catalog_import_records', function (Blueprint $table) {
            $table->foreignUuid('vendor_catalog_entry_id')
                ->after('vendor_catalog_import_id')
                ->nullable()
                ->constrained('vendor_catalog_entries')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_catalog_import_records', function (Blueprint $table) {
            $table->dropForeign('vendor_catalog_import_records_vendor_catalog_entry_id_foreign');
        });

        Schema::table('vendor_catalog_entries', function (Blueprint $table) {
            $table->dropForeign('vendor_catalog_entries_vendor_catalog_import_record_id_foreign');
            $table->dropForeign('vendor_catalog_entries_vendor_catalog_vendor_id_foreign');
        });

        Schema::dropIfExists('vendor_catalog_entries');
    }
};
