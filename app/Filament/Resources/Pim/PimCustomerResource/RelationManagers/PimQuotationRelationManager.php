<?php

namespace App\Filament\Resources\Pim\PimCustomerResource\RelationManagers;

use App\Enums\Pim\PimQuotationStatus;
use App\Filament\Resources\Pages\Traits\CanResizeColumnsPerUser;
use App\Filament\Resources\Pim\PimQuotationResource;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Models\Pim\PimLead;
use App\Models\Pim\PimQuotation;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PimQuotationRelationManager extends RelationManager
{
    use CanResizeColumnsPerUser;

    protected static string $relationship = 'quotations';

    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Angebote');
    }

    public static function getModelLabel(): string
    {
        return __('Angebot');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(100)
            ->searchable(false)
            ->searchOnBlur()
            ->query(function () {
                $query = PimQuotation::query()
                    ->with(['agents', 'customers', 'products', 'tax'])
                    ->orderBy('quotation_number', 'desc')
                    ->orderBy('version', 'desc');

                if ($this->getOwnerRecord() instanceof PimLead) {
                    $query->where('pim_lead_id', $this->getOwnerRecord()->id);
                } else {
                    $query->whereHas('customers', function ($query) {
                        $query->where('pim_customer_id', $this->getOwnerRecord()->id);
                    });
                }

                return $query;

            })
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->sortable()
                    ->formatStateUsing(fn (PimQuotationStatus $state): string => $state->getLabel())
                    ->colors([
                        'gray' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::DRAFT,
                        'info' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::SENT,
                        'success' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::ACCEPTED,
                        'danger' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::DECLINED,
                        'warning' => fn (PimQuotationStatus $state): bool => $state === PimQuotationStatus::EXPIRED,
                    ]),

                TextColumn::make('formatted_quotation_number')
                    ->label(__('Nr.'))
                    ->searchable(query: PimQuotationResourceFormService::getTableQuotationNumberSearch(PimQuotation::QUOTATION_NUMBER_PREFIX, 'quotation_number'), isIndividual: true)
                    ->sortable([
                        'quotation_number',
                    ])
                    ->alignEnd(),

                TextColumn::make('agent')
                    ->label(__('Vertrieb'))
                    ->getStateUsing(function ($record) {
                        return $record->agents->first()->summarized_title ?? null;
                    }),

                TextColumn::make('customer')
                    ->label(__('Kunde'))
                    ->getStateUsing(function ($record) {
                        return $record->customers->first()->summarized_title ?? null;
                    }),

                TextColumn::make('date')
                    ->label(__('Datum'))
                    ->alignEnd()
                    ->sortable()
                    ->date(),

                TextColumn::make('validity_period')
                    ->label(__('Gültigkeitsdauer'))
                    ->alignEnd()
                    ->sortable()
                    ->date(),

                TextColumn::make('item_count')
                    ->label(__('Posten'))
                    ->getStateUsing(function ($record) {
                        return $record->products->count();
                    })
                    ->alignEnd(),

                TextColumn::make('discount_percentage')
                    ->label(__('Rabatt in %'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('discount_amount')
                    ->label(__('Rabatt fix'))
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('shipping_cost')
                    ->visible(false)
                    ->label(__('Lieferkosten'))
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('total_cost')
                    ->label(__('MwSt.Grundlage'))
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('tax')
                    ->label(__('Steuersatz'))
                    ->getStateUsing(function ($record) {
                        return $record->tax->tax_rate.'%' ?? null;
                    })
                    ->alignEnd(),

                TextColumn::make('total_cost_with_tax')
                    ->label(__('Gesamtbetrag'))
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label(__('Erstellt am'))
                    ->sortable()
                    ->date(),

                TextColumn::make('internal_comment')
                    ->label(__('Kommentar (intern)'))
                    ->sortable(),

                IconColumn::make('sent_to_customer')
                    ->label(__('Angebot versendet'))
                    ->boolean()
                    ->falseColor('text-gray-400')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return $record->sent_to_customer ? true : false;
                    }),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('quotation_number')
                    ->label(__('Angebotsnummer'))
                    ->placeholder(__('Nach Angebotsnummer suchen'))
                    ->options(PimQuotationResourceFormService::getFilterQuotationNumberOptions())
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('edit')
                    ->label(__('bearbeiten'))
                    ->icon('heroicon-o-pencil')
                    ->url(fn (PimQuotation $record) => PimQuotationResource::getUrl('edit', ['record' => $record->id])),

                Action::make('mark_accepted')
                    ->label(__('Als angenommen markieren'))
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($record) {
                        $record->status = PimQuotationStatus::ACCEPTED;
                        $record->save();
                        Notification::make()->success()->title(__('Status aktualisiert'))->send();
                    }),

            ]);
    }
}
