<?php

namespace Database\Factories\Pim\Product;

use App\Models\Pim\Product\PimProductManufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pim\Product\PimProductManufacturer>
 */
class PimProductManufacturerFactory extends Factory
{
    protected $model = PimProductManufacturer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
