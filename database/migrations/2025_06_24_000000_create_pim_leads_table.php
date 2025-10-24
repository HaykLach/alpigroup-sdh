<?php

use App\Enums\Pim\PimLeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pim_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('number')->unique();
            $table->string('source')->nullable();
            $table->string('source_namespace')->nullable();
            $table->jsonb('custom_fields')->nullable();
            $table->uuid('pim_agent_id')->nullable();
            $table->uuid('pim_customer_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->index()->default(PimLeadStatus::OPEN->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pim_leads');
    }
};
