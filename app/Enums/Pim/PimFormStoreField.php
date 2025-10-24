<?php

namespace App\Enums\Pim;

enum PimFormStoreField: string
{
    case CUSTOM_FIELDS = 'custom_fields';
    case NAME = 'name';
    case DESCRIPTION = 'description';
    case MEDIA = 'media';
    case IMAGES = 'images';
    case IDENTIFIER = 'identifier';
    case PRODUCT_NUMBER = 'product_number';
    case ACTIVE = 'active';
    case STOCK = 'stock';
    case PRICES = 'prices';
    case MAIN_NAME = 'main_name';
}
