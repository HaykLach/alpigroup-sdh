<?php

namespace App\Filament\Resources\Pim\PimQuotationResource\Pages;

use App\Enums\Pim\PimQuotationStatus;
use App\Enums\RoleType;
use App\Filament\Resources\Pim\PimCustomerResource;
use App\Filament\Resources\Pim\PimLeadResource;
use App\Filament\Resources\Pim\PimQuotationResource;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Filament\Services\PimQuotationResourceService;
use App\Models\Pim\PimQuotation;
use App\Services\Pdf\PimQuotationPdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class EditPimQuotation extends EditRecord
{
    protected static string $resource = PimQuotationResource::class;

    public function getTitle(): string
    {
        return __('Angebot').' '.$this->record->formatted_quotation_number;
    }

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
            EditAction::make('edit_lead')
                ->label(__('Lead anzeigen'))
                ->icon('heroicon-o-arrow-right')
                ->visible(fn ($record) => $record?->pim_lead_id !== null)
                ->url(fn (PimQuotation $record) => PimLeadResource::getUrl('edit', ['record' => $record->pim_lead_id])),

            EditAction::make('edit_customer_address')
                ->label(__('Kunde anzeigen'))
                ->icon('heroicon-o-arrow-right')
                ->url(fn (PimQuotation $record) => PimCustomerResource::getUrl('edit', ['record' => $record->customers()->first()->id])),

            Action::make('view_all_versions')
                ->label(__('Versionen anzeigen'))
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn ($record) => $record->children->count() > 1 || $record->parent_id !== null)
                ->modalHeading(__('Versionen des Angebots'))
                ->modalDescription(fn () => __('Angebot').' '.$this->record->formatted_quotation_number)
                ->modalContent(function () {

                    $versions = PimQuotationResourceService::getAllVersions($this->record);

                    $html = '<div class="space-y-4">';

                    foreach ($versions as $version) {
                        $isCurrentVersion = $version->id === $this->record->id;
                        $statusClass = $isCurrentVersion ? 'bg-primary-50 border-primary-500' : '';
                        $statusText = $isCurrentVersion ? ' (Aktuelle Ansicht)' : '';

                        $html .= '<div class="p-4 border rounded '.$statusClass.'">';
                        $html .= '<div class="flex justify-between items-center">';
                        $html .= '<div>';
                        $html .= '<span class="font-medium">Version '.$version->version.$statusText.'</span>';
                        $html .= '<div class="text-sm text-gray-500">Erstellt am: '.$version->created_at->format('d.m.Y H:i').'</div>';
                        $html .= '<div class="text-sm text-gray-500">Status: '.$version->status->getLabel().'</div>';
                        $html .= '</div>';

                        if (! $isCurrentVersion) {
                            $url = PimQuotationResource::getUrl('edit', ['record' => $version->id]);
                            $html .= '<a href="'.$url.'" class="px-4 py-2 text-sm bg-primary-500 text-white rounded hover:bg-primary-600">'.__('Anzeigen').'</a>';
                        }

                        $html .= '</div>';
                        $html .= '</div>';
                    }

                    $html .= '</div>';

                    return new HtmlString($html);
                }),

            PimQuotationResourceService::getSendCustomerMailAction($this->record->id)
                ->action(function ($record, $data, $livewire) {
                    $this->closeFormComponentActionModal();
                    PimQuotationResourceService::sendEmailAction($record->id, $data);
                    $livewire->dispatch('reloadQuotationForm');
                }),

            Action::make('create_quotation_version')
                ->label(__('Neue Version erstellen'))
                ->icon('heroicon-o-document-duplicate')
                ->action(function ($record) {
                    $quotation = PimQuotationResourceService::generateQuotationVersion($record);

                    return redirect()->to(PimQuotationResource::getUrl('edit', ['record' => $quotation->id]));
                })
                ->visible(fn ($record) => $record?->status !== PimQuotationStatus::DRAFT),

            Action::make('generate_pdf_modal')
                ->label(__('PDF erstellen'))
                ->icon('heroicon-o-document-text')
                ->modal()
                ->form(function () {
                    /** @var PimQuotation $quotation */
                    $quotation = PimQuotationResourceService::getQuotationById($this->record->id);

                    return PimQuotationResourceFormService::getQuotationExportFormCheckboxes($quotation);
                })
                ->action(function ($record, $data) {
                    $this->closeFormComponentActionModal();
                    $filePath = PimQuotationPdfService::generateQuotationPdf($record->id, $data);

                    // Return the PDF for download
                    return response()->download($filePath); // ->deleteFileAfterSend();
                    // return redirect()->away(asset('storage/PimQuotation/pdf/' . basename($filePath)));
                })
                ->modalHeading(__('Angebot PDF erstellen')),

            Action::make('view_pdf')
                ->label('view PDF')
                ->icon('heroicon-o-document')
                ->url(fn ($record) => route('pdf.quotation', ['id' => $record->id]))
                ->visible(fn () => auth()->user()->hasRole(RoleType::SUPER_ADMIN->value))
                ->openUrlInNewTab(),

            DeleteAction::make()
                ->label(__('entfernen'))
                ->visible(fn ($record) => $record?->status === PimQuotationStatus::DRAFT),

            ForceDeleteAction::make()
                ->label(__('löschen')),

            RestoreAction::make()
                ->label(__('wiederherstellen')),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        PimQuotationResourceService::stripSelectAssignmentFormData($data);

        $data['total_cost'] = PimQuotationResourceService::formatMoneyToDB($data['total_cost']);
        $data['total_cost_with_tax'] = PimQuotationResourceService::formatMoneyToDB($data['total_cost_with_tax']);

        return $data;
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }
}
