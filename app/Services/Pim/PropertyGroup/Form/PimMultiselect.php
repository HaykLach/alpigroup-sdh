<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Services\Pim\PropertyGroup\Form\Traits\PimColumnMultipleOptions;
use App\Services\Pim\PropertyGroup\Form\Traits\PimFilterOptions;
use App\Services\Pim\PropertyGroup\Form\Traits\PimStoreOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;

class PimMultiselect extends Form implements PropertyGroupFormInterface
{
    use PimColumnMultipleOptions;
    use PimFilterOptions;
    use PimStoreOptions;

    public function getForm(): Select
    {
        $options = $this->group->groupOptions->pluck('name', 'id');

        return Select::make('properties.'.$this->group->id)
            ->label($this->group->description)
            ->options($options)
            ->preload()
            ->multiple()
            ->searchable()
            ->saveRelationshipsUsing(self::store($options))
            ->afterStateHydrated(
                function ($record, Set $set) {
                    if (! isset($record->properties)) {
                        return;
                    }

                    $assignedOptions = self::getAssignedOptions($record, $this->group);
                    if ($assignedOptions === null) {
                        return;
                    }

                    $set('properties.'.$this->group->id, array_values($assignedOptions->toArray()));
                }
            );
    }

    protected static function getAssignedOptions($record, PimPropertyGroup $group): ?Collection
    {
        return $record?->properties
            ->filter(fn ($property) => $property->group_id === $group->id)
            ->sortBy('name')
            ->map(function ($option) {
                return $option->id;
            });
    }

    public function getFilter(): SelectFilter
    {
        return $this->getFilterSelectMultiple($this->group);
    }

    public function getTableColumn(callable $fn): TextColumn
    {
        return $this->getColumnMultipleOptions($this->group, $fn);
    }

    public function isTranslatable(): bool
    {
        return false;
    }
}
