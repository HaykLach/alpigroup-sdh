<?php

namespace App\Imports\VendorCatalog;

use App\Models\VendorCatalog\VendorCatalogImport;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Cerbero\JsonParser\JsonParser;

class VendorCatalogGenericJsonImporter
{
    use VendorCatalogGenericStoreImportRecordTrait;

    public function __construct(
        protected VendorCatalogImport $import
    ) {}

    public function import()
    {
        $path = storage_path().'/app/'.(new VendorCatalogFileImportService)->getFileUploadPath($this->import->importDefinition);

        foreach (new JsonParser($path) as $record) {
            $this->newImportRecord($record)->save();
        }
    }
}
