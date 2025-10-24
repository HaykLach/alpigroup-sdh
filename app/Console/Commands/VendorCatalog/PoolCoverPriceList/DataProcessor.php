<?php

namespace App\Console\Commands\VendorCatalog\PoolCoverPriceList;

use App\Console\Commands\VendorCatalog\PoolCoverPriceList\DTO\PoolCoverPriceListDTO;
use Exception;
use Illuminate\Support\Facades\Log;

class DataProcessor
{
    public static function getPoolCoverPriceListData(?string $path = null, string $defaultPath = 'pool-cover-pricelists'): PoolCoverPriceListDTO
    {
        $path = $path ?? $defaultPath;
        $result = [];

        try {
            // Check if path is a directory by trying to list files
            try {
                // Process all CSV files in the directory
                $csvFiles = FileHandler::getCsvFilesInDirectory($path);

                if (empty($csvFiles)) {
                    // If no CSV files found, try to process as a single file
                    if (! FileHandler::validateFileExists($path)) {
                        $result = FileHandler::parseCSV($path);
                    } else {
                        Log::warning("No CSV files found in directory: $path");
                    }
                } else {
                    // Process each CSV file
                    foreach ($csvFiles as $csvFile) {
                        if (! FileHandler::validateFileExists($csvFile)) {
                            $fileData = FileHandler::parseCSV($csvFile);
                            $result = self::mergeResults($result, $fileData);
                        }
                    }
                }
            } catch (Exception $e) {
                // If exception occurs when listing files, try to process as a single file
                if (! FileHandler::validateFileExists($path)) {
                    $result = FileHandler::parseCSV($path);
                } else {
                    Log::error("Error processing path: $path - ".$e->getMessage());
                }
            }

            // Convert array result to DTO
            $dto = PoolCoverPriceListDTO::fromArray($result);

            return $dto;
        } catch (Exception $e) {
            Log::error('Error parsing pool cover price list: '.$e->getMessage());

            return new PoolCoverPriceListDTO;
        }
    }

    public static function processPrices(array $row, array $lengths, int $width, int $height, string $productNumber, array &$result): void
    {
        $j = 0;
        for ($i = 3; $i < count($row); $i++) {
            if (! empty($row[$i]) && isset($lengths[$j])) {
                $result[$productNumber][$height][$width][$lengths[$j]] = self::formatPriceValue($row[$i]);
            }
            $j++;
        }
    }

    public static function formatPriceValue(string $value): float
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    public static function mergeResults(array $existingResults, array $newResults): array
    {
        foreach ($newResults as $productNumber => $productData) {
            if (! isset($existingResults[$productNumber])) {
                // If product doesn't exist in results, add it
                $existingResults[$productNumber] = $productData;
            } else {
                // If product exists, merge length data
                foreach ($productData as $key => $value) {
                    if ($key === 'product_number' || $key === 'title') {
                        continue; // Skip metadata
                    }

                    if (! isset($existingResults[$productNumber][$key])) {
                        // If length doesn't exist in results, add it
                        $existingResults[$productNumber][$key] = $value;
                    } else {
                        // If length exists, merge width data
                        $existingResults[$productNumber][$key] = array_merge(
                            $existingResults[$productNumber][$key],
                            $value
                        );
                    }
                }
            }
        }

        return $existingResults;
    }

    public static function processDataRow(array $row, array $lengths, array &$result): void
    {
        if (count($row) < 3) {
            return; // Skip invalid rows
        }

        $productNumber = trim($row[0]);
        $title = trim($row[1]);

        // Skip empty product numbers
        if (empty($productNumber)) {
            return;
        }

        // get height
        $lastColIndex = count($row) - 1;
        $height = self::formatMeterToMillimeterDimension($row[$lastColIndex]);
        // remove last column
        unset($row[$lastColIndex]);

        // Get width from the third column
        $width = self::formatMeterToMillimeterDimension($row[2]);

        // Initialize product if not exists
        if (! isset($result[$productNumber])) {
            $result[$productNumber] = [
                'product_number' => $productNumber,
                'title' => $title,
            ];
        }

        self::processPrices($row, $lengths, $width, $height, $productNumber, $result);
    }

    protected static function formatMeterToMillimeterDimension(string $value): int
    {
        $value = str_replace(',', '', $value);
        $int = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);

        return $int * 10;
    }

    public static function extractLengthsFromHeaders(array $headers): array
    {
        $lengths = [];
        for ($i = 3; $i < count($headers); $i++) {
            if (! empty($headers[$i])) {
                $lengths[$i] = self::formatMeterToMillimeterDimension($headers[$i]);
            }
        }

        return array_values($lengths);
    }
}
