<?php

use App\Enums\Pim\PimQuatationValidityPeriodUnit;
use App\Enums\Pim\PimQuotationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pim_quotations', function (Blueprint $table) {
            $table->uuid('id')
                ->primary();

            $table->integer('quotation_number')
                ->unique()
                ->nullable();

            $table->json('content')
                ->nullable();

            $table->dateTime('date')
                ->nullable();

            $table->enum('validity_period_unit', array_column(PimQuatationValidityPeriodUnit::cases(), 'value'))
                ->nullable();

            $table->integer('validity_period_value')
                ->nullable();

            $table->date('validity_period')
                ->nullable();

            $table->text('internal_comment')
                ->nullable();

            $table->float('discount_percentage')
                ->default(0.00)
                ->nullable();

            $table->float('discount_amount')
                ->default(0.00)
                ->nullable();

            $table->float('shipping_cost')
                ->default(0.00)
                ->nullable();

            $table->float('total_cost')
                ->default(0.00)
                ->nullable();

            $table->dateTime('sent_to_customer')
                ->nullable();

            $table->enum('status', array_column(PimQuotationStatus::cases(), 'value'))
                ->default(PimQuotationStatus::DRAFT->value);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pim_quotations');
    }
};
