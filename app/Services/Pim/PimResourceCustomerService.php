<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Enums\Pim\PimCustomerType;
use App\Models\Pim\Customer\PimAgent;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\Customer\PimCustomerBranch;
use App\Models\Pim\Customer\PimCustomerSalutation;
use App\Models\Pim\Customer\PimCustomerTaxGroup;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PimResourceCustomerService
{
    public static function getTableColElements(PimCustomerType $type, bool $showAgent = true): array
    {
        return [
            Tables\Columns\TextColumn::make('quotations_count')
                ->label(__('Angebote'))
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('customers_count_db')
                ->label(__('Kunden'))
                ->toggleable()
                ->sortable()
                ->hidden(fn () => $type === PimCustomerType::CUSTOMER),

            Tables\Columns\TextColumn::make('agent.full_name')
                ->visible($showAgent)
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('Vertrieb')),

            Tables\Columns\TextColumn::make('branch.name')
                ->label(__('Branche'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->hidden(fn () => $type === PimCustomerType::AGENT)
                ->sortable(),

            Tables\Columns\TextColumn::make('custom_fields.'.PimCustomerCustomFields::COMPANY_NAME->value)
                ->label(__('Firmenname'))
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.company_name'))) LIKE ?", ['%'.strtolower($search).'%']);
                }, isIndividual: true)
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('salutation.display_name')
                ->toggleable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('Anrede')),

            Tables\Columns\TextColumn::make('first_name')
                ->label(__('Vorname'))
                ->toggleable()
                ->searchable(isIndividual: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('last_name')
                ->label(__('Nachname'))
                ->toggleable()
                ->searchable(isIndividual: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('email')
                ->label(__('Email'))
                ->toggleable()
                ->searchable(isIndividual: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('custom_fields.'.PimCustomerCustomFields::VAT_ID->value)
                ->label(__('MwSt. -Nummer'))
                ->searchable(isIndividual: true)
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('custom_fields.'.PimCustomerCustomFields::FISCAL_CODE->value)
                ->label(__('Steuernummer'))
                ->searchable(isIndividual: true)
                ->toggleable()
                ->sortable(),

            ImageColumn::make('profile_picture')
                ->label(__('Profilbild'))
                ->getStateUsing(fn ($record) => $record->getMedia(PimCustomer::getMediaCollectionPreviewName())->map(fn ($media) => $media->getUrl())->toArray())
                ->sortable()
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true),

            /*
            Tables\Columns\TextColumn::make('taxGroup.name')
                ->label(__('MwStGruppe'))
                ->sortable(),
            */
        ];
    }

    public static function setCustomFieldTypeData(array $data, PimCustomerType $type): array
    {
        $fields = [
            PimCustomerCustomFields::COMPANY_NAME->value,
            PimCustomerCustomFields::FISCAL_CODE->value,
            PimCustomerCustomFields::VAT_ID->value,
            PimCustomerCustomFields::AGENT_ID->value,
        ];

        foreach ($fields as $field) {
            $data['custom_fields'][$field] = $data['custom_fields'][$field] ?? null;
        }

        $data['custom_fields'][PimCustomerCustomFields::TYPE->value] = $type->value;

        return $data;
    }

    protected static function fncVisibleWhenCompany(string $privateId): callable
    {
        return fn (Get $get): bool => $get('branch_id') !== $privateId;
    }

    protected static function fncVisibleWhenPrivate(string $privateId): callable
    {
        return fn (Get $get): bool => $get('branch_id') === $privateId;
    }

    public static function getFormElementsSectionDetails(?PimCustomerType $type = PimCustomerType::CUSTOMER, ?bool $showBlockedForm = false): array
    {
        $privateId = PimCustomerBranch::query()->where('name', 'Privater Kunde')->value('id');
        $defaultSalutationId = PimCustomerSalutation::query()->where('salutation_key', '=', 'AN')->value('id');

        $blockedForm = [];
        if ($showBlockedForm) {
            $blockedForm[] = Toggle::make('custom_fields.'.PimCustomerCustomFields::BLOCKED->value)
                ->label(__('Blockiert'));
        }

        return [
            PimResourceCustomerService::getSelectBranch($type),

            // PimResourceCustomerService::getSelectTaxGroup(),

            TextInput::make('custom_fields.'.PimCustomerCustomFields::COMPANY_NAME->value)
                ->visible(PimResourceCustomerService::fncVisibleWhenCompany($privateId))
                ->required()
                ->label(__('Firmenname')),

            PimResourceCustomerService::getSelectSalutation()
                ->default($defaultSalutationId),

            TextInput::make('first_name')
                ->label(__('Vorname'))
                ->required(),

            TextInput::make('last_name')
                ->label(__('Nachname'))
                ->required(),

            TextInput::make('email')
                ->label(__('Email'))
                ->email()
                ->required(),

            TextInput::make('custom_fields.'.PimCustomerCustomFields::VAT_ID->value)
                ->visible(PimResourceCustomerService::fncVisibleWhenCompany($privateId))
                ->required()
                ->label(__('MwSt. -Nummer')),

            TextInput::make('custom_fields.'.PimCustomerCustomFields::FISCAL_CODE->value)
                ->visible(PimResourceCustomerService::fncVisibleWhenPrivate($privateId))
                ->label(__('Steuernummer')),

            ...$blockedForm,
        ];
    }

    public static function getFormElements(?PimCustomerType $type = PimCustomerType::CUSTOMER): array
    {
        return [
            Split::make([
                Section::make([
                    Section::make(__('Details'))
                        ->schema([
                            ...PimResourceCustomerService::getFormElementsSectionDetails($type),
                        ]),
                ]),
                Section::make([
                    Section::make(__('Profilbild'))
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('profile_picture')
                                ->label('')
                                ->collection(PimCustomer::getMediaCollectionPreviewName())
                                ->openable()
                                ->downloadable()
                                ->image()
                                ->imageEditor()
                                ->maxFiles(1),
                        ]),
                ]),
            ])->from('md'),
        ];
    }

    public static function getSelectSalutation(): Select
    {
        return Select::make('salutation_id')
            ->label(__('Anrede'))
            ->options(PimCustomerSalutation::query()->orderBy('display_name')->get()->mapWithKeys(function ($item) {
                return [$item->id => $item->display_name.' / '.$item->letter_name];
            })->toArray()
            );
    }

    public static function getSelectBranch(?PimCustomerType $type = PimCustomerType::CUSTOMER): Select
    {
        return
            Select::make('branch_id')
                ->label(__('Branche'))
                ->required()
                ->live()
                ->visible(fn (): bool => $type !== PimCustomerType::AGENT)
                ->default(PimCustomerBranch::query()->where('code', '=', 13)->value('id'))
                ->options(PimCustomerBranch::query()->pluck('name', 'id')->toArray());
    }

    public static function getSelectTaxGroup(): Select
    {
        return
            Select::make('tax_group_id')
                ->label(__('MwStGruppe'))
                ->required()
                ->options(PimCustomerTaxGroup::query()->pluck('name', 'id')->toArray());
    }

    public static function getSelectUserCreateOptions(): Collection
    {
        return PimAgent::query()
            ->with('addresses')
            ->whereNotIn('email', User::query()->select('email')->pluck('email'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (PimCustomer $customer) => PimResourceCustomerService::getSelectOptionsMapping($customer));
    }

    public static function getSelectOptions(PimCustomerType $type, ?string $agentId = null, ?string $search = null): Collection
    {
        $query = PimCustomer::where('custom_fields->'.PimCustomerCustomFields::TYPE->value, $type->value)
            ->with('addresses');

        if ($agentId) {
            $query = $query->byAgentId($agentId);
        }

        if ($search) {
            $query = $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('custom_fields->'.PimCustomerCustomFields::COMPANY_NAME->value, 'like', '%'.$search.'%');
            });
        }

        return $query->get()
            ->mapWithKeys(fn (PimCustomer $customer) => PimResourceCustomerService::getSelectOptionsMapping($customer));
    }

    public static function getSelectOptionsMapping(PimCustomer $customer): array
    {
        $address = $customer->main_address;
        if ($address) {
            return [
                $customer->id => $customer->getSummarizedTitleAttribute().' | '.$address->getFormattedAddressAttribute(),
            ];
        }

        return [
            $customer->id => $customer->getSummarizedTitleAttribute(),
        ];
    }

    public static function createCrmCustomer(array $data, string $agentId): PimCustomer
    {
        $customFields = [
            PimCustomerCustomFields::TYPE->value => PimCustomerType::CRM_CUSTOMER,
            PimCustomerCustomFields::COMPANY_NAME->value => $data['custom_fields']['company_name'] ?? null,
            PimCustomerCustomFields::FISCAL_CODE->value => $data['custom_fields']['fiscal_code'] ?? null,
            PimCustomerCustomFields::VAT_ID->value => $data['custom_fields']['vat_id'] ?? null,
            PimCustomerCustomFields::AGENT_ID->value => $agentId,
            PimCustomerCustomFields::BLOCKED->value => false,
        ];

        $customer = PimCustomer::create([
            'branch_id' => $data['branch_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'salutation_id' => $data['salutation_id'],
            // 'tax_group_id' => $data['tax_group_id'],
            'custom_fields' => $customFields,
        ]);

        if ($data['address']) {
            $customer->addresses()->create([
                'zipcode' => $data['zipcode'],
                'country_id' => $data['country_id'],
                'salutation_id' => $data['salutation_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'city' => $data['city'],
                'street' => $data['street'],
                'additional_address_line_1' => $data['additional_address_line_1'] ?? null,
                'region_id' => $data['region_id'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
            ]);
        }

        return $customer;
    }
}
