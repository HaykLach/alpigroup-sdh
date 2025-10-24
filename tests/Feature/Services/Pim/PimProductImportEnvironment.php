<?php

namespace Tests\Feature\Services\Pim;

use App\Enums\Pim\PimFormStoreField;
use App\Enums\Pim\PimMappingType;
use App\Enums\VendorCatalog\VendorCatalogImportEntryState;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\VendorCatalog\VendorCatalogEntry;
use App\Models\VendorCatalog\VendorCatalogVendor;
use App\Services\Pim\Import\PimPropertyGroupSetupService;
use App\Services\Pim\PimProductManufacturerService;
use App\Services\Pim\PimTranslationService;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Collection;

class PimProductImportEnvironment
{
    protected Generator $faker;

    protected array $config;

    protected Collection $vendorCatalogVendor;

    protected float $samplePimTaxRate = 22.0;

    protected PimTranslationService $translationService;

    protected array $manufacturerCodes;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->translationService = new PimTranslationService;

        $this->faker = Factory::create('de_DE');
        $this->vendorCatalogVendor = $this->setupVendorCatalogVendor();
    }

    protected function setupVendorCatalogVendor()
    {
        return VendorCatalogVendor::factory(1)->create();
    }

    public function addManufacturers(?Collection $manufacturers = null): void
    {
        $manufacturers = $manufacturers ?? $this->getSampleOmbisManufacturerData();
        $this->manufacturerCodes = PimProductManufacturerService::upsert($manufacturers, $this->getOtherLanguages());
    }

    public function getManufacturerCodes(): array
    {
        return $this->manufacturerCodes;
    }

    public function addPropertyGroups(): void
    {
        // add PropertyGroups for fields "custom_fields" and "prices"
        $mapping = $this->config['mapping'];

        PimPropertyGroupSetupService::addPropertyGroupsAndOptions($mapping, PimMappingType::PRODUCT, $this->getOtherLanguages());
        PimPropertyGroupSetupService::addPropertyGroupsAndOptions($mapping, PimMappingType::MANUFACTURER, $this->getOtherLanguages());
    }

    public function getPropertyGroupsDefinedByConfig(array $configArray, PimMappingType $type): array
    {
        $propertyGroupFields = $configArray['mapping'][$type->value];
        // filter fields that are stored as custom fields
        $propertyGroupFields = array_filter($propertyGroupFields, function ($entry) {
            return in_array($entry['field'], [PimFormStoreField::CUSTOM_FIELDS->value, PimFormStoreField::MEDIA->value, PimFormStoreField::PRICES->value]);
        });

        $propertyGroupFields = array_keys($propertyGroupFields);

        return $this->getArraySortedByName($propertyGroupFields);
    }

    protected function getArraySortedByName(array $array): array
    {
        asort($array);

        return array_values($array);
    }

    public function getPropertyGroupByName(string $name): PimPropertyGroup
    {
        return PimPropertyGroup::query()->where('name', $name)->get()->first();
    }

    public function getPropertyGroupsStoredByHandle(PimMappingType $type): Collection
    {
        // @todo set ->value instead of ->name, or use enum cast
        return PimPropertyGroup::query()->where('custom_fields->type', $type->name)->get();
    }

    public function getPropertyGroupsStoredByHandleFieldNames(PimMappingType $type): array
    {
        $propertyGroups = $this->getPropertyGroupsStoredByHandle($type);
        $propertyGroups = $propertyGroups->pluck('name')->toArray();

        return $this->getArraySortedByName($propertyGroups);
    }

    public function getPropertyGroupOptions(): Collection
    {
        return PimPropertyGroupOption::all();
    }

    public function getOtherLanguages(): Collection
    {
        return $this->translationService->getExtraLanguages();
    }

    public function getPimTaxRateId(): string
    {
        return PimTax::query()
            ->where('tax_rate', $this->samplePimTaxRate)
            ->first()
            ->id;
    }

    protected function getVendorCatalogId(): string
    {
        return $this->vendorCatalogVendor->first()->id;
    }

    public function getSampleMainArticleNumber(): int
    {
        return $this->faker->unique()->randomNumber();
    }

    public function getSampleVendorCatalogEntriesData(int $amount, ?string $mainArticleId = null): array
    {
        $data = [];
        for ($i = 0; $i < $amount; $i++) {
            $data[$i] = $this->getSampleVendorCatalogEntryData($mainArticleId);
        }

        return $data;
    }

    protected function getSampleVendorCatalogEntryData(?string $mainArticleId = null): array
    {
        $isProductVariant = $mainArticleId !== null;
        // @todo use fake() instead of faker
        $mainArticleId = $mainArticleId ?? $this->faker->unique()->randomNumber();

        return [
            'ID' => $this->faker->unique()->randomNumber(),
            'Code' => $this->faker->unique()->randomNumber(),
            'XF_Cut' => null,
            'EANCode' => $this->faker->unique()->randomNumber(),
            'XF_Shop' => true,
            'XF_Size' => $this->faker->word(),
            'MwStSatz' => $this->samplePimTaxRate,
            'XF_Color' => $this->faker->colorName(),
            'MarkeCode' => $this->faker->randomElement(array_keys($this->manufacturerCodes)),
            'XF_Gender' => $this->faker->randomElement(['women', 'men', 'unisex', 'kids']),
            'XF_Season' => $this->faker->randomElement(['summer', 'winter']),
            'XF_MainName' => $this->faker->word(),
            'Nettogewicht' => $this->faker->randomFloat(2, 0.01, 1),
            'XF_ArmLength' => $this->faker->randomElement(['full', 'half']),
            'Verkaufspreis' => $this->faker->randomFloat(2, 20, 50),
            'XF_SizeFilter' => $this->faker->randomElement(['s', 'm', 'l', 'xl']),
            'XF_TissueInfo' => $this->faker->realText(),
            'Bezeichnung_de' => $this->faker->realText(20),
            'Bezeichnung_it' => null,
            'XF_ColorFilter' => $this->faker->randomElement(['black', 'white', 'red']),
            'Beschreibung_de' => $this->faker->realText(),
            'Beschreibung_it' => null,
            'XF_NoteWashCare' => null,
            'XF_LinkDataSheet' => null,
            'XF_LinkModelImage1' => null,
            'XF_LinkModelImage2' => null,
            'XF_LinkModelImage3' => null,
            'XF_LinkModelImage4' => null,
            'XF_LinkModelImage5' => null,
            'XF_LinkProductImage1' => $this->faker->imageUrl(),
            'XF_LinkProductImage2' => $this->faker->imageUrl(),
            'XF_LinkProductImage3' => $this->faker->imageUrl(),
            'XF_LinkProductImage4' => null,
            'XF_LinkProductImage5' => null,
            'ArtikelkodeHersteller' => $mainArticleId,
        ];
    }

    public function addVendorCatalogEntries(int $amount, array $data)
    {
        for ($i = 0; $i < $amount; $i++) {
            // creates Entry with state "new"
            VendorCatalogEntry::factory()
                ->create([
                    'vendor_catalog_vendor_id' => $this->getVendorCatalogId(),
                    'data' => $data[$i],
                ]);
        }
    }

    protected function getSampleOmbisManufacturerData(): Collection
    {
        // sample json response from manufacturer api
        return collect([
            [
                'DisplayName' => 'Externe Marke 1',
                'ID' => 1,
                'CreationTime' => '2024-04-24T18:06:09',
                'LastUpdateTime' => '2024-04-24T18:06:20',
                'LieferantCode' => 'Hakro GmbH',
                'MarkeCode' => 'HAKRO',
                'Bezeichnung_de' => 'Hakro',
                'Bezeichnung_it' => 'Hakro',
                'Bezeichnung_en' => 'Hakro',
                'Bezeichnung_fr' => 'Hakro',
            ],
            [
                'DisplayName' => 'Externe Marke 2',
                'ID' => 1,
                'CreationTime' => '2024-04-23T18:06:09',
                'LastUpdateTime' => '2024-04-23T18:06:20',
                'LieferantCode' => 'F. Engel GmbH',
                'MarkeCode' => 'F. ENGEL',
                'Bezeichnung_de' => 'F. Engel',
                'Bezeichnung_it' => 'F. Engel',
                'Bezeichnung_en' => 'F. Engel',
                'Bezeichnung_fr' => 'F. Engel',
            ],
        ]);
    }

    public function utilityGetFirstProductVariant(): PimProduct
    {
        return PimProduct::query()
            ->whereNotNull('parent_id')
            ->with('translations')
            ->first();
    }

    public function utilityGetVendorCatalogEntries(VendorCatalogImportEntryState $state): Collection
    {
        return VendorCatalogEntry::query()
            ->where('state', $state->value)
            ->get();
    }
}
