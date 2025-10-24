<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Models\Pim\Cache\PimCacheTranslation;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PimCacheTranslationResource extends Resource
{
    protected static ?string $model = PimCacheTranslation::class;

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?string $navigationLabel = 'Translations';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 400;

    public static function getNavigationLabel(): string
    {
        return __('Übersetzungen API');
    }

    public static function getModelLabel(): string
    {
        return __('Übersetzung');
    }

    public static function getLabel(): string
    {
        return __('Übersetzung');
    }

    public static function getPluralLabel(): string
    {
        return __('Übersetzungen');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('class')
                    ->readOnly()
                    ->label('Entity')
                    ->required(),

                TextInput::make('provider')
                    ->readOnly()
                    ->label('Provider')
                    ->required(),

                TextInput::make('from_lang')
                    ->readOnly()
                    ->label('From language')
                    ->required(),

                TextInput::make('to_lang')
                    ->readOnly()
                    ->label('To language')
                    ->required(),

                Textarea::make('input')
                    ->readOnly()
                    ->rows(10)
                    ->label('Original input text')
                    ->required(),

                Textarea::make('translation')
                    ->label('Translated text')
                    ->rows(10)
                    ->required(),

                Toggle::make('successful')
                    ->label('Valid')
                    ->default(true),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                Tables\Columns\IconColumn::make('successful')
                    ->label('Valid')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('class')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->label('Entity'),

                Tables\Columns\TextColumn::make('provider')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->label('Provider'),

                Tables\Columns\TextColumn::make('from_lang')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->label('From Lang'),

                Tables\Columns\TextColumn::make('to_lang')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->label('To Lang'),

                Tables\Columns\TextColumn::make('input')
                    ->sortable()
                    ->label('Input')
                    ->words(10)
                    ->searchable(isIndividual: true),

                Tables\Columns\TextColumn::make('translation')
                    ->label('Translation')
                    ->weight(FontWeight::Bold)
                    ->searchable(isIndividual: true)
                    ->words(10)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('bearbeiten')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('entfernen')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->searchOnBlur()
            ->searchable(false);
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
            'index' => PimCacheTranslationsResource\Pages\ListPimCacheTranslation::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
