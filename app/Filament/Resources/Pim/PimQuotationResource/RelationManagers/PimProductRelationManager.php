<?php

namespace App\Filament\Resources\Pim\PimQuotationResource\RelationManagers;

use App\Enums\Pim\PimQuatationListingItemVisibility;
use App\Enums\Pim\PimQuotationStatus;
use App\Filament\Resources\Pages\Traits\CanResizeColumnsPerUser;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Filament\Services\PimQuotationResourceService;
use App\Models\Pim\PimQuotationCustomProduct;
use App\Models\Pim\PimQuotationTemplate;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\QuotationProduct;
use App\Services\Pim\PimGenerateIdService;
use App\Services\Pim\PimTranslationService;
use App\Tables\Columns\FileuploadQuotationProductOpenFileColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class PimProductRelationManager extends RelationManager
{
    use CanResizeColumnsPerUser;

    protected static string $relationship = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Products';

    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Posten Produkte');
    }

    #[On('reloadProductsRelationManager')]
    public function reloadProductsRelationManager(): void
    {
        $this->dispatch('$refresh');
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            TextInput::make('product.name')
                ->label(__('Titel'))
                ->visible(fn ($record) => $record->product_type === PimQuotationCustomProduct::class)
                ->formatStateUsing(function ($record) {
                    return $record->product->name;
                })
                ->required(),

            Textarea::make('product.description')
                ->visible(fn ($record) => $record->product_type === PimQuotationCustomProduct::class)
                ->formatStateUsing(function ($record) {
                    return $record->product->description;
                })
                ->label(__('Beschreibung')),

            ...PimQuotationResourceFormService::getRelationManagerFormElements(),
        ]);
    }

    public function table(Table $table): Table
    {
        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $otherLanguagesShort = $translationService->getExtraLanguagesShort();

        $wordLimit = 16;

        $priceKey = PimQuotationResourceService::getPriceKeyByQuotation($this->ownerRecord);

        return $table
            ->heading(__('Posten Produkte'))
            ->paginated([10, 25, 50, 'all'])
            ->defaultPaginationPageOption('all')
            ->recordTitleAttribute('summarized_title')
            ->searchable(false)
            ->searchOnBlur()
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label(__('Position'))
                    ->toggleable()
                    ->sortable()
                    ->alignEnd(),

                IconColumn::make('visibility')
                    ->label(__('Sichtbarkeit'))
                    ->toggleable()
                    ->sortable()
                    ->icon(fn ($record) => $record->visibility === PimQuatationListingItemVisibility::PUBLIC->value ? 'heroicon-c-eye' : 'heroicon-c-eye-slash'),

                Tables\Columns\TextColumn::make('product.identifier')
                    ->label('EAN Code')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Name'.' ('.$defaultLangCode.')')
                    ->weight(FontWeight::Bold)
                    ->toggleable(),

                ...PimProductRelationManager::getTranslatedColumn($otherLanguagesShort, 'name', 'Name'),

                Tables\Columns\TextColumn::make('product.description')
                    ->label('Beschreibung'.' ('.$defaultLangCode.')')
                    ->toggleable()
                    ->words($wordLimit)
                    ->extraAttributes(['style' => 'width: 320px;'])
                    ->wrap(),

                ...PimProductRelationManager::getTranslatedColumn($otherLanguagesShort, 'description', 'Beschreibung', $wordLimit),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('Menge'))
                    ->toggleable()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('quantity_entity')
                    ->label(__('M.E.'))
                    ->toggleable()
                    ->getStateUsing(fn () => __(' Stk.'))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label(__('Rabatt in %'))
                    ->toggleable()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('price_override')
                    ->label(__(PimQuotationResourceFormService::getPriceOverrideLabel()))
                    ->toggleable()
                    ->alignEnd()
                    ->money('EUR', locale: 'de')
                    ->sortable(),

                Tables\Columns\TextColumn::make('calculation.prices.single')
                    ->label(__('Einzelpreis'))
                    ->toggleable()
                    ->getStateUsing(function ($record) use ($priceKey) {
                        if ($record->product instanceof PimProduct) {
                            return PimQuotationResourceService::getProductItemPrice($record->product, $priceKey);
                        }

                        return null;
                    })
                    ->money('eur', locale: 'de')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('calculation.prices.sum')
                    ->label(__('Preis Summe'))
                    ->toggleable()
                    ->getStateUsing(function ($record) use ($priceKey) {
                        return PimQuotationResourceService::calculateProductRow($record, $priceKey);
                    })
                    ->money('eur', locale: 'de')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('product.product_number')
                    ->label('Product Nr.')
                    ->toggleable(),

                ImageColumn::make('fileupload.'.PimGenerateIdService::getPropertyGroupId('Preview Image'))
                    ->label('Fotos')
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        // a product was deleted
                        if ($record->product === null) {
                            return '';
                        }
                        // PimQuotationCustomProduct has currently no media
                        if ($record->product_type === PimQuotationCustomProduct::class) {
                            return '';
                        }

                        return $record->product->getMedia('preview-image')
                            ->sortByDesc('order_column')
                            ->map(fn ($media) => $media->getUrl())
                            ->toArray();
                    })
                    ->limit(3)
                    ->limitedRemainingText(),

                FileuploadQuotationProductOpenFileColumn::make('fileupload.'.PimGenerateIdService::getPropertyGroupId('Datasheet'))
                    ->label('Datasheet')
                    ->toggleable()
                    ->setCollection('datasheet'),

                Tables\Columns\TextColumn::make('note')
                    ->label(__('Notizen'))
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('exists')
                    ->label(__('im Sortiment'))
                    ->toggleable()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(function ($record) {
                        if ($record->product instanceof PimProduct) {
                            return $record->product->deleted_at === null;
                        }

                        return true;
                    }),
            ])
            ->filters([

            ])
            ->headerActions([
                Action::make('add_custom_product')
                    ->after(fn (Component $livewire) => $livewire->dispatch('updateQuotationAndProductsPosition'))
                    ->label(__('individuellen Posten hinzufügen'))
                    ->action(function (array $data) {
                        // store new custom product
                        $product = PimQuotationCustomProduct::create([
                            'name' => $data['product']['name'],
                            'description' => $data['product']['description'],
                        ]);

                        unset($data['product']);

                        QuotationProduct::createForQuotation($this->ownerRecord->id, $product->id, PimQuotationCustomProduct::class, $data);

                        $this->ownerRecord->refresh();
                    })
                    ->form([
                        TextInput::make('product.name')
                            ->label(__('Titel'))
                            ->required(),

                        Textarea::make('product.description')
                            ->label(__('Beschreibung')),

                        ...PimQuotationResourceFormService::getRelationManagerFormElements(),

                        ...PimQuotationResourceFormService::getRelationManagerPositionFormElement($this),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('bearbeiten'))
                    ->modalHeading(function ($record) {
                        return __('Posten bearbeiten').': '.$record->product->name;
                    })
                    ->after(fn (Component $livewire) => $livewire->dispatch('updateQuotation'))
                    ->using(function ($record, array $data): Model {

                        if (isset($data['product'])) {
                            $record->product->update($data['product']);
                        }

                        unset($data['product']);
                        $record->update($data);

                        return $record;
                    })
                    ->visible(fn ($record) => $record->product !== null),
                DeleteAction::make()
                    ->label(__('entfernen'))
                    ->after(fn (Component $livewire) => $livewire->dispatch('updateQuotationAndProductsPosition'))
                    ->visible(fn ($record) => $record->product !== null),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label(__('entfernen'))
                    ->after(fn (Component $livewire) => $livewire->dispatch('updateQuotationAndProductsPosition')),
            ]);
    }

    protected static function getTranslatedColumn(Collection $otherLanguages, string $field, string $label, ?int $wordLimit = null): array
    {
        $components = [];

        $otherLanguages->map(function ($langCode, $langId) use (&$components, $field, $label, $wordLimit) {

            $col = TextColumn::make($langCode.'_'.$field)
                ->label($label.' ('.$langCode.')')
                ->toggleable(isToggledHiddenByDefault: true)
                ->getStateUsing(function ($record) use ($langId, $field) {
                    // PimQuotationCustomProduct has currently no translations
                    if ($record->product_type === PimQuotationCustomProduct::class) {
                        return '';
                    }

                    return $record->product->translations()
                        ->where('language_id', $langId)
                        ->first()
                        ->$field;
                });

            if ($wordLimit) {
                $col->words($wordLimit)
                    ->extraAttributes(['style' => 'width: 320px;'])
                    ->wrap();
            }

            $components[] = $col;
        });

        return $components;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord instanceof PimQuotationTemplate) {
            return true;
        }

        return $ownerRecord->status === PimQuotationStatus::DRAFT;
    }
}
