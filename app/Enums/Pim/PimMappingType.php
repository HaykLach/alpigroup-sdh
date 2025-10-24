<?php

namespace App\Enums\Pim;

enum PimMappingType: string
{
    case PRODUCT = 'product';
    case MANUFACTURER = 'manufacturer';
    case SALUTATION = 'salutation';
    case CUSTOMER = 'customer';
    case AGENT = 'agent';
    case BRANCH = 'branch';
    case TAX = 'tax';
    case TAX_CUSTOMER_GROUP = 'tax_customer_group';
    case UNIT_TYPE = 'unit_type';
    case PRICE_LIST = 'pricelist';
}
