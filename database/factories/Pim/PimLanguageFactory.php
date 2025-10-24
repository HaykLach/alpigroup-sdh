<?php

namespace Database\Factories\Pim;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\PimLocal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PimLanguage>
 */
class PimLanguageFactory extends Factory
{
    protected $model = PimLanguage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => $this->faker->word,
            'pim_local_id' => PimLocal::factory(),
        ];
    }
}
