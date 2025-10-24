<?php

namespace Database\Seeders;

use App\Models\Pim\PimTax;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $values = $this->getValues();

        foreach ($values as $value) {
            PimTax::create($value);
        }
    }

    private function getValues(): array
    {
        return [
            [
                'id' => PimGenerateIdService::getTaxId(7.00),
                'name' => 'Reduced rate',
                'tax_rate' => 7.00,
                'position' => 2,
            ],
            [
                'id' => PimGenerateIdService::getTaxId(22.00),
                'name' => 'MwSt.',
                'tax_rate' => 22.00,
                'position' => 0,
            ],
            [
                'id' => PimGenerateIdService::getTaxId(0.00),
                'name' => 'Reduced rate 2',
                'tax_rate' => 0.00,
                'position' => 3,
            ],
            [
                'id' => PimGenerateIdService::getTaxId(19.00),
                'name' => 'Standard rate',
                'tax_rate' => 19.00,
                'position' => 1,
                'is_default' => 1,
            ],
        ];
    }
}
