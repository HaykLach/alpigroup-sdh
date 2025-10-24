<?php

namespace App\Filament\Resources\Pim\PimLeadResource\Pages;

use App\Enums\Pim\PimLeadSourceNamespace;
use App\Enums\RoleType;
use App\Filament\Resources\Pim\PimCustomerResource;
use App\Filament\Resources\Pim\PimLeadResource;
use App\Filament\Resources\Pim\PimQuotationResource;
use App\Models\Pim\PimLead;
use App\Models\Pim\PimQuotation;
use Filament\Actions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPimLead extends EditRecord
{
    protected static string $resource = PimLeadResource::class;

    public function getTitle(): string
    {
        return __('Lead').' '.__('Nr.').' '.$this->record->number;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(__('speichern'))
                ->icon('heroicon-o-document'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createQuotation')
                ->label('Angebot erstellen')
                ->icon('heroicon-o-document-plus')
                ->url(function (Model $record): string {
                    $queryParams = [
                        'pim_lead_id' => $record->id,
                        'agent' => $record->agent->id,
                        'customer' => $record->customer->id,
                    ];

                    return PimQuotationResource::getUrl('create', $queryParams);
                }),

            EditAction::make('edit_customer_address')
                ->label(__('Kunde anzeigen'))
                ->icon('heroicon-o-arrow-right')
                ->url(fn (PimLead $record) => PimCustomerResource::getUrl('edit', ['record' => $record->pim_customer_id])),

            Actions\DeleteAction::make()
                ->label(__('Lead entfernen'))
                ->icon('heroicon-o-trash')
                ->visible(function (Model $record): string {
                    if ($record->source_namespace === PimLeadSourceNamespace::SDH->value) {
                        $user = auth()->user();
                        $isAgent = $user->hasRole(RoleType::AGENT->value);
                        if ($isAgent) {
                            $count = PimQuotation::query()->where('pim_lead_id', $record->id)->count();
                            if ($user->agent()->get()->first()->id === $record->pim_agent_id && $count === 0) {
                                return true;
                            }
                        }
                    }

                    return false;
                })
                ->requiresConfirmation(),
        ];
    }
}
