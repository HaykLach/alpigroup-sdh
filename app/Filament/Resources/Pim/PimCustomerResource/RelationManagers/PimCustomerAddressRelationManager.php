<?php

namespace App\Filament\Resources\Pim\PimCustomerResource\RelationManagers;

use App\Enums\Pim\PimCustomerType;
use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Customer\PimCustomerAddress;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use PeterColes\Countries\CountriesFacade;

class PimCustomerAddressRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Adressen');
    }

    public static function getModelLabel(): string
    {
        return __('Adresse');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Section::make(__('Person / Unternehmen'))
                        ->schema([
                            PimResourceCustomerService::getSelectSalutation()
                                ->default($this->getOwnerRecord()->salutation_id),

                            TextInput::make('first_name')
                                ->label(__('Vorname'))
                                ->default(function ($record) {
                                    if (! $record) {
                                        return $this->getOwnerRecord()->first_name;
                                    }

                                    return null;
                                })
                                ->required(),

                            TextInput::make('last_name')
                                ->label(__('Nachname'))
                                ->default(function ($record) {
                                    if (! $record) {
                                        return $this->getOwnerRecord()->last_name;
                                    }

                                    return null;
                                })
                                ->required(),

                            TextInput::make('phone_number')
                                ->label(__('Telefon')),
                        ]),
                ]),
                Section::make([
                    Section::make(__('Adresse'))
                        ->schema(PimCustomerAddressRelationManager::getFormElements()),
                ]),
            ]);
    }

    public static function getFormElements(): array
    {
        return [
            Select::make('country_id')
                ->options(PimCustomerAddressRelationManager::getCountriesOptions())
                ->default(PimCustomerAddressRelationManager::getUserCountryId())
                ->searchable()
                ->label(__('Land'))
                ->required(),
            TextInput::make('zipcode')
                ->label(__('PLZ'))
                ->required(),
            TextInput::make('city')
                ->label(__('Stadt'))
                ->required(),
            TextInput::make('street')
                ->label(__('Straße'))
                ->required(),
            TextInput::make('additional_address_line_1')
                ->label(__('Adresszusatz')),
            Select::make('region_id')
                ->relationship('region', 'display_name')
                ->searchable()
                ->preload()
                ->label(__('Region')),

        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('formatted_address')
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('salutation.display_name')
                    ->label(__('Anrede')),

                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->label(__('Vorname')),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->label(__('Nachname')),

                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->label(__('Telefon')),

                Tables\Columns\TextColumn::make('country.name')
                    ->label(__('Land')),

                Tables\Columns\TextColumn::make('zipcode')
                    ->searchable()
                    ->label(__('PLZ')),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->label(__('Stadt')),
                Tables\Columns\TextColumn::make('street')
                    ->searchable()
                    ->label(__('Straße')),
                Tables\Columns\TextColumn::make('additional_address_line_1')
                    ->searchable()
                    ->label(__('Adresszusatz')),
                Tables\Columns\TextColumn::make('region.display_name')
                    ->searchable()
                    ->label(__('Region')),
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
                Tables\Actions\EditAction::make()
                    ->label(__('bearbeiten')),
                Tables\Actions\Action::make('duplicate')
                    ->label('duplizieren')
                    ->icon('heroicon-o-bookmark')
                    ->action(function (PimCustomerAddress $record) {
                        $newRecord = $record->replicate();
                        $newRecord->save();
                    })
                    ->hidden(fn (PimCustomerAddress $record) => $record->customers()->first()->type !== PimCustomerType::CRM_CUSTOMER->value)
                    ->color('secondary'), // Optional styling
                Tables\Actions\DeleteAction::make()
                    ->label('entfernen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('entfernen'),
                ]),
            ]);
    }

    protected static function getCountriesOptions(): array
    {
        $countriesInTable = CountriesFacade::lookup('en');
        $countriesInFrontendLang = CountriesFacade::lookup('de');

        $options = [];
        PimCountry::whereIn('iso', $countriesInTable->keys())
            ->get()
            ->each(function (PimCountry $country) use (&$options, $countriesInFrontendLang) {
                $options[$country->id] = $countriesInFrontendLang[$country->iso];
            });

        natsort($options);

        return $options;
    }

    protected static function getUserCountryId(): string
    {
        $user = auth()->user();
        $countryId = $user->agentMainAddress()?->country_id;
        if ($countryId === null) {
            return PimCountry::query()->where('iso', '=', 'AT')->first()->id;
        }

        return $countryId;
    }
}
