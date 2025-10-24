<?php

namespace App\Services\Pim\PropertyGroup;

use App\Models\Pim\Product\PimProduct;
use App\Services\Pim\PimProductService;
use Illuminate\Support\Collection;

class PimPropertyGroupStorePropertiesService
{
    public static function store(PimProduct $record, Collection $options, Collection|string|null $state = null)
    {
        if ($state === null) {
            return;
        }

        if ($record->isMainProduct) {
            // handle variants
            PimProductService::queryProductVariants($record['id'])
                ->get()
                ->each(fn ($variant) => self::handle($variant, $options, $state));
        }

        self::handle($record, $options, $state);
    }

    protected static function handle($variant, Collection $options, Collection|string $state): void
    {
        self::detachPreviousOptions($variant, $options);
        if (! $state->isEmpty()) {
            self::storeOptions($variant, $state);
        }
    }

    protected static function storeOptions($record, Collection|string $state): void
    {
        self::checkIfMultiple($state)
            ? $state->each(fn ($singleState) => $record->properties()->attach($singleState))
            : $record->properties()->attach($state);
    }

    protected static function detachPreviousOptions($record, Collection $options): void
    {
        $record->properties()
            ->wherePivotIn('option_id', $options->keys())
            ->detach();
    }

    protected static function checkIfMultiple(Collection|string $state): bool
    {
        return ! is_string($state);
    }

    public static function extractGroupIdFromStatePath(string $statePath)
    {
        // extract uuid from $statePath looking like "properties.9c8afe3f-d0e5-4f6f-8dc8-ad9913577952" or "custom_fields.properties.9c8afe3f-f3dc-4322-adf6-7992d352e3e9"
        $parts = explode('.', $statePath);

        return end($parts);
    }
}
