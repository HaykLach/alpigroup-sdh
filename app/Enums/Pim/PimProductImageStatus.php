<?php

namespace App\Enums\Pim;

enum PimProductImageStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETE = 'complete';
    case FAILED = 'failed';
}
