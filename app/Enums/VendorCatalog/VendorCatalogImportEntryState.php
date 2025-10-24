<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogImportEntryState: string
{
    case NEW = 'new';
    case PROCESSED = 'processed';
}
