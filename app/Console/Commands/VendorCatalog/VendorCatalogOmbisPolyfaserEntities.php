<?php

namespace App\Console\Commands\VendorCatalog;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Enums\Pim\PimCustomerType;
use App\Enums\Pim\PimMappingType;
use App\Enums\Pim\PimProductPriceListTypes;
use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\Customer\PimCustomerAddress;
use App\Models\Pim\Customer\PimCustomerBranch;
use App\Models\Pim\Customer\PimCustomerBranchTranslation;
use App\Models\Pim\Customer\PimCustomerSalutation;
use App\Models\Pim\Customer\PimCustomerSalutationTranslation;
use App\Models\Pim\Customer\PimCustomerTaxGroup;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProduct;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Services\Pim\Import\PimVendorCatalogImportService;
use App\Services\Pim\PimGenerateIdService;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use App\Settings\GeneralSettings;
use Cerbero\JsonParser\JsonParser;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use SmartDato\Ombis\Ombis;
use SmartDato\Ombis\Requests\AgentsRequest;
use SmartDato\Ombis\Requests\BranchesRequest;
use SmartDato\Ombis\Requests\CustomersRequest;
use SmartDato\Ombis\Requests\PriceListsRequest;
use SmartDato\Ombis\Requests\SalutationsRequest;
use SmartDato\Ombis\Requests\TaxCustomerGroupsRequest;
use SmartDato\Ombis\Requests\TaxesRequest;
use SmartDato\Ombis\Requests\UnitTypesRequest;

class VendorCatalogOmbisPolyfaserEntities extends Command
{
    protected const string DEFAULT_CONFIG_NAME = 'ombis';

    protected Ombis $connector;

    protected VendorCatalogFileImportService $vendorCatalogFileImportService;

    protected ?int $requestRequestMaxRows = 1000; // example values: null, 50, 100, 10000

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vc:ombis-polyfaser-entities {config?} {--skipApiRequest}';

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
        $skipApiRequest = $this->option('skipApiRequest');

        $vendorConfigArgument = $this->argument('config');
        $vendorConfig = $this->getVendorConfig($vendorConfigArgument);

        $this->connector = new Ombis;
        $this->vendorCatalogFileImportService = new VendorCatalogFileImportService;

        // get or create definition
        $vendorDefinition = $this->getOrCreateVendorDefinition($vendorConfig);

        $languageIdItaliano = PimGenerateIdService::getLanguageId('Italiano');
        $countries = PimCountry::all()->select('id', 'iso')->pluck('id', 'iso');

        // get products from API
        if (! $skipApiRequest) {
            $this->output->info('Call Ombis APIs:');

            $this->output->info(PimMappingType::PRICE_LIST->value);
            $priceListUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::PRICE_LIST->value.'.json');
            $this->connector->requestEntity(PriceListsRequest::class, $priceListUpload, $this->requestRequestMaxRows);
            $this->upsertPricelist($priceListUpload);

