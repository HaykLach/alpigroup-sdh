<?php

namespace App\Enums\VendorCatalog;

enum VendorCatalogImportDefinitionProtocolType: string
{
    case HTTP = 'http';
    case FTP = 'ftp';
    case SFTP = 'sftp';
    case LOCAL = 'local';
    case S3 = 'S3';
    case UPLOAD = 'Upload';
}
