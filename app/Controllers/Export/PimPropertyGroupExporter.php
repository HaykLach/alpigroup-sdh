<?php

namespace App\Controllers\Export;

use App\Models\Pim\PimLanguage;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Services\Export\PimPropertyGroupExporterService;
use App\Services\Export\PimPropertyGroupOptionExporterService;
use Illuminate\Support\Collection;

class PimPropertyGroupExporter
{
    protected PimPropertyGroupExporterService $pimPropertyGroupExporterService;

    protected PimPropertyGroupOptionExporterService $pimPropertyGroupOptionExporterService;

    public function __construct(
        protected $propertyGroupController,
    ) {
        $this->pimPropertyGroupExporterService = new PimPropertyGroupExporterService;
        $this->pimPropertyGroupOptionExporterService = new PimPropertyGroupOptionExporterService;
    }

    public function sync(): void
    {
        // $remoteEntries = $this->propertyGroupController->list();
        $localEntries = $this->pimPropertyGroupExporterService->getPropertyGroups();

        $locales = PimLanguage::getAllWithLocalKeyedByCode();

        $localEntries->each(function ($group) use ($locales) {

            echo 'Group: '.$group->description.PHP_EOL;

            $optionsData = $this->getOptionsData($group, $this->pimPropertyGroupOptionExporterService, $locales);
            $data = $this->pimPropertyGroupExporterService->assignData($group, $optionsData, $locales);

            $exists = $this->propertyGroupController->get($data->id);
            if ($exists !== false) {
                $success = $this->propertyGroupController->update($data);
            } else {
                $success = $this->propertyGroupController->create($data);
            }
            if ($success !== false) {
                echo $data->name.' success'.PHP_EOL;
            }
        });

        // $remoteEntries = $this->propertyGroupController->list();
    }

    protected function getOptionsData(PimPropertyGroup $group, PimPropertyGroupOptionExporterService $pimPropertyGroupOptionExporterService, Collection $locales): Collection
    {
        $optionsData = collect();
        $group->groupOptions->each(function ($option) use (&$optionsData, $group, $pimPropertyGroupOptionExporterService, $locales) {
            $optionsData->push($pimPropertyGroupOptionExporterService->assignData($group, $option, $locales));
        });

        return $optionsData;
    }
}
