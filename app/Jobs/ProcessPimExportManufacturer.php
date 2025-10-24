<?php

namespace App\Jobs;

use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Export\PimMediaExportService;
use Croustibat\FilamentJobsMonitor\Traits\QueueProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use SmartDato\SdhShopwareSdk\Controllers\ProductManufacturerController;
use SmartDato\SdhShopwareSdk\DataTransferObjects\ProductManufacturer;

class ProcessPimExportManufacturer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueProgress, SerializesModels;

    public $maxExceptions = 1;

    public $tries = 2;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected PimProductManufacturer $localEntry,
        protected ProductManufacturer $remoteEntry,
        protected PimMappingType $mappingType,
        protected Collection $locales,
        protected Collection $pimPropertyGroups,
        protected string $mediaFolderId,
    ) {}

    public function handle(): void
    {
        $this->setProgress(0);

        $data = PimMediaExportService::upsertItemMedia(
            $this->localEntry,
            $this->mappingType,
            $this->locales,
            $this->pimPropertyGroups,
            $this->mediaFolderId,
        );

        $data['name'] = $this->localEntry->name;

        $controller = new ProductManufacturerController;
        $controller->update($this->remoteEntry->id, $data);

        $this->setProgress(100);
    }

    public function displayName(): string
    {
        return get_class($this).' handle '.$this->localEntry->name;
    }

    public function __toString()
    {
        return $this->displayName();
    }
}
