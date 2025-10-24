<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\Pim\PimJobResource\Pages;
use App\Models\Pim\Job\PimJob;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PimJobResource extends Resource
{
    protected static ?string $model = PimJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?string $navigationLabel = 'Job';

    protected static ?int $navigationSort = 300;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Job Id')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('job_name')->label('Job Name'),
                Tables\Columns\TextColumn::make('last_execution_date')->dateTime(),
                Tables\Columns\TextColumn::make('last_execution_duration')->label('Last Execution Duration (s)'),
                Tables\Columns\TextColumn::make('last_execution_result')->label('Result'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPimJobs::route('/'),
        ];
    }
}
