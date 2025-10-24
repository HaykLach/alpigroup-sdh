<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimFormSection;
use App\Enums\Pim\PimFormStoreField;
use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Services\Pim\PropertyGroup\Form\Form;
use Closure;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Collection;

class PimPropertyGroupService
{
    public static function getGroups(PimMappingType $mappingType): Collection
    {
        return PimPropertyGroup::with(['groupOptions' => fn ($query) => $query->orderBy('position')])
            ->where('custom_fields->type', '=', $mappingType->name)
            ->orderBy('description')
            ->get();
    }

    public static function getRequiredGroups(PimMappingType $mappingType): Collection
    {
        return PimPropertyGroup::with(['groupOptions' => fn ($query) => $query->orderBy('position')])
            ->where('custom_fields->type', '=', $mappingType->name)
            ->where('custom_fields->form->validation->required', '=', true)
            ->orderBy('description')
            ->get();
    }

    public static function filterRequiredGroupsProperties(Collection $requiredGroups): Collection
    {
        return $requiredGroups->filter(function ($group) {
            return in_array($group->custom_fields['form']['type'], [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name]);
        });
    }

    public static function filterRequiredGroupsFields(Collection $requiredGroups): Collection
    {
        return $requiredGroups->filter(function ($group) {
            return ! in_array($group->custom_fields['form']['type'], [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name]);
        });
    }

    public static function getRequiredGroupsFieldnameValue(PimPropertyGroup $group, PimProduct $pimProduct): mixed
    {
        $fieldNameArray = explode('->', self::getRequiredGroupsFieldname($group));
        $selector = $pimProduct;

        foreach ($fieldNameArray as $key) {
            $selector = $selector[$key];
        }

        return $selector;
    }

    public static function getRequiredGroupsFieldname(PimPropertyGroup $group): string
    {
        $fieldName = $group->custom_fields['field'];

        if (in_array($fieldName, [PimFormStoreField::CUSTOM_FIELDS->value, PimFormStoreField::PRICES->value])) {
            $fieldName .= '->'.$group->id;
        }

        return $fieldName;
    }

    public static function determineUpdateableProperties(array $mapping): Collection
    {
        $fields = array_keys($mapping);

        return PimPropertyGroup::query()
            ->with('groupOptions')
            ->where('custom_fields->type', PimMappingType::PRODUCT->name)
            ->whereIn('custom_fields->form->type', [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name])
            ->whereIn('name', $fields)
            ->get()
            ->flatMap(function ($group) {
                return $group->groupOptions->keyBy('id');
            });
    }

    protected static function getTranslatableTextGroups(PimMappingType $mappingType): Collection
    {
        return PimPropertyGroup::with(['groupOptions' => fn ($query) => $query->orderBy('position')])
            ->where('custom_fields->type', '=', $mappingType->name)
            ->where('custom_fields->translatable', '=', true)
            ->whereIn('custom_fields->form->type', [PimFormType::TEXT->name, PimFormType::TEXTAREA->name])
            ->get();
    }

    public static function getTranslatableSelectGroups(): Collection
    {
        return PimPropertyGroup::with(['groupOptions' => fn ($query) => $query->orderBy('position')])
            ->where('custom_fields->translatable', '=', true)
            ->whereIn('custom_fields->form->type', [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name])
            ->get();
    }

    public static function getColorGroups(): Collection
    {
        return PimPropertyGroup::with(['groupOptions' => fn ($query) => $query->orderBy('position')])
            ->whereIn('custom_fields->form->type', [PimFormType::COLOR->name])
            ->get();
    }

    public static function getEditableTranslatableTextGroups(PimMappingType $mappingType): array
    {
        $propertyGroups = PimPropertyGroupService::getTranslatableTextGroups($mappingType);
        $editableMain = $propertyGroups->filter(function ($item) {
            return $item->custom_fields['edit']['main'] === true;
        });
        $editableVariant = $propertyGroups->filter(function ($item) {
            return $item->custom_fields['edit']['variant'] === true;
        });

        return [$editableMain, $editableVariant];
    }

    private static function filterGroupsBySection(Collection $groups, PimFormSection $section): Collection
    {
        return $groups->filter(function ($group) use ($section) {
            return $group->custom_fields['section'] === $section->value;
        });
    }

