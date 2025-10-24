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
        Schema::create('vendor_catalog_import_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('vendor_catalog_import_id')
                ->constrained('vendor_catalog_imports')
                ->cascadeOnDelete();

            $table->string('state');

            $table->string('number');
            $table->integer('stock')->default(0);
            $table->json('data');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_catalog_import_records', function (Blueprint $table) {
            $table->dropForeign('vendor_catalog_import_records_vendor_catalog_import_id_foreign');
        });

        Schema::dropIfExists('vendor_catalog_import_records');
    }
};
