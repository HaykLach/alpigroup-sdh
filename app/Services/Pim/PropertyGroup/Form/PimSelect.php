<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Services\Pim\PropertyGroup\Form\Traits\PimColumnMultipleOptions;
use App\Services\Pim\PropertyGroup\Form\Traits\PimFilterOptions;
use App\Services\Pim\PropertyGroup\Form\Traits\PimStoreOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class PimSelect extends Form implements PropertyGroupFormInterface
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
            ->saveRelationshipsUsing(self::store($options))
            ->searchable()
            ->afterStateHydrated(
                fn ($record, Set $set) => ! isset($record->properties) ? null : $set('properties.'.$this->group->id, self::getSingleOption($record, $this->group) ?? null)
            );
    }

    protected static function getSingleOption($record, PimPropertyGroup $group): ?string
    {
        return $record?->properties
            ->filter(fn ($property) => $property->group_id === $group->id)
            ->map(function ($option) {
                return $option->id;
            })
            ->first();
    }

    public function getFilter(): SelectFilter
    {
        return $this->getFilterSelectMultiple($this->group);
    }

    public function getTableColumn(callable $fn): TextColumn|SelectColumn
    {
        return $this->getColumnMultipleOptions($this->group, $fn);
    }

    public function isTranslatable(): bool
    {
        return false;
    }
}
