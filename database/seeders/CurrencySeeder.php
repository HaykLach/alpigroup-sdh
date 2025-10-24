<?php

namespace Database\Seeders;

use App\Models\Pim\PimCurrency;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $values = $this->getValues();

        foreach ($values as $currency) {
            PimCurrency::create($currency);
        }
    }

    private function getValues(): array
    {
        return [
            [
                'id' => PimGenerateIdService::getCurrencyId('EUR'),
                'name' => 'Euro',
                'iso_code' => 'EUR',
                'short_name' => 'EUR',
            ],
        ];
    }
}
