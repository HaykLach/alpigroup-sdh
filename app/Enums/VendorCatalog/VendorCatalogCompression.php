<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogCompression: string
{
    case ZIP = 'zip';
    case GZ = 'gz';
    case TAR = 'tar';
}
