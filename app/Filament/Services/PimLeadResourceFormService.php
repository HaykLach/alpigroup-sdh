<?php

namespace App\Filament\Services;

use App\Enums\Pim\PimLeadSource;
use App\Enums\Pim\PimLeadStatus;
use App\Filament\Resources\Pim\PimQuotationResource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PimLeadResourceFormService
{
    public static function getTable(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->searchable()
            ->searchOnBlur()
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Erstellt am'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('Aktualisiert am'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (PimLeadStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (PimLeadStatus $state): string => $state->getLabel())
                    ->sortable(),

                TextColumn::make('source')
                    ->label(__('Quelle'))
                    ->formatStateUsing(fn (PimLeadSource $state): string => $state->getLabel())
                    ->sortable()
                    ->searchable(),

                TextColumn::make('number')
                    ->label(__('Nummer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quotations')
                    ->label(__('Angebot'))
                    ->formatStateUsing(function ($record) {
                        if ($record->quotations !== null) {
                            $quotation = $record->quotations->sortByDesc('updated_at')->first();

                            return Action::make('view')
                                ->label(function () use ($quotation) {
                                    return __('Angebot').' '.$quotation->formatted_quotation_number;
                                })
                                ->url(function () use ($quotation) {
                                    return PimQuotationResource::getUrl('edit', ['record' => $quotation->id]);
                                })
                                ->color('primary')
                                ->button()
                                ->render();
                        }

                        return null;
                    })
                    ->html(),

                TextColumn::make('agent')
                    ->hidden($user->hasRole('agent'))
                    ->toggleable()
                    ->label(__('Vertrieb'))
                    ->getStateUsing(function ($record) {
                        return $record->agent->summarized_title ?? null;
                    }),

                TextColumn::make('customer_name')
                    ->label(__('Name'))
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->customer->summarized_title ?? null;
                    }),

                TextColumn::make('customer_email')
                    ->label(__('Email'))
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->customer->email ?? null;
                    }),

                TextColumn::make('customer_phone')
                    ->label(__('Telefon'))
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->customer->addresses->first()->phone_number ?? null;
                    }),
            ])
            ->filters([
                //
            ]);

    }
}
