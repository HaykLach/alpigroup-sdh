<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

class PimTextarea extends Form implements PropertyGroupFormInterface
{
    public function getForm(): Textarea
    {
        return Textarea::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description)
            ->rows(6);
    }

    public function getFilter(): ?Filter
    {
        return null;
    }

    public function getTableColumn(callable $fn): TextColumn
    {
        return TextColumn::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description)
            ->visible($fn)
            ->sortable()
            ->words(6)
            ->searchable(isIndividual: true)
            ->toggleable();
    }

    public function isTranslatable(): bool
    {
        return true;
    }
}
