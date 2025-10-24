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
        Schema::create('pim_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('identifier');
            $table->string('birthday')->nullable();
            $table->string('email');
            $table->longText('custom_fields');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pim_customers', function (Blueprint $table) {
            $table->foreignUuid('salutation_id')
                ->after('email')
                ->nullable()
                ->constrained('pim_customer_salutation')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('pim_customers', function (Blueprint $table) {
            $table->foreignUuid('default_address_id')
                ->after('salutation_id')
                ->nullable()
                ->constrained('pim_customer_salutation')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('pim_customers', function (Blueprint $table) {
            $table->foreignUuid('default_payment_method')
                ->after('default_address_id')
                ->nullable()
                ->constrained('pim_payment_method')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pim_customer');
    }
};
