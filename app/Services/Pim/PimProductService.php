<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimProductPriceListTypes;
use App\Enums\RoleType;
use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductTranslation;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\User;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PimProductService
{
    public static function update(PimProduct $record, array $data): void
    {
        PimResourceService::stripProvidedFormData($data);

        if ($record->isMainProduct) {
            self::updateVariants($record, $data);

            return;
        }

        $record->update($data);
    }

    protected static function stripMainToVariantNotTransferableFields(&$variantData): void
    {
        unset($variantData['identifier'], $variantData['product_number'], $variantData['name'], $variantData['custom_fields']);
    }

    public static function updateVariants(PimProduct $record, array $data): void
    {
        $variantData = $data;
        self::stripMainToVariantNotTransferableFields($variantData);

        $customFields = self::getCustomFieldsArray($data);
        unset($data['custom_fields']);

        $variants = self::queryProductVariants($record['id'])->get();
        $variants->each(fn (PimProduct $variant) => self::fill($variant, $customFields, $variantData));

        PimMediaService::sync($record, $variants);

        self::fill($record, $customFields, $data);
    }

    public static function getCustomFieldsArray($data): array
    {
        // check custom_field data
        // only fill custom fields where value is submitted
        $customFields = [];
        if (isset($data['custom_fields'])) {
            foreach ($data['custom_fields']['properties'] as $groupID => $value) {
                if (empty($value)) {
                    continue;
                }
                $customFields['custom_fields->properties->'.$groupID] = $value;

            }
        }

        return $customFields;
    }

    private static function fill(PimProduct $variant, array $customFields, array $variantData): void
    {
        if (! empty($customFields)) {
            $variant->forceFill($customFields)->save();
        }

        $variant->update($variantData);
    }

    public static function queryProductVariants(string $parentId): Builder
    {
        return PimProduct::query()
            ->where('parent_id', $parentId);
    }

    public static function getVisibilityFnc(PimPropertyGroup $group): Closure
    {
        return function (PimProduct|PimProductTranslation|null $record) use ($group) {

            if ($record instanceof PimProductTranslation) {
                // get the main product
                $record = PimProduct::find($record->product_id);
            }
            // return true when new record is edited
            if (! $record) {
                return true;
            }

            // main products without variants are editable like variants
            if ($record->isMainProduct) {
                if ($record->variations->isEmpty()) {
                    return true;
                } else {
                    $key = 'main';
                }

            } else {
                $key = 'variant';
            }

            return $group->custom_fields['edit'][$key];
        };
    }

    public static function getColumnsSortableQuery(PimPropertyGroup $group, Builder $query, string $direction): Builder
    {
        $tablePropertyGroupOption = (new PimPropertyGroupOption)->getTable();
        $tablePimProduct = (new PimProduct)->getTable();
        $tableProductProperties = 'product_properties';

        return $query
            ->select($tablePimProduct.'.*', DB::raw('MAX('.$tablePropertyGroupOption.'.name) as max_name'))
            ->leftJoin($tableProductProperties, $tableProductProperties.'.product_id', '=', $tablePimProduct.'.id')
            ->leftJoin($tablePropertyGroupOption, function ($join) use ($tablePropertyGroupOption, $tableProductProperties, $group) {
                $join->on($tablePropertyGroupOption.'.id', '=', $tableProductProperties.'.option_id')
                    ->where($tablePropertyGroupOption.'.group_id', '=', $group->id);
            })
            ->groupBy($tablePimProduct.'.id')
            ->orderBy('max_name', $direction);
    }

    public static function getProductMainIds(): Collection
    {
        return PimProduct::query()
            ->select('id')
            /*
            ->whereIn('id', [
                //'5ebd8f38-deb9-55d1-9fb4-3af209a7b218',
                //'42a39bec-cdd1-5937-b3ca-2ce3a03d568a',
                //'d6a5fa2f-6625-585d-a098-977849bb1c55', // error
                //'ecba267a-3f4a-513d-af66-0952ffdcda4d', // no prices and color
                //'9808e56a-40e0-5504-b6a2-fe5062ecbb17', // no prices and color
                //'e84cf9d2-bf46-518e-a582-e1794f5a3acf', // no prices and color
                //'442d8dd6-32cb-566a-be11-23090d27f974', // no prices and color
                // '819ffb1d-0f8b-5443-b8f9-834f5d8f73f4',
                '0bfd2d56-0d07-5e28-acce-f48260a29e4a',
            ])
            */
            ->whereNull('parent_id')
            ->withTrashed()
            // ->limit(100)
            // ->skip(30)
            ->orderBy('pim_manufacturer_id')
            ->orderBy('product_number')
            ->pluck('id');
    }

    public static function getMainProductWithVariants(string $pimProductId): ?PimProduct
    {
        return PimProduct::where('id', $pimProductId)
            ->with(
                [
                    'manufacturer',
                    'translations',
                    'properties',
                    'properties.translations',
                    'properties.propertyGroup',
                    'properties.propertyGroup.translations',
                    'options',
                    'variations',
                    'variations.manufacturer',
                    'variations.translations',
                    'variations.properties',
                    'variations.properties.translations',
                    'variations.properties.propertyGroup',
                    'variations.properties.propertyGroup.translations',
                    'variations.options',
                    'variations.variations',
                    'media',
                    'translations.media',
                    /*
                    'variations.media',
                    'variations.translations.media',
                    */
                ]
            )
            ->first();
    }

    public static function getProductsHavingImagesProductIds(): Builder
    {
        // get all product variants and main products without variants
        return PimProduct::query()
            ->select('id')
            ->orderBy('product_number')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('parent_id')
                        ->whereNotNull('images');
                })
                    ->orWhere(function ($query) {
                        $query->doesntHave('variations')
                            ->whereNull('parent_id')
                            ->whereNotNull('images');
                    });
            });
    }

    public static function getProductVariationIdsMissingPropertyByGroupId(string $groupId): Builder
    {
        // get all product variants and main products without variants where filter color is not set
        return self::getProductsHavingImagesProductIds()
            ->whereDoesntHave('properties', function ($query) use ($groupId) {
                $query->where('group_id', $groupId);
            });
    }

    public static function getTablePriceColumns(User $user): array
    {
        if ($user->hasRole(RoleType::AGENT->value)) {
            return [
                TextColumn::make('prices.'.PimProductService::getPriceTableType($user)->value)
                    ->label(__('Preis'))
                    ->money('EUR', locale: 'de')
                    ->alignEnd()
                    ->toggleable(),
            ];
        }

        return [
            TextColumn::make('prices.'.PimProductPriceListTypes::DEFAULT->value)
                ->label(__('Preis '.PimProductPriceListTypes::DEFAULT->value))
                ->money('EUR', locale: 'de')
                ->alignEnd()
                ->toggleable(),

            TextColumn::make('prices.'.PimProductPriceListTypes::AT->value)
                ->label(__('Preis '.PimProductPriceListTypes::AT->value))
                ->money('EUR', locale: 'de')
                ->alignEnd()
                ->toggleable(),
        ];

    }

    public static function getPriceTableType(User $user): PimProductPriceListTypes
    {
        $countryId = $user->agentMainAddress()?->country_id;
        if ($countryId === null) {
            return PimProductPriceListTypes::DEFAULT;
        }

        $country = PimCountry::find($countryId);
        if ($country && $country->iso === 'AT') {
            return PimProductPriceListTypes::AT;
        }

        return PimProductPriceListTypes::DEFAULT;
    }
}
