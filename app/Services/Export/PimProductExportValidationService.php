<?php

namespace App\Services\Export;

use App\Models\Pim\Product\PimProduct;
use App\Services\Pim\PimPropertyGroupService;
use Illuminate\Support\Collection;

class PimProductExportValidationService
{
    public const string VALID = 'valid';

    public const string INVALID = 'invalid';

    public const array MANDATORY_FIELDS = [
        'pim_tax_id' => 'Tax',
        'pim_manufacturer_id' => 'Manufacturer',
        'product_number' => 'Product number',
        'name' => 'Name',
    ];

    public const array OPTIONAL_FIELDS = [
        'identifier' => 'EAN Code',
        'description' => 'Description',
    ];

    public function validate(PimProduct $pimProduct, Collection $requiredProperties, Collection $requiredFields): bool
    {
        $validatedProductIds = $this->validateDetail($pimProduct, $requiredProperties, $requiredFields);

        return count($validatedProductIds[self::INVALID]) === 0;
    }

    public function validateDetail(PimProduct $pimProduct, Collection $requiredProperties, Collection $requiredFields): array
    {
        $validation = [
            self::VALID => [],
            self::INVALID => [],
        ];

        $isMainProductWithoutVariants = $pimProduct->isMainProduct && $pimProduct->variations->isEmpty();

        if (! $isMainProductWithoutVariants) {
            $pimProduct->variations->each(function ($pimProductVariant) use (&$validation, $requiredProperties, $requiredFields) {
                $key = $this->checkProductContainsRequiredDataSingleItem($pimProductVariant, $requiredProperties, $requiredFields) ? self::VALID : self::INVALID;
                $validation[$key][] = $pimProductVariant->id;
            });
        } else {
            $key = $this->checkProductContainsRequiredDataFields($pimProduct, $requiredFields) ? self::VALID : self::INVALID;
            $validation[$key][] = $pimProduct->id;
        }

        return $validation;
    }

    private function checkProductContainsRequiredDataSingleItem(PimProduct $pimProduct, Collection $requiredProperties, Collection $requiredFields): bool
    {
        $valid = $this->checkProductContainsRequiredDataProperties($pimProduct, $requiredProperties);
        if ($valid) {
            $valid = $this->checkProductContainsRequiredDataFields($pimProduct, $requiredFields);
        }

        return $valid;
    }

    private function checkProductContainsRequiredDataFieldsPrimary(PimProduct $pimProduct): bool
    {
        $valid = true;

        // check primary required fields
        foreach (array_keys(self::MANDATORY_FIELDS) as $field) {
            if (empty($pimProduct[$field])) {
                $valid = false;
                break;
            }
        }

        return $valid;
    }

    private function checkProductContainsRequiredDataFields(PimProduct $pimProduct, Collection $requiredFields): bool
    {
        $valid = $this->checkProductContainsRequiredDataFieldsPrimary($pimProduct);

        if ($valid) {
            $requiredFields->each(function ($group) use ($pimProduct, &$valid) {
                if ($valid) {
                    if (PimPropertyGroupService::getRequiredGroupsFieldnameValue($group, $pimProduct) === null) {
                        $valid = false;
                    }
                }
            });
        }

        return $valid;
    }

    private function checkProductContainsRequiredDataProperties(PimProduct $pimProduct, Collection $requiredProperties): bool
    {
        $valid = true;
        $pimProductOptions = $pimProduct->properties->pluck('id');

        $requiredProperties->each(function ($group) use (&$valid, $pimProductOptions) {
            if ($valid) {
                $requiredOptionIds = $group->groupOptions->pluck('id');
                // check if $pimProductOptions contains one of required $requiredOptionIds
                $valid = $valid && $requiredOptionIds->contains(function ($optionId) use ($pimProductOptions) {
                    return $pimProductOptions->contains($optionId);
                });
            }
        });

        return $valid;
    }
}
