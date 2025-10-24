<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimCustomerType;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Enums\RoleType;
use App\Filament\Resources\Pim\PimCustomerResource\Pages;
use App\Filament\Resources\Pim\PimCustomerResource\RelationManagers\PimCustomerAddressRelationManager;
use App\Filament\Resources\Pim\PimCustomerResource\RelationManagers\PimQuotationRelationManager;
use App\Models\Pim\Customer\PimAgent;
use App\Models\Pim\Customer\PimCustomer;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PimCustomerResource extends Resource
{
    protected static ?string $model = PimCustomer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Customer';

    protected static ?string $modelLabel = 'Customer';

    public static function getNavigationLabel(): string
    {
        return __('Kunden');
    }

    public static function getModelLabel(): string
    {
        return __('Kunde');
    }

    public static function getLabel(): string
    {
        return __('Kunde');
    }

    public static function getPluralLabel(): string
    {
        return __('Kunden');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::getEloquentQuery()->customers()->count() + self::getEloquentQuery()->crmCustomers()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAgent = $user->hasRole(RoleType::AGENT->value);

        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->searchable(false)
            ->searchOnBlur()
            ->columns([
                ...PimResourceCustomerService::getTableColElements(PimCustomerType::CUSTOMER, ! $isAgent),
            ])
            ->filters([
                SelectFilter::make('custom_fields.agent_id')
                    ->label(__('Vertrieb'))
                    ->options(
                        PimAgent::query()
                            ->orderBy('last_name')
                            ->get()
                            ->map(fn (PimAgent $agent) => [
                                'id' => $agent->id,
                                'full_name' => $agent->full_name.' ('.$agent->customers_count.')',
                            ])
                            ->pluck('full_name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->modifyQueryUsing(function (Builder $query, $data) {
                        if ($data['value']) {
                            $query->byAgentId($data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label(__('entfernen'))
                    ->requiresConfirmation()
                    ->visible(function (PimCustomer $record) {
                        return $record->type === PimCustomerType::CRM_CUSTOMER->value;
                    }),
                Tables\Actions\ViewAction::make()
                    ->hidden(fn (PimCustomer $record) => $record->type === PimCustomerType::CRM_CUSTOMER->value)
                    ->label(__('anzeigen')),
                Tables\Actions\EditAction::make()
                    ->hidden(fn (PimCustomer $record) => $record->type !== PimCustomerType::CRM_CUSTOMER->value)
                    ->label(__('bearbeiten')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($livewire) => $livewire->activeTab === PimCustomerType::CRM_CUSTOMER->value)
                        ->label(__('entfernen')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PimQuotationRelationManager::class,
            PimCustomerAddressRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPimCustomers::route('/'),
            'edit' => Pages\EditPimCustomer::route('/{record}/edit'),
            'view' => Pages\ViewPimCustomer::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with('agent')
            ->withQuotationsCount();

        if ($user->hasRole(RoleType::AGENT->value)) {
            $query->byAgentId($user->agent->id);
        }

        return $query;
    }
}
