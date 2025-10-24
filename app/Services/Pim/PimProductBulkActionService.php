<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Product\PimProduct;
use App\Services\Pim\PropertyGroup\PimPropertyGroupStorePropertiesService;
use Closure;
use Exception;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use ReflectionClass;
use ReflectionException;

class PimProductBulkActionService
{
    protected static array $bulkTranslatableFormTypes = [
        PimFormType::TEXT,
        PimFormType::TEXTAREA,
        PimFormType::URL,
        PimFormType::FILEUPLOAD,
    ];

    /**
     * @throws Exception
     */
    public static function getBulkActions(PimMappingType $mappingType, Collection $extraLangIdCodes, string $defaultLangCode): Collection
    {
        $groups = PimPropertyGroupService::getGroups($mappingType)->keyBy('id');
        $items = PimPropertyGroupService::getForms($mappingType, $groups, collect(), $defaultLangCode);

        $items = $items->map(function ($item) use ($mappingType, $groups) {

            $name = self::getBulkActionItemFormProperty($item, 'name');
            $label = self::getBulkActionItemFormProperty($item, 'label');

            return BulkAction::make($name)
                ->icon('heroicon-m-pencil-square')
                ->label('Edit '.$label)
                ->form([$item])
                ->visible(self::getBulkActionVisibilityFnc($mappingType, $name, $groups))
                ->action(self::getBulkActionFormActionFnc($name, $groups));
        });

        $langItems = $extraLangIdCodes->count() ? self::getBulkActionsLangForms($groups, $mappingType, $extraLangIdCodes) : collect();

        return $items->merge($langItems);
    }

    protected static function getBulkActionsLangForms(Collection $groups, PimMappingType $mappingType, Collection $extraLangIdCodes): Collection
    {
        $items = collect();

        // get names from enum
        $bulkTranslatableFormTypes = array_map(fn ($type) => $type->name, self::$bulkTranslatableFormTypes);

        $extraLangIdCodes->each(function ($langIdCode, $langId) use (&$items, $groups, $mappingType, $bulkTranslatableFormTypes) {
            $groups
                ->filter(function ($group) use ($bulkTranslatableFormTypes) {
                    return $group->custom_fields['translatable']
                        && in_array($group->custom_fields['form']['type'], $bulkTranslatableFormTypes);
                })
                ->map(/**
                 * @throws Exception
                 */ function ($group) use (&$items, $langIdCode, $langId, $mappingType, $groups) {

                    $formClass = (new (PimPropertyGroupService::getGroupClass($group))($group));
                    $item = $formClass->getForm();

                    $name = self::getBulkActionItemFormProperty($item, 'name');
                    $label = self::getBulkActionItemFormProperty($item, 'label');

                    $action = BulkAction::make($name.'.'.$langId)
                        ->icon('heroicon-m-pencil-square')
                        ->label('Edit ('.$langIdCode.') '.$label)
                        ->form([$item])
                        ->visible(self::getBulkActionVisibilityFnc($mappingType, $name, $groups))
                        ->action(self::getBulkActionLangFormActionFnc($langId, $name, $groups));

                    $items->push($action);
                });
        });

        return $items;
    }

    private static function getBulkActionLangFormActionFnc(string $langId, string $name, Collection $groups): Closure
    {
        return function ($records, $data, $livewire) use ($langId, $name, $groups) {

            $propertyGroupId = PimPropertyGroupStorePropertiesService::extractGroupIdFromStatePath($name);
            $group = $groups[$propertyGroupId];

            if ($group->custom_fields['form']['type'] === PimFormType::FILEUPLOAD->name) {
                $uploadedFiles = collect($livewire->mountedTableBulkActionData['fileupload'][$group->id]);
                $uploadedFiles->each(function (TemporaryUploadedFile $file) use ($langId, $records, $group) {
                    $records->each(function (PimProduct $record) use ($langId, $file, $group) {

                        $translation = $record->translations()
                            ->where('language_id', $langId)
                            ->first();

                        $translation->addMedia($file)
                            ->preservingOriginal()
                            ->toMediaCollection($group->custom_fields['collection']);

                        PimMediaService::syncTranslation($record, PimProductService::queryProductVariants($record->id)->get());
                    });

                    self::removeUploadedFile($file);
                });
            }

            self::saveTranslationRelationship($records, $data, $langId);
        };
    }

    public static function saveTranslationRelationship(Collection $records, array $data, string $langId): void
    {
        // check if data contains name, description, custom_fields
        if (isset($data['name']) || isset($data['description']) || isset($data['custom_fields'])) {
            $records->each(function (PimProduct $record) use ($data, $langId) {
                $data['language_id'] = $langId;
                PimTranslationService::saveRelationship($record, $data);
            });
        }
    }

    /**
     * BulkAction somehow does not execute Form saveRelationshipsUsing callback
     * so we need to manually store properties and fileupload
     */
    private static function getBulkActionFormActionFnc(string $name, Collection $groups): Closure
    {
        return function ($records, $data, $livewire) use ($name, $groups) {

            $propertyGroupId = PimPropertyGroupStorePropertiesService::extractGroupIdFromStatePath($name);
            $group = $groups[$propertyGroupId];

            switch ($group->custom_fields['form']['type']) {

                case PimFormType::COLOR->name:
                case PimFormType::MULTISELECT->name:
                case PimFormType::SELECT->name:
                    $options = $group->groupOptions->pluck('name', 'id');
                    $entries = collect($data['properties'][$propertyGroupId]);
                    $records->each(function (PimProduct $record) use ($entries, $options) {
                        PimPropertyGroupStorePropertiesService::store($record, $options, $entries);
                    });
                    break;

                case PimFormType::FILEUPLOAD->name:
                    $uploadedFiles = collect($livewire->mountedTableBulkActionData['fileupload'][$group->id]);
                    $uploadedFiles->each(function (TemporaryUploadedFile $file) use ($records, $group) {
                        $records->each(function (PimProduct $record) use ($file, $group) {
                            $record->addMedia($file)
                                ->preservingOriginal()
                                ->toMediaCollection($group->custom_fields['collection']);

                            PimMediaService::sync($record, PimProductService::queryProductVariants($record->id)->get());
                        });

                        self::removeUploadedFile($file);
                    });
                    break;
            }

            // check if data contains name, description, custom_fields
            if (isset($data['name']) || isset($data['description']) || isset($data['custom_fields'])) {
                $records->each(function (PimProduct $record) use ($data) {
                    PimProductService::update($record, $data);
                });
            }
        };
    }

    private static function removeUploadedFile(TemporaryUploadedFile $file): void
    {
        if (file_exists($file->getPathname())) {
            unlink($file->getPathname());
        }
    }

    /**
     * @throws ReflectionException
     */
    private static function getBulkActionItemFormProperty($item, string $key): string
    {
        // Assuming $item is an instance of a class with protected properties
        $itemClass = new ReflectionClass(get_class($item));

        $property = $itemClass->getProperty($key);
        $property->setAccessible(true);

        return $property->getValue($item);
    }

    private static function getBulkActionVisibilityFnc($mappingType, $name, $groups): Closure
    {
        return function ($livewire) use ($mappingType, $name, $groups) {

            $propertyGroupId = PimPropertyGroupStorePropertiesService::extractGroupIdFromStatePath($name);

            return PimPropertyGroupService::isFormEditableInCurrentTab($mappingType, $livewire, $groups[$propertyGroupId]);
        };
    }
}
