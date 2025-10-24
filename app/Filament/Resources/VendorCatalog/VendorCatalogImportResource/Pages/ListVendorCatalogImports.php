<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogImportResource\Pages;

use App\Enums\VendorCatalog\VendorCatalogImportDefinitionProtocolType;
use App\Filament\Resources\VendorCatalog\VendorCatalogImportResource;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVendorCatalogImports extends ListRecords
{
    protected static string $resource = VendorCatalogImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('import')
                ->label(__('Import'))
                ->color('danger')
                ->action(function ($data) {
                    $definition = VendorCatalogImportDefinition::find($data['importDefId']);
                    $service = app(VendorCatalogFileImportService::class);
                    $service->importFile($definition, $data['fileUpload'] ?? null);

                    return Notification::make()
                        ->title('Imported successfully')
                        ->success()
                        ->send();
                })
                ->form([
                    Select::make('importDefId')
                        ->label(__('Import definition'))
                        ->options(VendorCatalogImportDefinition::query()->pluck('name', 'id'))
                        ->reactive()
                        ->required(),
                    FileUpload::make('fileUpload')
                        ->disk('local')
                        ->directory('vendor_catalogs/'.now()->format('Y/m/d'))
                        ->visibility('private')
                        ->hidden(function (callable $get) {
                            $importDef = VendorCatalogImportDefinition::find($get('importDefId'));

                            return ! $importDef || $importDef->protocol !== VendorCatalogImportDefinitionProtocolType::UPLOAD;
                        })
                        ->preserveFilenames()
                        ->required(),
                ])
                ->modalButton(__('Import')),
        ];
    }
}
