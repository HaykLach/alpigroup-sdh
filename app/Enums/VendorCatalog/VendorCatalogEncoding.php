<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogEncoding: string
{
    case UTF_8 = 'UTF-8';
    case UTF_16_with_byte_order_mark = 'UTF-16 with byte order mark';
    case UTF_16_little_endian = 'UTF-16 (little endian)';
    case UTF_16_big_endian = 'UTF-16 (big endian)';
    case UTF_32_with_byte_order_mark = 'UTF-32 with byte order mark';
    case UTF_32_little_endian = 'UTF-32 (little endian)';
    case UTF_32_big_endian = 'UTF-32 (big endian)';
    case ISO_8859_1 = 'ISO-8859-1';
    case ISO_8859_15 = 'ISO-8859-15';
    case Windows_1250 = 'Windows-1250';
    case Windows_1251 = 'Windows-1251';
    case Windows_1252 = 'Windows-1252';
    case Windows_1254 = 'Windows-1254';
}
