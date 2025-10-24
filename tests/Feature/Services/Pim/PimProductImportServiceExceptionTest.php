<?php

use App\Services\Pim\Import\PimProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Services\Pim\PimProductImportEnvironment;

uses(RefreshDatabase::class);

it('import fails if no tax configuration is present', function () {

    // use common pimTax seeder
    // $this->seed('TaxSeeder');

    // get ombis config
    $config = config('ombis');

    // environment setup
    $env = new PimProductImportEnvironment($config);
    $otherLanguages = $env->getOtherLanguages();

    $env->addManufacturers(collect());
    $env->addPropertyGroups();

    // 1 main product with 15 variants (the main product will be generated automatically and stored in products table)
    $entryVariantCount = 15;
    $mainArticle = $env->getSampleMainArticleNumber();
    $sampleDataVariant = $env->getSampleVendorCatalogEntriesData($entryVariantCount, $mainArticle);
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    // catch exception
    expect(function () use ($config, $env, $otherLanguages) {
        PimProductImportService::handleImport($config, $env->getManufacturerCodes(), $otherLanguages);
    })->toThrow(\ErrorException::class);
});

it('import fails if no manufacturers are present', function () {
    // use common pimTax seeder
    $this->seed('TaxSeeder');

    // get ombis config
    $config = config('ombis');

    // environment setup
    $env = new PimProductImportEnvironment($config);
    $otherLanguages = $env->getOtherLanguages();

    $env->addManufacturers(collect());
    $env->addPropertyGroups();

    // 1 main product with 15 variants (the main product will be generated automatically and stored in products table)
    $entryVariantCount = 15;
    $mainArticle = $env->getSampleMainArticleNumber();
    $sampleDataVariant = $env->getSampleVendorCatalogEntriesData($entryVariantCount, $mainArticle);
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    // catch exception
    expect(function () use ($config, $env, $otherLanguages) {
        PimProductImportService::handleImport($config, $env->getManufacturerCodes(), $otherLanguages);
    })->toThrow(\ErrorException::class);
});

it('import fails if no property groups are present', function () {
    // use common pimTax seeder
    $this->seed('TaxSeeder');

    // get ombis config
    $config = config('ombis');

    // environment setup
    $env = new PimProductImportEnvironment($config);
    $otherLanguages = $env->getOtherLanguages();

    $env->addManufacturers();
    // $env->setupPropertyGroups();

    // 1 main product with 15 variants (the main product will be generated automatically and stored in products table)
    $entryVariantCount = 15;
    $mainArticle = $env->getSampleMainArticleNumber();
    $sampleDataVariant = $env->getSampleVendorCatalogEntriesData($entryVariantCount, $mainArticle);
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    // catch exception
    expect(function () use ($config, $env, $otherLanguages) {
        PimProductImportService::handleImport($config, $env->getManufacturerCodes(), $otherLanguages);
    })->toThrow(\ErrorException::class);
});
