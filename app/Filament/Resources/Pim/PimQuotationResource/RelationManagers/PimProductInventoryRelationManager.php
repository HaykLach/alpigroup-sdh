<?php

namespace App\Filament\Resources\Pim\PimQuotationResource\RelationManagers;

use App\Enums\Pim\PimMappingType;
use App\Enums\Pim\PimQuotationStatus;
use App\Filament\Resources\Pages\Traits\CanResizeColumnsPerUser;
use App\Filament\Resources\Pim\PimProductResource;
use App\Models\Pim\PimQuotationTemplate;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\QuotationProduct;
use App\Services\Pim\PimGenerateIdService;
use App\Services\Pim\PimProductService;
use App\Services\Pim\PimPropertyGroupService;
use App\Services\Pim\PimTranslationService;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;

class PimProductInventoryRelationManager extends RelationManager
{
    use CanResizeColumnsPerUser;

    protected static string $relationship = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Inventory';

    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Produktauswahl');
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $canEdit = $user->can('update', PimProduct::class);

        return $table
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->query(PimProduct::query()
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
                ]))
            ->recordTitleAttribute('summarized_title')
            ->searchable(false)
            ->searchOnBlur()
            ->defaultSort('product_number')
            ->columns([
                ...$this->getTableColumns(),
                ...PimProductService::getTablePriceColumns($user),
                ...[
                    Tables\Columns\IconColumn::make('exists')
                        ->label(__('im Sortiment'))
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-exclamation-triangle')
                        ->trueColor('success')
                        ->falseColor('danger')
                        ->getStateUsing(fn (PimProduct $record) => $record->deleted_at === null),
                ],
            ])
            ->filters([

            ])
            ->headerActions([

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('bearbeiten'))
                    ->form(PimProductResource::getFormElements(true, false))
                    ->using(fn (PimProduct $record, array $data) => PimProductService::update($record, $data))
                    ->visible($canEdit),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('add_products')
                    ->label(__('hinzufügen'))
                    ->after(fn (Component $livewire) => $livewire->dispatch('updateQuotation'))
                    ->action(function (Collection $records) {
                        $added = 0;
                        $records->each(function (PimProduct $record) use (&$added) {

                            $product = PimProduct::find($record->id);
                            if ($this->ownerRecord->products->contains(function ($quotationProduct) use ($product) {
                                return $quotationProduct->product_type === PimProduct::class && $quotationProduct->product_id === $product->id;
                            })) {
                                Notification::make()
                                    ->warning()
                                    ->title(__('Produkt wurde bereits hinzugefügt'))
                                    ->body(__('Das Produkt befindet sich bereits in der Liste. '.$product->summarized_title))
                                    ->send();

                                return;
                            }

                            QuotationProduct::createForQuotation($this->ownerRecord->id, $product->id, PimProduct::class);

                            $this->ownerRecord->refresh();

                            $added++;
                        });

                        if ($added > 0) {
                            Notification::make()
                                ->title($added.' '.__('Posten').' '.__('hinzugefügt'))
                                ->success()
                                ->send();
                        }

                        $this->deselectAllTableRecords();
                    }),
            ]);
    }

    protected function getTableColumns(): array
    {
        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $otherLanguagesShort = $translationService->getExtraLanguagesShort();

        $inlineEdit = false;

        return [
            Tables\Columns\TextColumn::make('id')
                ->label('Product Id')
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('product_number')
                ->label('Product Nr.')
                ->toggleable()
                ->searchable(isIndividual: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('identifier')
                ->label('EAN Code')
                ->searchable(isIndividual: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('name')
                ->label('Name'.' ('.$defaultLangCode.')')
                ->weight(FontWeight::Bold)
                ->searchable(isIndividual: true)
                ->sortable(),

            ...PimTranslationService::getTranslatedColumn($otherLanguagesShort, 'name', 'Name', $inlineEdit),

            Tables\Columns\TextColumn::make('description')
                ->label('Description'.' ('.$defaultLangCode.')')
                ->words(20)
                ->toggleable()
                ->searchable(isIndividual: true)
                ->wrap()
                ->sortable(),

            ...PimTranslationService::getTranslatedColumn($otherLanguagesShort, 'description', 'Description', $inlineEdit, 6),

            ImageColumn::make('fileupload.'.PimGenerateIdService::getPropertyGroupId('Preview Image'))
                ->label('Fotos')
                ->getStateUsing(function ($record) {
                    return $record->getMedia('preview-image')
                        ->sortByDesc('order_column')
                        ->map(fn ($media) => $media->getUrl())
                        ->toArray();
                })
                ->limit(3)
                ->limitedRemainingText(),

            ...PimPropertyGroupService::getTableColumns(PimMappingType::PRODUCT, $inlineEdit, $this->getGroups(['Datasheet'])),

            ...PimPropertyGroupService::getTableColumns(PimMappingType::PRODUCT, $inlineEdit, $this->getGroups(['ERP Id', 'Herstellerkode', 'Modell'])),

            Tables\Columns\TextColumn::make('deleted_at')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
        ];
    }

    protected function getGroups(array $fields): Collection
    {
        return PimPropertyGroup::with(['groupOptions' => fn ($query) => $query->orderBy('position')])
            ->whereIn('description', $fields)
            ->get();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord instanceof PimQuotationTemplate) {
            return true;
        }

        return $ownerRecord->status === PimQuotationStatus::DRAFT;
    }
}
