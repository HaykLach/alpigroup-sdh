<?php

namespace Database\Seeders;

use App\Models\VendorCatalog\VendorCatalogVendor;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->items() as $item) {
            VendorCatalogVendor::firstOrCreate([
                'id' => PimGenerateIdService::getVendorCatalogVendorId($item),
            ], [
                'name' => $item,
                'notes' => '',
            ]);
        }
    }

    private function items(): array
    {
        return [
            'Alonea',
            'Businesscom',
            'Catrade',
            'Coldtec',
            'Duna Electronics',
            'Elbmatic',
            'Eltric',
            'Hama',
            'I-Sports',
            'Innpro',
            'Kadastar',
            'Kosatec',
            'Leitz Acco',
            'Office Factory',
            'Onlinestore external',
            'Orion',
            'Partheco',
            'Powerdata',
            'Prebena',
            'Rotho Babydesign',
            'Secomp',
            'Serw',
            'Shots',
            'Sibir',
            'Ub Sports',
            'Velvet Trading',
        ];
    }
}
