<?php

namespace App\Jobs;

use App\Models\Pim\Product\PimProduct;
use App\Services\Export\PimProductMediaExportService;
use Croustibat\FilamentJobsMonitor\Traits\QueueProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPimExportProductMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueProgress, SerializesModels;

    public PimProduct $record;

    public $maxExceptions = 1;

    public $tries = 2;

    public $retryAfter = 20;

    public function __construct(
        protected string $id,
        protected string $url,
        protected string $filename,
    ) {}

    public function handle()
    {
        $this->setProgress(0);

        $productMediaService = new PimProductMediaExportService;
        $success = $productMediaService->handleImageRequest($this->id, $this->url, $this->filename);

        if ($success) {
            $this->setProgress(100);
        } else {
            $this->fail();
        }
    }

    public function displayName()
    {
        return get_class($this).' handle '.$this->url.' with filename '.$this->filename.' and id '.$this->id;
    }

    public function __toString()
    {
        return $this->displayName();
    }
}
