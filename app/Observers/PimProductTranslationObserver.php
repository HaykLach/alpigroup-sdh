<?php

namespace App\Observers;

use App\Models\Pim\Product\PimProductTranslation;

class PimProductTranslationObserver
{
    /**
     * Handle the PimProductTranslation "created" event.
     */
    public function created(PimProductTranslation $pimProduct): void
    {
        //
    }

    /**
     * Handle the PimProductTranslation "updated" event.
     */
    public function updated(PimProductTranslation $pimProduct): void
    {
        //
    }

    /**
     * Handle the PimProductTranslation "deleted" event.
     */
    public function deleted(PimProductTranslation $pimProduct): void
    {
        //
    }

    /**
     * Handle the PimProductTranslation "restored" event.
     */
    public function restored(PimProductTranslation $pimProduct): void
    {
        //
    }

    /**
     * Handle the PimProductTranslation "force deleted" event.
     */
    public function forceDeleted(PimProductTranslation $pimProduct): void
    {
        //
    }
}
