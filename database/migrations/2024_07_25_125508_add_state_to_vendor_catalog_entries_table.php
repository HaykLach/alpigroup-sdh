<?php

use App\Enums\VendorCatalog\VendorCatalogImportEntryState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendor_catalog_entries', function (Blueprint $table) {
            $table->string('state')
                ->default(VendorCatalogImportEntryState::NEW->value)
                ->after('data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendor_catalog_entries', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