    /**
     * @throws Exception
     */
    public static function getForms(PimMappingType $mappingType, Collection $groups, Collection $extraLangIdCodes, string $defaultLangCode, ?PimFormSection $section = null): Collection
    {
        $sectionGroups = $section !== null ? self::filterGroupsBySection($groups, $section) : $groups;

        $items = collect();

        $sectionGroups
            ->map(function ($group) use (&$items, $defaultLangCode, $extraLangIdCodes, $mappingType) {

                // add form to $items
                $form = self::getGroupForm($mappingType, $group);
                $items->push($form);

                // add repeater
                if ($extraLangIdCodes->count() &&
                    $group->custom_fields['translatable'] &&
                    (new (self::getGroupClass($group))($group))->isTranslatable()
                ) {
                    $forms = [
                        self::getGroupForm($mappingType, $group),
                    ];
                    // allow only text and textarea to be translated via action (chatgpt translation)
                    if (in_array($group->custom_fields['form']['type'], [PimFormType::TEXT->name, PimFormType::TEXTAREA->name])) {
                        $forms[] = PimTranslationService::getTranslateActionsWithinRepeater('custom_fields.properties.'.$group->id, $defaultLangCode, $extraLangIdCodes);
                    }

                    $items->push(
                        PimTranslationService::getForm($forms, $extraLangIdCodes)
                            ->visible(self::getFormVisibilityFnc($mappingType, $group))
                    );
                }
            });

        return $items;
    }

    /**
     * @throws Exception
     */
    private static function getGroupForm(PimMappingType $mappingType, PimPropertyGroup $group): TextInput|Select|Textarea|Toggle|DatePicker|SpatieMediaLibraryFileUpload
    {
        $class = self::getGroupClass($group);
        $form = (new $class($group))->getForm();

        if (! PimPropertyGroupService::formSetDisabled($form, $group)) {
            PimPropertyGroupService::formSetRequired($form, $group);
        }

        // show/hide fields based on the record and group configuration
        $form->visible(self::getFormVisibilityFnc($mappingType, $group));

        return $form;
    }

    protected static function formSetRequired($form, $group): void
    {
        if ($group->custom_fields['form']['validation']['required']) {
            $form->required();
        }
    }

    protected static function formSetDisabled($form, $group): bool
    {
        if ($group->custom_fields['form']['readonly']) {
            // get type of form
            $form->disabled();
            if ($form::class !== 'Filament\Tables\Columns\TextColumn') {
                $form->extraAttributes([
                    'x-data' => '{}',
                    'x-tooltip.raw' => 'Value must be set in ERP',
                ]);
            }

            return true;
        }

        return false;
    }

    public static function getFormVisibilityFnc(PimMappingType $mappingType, PimPropertyGroup $group): Closure
    {
        return match ($mappingType) {
            PimMappingType::PRODUCT => PimProductService::getVisibilityFnc($group),
            default => function () {
                return true;
            },
        };
    }

    private static function getTableColVisibilityFnc($group, $mappingType): Closure
    {
        return function ($livewire) use ($group, $mappingType) {
            return self::isFormEditableInCurrentTab($mappingType, $livewire, $group);
        };
    }

    public static function isFormEditableInCurrentTab(PimMappingType $mappingType, $livewire, PimPropertyGroup $group): bool
    {
        if ($mappingType === PimMappingType::PRODUCT) {
            return $livewire->activeTab === 'main'
                ? $group->custom_fields['edit']['main']
                : $group->custom_fields['edit']['variant'];
        }

        return true;
    }

    public static function getFilters(PimMappingType $mappingType): Collection
    {
        return PimPropertyGroupService::getGroups($mappingType)
            ->map(function ($group) {
                /** @var Form $class */
                $class = self::getGroupClass($group);

                return (new $class($group))->getFilter();
            })
            ->filter(function ($value) {
                return $value !== null;
            });
    }

    public static function getTableColumns(PimMappingType $mappingType, bool $inlineEdit = false, ?Collection $groups = null): Collection
    {
        $groups = $groups ?? PimPropertyGroupService::getGroups($mappingType);

        return $groups
            ->map(function ($group) use ($mappingType, $inlineEdit) {
                /** @var Form $class */
                $class = self::getGroupClass($group);

                $form = (new $class($group, $inlineEdit))->getTableColumn(self::getTableColVisibilityFnc($group, $mappingType));
                PimPropertyGroupService::formSetDisabled($form, $group);

                return $form;
            });
    }

    /**
     * @throws Exception
     */
    public static function getGroupClass(PimPropertyGroup $group): string
    {
        return PimFormType::tryFromName($group->custom_fields['form']['type'])->value;
    }

    public static function getMediaCollectionIds(PimMappingType $mappingType): Collection
    {
        return PimPropertyGroupService::getGroups($mappingType)
            ->filter(function ($group) {
                return $group->custom_fields['form']['type'] === PimFormType::FILEUPLOAD->name;
            })
            ->pluck('custom_fields.collection', 'id');
    }

    public static function getPropertyGroupByName(string $name): PimPropertyGroup
    {
        return PimPropertyGroup::query()
            ->where('name', '=', $name)
            ->with([
                'groupOptions',
                'groupOptions.propertyGroup',
            ])
            ->first();
    }

    public static function getPropertyGroupOptionById(string $id): PimPropertyGroupOption
    {
        return PimPropertyGroupOption::query()
            ->where('id', '=', $id)
            ->first();
    }
}
