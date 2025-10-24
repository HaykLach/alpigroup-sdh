<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use App\Enums\Pim\PimMappingType;
use App\Services\Pim\PimProductService;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PimBool extends Form implements PropertyGroupFormInterface
{
    public function getForm(): Toggle
    {
        $defaultState = $this->group->custom_fields['form']['default']['state'] ?? true;

        return Toggle::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description)
            ->default($defaultState);
    }

    public function getFilter(): SelectFilter
    {
        return SelectFilter::make('custom_fields_properties_'.$this->group->id)
            ->label($this->group->description)
            ->options([
                true => 'Yes',
                false => 'No',
            ])
            ->modifyQueryUsing(fn (Builder $query, $data) => $query->when($data['value'] !== null,
                fn (Builder $query) => $query->where('custom_fields->properties->'.$this->group->id, '=', (bool) $data['value']))
            );
    }

    public function getTableColumn(callable $fn): IconColumn|ToggleColumn
    {
        if ($this->inlineEdit) {
            $col = ToggleColumn::make('custom_fields.properties.'.$this->group->id)
                ->label($this->group->description)
                ->visible($fn)
                ->sortable()
                ->toggleable();

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
            return IconColumn::make('custom_fields.properties.'.$this->group->id)
                ->label($this->group->description)
                ->visible($fn)
                ->boolean()
                ->sortable()
                ->toggleable();
        }

    }

    public function isTranslatable(): bool
    {
        return false;
    }
}
