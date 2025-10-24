<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimFormSection;
use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOptionTranslation;
use App\Services\Pim\PimTranslationService;
use App\Services\Pim\PropertyGroup\Form\PimColor;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PimPropertyGroupResource extends Resource
{
    protected static ?string $model = PimPropertyGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::SETTINGS->value;

    protected static ?string $navigationLabel = 'Properties';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        $translatable = $form->model['custom_fields']['translatable'];

        return $form
            ->schema(self::getFormElements(true, $translatable))
            ->columns(1);
    }

    public static function getFormElements(bool $edit, bool $propertyGroupIsTranslatable = false)
    {
        if (! $edit) {
            $translatableField = Fieldset::make('custom_fields.translatable')
                ->label('Translatable')
                ->schema([
                    Toggle::make('custom_fields.translatable')
                        ->default(false),
                    // ->disabled(fn (Get $get): bool => !in_array($get('custom_fields.form.type'), [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::TEXT->name, PimFormType::TEXTAREA->name, PimFormType::URL->name])),
                ]);
        } else {
            $translatableField = Hidden::make('custom_fields.translatable')
                ->default($propertyGroupIsTranslatable);
        }

        // options should countain names of the cases keyed by their name
        $formTypeOptions = array_combine(
            array_map(fn ($case) => $case->name, PimFormType::cases()),
            array_map(fn ($case) => $case->name, PimFormType::cases())
        );

        $mappingTypeOptions = array_combine(
            array_map(fn ($case) => $case->name, PimMappingType::cases()),
            array_map(fn ($case) => $case->name, PimMappingType::cases())
        );

        return [
            Select::make('custom_fields.type')
                ->label('Mapping type')
                ->options($mappingTypeOptions)
                ->required(),

            Select::make('custom_fields.form.type')
                ->options($formTypeOptions)
                ->required()
                ->live(),

            TextInput::make('name')
                ->required(),

            TextInput::make('description')
                ->label('Label')
                ->required(),

            Fieldset::make('custom_fields.form.type.select')
                ->label('Type configuration')
                ->visible(fn (Get $get): bool => in_array($get('custom_fields.form.type'), [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name]))
                ->schema(fn (Get $get) => [
                    self::getOptionsForm($edit, $propertyGroupIsTranslatable, $get('custom_fields.form.type')),
                ]),

            Fieldset::make('custom_fields.form.type.bool')
                ->label('Type configuration')
                ->visible(fn (Get $get): bool => $get('custom_fields.form.type') === PimFormType::BOOL->name)
                ->schema([
                    Toggle::make('custom_fields.form.default.state')
                        ->label('Default State')
                        ->default(true),
                ]),

            Select::make('custom_fields.form.config.format')
                ->label('Number type')
                ->visible(fn (Get $get): bool => $get('custom_fields.form.type') === PimFormType::NUMBER->name)
                ->options([
                    'integer' => 'Integer (example: 1, 2, 3)',
                    'float' => 'Float (example: 1.1, 2.2, 3.3)',
                ])
                ->required(),

            // add select for enum PimFormSection
            Select::make('custom_fields.section')
                ->options(PimFormSection::class)
                ->label('Section')
                ->required(),

            Fieldset::make('custom_fields.fileupload.new')
                ->label('Validation')
                ->reactive()
                ->visible(fn (Get $get, $record): bool => $get('custom_fields.form.type') == PimFormType::FILEUPLOAD->name && ! $record)
                ->schema([
                    TextInput::make('custom_fields.collection')
                        ->label('collection id')
                        ->required(),
                    TextInput::make('custom_fields.form.validation.acceptedFileTypes')
                        ->label('Accepted File Types')
                        ->required(),
                ]),

            Fieldset::make('custom_fields.fileupload.edit')
                ->label('Validation')
                ->visible(fn (Get $get, $record): bool => $get('custom_fields.form.type') == PimFormType::FILEUPLOAD->name && $record)
                ->schema([
                    TextInput::make('custom_fields.collection')
                        ->label('collection id')
                        ->required()
                        ->readOnly(),
                    TextInput::make('custom_fields.form.validation.acceptedFileTypes')
                        ->label('Accepted File Types')
                        ->required()
                        ->readOnly(),
                ]),

            Fieldset::make('custom_fields.form.validation')
                ->label('Validation')
                ->schema([
                    Toggle::make('custom_fields.form.validation.required')
                        // ->hidden(fn (Get $get): bool => $get('custom_fields.form.type') === PimFormType::BOOL->name)
                        ->default(fn (Get $get): bool => $get('custom_fields.form.type') !== PimFormType::BOOL->name),

                    Toggle::make('filterable')
                        ->default(true),
                ]),

            Fieldset::make('custom_fields.form.editable')
                ->label('Edit')
                ->schema([
                    Toggle::make('custom_fields.edit.main'),

                    Toggle::make('custom_fields.edit.variant')
                        ->default(true),
                ]),

            Fieldset::make('custom_fields.form.readonly')
                ->label('Readonly')
                ->schema([
                    Toggle::make('custom_fields.form.readonly')
                        ->default(false),
                ]),

            $translatableField,
        ];

    }

    private static function getOptionsForm(bool $edit, bool $propertyGroupIsTranslatable, ?string $formType = null): Repeater
    {
        $translationService = new PimTranslationService;
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();
        $extraLangIdCodes = $translationService->getExtraLanguagesShort();

        $component = Repeater::make('typeSelectOptions')
            ->relationship(name: 'groupOptions')
            ->orderColumn('position')
            ->minItems(1)
            ->required()
            ->reorderable();

        $textField = TextInput::make('name')->required();

        $colorField = $formType === PimFormType::COLOR->name ? [
            ColorPicker::make('custom_fields.'.PimColor::CUSTOM_FIELD_KEY)
                ->label('Color')
                ->default('#FFFFFF'),
        ] : [];

        if (! $edit) {
            // new
            return $component
                ->schema([
                    ...$colorField,
                    $textField,
                ])
                ->saveRelationshipsUsing(function ($record, $state) use ($extraLangIdCodes) {

                    $position = 0;
                    $translatable = $record->custom_fields['translatable'];

                    foreach ($state as $option) {

                        $position++;

                        $option = PimPropertyGroupOption::create([
                            'name' => $option['name'],
                            'position' => $position,
                            'group_id' => $record->id,
                        ]);

                        if ($translatable) {
                            $extraLangIdCodes->each(function ($codeShort, $languageId) use ($position, $option) {
                                PimPropertyGroupOptionTranslation::create([
                                    'language_id' => $languageId,
                                    'name' => $option['name'],
                                    'position' => $position,
                                    'property_group_option_id' => $option->id,
                                ]);
                            });
                        }
                    }
                });

        } else {
            // edit
            if (! $propertyGroupIsTranslatable) {

                return $component
                    ->schema([
                        ...$colorField,
                        $textField,
                    ]);

            } else {
                return $component
                    ->schema([
                        ...$colorField,

                        TextInput::make('name')
                            ->label('Name ('.$defaultLangCode.')')
                            ->required(),

                        Repeater::make('translations')
                            ->relationship('translations')
                            ->schema([
                                $textField,
                                PimTranslationService::getTranslateActionsWithinRepeater('name', $defaultLangCode, $extraLangIdCodes),
                            ])
                            ->maxItems($extraLangIdCodes->count())
                            ->itemLabel(function (array $state) use ($extraLangIdCodes): ?string {
                                return $extraLangIdCodes[$state['language_id']] ?? null;
                            })
                            ->deletable(false),
                    ])
                    ->addable(false);

            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->defaultSort('custom_fields.type')
            ->columns([
                Tables\Columns\TextColumn::make('custom_fields.type')
                    ->label('Mapping Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('custom_fields.form.type')
                    ->label('Type')
                    ->formatStateUsing(function (PimPropertyGroup $group): string {
                        return PimFormType::tryFromName($group->custom_fields['form']['type'])->name;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('groupOptions.name')
                    // set max width for the column
                    ->words(8)
                    ->label('Options')
                    // force sort groupOptions by position
                    ->getStateUsing(function (PimPropertyGroup $record): string {
                        $record->groupOptions = $record->groupOptions->sortBy('position');

                        return $record->groupOptions->map(function ($option) {
                            return $option->name;
                        })->implode(', ');
                    }),

                Tables\Columns\IconColumn::make('custom_fields.translatable')
                    ->label('translatable')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('custom_fields.form.validation.required')
                    ->label('required')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('filterable')
                    ->label('Filterable')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('custom_fields.edit.main')
                    ->label('edit main')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('custom_fields.edit.variant')
                    ->label('edit variant')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('custom_fields.section')
                    ->label('Section')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('custom_fields.form.readonly')
                    ->label('readonly')
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('addOption')
                    ->label('add option')
                    ->icon('heroicon-o-plus')
                    ->visible(function (PimPropertyGroup $record): bool {
                        $type = in_array($record->custom_fields['form']['type'], [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name]);
                        if (! $type) {
                            return false;
                        }

                        return $record->custom_fields['translatable'];
                    })
                    ->form([
                        TextInput::make('name')
                            ->required(),
                    ])
                    ->action(function (PimPropertyGroup $record, array $data) {
                        $translationService = new PimTranslationService;
                        $extraLangIdCodes = $translationService->getExtraLanguagesShort();

                        $position = $record->groupOptions->count() + 1;
                        $name = $data['name'];

                        // add new record
                        $option = PimPropertyGroupOption::create([
                            'name' => $name,
                            'position' => $position,
                            'group_id' => $record->id,
                        ]);

                        $extraLangIdCodes->each(function ($langCode, $languageId) use ($name, $option, $position) {
                            PimPropertyGroupOptionTranslation::create([
                                'language_id' => $languageId,
                                'name' => $name,
                                'position' => $position,
                                'property_group_option_id' => $option->id,
                            ]);
                        });
                    }),
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => PimPropertyGroupResource\Pages\ListPimPropertyGroups::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['groupOptions']);
    }
}
