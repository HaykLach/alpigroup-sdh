<?php

namespace App\Jobs;

use App\Models\Pim\Product\PimProduct;
use App\Services\Export\PimProductExportService;
use Croustibat\FilamentJobsMonitor\Traits\QueueProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessPimExportDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueProgress, SerializesModels;

    public $maxExceptions = 1;

    public $tries = 2;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected PimProduct $pimProduct;

    protected string $sw6Currency;

    protected string $pricePropertyId;

    protected string $weightPropertyId;

    protected string $mediaFolderId;

    protected array $salesChannelIds;

    protected array $languageMap;

    protected Collection $shopwareManufacturersMap;

    protected Collection $shopwareTaxMap;

    protected Collection $pimTax;

    protected Collection $locales;

    protected Collection $customFieldMap;

    protected Collection $propertyGroupsThatDefineVariantsMap;

    protected Collection $propertyGroupsToApplyFilterMap;

    protected Collection $pimPropertyGroupsMedia;

    public function __construct(
        PimProduct $pimProduct,
        string $sw6Currency,
        string $pricePropertyId,
        string $weightPropertyId,
        string $mediaFolderId,
        array $salesChannelIds,
        array $languageMap,
        Collection $shopwareManufacturersMap,
        Collection $shopwareTaxMap,
        Collection $pimTax,
        Collection $locales,
        Collection $customFieldMap,
        Collection $propertyGroupsThatDefineVariantsMap,
        Collection $propertyGroupsToApplyFilterMap,
        Collection $pimPropertyGroupsMedia,
    ) {
        $this->pimProduct = $pimProduct;
        $this->sw6Currency = $sw6Currency;
        $this->pricePropertyId = $pricePropertyId;
        $this->weightPropertyId = $weightPropertyId;
        $this->mediaFolderId = $mediaFolderId;
        $this->salesChannelIds = $salesChannelIds;
        $this->languageMap = $languageMap;
        $this->shopwareManufacturersMap = $shopwareManufacturersMap;
        $this->shopwareTaxMap = $shopwareTaxMap;
        $this->pimTax = $pimTax;
        $this->locales = $locales;
        $this->customFieldMap = $customFieldMap;
        $this->propertyGroupsThatDefineVariantsMap = $propertyGroupsThatDefineVariantsMap;
        $this->propertyGroupsToApplyFilterMap = $propertyGroupsToApplyFilterMap;
        $this->pimPropertyGroupsMedia = $pimPropertyGroupsMedia;
    }

    public function handle(PimProductExportService $productService)
    {
        $this->setProgress(0);

        $productService->dispatchProductCreateJobs(
            $this->pimProduct,
            $this->sw6Currency,
            $this->pricePropertyId,
            $this->weightPropertyId,
            $this->mediaFolderId,
            $this->salesChannelIds,
            $this->languageMap,
            $this->shopwareManufacturersMap,
            $this->shopwareTaxMap,
            $this->pimTax,
            $this->locales,
            $this->customFieldMap,
            $this->propertyGroupsThatDefineVariantsMap,
            $this->propertyGroupsToApplyFilterMap,
            $this->pimPropertyGroupsMedia,
        );

        $this->setProgress(100);
    }

    public function displayName()
    {
        $identifierString = $this->pimProduct->identifier ? ' with identifier '.$this->pimProduct->identifier : '';

        return get_class($this).' handle '.$this->pimProduct->id.' with product_number '.$this->pimProduct->product_number.$identifierString.' name: '.$this->pimProduct->name;
    }

    public function __toString()
    {
        return $this->displayName();
    }
}
