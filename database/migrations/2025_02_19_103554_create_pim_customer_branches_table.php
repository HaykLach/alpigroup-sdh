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
        Schema::create('pim_customer_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->integer('code')->unique();
            $table->timestamps();
        });

        Schema::create('pim_customer_branch_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('branch_id')
                ->constrained('pim_customer_branches')
                ->onDelete('cascade');

            $table->foreignUuid('language_id')
                ->constrained(
                    table: 'pim_languages',
                    indexName: 'pim_customer_branch_translations_id_to_language_id'
                )->onUpdate('cascade')
                ->onDelete('cascade');

            $table->string('name');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_customer_branch_translations');
        Schema::dropIfExists('pim_customer_branches');
    }
};
