<?php

use App\Enums\Pim\PimFormStoreField;
use App\Enums\Pim\PimMappingType;

return [
    'autoTranslateByRemoteService' => true,
    'autoAssignColorByRemoteService' => true,
    'autoDetermineProductColor' => true,

    // @todo remove this key
    'update' => [
        PimMappingType::PRODUCT->value => [
            PimFormStoreField::PRICES->value,
        ],
    ],
];
