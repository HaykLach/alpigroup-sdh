<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Models\Pim\PimMedia;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PimMediaResource extends Resource
{
    protected static ?string $model = PimMedia::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?string $navigationLabel = 'Media';

    protected static ?int $navigationSort = 101;

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
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
            'index' => PimMediaResource\Pages\ListPimMedia::route('/'),
            'create' => PimMediaResource\Pages\CreatePimMedia::route('/create'),
            'view' => PimMediaResource\Pages\ViewPimMedia::route('/{record}'),
            'edit' => PimMediaResource\Pages\EditPimMedia::route('/{record}/edit'),
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
