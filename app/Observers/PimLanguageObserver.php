<?php

namespace App\Observers;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\Product\PimProductManufacturerTranslation;
use App\Models\Pim\Product\PimProductTranslation;
use App\Services\Pim\PimProductManufacturerTranslationService;
use App\Services\Pim\PimProductTranslationService;
use App\Services\Pim\PimPropertyGroupOptionTranslationService;

class PimLanguageObserver
{
    /**
     * Handle the PimLanguage "created" event.
     */
    public function created(PimLanguage $pimLanguage): void
    {
        PimPropertyGroupOptionTranslationService::createByLanguage($pimLanguage);
        PimProductTranslationService::createByLanguage($pimLanguage);
        PimProductManufacturerTranslationService::createByLanguage($pimLanguage);
    }

    /**
     * Handle the PimLanguage "updated" event.
     */
    public function updated(PimLanguage $pimLanguage): void {}

    /**
     * Handle the PimLanguage "deleted" event.
     */
    public function deleted(PimLanguage $pimLanguage): void
    {
        PimPropertyGroupOptionTranslationService::deleteByLanguage($pimLanguage);
        PimProductTranslationService::deleteByLanguage($pimLanguage);
        PimProductManufacturerTranslationService::deleteByLanguage($pimLanguage);
    }

    /**
     * Handle the PimLanguage "restored" event.
     */
    public function restored(PimLanguage $pimLanguage): void
    {
        if (PimProductTranslation::withTrashed()->where('language_id', $pimLanguage->id)->exists()) {
            PimPropertyGroupOptionTranslationService::restoreByLanguage($pimLanguage);
            PimProductTranslationService::restoreByLanguage($pimLanguage);
        } else {
            PimPropertyGroupOptionTranslationService::createByLanguage($pimLanguage);
            PimProductTranslationService::createByLanguage($pimLanguage);
        }

        if (PimProductManufacturerTranslation::withTrashed()->where('language_id', $pimLanguage->id)->exists()) {
            PimProductManufacturerTranslationService::restoreByLanguage($pimLanguage);
        } else {
            PimProductManufacturerTranslationService::createByLanguage($pimLanguage);
        }
    }

    /**
     * Handle the PimLanguage "force deleted" event.
     */
    public function forceDeleted(PimLanguage $pimLanguage): void
    {
        //
    }
}
