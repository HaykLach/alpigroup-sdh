<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimMappingType;
use App\Filament\Resources\Pim\PimProductResource;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Session\SessionService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\ViewColumn;

class PimProductResourceService
{
    public static function getTableColumns(): array
    {
        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $otherLanguagesShort = $translationService->getExtraLanguagesShort();

        $inlineEdit = SessionService::getConfigUserTableEditInline();
        $hasManufacturers = PimProductResource::hasManufacturers();
        $hasVariations = PimProductResource::hasVariations();
        $hasTaxes = PimProductResource::hasTaxes();

        return [
            Tables\Columns\TextColumn::make('id')
                ->label('Product Id')
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),

            ViewColumn::make('variations')
                ->label('Variations')
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: ! $hasVariations)
                ->visible(self::colVisibleWhenMainProduct())
                ->view('filament.tables.columns.variations-button'),

            Tables\Columns\ImageColumn::make('thumbnails')
                ->label('Images')
                ->getStateUsing(fn (PimProduct $record) => PimResourceService::getProductThumbnails($record))
                ->limit(5)
                ->toggleable()
                ->visible(fn () => PimProduct::whereNotNull('images')->whereJsonLength('images', '>', 0)->exists())
                ->limitedRemainingText(isSeparate: true),

            Tables\Columns\TextColumn::make('parent.product_number')
                ->label('Main Product Nr.')
                ->toggleable()
                ->searchable(isIndividual: true)
                ->visible(self::colVisibleWhenNotMainOrDeletedProduct(true))
                ->view('filament.tables.columns.main-button')
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

            ...PimProductResourceService::getManufacturerCol($hasManufacturers, $inlineEdit),

            ...[$inlineEdit
                ?
                Tables\Columns\TextInputColumn::make('name')
                    ->label('Name'.' ('.$defaultLangCode.')')
                    ->getStateUsing(fn ($record) => $record->name)
                    ->updateStateUsing(fn ($state, $record) => PimProductService::update($record, ['name' => $state]))
                    ->searchable(isIndividual: true)
                    ->sortable()
                    ->extraAttributes(['style' => 'min-width: 400px;'])
                :
                Tables\Columns\TextColumn::make('name')
                    ->label('Name'.' ('.$defaultLangCode.')')
                    ->weight(FontWeight::Bold)
                    ->searchable(isIndividual: true)
                    ->sortable(),
            ],

            ...PimTranslationService::getTranslatedColumn($otherLanguagesShort, 'name', 'Name', $inlineEdit),

            Tables\Columns\TextColumn::make('description')
                ->label('Description'.' ('.$defaultLangCode.')')
                ->toggleable()
                ->searchable(isIndividual: true)
                ->sortable(),

            ...PimTranslationService::getTranslatedColumn($otherLanguagesShort, 'description', 'Description', $inlineEdit, 6),

            ...PimPropertyGroupService::getTableColumns(PimMappingType::PRODUCT, $inlineEdit),

            ...[$inlineEdit
                ?
                Tables\Columns\TextInputColumn::make('stock')
                    ->label('Stock')
                    ->getStateUsing(fn ($record) => $record->stock)
                    ->updateStateUsing(fn ($state, $record) => PimProductService::update($record, ['stock' => $state]))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->extraAttributes(['style' => 'min-width: 460px;'])
                :
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->visible(self::colVisibleWhenNotMainOrDeletedProduct())
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ],

            ...self::getTaxCol($hasTaxes, $inlineEdit),

            ...PimProductResource::getActiveTableCol($inlineEdit),

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
            // TextColumn::make('amount_including_vat')
            //    ->state(function (Order $record): float {
            //        return $record->amount * (1 + $record->vat_rate);
            //    })
            /*
            Tables\Columns\TextColumn::make('categories.name')->sortable()->label('Category Name'),
            */
        ];
    }

    public static function getManufacturerCol(bool $hasManufacturers, bool $inlineEdit = false): array
    {
        if (! $hasManufacturers) {
            return [];
        }

        return [
            $inlineEdit
                ?
                Tables\Columns\SelectColumn::make('pim_manufacturer_id')
                    ->label('Manufacturer selection')
                    ->getStateUsing(fn ($record) => $record->manufacturer->id)
                    ->options(fn () => PimProductManufacturer::all()->pluck('name', 'id')->toArray())
                    ->updateStateUsing(fn ($state, $record) => PimProductService::update($record, ['pim_manufacturer_id' => $state]))
                    ->toggleable()
                    ->selectablePlaceholder(false)
                    ->sortable()
                :
                Tables\Columns\TextColumn::make('manufacturer.name')
                    ->label('Manufacturer')
                    ->sortable()
                    ->toggleable(),
        ];
    }

    public static function getTaxCol(bool $hasTaxes, bool $inlineEdit = false): array
    {
        if (! $hasTaxes) {
            return [];
        }

        return [
            $inlineEdit
                ?
                Tables\Columns\SelectColumn::make('pim_tax_id')
                    ->label('Tax selection')
                    ->getStateUsing(fn ($record) => $record->tax->id ?? null)
                    ->options(fn () => PimTax::all()->pluck('name', 'id')->toArray())
                    ->updateStateUsing(fn ($state, $record) => PimProductService::update($record, ['pim_tax_id' => $state]))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->selectablePlaceholder(false)
                    ->sortable()
                :
                Tables\Columns\TextColumn::make('tax.name')
                    ->label('Tax')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
        ];
    }

    public static function colVisibleWhenNotMainOrDeletedProduct(bool $checkVariations = false): callable
    {
        return fn ($livewire) => $livewire
            && (! $checkVariations || PimProductResource::hasVariations())
            && ! in_array($livewire->activeTab, ['main', 'deleted-variants', 'deleted-main']);
    }

    public static function colVisibleWhenMainProduct(): callable
    {
        return fn ($livewire) => $livewire && $livewire->activeTab === 'main';
    }
}
