<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimMappingType;
use App\Jobs\ProcessPimTranslation;
use App\Models\Pim\PimLanguage;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Models\Pim\Product\PimProductManufacturerTranslation;
use App\Models\Pim\Product\PimProductTranslation;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PimPropertyGroupTranslation;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOptionTranslation;
use App\Services\Pim\PropertyGroup\Form\PimColor;
use App\Services\Translation\OpenAiTranslationService;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Support\Collection;
use Throwable;

class PimTranslationService
{
    private string $defaultLangCode;

    private Collection $supportedLangCodes;

    private Collection $extraLangCodes;

    private const string INSTRUCTION_FOR_PRODUCT_NAME = 'instructionProductName';

    public function __construct()
    {
        $this->setDefaultLanguageCode();
        $this->defaultLangCode = $this->getDefaultLanguageCode();
        $this->supportedLangCodes = $this->getSupportedLangCodes();
        $this->extraLangCodes = $this->stripDefaultLangCode();
    }

    public function getDefaultLanguageCode(): string
    {
        return $this->defaultLangCode;
    }

    protected function setDefaultLanguageCode(): void
    {
        $this->defaultLangCode = config('language.locales.default');
    }

    public function getExtraLanguages(): Collection
    {
        return $this->extraLangCodes;
    }

    public function getExtraLanguagesShort()
    {
        return $this->extraLangCodes
            ->map(fn ($langCode) => $this->getLanguageCodeShort($langCode));
    }

    public function getDefaultLanguageCodeShort()
    {
        return $this->getLanguageCodeShort($this->defaultLangCode);
    }

    protected function getSupportedLangCodes(): Collection
    {
        return PimLanguage::query()
            ->with('local')
            ->get()
            ->pluck('local.code', 'id');
    }

    protected function getLanguageCodeShort(string $langCode): string
    {
        return substr($langCode, 0, 2);
    }

    protected function stripDefaultLangCode()
    {
        return $this->supportedLangCodes->reject(function ($code) {
            return $code === $this->defaultLangCode;
        });
    }

    public static function getTranslatedColumn(Collection $otherLanguages, string $field, string $label, bool $inlineEdit = false, ?int $wordLimit = null): array
    {
        $components = [];

        $otherLanguages->map(function ($langCode, $langId) use (&$components, $field, $label, $wordLimit, $inlineEdit) {

            if ($inlineEdit && $field !== 'description') {
                $col = TextInputColumn::make($langCode.'_'.$field)
                    ->label($label.' ('.$langCode.')')
                    ->getStateUsing(function ($record) use ($langId, $field) {
                        return $record->translations()
                            ->where('language_id', $langId)
                            ->first()
                            ->$field;
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(false)
                    ->extraAttributes(['style' => 'min-width: 460px;']);

                $col->updateStateUsing(function ($state, $record) use ($langId, $field) {
                    $data = [
                        $field => $state,
                        'language_id' => $langId,
                    ];
                    self::saveRelationship($record, $data);
                });

            } else {
                $col = TextColumn::make($langCode.'_'.$field)
                    ->label($label.' ('.$langCode.')')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) use ($langId, $field) {
                        return $record->translations()
                            ->where('language_id', $langId)
                            ->first()
                            ->$field;
                    });

                if ($wordLimit) {
                    $col->words($wordLimit);
                }
            }

            $components[] = $col;
        });

        return $components;
    }

