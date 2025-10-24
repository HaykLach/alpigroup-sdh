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
        Schema::create('customer_detail_import', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('data');
            $table->string('status');
            $table->string('result')->nullable();
            $table->timestamps();
        });

        Schema::table('customer_detail_import', function (Blueprint $table) {
            $table->foreignUuid('customer_import_id')
                ->after('id')
                ->references('id')
                ->on('customer_imports')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_detail_import');
    }
};
