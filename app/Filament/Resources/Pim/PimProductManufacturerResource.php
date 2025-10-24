<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimMappingType;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Pim\PimProductManufacturerService;
use App\Services\Pim\PimPropertyGroupService;
use App\Services\Pim\PimResourceService;
use App\Services\Pim\PimTranslationService;
use App\Services\Session\SessionService;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PimProductManufacturerResource extends Resource
{
    protected static ?string $model = PimProductManufacturer::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?string $navigationLabel = 'Manufacturer';

    protected static ?int $navigationSort = 70;

    public static function getNavigationLabel(): string
    {
        return __('Hersteller');
    }

    public static function getModelLabel(): string
    {
        return __('Hersteller');
    }

    public static function getLabel(): string
    {
        return __('Hersteller');
    }

    public static function getPluralLabel(): string
    {
        return __('Hersteller');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormElements())
            ->columns(1);
    }

    /**
     * @throws Exception
     */
    public static function getFormElements(?bool $showTranslatableFields = true)
    {
        $groups = PimPropertyGroupService::getGroups(PimMappingType::MANUFACTURER);

        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $extraLangIdCodes = $showTranslatableFields ? $translationService->getExtraLanguagesShort() : collect();

        return [

            TextInput::make('name')
                ->label('Name')
                ->required()
                ->unique(ignoreRecord: true),

            ...PimTranslationService::getFormArray([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                PimTranslationService::getTranslateActionsWithinRepeater('name', $defaultLangCode, $extraLangIdCodes),
            ], $extraLangIdCodes
            ),

            ...PimPropertyGroupService::getForms(PimMappingType::MANUFACTURER, $groups, $extraLangIdCodes, $defaultLangCode),
        ];
    }

    public static function table(Table $table): Table
    {
        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $otherLanguagesShort = $translationService->getExtraLanguagesShort();

        $inlineEdit = SessionService::getConfigUserTableEditInline();

        $livewire = $table->getLivewire();

        return $table
            ->headerActions([
                PimResourceService::getTableHeaderActionToggleEditInline($livewire),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Manufacturer Id')
                    ->toggleable(isToggledHiddenByDefault: true),

                ...[$inlineEdit
                    ?
                    Tables\Columns\TextInputColumn::make('name')
                        ->label('Name'.' ('.$defaultLangCode.')')
                        ->getStateUsing(fn ($record) => $record->name)
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable()
                        ->extraAttributes(['style' => 'min-width: 460px;'])
                    :
                    Tables\Columns\TextColumn::make('name')
                        ->label('Name'.' ('.$defaultLangCode.')')
                        ->weight(FontWeight::Bold)
                        ->searchable(isIndividual: true)
                        ->sortable(),
                ],

                ...PimTranslationService::getTranslatedColumn($otherLanguagesShort, 'name', 'Name', $inlineEdit),

                ...PimPropertyGroupService::getTableColumns(PimMappingType::MANUFACTURER, $inlineEdit),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label(__('erstellen')),
                ...PimPropertyGroupService::getFilters(PimMappingType::MANUFACTURER),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('bearbeiten'))
                    ->using(fn (PimProductManufacturer $record, array $data) => PimProductManufacturerService::update($record, $data)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label(__('entfernen')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('unwiederruflich löschen')),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('wiederherstellen')),
                ...PimTranslationService::getBulkTranslationButtons(),
            ]);
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
            'index' => PimProductManufacturerResource\Pages\ListPimProductManufacturers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'translations.media',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
