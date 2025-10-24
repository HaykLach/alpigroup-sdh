<?php

namespace App\Contracts\Property;

use App\Models\Pim\Property\PimPropertyGroup;
use App\Tables\Columns\FileuploadOpenFileColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

interface PropertyGroupFormInterface
{
    public function __construct(PimPropertyGroup $group);

    public function getForm(): Select|Toggle|DatePicker|TextInput|Textarea|SpatieMediaLibraryFileUpload;

    public function getFilter(): DateRangeFilter|SelectFilter|Filter|null;

    public function getTableColumn(callable $fn): TextColumn|ImageColumn|IconColumn|FileuploadOpenFileColumn|SelectColumn|TextInputColumn|ToggleColumn;

    public function isTranslatable(): bool;
}
