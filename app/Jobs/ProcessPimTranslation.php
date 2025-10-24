<?php

namespace App\Jobs;

use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Pim\PimTranslationService;
use Croustibat\FilamentJobsMonitor\Traits\QueueProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPimTranslation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueProgress, SerializesModels;

    public PimProduct|PimProductManufacturer $record;

    public $maxExceptions = 1;

    public $tries = 2;

    public function __construct(PimProduct|PimProductManufacturer $record)
    {
        $this->record = $record;
    }

    public function handle()
    {
        $this->setProgress(0);

        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $extraLangIdCodes = $translationService->getExtraLanguagesShort();

        $this->record->refresh();

        PimTranslationService::getBulkTranslation($this->record, $defaultLangCode, $extraLangIdCodes);

        $this->setProgress(100);
    }

    public function displayName(): string
    {
        if ($this->record instanceof PimProductManufacturer) {
            return get_class($this).' manufacturer name: '.$this->record->name.' manufacturer id: '.$this->record->id;
        } else {
            $productNumber = $this->record->product_number ? 'product_number: '.$this->record->product_number : 'no product number';

            return get_class($this).' product identifier: '.$this->record->identifier.', '.$productNumber.', id: '.$this->record->id;
        }
    }

    public function __toString(): string
    {
        return $this->displayName();
    }
}
