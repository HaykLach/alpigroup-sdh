<?php

namespace App\Services\MediaLibrary;

use App\Console\Commands\PimProductImageColor\PimProductImageColorDetermine;
use App\Jobs\ProcessPimProductImageColorDetermine;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Services\Pim\PimMediaService;
use App\Services\Pim\PimPropertyGroupService;
use App\Services\Pim\PimTranslationService;
use App\Services\Pim\PropertyGroup\PimPropertyGroupStorePropertiesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Laravel\Facades\OpenAI;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OpenAiImageGetColorService
{
    protected array $config;

    protected string $instruction;

    public function __construct()
    {
        $this->config = config('openai.imageGetColor');
    }

    public function determine(Media $media, PimProduct $product): ?PimPropertyGroupOption
    {
        // use general product name
        $productName = $product->parent_id !== null ? $product->parent->name : $product->name;

        $defaultLang = (new PimTranslationService)->getDefaultLanguageCodeShort();
        $propertyGroupFilter = PimPropertyGroupService::getPropertyGroupByName($this->config['groupFilter']);

        // create prompt
        $prompt = sprintf($this->config['instructionProduct'], $productName, $defaultLang, $this->getColorMapString($propertyGroupFilter));

        // add product color text hint
        $propertyGroupText = PimPropertyGroupService::getPropertyGroupByName($this->config['groupText']);
        $productColorText = $product->custom_fields['properties'][$propertyGroupText->id] ?? null;
        if ($productColorText !== null) {
            $productColorTextHint = $this->config['instructionColorHint'];
            $prompt .= sprintf($productColorTextHint, $productColorText);
        }

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt,
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $this->getPromptDataUrl($media),
                        ],
                    ],
                ],
            ],
        ];

        $result = OpenAI::chat()->create([
            'model' => $this->config['model'],
            'messages' => $messages,
        ]);

        $color = $this->returnNullIfUnclear($result->choices[0]->message->content);
        if ($color === null) {
            return null;
        }

        $groupOption = $this->getGroupOptionByColor($propertyGroupFilter, $color);
        if ($groupOption === null) {
            return null;
        }

        return $groupOption;
    }

    protected function getPromptDataUrl(Media $media): string
    {
        $base64Image = PimMediaService::getBase64PreviewImage($media);
        $mimeType = $media->mime_type;

        return "data:$mimeType;base64,$base64Image";
    }

    protected function getGroupOptionByColor(PimPropertyGroup $group, string $color): ?PimPropertyGroupOption
    {
        return $group->groupOptions
            ->where('name', '=', $color)
            ->first();
    }

    protected function returnNullIfUnclear(string $color): ?string
    {
        // check if color begins with unclear term
        if (strpos($color, $this->config['unclearTerm']) === 0) {
            return null;
        }

        return $color;
    }

    protected function getColorMapString(PimPropertyGroup $group): string
    {
        $colorSet = $group->groupOptions->pluck('name')->toArray();

        return '"'.implode('", "', $colorSet).'"';
    }

    public function checkCustomFieldContainsOption(PimProduct $product, bool $checkifEmpty = false): bool
    {
        if (array_key_exists('openai', $product->custom_fields) &&
            array_key_exists('image-color', $product->custom_fields['openai']) &&
            array_key_exists('group', $product->custom_fields['openai']['image-color']) &&
            array_key_exists('group-option', $product->custom_fields['openai']['image-color']) &&
            array_key_exists('created_at', $product->custom_fields['openai']['image-color'])
        ) {
            if ($checkifEmpty && $product->custom_fields['openai']['image-color']['group-option'] === null) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function storeCustomFieldsData(Collection $products, ?PimPropertyGroupOption $groupOption = null): array
    {
        $data = [
            'group' => $groupOption?->propertyGroup->id,
            'group-option' => $groupOption?->id,
        ];

        $products->each(function (PimProduct $product) use ($data) {
            $customFields = $product->custom_fields;
            $customFields['openai']['image-color'] = $data;
            $customFields['openai']['image-color']['created_at'] = Carbon::now()->toDateTimeString();
            $product->custom_fields = $customFields;
            $product->save();
        });

        return $data;
    }

    public function getSimilarProducts(Media $media, PimProduct $product): Collection
    {
        if ($product->parent_id !== null) {
            $url = $media->custom_properties['source_url'];
            $products = PimProduct::whereJsonContains('images', $url)
                ->where('parent_id', '=', $product->parent_id);
        } else {
            $products = PimProduct::query()
                ->where('id', '=', $product->id);
        }

        return $products->get();
    }

    public function handleSimilarProducts(
        Collection $similarProducts,
        PimProduct $product,
        Collection $media
    ): void {
        // limit $media to 3 items
        $media = $media->take(3);
        $groupOption = null;

        foreach ($media as $k => $singleMedia) {

            try {
                $groupOption = $this->determine($singleMedia, $product);
            } catch (ErrorException $e) {
                Log::error($e->getMessage(), [
                    'product_id' => $product->id,
                    'command' => PimProductImageColorDetermine::class,
                    'attempt' => $k + 1,
                ]);

                sleep(3);
                // retry same media
                $groupOption = $this->determine($singleMedia, $product);
            }

            if ($groupOption !== null) {
                $fields = $this->storeCustomFieldsData($similarProducts, $groupOption);
                $similarProducts->each(function (PimProduct $product) use ($fields) {
                    $this->storeProperties($product, $fields['group'], $fields['group-option']);
                });

                break;
            }
        }

        if ($groupOption === null) {
            $this->storeCustomFieldsData($similarProducts);
        }
    }

    protected function storeProperties(PimProduct $product, string $groupId, string $optionId): void
    {
        $option = PimPropertyGroupService::getPropertyGroupOptionById($optionId);
        $group = PimPropertyGroup::with('groupOptions')->find($groupId);

        PimPropertyGroupStorePropertiesService::store($product, $group->groupOptions->pluck('name', 'id'), collect([$option->id]));
    }

    public function checkIfOptionIsAssigned(PimProduct $product, string $propertyGroupFilterId): bool
    {
        $productColorFilterProperties = $product->properties->filter(function ($property) use ($propertyGroupFilterId) {
            return $property->group_id === $propertyGroupFilterId;
        });

        return ! $productColorFilterProperties->isEmpty();
    }

    public function request(string $productId, string $propertyGroupFilterId, bool $forceOverwriting = false): array
    {
        $processed = [];
        $product = PimProduct::find($productId);
        if (! $forceOverwriting
            && $this->checkIfOptionIsAssigned($product, $propertyGroupFilterId)
            && $this->checkCustomFieldContainsOption($product, true)
        ) {
            return $processed;
        }

        $media = $product->getMedia(PimProduct::getMediaCollectionPreviewName());
        if ($media->isEmpty()) {
            return $processed;
        }

        $similarProducts = $this->getSimilarProducts($media->first(), $product);
        ProcessPimProductImageColorDetermine::dispatch($similarProducts, $product, $media);
        // $this->handleSimilarProducts($similarProducts, $product, $media);

        $similarProducts->each(function (PimProduct $product) use (&$processed) {
            $processed[$product->id] = $product->id;
        });

        return $processed;
    }

    public function getPropertyGroupColorFilterId(): ?string
    {
        return PimPropertyGroup::where('name', $this->config['groupFilter'])->first()->id;
    }
}
