<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use App\Enums\Pim\PimMappingType;
use App\Services\Pim\PimProductService;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\Filter;

class PimNumber extends Form implements PropertyGroupFormInterface
{
    public function getForm(): TextInput
    {
        $form = TextInput::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description)
            ->numeric();

        switch ($this->group->custom_fields['form']['config']['format']) {
            case 'float':
                $form->inputMode('decimal');
                break;

            case 'integer':
                $form->integer();
                break;
        }

        return $form;
    }

    public function getFilter(): ?Filter
    {
        return null;
    }

    public function getTableColumn(callable $fn): TextColumn|TextInputColumn
    {
        if ($this->inlineEdit) {

            $col = TextInputColumn::make('custom_fields.properties.'.$this->group->id)
                ->label($this->group->description)
                ->visible($fn)
                ->sortable()
                ->searchable(isIndividual: true)
                ->toggleable()
                ->rules(['numeric'])
                ->alignEnd()
                ->extraAttributes(['style' => 'min-width: 100px;']);

            if ($this->group->custom_fields['type'] === PimMappingType::PRODUCT->name) {
                $col = $col->updateStateUsing(function ($state, $record) {
                    $data = [
                        'custom_fields' => [
                            'properties' => [
                                $this->group->id => $state,
                            ],
                        ],
                    ];
                    PimProductService::update($record, $data);
                });
            }

            return $col;

        } else {
            return TextColumn::make('custom_fields.properties.'.$this->group->id)
                ->label($this->group->description)
                ->visible($fn)
                ->sortable()
                ->searchable(isIndividual: true)
                ->alignEnd()
                ->toggleable();
        }
    }

    public function isTranslatable(): bool
    {
        return false;
    }
}
