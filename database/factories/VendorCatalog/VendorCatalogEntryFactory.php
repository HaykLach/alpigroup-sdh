<?php

namespace Database\Factories\VendorCatalog;

use App\Enums\VendorCatalog\VendorCatalogImportEntryState;
use App\Models\VendorCatalog\VendorCatalogImportRecord;
use App\Models\VendorCatalog\VendorCatalogVendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\VendorCatalog\VendorCatalogEntry>
 */
class VendorCatalogEntryFactory extends Factory
{
    public function definition(): array
    {
        $vendorCatalogVendorId = $this->vendor_catalog_vendor_id ?? VendorCatalogVendor::factory();
        $vendorCatalogRecordId = $this->vendor_catalog_import_record_id ?? VendorCatalogImportRecord::factory();
        $data = $this->data ?? [];

        return [
            'vendor_catalog_vendor_id' => $vendorCatalogVendorId,
            'vendor_catalog_import_record_id' => $vendorCatalogRecordId,
            'gtin' => fake()->randomNumber(),
            'number' => fake()->randomNumber(),
            'stock' => fake()->randomNumber(),
            'price' => fake()->randomFloat(),
            'currency' => 'euro',
            'data' => $data,
            'state' => VendorCatalogImportEntryState::NEW->value,
        ];
    }
}
