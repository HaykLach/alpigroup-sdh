<?php

namespace App\Imports\VendorCatalog;

use App\Enums\VendorCatalog\VendorCatalogImportRecordState;
use App\Models\VendorCatalog\VendorCatalogImportRecord;

trait VendorCatalogGenericStoreImportRecordTrait
{
    protected function newImportRecord(array $data): VendorCatalogImportRecord
    {
        $setup = $this->import->importDefinition->setup;

        $number = $row[$setup['article_column']] ?? '';

        $stock = -1;
        if (strlen($setup['stock_column'])) {
            $stock = $row[$setup['stock_column']] ?? -1;
        }

        return new VendorCatalogImportRecord([
            'vendor_catalog_import_id' => $this->import->id,
            'state' => VendorCatalogImportRecordState::NEW,
            'number' => $number,
            'stock' => $stock,
            'data' => $data,
        ]);
    }
}
