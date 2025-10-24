<?php

namespace App\Jobs;

use App\Models\Pim\Product\PimProduct;
use App\Services\MediaLibrary\OpenAiImageGetColorService;
use App\Settings\GeneralSettings;
use Croustibat\FilamentJobsMonitor\Traits\QueueProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessPimProductImageColorDetermine implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueProgress, SerializesModels;

    public $maxExceptions = 1;

    public $tries = 2;

    public function __construct(
        protected Collection $similarProducts,
        protected PimProduct $product,
        protected Collection $media,
    ) {}

    public function handle()
    {
        $this->setProgress(0);

        if (! (new GeneralSettings)->openai_enabled) {
            return;
        }

        (new OpenAiImageGetColorService)->handleSimilarProducts($this->similarProducts, $this->product, $this->media);

        $this->setProgress(100);
    }

    public function displayName()
    {
        return get_class($this).' handle product identifier: '.$this->product->identifier.' product number: '.$this->product->product_number;
    }

    public function __toString()
    {
        return $this->displayName();
    }
}
