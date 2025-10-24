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
        Schema::create('pim_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pim_categories', function (Blueprint $table) {
            $table->foreignUuid('parent_id')
                ->after('id')
                ->nullable()
                ->constrained('pim_categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_categories', function (Blueprint $table) {
            $table->dropForeign('pim_categories_parent_id_foreign');
        });

        Schema::dropIfExists('pim_categories');
    }
};
