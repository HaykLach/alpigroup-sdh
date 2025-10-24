<?php

namespace App\Filament\Resources\Pim\PimProductResource\Pages;

use App\Enums\Pim\PimMappingType;
use App\Filament\Resources\Pages\PimListRecords;
use App\Filament\Resources\Pim\PimProductResource;
use App\Models\Pim\Product\PimProduct;
use App\Services\Export\PimProductExportValidationService;
use App\Services\Pim\PimPropertyGroupService;
use Filament\Resources\Components\Tab;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListPimProducts extends PimListRecords
{
    public function getBreadcrumb(): string
    {
        return __('Liste');
    }

    public static function boot()
    {
        self::limitPaginationOptions();
    }

    protected static function limitPaginationOptions(): void
    {
        Table::configureUsing(function (Table $table) {
            $table->paginated([10, 25, 50]);
        });
    }

    protected static string $resource = PimProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*
            Actions\CreateAction::make()
                ->form(PimProductResource::getFormElements(false))
                ->using(function (array $data) {
                    PimResourceService::stripProvidedFormData($data);

                    $product = PimProduct::create($data);

                    // add translations
                    $translationService = new PimTranslationService();
                    $otherLanguages = $translationService->getExtraLanguages();

                    PimProductTranslationService::addInitialTranslations($product, $otherLanguages);

                    return $product;
                }),
            */
        ];
    }

    public function getTabs(): array
    {
        $requiredGroups = PimPropertyGroupService::getRequiredGroups(PimMappingType::PRODUCT);

        return [
            ...self::getMainVariantTabs(),
            ...self::getIncompletePropertiesTabs($requiredGroups),
            ...self::getIncompleteFieldsTabs($requiredGroups),
            ...self::getIncompletePrimaryFieldsTab(),
            ...self::getDeletedItemsTab(),
        ];
    }

    public static function getMainVariantTabs(): array
    {
        $hasVariations = PimProductResource::hasVariations();
        if ($hasVariations) {
            return [
                'main' => self::getMainTab('Main products'),

                'variants' => Tab::make()
                    ->label('Product Variants')
                    ->modifyQueryUsing(fn (Builder $query) => $query
                        ->withoutTrashed()
                        ->whereNotNull('parent_id')
                    )
                    ->badge(function () {
                        return PimProduct::query()
                            ->whereNotNull('parent_id')
                            ->withoutTrashed()
                            ->count();
                    }),
            ];
        } else {
            return [
                'main' => self::getMainTab('Products'),
            ];
        }
    }

    protected static function getMainTab(string $title): Tab
    {
        return Tab::make()
            ->label($title)
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->withoutTrashed()
                    ->whereNull('parent_id');
            })
            ->badge(function () {
                return PimProduct::query()
                    ->withoutTrashed()
                    ->whereNull('parent_id')
                    ->count();
            });
    }

    protected static function getMainDeletedTab(string $title): Tab
    {
        $countMain = PimProduct::query()
            ->onlyTrashed()
            ->whereNull('parent_id')
            ->count();

        return Tab::make()
            ->label($title)
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->onlyTrashed()
                ->whereNull('parent_id')
            )
            ->badge(function () use ($countMain) {
                return $countMain;
            });
    }

    protected static function getDeletedItemsTab(): array
    {
        $tabs = [];

        $hasVariations = PimProductResource::hasVariations();
        if ($hasVariations) {
            $tabs['deleted-main'] = self::getMainDeletedTab('deleted Main');

            $countVariants = PimProduct::query()
                ->onlyTrashed()
                ->whereNotNull('parent_id')
                ->count();

            if ($countVariants) {
                $tabs['deleted-variants'] = Tab::make()
                    ->label('deleted Variants')
                    ->modifyQueryUsing(fn (Builder $query) => $query
                        ->onlyTrashed()
                        ->whereNotNull('parent_id')
                    )
                    ->badge(function () use ($countVariants) {
                        return $countVariants;
                    });
            }
        } else {
            $tabs['deleted-main'] = self::getMainDeletedTab('Deleted');
        }

        return $tabs;
    }

    protected static function getIncompletePropertiesTabs(Collection $requiredGroups): array
    {
        $incompletePropertiesTabs = [];
        $requiredProperties = PimPropertyGroupService::filterRequiredGroupsProperties($requiredGroups);

        $requiredProperties->each(function ($group) use (&$incompletePropertiesTabs) {
            $count = self::queryMissingProductOptions(PimProduct::query(), collect([$group]))->count();
            if ($count) {
                $incompletePropertiesTabs['missing_'.$group->name] = Tab::make()
                    ->label($group->description)
                    ->badgeIcon('heroicon-m-bell')
                    ->modifyQueryUsing(function (Builder $query) use ($group) {
                        return self::queryMissingProductOptions($query, collect([$group]));
                    })
                    ->badge(function () use ($count) {
                        return $count;
                    });
            }
        });

        return $incompletePropertiesTabs;
    }

    protected static function getIncompletePrimaryFieldsTab(): array
    {
        $tabs = [];

        $fields = array_merge(PimProductExportValidationService::MANDATORY_FIELDS, PimProductExportValidationService::OPTIONAL_FIELDS);

        foreach ($fields as $fieldName => $label) {

            $count = self::queryMissingProductFields(PimProduct::query(), $fieldName)->count();
            if ($count) {
                $tabs['missing_'.$fieldName] = Tab::make()
                    ->label($label)
                    ->badgeIcon('heroicon-m-bell')
                    ->modifyQueryUsing(function (Builder $query) use ($fieldName) {
                        return self::queryMissingProductFields($query, $fieldName);
                    })
                    ->badge(function () use ($count) {
                        return $count;
                    });
            }
        }

        return $tabs;
    }

    protected static function getIncompleteFieldsTabs(Collection $requiredGroups): array
    {
        $tabs = [];

        $requiredFields = PimPropertyGroupService::filterRequiredGroupsFields($requiredGroups);
        $requiredFields->each(function ($group) use (&$tabs) {

            $fieldName = PimPropertyGroupService::getRequiredGroupsFieldname($group);

            $count = self::queryMissingProductFields(PimProduct::query(), $fieldName)->count();
            if ($count) {
                $tabs['missing_'.$group->name] = Tab::make()
                    ->label($group->description)
                    ->badgeIcon('heroicon-m-bell')
                    ->modifyQueryUsing(function (Builder $query) use ($fieldName) {
                        return self::queryMissingProductFields($query, $fieldName);
                    })
                    ->badge(function () use ($count) {
                        return $count;
                    });
            }
        });

        return $tabs;
    }

    protected static function queryMissingProductOptions(Builder $query, Collection $required): Builder
    {
        return $query
            ->withoutTrashed()
            ->whereNotNull('parent_id')
            ->orderBy('product_number')
            ->where(function ($query) use ($required) {
                $count = 0;
                $required
                    ->each(function ($property) use ($query, &$count) {
                        if ($count === 0) {
                            $query->whereDoesntHave('properties', function (Builder $query) use ($property) {
                                $query->where('group_id', $property->id);
                            });
                        } else {
                            $query->orWhereDoesntHave('properties', function (Builder $query) use ($property) {
                                $query->where('group_id', $property->id);
                            });
                        }
                        $count++;
                    });

                return $query;
            });
    }

    protected static function queryMissingProductFields(Builder $query, string $requiredField): Builder
    {
        return $query
            ->withoutTrashed()
            ->whereNotNull('parent_id')
            ->orderBy('product_number')
            ->whereNull($requiredField);
    }

    public function updatedActiveTab(): void
    {
        $this->resetTable();
    }
}