    public static function getBulkTranslation(PimProduct|PimProductManufacturer $record, string $fromLang, Collection $extraLangIdCodes): void
    {
        // get type of $record
        $type = $record instanceof PimProduct ? PimMappingType::PRODUCT : PimMappingType::MANUFACTURER;
        [$editableMain, $editableVariant] = PimPropertyGroupService::getEditableTranslatableTextGroups($type);

        $editable = $editableMain->merge($editableVariant);

        $text = self::collectTextToTranslate($record, $editableMain, $editableVariant);

        $extraLangIdCodes->each(function ($langCode, $langId) use ($record, $fromLang, $text, $editable, $editableVariant) {

            $data = [];
            $variantData = [];

            if ($record instanceof PimProduct) {

                $translations = [];
                foreach ($text as $hash => $value) {
                    $translations[$hash] = self::getTranslation($record::class, $fromLang, $langCode, $value);
                }

                $mainCustomFieldsTranslated = [];

                $editable->each(function (PimPropertyGroup $propertyGroup) use ($record, $translations, &$mainCustomFieldsTranslated) {
                    $fieldValue = array_key_exists($propertyGroup->id, $record['custom_fields']['properties']) ? $record['custom_fields']['properties'][$propertyGroup->id] : null;
                    if ($fieldValue !== null) {
                        $mainCustomFieldsTranslated[$propertyGroup->id] = $translations[md5($fieldValue)];
                    }
                });

                if (! $record->variations->isEmpty()) {
                    $record->variations->each(function (PimProduct $variant) use ($translations, $editableVariant, &$variantData) {
                        $editableVariant->each(function (PimPropertyGroup $propertyGroup) use ($variant, $translations, &$variantData) {
                            $fieldValue = array_key_exists($propertyGroup->id, $variant['custom_fields']['properties']) ? $variant['custom_fields']['properties'][$propertyGroup->id] : null;
                            if ($fieldValue !== null) {
                                $variantData[$variant->id][$propertyGroup->id] = $translations[md5($fieldValue)];
                            }
                        });
                    });
                }

                $data['description'] = self::getTranslation($record::class, $fromLang, $langCode, $record['description']);
                $data['custom_fields']['properties'] = $mainCustomFieldsTranslated;
            }

            $data['language_id'] = $langId;
            $data['name'] = self::getTranslation($record::class, $fromLang, $langCode, $record['name'], self::INSTRUCTION_FOR_PRODUCT_NAME);

            self::saveRelationship($record, $data, $variantData);
        });
    }

    protected static function collectTextToTranslate(PimProduct $record, Collection $editableMain, Collection $editableVariant): array
    {
        $text = [];
        if ($record->isMainProduct) {
            self::collectPropertyText(collect([$record]), $editableMain, $text);
            if (! $record->variations->isEmpty()) {
                self::collectPropertyText($record->variations, $editableVariant, $text);
            }
        } else {
            self::collectPropertyText(collect([$record]), $editableVariant, $text);
        }

        return $text;
    }

    protected static function collectPropertyText(Collection $records, $propertyGroups, &$text): void
    {
        $records->each(function ($variant) use ($propertyGroups, &$text) {
            $propertyGroups->each(function (PimPropertyGroup $propertyGroup) use ($variant, &$text) {
                $fieldValue = $variant->custom_fields['properties'][$propertyGroup->id] ?? null;
                if ($fieldValue !== null) {
                    $text[md5($fieldValue)] = $fieldValue;
                }
            });
        });
    }

    public static function saveRelationship(PimProduct|PimProductManufacturer $record, $data, array $variantCustomFieldsTranslationsData = []): void
    {
        PimResourceService::stripProvidedFormData($data);

        $record->translations()
            ->updateOrCreate(
                ['language_id' => $data['language_id']],
                $data
            );

        if ($record instanceof PimProduct) {
            $pimProductService = new PimProductService;
            if ($record->isMainProduct) {

                $variants = $pimProductService->queryProductVariants($record['id'])->get();
                $variantData = [];

                // bulk actions do not have name and description
                if (isset($data['name'])) {
                    $variantData['name'] = $data['name'];
                }

                if (isset($data['description'])) {
                    $variantData['description'] = $data['description'];
                }

                if (isset($data['custom_fields'])) {
                    $variantData['custom_fields'] = $data['custom_fields'];
                }

                foreach ($variants as $variant) {

                    if (! empty($variantCustomFieldsTranslationsData[$variant->id])) {
                        $variantData['custom_fields']['properties'] = array_merge(
                            $variantData['custom_fields']['properties'] ?? [],
                            $variantCustomFieldsTranslationsData[$variant->id] ?? []
                        );
                    }

                    $variant->translations()
                        ->updateOrCreate(
                            [
                                'language_id' => $data['language_id'],
                                'product_id' => $variant['id'],
                            ],
                            $variantData
                        );
                }

                PimMediaService::syncTranslation($record, $variants);
            }
        }
    }

