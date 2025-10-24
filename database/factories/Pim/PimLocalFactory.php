<?php

namespace Database\Factories\Pim;

use App\Models\Pim\PimLocal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PimLocal>
 */
class PimLocalFactory extends Factory
{
    protected $model = PimLocal::class;

    protected $languageCodes = ['de_DE', 'it_IT', 'en_EN', 'es_ES', 'fr_FR', 'pt_PT', 'nl_NL', 'pl_PL', 'ru_RU'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->randomElement($this->languageCodes),
        ];
    }
}
