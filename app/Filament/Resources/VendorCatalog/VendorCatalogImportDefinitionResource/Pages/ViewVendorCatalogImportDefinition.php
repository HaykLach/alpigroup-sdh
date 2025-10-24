<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogImportDefinitionResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogImportDefinitionResource;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorCatalogImportDefinition extends ViewRecord
{
    protected static string $resource = VendorCatalogImportDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('run')
                ->icon('heroicon-o-adjustments-vertical')
                ->tooltip('Run Import Definition')
                ->requiresConfirmation()
                ->action('importFile'),
            Actions\DeleteAction::make(),
        ];
    }

    public function importFile(): Notification
    {
        app(VendorCatalogFileImportService::class)
            ->importFile(
                definition: $this->record
            );

        return Notification::make()
            ->title('Imported successfully')
            ->success()
            ->send();

    }
}
