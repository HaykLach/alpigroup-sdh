<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimCustomerType;
use App\Enums\Pim\PimLeadSource;
use App\Enums\Pim\PimLeadStatus;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\Pim\PimCustomerResource\RelationManagers\PimQuotationRelationManager;
use App\Filament\Resources\Pim\PimLeadResource\Pages;
use App\Filament\Services\PimLeadResourceFormService;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Models\Pim\PimLead;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PimLeadResource extends Resource
{
    protected static ?string $model = PimLead::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $modelLabel = 'Lead';

    public static function getNavigationLabel(): string
    {
        return __('Leads');
    }

    public static function getModelLabel(): string
    {
        return __('Lead');
    }

    public static function getLabel(): string
    {
        return __('Lead');
    }

    public static function getPluralLabel(): string
    {
        return __('Leads');
    }

    public static function getNavigationBadge(): ?string
    {
        $query = PimLead::query()->withoutTrashed();

        $user = auth()->user();
        $query = PimQuotationResourceFormService::queryPerAgent($query, $user);

        return (string) $query->count();
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Section::make()
                    ->schema([

                        Select::make('pim_agent_id')
                            ->label(__('Vertrieb'))
                            ->placeholder('-- '.__('auswählen').' --')
                            ->searchPrompt(__('Tippen um zu suchen'))
                            ->live()
                            ->relationship('agent', 'id')
                            ->options(fn () => $user->hasRole('agent')
                                ? PimResourceCustomerService::getSelectOptionsMapping($user->agent)
                                : PimResourceCustomerService::getSelectOptions(PimCustomerType::AGENT))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->summarized_title)
                            ->default(fn () => $user->hasRole('agent') ? $user->agent->id : null)
                            ->selectablePlaceholder(fn () => ! $user->hasRole('agent'))
                            ->searchable(fn () => ! $user->hasRole('agent'))
                            ->required()
                            ->preload(),

                        Select::make('pim_customer_id')
                            ->label(__('Kunde'))
                            ->placeholder('-- '.__('auswählen').' --')
                            ->searchPrompt(__('Tippen um zu suchen'))
                            ->relationship('customer')
                            ->disabled(function (Get $get, $record) {
                                if ($record === null) {
                                    return $get('pim_agent_id') === null;
                                }

                                return true;
                            })
                            ->options(function (Get $get) {
                                return PimQuotationResource::getSelectCustomerOptions($get('pim_agent_id'));
                            })
                            ->getSearchResultsUsing(function (string $search, Get $get) {
                                return PimQuotationResource::getSelectCustomerOptions($get('pim_agent_id'), $search);
                            })
                            ->suffixAction(PimQuotationResourceFormService::getCustomersSelectSuffixAction('pim_agent_id', 'pim_customer_id'))
                            ->searchable()
                            ->required()
                            ->preload(),

                        Select::make('status')
                            ->label(__('Status'))
                            ->placeholder('-- '.__('auswählen').' --')
                            ->options(PimLeadStatus::toArray())
                            ->default(PimLeadStatus::OPEN->value)
                            ->required(),

                        Select::make('source')
                            ->label(__('Quelle'))
                            ->placeholder('-- '.__('auswählen').' --')
                            ->options(PimLeadSource::toArray())
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('Notizen'))
                            ->rows(5),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PimLeadResourceFormService::getTable($table)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'agent',
                'agent.addresses',
                'customer',
                'customer.addresses',
                'quotations',
            ]);

        $user = auth()->user();
        $query = PimQuotationResourceFormService::queryPerAgent($query, $user);

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            PimQuotationRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPimLead::route('/'),
            'create' => Pages\CreatePimLead::route('/create'),
            'edit' => Pages\EditPimLead::route('/{record}/edit'),
        ];
    }
}
