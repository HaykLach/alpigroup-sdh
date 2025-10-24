<?php

namespace App\Filament\Resources\VendorCatalog;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\VendorCatalog;
use App\Models\VendorCatalog\VendorCatalogImport;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorCatalogImportResource extends Resource
{
    protected static ?string $model = VendorCatalogImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::VENDOR_CATALOG->value;

    protected static ?string $navigationLabel = 'Imports';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('file_name'),
                Forms\Components\TextInput::make('state'),

                Forms\Components\Section::make('meta')->schema([
                    Forms\Components\Section::make('storage')->schema([
                        Forms\Components\TextInput::make('path'),
                        Forms\Components\TextInput::make('disk'),
                    ]),
                    Forms\Components\TextInput::make('human_file_size')->label('File size'),
                    // Forms\Components\TextInput::make('size'),
                    Forms\Components\TextInput::make('mime_type'),
                    Forms\Components\TextInput::make('file_hash'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('state'),

                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            VendorCatalog\VendorCatalogImportResource\RelationManagers\VendorCatalogImportRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => VendorCatalog\VendorCatalogImportResource\Pages\ListVendorCatalogImports::route('/'),
            'view' => VendorCatalog\VendorCatalogImportResource\Pages\ViewVendorCatalogImport::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? static::getModel()::count()
            : null;
    }
}
