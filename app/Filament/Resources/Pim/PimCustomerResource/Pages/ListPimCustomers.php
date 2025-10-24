<?php

namespace App\Filament\Resources\Pim\PimCustomerResource\Pages;

use App\Enums\Pim\PimCustomerType;
use App\Filament\Resources\Pages\PimListRecords;
use App\Filament\Resources\Pim\PimCustomerResource;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPimCustomers extends PimListRecords
{
    protected static string $resource = PimCustomerResource::class;

    public function getBreadcrumb(): string
    {
        return __('Liste');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label(__('Alle Kunden'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNot('custom_fields->type', PimCustomerType::AGENT->value))
                ->badge(function () {
                    return PimCustomerResource::getEloquentQuery()->whereNot('custom_fields->type', PimCustomerType::AGENT->value)->count();
                }),

            PimCustomerType::CRM_CUSTOMER->value => Tab::make()
                ->label(__('CRM Kunden'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('custom_fields->type', PimCustomerType::CRM_CUSTOMER->value))
                ->badge(function () {
                    return PimCustomerResource::getEloquentQuery()->where('custom_fields->type', PimCustomerType::CRM_CUSTOMER->value)->count();
                }),

            PimCustomerType::CUSTOMER->value => Tab::make()
                ->label(__('ERP Kunden'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('custom_fields->type', PimCustomerType::CUSTOMER->value))
                ->badge(function () {
                    return PimCustomerResource::getEloquentQuery()->where('custom_fields->type', PimCustomerType::CUSTOMER->value)->count();
                }),
        ];
    }
}
