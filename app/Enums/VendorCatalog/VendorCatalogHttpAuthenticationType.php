<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogHttpAuthenticationType: string
{
    case NONE = 'No Authentication';
    case BASIC_AUTH = 'Basic Auth';
    case DIGEST_AUTH = 'Digest Auth';
    case AUTH_HEADER = 'Authentication Header';
}
