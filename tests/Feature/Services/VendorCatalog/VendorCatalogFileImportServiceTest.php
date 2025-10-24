<?php

use App\Enums\VendorCatalog\VendorCatalogImportState;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Models\VendorCatalog\VendorCatalogImport;
use App\Models\VendorCatalog\VendorCatalogImportRecord;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('removes all records', function () {
    $count = 3;
    $import = VendorCatalogImport::factory()
        ->has(
            factory: VendorCatalogImportRecord::factory()->count($count),
            relationship: 'records'
        )->create();

    expect($import->records)->toHaveCount($count);

    $import = app(VendorCatalogFileImportService::class)
        ->truncateRecords(
            import: $import,
        );

    expect($import->records)->toHaveCount(0);
});

it('creates import file fails if file is not found', function () {
    $import = app(VendorCatalogFileImportService::class)
        ->createImportFile(
            definition: VendorCatalogImportDefinition::factory()->create(),
            path: fake()->filePath(),
            fileName: 'foobar',
            name: 'foo',
        );

    expect($import)->not()->toBeNull();
})->throws(\Exception::class, 'file not found');

it('creates file', function () {
    $import = app(VendorCatalogFileImportService::class)
        ->createFile(
            definition: VendorCatalogImportDefinition::factory()->create(),
            state: VendorCatalogImportState::NEW,
            path: fake()->filePath(),
            fileName: 'foobar',
            name: 'foobar',
            hash: \Hash::make('foobar'),
            contentType: 'csv',
        );

    expect($import)->not()->toBeNull();
});

it('imports entries', function () {
    app(VendorCatalogFileImportService::class)
        ->importEntries(
            import: VendorCatalogImport::factory()
                ->has(
                    factory: VendorCatalogImportRecord::factory()->count(2),
                    relationship: 'records'
                )->create([
                    'disk' => 'test',
                ])
        );
})->skip();

it('import file', function () {
    app(VendorCatalogFileImportService::class)
        ->importFile(
            definition: VendorCatalogImportDefinition::factory()
                ->create([
                    'configuration' => [
                        'local' => [
                            'filename' => 'foo.txt',
                        ],
                    ],
                ]),
            filePath: fake()->filePath()
        );

})->throws(\Exception::class, 'file size 0');
