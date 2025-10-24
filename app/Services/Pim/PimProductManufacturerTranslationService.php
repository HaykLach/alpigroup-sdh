<?php

namespace App\Services\Pim;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Models\Pim\Product\PimProductManufacturerTranslation;
use Illuminate\Support\Collection;

class PimProductManufacturerTranslationService
{
    public static function addInitialTranslations(PimProductManufacturer $manufacturer, Collection $otherLanguages): void
    {
        $otherLanguages->each(function ($langCode, $languageId) use ($manufacturer) {
            PimProductManufacturerTranslation::create([
                'manufacturer_id' => $manufacturer->id,
                'language_id' => $languageId,
                'name' => $manufacturer->name,
            ]);
        });
    }

    public static function restoreByLanguage(PimLanguage $language): void
    {
        PimProductManufacturerTranslation::withTrashed()
            ->where('language_id', $language->id)
            ->restore();
    }

    public static function deleteByLanguage(PimLanguage $language): void
    {
        PimProductManufacturerTranslation::where('language_id', $language->id)->delete();
    }

    public static function createByLanguage(PimLanguage $language): void
    {
        PimProductManufacturer::query()->each(function ($manufacturer) use ($language) {
            PimProductManufacturerTranslation::firstOrCreate(
                [
                    'manufacturer_id' => $manufacturer->id,
                    'language_id' => $language->id,
                ],
                [
                    'name' => $manufacturer->name,
                ]
            );
        });
    }
}
