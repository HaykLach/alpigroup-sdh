<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimFormSection;
use App\Enums\Pim\PimMappingType;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Pim\PimProductBulkActionService;
use App\Services\Pim\PimProductResourceService;
use App\Services\Pim\PimProductService;
use App\Services\Pim\PimPropertyGroupService;
use App\Services\Pim\PimResourceService;
use App\Services\Pim\PimTranslationService;
use Closure;
use Exception;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PimProductResource extends Resource
{
    protected static ?string $model = PimProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?string $navigationLabel = 'Product';

    protected static ?int $navigationSort = 80;

    protected static int $paginationCount = 10;

    public static function getNavigationLabel(): string
    {
        return __('Produkte');
    }

    public static function getModelLabel(): string
    {
        return __('Produkt');
    }

    public static function getLabel(): string
    {
        return __('Produkt');
    }

    public static function getPluralLabel(): string
    {
        return __('Produkte');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PimProduct::withoutTrashed()->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * @throws Exception
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormElements())
            ->columns(1);
    }

    /**
     * @throws Exception
     */
    public static function getFormElements(?bool $showTranslatableFields = true, ?bool $showImagesTab = true): array
    {
        $groups = PimPropertyGroupService::getGroups(PimMappingType::PRODUCT);

        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $extraLangIdCodes = $showTranslatableFields ? $translationService->getExtraLanguagesShort() : collect();

        $hasManufacturers = PimProductResource::hasManufacturers();
        $hasTaxes = PimProductResource::hasTaxes();

        return [
            Tabs::make('Product')
                ->tabs([
                    Tabs\Tab::make('Information')
                        ->schema([
                            ...self::getManufacturerInputForm($hasManufacturers),

                            self::getTextInputForm('name', 'Name ('.$defaultLangCode.')'),

                            self::getTextareaInputForm('description', 'Description ('.$defaultLangCode.')'),

                            // ...PimTranslationService::getTranslateActionsNextToField('description', $defaultLangCode, $extraLangIdCodes),

                            ...PimTranslationService::getFormArray([
                                self::getTextInputForm('name', 'Name'),
                                PimTranslationService::getTranslateActionsWithinRepeater('name', $defaultLangCode, $extraLangIdCodes),

                                self::getTextareaInputForm('description', 'Description', 8),
                                PimTranslationService::getTranslateActionsWithinRepeater('description', $defaultLangCode, $extraLangIdCodes),

                            ], $extraLangIdCodes
                            ),

                            ...PimPropertyGroupService::getForms(PimMappingType::PRODUCT, $groups, $extraLangIdCodes, $defaultLangCode, PimFormSection::MAIN),

                        ]),

                    Tabs\Tab::make('Specification')
                        ->schema([
                            ...PimPropertyGroupService::getForms(PimMappingType::PRODUCT, $groups, $extraLangIdCodes, $defaultLangCode, PimFormSection::SPECIFICATION),
                        ]),

                    Tabs\Tab::make('Pricing')
                        ->schema([
                            ...PimPropertyGroupService::getForms(PimMappingType::PRODUCT, $groups, $extraLangIdCodes, $defaultLangCode, PimFormSection::PRICING),

                            ...self::getTaxInputForm($hasTaxes),
                        ]),

                    Tabs\Tab::make('Availability')
                        ->schema([
                            ...PimPropertyGroupService::getForms(PimMappingType::PRODUCT, $groups, $extraLangIdCodes, $defaultLangCode, PimFormSection::AVAILABILITY),
                            TextInput::make('stock')->numeric(),
                            Toggle::make('active')
                                ->label('Active')
                                ->default(true),
                        ]),

                    Tabs\Tab::make('Identity')
                        ->schema([
                            ...PimPropertyGroupService::getForms(PimMappingType::PRODUCT, $groups, $extraLangIdCodes, $defaultLangCode, PimFormSection::IDENTITY),

                            TextInput::make('identifier')
                                ->label('EAN Code')
                                ->nullable()
                                ->readOnly()
                                ->unique(ignoreRecord: true),

                            TextInput::make('product_number')
                                ->label('Product Nr.')
                                ->required()
                                ->readOnly()
                                ->unique(ignoreRecord: true),
                        ]),

                    Tabs\Tab::make('Images')
                        ->visible($showImagesTab)
                        ->schema([
                            Placeholder::make('images')
                                ->label('Images')
                                ->content(function ($record): HtmlString {
                                    $images = collect($record->images ?? [])
                                        ->map(fn ($images) => "<img src=\"{$images}\" alt=\"\">")
                                        ->implode('');

                                    return new HtmlString($images);
                                }),
                        ]),
                ]),
            /*
            Select::make('categories')
                ->multiple()
                ->relationship(titleAttribute: 'name'),
            */
        ];
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $extraLangIdCodes = $translationService->getExtraLanguagesShort();

        $hasManufacturers = PimProductResource::hasManufacturers();
        $hasVariations = PimProductResource::hasVariations();

        $livewire = $table->getLivewire();

        $user = auth()->user();

        if ($hasVariations) {
            $table = $table
                ->defaultGroup('parent.name')
                ->groups([
                    'parent.name',
                    'manufacturer.name',
                ]);
        }

        return $table
            ->defaultSort('product_number')
            ->searchable(false)
            ->searchOnBlur()
            ->defaultPaginationPageOption(self::$paginationCount)
            ->headerActions([
                PimResourceService::getTableHeaderActionToggleEditInline($livewire),
            ])
            ->columns([
                ...PimProductResourceService::getTableColumns(),
                //...PimProductService::getTablePriceColumns($user),
            ])
            // ->persistSortInSession()
            // ->persistSearchInSession()
            // ->persistColumnSearchesInSession()
            // ->persistFiltersInSession()
            ->filters([
                /*
                TernaryFilter::make('active')
                    ->label('Active / Inactive')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->queries(
                        true: fn (Builder $query) => $query->where('active', true),
                        false: fn (Builder $query) => $query->where('active', false),
                        blank: fn (Builder $query) => $query
                    ),
                */

                SelectFilter::make('parent_id')
                    ->label('Main Product Nr.')
                    ->options(fn () => PimProduct::whereNull('parent_id')
                        ->get()
                        ->pluck('product_number', 'id')
                        ->toArray()
                    )
                    ->visible(PimProductResourceService::colVisibleWhenNotMainOrDeletedProduct())
                    ->searchable(),

                SelectFilter::make('product_number')
                    ->label('Product Nr.')
                    ->options(fn () => PimProduct::whereNull('parent_id')
                        ->get()
                        ->pluck('product_number', 'product_number')
                        ->toArray()
                    )
                    ->visible(PimProductResourceService::colVisibleWhenMainProduct())
                    ->searchable(),

                SelectFilter::make('manufacturer_id')
                    ->label('Manufacturer')
                    ->relationship(name: 'manufacturer', titleAttribute: 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                ...PimPropertyGroupService::getFilters(PimMappingType::PRODUCT),

                // Tables\Filters\TrashedFilter::make(),
                DateRangeFilter::make('created_at'),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(fn (PimProduct $record, array $data) => PimProductService::update($record, $data))
                    ->label(__('bearbeiten')),
            ])
            ->bulkActions(self::getBulkActions($defaultLangCode, $extraLangIdCodes, $hasManufacturers));
    }

    protected static function getBulkActions($defaultLangCode, $extraLangIdCodes, bool $hasManufacturers): array
    {
        return [
            BulkActionGroup::make([

                BulkAction::make('bulkAction.name')
                    ->label('Edit Name')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        self::getTextInputForm('name', 'Name'),
                    ])
                    ->action(self::getBulkActionAction()),

                BulkAction::make('bulkAction.description')
                    ->label('Edit Description')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        self::getTextareaInputForm('description', 'Description'),
                    ])
                    ->action(self::getBulkActionAction()),

                // @todo add main product to bulk action

                BulkAction::make('bulkAction.manufacturer')
                    ->label('Edit Manufacturer')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        self::getManufacturerInputForm($hasManufacturers),
                    ])
                    ->action(self::getBulkActionAction()),

                ...PimProductBulkActionService::getBulkActions(PimMappingType::PRODUCT, $extraLangIdCodes, $defaultLangCode),

                ...$extraLangIdCodes->map(function ($langIdCode, $langId) {
                    return BulkAction::make('bulkAction.name'.$langId)
                        ->label('Edit ('.$langIdCode.') Name')
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            self::getTextInputForm('name', 'Name ('.$langIdCode.')'),
                        ])
                        ->action(self::getBulkActionLangAction($langId));
                })->toArray(),

                ...$extraLangIdCodes->map(function ($langIdCode, $langId) {
                    return BulkAction::make('bulkAction.description'.$langId)
                        ->label('Edit ('.$langIdCode.') Description')
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            self::getTextareaInputForm('description', 'Edit ('.$langIdCode.') Description'),
                        ])
                        ->action(self::getBulkActionLangAction($langId));
                })->toArray(),

                Tables\Actions\DeleteBulkAction::make()->visible(fn ($livewire) => in_array($livewire->activeTab, ['main', 'variants'])),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()->visible(fn ($livewire) => in_array($livewire->activeTab, ['deleted-main', 'deleted-variants'])),
            ]),

            ...PimTranslationService::getBulkTranslationButtons(),

            BulkAction::make('set_active')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->color('success')
                ->visible(PimProductResourceService::colVisibleWhenNotMainOrDeletedProduct())
                ->action(fn (Collection $records) => $records->each->update(['active' => true])),

            BulkAction::make('set_inactive')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->color('gray')
                ->visible(PimProductResourceService::colVisibleWhenNotMainOrDeletedProduct())
                ->action(fn (Collection $records) => $records->each->update(['active' => false])),
        ];
    }

    private static function getBulkActionAction(): Closure
    {
        return function (Collection $records, array $data) {
            $records->each(function (PimProduct $record) use ($data) {
                PimProductService::update($record, $data);
            });
        };
    }

    private static function getBulkActionLangAction(string $langId): Closure
    {
        return function (Collection $records, array $data) use ($langId) {
            PimProductBulkActionService::saveTranslationRelationship($records, $data, $langId);
        };
    }

    private static function getManufacturerInputForm(bool $hasManufacturers): array
    {
        if (! $hasManufacturers) {
            return [];
        }

        return [
            Select::make('pim_manufacturer_id')
                ->label('Manufacturer')
                ->relationship(name: 'manufacturer', titleAttribute: 'name')
                ->searchable(['name'])
                ->options(PimProductManufacturer::all()
                    ->pluck('name', 'id')
                    ->toArray())
                ->preload(),
        ];
    }

    private static function getTaxInputForm(bool $hasTaxes): array
    {
        if (! $hasTaxes) {
            return [];
        }

        return [
            Select::make('pim_tax_id')
                ->relationship(name: 'tax', titleAttribute: 'name')
                ->preload(),
        ];
    }

    private static function getTextInputForm(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->required();
    }

    private static function getTextareaInputForm(string $field, string $label, int $rows = 16): Textarea
    {
        return Textarea::make($field)
            ->label($label)
            ->rows($rows);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => PimProductResource\Pages\ListPimProducts::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'media',
                'translations',
                'translations.media',
                'properties',
                'options',
                'parent',
                'variations',
                'tax',
                'manufacturer',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function hasManufacturers(): bool
    {
        return PimProductManufacturer::count() > 0;
    }

    public static function hasTaxes(): bool
    {
        return PimTax::count() > 0;
    }

    public static function hasVariations(): bool
    {
        return PimProduct::query()->whereNotNull('parent_id')->count() > 0;
    }

    public static function hasActiveStateSet(): bool
    {
        return PimProduct::query()->whereNotNull('active')->count() > 0;
    }

    public static function getActiveTableCol(bool $inlineEdit = false): array
    {
        $hasActiveStateSet = PimProductResource::hasActiveStateSet();
        if ($hasActiveStateSet) {
            return [$inlineEdit
                ?
                ToggleColumn::make('active')
                    ->label('Active')
                    ->updateStateUsing(fn ($state, $record) => PimProductService::update($record, ['active' => $state]))
                    ->visible(PimProductResourceService::colVisibleWhenNotMainOrDeletedProduct())
                    ->sortable()
                :
                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->visible(PimProductResourceService::colVisibleWhenNotMainOrDeletedProduct())
                    ->boolean()
                    ->sortable(),
            ];
        } else {
            return [];
        }
    }
}
