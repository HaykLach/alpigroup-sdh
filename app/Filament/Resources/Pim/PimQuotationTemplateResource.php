<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\Pim;
use App\Filament\Resources\Pim\PimQuotationResource\RelationManagers\PimProductInventoryRelationManager;
use App\Filament\Resources\Pim\PimQuotationResource\RelationManagers\PimProductRelationManager;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Models\Pim\PimQuotationTemplate;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PimQuotationTemplateResource extends Resource
{
    protected static ?string $model = PimQuotationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = 'Quotation Template';

    protected static ?string $modelLabel = 'Quotation';

    public static function getNavigationLabel(): string
    {
        return __('Angebote Vorlagen');
    }

    public static function getModelLabel(): string
    {
        return __('Angebot Vorlage');
    }

    public static function getLabel(): string
    {
        return __('Angebot Vorlage');
    }

    public static function getPluralLabel(): string
    {
        return __('Angebote Vorlagen');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) PimQuotationTemplate::query()
            ->withoutTrashed()
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(function ($record) {
                    return ! $record ? __('Neue Vorlage') : __('Vorlage').' '.$record->formatted_quotation_number;
                })
                    ->collapsible()
                    ->schema([

                        ...PimQuotationResourceFormService::getFormValidityPeriod(),
                        ...PimQuotationResourceFormService::getFormAdditional(),

                    ])->columns(2),

                ...PimQuotationResourceFormService::getFormTextContent(),
                ...PimQuotationResourceFormService::getFormCalculation(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(100)
            ->searchable(false)
            ->searchOnBlur()
            ->columns([

                TextColumn::make('formatted_quotation_number')
                    ->label(__('Nr.'))
                    ->searchable(query: PimQuotationResourceFormService::getTableQuotationNumberSearch(PimQuotationTemplate::QUOTATION_NUMBER_PREFIX, 'quotation_template_number'), isIndividual: true)
                    ->sortable([
                        'quotation_template_number',
                    ])
                    ->alignEnd(),

                ...PimQuotationResourceFormService::getCommonTableColumns(PimQuotationTemplate::class),

                TextColumn::make('updated_at')
                    ->label(__('Aktualisiert am'))
                    ->toggleable()
                    ->sortable()
                    ->alignEnd()
                    ->date('d.m.Y'),

            ])
            ->filters([

                ...PimQuotationResourceFormService::getCommonTableFilters(PimQuotationTemplate::class),

            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::ExtraLarge)
            ->actions([
                EditAction::make()
                    ->label(__('bearbeiten')),
                DeleteAction::make()
                    ->label(__('entfernen')),
                RestoreAction::make()
                    ->label(__('wiederherstellen')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('entfernen')),
                    RestoreBulkAction::make()
                        ->label(__('wiederherstellen')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(__('Posten'), [
                PimProductRelationManager::class,
            ]),
            RelationGroup::make(__('Produktauswahl'), [
                PimProductInventoryRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pim\PimQuotationTemplateResource\Pages\ListPimQuotationTemplates::route('/'),
            'create' => Pim\PimQuotationTemplateResource\Pages\CreatePimQuotationTemplate::route('/create'),
            'edit' => Pim\PimQuotationTemplateResource\Pages\EditPimQuotationTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'products',
                'products.product.media',
            ]);
    }
}
