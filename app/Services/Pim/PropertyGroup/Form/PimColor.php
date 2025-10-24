<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;

class PimColor extends PimSelect implements PropertyGroupFormInterface
{
    public const string CUSTOM_FIELD_KEY = 'colorHex';

    public const string FALLBACK_COLOR = '#999999';
}
