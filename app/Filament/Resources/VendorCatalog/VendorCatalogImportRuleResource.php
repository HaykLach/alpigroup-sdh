<?php

namespace App\Filament\Resources\VendorCatalog;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\VendorCatalog;
use App\Models\VendorCatalog\VendorCatalogImportRule;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorCatalogImportRuleResource extends Resource
{
    protected static ?string $model = VendorCatalogImportRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::VENDOR_CATALOG->value;

    protected static ?string $navigationLabel = 'Rules';

    protected static ?int $navigationSort = 55;

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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => VendorCatalog\VendorCatalogImportRuleResource\Pages\ListVendorCatalogImportRules::route('/'),
            'create' => VendorCatalog\VendorCatalogImportRuleResource\Pages\CreateVendorCatalogImportRule::route('/create'),
            'edit' => VendorCatalog\VendorCatalogImportRuleResource\Pages\EditVendorCatalogImportRule::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? static::getModel()::count()
            : null;
    }
}