    public static function getFormArray(array $forms, Collection $extraLangIdCodes): array
    {
        return $extraLangIdCodes->isEmpty() ? [] : [self::getForm($forms, $extraLangIdCodes)];
    }

    public static function getTranslateActionsNextToField(string $fieldName, string $fromLang, Collection $extraLangIdCodes): array
    {
        if (! $extraLangIdCodes->count()) {
            return [];
        }

        $actions = [];

        $extraLangIdCodes->map(function ($langCode, $langId) use (&$actions, $fromLang, $fieldName, $extraLangIdCodes) {
            $actions[] = FormAction::make('translate-'.$fieldName.'-to-'.$langCode)
                ->label('Translate to '.$langCode)
                ->action(function (Set $set, Get $get, PimProduct $record) use ($langId, $fromLang, $extraLangIdCodes, $fieldName) {
                    $id = $record->translations()
                        ->where('language_id', '=', $langId)
                        ->select('id')->first()->id;

                    $path = 'translations.record-'.$id.'.'.$fieldName;

                    $translated = self::getTranslation($record::class, $fromLang, $extraLangIdCodes[$langId], $get($fieldName));
                    $set($path, $translated);
                });
        });

        return [Actions::make($actions)];
    }

    public static function getTranslateActionsWithinRepeater(string $fieldName, string $fromLang, Collection $extraLangIdCodes): ?Actions
    {
        if (! (new GeneralSettings)->translationService_enabled) {
            return Actions::make([]);
        }

        $action = FormAction::make('translate-'.$fieldName)
            ->label('translate')
            ->action(function (Set $set, Get $get, PimProductTranslation|PimPropertyGroupOptionTranslation|PimProductManufacturerTranslation $record) use ($fieldName, $fromLang, $extraLangIdCodes) {
                switch ($record::class) {
                    case PimProductTranslation::class:
                        $class = PimProduct::class;
                        break;
                    case PimProductManufacturerTranslation::class:
                        $class = PimProductManufacturer::class;
                        break;
                    case PimPropertyGroupOptionTranslation::class:
                        $class = PimPropertyGroupOption::class;
                        break;
                }
                $translationKey = $record instanceof PimProductTranslation && $fieldName === 'name' ? self::INSTRUCTION_FOR_PRODUCT_NAME : null;
                $translated = self::getTranslation($class, $fromLang, $extraLangIdCodes[$record->language_id], $get('../../'.$fieldName), $translationKey);
                $set($fieldName, $translated);
            });

        return Actions::make([$action]);
    }

