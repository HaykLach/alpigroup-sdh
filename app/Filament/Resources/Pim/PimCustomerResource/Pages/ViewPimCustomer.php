<?php

namespace App\Filament\Resources\Pim\PimCustomerResource\Pages;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Enums\Pim\PimCustomerType;
use App\Filament\Forms\Components\NetFolderLink;
use App\Filament\Resources\Pim\PimCustomerResource;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewPimCustomer extends ViewRecord
{
    protected static string $resource = PimCustomerResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...PimResourceCustomerService::getFormElementsSectionDetails(),

                NetFolderLink::make('custom_fields.'.PimCustomerCustomFields::NET_FOLDER_DOCUMENTS->value)
                    ->label(__('Ordner Netzlaufwerk'))
                    ->visible(fn ($record) => $record->custom_fields[PimCustomerCustomFields::TYPE->value] === PimCustomerType::CUSTOMER->value)
                    ->viewData([
                        'path' => $this->record->custom_fields[PimCustomerCustomFields::NET_FOLDER_DOCUMENTS->value] ?? null,
                    ]),
            ])
            ->columns(2);
    }
}
