<?php

namespace Database\Seeders;

use App\Models\Pim\PimLocal;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Seeder;

class LocaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $values = $this->getValues();

        foreach ($values as $languageResource) {
            PimLocal::create($languageResource);
        }
    }

    private function getValues(): array
    {
        return [
            [
                'id' => PimGenerateIdService::getLocaleId('it_IT'),
                'code' => 'it_IT',
            ],
            [
                'id' => PimGenerateIdService::getLocaleId('en_GB'),
                'code' => 'en_GB',
            ],
            [
                'id' => PimGenerateIdService::getLocaleId('de_DE'),
                'code' => 'de_DE',
            ],
        ];
    }
}
