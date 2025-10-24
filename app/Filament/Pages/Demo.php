<?php

namespace App\Filament\Pages;

use App\Models\VendorCatalog;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Pages\Page;
use Illuminate\Support\Lottery;

class Demo extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static string $view = 'filament.pages.demo';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Reset Database')->requiresConfirmation()->tooltip('Drop all data, except user table')->action('resetDatabase'),

            Actions\Action::make('Demo Data')->requiresConfirmation()->tooltip('Import demo data')->action('importDemoData'),

            Actions\Action::make('Notify')->icon('heroicon-o-adjustments-vertical')->tooltip('Dummy Notification')->action('notifyDummy'),
        ];
    }

    public function resetDatabase(): Notification
    {
        VendorCatalog\VendorCatalogVendor::truncate();
        VendorCatalog\VendorCatalogEntry::truncate();
        VendorCatalog\ImportDefinition\VendorCatalogImportDefinition::truncate();
        VendorCatalog\VendorCatalogImport::truncate();
        VendorCatalog\VendorCatalogImportRecord::truncate();

        return Notification::make()->title('Database reset successfully')->success()->send();
    }

    public function importDemoData(): Notification
    {
        $vendors = VendorCatalog\VendorCatalogVendor::factory()->count(30)->create();
        $vendors->each(function (VendorCatalog\VendorCatalogVendor $vendor) {
            if (Lottery::odds(2, 3)->choose()) {
                VendorCatalog\ImportDefinition\VendorCatalogImportDefinition::factory()
                    ->vendor($vendor)->create([
                        'name' => 'CSV Importer - '.$vendor->name,
                    ]);
            }
        });

        return Notification::make()->title('Demo data imported successfully')->success()->send();
    }

    public function notifyDummy(): Notification
    {
        return Notification::make()->title('Send successfully')->success()->send();
    }
}
