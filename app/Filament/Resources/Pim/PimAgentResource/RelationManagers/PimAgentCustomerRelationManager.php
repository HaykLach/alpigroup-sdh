<?php

namespace App\Filament\Resources\Pim\PimAgentResource\RelationManagers;

use App\Enums\Pim\PimCustomerType;
use App\Filament\Resources\Pim\PimCustomerResource;
use App\Models\Pim\Customer\PimCustomer;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PimAgentCustomerRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Kunden');
    }

    public static function getModelLabel(): string
    {
        return __('Kunde');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function getFormElements(): array
    {
        return [
            TextInput::make('region')
                ->label(__('Region')),

        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->searchable(false)
            ->searchOnBlur()
            ->query(PimCustomer::query()
                ->byAgentId($this->ownerRecord->id)
                ->withQuotationsCount()
            )
            ->columns([
                ...PimResourceCustomerService::getTableColElements(PimCustomerType::CUSTOMER, false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('hinzufügen'))
                    ->modalHeading(__(self::getModelLabel())),
            ])
            ->actions([
                Tables\Actions\Action::make('redirectToCustomer')
                    ->label(__('anzeigen'))
                    ->icon('heroicon-m-eye')
                    ->url(fn (PimCustomer $record) => PimCustomerResource::getUrl('view', ['record' => $record->id])),
            ])
            ->recordUrl(
                fn (PimCustomer $record) => PimCustomerResource::getUrl('view', ['record' => $record->id])
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('entfernen'),
                ]),
            ]);
    }
}
