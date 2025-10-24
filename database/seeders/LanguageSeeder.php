<?php

namespace Database\Seeders;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\PimLocal;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $values = $this->getValues();

        foreach ($values as $languageResource) {
            PimLanguage::create($languageResource);
        }
    }

    private function getValues(): array
    {
        return [
            [
                'id' => PimGenerateIdService::getLanguageId('Italiano'),
                'name' => 'Italiano',
                'pim_local_id' => PimLocal::whereCode('it_IT')->first()->id,
            ],
            [
                'id' => PimGenerateIdService::getLanguageId('English'),
                'name' => 'English',
                'pim_local_id' => PimLocal::whereCode('en_GB')->first()->id,
            ],
            [
                'id' => PimGenerateIdService::getLanguageId('Deutsch'),
                'name' => 'Deutsch',
                'pim_local_id' => PimLocal::whereCode('de_DE')->first()->id,
            ],
        ];
    }
}
