<?php

namespace App\Observers;

use App\Models\Pim\Product\PimProduct;

class PimProductObserver
{
    /**
     * Handle the PimProduct "created" event.
     */
    public function created(PimProduct $pimProduct): void
    {
        //
    }

    /**
     * Handle the PimProduct "updated" event.
     */
    public function updated(PimProduct $pimProduct): void {}

    /**
     * Handle the PimProduct "deleted" event.
     */
    public function deleted(PimProduct $pimProduct): void
    {
        $pimProduct->translations()->delete();
    }

    /**
     * Handle the PimProduct "restored" event.
     */
    public function restored(PimProduct $pimProduct): void
    {
        $pimProduct->translations()->restore();
    }

    /**
     * Handle the PimProduct "force deleted" event.
     */
    public function forceDeleted(PimProduct $pimProduct): void
    {
        //
    }
}
