<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogImportResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogImportResource;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorCatalogImport extends ViewRecord
{
    protected static string $resource = VendorCatalogImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('run')
                ->icon('heroicon-o-adjustments-vertical')
                ->tooltip('Import rows from file')
                ->requiresConfirmation()
                ->action('importRows'),
        ];
    }

    public function importRows(): Notification
    {
        $app = app(VendorCatalogFileImportService::class);
        $app->readHeadings(import: $this->record);
        $app->truncateRecords(import: $this->record);
        $app->importRecords(import: $this->record);

        return Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();

    }
}
