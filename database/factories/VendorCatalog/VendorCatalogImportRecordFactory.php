<?php

namespace Database\Factories\VendorCatalog;

use App\Enums\VendorCatalog\VendorCatalogImportRecordState;
use App\Models\VendorCatalog\VendorCatalogImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorCatalog\VendorCatalogImportRecord>
 */
class VendorCatalogImportRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_catalog_import_id' => VendorCatalogImport::factory(),
            'vendor_catalog_entry_id' => null,
            'state' => VendorCatalogImportRecordState::NEW,
            'number' => fake()->randomAscii,
            'stock' => fake()->randomNumber(),
            'data' => [],
        ];
    }
}
