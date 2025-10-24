<?php

namespace App\Services\Pim\PropertyGroup\Form\Traits;

use App\Services\Pim\PropertyGroup\PimPropertyGroupStorePropertiesService;

trait PimStoreOptions
{
    protected static function store($options): callable
    {
        return fn ($record, $state) => PimPropertyGroupStorePropertiesService::store($record, $options, collect($state)->filter(fn ($id) => $options->has($id)));
    }
}
