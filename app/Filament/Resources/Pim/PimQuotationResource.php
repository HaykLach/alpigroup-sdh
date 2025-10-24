<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimCustomerType;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Enums\Pim\PimQuatationValidityPeriodUnit;
use App\Enums\Pim\PimQuotationStatus;
use App\Filament\Resources\Pim;
use App\Filament\Resources\Pim\PimQuotationResource\RelationManagers\PimProductInventoryRelationManager;
use App\Filament\Resources\Pim\PimQuotationResource\RelationManagers\PimProductRelationManager;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Filament\Services\PimQuotationResourceService;
use App\Models\Pim\PimQuotation;
use App\Models\Pim\PimQuotationTemplate;
use App\Models\Pim\PimTax;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class PimQuotationResource extends Resource
{
    protected static ?string $model = PimQuotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Quotation';

    protected static ?string $modelLabel = 'Quotation';

    public static function getNavigationLabel(): string
    {
        return __('Angebote');
    }

    public static function getModelLabel(): string
    {
        return __('Angebot');
    }

    public static function getLabel(): string
    {
        return __('Angebot');
    }

    public static function getPluralLabel(): string
    {
        return __('Angebote');
    }

    public static function getNavigationBadge(): ?string
    {
        $query = PimQuotation::query()->withoutTrashed();

        $user = auth()->user();
        $query = PimQuotationResourceFormService::queryPerAgent($query, $user);

        return (string) $query->count();
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Section::make(function ($record) {
                    if (! $record) {
                        return __('Neues Angebot');
                    }

                    return __('Angebot').' '.__('Nr.: ').$record->formatted_quotation_number;
                })
                    ->collapsible(false)
                    ->schema([

                        ...PimQuotationResourceFormService::getAgentsSelect($user, required: true, live: true),

                        ...PimQuotationResourceFormService::getCustomersSelect(),

                        Hidden::make('pim_lead_id')
                            ->label(__('Lead'))
                            ->required()
                            ->afterStateHydrated(function (Set $set) {
                                $leadId = request()->get('pim_lead_id');
                                if ($leadId) {
                                    $set('pim_lead_id', $leadId);
                                }
                            }),

                        Select::make('quotation_template_selector')
                            ->label(__('Angebotsvorlage'))
                            ->visibleOn('create')
                            ->columnSpan(2)
                            ->reactive()
                            ->required()
                            ->placeholder(__('Vorlage wählen'))
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                                if ($state !== null) {
                                    $quotation = PimQuotationResourceService::getPimQuotationTemplate($state);

                                    $set('validity_period_value', $quotation->validity_period_value);
                                    $set('validity_period_unit', $quotation->validity_period_unit);

                                    PimQuotationResource::updateValidityPeriod($get, $set);

                                    foreach ($quotation->content as $key => $text) {
                                        $set('content.'.$key, $text);
                                    }

                                }
                            })
                            ->options(PimQuotationTemplate::query()->get()->pluck('summarized_label', 'id')),

                        Placeholder::make('template_preview')
                            ->hiddenLabel()
                            ->visibleOn('create')
                            ->columnSpan(2)
                            ->content(function ($get) {
                                $quotationId = $get('quotation_template_selector');
                                if ($quotationId) {
                                    $quotation = PimQuotationResourceService::getPimQuotationTemplate($quotationId);

                                    return new HtmlString(
                                        view('filament.partials.quotation-template-preview', [
                                            'quotation' => $quotation,
                                        ])->render());
                                }

                                return new HtmlString;
                            }),

                        ...PimQuotationResourceFormService::getFormValidityPeriod(),

                        DatePicker::make('date')
                            ->label(__('Datum'))
                            ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                            ->required()
                            ->default(Carbon::now())
                            ->live()
                            ->afterStateUpdated(fn (Get $get, callable $set) => PimQuotationResource::updateValidityPeriod($get, $set)),

                        DatePicker::make('validity_period')
                            ->label(__('Gültigkeitsdauer'))
                            ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                            ->readOnly()
                            ->required()
                            ->default(Carbon::now()->addMonths(2)),

                        Select::make('status')
                            ->label(__('Status'))
                            ->options(function () {
                                return collect(PimQuotationStatus::cases())
                                    ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()]);
                            })
                            ->disableOptionWhen(function (string $value, string $label, $component) {
                                $record = $component->getLivewire()->record ?? null;

                                if ($record && $record->status !== PimQuotationStatus::DRAFT) {
                                    return $value === PimQuotationStatus::DRAFT->value;
                                }

                                return false;
                            })
                            ->default(PimQuotationStatus::DRAFT->value)
                            ->hiddenOn('create')
                            ->required(),

                        Select::make('pim_tax_id')
                            ->disabled(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                            ->relationship(
                                name: 'tax',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereNotNull('position')->orderBy('position')
                            )
                            ->getOptionLabelFromRecordUsing(fn (PimTax $record) => "{$record->tax_rate}%")
                            ->default(fn () => PimTax::query()->where('is_default', '=', true)->first()?->id)
                            ->label(__('Steuersatz'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(PimQuotationResourceService::getCalcTotalLiveFnc()),

                        ...PimQuotationResourceFormService::getFormAdditional(),

                    ])->columns(2),

                ...PimQuotationResourceFormService::getFormTextContent(true),
                ...PimQuotationResourceFormService::getFormCalculation(),

                Section::make(__('Posten'))
                    ->visible(PimQuotationResourceFormService::getDisabledFormIfNotDraftFnc())
                    ->collapsible()
                    ->schema([
                        Placeholder::make('quotation_items_preview')
                            ->hiddenLabel()
                            ->columnSpan(2)
                            ->content(function ($record) {
                                return new HtmlString(
                                    view('filament.partials.quotation-template-preview', [
                                        'quotation' => $record,
                                    ])->render());
                            }),
                    ]),

            ]);
    }

    public static function getSelectCustomerOptions(?string $id = null, ?string $search = null): Collection
    {
        $customers = collect();
        if ($id === null) {
            return $customers;
        }
        $crmCustomers = PimResourceCustomerService::getSelectOptions(PimCustomerType::CRM_CUSTOMER, $id, $search);
        $regularCustomers = PimResourceCustomerService::getSelectOptions(PimCustomerType::CUSTOMER, $id, $search);

        if ($crmCustomers->isNotEmpty()) {
            $customers = $customers->merge($crmCustomers);
        }

        if ($regularCustomers->isNotEmpty()) {
            $customers = $customers->merge($regularCustomers);
        }

        return $customers;
    }

    public static function table(Table $table): Table
    {
        return PimQuotationResourceFormService::getTable($table)
            ->heading(null)
            ->actions([
                EditAction::make()
                    ->label(__('bearbeiten')),
                DeleteAction::make()
                    ->label(__('entfernen')),
                RestoreAction::make()
                    ->label(__('wiederherstellen')),

                Action::make('mark_accepted')
                    ->label(__('Als angenommen markieren'))
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($record) {
                        $record->status = PimQuotationStatus::ACCEPTED;
                        $record->save();
                        Notification::make()->success()->title(__('Status aktualisiert'))->send();
                    }),

            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('entfernen')),
                    RestoreBulkAction::make()
                        ->label(__('wiederherstellen')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(__('Posten'), [
                PimProductRelationManager::class,
            ]),
            RelationGroup::make(__('Produktauswahl'), [
                PimProductInventoryRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pim\PimQuotationResource\Pages\ListPimQuotations::route('/'),
            'create' => Pim\PimQuotationResource\Pages\CreatePimQuotation::route('/create'),
            'edit' => Pim\PimQuotationResource\Pages\EditPimQuotation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'agents',
                'customers',
                'products',
                'products.product.media',
                'tax',
                'parent',
                'children',
                'lead',
            ])
            ->orderBy('quotation_number', 'desc')
            ->orderBy('version', 'desc');

        $user = auth()->user();
        $query = PimQuotationResourceFormService::queryPerAgent($query, $user);

        return $query;
    }

    public static function updateValidityPeriod(Get $get, callable $set): void
    {
        $validityPeriodValue = $get('validity_period_value');
        $validityPeriodUnit = $get('validity_period_unit');
        if (empty($validityPeriodValue) || empty($validityPeriodUnit)) {
            return;
        }

        if ($validityPeriodUnit instanceof PimQuatationValidityPeriodUnit) {
            $validityPeriodUnit = $validityPeriodUnit->value;
        }

        $endDate = Carbon::parse($get('date'))
            ->add($validityPeriodValue.' '.$validityPeriodUnit)
            ->format('Y-m-d');

        $set('validity_period', $endDate);
    }
}
