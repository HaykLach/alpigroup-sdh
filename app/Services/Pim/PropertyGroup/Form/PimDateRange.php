<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PimDateRange extends Form implements PropertyGroupFormInterface
{
    public function getForm(): DatePicker
    {
        return DatePicker::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description);
    }

    public function getFilter(): DateRangeFilter
    {
        return DateRangeFilter::make('custom_fields_properties_'.$this->group->id)
            ->label($this->group->description)
            ->modifyQueryUsing(fn (Builder $query, ?Carbon $startDate, ?Carbon $endDate, $dateString) => $query->when(! empty($dateString),
                fn (Builder $query, $date): Builder => $query->whereBetween('custom_fields->properties->'.$this->group->id, [$startDate, $endDate]))
            );
    }

    public function getTableColumn(callable $fn): TextColumn
    {
        return TextColumn::make('custom_fields.properties.'.$this->group->id)
            ->label($this->group->description)
            ->visible($fn)
            ->sortable()
            ->date()
            ->toggleable();
    }

    public function isTranslatable(): bool
    {
        return false;
    }
}
