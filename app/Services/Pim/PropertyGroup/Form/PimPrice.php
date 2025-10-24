<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use App\Enums\Pim\PimMappingType;
use App\Services\Pim\PimProductService;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\Filter;

class PimPrice extends Form implements PropertyGroupFormInterface
{
    public function getForm(): TextInput
    {
        return TextInput::make('prices.'.$this->group->id)
            ->label($this->group->description)
            ->numeric()
            ->inputMode('decimal')
            ->suffix('€');
    }

    public function getFilter(): ?Filter
    {
        return null;
    }

    public function getTableColumn(callable $fn): TextColumn|TextInputColumn
    {
        if ($this->inlineEdit) {

            $col = TextInputColumn::make('prices.'.$this->group->id)
                ->label($this->group->description.' €')
                ->rules(['numeric'])
                ->visible($fn)
                ->inputMode('decimal')
                ->sortable()
                ->alignEnd()
                ->toggleable()
                ->extraAttributes(['style' => 'min-width: 160px;']);

            if ($this->group->custom_fields['type'] === PimMappingType::PRODUCT->name) {
                $col = $col->updateStateUsing(function ($state, $record) {
                    $data = [
                        'prices' => [
                            $this->group->id => $state,
                        ],
                    ];
                    PimProductService::update($record, $data);
                });
            }

            return $col;

        } else {
            return TextColumn::make('prices.'.$this->group->id)
                ->label($this->group->description)
                ->visible($fn)
                ->money('EUR', locale: 'de')
                ->alignEnd()
                ->sortable()
                ->toggleable();
        }

    }

    public function isTranslatable(): bool
    {
        return false;
    }
}
