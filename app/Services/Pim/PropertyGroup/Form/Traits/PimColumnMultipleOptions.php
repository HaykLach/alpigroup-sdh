<?php

namespace App\Services\Pim\PropertyGroup\Form\Traits;

use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Services\Pim\PimProductService;
use App\Services\Pim\PropertyGroup\Form\PimColor;
use App\Services\Pim\PropertyGroup\Form\PimSelect;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

trait PimColumnMultipleOptions
{
    protected function getColumnMultipleOptions(PimPropertyGroup $group, callable $fn): TextColumn|SelectColumn
    {
        $sortableFnc = function (Builder $query, string $direction) use ($group): Builder {
            if ($query->getModel() instanceof PimProduct) {
                return PimProductService::getColumnsSortableQuery($group, $query, $direction);
            }

            return $query;
        };

        if (in_array(get_class($this), [PimSelect::class, PimColor::class]) && $this->inlineEdit) {

            $options = $group->groupOptions->pluck('name', 'id');
            // add new option with value null to options
            $options->put('', '');

            return SelectColumn::make('properties.'.$group->id)
                ->label($group->description.' selection')
                ->extraAttributes(['style' => 'min-width: 180px;'])
                ->options(fn () => $options)
                ->getStateUsing(function ($record) use ($group): string {
                    $id = $record->properties
                        ->filter(fn ($property) => $property->group_id === $group->id)
                        ->map(fn ($property) => $property->id)
                        ->first();

                    return $id ? (string) $id : '';
                })
                ->updateStateUsing(self::store($options))
                ->selectablePlaceholder(false)
                ->visible($fn)
                ->toggleable()
                ->sortable(query: $sortableFnc);
        } else {

            return TextColumn::make('properties.'.$group->id)
                ->label($group->description)
                ->getStateUsing(function ($record) use ($group): string {
                    if (! isset($record->properties)) {
                        return '';
                    }

                    return $record->properties
                        ->filter(fn ($property) => $property->group_id === $group->id)
                        ->map(fn ($property) => $property->name)
                        ->sort()
                        ->implode(', ');
                })
                ->visible($fn)
                ->toggleable()
                ->sortable(query: $sortableFnc);
        }
    }
}