    private static function getTranslation(string $class, string $fromLang, string $toLang, ?string $input, ?string $instructionKey = null): string
    {
        if (! $input) {
            return '';
        }

        if (! (new GeneralSettings)->openai_enabled) {
            return $input;
        }

        try {
            $translation = (new OpenAiTranslationService)->translate($class, $input, $fromLang, $toLang, $instructionKey);

        } catch (Throwable $e) {
            $translation = $input;
            Notification::make()
                ->title('Translation failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $translation;
    }

    private static function getColor(string $class, string $fromLang, string $input): string
    {
        try {
            $translation = (new OpenAiTranslationService)->translate($class, $input, $fromLang, PimColor::CUSTOM_FIELD_KEY, 'instructionColorHex');

        } catch (Throwable $e) {
            $translation = $input;
            Notification::make()
                ->title('Translation failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $translation;
    }

    public static function getForm(array $forms, Collection $extraLangIdCodes): Repeater
    {
        return
            Repeater::make('translations')
                ->relationship('translations')
                ->saveRelationshipsUsing(function ($record, $state) {
                    foreach ($state as $data) {
                        self::saveRelationship($record, $data);
                    }
                })
                ->schema($forms)
                ->maxItems($extraLangIdCodes->count())
                ->itemLabel(function (array $state) use ($extraLangIdCodes): ?string {
                    return $extraLangIdCodes[$state['language_id']] ? 'Translation ('.$extraLangIdCodes[$state['language_id']].')' : null;
                })
                ->collapsible()
                ->collapsed(false)
                ->deletable(false);
    }

    public static function getBulkTranslationButtons($visible = true): array
    {
        if (! (new GeneralSettings)->translationService_enabled) {
            return [];
        }

        return [
            BulkAction::make('translate')
                ->icon('heroicon-m-pencil-square')
                ->requiresConfirmation()
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->modalCloseButton(false)
                ->color('success')
                ->action(function (Collection $records) {
                    $records->each(function (PimProduct|PimProductManufacturer $record) {
                        ProcessPimTranslation::dispatch($record);
                    });
                    Notification::make()
                        ->title('Translation jobs started')
                        ->body('Translations will be executed in background. This may take a while.')
                        ->info()
                        ->send();
                })
                ->visible($visible),
        ];
    }

    public function translateAllPropertyGroups(string $defaultLangCode, Collection $extraLangIdCodes): void
    {
        PimPropertyGroup::all()
            ->each(function ($group) use ($defaultLangCode, $extraLangIdCodes) {
                foreach ($extraLangIdCodes as $languageId => $langCode) {
                    $translation = self::getTranslation(PimPropertyGroup::class, $defaultLangCode, $langCode, $group->description);
                    PimPropertyGroupTranslation::updateOrCreate([
                        'language_id' => $languageId,
                        'property_group_id' => $group->id,
                    ], [
                        'name' => $group->name,
                        'description' => $translation,
                        'property_group_id' => $group->id,
                        'language_id' => $languageId,
                    ]);
                }
            });
    }

    public function translateAllPropertyGroupOptions(string $defaultLangCode, Collection $extraLangIdCodes): void
    {
        PimPropertyGroupService::getTranslatableSelectGroups()
            ->each(function ($group) use ($defaultLangCode, $extraLangIdCodes) {
                $group->groupOptions->each(function ($record) use ($defaultLangCode, $extraLangIdCodes) {
                    foreach ($extraLangIdCodes as $languageId => $langCode) {
                        $translation = self::getTranslation(PimPropertyGroupOption::class, $defaultLangCode, $langCode, $record->name);
                        PimPropertyGroupOptionTranslation::updateOrCreate([
                            'language_id' => $languageId,
                            'property_group_option_id' => $record->id,
                        ], [
                            'name' => $translation,
                            'position' => $record->position,
                            'property_group_option_id' => $record->id,
                            'language_id' => $languageId,
                        ]);
                    }
                });
            });
    }

    public function assignColorAllPropertyGroupOptions(string $defaultLangCode): void
    {
        PimPropertyGroupService::getColorGroups()->each(function ($group) use ($defaultLangCode) {
            $group->groupOptions->each(function (PimPropertyGroupOption $record) use ($defaultLangCode) {
                $color = self::getColor(PimPropertyGroupOption::class, $defaultLangCode, $record->name);
                if ($color === $record->name) {
                    return;
                }
                // store custom field -> 'custom_fields.'.PimColor::CUSTOM_FIELD_KEY = $color
                $record->update([
                    'custom_fields' => [
                        PimColor::CUSTOM_FIELD_KEY => $color,
                    ],
                ]);
            });
        });
    }

    public static function translateAllProducts(): void
    {
        PimProductService::getProductMainIds()
            ->each(function ($pimProductId) {
                $pimProduct = PimProduct::find($pimProductId);
                ProcessPimTranslation::dispatch($pimProduct);
            });
    }
}
