<?php

namespace Database\Factories\VendorCatalog\ImportDefinition;

use App\Enums\VendorCatalog\VendorCatalogImportDefinitionProtocolType;
use App\Enums\VendorCatalog\VendorCatalogImportSource;
use App\Models\VendorCatalog\VendorCatalogVendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition>
 */
class VendorCatalogImportDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'vendor_catalog_vendor_id' => VendorCatalogVendor::factory(),
            'source' => VendorCatalogImportSource::CSV,
            'protocol' => VendorCatalogImportDefinitionProtocolType::LOCAL,
            'file' => [],
            'compression' => [],
            'setup' => [],
            'notification' => [],
            'configuration' => [],
        ];
    }

    public function vendor(VendorCatalogVendor $vendor): Factory
    {
        return $this->state(function (array $attributes) use ($vendor) {
            return [
                'vendor_catalog_vendor_id' => $vendor->id,
            ];
        });
    }
}
