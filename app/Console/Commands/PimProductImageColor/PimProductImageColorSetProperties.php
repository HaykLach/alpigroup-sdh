<?php

namespace App\Console\Commands\PimProductImageColor;

use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Services\MediaLibrary\OpenAiImageGetColorService;
use App\Services\Pim\PimProductService;
use App\Services\Pim\PimPropertyGroupService;
use App\Services\Pim\PropertyGroup\PimPropertyGroupStorePropertiesService;
use Illuminate\Console\Command;

class PimProductImageColorSetProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:image-color-set-properties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'OpenAi Image Color - set color stored in PimProduct custom fields';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $openaiImageGetColorService = new OpenAiImageGetColorService;
        $config = config('openai.imageGetColor');

        $setAll = false;
        if ($setAll) {
            $productIds = PimProductService::getProductsHavingImagesProductIds()->pluck('id');
        } else {
            $propertyGroupFilterId = PimPropertyGroup::where('name', $config['groupFilter'])->first()->id;
            $productIds = PimProductService::getProductVariationIdsMissingPropertyByGroupId($propertyGroupFilterId)
                ->pluck('id');
        }

        if ($productIds->isEmpty()) {
            return self::SUCCESS;
        }

        $results = [
            'solved' => [],
            'unresolved' => [],
            'no_custom_fields' => [],
        ];

        $skip = [];

        $propertyGroup = PimPropertyGroupService::getPropertyGroupByName($config['groupFilter']);
        $propertyGroupText = PimPropertyGroupService::getPropertyGroupByName($config['groupText']);

        $productIds->each(function ($productId) use ($propertyGroup, $propertyGroupText, $openaiImageGetColorService, &$results, &$skip) {

            if (isset($skip[$productId])) {
                return;
            }

            $product = PimProduct::find($productId);

            // get current option from product where group_id = $propertyGroup->id
            $currentOption = $product->properties()->where('group_id', $propertyGroup->id)->first();
            $currentOptionName = $currentOption ? $currentOption->name : null;

            $productColorText = $product->custom_fields['properties'][$propertyGroupText->id] ?? null;

            // get similar products
            $products = $openaiImageGetColorService->getSimilarProducts($product->media->first(), $product);

            // skip similar products
            $products->each(function ($product) use (&$skip) {
                $skip[$product->id] = $product->id.' '.$product->name;
            });

            if (! $openaiImageGetColorService->checkCustomFieldContainsOption($product)) {
                $results['no_custom_fields'][] = $this->getSolvableEntry($product, $productColorText, $currentOptionName);

                return;
            }

            // check for unresolvable image color
            if (! $openaiImageGetColorService->checkCustomFieldContainsOption($product, true)) {
                $results['unresolved'][] = $this->getSolvableEntry($product, $productColorText, $currentOptionName);

                return;
            }

            $optionId = $product['custom_fields']['openai']['image-color']['group-option'];
            $option = PimPropertyGroupService::getPropertyGroupOptionById($optionId);

            $results['solved'][] = $this->getSolvableEntry($product, $productColorText, $currentOptionName, $option);

            $products->each(function ($product) {
                $this->setPropertyGroup($product);
            });
        });

        $this->exportCSV($results);

        return self::SUCCESS;
    }

    protected function exportCSV(array $results): void
    {
        $filename = 'image-color-set-properties-'.date('Y-m-d-H-i-s').'.csv';
        $file = fopen(storage_path('app/'.$filename), 'w');

        // Combine results with type column
        $combinedResults = [];
        foreach ($results as $type => $items) {
            foreach ($items as $item) {
                $item['type'] = $type;
                $combinedResults[] = $item;
            }
        }

        // Write CSV headers
        fputcsv($file, array_keys($combinedResults[0]));

        // Write data rows
        foreach ($combinedResults as $result) {
            fputcsv($file, $result);
        }

        fclose($file);
    }

    protected function getSolvableEntry(PimProduct $product, ?string $productColorText = null, ?string $currentOptionName = null, ?PimPropertyGroupOption $option = null): array
    {
        return [
            'Pim id' => $product->id,
            'EANCode' => $product->identifier,
            'product_number' => $product->product_number,
            'url' => count($product->images) ? $product->images[0] : null,
            'name' => $product->name,
            'existing-color-text' => $productColorText,
            'existing-color-filter' => $currentOptionName,
            'openai-determined-color-filter' => $option?->name,
        ];
    }

    protected function setPropertyGroup(PimProduct $product): void
    {
        $optionId = $product['custom_fields']['openai']['image-color']['group-option'];
        $groupId = $product['custom_fields']['openai']['image-color']['group'];

        $option = PimPropertyGroupService::getPropertyGroupOptionById($optionId);
        $group = PimPropertyGroup::with('groupOptions')->find($groupId);

        PimPropertyGroupStorePropertiesService::store($product, $group->groupOptions->pluck('name', 'id'), collect([$option->id]));
    }
}
