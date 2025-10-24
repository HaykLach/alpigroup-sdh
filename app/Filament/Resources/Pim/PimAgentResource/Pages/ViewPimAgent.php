<?php

namespace App\Filament\Resources\Pim\PimAgentResource\Pages;

use App\Filament\Resources\Pim\PimAgentResource;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewPimAgent extends ViewRecord
{
    protected static string $resource = PimAgentResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->full_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }
}
