<?php

use App\Models\Pim\PimLanguage;
use App\Services\Pim\PimTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has language and extra languages', function () {

    // add 4 PimLanguage records
    $count = 4;
    $extraLanguagesCount = $count - 1;
    $configDefaultLanguage = substr(config('language.locales.default'), 0, 2);

    $languages = PimLanguage::factory($count)->create();

    expect($languages)->toHaveCount($count);

    $translationService = new PimTranslationService;

    $extraLanguages = $translationService->getExtraLanguages();
    $extraLanguagesShort = $translationService->getExtraLanguagesShort();
    $defaultLanguageCodeShort = $translationService->getDefaultLanguageCodeShort();

    expect($extraLanguages)->toHaveCount($extraLanguagesCount)
        ->and($extraLanguagesShort)->toHaveCount($extraLanguagesCount)
        ->and($defaultLanguageCodeShort)->toBeString()->toEqual($configDefaultLanguage);

});
