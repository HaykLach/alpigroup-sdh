<?php

namespace App\Services\Pim\PropertyGroup\Form\Traits;

use App\Models\Pim\Property\PimPropertyGroup;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

trait PimFilterOptions
{
    protected function getFilterSelectMultiple(PimPropertyGroup $group): SelectFilter
    {
        return SelectFilter::make('filter.properties.'.$group->id)
            ->name($group->name)
            ->label($group->description)
            ->relationship(name: 'properties', titleAttribute: 'name', modifyQueryUsing: function (Builder $query) use ($group) {
                return $query->where('group_id', $group->id)
                    ->orderBy('position');
            })
            ->multiple()
            ->searchable()
            ->preload()
            ->searchable();
    }
}
