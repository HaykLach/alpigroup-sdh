<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogImportRecordState: string
{
    case NEW = 'new';
    case PROCESSED = 'processed';
}
