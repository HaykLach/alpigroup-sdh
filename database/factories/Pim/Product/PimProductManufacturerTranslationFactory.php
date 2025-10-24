<?php

namespace Database\Factories\Pim\Product;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Models\Pim\Product\PimProductManufacturerTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pim\Product\PimProductManufacturerTranslation>
 */
class PimProductManufacturerTranslationFactory extends Factory
{
    protected $model = PimProductManufacturerTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'language_id' => PimLanguage::factory(),
            'manufacturer_id' => PimProductManufacturer::factory(),
            'name' => $this->faker->name,
        ];
    }
}
