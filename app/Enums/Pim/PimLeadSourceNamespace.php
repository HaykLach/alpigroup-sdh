<?php

namespace App\Enums\Pim;

use App\Enums\Traits\ToArray;

enum PimLeadSourceNamespace: string
{
    use ToArray;

    case SDH = 'sdh';

    public function getLabel(): string
    {
        return match ($this) {
            self::SDH => __('SDH'),
        };
    }
}
