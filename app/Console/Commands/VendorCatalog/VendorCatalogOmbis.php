<?php

namespace App\Console\Commands\VendorCatalog;

use App\Enums\Pim\PimMappingType;
use App\Enums\VendorCatalog\VendorCatalogImportEntryState;
use App\Enums\VendorCatalog\VendorCatalogImportState;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Models\VendorCatalog\VendorCatalogEntry;
use App\Services\Pim\Import\PimProductImportService;
use App\Services\Pim\Import\PimPropertyGroupSetupService;
use App\Services\Pim\Import\PimVendorCatalogImportService;
use App\Services\Pim\PimProductManufacturerService;
use App\Services\Pim\PimTranslationService;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use App\Settings\GeneralSettings;
use Cerbero\JsonParser\JsonParser;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use SmartDato\Ombis\Ombis;

class VendorCatalogOmbis extends Command
{
    protected const string DEFAULT_CONFIG_NAME = 'ombis';

    protected Ombis $connector;

    protected VendorCatalogFileImportService $vendorCatalogFileImportService;

    protected ?int $productRequestMaxRows = 1000; // example values: null, 50, 100, 10000

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vc:ombis {config?} {--forceImport} {--skipApiRequest} {--skipEntries} {--truncateTables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Ombis Import';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(GeneralSettings $settings): int
    {
        $forceImport = $this->option('forceImport');
        $skipApiRequest = $this->option('skipApiRequest');
        $skipEntries = $this->option('skipEntries');
        $truncateTables = $this->option('truncateTables');

        if ($truncateTables) {
            PimVendorCatalogImportService::truncateTables();
        }

        $vendorConfigArgument = $this->argument('config');
        $vendorConfig = $this->getVendorConfig($vendorConfigArgument);

        $this->connector = new Ombis;
        $this->vendorCatalogFileImportService = new VendorCatalogFileImportService;

        // purge entries that where not processed and are in state "new"
        VendorCatalogEntry::query()->where('state', '=', VendorCatalogImportEntryState::NEW->value)->delete();

        // get or create definition
        $vendorDefinition = $this->getOrCreateVendorDefinition($vendorConfig);

        $handleManufacturers = $this->getHandleManufacturers($vendorConfig);
        $manufacturersFileUpload = $handleManufacturers ? $this->getManufacturersUpload($vendorDefinition) : null;
        $productsFileUpload = $this->getProductsUpload($vendorDefinition);

        // get products from API
        if (! $skipApiRequest) {
            $this->output->info('Call Ombis APIs:');
            $this->output->info(PimMappingType::PRODUCT->value);

            $validManufacturerCodes = $handleManufacturers ? $vendorConfig['validManufacturerCodes'] : null;
            $this->connector->requestProducts($productsFileUpload, maxRows: $this->productRequestMaxRows, validManufacturerCodes: $validManufacturerCodes);

            if ($handleManufacturers) {
                $this->output->info(PimMappingType::MANUFACTURER->value);
                $this->requestManufacturers($productsFileUpload, $manufacturersFileUpload);
            }

            $this->output->success('Api Responses stored');
        }

        // check if manufacturers.json exists
        if ($handleManufacturers && $this->checkManufacturersFileExists($manufacturersFileUpload)) {
            $this->output->error('Manufacturers json response "'.$manufacturersFileUpload.'" not found');

            return self::FAILURE;
        }

        if (! $skipEntries) {
            // create VendorCatalogImport, import Records, store Entries

            // store file in ex. vendor_catalogs/2024/05/10/15ec0d0b-4aab-4db5-8a9f-981f4e7553dd
            // check if content of file is already processed, set sate "New" or "Duplicate"
            $vendorCatalogImport = $this->vendorCatalogFileImportService->importFile($vendorDefinition);

            if ($vendorCatalogImport->state === VendorCatalogImportState::DUPLICATED) {
                $this->output->info('VendorCatalogImport "'.$vendorConfig['vendorCatalogDefinitionName'].'" already processed, no changes detected');
                if (! $forceImport) {
                    $this->output->info('use option --forceImport to proceed');

                    return self::FAILURE;
                }
            }

            // store records in VendorCatalogImportRecord table
            $this->output->info('process VendorCatalogImportRecord...');
            $this->vendorCatalogFileImportService->importRecords(import: $vendorCatalogImport);
            $this->output->success('VendorCatalogImportRecord processed');

            // store records in VendorCatalogEntries table
            $this->output->info('process VendorCatalogEntries...');
            $this->vendorCatalogFileImportService->importEntries($vendorCatalogImport);
            $this->output->success('VendorCatalogEntries processed');
        }

        PimPropertyGroupSetupService::handlePropertyGroupsAndOptions($vendorConfig['mapping']);
        $this->output->success('PropertyGroups processed');

        $translationService = new PimTranslationService;
        $otherLanguages = $translationService->getExtraLanguages();
        $this->handleImport($vendorConfig, $settings, $manufacturersFileUpload, $otherLanguages, $handleManufacturers);

        return self::SUCCESS;
    }

    protected function getOrCreateVendorDefinition(array $vendorConfig): VendorCatalogImportDefinition
    {
        $vendorDefinition = $this->vendorCatalogFileImportService->getDefinitionByName($vendorConfig['vendorCatalogDefinitionName']);
        if ($vendorDefinition === null) {
            $this->output->info('updateOrCreate VendorCatalogVendor "'.$vendorConfig['vendorName'].'"');
            $vendorId = PimVendorCatalogImportService::createVendorCatalogVendor($vendorConfig['vendorName']);

            $this->output->info('Create VendorCatalogImportDefinition "'.$vendorConfig['vendorCatalogDefinitionName'].'"');
            $nameField = $vendorConfig['structure'][PimMappingType::PRODUCT->value]['name'];
            $vendorDefinition = PimVendorCatalogImportService::createVendorDefinition($nameField, $vendorId, $vendorConfig['vendorCatalogDefinitionName']);
        }

        return $vendorDefinition;
    }

    protected function requestManufacturers(string $productsFileUpload, string $manufacturersFile): void
    {
        // @todo check manufacturer json storage should be with date folder
        $productsFileFullPath = storage_path().'/app/'.$productsFileUpload;

        // add manufacturers
        $manufacturers = [];
        foreach (new JsonParser($productsFileFullPath) as $record) {
            $manufacturers[$record['MarkeCode']] = $record['MarkeCode'];
        }
        $manufacturers = collect($manufacturers)
            ->map(function ($manufacturerId) {
                return $this->connector->requestManufacturer($manufacturerId);
            });

        $this->vendorCatalogFileImportService->storeFile($manufacturersFile, json_encode($manufacturers));
    }

    protected function upsertManufacturers(string $manufacturersPath, Collection $otherLanguages): array
    {
        $manufacturers = collect(json_decode(Storage::get($manufacturersPath), true));

        return PimProductManufacturerService::upsert($manufacturers, $otherLanguages);
    }

    protected function handleImport(array $vendorConfig, GeneralSettings $settings, ?string $manufacturersPath, Collection $otherLanguages, bool $handleManufacturers = true): void
    {
        // count new entries
        $entriesCount = $this->getNewVendorCatalogEntryCount();
        if ($entriesCount === 0) {
            $this->output->info('No new entries found');

            return;
        }

        $this->output->info($entriesCount.' entries found');

        $manufacturers = $handleManufacturers ? $this->upsertManufacturers($manufacturersPath, $otherLanguages) : [];
        $handledRecords = PimProductImportService::handleImport($vendorConfig, $manufacturers, $otherLanguages);

        $this->output->success($handledRecords['variant']->count().' Product variants processed, '.' '.$handledRecords['main']->count().' main Products processed');

        $deletedProducts = PimProductImportService::deleteUnhandledProducts($handledRecords);
        $this->output->success($deletedProducts.' Products deleted');

        if ($settings->translationService_enabled && $settings->autoTranslateByRemoteService) {
            $this->output->success('Translate all products');
            (new PimTranslationService)->translateAllProducts();
        }

        if ($settings->translationService_enabled && $settings->autoDetermineProductColor) {
            $this->output->success('Determine product color');
            $this->call('sdh:image-color-determine');
        }
    }

    protected function getHandleManufacturers(array $vendorConfig): bool
    {
        return isset($vendorConfig['validManufacturerCodes']) && $vendorConfig['validManufacturerCodes'] !== false;
    }

    protected function getVendorConfig(?string $vendorConfigArgument): array
    {
        return $vendorConfigArgument ? config($vendorConfigArgument) : config(VendorCatalogOmbis::DEFAULT_CONFIG_NAME);
    }

    protected function getManufacturersUpload(VendorCatalogImportDefinition $vendorDefinition): string
    {
        return $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::MANUFACTURER->value.'.json');
    }

    protected function getProductsUpload(VendorCatalogImportDefinition $vendorDefinition): string
    {
        return $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition);
    }

    protected function checkManufacturersFileExists(string $manufacturersFileUpload): bool
    {
        return ! Storage::disk('local')->exists($manufacturersFileUpload);
    }

    protected function getNewVendorCatalogEntryCount(): int
    {
        return VendorCatalogEntry::query()
            ->where('state', VendorCatalogImportEntryState::NEW)
            ->count();
    }
}
