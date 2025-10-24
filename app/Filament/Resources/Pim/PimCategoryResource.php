<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Models\Pim\PimCategory;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PimCategoryResource extends Resource
{
    protected static ?string $model = PimCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?string $navigationLabel = 'Category';

    protected static ?int $navigationSort = 80;

    public static function getNavigationLabel(): string
    {
        return __('Kategorien');
    }

    public static function getModelLabel(): string
    {
        return __('Kategorie');
    }

    public static function getLabel(): string
    {
        return __('Kategorie');
    }

    public static function getPluralLabel(): string
    {
        return __('Kateogrien');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('anzeigen')),
                Tables\Actions\EditAction::make()
                    ->label(__('bearbeiten')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label(__('entfernen')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('unwiederruflich löschen')),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('wiederherstellen')),
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
            'index' => PimCategoryResource\Pages\ListPimCategories::route('/'),
            'create' => PimCategoryResource\Pages\CreatePimCategory::route('/create'),
            'view' => PimCategoryResource\Pages\ViewPimCategory::route('/{record}'),
            'edit' => PimCategoryResource\Pages\EditPimCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
