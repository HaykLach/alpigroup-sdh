<?php

namespace Database\Seeders;

use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Seeder;

class VendorCatalogImportDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $values = $this->values();
        foreach ($values as $name => $value) {
            VendorCatalogImportDefinition::firstOrCreate([
                'id' => PimGenerateIdService::getVendorCatalogImportDefinitionId($name),
            ], [
                'protocol' => $value['type'],
                'vendor_catalog_vendor_id' => PimGenerateIdService::getVendorCatalogVendorId($value['vendor']),
                'file' => $value['file'],
                'setup' => $value['setup'],
                'notification' => [
                    'mail' => [
                        'notification' => false,
                        'address' => null,
                    ],
                ],
                'configuration' => $value['configuration'],
            ]);
        }
    }

    private function values(): array
    {
        return [
            'alonea' => [
                'vendor' => 'alonea',
                'type' => 'http',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => 'L-1178',
                    'article_column' => 4,
                    'stock_column' => 12,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => 'https://www.alonea.ch/venova/Alonea_stock.csv',
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'businesscom' => [
                'vendor' => 'businesscom',
                'type' => 'ftp',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => 'L-1125',
                    'article_column' => 1,
                    'stock_column' => 8,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => 'data.businesscom.ch',
                        'port' => 21,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'catrade' => [
                'vendor' => 'catrade',
                'type' => 'http',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ',',
                    'enclosure' => '"',
                    'skip_lines' => 0,
                ],
                'setup' => [
                    'depot_id' => 'L-1121',
                    'article_column' => 0,
                    'stock_column' => 1,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => 'https://api.dfshop.com/v1/products?token=utamk9tui0wu0dzmlq9g19tg2d7t2xalcddxmbj6uuozvn21c7&page=1',
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'coldtec' => [
                'vendor' => 'coldtec',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => "'",
                    'skip_lines' => 0,
                ],
                'setup' => [
                    'depot_id' => 'L-1103',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => 'https://coldtec.ch/kundenportal/Lagerbestand.csv',
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'duna_electronics' => [
                'vendor' => 'duna electronics',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 0,
                ],
                'setup' => [
                    'depot_id' => 'L-1166',
                    'article_column' => 1,
                    'stock_column' => 5,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'elbmatic' => [
                'vendor' => 'elbmatic',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => "\t",
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => 'L-1134',
                    'article_column' => 1,
                    'stock_column' => 5,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'eltric' => [
                'vendor' => 'eltric',
                'type' => 'ftp',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 0,
                ],
                'setup' => [
                    'depot_id' => 'L-1127',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => 'home502003097.1and1-data.host',
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'hama' => [
                'vendor' => 'hama',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'i_sports' => [
                'vendor' => 'i-sports',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'innpro' => [
                'vendor' => 'innpro',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'kadastar' => [
                'vendor' => 'kadastar',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'kosatec' => [
                'vendor' => 'kosatec',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'leitz_acco' => [
                'vendor' => 'leitz acco',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'office_factory' => [
                'vendor' => 'office factory',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'orion' => [
                'vendor' => 'orion',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'partheco' => [
                'vendor' => 'partheco',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'powerdata' => [
                'vendor' => 'powerdata',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'prebena' => [
                'vendor' => 'prebena',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'rotho_babydesign' => [
                'vendor' => 'rotho babydesign',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'secomp' => [
                'vendor' => 'secomp',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'serw' => [
                'vendor' => 'serw',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'shots' => [
                'vendor' => 'shots',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'sibir' => [
                'vendor' => 'sibir',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
            'ub_sports' => [
                'vendor' => 'ub sports',
                'type' => 'local',
                'file' => [
                    'escape' => '\\',
                    'delimiter' => ';',
                    'enclosure' => '"',
                    'skip_lines' => 1,
                ],
                'setup' => [
                    'depot_id' => '',
                    'article_column' => 1,
                    'stock_column' => 2,
                ],
                'configuration' => [
                    'ftp' => [
                        'ssl' => true,
                        'host' => null,
                        'port' => null,
                        'root' => null,
                        'passive' => true,
                        'timeout' => 30,
                        'password' => null,
                        'username' => null,
                    ],
                    'http' => [
                        'url' => null,
                    ],
                    'local' => [
                        'filename' => null,
                    ],

                ],
            ],
        ];
    }
}
