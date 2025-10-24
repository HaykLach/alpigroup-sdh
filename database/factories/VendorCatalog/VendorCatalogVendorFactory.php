<?php

namespace Database\Factories\VendorCatalog;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorCatalog\VendorCatalogVendor>
 */
class VendorCatalogVendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'code' => $this->faker->postcode,
            'contact' => [],
            'notes' => $this->faker->text,
        ];
    }
}
