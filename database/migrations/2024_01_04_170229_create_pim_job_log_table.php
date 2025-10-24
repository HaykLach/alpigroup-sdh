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
        Schema::create('pim_job_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('status');
            $table->json('log');

            $table->timestamps();
        });

        Schema::table('pim_job_logs', function (Blueprint $table) {
            $table->foreignUuid('pim_job_id')
                ->after('id')
                ->constrained('pim_jobs')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_job_logs');
    }
};
