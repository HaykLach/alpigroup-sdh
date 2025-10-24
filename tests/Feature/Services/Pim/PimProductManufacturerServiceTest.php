<?php

use App\Models\Pim\PimLanguage;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Pim\PimProductManufacturerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has no manufacturer translations', function () {

    PimProductManufacturer::factory()
        ->create();

    $manufacturersResult = PimProductManufacturer::all()
        ->load('translations')
        ->first();

    expect($manufacturersResult->translations)->toBeEmpty();
});

it('has manufacturer translations', function () {

    // setup languages
    $languageCount = 2;
    $languages = PimLanguage::factory($languageCount)->create();
    expect($languages)->toHaveCount($languageCount);

    $testName = 'Delphi';

    // setup Manufacturers
    $manufacturers = collect(['Hakro', $testName, 'Bosch', 'Siemens']);
    $manufacturerCount = count($manufacturers);

    $manufacturers->each(function ($manufacturer) use ($languages) {
        $manufacturer = PimProductManufacturer::factory()
            ->create([
                'name' => $manufacturer,
            ]);
        // keep default language name for all languages
        $languages->each(function ($language) use ($manufacturer) {
            $manufacturer->translations()->create([
                'language_id' => $language->id,
                'name' => $manufacturer->name,
            ]);
        });
    });

    $manufacturersResult = PimProductManufacturer::all()
        ->load('translations');
    expect($manufacturersResult)->toHaveCount($manufacturerCount);

    // check language count
    $delphi = PimProductManufacturer::byName($testName)->first()->translations;
    expect($delphi)->toHaveCount($languageCount);

    // add new language
    PimLanguage::factory()->create();
    $extraLanguages = PimLanguage::all();
    expect($extraLanguages)->toHaveCount($languageCount + 1);

    $delphi = PimProductManufacturer::byName($testName)->first()->translations;
    expect($delphi)->toHaveCount($languageCount + 1);

    // remove language
    $extraLanguages->last()->delete();
    $extraLanguages = PimLanguage::all();
    expect($extraLanguages)->toHaveCount($languageCount);

    $delphi = PimProductManufacturer::byName($testName)->first()->translations;
    expect($delphi)->toHaveCount($languageCount);

    // restore language
    PimLanguage::onlyTrashed()->first()->restore();
    $delphi = PimProductManufacturer::byName($testName)->first()->translations;
    expect($delphi)->toHaveCount($languageCount + 1);
});

it('upserts manufacturers', function () {

    $manufacturersResult = PimProductManufacturer::all();
    expect($manufacturersResult)->toBeEmpty();

    $otherLanguages = collect();

    $manufacturers = collect();
    $manufacturers->push([
        'MarkeCode' => 'HAK',
        'Bezeichnung_de' => 'Hakro',
    ]);

    PimProductManufacturerService::upsert($manufacturers, $otherLanguages);
    $manufacturersResult = PimProductManufacturer::all();
    expect($manufacturersResult)->toHaveCount(1);

    $manufacturers->push([
        'MarkeCode' => 'HAK',
        'Bezeichnung_de' => 'Delphi',
    ]);

    PimProductManufacturerService::upsert($manufacturers, $otherLanguages);
    $manufacturersResult = PimProductManufacturer::all();
    expect($manufacturersResult)->toHaveCount(2);

});