            $this->output->info(PimMappingType::TAX->value);
            $taxUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::TAX->value.'.json');
            $this->connector->requestEntity(TaxesRequest::class, $taxUpload, $this->requestRequestMaxRows);
            $this->upsertTaxes($taxUpload);
            $this->resetTaxesPosition();

            $this->output->info(PimMappingType::SALUTATION->value);
            $salutationUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::SALUTATION->value.'.json');
            $this->connector->requestEntity(SalutationsRequest::class, $salutationUpload, $this->requestRequestMaxRows);
            $this->upsertSalutations($salutationUpload, $languageIdItaliano);

            $this->output->info(PimMappingType::BRANCH->value);
            $branchesUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::BRANCH->value.'.json');
            $this->connector->requestEntity(BranchesRequest::class, $branchesUpload, $this->requestRequestMaxRows);
            $this->upsertBranches($branchesUpload, $languageIdItaliano);

            $this->output->info(PimMappingType::TAX_CUSTOMER_GROUP->value);
            $taxCustomerGroupsUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::TAX_CUSTOMER_GROUP->value.'.json');
            $this->connector->requestEntity(TaxCustomerGroupsRequest::class, $taxCustomerGroupsUpload, $this->requestRequestMaxRows);
            $this->upsertCustomerTaxGroups($taxCustomerGroupsUpload);

            $this->output->info(PimMappingType::UNIT_TYPE->value);
            $unitTypesUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::UNIT_TYPE->value.'.json');
            $this->connector->requestEntity(UnitTypesRequest::class, $unitTypesUpload, $this->requestRequestMaxRows);

            $this->output->info(PimMappingType::AGENT->value);
            $agentsUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::AGENT->value.'.json');
            $this->connector->requestEntity(AgentsRequest::class, $agentsUpload, $this->requestRequestMaxRows);
            $this->upsertCustomers(PimCustomerType::AGENT, $agentsUpload, $countries);

            $this->output->info(PimMappingType::CUSTOMER->value);
            $customersUpload = $this->vendorCatalogFileImportService->getFileUploadPath($vendorDefinition, PimMappingType::CUSTOMER->value.'.json');
            $this->connector->requestEntity(CustomersRequest::class, $customersUpload, $this->requestRequestMaxRows);
            $this->upsertCustomers(PimCustomerType::CUSTOMER, $customersUpload, $countries);

            // artikelart

            $this->output->success('Api Responses processed');
        }

        return self::SUCCESS;
    }

    protected function upsertPricelist(string $fileUpload): void
    {
        if ($this->checkImportFileExists($fileUpload)) {
            $this->output->error('File not found: '.$fileUpload);
        } else {
            $erpIdPropertyGroupId = PimGenerateIdService::getPropertyGroupId('ID');

            /**
             * @var array $data
             */
            foreach (new JsonParser(storage_path().'/app/'.$fileUpload) as $data) {
                $erpId = $data['Artikel.ID'];
                $code = $data['PreislisteGueltigkeit.Preisliste.Code'];
                $price = $data['Verkaufspreis'];

                // validate code
                if (in_array($code, array_map(fn ($case) => $case->value, PimProductPriceListTypes::cases()))) {
                    // get product
                    $product = PimProduct::whereJsonContains('custom_fields->properties', [$erpIdPropertyGroupId => $erpId])->withTrashed()->first();
                    if (! $product) {
                        $this->output->error('Product not found: '.$erpId);

                        continue;
                    }

                    // store new prices in product
                    $prices = $product->prices ?? [];
                    $prices[$code] = $price;
                    $product->prices = $prices;
                    $product->save();
                }
            }
        }
    }

    protected function resetTaxesPosition(): void
    {
        PimTax::query()->update(['position' => null]);
        $taxRates = [0, 4, 10, 20, 22];
        foreach ($taxRates as $index => $rate) {
            PimTax::query()->where('tax_rate', $rate)->update(['position' => $index]);
        }
        // set default tax rate where tax_rate = 22
        PimTax::query()->update(['is_default' => null]);
        PimTax::query()->where('tax_rate', 22)->update(['is_default' => true]);
    }

    protected function upsertTaxes(string $fileUpload): void
    {
        if ($this->checkImportFileExists($fileUpload)) {
            $this->output->error('File not found: '.$fileUpload);
        } else {
            foreach (new JsonParser(storage_path().'/app/'.$fileUpload) as $data) {

                $id = PimGenerateIdService::getTaxId($data['Prozent']);

                PimTax::updateOrCreate(
                    ['id' => $id],
                    [
                        'name' => $data['Name_de'],
                        'tax_rate' => $data['Prozent'],
                    ]
                );
            }
        }
    }

    protected function upsertSalutations(string $fileUpload, string $languageIdItaliano): void
    {
        if ($this->checkImportFileExists($fileUpload)) {
            $this->output->error('File not found: '.$fileUpload);
        } else {
            foreach (new JsonParser(storage_path().'/app/'.$fileUpload) as $data) {

                $id = PimGenerateIdService::getCustomerSalutationId($data['ID']);

                $salutation = PimCustomerSalutation::updateOrCreate(
                    ['id' => $id],
                    [
                        'salutation_key' => $data['Code'],
                        'letter_name' => $data['FullName_de'],
                        'display_name' => $data['Name_de'],
                    ]
                );

                PimCustomerSalutationTranslation::updateOrCreate(
                    ['salutation_id' => $salutation->id, 'language_id' => $languageIdItaliano],
                    [
                        'letter_name' => $data['FullName_it'],
                        'display_name' => $data['Name_it'],
                    ]
                );
            }
        }
    }

    protected function upsertCustomerTaxGroups(string $fileUpload): void
    {
        if ($this->checkImportFileExists($fileUpload)) {
            $this->output->error('File not found: '.$fileUpload);
        } else {
            foreach (new JsonParser(storage_path().'/app/'.$fileUpload) as $data) {
                $id = PimGenerateIdService::getCustomerTaxGroupId($data['ID']);

                PimCustomerTaxGroup::updateOrCreate(
                    ['id' => $id],
                    [
                        'name' => $data['Name'],
                        'code' => $data['Code'],
                        'tax_handling' => $data['MwStBehandlung'],
                    ]
                );
            }
        }
    }

    protected function upsertBranches(string $fileUpload, string $languageIdItaliano): void
    {
        if ($this->checkImportFileExists($fileUpload)) {
            $this->output->error('File not found: '.$fileUpload);
        } else {
            foreach (new JsonParser(storage_path().'/app/'.$fileUpload) as $data) {
                $id = PimGenerateIdService::getCustomerBranchId($data['ID']);

                $branch = PimCustomerBranch::updateOrCreate(
                    ['id' => $id],
                    [
                        'code' => $data['Code'],
                        'name' => $data['Name_de'],
                    ]
                );

                PimCustomerBranchTranslation::updateOrCreate(
                    ['branch_id' => $branch->id, 'language_id' => $languageIdItaliano],
                    ['name' => $data['Name_it']]
                );
            }
        }
    }

    protected function upsertCustomers(PimCustomerType $type, string $fileUpload, Collection $countries): void
    {
        if ($this->checkImportFileExists($fileUpload)) {
            $this->output->error('File not found: '.$fileUpload);

            return;
        }

        $addressKey = $type === PimCustomerType::CUSTOMER ? 'Postadresse.' : 'Adresse.';

        foreach (new JsonParser(storage_path().'/app/'.$fileUpload) as $data) {

            // fallback empty email address
            if (empty($data[$addressKey.'EMail'])) {
                $data[$addressKey.'EMail'] = 'x';
            }

            // fallback empty address
            foreach ([$addressKey.'PLZ', $addressKey.'Strasse1', $addressKey.'Ort'] as $field) {
                if (empty($data[$field])) {
                    $data[$field] = '';
                }
            }

            $id = PimGenerateIdService::getCustomerId($data['ID'], $type);
            $branchId = $type === PimCustomerType::CUSTOMER ? PimGenerateIdService::getCustomerBranchId($data['Branche.ID']) : null;
            $vatId = $data[$addressKey.'MwStNummer'] ?? $data[$addressKey.'UStIDNummer'] ?? null;

            $customFields = [
                PimCustomerCustomFields::TYPE->value => $type->value,
                PimCustomerCustomFields::COMPANY_NAME->value => $data[$addressKey.'JuristischePerson'] ? trim($data[$addressKey.'Name2'].' '.$data[$addressKey.'Name1']) : null,
                PimCustomerCustomFields::FISCAL_CODE->value => $data[$addressKey.'Steuernummer'],
                PimCustomerCustomFields::VAT_ID->value => $vatId,
                PimCustomerCustomFields::NET_FOLDER_DOCUMENTS->value => $data['VerknuepfteDokumente'] ?? null,
                PimCustomerCustomFields::BLOCKED->value => $data['Gesperrt'],
            ];

            $agentId = null;
            if ($type === PimCustomerType::CUSTOMER && isset($data['Verkaeufer.ID']) && $data['Verkaeufer.ID']) {
                $agentId = PimGenerateIdService::getCustomerId($data['Verkaeufer.ID'], PimCustomerType::AGENT);
            }
            $customFields[PimCustomerCustomFields::AGENT_ID->value] = $agentId;

            $salutationId = $data[$addressKey.'Anrede.ID'];
            if ($salutationId !== null) {
                $salutationId = PimGenerateIdService::getCustomerSalutationId($salutationId);
            }

            $customer = PimCustomer::updateOrCreate(
                ['id' => $id],
                [
                    'branch_id' => $branchId,
                    'first_name' => $data[$addressKey.'Name2'],
                    'last_name' => $data[$addressKey.'Name1'],
                    'email' => $data[$addressKey.'EMail'] ?? 'x',
                    'salutation_id' => $salutationId,
                    'custom_fields' => $customFields,
                    'tax_group_id' => $type === PimCustomerType::CUSTOMER ? PimGenerateIdService::getCustomerTaxGroupId($data['MwStGruppe.ID']) : null,
                ]
            );

            $countryId = $countries[$data[$addressKey.'Land.ISOCode']] ?? null;
            if ($countryId) {
                $addressData = [
                    'customer_id' => $customer->id,
                    'zipcode' => $data[$addressKey.'PLZ'],
                    'country_id' => $countryId,
                    'salutation_id' => $salutationId,
                    'first_name' => $data[$addressKey.'Name2'],
                    'last_name' => $data[$addressKey.'Name1'],
                    'city' => $data[$addressKey.'Ort'],
                    'street' => $data[$addressKey.'Strasse1'],
                    'additional_address_line_1' => $data[$addressKey.'Strasse2'],
                    'phone_number' => $data[$addressKey.'Telefon'],
                    'region' => null,
                    'vat_id' => $vatId,
                ];

                PimCustomerAddress::updateOrCreate(
                    ['id' => $data[$addressKey.'UUID']],
                    $addressData
                );
            } else {
                $this->output->error('Country not found: '.$data[$addressKey.'Land.ISOCode'].' => '.$data[$addressKey.'Land.Name']);
            }
        }
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

    protected function getVendorConfig(?string $vendorConfigArgument): array
    {
        return $vendorConfigArgument ? config($vendorConfigArgument) : config(VendorCatalogOmbisPolyfaserEntities::DEFAULT_CONFIG_NAME);
    }

    protected function checkImportFileExists(string $fileUpload): bool
    {
        return ! Storage::disk('local')->exists($fileUpload);
    }
}
