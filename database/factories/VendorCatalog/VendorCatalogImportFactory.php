<?php

namespace Database\Factories\VendorCatalog;

use App\Enums\VendorCatalog\VendorCatalogImportSource;
use App\Enums\VendorCatalog\VendorCatalogImportState;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorCatalog\VendorCatalogImport>
 */
class VendorCatalogImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_catalog_import_definition_id' => VendorCatalogImportDefinition::factory(),
            'type' => VendorCatalogImportSource::CSV,
            'state' => VendorCatalogImportState::NEW,
            'name' => $this->faker->word,
            'file_name' => $this->faker->word,
            'mime_type' => $this->faker->mimeType(),
            'path' => $this->faker->filePath(),
            'disk' => 'fake',
            'file_hash' => \Str::uuid(),
            'size' => 0,

        ];
    }
}
