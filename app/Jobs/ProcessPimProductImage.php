<?php

namespace App\Jobs;

use App\Services\Pim\Import\PimProductImageService;
use Croustibat\FilamentJobsMonitor\Traits\QueueProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPimProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueProgress, SerializesModels;

    protected string $url;

    public $maxExceptions = 1;

    public $tries = 2;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function handle()
    {
        $this->setProgress(0);

        (new PimProductImageService)->handleUrl($this->url);

        $this->setProgress(100);
    }

    public function displayName()
    {
        return get_class($this).' handle '.$this->url;
    }

    public function __toString()
    {
        return $this->displayName();
    }
}
