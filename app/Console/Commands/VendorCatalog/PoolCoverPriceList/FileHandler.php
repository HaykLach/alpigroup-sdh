<?php

namespace App\Console\Commands\VendorCatalog\PoolCoverPriceList;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileHandler
{
    public static function validateInputDirectory(string $directoryPath): bool
    {
        if (! Storage::exists($directoryPath)) {
            $errorMessage = "Directory not found: $directoryPath";
            Log::error($errorMessage);

            return false;
        }

        return true;
    }

    public static function validateFileExists(string $filePath): bool
    {
        if (! Storage::exists($filePath)) {
            $errorMessage = "Pool cover price list file not found: $filePath";
            Log::error($errorMessage);

            return true;
        }

        return false;
    }

    public static function getCsvFilesInDirectory(string $directoryPath): array
    {
        $csvFiles = [];

        try {
            $files = Storage::files($directoryPath);

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                    $csvFiles[] = $file;
                }
            }
        } catch (Exception $e) {
            $errorMessage = "Error reading directory: $directoryPath - ".$e->getMessage();
            Log::error($errorMessage);
        }

        return $csvFiles;
    }

    public static function parseCSV(string $filePath): array
    {
        $result = [];

        $content = self::getCsvFileContent($filePath);
        if ($content === false) {
            return [];
        }

        // Split content into lines
        $lines = explode("\n", $content);

        // Skip the first line (header with "Längenmaße - Außenmaße")
        if (count($lines) < 2) {
            return [];
        }

        // Read the second line to get the length values (column headers)
        $headers = str_getcsv($lines[1], ';');
        $lengths = DataProcessor::extractLengthsFromHeaders($headers);

        // remove last length
        unset($lengths[count($lengths) - 1]);

        // Process data rows
        for ($i = 2; $i < count($lines); $i++) {
            if (empty(trim($lines[$i]))) {
                continue; // Skip empty lines
            }

            $row = str_getcsv($lines[$i], ';');
            DataProcessor::processDataRow($row, $lengths, $result);
        }

        return $result;
    }

    public static function getCsvFileContent(string $filePath)
    {
        try {
            return Storage::get($filePath);
        } catch (Exception $e) {
            $errorMessage = "Could not read file: $filePath - ".$e->getMessage();
            Log::error($errorMessage);

            return false;
        }
    }
}
