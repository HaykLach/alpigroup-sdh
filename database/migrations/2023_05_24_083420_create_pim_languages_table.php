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
        Schema::create('pim_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            $table->foreignUuid('pim_local_id')
                ->constrained('pim_locals')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pim_languages', function (Blueprint $table) {
            $table->foreignUuid('parent_id')
                ->after('id')->nullable()
                ->constrained('pim_languages')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pim_languages', function (Blueprint $table) {
            $table->dropForeign('pim_languages_pim_local_id_foreign');
            $table->dropForeign('pim_languages_parent_id_foreign');
        });

        Schema::dropIfExists('pim_languages');
    }
};
