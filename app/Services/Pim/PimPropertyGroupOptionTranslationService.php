<?php

namespace App\Services\Pim;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOptionTranslation;

class PimPropertyGroupOptionTranslationService
{
    public static function deleteByLanguage(PimLanguage $language): void
    {
        PimPropertyGroupOptionTranslation::where('language_id', $language->id)->delete();
    }

    public static function restoreByLanguage(PimLanguage $language): void
    {
        PimPropertyGroupOptionTranslation::withTrashed()
            ->where('language_id', $language->id)
            ->restore();
    }

    public static function createByLanguage(PimLanguage $language): void
    {
        PimPropertyGroupOption::query()->each(function ($propertyGroupOption) use ($language) {
            PimPropertyGroupOptionTranslation::firstOrCreate(
                [
                    'property_group_option_id' => $propertyGroupOption->id,
                    'language_id' => $language->id,
                ],
                [
                    'name' => $propertyGroupOption->name,
                    'position' => $propertyGroupOption->position,
                ]
            );
        });
    }
}
