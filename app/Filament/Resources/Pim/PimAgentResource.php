<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Enums\Pim\PimCustomerType;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\Pim\PimAgentResource\Pages;
use App\Filament\Resources\Pim\PimAgentResource\RelationManagers\PimAgentCustomerRelationManager;
use App\Filament\Resources\Pim\PimCustomerResource\RelationManagers\PimCustomerAddressRelationManager;
use App\Models\Pim\Customer\PimAgent;
use App\Models\Pim\Customer\PimCustomer;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PimAgentResource extends Resource
{
    protected static ?string $model = PimAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?int $navigationSort = 35;

    protected static ?string $navigationLabel = 'Agent';

    protected static ?string $modelLabel = 'Agent';

    public static function getNavigationLabel(): string
    {
        return __('Vertrieb');
    }

    public static function getModelLabel(): string
    {
        return __('Vertrieb');
    }

    public static function getLabel(): string
    {
        return __('Vertrieb');
    }

    public static function getPluralLabel(): string
    {
        return __('Vertrieb');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PimCustomer::query()->where('custom_fields->'.PimCustomerCustomFields::TYPE->value, '=', PimCustomerType::AGENT->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(PimResourceCustomerService::getFormElements(PimCustomerType::AGENT))
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->searchable(false)
            ->searchOnBlur()
            ->columns([
                ...PimResourceCustomerService::getTableColElements(PimCustomerType::AGENT, false),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('anzeigen')),
                // Tables\Actions\EditAction::make()->label(__('bearbeiten')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PimAgentCustomerRelationManager::class,
            PimCustomerAddressRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCustomersDBCount()
            ->withQuotationsCount();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPimAgents::route('/'),
            // 'edit' => Pages\EditPimAgent::route('/{record}/edit'),
            'view' => Pages\ViewPimAgent::route('/{record}'),
        ];
    }
}
