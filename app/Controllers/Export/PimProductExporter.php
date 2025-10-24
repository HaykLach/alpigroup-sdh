<?php

namespace App\Controllers\Export;

use App\Enums\Pim\PimMappingType;
use App\Jobs\ProcessPimExportDispatchJob;
use App\Models\Pim\PimTax;
use App\Services\Export\GenerateIdService;
use App\Services\Export\PimCustomFieldsExporterService;
use App\Services\Export\PimJobExportService;
use App\Services\Export\PimMediaExportService;
use App\Services\Export\PimProductExportService;
use App\Services\Export\PimProductExportValidationService;
use App\Services\Export\PimProductMediaExportService;
use App\Services\Export\PimPropertyGroupExporterService;
use App\Services\Export\PimPropertyGroupOptionExporterService;
use App\Services\Pim\PimProductService;
use App\Services\Pim\PimPropertyGroupService;
use App\Services\Pim\PimTranslationService;
use Carbon\Carbon;
use Exception;

class PimProductExporter
{
    protected string $provider = 'shopware';

    protected PimCustomFieldsExporterService $customFieldsExporterService;

    public PimProductExportService $productExportService;

    protected PimJobExportService $jobService;

    protected PimProductMediaExportService $productMediaService;

    protected PimPropertyGroupExporterService $propertyGroupExporterService;

    protected PimPropertyGroupOptionExporterService $propertyGroupOptionExporterService;

    protected PimProductExportValidationService $productExportValidationService;

    protected array $configMapping;

    protected string $mediaFolderId;

    protected array $salesChannelIds;

    protected array $languageMap;

    protected array $propertyGroupsThatDefineVariants;

    protected array $propertyGroupsToApplyFilter;

    public function __construct(
    ) {
        $this->productExportService = new PimProductExportService;
        $this->productExportValidationService = new PimProductExportValidationService;
        $this->jobService = new PimJobExportService;
        $this->propertyGroupExporterService = new PimPropertyGroupExporterService;
        $this->customFieldsExporterService = new PimCustomFieldsExporterService;
        $this->propertyGroupOptionExporterService = new PimPropertyGroupOptionExporterService;
        $this->productMediaService = new PimProductMediaExportService;

        $config = config('sdh-shopware-sdk.defaults');

        $this->configMapping = $config['mapping'];
        $this->mediaFolderId = $config['mediaFolderId'];
        $this->salesChannelIds = $config['salesChannels'];
        $this->languageMap = $config['languages'];
        $this->propertyGroupsThatDefineVariants = $config['propertyGroupsThatDefineVariants'];
        $this->propertyGroupsToApplyFilter = $config['propertyGroupsToApplyFilter'];
    }

    /**
     * @throws Exception
     */
    public function sync(): void
    {
        $exceptionFieldMapping = $this->propertyGroupExporterService->getExceptionFieldMapping(PimMappingType::PRODUCT, $this->configMapping);
        $pricePropertyId = $this->propertyGroupExporterService->getRetailPricePropertyId($exceptionFieldMapping, $this->configMapping);
        $weightPropertyId = $this->propertyGroupExporterService->getWeightPropertyId($exceptionFieldMapping, $this->configMapping);

        $propertyGroupsThatDefineVariantsMap = $this->propertyGroupOptionExporterService->getOptionsThatDefineVariations($this->propertyGroupsThatDefineVariants);
        $propertyGroupsToApplyFilterMap = $this->propertyGroupOptionExporterService->getPropertiesToApplyFilter($this->propertyGroupsToApplyFilter);
        $pimPropertyGroupsMedia = PimMediaExportService::getMediaPropertyGroups(PimMappingType::PRODUCT);

        $sw6Currency = $this->productExportService->getShopwareCurrency();
        $shopwareTaxMap = $this->productExportService->getProductShopwareTaxMap();
        $shopwareManufacturersMap = $this->productExportService->getProductShopwareManufacturersMap();

        $pimTax = PimTax::all();
        $locales = (new PimTranslationService)->getExtraLanguages();

        $customFieldMap = $this->customFieldsExporterService->getCustomFieldMap(PimMappingType::PRODUCT, $this->configMapping);
        $requiredGroups = PimPropertyGroupService::getRequiredGroups(PimMappingType::PRODUCT);
        $requiredProperties = PimPropertyGroupService::filterRequiredGroupsProperties($requiredGroups);
        $requiredFields = PimPropertyGroupService::filterRequiredGroupsFields($requiredGroups);

        $mainProducts = PimProductService::getProductMainIds();
        $mainProductsCount = $mainProducts->count();
        $jobCount = 0;

        $mainProducts->each(function ($pimProductId) use (
            $sw6Currency,
            $pricePropertyId,
            $weightPropertyId,
            $shopwareManufacturersMap,
            $shopwareTaxMap,
            $pimTax,
            $locales,
            $customFieldMap,
            $propertyGroupsThatDefineVariantsMap,
            $propertyGroupsToApplyFilterMap,
            $pimPropertyGroupsMedia,
            $requiredProperties,
            $requiredFields,
            $mainProductsCount,
            &$jobCount,
        ) {
            $pimProduct = PimProductService::getMainProductWithVariants($pimProductId);
            if ($pimProduct === null) {
                return;
            }

            $valid = $this->productExportValidationService->validate($pimProduct, $requiredProperties, $requiredFields);
            if (! $valid) {
                echo "Product $pimProductId is incomplete Manufacturer: ".$pimProduct->manufacturer->name.' - Product: '.$pimProduct->name.' ('.$pimProduct->id.' - '.")\n";

                return;
            }

            $jobCount++;

            $productId = GenerateIdService::getProductId($pimProduct);
            echo "$jobCount of $mainProductsCount => Manufacturer: ".$pimProduct->manufacturer->name.' - Product: '.$pimProduct->name.' ('.$pimProduct->id.' - '.$productId.")\n";
            echo 'Variants: '.count($pimProduct->variations)."\n";

            if (1) {
                ProcessPimExportDispatchJob::dispatch(
                    $pimProduct,
                    $sw6Currency,
                    $pricePropertyId,
                    $weightPropertyId,
                    $this->mediaFolderId,
                    $this->salesChannelIds,
                    $this->languageMap,
                    $shopwareManufacturersMap,
                    $shopwareTaxMap,
                    $pimTax,
                    $locales,
                    $customFieldMap,
                    $propertyGroupsThatDefineVariantsMap,
                    $propertyGroupsToApplyFilterMap,
                    $pimPropertyGroupsMedia,
                );
            } else {
                $this->productExportService->dispatchProductCreateJobs(
                    $pimProduct,
                    $sw6Currency,
                    $pricePropertyId,
                    $weightPropertyId,
                    $this->mediaFolderId,
                    $this->salesChannelIds,
                    $this->languageMap,
                    $shopwareManufacturersMap,
                    $shopwareTaxMap,
                    $pimTax,
                    $locales,
                    $customFieldMap,
                    $propertyGroupsThatDefineVariantsMap,
                    $propertyGroupsToApplyFilterMap,
                    $pimPropertyGroupsMedia,
                );
            }
        });

        $this->jobService->registerPimJob(Carbon::now(), $this->provider);
    }
}
