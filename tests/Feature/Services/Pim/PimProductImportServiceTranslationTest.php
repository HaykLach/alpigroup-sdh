<?php

use App\Enums\VendorCatalog\VendorCatalogImportEntryState;
use App\Models\Pim\PimLanguage;
use App\Models\Pim\Product\PimProduct;
use App\Models\VendorCatalog\VendorCatalogEntry;
use App\Services\Pim\Import\PimProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Services\Pim\PimProductImportEnvironment;

uses(RefreshDatabase::class);

it('can translate products, manufacturers and property groups', function () {

    // steps: add language, delete language, add same language again

    // use common pimTax seeder
    $this->seed('TaxSeeder');

    // get ombis config
    $config = config('ombis');

    // environment setup
    $env = new PimProductImportEnvironment($config);
    $otherLanguages = $env->getOtherLanguages();

    // languages are empty
    expect($otherLanguages)->toBeEmpty();

    $env->addManufacturers();
    $env->addPropertyGroups();

    // 1 main product with 15 variants (the main product will be generated automatically and stored in products table)
    $entryVariantCount = 15;
    $mainArticle = $env->getSampleMainArticleNumber();
    $sampleDataVariant = $env->getSampleVendorCatalogEntriesData($entryVariantCount, $mainArticle);
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    $new = VendorCatalogEntry::query()->where('state', VendorCatalogImportEntryState::NEW->value)->get();
    PimProductImportService::handleImport($config, $env->getManufacturerCodes(), $otherLanguages, $new);

    // data checks
    /** @var PimProduct $mainProduct */
    $randomVariantProduct = $env->utilityGetFirstProductVariant();

    expect(PimLanguage::query()->count())->toBe(0);
    expect($randomVariantProduct->translations)->toBeEmpty();

    $this->seed('LocaleSeeder');
    $this->seed('LanguageSeeder');

    $randomVariantProduct->refresh();

    $languageCount = PimLanguage::query()->count();
    expect($languageCount)->toBeGreaterThan(0);
    expect($randomVariantProduct->translations)->toHaveCount($languageCount);

    // check if translation of description in main language is the same in translations
    $randomVariantProduct->translations->each(function ($translation) use ($randomVariantProduct) {
        expect($randomVariantProduct->name)->toEqual($translation->name);
        expect($randomVariantProduct->description)->toEqual($translation->description);
    });

    // get a language id of $randomVariantProduct translations
    $languageId = $randomVariantProduct->translations->first()->language_id;

    // delete language
    PimLanguage::find($languageId)->delete();
    $languageCountNew = PimLanguage::query()->count();

    expect($languageCountNew)->toBe($languageCount - 1);

    $randomVariantProduct->refresh();
    // product translations should be updated
    expect($randomVariantProduct->translations)->toHaveCount($languageCountNew);

    // restore language
    PimLanguage::withTrashed()->find($languageId)->restore();
    $randomVariantProduct->refresh();
    // product translations should be updated
    expect($randomVariantProduct->translations)->toHaveCount($languageCount);
});

// @todo add test for languages setup in propertyGroups, propertyGroupOptions
