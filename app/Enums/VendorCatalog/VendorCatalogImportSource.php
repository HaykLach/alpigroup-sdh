<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogImportSource: string
{
    case FILE = 'file';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case TXT = 'txt';
    case XML = 'xml';
    case JSON = 'json';
    case GOOGLE_SHEET = 'google sheet';
}
