<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use App\Controllers\Export\PimProductExporter;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\PimLanguage;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProduct;
use App\Services\Export\GenerateIdService;
use App\Services\Export\PimCustomFieldsExporterService;
use App\Services\Export\PimMediaExportService;
use App\Services\Export\PimPropertyGroupExporterService;
use App\Services\Export\PimPropertyGroupOptionExporterService;
use App\Services\Pim\PimProductService;
use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\ProductController;
use SmartDato\SdhShopwareSdk\DataTransferObjects\Product;

class ProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export Products to Shopware 6';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // @todo make sure to sync manufacturers, tax, currency, propertyGroups, customFieldSets first

        $exporter = new PimProductExporter;

        // $this->testGetProduct();
        // $this->testCRUD($exporter);
        $exporter->sync();

        return self::SUCCESS;
    }

    protected function testGetProduct(): void
    {
        $testId = 'dbe886b058f4550ea2da2b48ddf4e50d';

        $product = (new ProductController)->get($testId);
        dd($product);
    }

    protected function testCRUD(PimProductExporter $exporter): void
    {
        $productController = new ProductController;

        $pimProduct = PimProduct::query()
            ->whereNull('parent_id')
            ->skip(5) // Skip
            ->take(1) // Take
            ->first();

        $pimProduct = PimProductService::getMainProductWithVariants($pimProduct->id);

        $productId = GenerateIdService::getProductId($pimProduct);

        $existingProduct = $productController->get($productId);

        $config = config('sdh-shopware-sdk.defaults');

        $mediaFolderId = $config['mediaFolderId'];
        $configMapping = $config['mapping'];
        $salesChannelIds = $config['salesChannels'];
        $languageMap = $config['languages'];
        $propertyGroupsThatDefineVariants = $config['propertyGroupsThatDefineVariants'];
        $propertyGroupsToApplyFilter = $config['propertyGroupsToApplyFilter'];

        $propertyGroupExporterService = new PimPropertyGroupExporterService;
        $exceptionFieldMapping = $propertyGroupExporterService->getExceptionFieldMapping(PimMappingType::PRODUCT, $configMapping);
        $pricePropertyId = $propertyGroupExporterService->getRetailPricePropertyId($exceptionFieldMapping, $configMapping);

        $propertyGroupOptionExporterService = new PimPropertyGroupOptionExporterService;
        $propertyGroupsThatDefineVariantsMap = $propertyGroupOptionExporterService->getOptionsThatDefineVariations($propertyGroupsThatDefineVariants);
        $propertyGroupsToApplyFilterMap = $propertyGroupOptionExporterService->getPropertiesToApplyFilter($propertyGroupsToApplyFilter);

        $pimPropertyGroupsMedia = PimMediaExportService::getMediaPropertyGroups(PimMappingType::PRODUCT);

        $weightPropertyId = $propertyGroupExporterService->getWeightPropertyId($exceptionFieldMapping, $configMapping);

        $sw6Currency = $exporter->productExportService->getShopwareCurrency();

        $shopwareManufacturersMap = $exporter->productExportService->getProductShopwareManufacturersMap();
        $shopwareTaxMap = $exporter->productExportService->getProductShopwareTaxMap();

        $pimTax = PimTax::all();
        $locales = PimLanguage::getAllWithLocalKeyedByCode();

        $customFieldsExporterService = new PimCustomFieldsExporterService;
        $customFieldMap = $customFieldsExporterService->getCustomFieldMap(PimMappingType::PRODUCT, $configMapping);

        $this->info('assign Data for new product');

        $newProduct = $exporter->productExportService->assignData(
            $pimProduct,
            $existingProduct,
            $sw6Currency,
            $pricePropertyId,
            $weightPropertyId,
            $mediaFolderId,
            $salesChannelIds,
            $languageMap,
            $shopwareManufacturersMap,
            $shopwareTaxMap,
            $pimTax,
            $locales,
            $customFieldMap,
            $propertyGroupsThatDefineVariantsMap,
            $propertyGroupsToApplyFilterMap,
            $pimPropertyGroupsMedia,
        );

        $this->info('delete Product '.$pimProduct->name);
        $delete = $productController->delete($newProduct->id);
        $this->print($delete);

        // product handling
        $this->info('create Product '.$pimProduct->name);
        $create = $productController->create($newProduct);
        $this->print($create);

        $this->info('list products (limit=100)');
        $list = $productController->list();
        $this->print($list->count());

        // image handling
        // $this->info('dispatch images jobs');
        // $exporter->productMediaService->dispatchProductImagesJobs($pimProduct);

        $this->info('get Product '.$pimProduct->name);
        $item = $productController->get($create->id);
        $this->print($item);

        $updatedName = $pimProduct->name.' updated';

        $this->info('update testProduct -> '.$updatedName);
        $update = $productController->update(
            new Product(
                name: $updatedName,
                id: GenerateIdService::getProductId($pimProduct),
            )
        );
        $this->print($update);

        $this->info('get Product '.$updatedName);
        $item = $productController->get($update->id);
        $this->print($item);
        /*
                $this->info('delete Product ' . $updatedName);
                $delete = $productController->delete($item->id);
                $this->print($delete);
        */
    }

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}
