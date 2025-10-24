<?php

namespace App\Services\Pim;

use App\Models\Pim\Product\PimProductManufacturer;
use Illuminate\Support\Collection;

class PimProductManufacturerService
{
    public static function update(PimProductManufacturer $record, array $data): void
    {
        PimResourceService::stripProvidedFormData($data);

        $record->update($data);
    }

    public static function upsert(Collection $records, Collection $otherLanguages): array
    {
        $manufacturers = [];

        foreach ($records as $manufacturer) {

            $manufacturerExisting = self::checkIfManufacturerExists($manufacturer['Bezeichnung_de']);
            if ($manufacturerExisting) {
                $manufacturers[$manufacturer['MarkeCode']] = $manufacturerExisting->id;

                continue;
            }

            $created = PimProductManufacturer::create([
                'name' => $manufacturer['Bezeichnung_de'],
            ]);

            $manufacturers[$manufacturer['MarkeCode']] = $created->id;

            PimProductManufacturerTranslationService::addInitialTranslations($created, $otherLanguages);
        }

        return $manufacturers;
    }

    protected static function checkIfManufacturerExists(string $name): ?PimProductManufacturer
    {
        return PimProductManufacturer::byName($name)->first();
    }
}
