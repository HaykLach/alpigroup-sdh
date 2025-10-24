<?php

use App\Enums\Pim\PimMediaPoolType;
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
        Schema::create('pim_media_pools', function (Blueprint $table) {
            $table->uuid('id')
                ->primary();
            $table->string('name');
            $table->foreignUuid('parent_id')
                ->nullable()
                ->constrained('pim_media_pools')
                ->onDelete('cascade');
            $table
                ->enum('type', array_map(fn ($type) => $type
                    ->value, PimMediaPoolType::cases()))
                ->default(PimMediaPoolType::FOLDER->value);
            $table->text('description')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_media_pools');
    }
};
