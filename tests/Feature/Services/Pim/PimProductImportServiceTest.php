<?php

use App\Enums\Pim\PimMappingType;
use App\Enums\VendorCatalog\VendorCatalogImportEntryState;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\VendorCatalog\VendorCatalogEntry;
use App\Services\Pim\Import\PimProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Services\Pim\PimProductImportEnvironment;

uses(RefreshDatabase::class);

it('can import ombis records, create pim products and handle updates', function () {

    // rule: if price changes, record gets updated
    // rule: if product is not present in new import, it gets deleted

    // use common pimTax seeder
    $this->seed('TaxSeeder');

    // get ombis config
    $config = config('ombis');

    // environment setup
    $env = new PimProductImportEnvironment($config);
    $otherLanguages = $env->getOtherLanguages();

    $env->addManufacturers();
    $env->addPropertyGroups();

    $manufacturerCodes = $env->getManufacturerCodes();

    /**
     * check if property groups are defined in config and stored in database
     */
    $configPropertyGroupFieldsProduct = $env->getPropertyGroupsDefinedByConfig($config, PimMappingType::PRODUCT);
    $configPropertyGroupFieldsManufacturer = $env->getPropertyGroupsDefinedByConfig($config, PimMappingType::MANUFACTURER);

    $propertyGroupsProductArr = $env->getPropertyGroupsStoredByHandleFieldNames(PimMappingType::PRODUCT);
    $propertyGroupsManufacturerArr = $env->getPropertyGroupsStoredByHandleFieldNames(PimMappingType::MANUFACTURER);

    expect($configPropertyGroupFieldsProduct)->toHaveCount(15);
    expect($configPropertyGroupFieldsManufacturer)->toHaveCount(2);

    // expect all property groups defined by config to be stored as PimPropertyGroup
    expect($configPropertyGroupFieldsProduct)->toEqual($propertyGroupsProductArr)
        ->and($configPropertyGroupFieldsManufacturer)->toEqual($propertyGroupsManufacturerArr);

    // options are empty
    expect($env->getPropertyGroupOptions())->toHaveCount(0);

    /**
     * check handling of main and variant products
     */
    // Products without variants (resulting in [n] main products)
    $entryMainCount = 3;
    $sampleDataMain = $env->getSampleVendorCatalogEntriesData($entryMainCount);

    // 1 main product with 15 variants (the main product will be generated automatically and stored in products table)
    $entryVariantCount = 15;
    $accumulateMainProducts = 1;
    $mainArticle = $env->getSampleMainArticleNumber();
    $sampleDataVariant = $env->getSampleVendorCatalogEntriesData($entryVariantCount, $mainArticle);

    $env->addVendorCatalogEntries($entryMainCount, $sampleDataMain);
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    /**
     * after adding new VendorCatalogEntries we can add property groups and count options
     * new options were added
     */
    expect($env->getPropertyGroupOptions())->toHaveCount(0);

    $env->addPropertyGroups();
    expect($env->getPropertyGroupOptions())->not()->toHaveCount(0);

    $new = $env->utilityGetVendorCatalogEntries(VendorCatalogImportEntryState::NEW);
    $processed = $env->utilityGetVendorCatalogEntries(VendorCatalogImportEntryState::PROCESSED);

    // check VendorCatalogEntry has some state "new" and some "processed"
    expect($new)->toHaveCount($entryMainCount + $entryVariantCount)
        ->and($processed)->isEmpty()->toBeTrue()
        ->and(PimProduct::all())->toBeEmpty();

    $handledRecords = PimProductImportService::handleImport($config, $manufacturerCodes, $otherLanguages);
    expect($handledRecords['main'])->toHaveCount($entryMainCount + $accumulateMainProducts) // + $accumulateMainProducts because of the main product generated from variants
        ->and($handledRecords['variant'])->toHaveCount($entryVariantCount);

    $entries = VendorCatalogEntry::all();
    $new = $entries->where('state', VendorCatalogImportEntryState::NEW->value);
    $processed = $entries->where('state', VendorCatalogImportEntryState::PROCESSED->value);

    // all new VendorCatalogEntry entries where processed
    expect($processed)->toHaveCount($entryMainCount + $entryVariantCount)
        ->and($new)->isEmpty()->toBeTrue();

    // expect pim_products main products to have same amount as $entryMainCount + $accumulateMainProducts
    $mainProductCount = PimProduct::query()->whereNull('parent_id')->count();
    expect($mainProductCount)->toEqual($entryMainCount + $accumulateMainProducts);

    // expect variants to have same amount as $entryVariantCount, for each main product 1 variant will be generated if there are at least 2 variants
    $variantProductCount = PimProduct::query()->whereNotNull('parent_id')->count();
    expect($variantProductCount)->toEqual($entryVariantCount);

    $allProductCount = PimProduct::query()->count();
    expect($allProductCount)->toEqual($entryMainCount + $entryVariantCount + $accumulateMainProducts);

    // data checks
    $randomVariantProduct = $env->utilityGetFirstProductVariant();
    $randomVariantProductId = $randomVariantProduct->id;
    $randomVariantProductPrice = $randomVariantProduct->prices;

    // check manufacturer_id has been assigned correctly
    expect($randomVariantProduct->pim_manufacturer_id)->toBeIn($manufacturerCodes);

    // check pim_tax_id has been assigned correctly
    expect($randomVariantProduct->pim_tax_id)->toBe($env->getPimTaxRateId());

    /**
     * Update data checks
     */
    // @todo add update test

    $priceField = 'Verkaufspreis';
    $priceFieldId = $env->getPropertyGroupByName($priceField)->id;

    /**
     * all 5 select boxes with each 1 single option attached
     */
    expect($randomVariantProduct->properties()->count())->toBe(7);

    /**
     * check price update
     * test: same price, handle same data, price remains the same
     */
    $env->addVendorCatalogEntries($entryMainCount, $sampleDataMain);
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    PimProductImportService::handleImport($config, $manufacturerCodes, $otherLanguages);
    $randomVariantProductNew = PimProduct::find($randomVariantProductId);
    expect($randomVariantProductNew->prices)->toEqual($randomVariantProductPrice);

    /**
     * test: change price and other data, insert data again, only price changed
     * test: new PimPropertyGroupOption added
     */
    $priceChange = 100.20;
    $newOptionSizeFilter = 's new';
    $newValues = [
        $priceField => $priceChange,
        'Nettogewicht' => 1000,
        'Bezeichnung_de' => 'neu name',
        'Beschreibung_de' => 'neu description',
        'XF_TissueInfo' => 'neu tissue',
        'XF_SizeFilter' => $newOptionSizeFilter,
    ];
    for ($i = 0; $i < $entryMainCount; $i++) {
        $sampleDataMain[$i] = array_merge($sampleDataMain[$i], $newValues);
    }
    $env->addVendorCatalogEntries($entryMainCount, $sampleDataMain);

    for ($i = 0; $i < $entryVariantCount; $i++) {
        $sampleDataVariant[$i] = array_merge($sampleDataVariant[$i], $newValues);
    }
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    // expect new option $newOptionSizeFilter to not exist
    $options = PimPropertyGroupOption::query()->where('name', $newOptionSizeFilter)->get();
    expect($options)->toHaveCount(0);

    $env->addPropertyGroups();

    // expect new option $newOptionSizeFilter to be added
    $options = PimPropertyGroupOption::query()->where('name', $newOptionSizeFilter)->get();
    expect($options)->toHaveCount(1);

    PimProductImportService::handleImport($config, $manufacturerCodes, $otherLanguages);

    $randomVariantProductNew = PimProduct::find($randomVariantProductId);

    // expect prices to be updated
    expect($randomVariantProductNew->prices[$priceFieldId])->toEqual($priceChange);

    // expect other fields to remain the same
    expect($randomVariantProductNew->name)->not()->toEqual($newValues['Bezeichnung_de']);
    expect($randomVariantProductNew->description)->not()->toEqual($newValues['Beschreibung_de']);
    expect($randomVariantProductNew->custom_fields['properties'][$env->getPropertyGroupByName('XF_TissueInfo')->id])->not()->toEqual($newValues['XF_TissueInfo']);
    expect($randomVariantProductNew->properties()->where('group_id', '=', $env->getPropertyGroupByName('XF_SizeFilter')->id)->first()->name)->not()->toEqual($newValues['XF_SizeFilter']);

    /**
     * test: perform new import with minor amount of product items, products get deleted
     */
    $productAmountInitial = PimProduct::query()->count();

    // add only variant products
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    $handledRecords = PimProductImportService::handleImport($config, $manufacturerCodes, $otherLanguages);
    $deletedProductsCount = PimProductImportService::deleteUnhandledProducts($handledRecords);

    // deleted main products and each corresponding variant product
    expect($deletedProductsCount)->toEqual($entryMainCount);

    $productAmountNew = PimProduct::query()->count();
    expect($productAmountNew)->not()->toEqual($productAmountInitial);

    /**
     * test: perform new import with initial amount of product items, deleted products get restored
     */
    $productAmount = PimProduct::query()->count();
    $productAmountWithTrashed = PimProduct::withTrashed()->count();

    // add only variant products
    $env->addVendorCatalogEntries($entryMainCount, $sampleDataMain);
    $env->addVendorCatalogEntries($entryVariantCount, $sampleDataVariant);

    $handledRecords = PimProductImportService::handleImport($config, $manufacturerCodes, $otherLanguages);
    $deletedProductsCount = PimProductImportService::deleteUnhandledProducts($handledRecords);

    $productAmountWithTrashedNew = PimProduct::withTrashed()->count();
    $productAmountNew = PimProduct::query()->count();

    // expect all products to be restored
    expect($deletedProductsCount)->toEqual(0)
        ->and($productAmount)->not()->toEqual($productAmountInitial)
        ->and($productAmountInitial)->toEqual($productAmountWithTrashed)
        ->and($productAmountInitial)->toEqual($productAmountNew)
        ->and($productAmountInitial)->toEqual($productAmountWithTrashedNew)
        ->and($productAmountWithTrashed)->toEqual($productAmountNew)
        ->and($productAmountWithTrashed)->toEqual($productAmountWithTrashedNew)
        ->and($productAmountNew)->toEqual($productAmountWithTrashedNew);
});
