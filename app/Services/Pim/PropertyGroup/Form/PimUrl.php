<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;

class PimUrl extends Form implements PropertyGroupFormInterface
{
    public function getForm(): TextInput
    {
        return TextInput::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description)
            ->url()
            ->suffixAction(fn (?string $state): Action => Action::make('visit')
                ->icon('heroicon-o-cube')
                ->url(
                    filled($state) ? $state : null,
                    shouldOpenInNewTab: true,
                ),
            );
    }

    public function getFilter(): ?Filter
    {
        return null;
    }

    public function getTableColumn(callable $fn): IconColumn
    {
        return IconColumn::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description)
            ->visible($fn)
            ->boolean()
            ->toggleable()
            ->sortable();
    }

    public function isTranslatable(): bool
    {
        return true;
    }
}
