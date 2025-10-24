<?php

namespace App\Filament\Resources\VendorCatalog;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\VendorCatalog;
use App\Models\VendorCatalog\VendorCatalogVendor;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorCatalogVendorResource extends Resource
{
    protected static ?string $model = VendorCatalogVendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::VENDOR_CATALOG->value;

    protected static ?string $navigationLabel = 'Vendors';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('code')->required(),
                Forms\Components\Fieldset::make('contact')
                    ->schema([
                        Forms\Components\TextInput::make('contact.name'),
                        Forms\Components\TextInput::make('contact.email'),
                        Forms\Components\TextInput::make('contact.phone'),
                    ]),
                Forms\Components\MarkdownEditor::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('code'),

                Tables\Columns\TextColumn::make('contact.name'),
                Tables\Columns\TextColumn::make('contact.email'),
                Tables\Columns\TextColumn::make('contact.phone'),

                Tables\Columns\TextColumn::make('updated_at')->dateTime(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => VendorCatalog\VendorCatalogVendorResource\Pages\ListVendorCatalogVendors::route('/'),
            'create' => VendorCatalog\VendorCatalogVendorResource\Pages\CreateVendorCatalogVendor::route('/create'),
            'view' => VendorCatalog\VendorCatalogVendorResource\Pages\ViewVendorCatalogVendor::route('/{record}'),
            'edit' => VendorCatalog\VendorCatalogVendorResource\Pages\EditVendorCatalogVendor::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? static::getModel()::count()
            : null;
    }
}
