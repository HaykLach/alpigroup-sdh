<?php

namespace App\Filament\Resources\Pim\PimCustomerResource\Pages;

use App\Enums\Pim\PimCustomerType;
use App\Filament\Resources\Pim\PimCustomerResource;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Pages\EditRecord;

class EditPimCustomer extends EditRecord
{
    protected static string $resource = PimCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('entfernen')),
        ];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        return PimResourceCustomerService::setCustomFieldTypeData($data, PimCustomerType::CRM_CUSTOMER);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('custom_fields.agent_id')
                    ->label(__('Vertrieb'))
                    ->required()
                    ->options(PimResourceCustomerService::getSelectOptions(PimCustomerType::AGENT))
                    ->afterStateHydrated(function ($record, Set $set) {
                        if ($record && $record->agent) {
                            $set('custom_fields.agent_id', $record->agent->id ?? null);
                        }
                    })
                    ->preload()
                    ->searchable()
                    ->placeholder(__('Vertrieb auswählen')),

                ...PimResourceCustomerService::getFormElements(),
            ])
            ->columns(1);
    }
}
