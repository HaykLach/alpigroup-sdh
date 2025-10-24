<?php

namespace App\Filament\Services;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Enums\Pim\PimCustomerType;
use App\Enums\Pim\PimMediaPoolType;
use App\Enums\Pim\PimQuatationListingItemVisibility;
use App\Enums\Pim\PimQuatationValidityPeriodUnit;
use App\Enums\Pim\PimQuotationStatus;
use App\Enums\RoleType;
use App\Filament\Forms\Components\PdfPreviewButton;
use App\Filament\Resources\Pim\PimCustomerResource\RelationManagers\PimCustomerAddressRelationManager;
use App\Filament\Resources\Pim\PimLeadResource;
use App\Filament\Resources\Pim\PimQuotationResource;
use App\Models\Pim\PimMediaPool;
use App\Models\Pim\PimQuotation;
use App\Models\Pim\PimQuotationTemplate;
use App\Models\User;
use App\Services\Pdf\PimQuotationPdfService;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PimQuotationResourceFormService
{
    // Define a static variable for TextInput black color style with Tailwind CSS
    public static $textCalcExtraAttributes = [
        'class' => 'smart-dato_highlight-field',
    ];

    public static function getRelationManagerFormElements(bool $priceOverrideRequired = false, bool $priceOverrideTitle = true): array
    {
        $priceOverrideField = TextInput::make('price_override')
            ->label(__(self::getPriceOverrideLabel($priceOverrideTitle)))
            ->numeric()
            ->inputMode('decimal')
            ->suffix('€');

        return [
            TextInput::make('quantity')
                ->label(__('Menge'))
                ->required()
                ->minValue(1)
                ->default(1)
                ->numeric(),

            ...PimQuotationResourceFormService::getRelationManagerDiscountPercentageFormElement(),

            $priceOverrideRequired ? $priceOverrideField->required() : $priceOverrideField,

            Select::make('visibility')
                ->label(__('Sichtbarkeit'))
                ->options(PimQuatationListingItemVisibility::asSelectArray())
                ->default(PimQuatationListingItemVisibility::PUBLIC)
                ->required(),

            Textarea::make('note')
                ->label(__('Notizen')),
        ];
    }

    public static function getRelationManagerDiscountPercentageFormElement(): array
    {
        return [
            TextInput::make('discount_percentage')
                ->label(__('Rabatt in %'))
                ->required()
                ->minValue(0)
                ->maxValue(100)
                ->default(0)
                ->numeric(),
        ];
    }

    public static function getRelationManagerPositionFormElement($instance): array
    {
        return [
            Select::make('position')
                ->label(__('einfügen nach Position'))
                ->options(function () use ($instance) {
                    return $instance->ownerRecord->products->mapWithKeys(function ($product) {
                        return [$product->position => $product->position.' - '.$product->product->name];
                    });
                })
                ->required(),
        ];
    }

    public static function getPriceOverrideLabel(bool $priceOverrideTitle = true): string
    {
        $priceLabelText = $priceOverrideTitle ? ' (individuell)' : '';

        return 'Einzelpreis'.$priceLabelText;
    }

    public static function getQuotationExportFormCheckboxes(PimQuotation $quotation): array
    {
        return [
            ...PimQuotationResourceFormService::getQuotationExportProductDetailFormCheckboxes($quotation),
            ...PimQuotationResourceFormService::getQuotationExportDatasheetFormCheckboxes($quotation),
        ];
    }

    public static function getQuotationExportProductDetailFormCheckboxes(PimQuotation $quotation): array
    {
        $imageSet = PimQuotationPdfService::getImageSectionFromProducts($quotation);

        $imageCheckboxes = [];
        foreach ($imageSet as $set) {

            $images = PimQuotationResourceFormService::getMediaPath($set['images'])->toArray();

            $imageCheckboxes[] = Checkbox::make('include_images.'.$set['product']['id'])
                ->label($set['product']['identifier'].' - '.$set['product']['name'])
                ->default(true);

            /*
            $imageCheckboxes[] = GalleryPreview::make('image_preview.'.$key)
                ->hiddenLabel()
                ->viewData([
                    'name' => $set['product']['name'],
                    'images' => $set['images'],
                ]);
            */

            $imageCheckboxes[] = FileUpload::make('image_preview.'.$set['product']['id'])
                ->hiddenLabel()
                ->extraAttributes(['class' => 'custom-file-upload-for-pdf-quotation'])
                ->label(__('Fotos'))
                ->multiple()
                ->reorderable()
                ->openable()
                ->image()
                ->formatStateUsing(fn () => $images);
        }

        if (count($imageCheckboxes) === 0) {
            $imageCheckboxes = [
                Placeholder::make('missing.include_images')
                    ->label(__('Keine Fotos zum exportieren gefunden')),
            ];
        }

        return [
            Section::make(__('Angebot Produktdetails'))
                ->columns(1)
                ->schema($imageCheckboxes),
        ];
    }

    protected static function getMediaPath(array $images): Collection
    {
        return collect($images)->map(function (Media $image) {
            return str_replace(config('app.url').'/storage/', '', $image->getUrl());
        });
    }

    public static function getQuotationExportDatasheetFormCheckboxes(PimQuotation $quotation): array
    {
        $datasheets = PimQuotationResourceFormService::getDatasheetFromProducts($quotation);
        $datasheetCheckboxes = PimQuotationResourceFormService::getMediaAttachmentCheckboxes($datasheets);

        if (count($datasheetCheckboxes) === 0) {
            $datasheetCheckboxes = [
                Placeholder::make('missing.datasheets')
                    ->label(__('Keine Datasheets zum exportieren gefunden')),
            ];
        }

        return [
            Section::make(__('Angebot Datasheets'))
                ->columns(1)
                ->schema([
                    ...$datasheetCheckboxes,
                ]),
        ];
    }

    public static function getQuotationEmailAttachmentMediaPoolFormCheckboxes(): array
    {
        $mediaPool = PimQuotationResourceFormService::getDatasheetFromMediaPool();
        $mediaCheckboxes = PimQuotationResourceFormService::getMediaAttachmentCheckboxes($mediaPool, PimMediaPool::getMediaCollectionName());

        if (count($mediaCheckboxes) === 0) {
            return [];
        }

        return [
            Section::make(__('Medien Anhänge'))
                ->columns(1)
                ->schema([
                    ...$mediaCheckboxes,
                ]),
        ];
    }

    protected static function getMediaAttachmentCheckboxes(array $datasheets, string $type = 'datasheets'): array
    {
        $default = $type === 'datasheets';
        $groupedCheckboxes = self::groupDatasheetsByFolder($datasheets, $type, $default);

        return self::createTabsFromGroupedCheckboxes($groupedCheckboxes);
    }

    protected static function groupDatasheetsByFolder(array $datasheets, string $type, bool $default): array
    {
        $groupedCheckboxes = [];
        foreach ($datasheets as $datasheet) {
            $folder = $datasheet['folder'] ?? __('Dateien');
            $groupedCheckboxes[$folder][] = self::createCheckbox($datasheet, $type, $default);
            $groupedCheckboxes[$folder][] = self::createPdfPreviewButton($datasheet);
        }

        ksort($groupedCheckboxes);

        return $groupedCheckboxes;
    }

    protected static function createCheckbox(array $datasheet, string $type, bool $default): Checkbox
    {
        $title = isset($datasheet['product'])
            ? $datasheet['product']->identifier.' - '.$datasheet['product']->name
            : $datasheet['mediaPool']->name;

        $checkbox = Checkbox::make($type.'.'.$datasheet['media']->id)
            ->label($title)
            ->default($default);

        if (isset($datasheet['mediaPool']) && ! empty($datasheet['mediaPool']->description)) {
            $checkbox->helperText($datasheet['mediaPool']->description);
        }

        return $checkbox;
    }

    protected static function createPdfPreviewButton(array $datasheet): PdfPreviewButton
    {
        return PdfPreviewButton::make('datasheet_preview.'.$datasheet['media']->id)
            ->hiddenLabel()
            ->viewData([
                'datasheet' => $datasheet,
            ]);
    }

    protected static function createTabsFromGroupedCheckboxes(array $groupedCheckboxes): array
    {
        $tabs = [];
        foreach ($groupedCheckboxes as $folder => $checkboxes) {
            $tabs[] = Section::make($folder)
                ->columns(1)
                ->collapsible()
                ->collapsed()
                ->compact()
                ->schema($checkboxes);
        }

        return $tabs;
    }

    protected static function getDatasheetFromProducts(PimQuotation $quotation): array
    {
        $datasheets = [];
        $quotation->products
            ->filter(fn ($quotation_product) => $quotation_product->visibility === PimQuatationListingItemVisibility::PUBLIC->value)
            ->each(function ($quotation_product) use (&$datasheets) {
                $quotation_product->product
                    ->getMedia('datasheet')
                    ->each(function (?Media $media) use (&$datasheets, $quotation_product) {
                        if ($media !== null) {
                            $datasheets[] = [
                                'product' => $quotation_product->product,
                                'media' => $media,
                            ];
                        }

                        return null;
                    });
            })
            ->filter();

        return $datasheets;
    }

    protected static function getDatasheetFromMediaPool(): array
    {
        return PimMediaPool::query()
            ->orderBy('name')
            ->with('parent')
            ->where('type', '=', PimMediaPoolType::FILE->value)
            ->get()
            ->map(function (PimMediaPool $mediaPool) {
                $media = $mediaPool->getMedia(PimMediaPool::getMediaCollectionName())->first();
                if ($media !== null) {
                    return [
                        'media' => $media,
                        'mediaPool' => $mediaPool,
                        'folder' => $mediaPool->parent->name,
                    ];
                }

                return null;
            })
            ->filter()
            ->toArray();
    }

    public static function getFilterQuotationNumberOptions(): Collection
    {
        return PimQuotation::query()
            ->select(['id', 'quotation_number', 'created_at'])
            ->orderBy('quotation_number', 'desc')
            ->get()
            ->mapWithKeys(function (PimQuotation $quotation) {
                return [$quotation->quotation_number => $quotation->formatted_quotation_number];
            });
    }

    public static function getTableQuotationNumberSearch(string $prefix, string $field): callable
    {
        return function ($query, $search) use ($prefix, $field) {
            // filter out the prefix
            $search = str_replace($prefix, '', $search);
            $search = (int) preg_replace('/-\d{2}$/', '', $search);
            if ($search > 10000) {
                $search = $search - 10000;
            }
            if ($search <= 0) {
                $search = '';
            }
            $query->where($field, 'like', '%'.$search.'%');
        };
    }

    public static function getCommonTableColumns(string $modelName): array
    {
        return [
            TextColumn::make('validity_period')
                ->label(__('Gültigkeitsdauer'))
                ->toggleable()
                ->visible(fn () => $modelName === PimQuotation::class)
                ->alignEnd()
                ->sortable()
                ->date('d.m.Y'),

            TextColumn::make('item_count')
                ->label(__('Posten'))
                ->toggleable()
                ->getStateUsing(function ($record) {
                    return $record->products->count();
                })
                ->alignEnd(),

            TextColumn::make('discount_percentage')
                ->label(__('Rabatt in %'))
                ->toggleable()
                ->sortable()
                ->alignEnd(),

            TextColumn::make('discount_amount')
                ->label(__('Rabatt fix'))
                ->toggleable()
                ->money('EUR', locale: 'de')
                ->sortable()
                ->alignEnd(),

            TextColumn::make('shipping_cost')
                ->visible(false)
                ->label(__('Lieferkosten'))
                ->toggleable()
                ->money('EUR', locale: 'de')
                ->sortable()
                ->alignEnd(),

            TextColumn::make('total_cost')
                ->label(__('MwSt.Grundlage'))
                ->toggleable()
                ->money('EUR', locale: 'de')
                ->sortable()
                ->alignEnd(),

            TextColumn::make('total_cost_with_tax')
                ->label(__('Gesamtbetrag'))
                ->toggleable()
                ->money('EUR', locale: 'de')
                ->sortable()
                ->visible(fn () => $modelName === PimQuotation::class)
                ->alignEnd(),

            TextColumn::make('internal_comment')
                ->label(__('Kommentar (intern)'))
                ->toggleable()
                ->searchable(isIndividual: true)
                ->sortable(),

            TextColumn::make('created_at')
                ->label(__('Erstellt am'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable()
                ->alignEnd()
                ->date('d.m.Y'),
        ];
    }

    public static function getCommonTableFilters(string $modelName): array
    {
        return [
            DateRangeFilter::make('created_at')
                ->label(__('Erstellt am')),

            Filter::make('total_cost_range')
                ->columns(2)
                ->form([
                    TextInput::make('min')
                        ->label('MwSt.Grundlage min')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€')
                        ->placeholder('100'),
                    TextInput::make('max')
                        ->label('MwSt.Grundlage max')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€')
                        ->placeholder('20000'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['min'], fn ($q, $min) => $q->where('total_cost', '>=', $min))
                        ->when($data['max'], fn ($q, $max) => $q->where('total_cost', '<=', $max));
                }),

            Filter::make('total_cost_with_tax_range')
                ->columns(2)
                ->visible(fn () => $modelName === PimQuotation::class)
                ->form([
                    TextInput::make('min')
                        ->label('Gesamtbetrag min')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€')
                        ->placeholder('100'),
                    TextInput::make('max')
                        ->label('Gesamtbetrag max')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€')
                        ->placeholder('20000'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['min'], fn ($q, $min) => $q->where('total_cost_with_tax', '>=', $min))
                        ->when($data['max'], fn ($q, $max) => $q->where('total_cost_with_tax', '<=', $max));
                }),
        ];
    }

    public static function getFormValidityPeriod(): array
    {
        return [
            TextInput::make('validity_period_value')
                ->label(__('Gültigkeitsdauer Wert'))
                ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                ->numeric()
                ->required()
                ->default(2)
                ->maxValue(100)
                ->live()
                ->afterStateUpdated(fn (Get $get, callable $set) => PimQuotationResource::updateValidityPeriod($get, $set)),

            Select::make('validity_period_unit')
                ->label(__('Gültigkeitsdauer Einheit'))
                ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                ->required()
                ->options(PimQuatationValidityPeriodUnit::toArray())
                ->default(PimQuatationValidityPeriodUnit::MONTH)
                ->live()
                ->afterStateUpdated(fn (Get $get, callable $set) => PimQuotationResource::updateValidityPeriod($get, $set)),
        ];
    }

    public static function getFormAdditional(): array
    {
        return [
            Textarea::make('internal_comment')
                ->label(__('Kommentar (intern)'))
                ->nullable()
                ->rows(5),
        ];
    }

    public static function getFormCalculation(int $columns = 6): array
    {
        return [
            Section::make(__('Kalkulation'))
                ->hiddenOn('create')
                ->collapsible()
                ->schema([

                    TextInput::make('products_count')
                        ->label(__('Posten Anzahl'))
                        ->extraAttributes(self::$textCalcExtraAttributes)
                        ->afterStateHydrated(function ($record, Set $set) {
                            $productsCount = $record?->products->count() ?? 0;
                            $set('products_count', $productsCount);
                        })
                        ->disabled()
                        ->readonly(),

                    TextInput::make('calc_items_cost')
                        ->label(__('Posten Gesamtpreis'))
                        ->extraAttributes(self::$textCalcExtraAttributes)
                        ->afterStateHydrated(function ($record, Set $set, Get $get) {
                            if ($record) {
                                $priceKey = PimQuotationResourceService::getPriceKeyByGet($get);
                                $itemsCost = PimQuotationResourceService::calcItemsCost($record, $priceKey);
                                $set('calc_items_cost', PimQuotationResourceService::formatMoney($itemsCost));
                            }
                        })
                        ->suffix('€')
                        ->disabled()
                        ->readonly(),

                    TextInput::make('discount_percentage')
                        ->label(__('Rabatt in %'))
                        ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                        ->required()
                        ->default(0.0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->live()
                        ->afterStateUpdated(PimQuotationResourceService::getCalcTotalLiveFnc())
                        ->numeric(),

                    TextInput::make('discount_amount')
                        ->label(__('Rabatt fix'))
                        ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                        ->required()
                        ->default(0.0)
                        ->minValue(0)
                        ->suffix('€')
                        ->live()
                        ->afterStateUpdated(PimQuotationResourceService::getCalcTotalLiveFnc())
                        ->numeric(),

                    TextInput::make('shipping_cost')
                        ->visible(false)
                        ->label(__('Lieferkosten'))
                        ->required()
                        ->default(0.0)
                        ->minValue(0)
                        ->suffix('€')
                        ->live()
                        ->afterStateUpdated(PimQuotationResourceService::getCalcTotalLiveFnc())
                        ->numeric(),

                    TextInput::make('total_cost')
                        ->label(__('MwSt.Grundlage'))
                        ->extraAttributes(self::$textCalcExtraAttributes)
                        ->afterStateHydrated(PimQuotationResourceService::getCalcTotalLiveFnc())
                        ->suffix('€')
                        ->readonly(),

                    TextInput::make('total_cost_with_tax')
                        ->label(__('Gesamtbetrag'))
                        ->extraAttributes(self::$textCalcExtraAttributes)
                        ->visible(fn ($record) => $record instanceof PimQuotation)
                        ->afterStateHydrated(PimQuotationResourceService::getCalcTotalLiveFnc())
                        ->suffix('€')
                        ->readonly(),
                ])->columns($columns),

        ];
    }

    public static function getFormTextContent(bool $hiddenOnCreate = false): array
    {
        // Set default values for text fields
        $data = [
            'introductionText' => '',
            'deliveryTime' => '',
            'transport' => '',
            'installation' => '',
            'warranty' => '',
            'paymentTerms' => '',
            'generalInformation' => '',
        ];

        $section = Section::make(__('Texte'))
            ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
            ->collapsible()
            ->schema([
                TextArea::make('content.introductionText')
                    ->label(__('Einleitungstext'))
                    ->default($data['introductionText'])
                    ->rows(7)
                    ->nullable(),

                TextArea::make('content.deliveryTime')
                    ->label(__('Lieferzeit'))
                    ->default($data['deliveryTime'])
                    ->rows(7)
                    ->nullable(),

                TextArea::make('content.transport')
                    ->label(__('Transport'))
                    ->default($data['transport'])
                    ->rows(7)
                    ->nullable(),

                TextArea::make('content.installation')
                    ->label(__('Montage'))
                    ->default($data['installation'])
                    ->rows(7)
                    ->nullable(),

                TextArea::make('content.warranty')
                    ->label(__('Garantie'))
                    ->default($data['warranty'])
                    ->rows(7)
                    ->nullable(),

                TextArea::make('content.paymentTerms')
                    ->label(__('Zahlung'))
                    ->default($data['paymentTerms'])
                    ->rows(7)
                    ->nullable(),

                TextArea::make('content.generalInformation')
                    ->label(__('Sonstige Informationen'))
                    ->default($data['generalInformation'])
                    ->rows(7)
                    ->nullable(),

                TextArea::make('content.additionalText')
                    ->label(__('Weiterer Text'))
                    ->default('')
                    ->rows(7)
                    ->nullable(),

            ])->columns(4);

        if ($hiddenOnCreate) {
            $section->hiddenOn('create');
        }

        return [
            $section,
        ];
    }

    public static function eventUpdateQuotation($instance)
    {
        $data = $instance->form->getState();
        $data['products_count'] = $instance->record->products->count();

        $priceKey = PimQuotationResourceService::getPriceKeyByQuotation($instance->record);

        $shippingCost = $data['shipping_cost'] ?? 0;
        $shippingCost = $shippingCost ? PimQuotationResourceService::replaceComma($data['shipping_cost']) : 0;
        $discountAmount = PimQuotationResourceService::replaceComma($data['discount_amount']);
        $discountPercentage = PimQuotationResourceService::replaceComma($data['discount_percentage']);

        $data['total_cost'] = PimQuotationResourceService::calcTotal(
            quotation: $instance->record,
            priceKey: $priceKey,
            shippingCost: $shippingCost,
            discount_amount: $discountAmount,
            discount_percentage: $discountPercentage,
        );

        $data['total_cost_with_tax'] = null;
        if ($instance->record->tax?->tax_rate) {
            $data['total_cost_with_tax'] = PimQuotationResourceService::calcTotalWithAppliedTax(
                taxRate: $instance->record->tax->tax_rate,
                total: $data['total_cost']
            );
        }

        $itemsCost = PimQuotationResourceService::calcItemsCost($instance->record, $priceKey);
        $data['calc_items_cost'] = PimQuotationResourceService::formatMoney($itemsCost);

        $instance->form->fill($data);

        $instance->record->update([
            'total_cost' => $data['total_cost'],
            'total_cost_with_tax' => $data['total_cost_with_tax'],
        ]);

        Notification::make()
            ->success()
            ->title(__('Angebot aktualisiert'))
            ->body(__('Das Angebot wurde erfolgreich aktualisiert.'))
            ->send();
    }

    public static function resetProductPositions(Collection $products): void
    {
        $products->sortBy([
            ['position', 'asc'],
            ['updated_at', 'asc'],
        ])
            ->values()
            ->each(function ($product, $index) {
                if ($product->position !== $index + 1) {
                    $product->update([
                        'position' => $index + 1,
                    ]);
                }
            });
    }

    public static function getDisabledFormIfNotDraftFnc(): callable
    {
        return function ($record) {
            if ($record === null || $record instanceof PimQuotationTemplate) {
                return false;
            }

            return $record->status !== PimQuotationStatus::DRAFT;
        };
    }

    public static function getDisabledFormClientFnc(): callable
    {
        return function (Get $get, $record) {
            if ($record === null) {
                return empty($get('agents'));
            }

            return empty($get('agents')) || $record->version > 1 || $record->status !== PimQuotationStatus::DRAFT;
        };
    }

    public static function getTable(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->heading(__('Angebote'))
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(100)
            ->searchable(false)
            ->searchOnBlur()
            ->groups([
                Group::make('quotation_number')
                    ->label(__('Nummer')),
            ])
            ->columns([

                TextColumn::make('updated_at')
                    ->label(__('Aktualisiert am'))
                    ->toggleable()
                    ->sortable()
                    ->alignEnd()
                    ->date('d.m.Y'),

                TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn (PimQuotationStatus $state): string => $state->getLabel())
                    ->colors([
                        'gray' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::DRAFT,
                        'info' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::SENT,
                        'success' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::ACCEPTED,
                        'danger' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::DECLINED,
                        'warning' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::EXPIRED,
                    ]),

                TextColumn::make('formatted_quotation_number')
                    ->label(__('Nr.'))
                    ->searchable(query: PimQuotationResourceFormService::getTableQuotationNumberSearch(PimQuotation::QUOTATION_NUMBER_PREFIX, 'quotation_number'), isIndividual: true)
                    ->sortable([
                        'quotation_number',
                    ])
                    ->toggleable()
                    ->alignEnd(),

                TextColumn::make('version')
                    ->label(__('Version'))
                    ->sortable()
                    ->toggleable()
                    ->alignCenter(),

                TextColumn::make('agent')
                    ->hidden($user->hasRole(RoleType::AGENT->value))
                    ->toggleable()
                    ->label(__('Vertrieb'))
                    ->getStateUsing(function ($record) {
                        return $record->agents->first()->summarized_title ?? null;
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('agents', function (Builder $subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                    }, isIndividual: true),

                TextColumn::make('customer')
                    ->label(__('Kunde'))
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->customers->first()->summarized_title ?? null;
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('customers', function (Builder $subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('custom_fields->'.PimCustomerCustomFields::COMPANY_NAME->value, 'like', '%'.$search.'%');
                        });
                    }, isIndividual: true),

                TextColumn::make('lead')
                    ->formatStateUsing(function ($record) {
                        if ($record->lead !== null) {
                            return Action::make('view')
                                ->label(function () use ($record) {
                                    return __('Lead').' '.$record->lead->number;
                                })
                                ->url(function () use ($record) {
                                    return PimLeadResource::getUrl('edit', ['record' => $record->lead->id]);
                                })
                                ->icon('heroicon-o-arrow-right')
                                ->color('primary')
                                ->button()
                                ->render();
                        }

                        return null;
                    })
                    ->html(),

                TextColumn::make('date')
                    ->label(__('Datum'))
                    ->toggleable()
                    ->alignEnd()
                    ->sortable()
                    ->date('d.m.Y'),

                TextColumn::make('tax')
                    ->label(__('Steuersatz'))
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->tax->tax_rate.'%' ?? null;
                    })
                    ->alignEnd(),

                ...PimQuotationResourceFormService::getCommonTableColumns(PimQuotation::class),

                IconColumn::make('sent_to_customer')
                    ->label(__('Angebot versendet'))
                    ->toggleable()
                    ->boolean()
                    ->falseColor('text-gray-400')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return $record->sent_to_customer ? true : false;
                    }),

            ])
            ->filters([
                TernaryFilter::make('sent_to_customer')
                    ->label(__('Angebot versendet'))
                    ->trueLabel(__('Versendet'))
                    ->falseLabel(__('Nicht versendet'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('sent_to_customer'),
                        false: fn (Builder $query) => $query->whereNull('sent_to_customer'),
                    ),

                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(PimQuotationStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()]))
                    ->placeholder(__('Alle'))
                    ->searchable()
                    ->multiple()
                    ->preload(),

                // TrashedFilter::make('trashed')->label(__('Gelöschte')),

                SelectFilter::make('quotation_number')
                    ->label(__('Angebotsnummer'))
                    ->placeholder(__('Nach Angebotsnummer suchen'))
                    ->options(PimQuotationResourceFormService::getFilterQuotationNumberOptions())
                    ->searchable()
                    ->preload(),

                ...PimQuotationResourceFormService::getCommonTableFilters(PimQuotation::class),

            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::ExtraLarge);
    }

    public static function queryPerAgent(Builder $query, $user): Builder
    {
        if ($user->hasRole(RoleType::AGENT->value)) {
            $query->byAgentId($user->agent->id);
        }

        return $query;
    }

    public static function getAgentsSelect(User $user, ?int $colspan = null, bool $required = false, bool $live = false): array
    {
        $select = Select::make('agents')
            ->label(__('Vertrieb'))
            ->live()
            ->options(fn () => $user->hasRole('agent')
                ? PimResourceCustomerService::getSelectOptionsMapping($user->agent)
                : PimResourceCustomerService::getSelectOptions(PimCustomerType::AGENT))
            ->saveRelationshipsUsing(fn (PimQuotation $record, $state) => $record->agents()->sync([$state]))
            ->afterStateHydrated(function ($record, Set $set) {
                if ($record?->agents->isNotEmpty()) {
                    $set('agents', $record->agents->first()->id);
                } else {
                    $agentId = request()->get('agent');
                    if ($agentId) {
                        $set('agents', $agentId);
                    }
                }
            })
            ->default(fn () => $user->hasRole('agent') ? $user->agent->id : null)
            ->disabled(fn ($record) => $record?->agents->isNotEmpty())
            ->preload()
            ->searchable(fn () => ! $user->hasRole('agent'))
            ->selectablePlaceholder(fn () => ! $user->hasRole('agent'))
            ->placeholder(__('Vertrieb auswählen'));

        if ($colspan) {
            $select->columnSpan($colspan);
        }

        if ($required) {
            $select->required();
        }

        if ($live) {
            $select->live();
        }

        return [
            $select,
        ];
    }

    public static function getCustomersSelect(): array
    {
        return [
            Select::make('customers')
                ->label(__('Kunde'))
                ->required()
                ->live()
                ->disabled(PimQuotationResourceFormService::getDisabledFormClientFnc())
                ->options(function (Get $get) {
                    return PimQuotationResource::getSelectCustomerOptions($get('agents'));
                })
                ->getSearchResultsUsing(function (string $search, Get $get) {
                    return PimQuotationResource::getSelectCustomerOptions($get('agents'), $search);
                })
                ->saveRelationshipsUsing(function (PimQuotation $record, $state) {
                    $record->customers()->sync([$state]);
                })
                ->afterStateHydrated(function ($record, Set $set) {
                    if ($record && $record->customers->count() > 0) {
                        $set('customers', $record->customers->first()->id ?? null);
                    }

                    $customerId = request()->get('customer');
                    if ($customerId) {
                        $set('customers', $customerId);
                    }
                })
                ->afterStateUpdated(function (Component $livewire, ?string $state, $record) {
                    if ($record && $state !== null) {
                        $record->customers()->sync([$state]);
                        $record->save();

                        $livewire->dispatch('updateQuotation');
                        $livewire->dispatch('reloadProductsRelationManager');
                    }
                })
                ->preload()
                ->searchable()
                ->suffixAction(PimQuotationResourceFormService::getCustomersSelectSuffixAction())
                ->placeholder(__('Kunde auswählen')),
        ];
    }

    public static function getCustomersSelectSuffixAction(string $agentPath = 'agents', string $customerPath = 'customers'): callable
    {
        return function ($record) use ($agentPath, $customerPath) {
            if ($record === null || $record->status === PimQuotationStatus::DRAFT) {
                return FormAction::make('createNewCustomer')
                    ->label(__('Neuer Kunde'))
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading(__('Neuer Kunde'))
                    ->modalWidth('lg')
                    ->form([
                        ...PimResourceCustomerService::getFormElementsSectionDetails(),
                        Checkbox::make('address')
                            ->label(__('Adresse hinzufügen'))
                            ->default(true)
                            ->live(),
                        Section::make('Address Information')
                            ->schema([
                                TextInput::make('phone_number')
                                    ->label(__('Telefon')),
                                ...PimCustomerAddressRelationManager::getFormElements(),
                            ])
                            ->hidden(fn (Get $get): bool => ! $get('address')),
                    ])
                    ->action(function ($data, $livewire, Set $set, Get $get) use ($agentPath, $customerPath) {
                        $customer = PimResourceCustomerService::createCrmCustomer($data, $get($agentPath));

                        $set($customerPath, $customer->id);

                        $livewire->dispatch('updateQuotation');

                        Notification::make()->success()->title(__('Kunde hinzugefügt: '.$customer->getSummarizedTitleAttribute()))->send();
                    });
            }

            return null;
        };
    }
}
