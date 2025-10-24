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
        Schema::create('pim_media', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('file_name');
            $table->integer('file_size')->default(0);
            $table->string('file_extension')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('path');
            $table->string('disk')->default('local');
            $table->text('hash')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_media');
    }
};
