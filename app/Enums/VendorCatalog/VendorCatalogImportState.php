<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogImportState: string
{
    case NEW = 'new';
    case DUPLICATED = 'duplicated';

    case PROCESSED = 'processed';
}
