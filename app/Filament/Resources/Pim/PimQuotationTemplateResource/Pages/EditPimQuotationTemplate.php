<?php

namespace App\Filament\Resources\Pim\PimQuotationTemplateResource\Pages;

use App\Filament\Resources\Pim\PimQuotationTemplateResource;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Filament\Services\PimQuotationResourceService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditPimQuotationTemplate extends EditRecord
{
    protected static string $resource = PimQuotationTemplateResource::class;

    #[On('updateQuotation')]
    public function updateQuotation(): void
    {
        PimQuotationResourceFormService::eventUpdateQuotation($this);
    }

    #[On('updateQuotationAndProductsPosition')]
    public function updateQuotationAndProductsPosition(): void
    {
        $this->updateQuotation();
        // update $this->record->products positions, set position +1 for each product
        PimQuotationResourceFormService::resetProductPositions($this->record->products);
    }

    #[On('reloadQuotationForm')]
    public function reloadProductsRelationManager(): void
    {
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(__('entfernen')),
            ForceDeleteAction::make()
                ->label(__('löschen')),
            RestoreAction::make()
                ->label(__('wiederherstellen')),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total_cost'] = PimQuotationResourceService::formatMoneyToDB($data['total_cost']);

        return $data;
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }
}
