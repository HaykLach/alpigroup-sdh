<?php

namespace App\Services\Pim;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductTranslation;
use Illuminate\Support\Collection;

class PimProductTranslationService
{
    public static function addInitialTranslations(PimProduct $product, Collection $otherLanguages): void
    {
        $otherLanguages->each(function ($langCode, $languageId) use ($product) {
            PimProductTranslation::create([
                'product_id' => $product->id,
                'language_id' => $languageId,
                'name' => $product->name,
                'description' => $product->description,
                'custom_fields' => $product->custom_fields,
            ]);
        });
    }

    public static function updateTranslations(PimProduct $product, array $translations): void
    {
        foreach ($translations as $languageId => $translation) {
            $data = [];
            if ($translation['name']) {
                $data['name'] = $translation['name'];
            }
            if ($translation['description']) {
                $data['description'] = $translation['description'];
            }
            if (count($data)) {
                PimProductTranslation::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'language_id' => $languageId,
                    ],
                    $data
                );
            }
        }
    }

    public static function restoreByLanguage(PimLanguage $language): void
    {
        PimProductTranslation::withTrashed()
            ->where('language_id', $language->id)
            ->restore();
    }

    public static function deleteByLanguage(PimLanguage $language): void
    {
        PimProductTranslation::where('language_id', $language->id)->delete();
    }

    public static function createByLanguage(PimLanguage $language): void
    {
        PimProduct::query()->each(function ($product) use ($language) {
            PimProductTranslation::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'language_id' => $language->id,
                ],
                [
                    'name' => $product->name,
                    'description' => $product->description,
                    'custom_fields' => $product->custom_fields,
                ]
            );
        });
    }
}
