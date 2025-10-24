<?php

namespace App\Controllers\Export;

use App\Enums\Pim\PimMappingType;
use App\Models\Pim\PimLanguage;
use App\Services\Export\PimCustomFieldsExporterService;

class PimCustomFieldSetExporter
{
    public PimCustomFieldsExporterService $customFieldsExporterService;

    public function __construct(
        protected $customFieldSetController,
        protected $customFieldController,
    ) {
        $this->customFieldsExporterService = new PimCustomFieldsExporterService;
    }

    public function sync(): void
    {
        $configMapping = config('sdh-shopware-sdk.defaults.mapping');

        // $remoteEntries = $this->customFieldSetController->list();
        $locales = PimLanguage::query()->with('local')->get();

        foreach ([PimMappingType::PRODUCT, PimMappingType::MANUFACTURER] as $type) {
            $customFieldSet = $this->customFieldsExporterService->getCustomFieldSet($type, $locales, $configMapping);
            $exists = $this->customFieldSetController->get($customFieldSet->id);
            if ($exists !== false) {
                echo 'it exists'.PHP_EOL;
                $success = $this->customFieldSetController->update($customFieldSet);
            } else {
                echo 'it does not exist'.PHP_EOL;
                $success = $this->customFieldSetController->create($customFieldSet);
            }
            if ($success === false) {
                echo 'failed'.PHP_EOL;
            } else {
                echo 'success'.PHP_EOL;
            }

            $customFieldSetShopware = $this->customFieldSetController->get($customFieldSet->id);

            $orphaned = $this->customFieldsExporterService->compareCustomFieldSetsGetOrphaned($customFieldSet, $customFieldSetShopware);
            $orphaned->each(function ($orphan) {
                $success = $this->customFieldController->delete($orphan['id']);
                if ($success) {
                    echo 'deleted: '.$orphan['id'].PHP_EOL;
                } else {
                    echo 'failed to delete: '.$orphan['id'].PHP_EOL;
                }
            });
        }

        /*
        $remoteEntries = $this->customFieldSetController->list();
        dd($remoteEntries);
        */
    }
}
