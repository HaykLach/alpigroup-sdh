<?php

namespace App\Enums\Pim;

use App\Services\Pim\PropertyGroup\Form\PimBool;
use App\Services\Pim\PropertyGroup\Form\PimColor;
use App\Services\Pim\PropertyGroup\Form\PimDateRange;
use App\Services\Pim\PropertyGroup\Form\PimFileUpload;
use App\Services\Pim\PropertyGroup\Form\PimMultiselect;
use App\Services\Pim\PropertyGroup\Form\PimNumber;
use App\Services\Pim\PropertyGroup\Form\PimPrice;
use App\Services\Pim\PropertyGroup\Form\PimSelect;
use App\Services\Pim\PropertyGroup\Form\PimText;
use App\Services\Pim\PropertyGroup\Form\PimTextarea;
use App\Services\Pim\PropertyGroup\Form\PimUrl;

enum PimFormType: string
{
    case SELECT = PimSelect::class;
    case COLOR = PimColor::class;
    case MULTISELECT = PimMultiselect::class;
    case BOOL = PimBool::class;
    case DATE = PimDateRange::class;
    case TEXT = PimText::class;
    case TEXTAREA = PimTextarea::class;
    case NUMBER = PimNumber::class;
    case PRICE = PimPrice::class;
    case URL = PimUrl::class;
    case FILEUPLOAD = PimFileUpload::class;

    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === strtoupper($name)) {
                return $case;
            }
        }

        return null;
    }
}
