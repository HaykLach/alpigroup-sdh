<?php

namespace App\Enums\Pim;

enum PimFormSection: string
{
    case MAIN = 'main';
    case IDENTITY = 'identity';
    case SPECIFICATION = 'specification';
    case PRICING = 'pricing';
    case AVAILABILITY = 'availability';
}
