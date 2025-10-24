<?php

namespace App\Filament\Resources\Pim\PimQuotationTemplateResource\Pages;

use App\Filament\Resources\Pages\PimListRecords;
use App\Filament\Resources\Pim\PimQuotationTemplateResource;
use Filament\Actions\CreateAction;

class ListPimQuotationTemplates extends PimListRecords
{
    protected static string $resource = PimQuotationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('erstellen'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
