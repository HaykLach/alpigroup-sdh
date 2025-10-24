<?php

namespace App\Imports\VendorCatalog;

use App\Models\VendorCatalog\VendorCatalogImport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class VendorCatalogGenericCsvImporter implements ToModel, WithCustomCsvSettings, WithHeadingRow, WithStartRow
{
    use VendorCatalogGenericStoreImportRecordTrait;

    public function __construct(
        protected VendorCatalogImport $import
    ) {}

    public function model(array $row)
    {
        return $this->newImportRecord($row);
    }

    public function getCsvSettings(): array
    {
        $importDefinition = $this->import->importDefinition;

        $file = $importDefinition->file;

        $enclosure = match ($file['enclosure']) {
            '\t' => "\t",
            default => $file['enclosure'],
        };

        $settings = [
            'delimiter' => $file['delimiter'],
            // 'enclosure' => ,
            'enclosure' => $enclosure,

            'escape_character' => $file['escape'],
            // 'contiguous' => '',
        ];

        if (array_key_exists('encoding', $file)) {
            $settings['input_encoding'] = $file['encoding'];
        }

        return $settings;
    }

    public function startRow(): int
    {
        $importDefinition = $this->import->importDefinition;
        $file = $importDefinition->file;

        return $file['start_row'];
    }

    public function headingRow(): int
    {
        $importDefinition = $this->import->importDefinition;
        $file = $importDefinition->file;

        return $file['header_row'] ?? 0;
    }
}
